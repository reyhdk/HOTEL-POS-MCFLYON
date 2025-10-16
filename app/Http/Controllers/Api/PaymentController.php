<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Mendapatkan daftar pesanan yang menunggu pembayaran
     */
    public function getPendingOrders()
    {
        // PERBAIKAN: Tampilkan semua order yang belum dibayar
        $billableStatuses = ['pending', 'processing', 'delivering'];

        $orders = Order::whereIn('status', $billableStatuses)
                        ->with('room', 'items.menu', 'user')
                        ->latest()
                        ->get();
        return response()->json($orders);
    }

    /**
     * Memproses pembayaran pesanan individual (bukan melalui folio)
     */
    public function processPayment(Request $request, Order $order)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        // PERBAIKAN: Cek apakah order masih bisa dibayar
        $billableStatuses = ['pending', 'processing', 'delivering'];

        if (!in_array($order->status, $billableStatuses)) {
            return response()->json(['message' => 'Pesanan ini tidak bisa dibayar (status: ' . $order->status . ')'], 400);
        }

        DB::beginTransaction();

        try {
            // Ubah status menjadi 'paid' (konsisten dengan folio)
            $order->status = 'paid';
            $order->save();

            DB::commit();
            return response()->json(['message' => 'Pembayaran berhasil.', 'order' => $order]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses pembayaran.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Membatalkan pesanan yang masih pending
     */
    public function cancelOrder(Order $order)
    {
        // PERBAIKAN: Hanya order yang belum dibayar bisa dibatalkan
        $billableStatuses = ['pending', 'processing', 'delivering'];

        if (!in_array($order->status, $billableStatuses)) {
            return response()->json(['message' => 'Hanya pesanan yang belum dibayar yang bisa dibatalkan.'], 400);
        }

        DB::beginTransaction();
        try {
            // Kembalikan stok
            foreach ($order->items as $item) {
                $item->menu()->increment('stock', $item->quantity);
            }

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

    /**
     * Mendapatkan riwayat transaksi yang sudah selesai
     */
    public function getTransactionHistory()
    {
        // PERBAIKAN: Tambahkan 'paid' ke dalam filter
        $orders = Order::whereIn('status', ['paid', 'cancelled'])
                        ->with('room', 'items.menu', 'user')
                        ->latest()
                        ->get();
        return response()->json($orders);
    }
}
