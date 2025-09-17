<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan yang masuk.
     */
    public function index()
    {
        // Ambil semua pesanan, urutkan dari yang terbaru
        // Muat juga relasi ke kamar, user/tamu, dan item-itemnya
        $orders = Order::with(['room', 'user', 'items.menu'])
                        ->latest()
                        ->get();

        return response()->json($orders);
    }
    public function show(Order $order)
    {
    // Muat semua relasi yang dibutuhkan untuk halaman detail
    return $order->load(['room', 'user', 'items.menu']);
    }

    public function updateStatus(Request $request, Order $order)
{
    $validated = $request->validate([
        'status' => 'required|string|in:processing,delivering,completed,cancelled',
    ]);

    $order->status = $validated['status'];
    $order->save();

    return response()->json([
        'message' => 'Status pesanan berhasil diubah.',
        'order' => $order,
    ]);
}
}
