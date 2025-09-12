<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function getPendingOrders()
    {
        $orders = Order::where('status', 'pending')
                        ->with('room', 'items.menu')
                        ->latest()
                        ->get();
        return response()->json($orders);
    }

    public function processPayment(Request $request, Order $order)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // 1. Ubah status pesanan menjadi 'completed'
            $order->status = 'completed';
            $order->save();

            // 2. LOGIKA PENGURANGAN STOK DIHAPUS DARI SINI
            // Stok sudah dikurangi saat pesanan dibuat.

            DB::commit();
            return response()->json(['message' => 'Pembayaran berhasil.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses pembayaran.', 'error' => $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Hanya pesanan yang belum lunas yang bisa dibatalkan.'], 400);
        }

        DB::beginTransaction();
        try {
            // ==================================================
            // PERUBAHAN DI SINI: Stok dikembalikan saat pesanan dibatalkan
            // ==================================================
            foreach ($order->items as $item) {
                // 'increment' adalah cara aman untuk menambah nilai di database
                $item->menu()->increment('stock', $item->quantity);
            }
            // ==================================================

            // Ubah status pesanan menjadi 'cancelled'
            $order->status = 'cancelled';
            $order->save();

            DB::commit();

            return response()->json(['message' => 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan pesanan.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getTransactionHistory()
    {
        $orders = Order::whereIn('status', ['completed', 'cancelled'])
                        ->with('room', 'items.menu')




                        
                        ->latest()
                        ->get();
        return response()->json($orders);
    }
}