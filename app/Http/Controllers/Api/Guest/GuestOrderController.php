<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestOrderController extends Controller
{
    /**
     * Mendapatkan profil tamu yang sedang login dan info kamar aktif mereka.
     * Ini sangat berguna untuk frontend.
     */
    public function getProfile(Request $request)
    {
        $guest = $request->user(); // Mengambil model Guest yang terotentikasi

        // Cari sesi check-in yang masih aktif untuk tamu ini
        $activeCheckIn = $guest->checkIns()->where('is_active', true)->with('room')->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 403);
        }

        return response()->json([
            'guest' => $guest,
            'active_room' => $activeCheckIn->room
        ]);
    }

    /**
     * Menyimpan pesanan makanan baru dari tamu.
     */
    public function store(Request $request)
    {
        $guest = $request->user(); // 1. Dapatkan tamu dari token otentikasi

        // 2. Cari kamar tempat tamu sedang check-in aktif
        $activeCheckIn = $guest->checkIns()->where('is_active', true)->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda harus check-in untuk membuat pesanan.'], 403);
        }
        $roomId = $activeCheckIn->room_id;

        // 3. Validasi input, SAMA SEPERTI OrderController, tapi tanpa 'room_id'
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // 4. Logika pembuatan pesanan (di-copy dari OrderController.php)
        DB::beginTransaction();
        try {
            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);

                if ($menu->stock < $item['quantity']) {
                    // Gunakan ValidationException agar frontend bisa menangani error per item
                    throw ValidationException::withMessages([
                        'items' => "Stok untuk menu '{$menu->name}' tidak mencukupi."
                    ]);
                }

                $totalPrice += $menu->price * $item['quantity'];
                $orderItemsData[] = [ 'menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price ];
            }

            $order = Order::create([
                'room_id' => $roomId, // ID Kamar didapat dari otentikasi, bukan input!
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            $order->items()->createMany($orderItemsData);
            
            DB::commit();

            return response()->json([
                'message' => 'Pesanan berhasil dibuat dan akan ditagihkan ke kamar Anda.',
                'order' => $order->load('items.menu')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Menampilkan riwayat pesanan untuk kamar tamu yang sedang aktif.
     */
    public function getOrderHistory(Request $request)
    {
        $guest = $request->user();
        $activeCheckIn = $guest->checkIns()->where('is_active', true)->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 403);
        }

        $orders = Order::where('room_id', $activeCheckIn->room_id)
                        ->with('items.menu')
                        ->latest()
                        ->get();

        return response()->json($orders);
    }
}