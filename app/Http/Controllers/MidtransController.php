<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Order;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;
use Midtrans\Config;
use Throwable;

/**
 * Class MidtransController
 * * Menangani Webhook/Notifikasi dari Midtrans untuk berbagai jenis transaksi:
 * 1. Reservasi Kamar (BOOK-)
 * 2. Biaya Masuk Awal (EARLY-)
 * 3. Walk-In Langsung (WALKIN-)
 * 4. Pesanan POS/Makanan (ORDER-)
 * 5. Pelunasan saat Checkout (CHECKOUT-)
 */
class MidtransController extends Controller
{
    /**
     * Inisialisasi konfigurasi Midtrans.
     */
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    /**
     * Entry point utama untuk menerima notifikasi dari Midtrans.
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleNotification(Request $request)
    {
        try {
            Log::info('===== MIDTRANS WEBHOOK: MULAI PROSES =====');
            
            // Inisialisasi notifikasi (Midtrans akan memvalidasi signature secara otomatis)
            $notification = new Notification();
            
            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $paymentType = $notification->payment_type;

            Log::info("Notifikasi Diterima - Order ID: $orderId");
            Log::info("Status Transaksi: $transactionStatus | Metode: $paymentType");

            // Routing berdasarkan Prefix Order ID
            if (str_starts_with($orderId, 'BOOK-')) {
                return $this->handleBookingNotification($notification);
            }
            
            if (str_starts_with($orderId, 'EARLY-')) {
                return $this->handleEarlyCheckInNotification($notification);
            }
            
            if (str_starts_with($orderId, 'WALKIN-')) {
                return $this->handleWalkInNotification($notification);
            }
            
            if (str_starts_with($orderId, 'ORDER-')) {
                return $this->handleOrderNotification($notification);
            }
            
            if (str_starts_with($orderId, 'CHECKOUT-')) {
                return $this->handleCheckoutNotification($notification);
            }

            Log::warning("Webhook diterima tapi tipe transaksi tidak dikenali: $orderId");
            return response()->json(['message' => 'Tipe transaksi tidak dikenali.'], 200);
            
        } catch (Throwable $e) {
            Log::error('===== MIDTRANS WEBHOOK: GAGAL =====');
            Log::error('Pesan Error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ' baris ' . $e->getLine());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error saat memproses notifikasi.'
            ], 500);
        }
    }

    /**
     * Menangani Notifikasi untuk Booking/Reservasi Online.
     */
    private function handleBookingNotification(Notification $notification)
    {
        Log::info("Memproses Booking Notification: " . $notification->order_id);
        
        $booking = Booking::where('midtrans_order_id', $notification->order_id)->first();

        if (!$booking) {
            Log::error("Data Booking tidak ditemukan untuk Order ID: " . $notification->order_id);
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($this->determineIfPaid($notification)) {
            DB::transaction(function () use ($booking, $notification) {
                $booking->update([
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'payment_method' => $notification->payment_type ?? 'online'
                ]);
            });
            Log::info("✅ Booking #{$booking->id} lunas.");
        } 
        
        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Biaya Early Check-In (Hanya biaya tambahan saja).
     */
    private function handleEarlyCheckInNotification(Notification $notification)
    {
        Log::info("Memproses Early Fee Notification: " . $notification->order_id);
        
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 2) {
            return response()->json(['message' => 'Invalid ID format'], 400);
        }
        
        $bookingId = $parts[1];
        $booking = Booking::find($bookingId);

        if ($booking && $this->determineIfPaid($notification)) {
            $booking->update(['early_payment_status' => 'paid']);
            Log::info("✅ Biaya Early Check-In untuk Booking #{$bookingId} telah dibayar.");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Pembayaran Walk-In (Harga Kamar + Biaya Early Fee).
     * Disinilah proses Check-In diselesaikan secara otomatis setelah bayar lunas.
     */
    private function handleWalkInNotification(Notification $notification)
    {
        Log::info("Memproses Walk-In Payment Notification: " . $notification->order_id);
        
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 2) {
            return response()->json(['message' => 'Invalid ID format'], 400);
        }
        
        $bookingId = $parts[1];
        $booking = Booking::with('room')->find($bookingId);

        if (!$booking) {
            Log::error("Booking Walk-In tidak ditemukan: " . $bookingId);
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($this->determineIfPaid($notification)) {
            DB::transaction(function () use ($booking, $notification) {
                // 1. Update status booking & pembayaran utama
                $booking->update([
                    'payment_status'       => 'paid',
                    'early_payment_status' => 'paid',
                    'status'               => 'checked_in',
                    'check_in_time'        => now(),
                    'payment_method'       => $notification->payment_type ?? 'qris'
                ]);

                // 2. Buat record Check-In Aktif di sistem
                CheckIn::create([
                    'room_id'        => $booking->room_id,
                    'guest_id'       => $booking->guest_id,
                    'booking_id'     => $booking->id,
                    'check_in_time'  => now(),
                    'is_active'      => true,
                    // Kita baca preferensi incognito dari field 'notes' yang diset di CheckInController
                    'is_incognito'   => $booking->notes === 'INCOGNITO',
                ]);

                // 3. Ubah status fisik kamar menjadi Terisi (Occupied)
                if ($booking->room) {
                    $booking->room->update(['status' => 'occupied']);
                }
            });
            Log::info("✅ Walk-In Lunas & Check-In Selesai: Booking #{$booking->id}");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Notifikasi untuk Pesanan POS / Food & Beverage.
     */
    private function handleOrderNotification(Notification $notification)
    {
        Log::info("Memproses POS Order Notification: " . $notification->order_id);
        
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        if (!$order) {
            Log::error("Order POS tidak ditemukan: " . $notification->order_id);
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($this->determineIfPaid($notification)) {
            $order->update([
                'status'         => 'paid',
                'payment_status' => 'paid',
                'payment_method' => $notification->payment_type ?? 'qris'
            ]);
            Log::info("✅ Pesanan POS #{$order->id} lunas.");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Pelunasan Seluruh Tagihan saat tamu Checkout.
     */
    private function handleCheckoutNotification(Notification $notification)
    {
        Log::info("Memproses Checkout Payment Notification: " . $notification->order_id);
        
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 2) {
            return response()->json(['message' => 'Invalid ID'], 400);
        }
        
        $bookingId = $parts[1];
        $booking = Booking::with('room')->find($bookingId);
        
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($this->determineIfPaid($notification)) {
            DB::transaction(function () use ($booking) {
                // Tandai semua pesanan makanan yang masih gantung menjadi lunas
                Order::where('user_id', $booking->user_id)
                    ->whereIn('status', ['pending', 'processing', 'delivering', 'completed'])
                    ->update(['status' => 'paid']);

                // Selesaikan status booking
                $booking->update(['status' => 'completed']);

                // Set kamar menjadi kotor (dirty)
                if ($booking->room) {
                    $booking->room->update(['status' => 'dirty']);
                }

                // Tutup sesi Check-In
                CheckIn::where('booking_id', $booking->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active'      => false, 
                        'check_out_time' => now()
                    ]);
            });
            Log::info("✅ Proses Checkout Selesai untuk Booking #{$bookingId}");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Helper: Menentukan apakah transaksi dianggap berhasil berdasarkan status dari Midtrans.
     * * @param Notification $notification
     * @return bool
     */
    private function determineIfPaid(Notification $notification)
    {
        $status = $notification->transaction_status;
        $fraud = $notification->fraud_status;

        // Untuk kartu kredit (capture)
        if ($status == 'capture') {
            return ($fraud == 'accept');
        }
        
        // Untuk QRIS, GoPay, Transfer Bank, dll (settlement)
        return ($status == 'settlement');
    }
}