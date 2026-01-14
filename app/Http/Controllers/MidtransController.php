<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Order;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;
use Midtrans\Snap;
use Throwable;

class MidtransController extends Controller
{
    // ... method createTransaction tetap sama ...

    public function handleNotification(Request $request)
    {
        try {
            $notification = new Notification();
            $orderId = $notification->order_id;

            Log::info('Midtrans Notification Received', [
                'order_id' => $orderId,
                'status' => $notification->transaction_status,
                'type' => $notification->payment_type
            ]);

            // 1. Handle Booking (BOOK-...)
            if (str_starts_with($orderId, 'BOOK-')) {
                $this->handleBookingNotification($notification);
            }
            // 2. Handle Early Check-In (EARLY-...)
            else if (str_starts_with($orderId, 'EARLY-')) {
                $this->handleEarlyCheckInNotification($notification);
            }
            // 3. Handle Order Makanan (ORDER-...)
            else if (str_starts_with($orderId, 'ORDER-')) {
                $this->handleOrderNotification($notification);
            }
            // 4. Handle Checkout (CHECKOUT-...)
            else if (str_starts_with($orderId, 'CHECKOUT-')) {
                $this->handleCheckoutNotification($notification);
            }

            return response()->json(['message' => 'Notifikasi berhasil diproses.']);
        } catch (Throwable $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }

    /**
     * ✅ FIX: Handler Booking
     * HANYA update status Booking. JANGAN update status Room jadi Occupied disini.
     */
    private function handleBookingNotification(Notification $notification)
    {
        $booking = Booking::with('guest')->where('midtrans_order_id', $notification->order_id)->first();

        if (!$booking) return;
        if ($booking->payment_status === 'paid') return; // Idempotency

        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;
        $isPaid = false;

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') $isPaid = true;
        } else if ($transactionStatus == 'settlement') {
            $isPaid = true;
        } else if (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $booking->update(['status' => 'cancelled', 'payment_status' => 'failed']);
        }

        if ($isPaid) {
            // Update Status Booking jadi PAID
            // Kamar tetap AVAILABLE sampai nanti Check-In fisik dilakukan
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'paid', // Menunggu Verifikasi Admin
                'payment_method' => $notification->payment_type ?? 'online'
            ]);

            Log::info("Booking Paid: {$booking->id}. Status set to PAID. Room status untouched.");

            // Sync KTP ke Guest (Unverified)
            if ($booking->guest && !empty($booking->ktp_image)) {
                try {
                    $booking->guest->update([
                        'ktp_image' => $booking->ktp_image,
                        'is_verified' => false
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to sync KTP: " . $e->getMessage());
                }
            }
        }
    }

    // ... method handleEarlyCheckInNotification, handleOrderNotification, handleCheckoutNotification tetap sama ...
    // Pastikan method-method lain juga TIDAK mengubah status room sembarangan.

    private function handleEarlyCheckInNotification(Notification $notification)
    {
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 3) return;
        $booking = Booking::find($parts[1]);
        if (!$booking) return;

        if ($notification->transaction_status == 'settlement' || ($notification->transaction_status == 'capture' && $notification->fraud_status == 'accept')) {
            $booking->update(['early_payment_status' => 'paid']);
        }
    }

    private function handleOrderNotification(Notification $notification)
    {
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();
        if (!$order || $order->status === 'paid') return;

        if ($notification->transaction_status == 'settlement' || ($notification->transaction_status == 'capture' && $notification->fraud_status == 'accept')) {
            $order->update(['status' => 'paid', 'payment_status' => 'paid', 'payment_method' => $notification->payment_type ?? 'qris']);
        }
    }

    private function handleCheckoutNotification(Notification $notification)
    {
        // Logic checkout tetap sama seperti sebelumnya (Update room jadi dirty, checkin active jadi false)
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 3) return;
        $booking = Booking::find($parts[1]);
        if (!$booking) return;

        if ($notification->transaction_status == 'settlement' || ($notification->transaction_status == 'capture' && $notification->fraud_status == 'accept')) {
            try {
                DB::transaction(function () use ($booking) {
                    Order::where('user_id', $booking->user_id)
                        ->whereIn('status', ['pending', 'processing', 'delivering', 'completed'])
                        ->update(['status' => 'paid']);

                    $booking->update(['status' => 'completed']);
                    if ($booking->room) $booking->room->update(['status' => 'dirty']);

                    CheckIn::where('booking_id', $booking->id)->where('is_active', true)
                        ->update(['is_active' => false, 'check_out_time' => now()]);
                });
            } catch (Throwable $e) {
                Log::error('Checkout Midtrans Error: ' . $e->getMessage());
            }
        }
    }
}
