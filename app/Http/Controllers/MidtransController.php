<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Guest\GuestOrderController;
use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;
use Throwable;

class MidtransController extends Controller
{
    /**
     * Menangani notifikasi (webhook) yang dikirim oleh Midtrans.
     */
    public function handleNotification(Request $request)
    {
        try {
            $notification = new Notification();

            if (str_starts_with($notification->order_id, 'BOOK-')) {
                $this->handleBookingNotification($notification);
            } else if (str_starts_with($notification->order_id, 'ORDER-')) {
                $this->handleOrderNotification($notification);
            }

            return response()->json(['message' => 'Notifikasi berhasil diproses.']);

        } catch (Throwable $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }

    /**
     * Memproses notifikasi khusus untuk Booking Kamar.
     */
    private function handleBookingNotification(Notification $notification)
    {
        $booking = Booking::where('midtrans_order_id', $notification->order_id)->first();

        if (!$booking || $booking->status !== 'pending') {
            return;
        }

        if (($notification->transaction_status == 'capture' || $notification->transaction_status == 'settlement') && $notification->fraud_status == 'accept') {
            DB::transaction(function () use ($booking) {
                $booking->status = 'paid';
                $booking->save();

                $booking->room()->update(['status' => 'occupied']);

                CheckIn::create([
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'booking_id' => $booking->id,
                    'check_in_time' => now(),
                    'is_active' => true
                ]);
            });
        } else if (in_array($notification->transaction_status, ['cancel', 'expire', 'deny'])) {
            $booking->status = 'cancelled';
            $booking->save();
        }
    }

    /**
     * Memproses notifikasi khusus untuk Order Makanan.
     */
    private function handleOrderNotification(Notification $notification)
    {
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        if (!$order || $order->status !== 'pending') {
            return;
        }

        if (($notification->transaction_status == 'capture' || $notification->transaction_status == 'settlement') && $notification->fraud_status == 'accept') {
            // Panggil fungsi untuk mengurangi stok dari GuestOrderController
            GuestOrderController::handleSuccessfulPayment($order);
            
            // Ubah status pesanan
            $order->status = 'paid'; // atau 'processing' sesuai alur dapur Anda
            
        } else if (in_array($notification->transaction_status, ['cancel', 'expire', 'deny'])) {
            $order->status = 'cancelled';
        }
        
        $order->save();
    }
}