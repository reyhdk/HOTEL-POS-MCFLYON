<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestOrderController extends Controller
{
    /**
     * Mendapatkan profil tamu yang sedang login dan info kamar aktif mereka.
     * Endpoint ini sangat berguna untuk halaman utama aplikasi tamu.
     */
    public function getProfile(Request $request)
    {
        $user = Auth::user(); // Dapatkan User yang terotentikasi

        // Cari sesi check-in yang masih aktif yang terhubung dengan booking milik user ini.
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['room', 'guest']) // Muat relasi room dan guest
            ->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 403);
        }

        return response()->json([
            'user' => $user,
            'guest_details' => $activeCheckIn->guest, // Detail tamu dari check-in
            'active_room' => $activeCheckIn->room     // Kamar yang sedang ditempati
        ]);
    }

    /**
     * Menyimpan pesanan makanan baru dari tamu.
     */
    public function store(Request $request)
    {
        $user = Auth::user(); // 1. Dapatkan User dari token otentikasi

        // 2. Cari kamar tempat tamu sedang check-in aktif melalui relasi User -> Booking -> CheckIn
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda harus sedang check-in untuk dapat membuat pesanan.'], 403);
        }
        $roomId = $activeCheckIn->room_id;

        // 3. Validasi input, SAMA SEPERTI OrderController, tapi tanpa 'room_id'
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // 4. Logika pembuatan pesanan dibungkus dalam transaksi database
        return DB::transaction(function () use ($validated, $roomId, $user) {
            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);

                if ($menu->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok untuk menu '{$menu->name}' tidak mencukupi."
                    ]);
                }

                $menu->decrement('stock', $item['quantity']); // Langsung kurangi stok

                $totalPrice += $menu->price * $item['quantity'];
                $orderItemsData[] = [ 'menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price ];
            }

            $order = Order::create([
                'room_id' => $roomId,       // ID Kamar didapat dari otentikasi
                'user_id' => $user->id,       // ID User yang memesan
                'total_price' => $totalPrice,
                'status' => 'pending',        // Status awal pesanan
            ]);

            $order->items()->createMany($orderItemsData);

            return response()->json([
                'message' => 'Pesanan berhasil dibuat dan akan ditambahkan ke tagihan kamar Anda.',
                'order' => $order->load('items.menu')
            ], 201);
        });
    }

    /**
     * Menampilkan riwayat pesanan untuk kamar tamu yang sedang aktif.
     */
    public function getOrderHistory(Request $request)
    {
        $user = Auth::user();
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', fn($q) => $q->where('user_id', $user->id))
            ->first();

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
