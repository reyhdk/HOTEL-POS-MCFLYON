<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Order;
use App\Models\CheckIn;
use App\Models\WarehouseItem; 
use App\Models\CashFlow; // [TAMBAHAN] Import CashFlow
use App\Mail\BookingSuccessMail; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Str; 
use Midtrans\Notification;
use Midtrans\Config;
use Throwable;

class MidtransController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

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
            if (str_starts_with($orderId, 'CHECKOUT-')) {
                return $this->handleCheckoutNotification($notification);
            }
            if (str_starts_with($orderId, 'ORD/') || str_starts_with($orderId, 'ORDER-')) {
                return $this->handleOrderNotification($notification);
            }

            Log::warning("Webhook diterima tapi tipe transaksi tidak dikenali: $orderId");
            return response()->json(['message' => 'Tipe transaksi tidak dikenali.'], 200);
            
        } catch (Throwable $e) {
            Log::error('===== MIDTRANS WEBHOOK: GAGAL =====');
            Log::error('Pesan Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    private function handleBookingNotification(Notification $notification)
    {
        Log::info("Memproses Booking Notification: " . $notification->order_id);
        
        $booking = Booking::with(['guest', 'room'])->where('midtrans_order_id', $notification->order_id)->first();

        if (!$booking) {
            Log::error("Data Booking tidak ditemukan untuk Order ID: " . $notification->order_id);
            return response()->json(['message' => 'Booking not found'], 404);
        }

        if ($this->determineIfPaid($notification) && $booking->status !== 'paid') {
            DB::transaction(function () use ($booking, $notification) {
                $booking->update([
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'payment_method' => $notification->payment_type ?? 'online'
                ]);

                // [TAMBAHAN] OTOMATIS CATAT CASH FLOW
                CashFlow::create([
                    'transaction_date' => now(),
                    'type' => 'income',
                    'category' => 'booking',
                    'description' => 'Pelunasan Booking Kamar Online',
                    'payment_method' => 'Midtrans ('.$notification->payment_type.')',
                    'amount' => $notification->gross_amount ?? $booking->total_price,
                    'reference_id' => $notification->order_id,
                    'user_id' => 1 // System/Midtrans
                ]);
            });

            try {
                if ($booking->guest && $booking->guest->email) {
                    Mail::to($booking->guest->email)->send(new BookingSuccessMail($booking));
                    Log::info("📧 Email konfirmasi dikirim ke: " . $booking->guest->email);
                }
            } catch (Throwable $mailError) {
                Log::error("❌ Gagal mengirim email: " . $mailError->getMessage());
            }

            Log::info("✅ Booking #{$booking->id} lunas.");
        } 
        
        return response()->json(['message' => 'Success']);
    }

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

                // [TAMBAHAN] OTOMATIS CATAT CASH FLOW
                CashFlow::create([
                    'transaction_date' => now(),
                    'type' => 'income',
                    'category' => 'booking',
                    'description' => 'Pembayaran Kamar Walk-In',
                    'payment_method' => 'Midtrans ('.$notification->payment_type.')',
                    'amount' => $notification->gross_amount ?? $booking->total_price,
                    'reference_id' => $notification->order_id,
                    'user_id' => 1
                ]);
            });

            try {
                if ($booking->guest && $booking->guest->email) {
                    Mail::to($booking->guest->email)->send(new BookingSuccessMail($booking));
                }
            } catch (Throwable $mailError) {}

            Log::info("✅ Walk-In Lunas & Check-In Selesai: Booking #{$booking->id}");
        }

        return response()->json(['message' => 'Success']);
    }

    private function handleOrderNotification(Notification $notification)
    {
        Log::info("Memproses POS Order Notification: " . $notification->order_id);
        
        $order = Order::where('midtrans_order_id', $notification->order_id)
                      ->orWhere('order_code', $notification->order_id)
                      ->first();

        if ($order && $this->determineIfPaid($notification)) {
            
            if ($order->status !== 'paid') {
                DB::transaction(function () use ($order, $notification) {
                    $order->update([
                        'status'         => 'paid',
                        'payment_status' => 'paid',
                        'payment_method' => $notification->payment_type ?? 'qris'
                    ]);

                    $this->deductIngredients($order);

                    // [TAMBAHAN] OTOMATIS CATAT CASH FLOW
                    CashFlow::create([
                        'transaction_date' => now(),
                        'type' => 'income',
                        'category' => 'resto',
                        'description' => 'Pelunasan Pesanan Resto',
                        'payment_method' => 'Midtrans ('.$notification->payment_type.')',
                        'amount' => $notification->gross_amount ?? $order->total_price,
                        'reference_id' => $notification->order_id,
                        'user_id' => 1
                    ]);
                });
                
                Log::info("✅ Pesanan POS #{$order->id} lunas & Bahan Baku dipotong (Midtrans).");
            }
        }

        return response()->json(['message' => 'Success']);
    }

    private function deductIngredients(Order $order)
    {
        $order->load('items.menu.ingredients');
        foreach ($order->items as $orderItem) {
            $menu = $orderItem->menu;
            if ($menu->stock >= $orderItem->quantity) $menu->decrement('stock', $orderItem->quantity);

            if ($menu->ingredients) {
                foreach ($menu->ingredients as $ingredient) {
                    $totalDeduction = $ingredient->pivot->quantity * $orderItem->quantity;
                    $warehouseItem = WarehouseItem::find($ingredient->id);
                    if ($warehouseItem) {
                        $warehouseItem->decrement('current_stock', $totalDeduction);
                        DB::table('stock_transactions')->insert([
                            'transaction_code' => 'OUT/' . date('Ymd') . '/' . strtoupper(Str::random(5)),
                            'warehouse_item_id' => $warehouseItem->id,
                            'transaction_type' => 'out',
                            'quantity' => $totalDeduction,
                            'unit_price' => $warehouseItem->cost_price,
                            'total_price' => $totalDeduction * $warehouseItem->cost_price,
                            'reference_type' => 'sale',
                            'notes' => "Terjual via Pesanan F&B #{$order->order_code} (Menu: {$menu->name}) - Midtrans",
                            'transaction_date' => now(),
                            'created_by' => $order->user_id ?? 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        }
    }

    private function handleCheckoutNotification(Notification $notification)
    {
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
        }
        return response()->json(['message' => 'Success']);
    }

    private function determineIfPaid(Notification $notification)
    {
        $status = $notification->transaction_status;
        $fraud = $notification->fraud_status;
        if ($status == 'capture') {
            return ($fraud == 'accept');
        }
        return ($status == 'settlement' || $status == 'capture');
    }
}