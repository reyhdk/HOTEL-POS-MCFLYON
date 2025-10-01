<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Booking;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Throwable;

class MidtransController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        if (App::environment('local')) {
            Config::$curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }
    }

    public function createTransaction(Request $request)
    {
        // Fungsi ini untuk transaksi selain booking (misal: order makanan)
        $request->validate(['order_id' => 'required|exists:orders,id']);
        try {
            $order = Order::with(['user', 'items.product'])->findOrFail($request->order_id);
            if (auth()->id() !== $order->user_id) {
                return response()->json(['message' => 'Tidak diizinkan.'], 403);
            }
            $item_details = [];
            foreach ($order->items as $item) {
                $item_details[] = ['id' => $item->product->id, 'price' => (int) $item->price, 'quantity' => (int) $item->quantity, 'name' => $item->product->name];
            }
            $params = [
                'transaction_details' => ['order_id' => 'ORDER-' . $order->id . '-' . time(), 'gross_amount' => (int) $order->total_price],
                'customer_details' => ['first_name' => $order->user->name, 'email' => $order->user->email, 'phone' => $order->user->phone],
                'item_details' => $item_details,
            ];
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        } catch (Throwable $e) {
            Log::error('Midtrans Snap Token Error (Order): ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat transaksi pembayaran.'], 500);
        }
    }

    public function handleNotification(Request $request)
    {
        $payload = $request->all();
        $signatureKey = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('midtrans.server_key'));

        if ($payload['signature_key'] !== $signatureKey) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $midtransOrderId = $payload['order_id'];
        $transactionStatus = $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        if (str_starts_with($midtransOrderId, 'BOOK-')) {
            $booking = Booking::where('midtrans_order_id', $midtransOrderId)->first();
            if (!$booking) {
                return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
            }

            if (($transactionStatus == 'capture' || $transactionStatus == 'settlement') && $fraudStatus == 'accept') {
                if ($booking->status === 'pending') {
                    $booking->status = 'paid';
                    CheckIn::create(['room_id' => $booking->room_id, 'guest_id' => $booking->guest_id, 'booking_id' => $booking->id, 'check_in_time' => now(), 'is_active' => true]);
                    $booking->room()->update(['status' => 'occupied']);
                }
            } else if (in_array($transactionStatus, ['cancel', 'expire', 'deny'])) {
                if ($booking->status === 'pending') {
                    $booking->status = 'cancelled';
                    $booking->room()->update(['status' => 'available']);
                }
            }
            $booking->save();
        } else {
            $orderId = explode('-', $midtransOrderId)[1];
            $order = Order::find($orderId);
            if (!$order) {
                return response()->json(['message' => 'Order tidak ditemukan.'], 404);
            }
            if (($transactionStatus == 'capture' || $transactionStatus == 'settlement') && $fraudStatus == 'accept') {
                $order->status = 'paid';
            } else if ($transactionStatus == 'pending') {
                $order->status = 'pending';
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $order->status = 'cancelled';
            }
            $order->save();
        }

        return response()->json(['message' => 'Notifikasi berhasil diproses.']);
    }
}