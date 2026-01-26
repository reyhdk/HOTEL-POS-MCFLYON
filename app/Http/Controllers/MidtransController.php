<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Order;
use App\Models\CheckIn;
use App\Mail\BookingSuccessMail; // Import Mailable
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; // Import Facade Mail
use Midtrans\Notification;
use Midtrans\Config;
use Throwable;

/**
 * Class MidtransController
 * Menangani Webhook/Notifikasi dari Midtrans untuk berbagai jenis transaksi.
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
     */
    public function handleNotification(Request $request)
    {
        try {
            Log::info('===== MIDTRANS WEBHOOK: MULAI PROSES =====');
            
            $notification = new Notification();
            
            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $paymentType = $notification->payment_type;

            Log::info("Notifikasi Diterima - Order ID: $orderId");
            Log::info("Status Transaksi: $transactionStatus | Metode: $paymentType");

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
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Menangani Notifikasi untuk Booking/Reservasi Online.
     */
    private function handleBookingNotification(Notification $notification)
    {
        Log::info("Memproses Booking Notification: " . $notification->order_id);
        
        $booking = Booking::with(['guest', 'room'])->where('midtrans_order_id', $notification->order_id)->first();

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

            // LOGIKA PENGIRIMAN EMAIL
            try {
                if ($booking->guest && $booking->guest->email) {
                    Mail::to($booking->guest->email)->send(new BookingSuccessMail($booking));
                    Log::info("📧 Email konfirmasi dikirim ke: " . $booking->guest->email);
                }
            } catch (Throwable $mailError) {
                Log::error("❌ Gagal mengirim email: " . $mailError->getMessage());
                // Jangan return error response agar Midtrans tidak mencoba kirim webhook ulang terus menerus
            }

            Log::info("✅ Booking #{$booking->id} lunas.");
        } 
        
        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Biaya Early Check-In.
     */
    private function handleEarlyCheckInNotification(Notification $notification)
    {
        Log::info("Memproses Early Fee Notification: " . $notification->order_id);
        
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 2) return response()->json(['message' => 'Invalid ID'], 400);
        
        $bookingId = $parts[1];
        $booking = Booking::find($bookingId);

        if ($booking && $this->determineIfPaid($notification)) {
            $booking->update(['early_payment_status' => 'paid']);
            Log::info("✅ Biaya Early Check-In #{$bookingId} lunas.");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Pembayaran Walk-In.
     */
    private function handleWalkInNotification(Notification $notification)
    {
        Log::info("Memproses Walk-In Payment Notification: " . $notification->order_id);
        
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 2) return response()->json(['message' => 'Invalid ID'], 400);
        
        $bookingId = $parts[1];
        $booking = Booking::with(['room', 'guest'])->find($bookingId);

        if (!$booking) return response()->json(['message' => 'Booking not found'], 404);

        if ($this->determineIfPaid($notification)) {
            DB::transaction(function () use ($booking, $notification) {
                $booking->update([
                    'payment_status'       => 'paid',
                    'early_payment_status' => 'paid',
                    'status'               => 'checked_in',
                    'check_in_time'        => now(),
                    'payment_method'       => $notification->payment_type ?? 'qris'
                ]);

                CheckIn::create([
                    'room_id'        => $booking->room_id,
                    'guest_id'       => $booking->guest_id,
                    'booking_id'     => $booking->id,
                    'check_in_time'  => now(),
                    'is_active'      => true,
                    'is_incognito'   => $booking->notes === 'INCOGNITO',
                ]);

                if ($booking->room) {
                    $booking->room->update(['status' => 'occupied']);
                }
            });

            // Kirim email juga untuk Walk-In jika mereka memasukkan email
            try {
                if ($booking->guest && $booking->guest->email) {
                    Mail::to($booking->guest->email)->send(new BookingSuccessMail($booking));
                    Log::info("📧 Email Walk-In dikirim ke: " . $booking->guest->email);
                }
            } catch (Throwable $mailError) {
                Log::error("❌ Gagal mengirim email Walk-In: " . $mailError->getMessage());
            }

            Log::info("✅ Walk-In Lunas & Check-In Selesai: Booking #{$booking->id}");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Menangani Notifikasi untuk Pesanan POS.
     */
    private function handleOrderNotification(Notification $notification)
    {
        Log::info("Memproses POS Order Notification: " . $notification->order_id);
        
        $order = Order::where('midtrans_order_id', $notification->order_id)->first();

        if ($order && $this->determineIfPaid($notification)) {
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
     * Menangani Pelunasan saat Checkout.
     */
    private function handleCheckoutNotification(Notification $notification)
    {
        Log::info("Memproses Checkout Payment Notification: " . $notification->order_id);
        
        $parts = explode('-', $notification->order_id);
        if (count($parts) < 2) return response()->json(['message' => 'Invalid ID'], 400);
        
        $bookingId = $parts[1];
        $booking = Booking::with('room')->find($bookingId);
        
        if ($booking && $this->determineIfPaid($notification)) {
            DB::transaction(function () use ($booking) {
                Order::where('user_id', $booking->user_id)
                    ->whereIn('status', ['pending', 'processing', 'delivering', 'completed'])
                    ->update(['status' => 'paid']);

                $booking->update(['status' => 'completed']);

                if ($booking->room) $booking->room->update(['status' => 'dirty']);

                CheckIn::where('booking_id', $booking->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'check_out_time' => now()]);
            });
            Log::info("✅ Proses Checkout Selesai untuk Booking #{$bookingId}");
        }

        return response()->json(['message' => 'Success']);
    }

    /**
     * Helper: Menentukan apakah transaksi dianggap berhasil.
     */
    private function determineIfPaid(Notification $notification)
    {
        $status = $notification->transaction_status;
        $fraud = $notification->fraud_status;

        if ($status == 'capture') {
            return ($fraud == 'accept');
        }
        
        return ($status == 'settlement');
    }
}