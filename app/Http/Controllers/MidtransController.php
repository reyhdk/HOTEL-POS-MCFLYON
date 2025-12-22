<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Guest\GuestOrderController;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Order;
use App\Models\Guest; // [TAMBAHKAN IMPORT INI]
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;
use Midtrans\Snap;
use Throwable;

class MidtransController extends Controller
{
 
    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'order_type' => 'required|string|in:FOOD_ORDER,BOOKING',
        ]);

        try {
            $order = null;
            $user = $request->user();
            $midtransOrderId = '';

            if ($validated['order_type'] === 'FOOD_ORDER') {
                $order = Order::findOrFail($validated['order_id']);
                if ($order->user_id !== $user->id || $order->status !== 'pending') {
                    throw new \Exception('Pesanan makanan tidak valid untuk pembayaran.');
                }
                $midtransOrderId = 'ORDER-' . $order->id . '-' . time();
            }

            if (!$order) {
                throw new \Exception('Logic booking create transaction ada di BookingController store.');
            }

            $order->midtrans_order_id = $midtransOrderId;
            $order->save();

            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $order->total_price,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
            ];

            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        } catch (Throwable $e) {
            Log::error('Gagal membuat transaksi Midtrans: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memulai pembayaran: ' . $e->getMessage()], 500);
        }
    }


    public function handleNotification(Request $request)
    {
        try {
            $notification = new Notification();
            $orderId = $notification->order_id;

            Log::info('Midtrans Notification Received', [
                'order_id' => $orderId,
                'transaction_status' => $notification->transaction_status,
                'fraud_status' => $notification->fraud_status ?? 'N/A'
            ]);

            if (str_starts_with($orderId, 'BOOK-')) {
                $this->handleBookingNotification($notification);
            } else if (str_starts_with($orderId, 'ORDER-')) {
                $this->handleOrderNotification($notification);
            } else if (str_starts_with($orderId, 'CHECKOUT-')) {
                $this->handleCheckoutNotification($notification);
            }

            return response()->json(['message' => 'Notifikasi berhasil diproses.']);
        } catch (Throwable $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }


    private function handleBookingNotification(Notification $notification)
    {
        // 1. PENTING: Eager load relasi 'guest' untuk memastikan sync KTP berhasil
        $booking = Booking::with('guest')->where('midtrans_order_id', $notification->order_id)->first();

        if (!$booking) {
            Log::warning("Booking not found for Midtrans Order: {$notification->order_id}");
            return;
        }

        // 2. Cek Idempotency (Hindari double processing)
        if (in_array($booking->status, ['paid', 'confirmed', 'checked_in', 'cancelled', 'completed'])) {
            Log::info("Booking {$booking->id} already processed. Status: {$booking->status}");
            return;
        }

        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;
        $newStatus = null;

        // 3. Tentukan Status Baru
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $newStatus = 'pending';
            } else if ($fraudStatus == 'accept') {
                $newStatus = 'paid';
            }
        } else if ($transactionStatus == 'settlement') {
            $newStatus = 'paid';
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $newStatus = 'cancelled';
        } else if ($transactionStatus == 'pending') {
            $newStatus = 'pending';
        }

        if ($newStatus) {
            $booking->status = $newStatus;

            // 4. ✅ FIX UTAMA: Sync KTP ke Guest Profile SAAT PEMBAYARAN LUNAS
            if ($newStatus === 'paid') {

                // Validasi: Pastikan relasi guest ter-load & KTP ada
                if (!$booking->guest) {
                    Log::error("Guest not loaded for Booking {$booking->id}. Cannot sync KTP.");
                } elseif (empty($booking->ktp_image)) {
                    Log::warning("Booking {$booking->id} tidak punya ktp_image. Skip sync.");
                } else {
                    try {
                        // Update Guest Profile
                        $booking->guest->update([
                            'ktp_image' => $booking->ktp_image,
                            'is_verified' => false  // Trigger masuk ke halaman verifikasi
                        ]);

                        Log::info("✅ KTP Synced: Booking {$booking->id} → Guest {$booking->guest_id}");
                    } catch (\Exception $e) {
                        Log::error("Failed to sync KTP for Booking {$booking->id}: " . $e->getMessage());
                    }
                }
            }

            $booking->save();
            Log::info("Booking {$booking->id} status updated to {$newStatus}");
        }
    }

    private function handleOrderNotification(Notification $notification)
    {
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        if (!$order || $order->status === 'paid') {
            return;
        }

        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;
        $isPaid = false;

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                $isPaid = true;
            }
        } else if ($transactionStatus == 'settlement') {
            $isPaid = true;
        } else if (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $order->status = 'cancelled';
        }

        if ($isPaid) {
            $order->update([
                'status' => 'paid',
                'payment_method' => 'qris',
                'updated_at' => now()
            ]);
        }
        $order->save();
    }

    private function handleCheckoutNotification(Notification $notification)
    {
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 3) return;

        $bookingId = $parts[1];
        $booking = Booking::find($bookingId);

        if (!$booking) return;

        if (!$booking->midtrans_checkout_id) {
            $booking->midtrans_checkout_id = $notification->order_id;
            $booking->save();
        }

        $checkIn = CheckIn::where('booking_id', $booking->id)->first();
        if ($checkIn && !$checkIn->is_active && $checkIn->check_out_time) {
            return;
        }

        $isSuccess = in_array($notification->transaction_status, ['capture', 'settlement'])
            && ($notification->fraud_status == 'accept' || !isset($notification->fraud_status));

        if (!$isSuccess) return;

        try {
            DB::transaction(function () use ($booking, $notification) {
                Order::where('user_id', $booking->user_id)
                    ->whereIn('status', ['pending', 'processing', 'delivering', 'completed'])
                    ->update(['status' => 'paid']);

                $booking->update(['status' => 'completed']);

                if ($booking->room) {
                    $booking->room->update(['status' => 'dirty']);
                }

                CheckIn::where('booking_id', $booking->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'check_out_time' => now()
                    ]);
            });
            Log::info('Checkout processed via Midtrans for Booking ' . $booking->id);
        } catch (Throwable $e) {
            Log::error('Checkout Midtrans Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
