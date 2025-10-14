<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn; // <-- Tambahkan ini
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // <-- Tambahkan ini
use Throwable; // <-- Tambahkan ini

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // --- LOGIKA BARU UNTUK MENCARI TAMU AKTIF ---
            $activeCheckIn = CheckIn::where('room_id', $validated['room_id'])
                                    ->where('is_active', true)
                                    ->with('booking') // Muat relasi booking
                                    ->first();

            if (!$activeCheckIn) {
                throw new \Exception("Tidak ada sesi check-in yang aktif untuk kamar ini.");
            }
            // ---------------------------------------------

            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);

                if ($menu->stock < $item['quantity']) {
                    throw new \Exception("Stok untuk menu '{$menu->name}' tidak mencukupi.");
                }

                $totalPrice += $menu->price * $item['quantity'];
                $orderItemsData[] = [ 'menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price ];
            }

            $order = Order::create([
                'room_id' => $validated['room_id'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                // --- SIMPAN DATA TAMU & USER ---
                'user_id' => $activeCheckIn->booking->user_id, // Ambil user_id dari booking
                'guest_id' => $activeCheckIn->guest_id,     // Ambil guest_id dari check-in
            ]);

            $order->items()->createMany($orderItemsData);

            DB::commit();

            return response()->json([
                'message' => 'Pesanan berhasil ditambahkan ke tagihan kamar.',
                'order' => $order->load('items.menu')
            ], 201);

        } catch (Throwable $e) { // Gunakan Throwable untuk menangkap semua jenis error
            DB::rollBack();
            Log::error('Gagal membuat pesanan POS: ' . $e->getMessage()); // Tambahkan logging
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}
