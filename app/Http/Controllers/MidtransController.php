<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckIn;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- 1. Tambahkan ini
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
            } else {
                $this->handleOrderNotification($notification);
            }

            return response()->json(['message' => 'Notifikasi berhasil diproses.']);

        } catch (Throwable $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }

    /**
     * Memproses notifikasi khusus untuk Booking.
     */
    private function handleBookingNotification(Notification $notification)
    {
        $booking = Booking::where('midtrans_order_id', $notification->order_id)->first();

        // Abaikan jika booking tidak ditemukan atau statusnya bukan 'pending'
        if (!$booking || $booking->status !== 'pending') {
            return;
        }

        if (($notification->transaction_status == 'capture' || $notification->transaction_status == 'settlement') && $notification->fraud_status == 'accept') {

            // --- PERBAIKAN UTAMA DI SINI ---
            // Gunakan DB Transaction untuk memastikan semua proses berhasil atau tidak sama sekali.
            DB::transaction(function () use ($booking) {
                // 1. Ubah status booking menjadi 'paid'
                $booking->status = 'paid';
                $booking->save();

                // 2. Ubah status kamar terkait menjadi 'occupied'
                $booking->room()->update(['status' => 'occupied']);

                // 3. Buat record check-in baru
                CheckIn::create([
                    'room_id' => $booking->room_id,
                    'guest_id' => $booking->guest_id,
                    'booking_id' => $booking->id,
                    'check_in_time' => now(),
                    'is_active' => true
                ]);
            });

        } else if (in_array($notification->transaction_status, ['cancel', 'expire', 'deny'])) {
            // Jika pembayaran dibatalkan atau gagal
            $booking->status = 'cancelled';
            $booking->save();
            // Tidak ada lagi yang perlu dilakukan pada kamar, karena statusnya tidak pernah berubah.
        }
    }

    /**
     * Memproses notifikasi untuk Order lain (contoh: makanan).
     */
    private function handleOrderNotification(Notification $notification)
    {
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        if (!$order) {
            Log::warning("Order dengan Midtrans Order ID {$notification->order_id} tidak ditemukan.");
            return;
        }
        // ... logika untuk order
    }
}
