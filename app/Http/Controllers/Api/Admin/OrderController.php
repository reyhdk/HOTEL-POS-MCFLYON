<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['room', 'user', 'guest', 'items.menu'])
                        ->latest()
                        ->get();

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        return $order->load(['room', 'user', 'guest', 'items.menu']);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:processing,delivering,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status operasional berhasil diubah.',
            'order' => $order,
        ]);
    }

    public function markAsPaid(Request $request, Order $order)
    {
        // Tambahkan 'midtrans_success' ke validasi
        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,qris,midtrans_success', 
        ]);

        if ($order->status === 'paid') {
            return response()->json(['message' => 'Pesanan ini sudah lunas.'], 200);
        }

        // --- SKENARIO 1: PEMBAYARAN TUNAI ATAU KONFIRMASI SUKSES MIDTRANS ---
        // Jika admin pilih Cash, ATAU jika Frontend melapor Midtrans sudah sukses
        if ($validated['payment_method'] === 'cash' || $validated['payment_method'] === 'midtrans_success') {
            try {
                DB::transaction(function () use ($order, $validated) {
                    $method = $validated['payment_method'] === 'cash' ? 'cash' : 'qris';
                    
                    $order->update([
                        'status' => 'paid',
                        'payment_method' => $method,
                        'updated_at' => now(),
                    ]);
                });

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran berhasil dikonfirmasi.',
                    'order' => $order
                ]);

            } catch (\Exception $e) {
                return response()->json(['message' => 'Gagal update database: ' . $e->getMessage()], 500);
            }
        }

        // --- SKENARIO 2: REQUEST QRIS BARU (Minta Token) ---
        if ($validated['payment_method'] === 'qris') {
            try {
                // Konfigurasi Midtrans
                \Midtrans\Config::$serverKey = config('midtrans.server_key');
                \Midtrans\Config::$isProduction = config('midtrans.is_production');
                \Midtrans\Config::$isSanitized = true;
                \Midtrans\Config::$is3ds = true;

                $transactionOrderId = 'POS-' . $order->id . '-' . time();

                // Simpan ID Transaksi Midtrans
                $order->update(['midtrans_order_id' => $transactionOrderId]);

                $params = [
                    'transaction_details' => [
                        'order_id' => $transactionOrderId,
                        'gross_amount' => (int) $order->total_price,
                    ],
                    'customer_details' => [
                        'first_name' => $order->user ? $order->user->name : ($order->guest ? $order->guest->name : 'Guest'),
                        'email' => 'guest@hotel.com', 
                        'phone' => '08123456789',
                    ],
                    'item_details' => $order->items->map(function ($item) {
                        return [
                            'id' => 'MENU-' . $item->menu_id,
                            'price' => (int) $item->price,
                            'quantity' => $item->quantity,
                            'name' => substr($item->menu->name, 0, 50),
                        ];
                    })->toArray(),
                    'enabled_payments' => ['gopay', 'other_qris'],
                ];

                $snapToken = \Midtrans\Snap::getSnapToken($params);

                return response()->json([
                    'status' => 'midtrans_initiated',
                    'snap_token' => $snapToken,
                ]);

            } catch (\Exception $e) {
                return response()->json(['message' => 'Midtrans Error: ' . $e->getMessage()], 500);
            }
        }
    }
}