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
use Midtrans\Snap;
use Throwable;

class MidtransController extends Controller
{
    /**
     * Membuat transaksi Midtrans untuk berbagai jenis pesanan.
     */
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
                throw new \Exception('Tipe pesanan tidak valid.');
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
            } else if (str_starts_with($notification->order_id, 'CHECKOUT-')) {

            return response()->json(['message' => 'Notifikasi berhasil diproses.']);
            }
        } catch (Throwable $e) {
            Log::error('Midtrans Notification Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }

    /**
 * [FUNGSI BARU] Menangani notifikasi pembayaran checkout.
 */
private function handleCheckoutNotification(Notification $notification)
{
    // Cari booking berdasarkan ID checkout yang kita simpan
    $booking = Booking::where('midtrans_checkout_id', $notification->order_id)->first();

    if (!$booking) return;

    if (($notification->transaction_status == 'capture' || $notification->transaction_status == 'settlement') && $notification->fraud_status == 'accept') {
        DB::transaction(function () use ($booking) {
            // 1. Ubah status semua pesanan 'unpaid' menjadi 'paid'
            Order::where('user_id', $booking->user_id)->where('status', 'unpaid')->update(['status' => 'paid']);

            // 2. Tandai booking sebagai selesai
            $booking->update(['status' => 'completed']);

            // 3. Ubah status kamar menjadi 'dirty'
            $booking->room()->update(['status' => 'dirty']);

            // 4. Nonaktifkan sesi check-in
            CheckIn::where('booking_id', $booking->id)->where('is_active', true)->update(['is_active' => false, 'check_out_time' => now()]);
        });
    }}

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

                // --- PERBAIKAN UTAMA DI SINI ---
                // Gunakan firstOrCreate untuk mencegah data check-in duplikat
                CheckIn::firstOrCreate(
                    ['booking_id' => $booking->id], // Cari berdasarkan booking_id
                    [
                        'room_id' => $booking->room_id,
                        'guest_id' => $booking->guest_id,
                        'check_in_time' => now(),
                        'is_active' => true
                    ]
                );
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
            GuestOrderController::handleSuccessfulPayment($order);
            $order->status = 'paid';
        } else if (in_array($notification->transaction_status, ['cancel', 'expire', 'deny'])) {
            $order->status = 'cancelled';
        }

        $order->save();
    }
}
