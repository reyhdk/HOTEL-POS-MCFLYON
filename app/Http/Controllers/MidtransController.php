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
            Log::error('Midtrans Notification Error: ' . $e->getMessage() . ' for Order ID: ' . ($notification->order_id ?? 'N/A'));
            return response()->json(['message' => 'Gagal memproses notifikasi.'], 500);
        }
    }

    /**
     * [PERBAIKAN] Menangani notifikasi pembayaran checkout.
     */
    private function handleCheckoutNotification(Notification $notification)
    {
        // Format order_id: CHECKOUT-{booking_id}-{timestamp}
        // Contoh: CHECKOUT-123-1634567890
        $parts = explode('-', $notification->order_id);

        if (count($parts) < 3) {
            Log::error('Invalid checkout order_id format: ' . $notification->order_id);
            return;
        }

        $bookingId = $parts[1]; // Ambil booking_id dari order_id

        // Cari booking berdasarkan ID yang di-extract
        $booking = Booking::find($bookingId);

        if (!$booking) {
            Log::warning('Booking tidak ditemukan untuk checkout: ' . $notification->order_id);
            return;
        }

        // Update midtrans_checkout_id jika belum ada
        if (!$booking->midtrans_checkout_id) {
            $booking->midtrans_checkout_id = $notification->order_id;
            $booking->save();
        }

        // Cek apakah checkout ini sudah pernah diproses
        // dengan mengecek apakah check-in sudah tidak aktif
        $checkIn = CheckIn::where('booking_id', $booking->id)->first();

        if ($checkIn && !$checkIn->is_active && $checkIn->check_out_time) {
            Log::info('Checkout sudah diproses sebelumnya: ' . $notification->order_id);
            return;
        }

        // Hanya proses jika transaksi sukses
        $isSuccess = in_array($notification->transaction_status, ['capture', 'settlement'])
                     && ($notification->fraud_status == 'accept' || !isset($notification->fraud_status));

        if (!$isSuccess) {
            Log::info('Transaction not successful', [
                'order_id' => $notification->order_id,
                'status' => $notification->transaction_status,
                'fraud' => $notification->fraud_status ?? 'N/A'
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($booking, $notification) {
                // Definisikan semua status tagihan yang perlu dibayar
                $billableStatuses = ['pending', 'processing', 'delivering', 'completed'];

                // Update SEMUA order milik user ini yang masih unpaid
                $updatedOrders = Order::where('user_id', $booking->user_id)
                     ->whereIn('status', $billableStatuses)
                     ->update(['status' => 'paid']);

                Log::info('Orders updated to paid', [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'orders_updated' => $updatedOrders
                ]);

                // Update booking status
                $booking->update(['status' => 'completed']);

                // Update room status
                if ($booking->room) {
                    $booking->room->update(['status' => 'dirty']);
                }

                // Nonaktifkan check-in
                CheckIn::where('booking_id', $booking->id)
                       ->where('is_active', true)
                       ->update([
                           'is_active' => false,
                           'check_out_time' => now()
                       ]);

                Log::info('Checkout completed successfully', [
                    'booking_id' => $booking->id,
                    'order_id' => $notification->order_id
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Error processing checkout notification: ' . $e->getMessage(), [
                'order_id' => $notification->order_id,
                'booking_id' => $booking->id
            ]);
            throw $e;
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
                $booking->status = 'completed';
                $booking->save();
                $booking->room()->update(['status' => 'occupied']);

                CheckIn::firstOrCreate(
                    ['booking_id' => $booking->id],
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
        // Cari order berdasarkan ID unik yang tadi kita simpan
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        // [PERBAIKAN] Jangan tolak jika statusnya 'processing', 'delivering', atau 'completed'.
        // Hanya tolak jika pesanan tidak ditemukan atau SUDAH LUNAS.
        if (!$order || $order->status === 'paid') {
            return;
        }

        // Cek status transaksi dari Midtrans
        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;

        $isPaid = false;

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                // Handle challenge
            } else if ($fraudStatus == 'accept') {
                $isPaid = true;
            }
        } else if ($transactionStatus == 'settlement') {
            $isPaid = true;
        } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $order->status = 'cancelled';
        } else if ($transactionStatus == 'pending') {
            // Menunggu pembayaran, biarkan saja
        }

        if ($isPaid) {
            // [PENTING] Update status jadi PAID dan catat methodnya
            $order->update([
                'status' => 'paid',
                'payment_method' => 'qris', // atau 'midtrans'
                'updated_at' => now()
            ]);
            
            // Opsional: Jika ada logic lain (seperti notifikasi ke dapur), masukkan di sini
        }

        $order->save();
    }
}
