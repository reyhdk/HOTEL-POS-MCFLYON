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
     */
    public function getProfile(Request $request)
    {
        $user = Auth::user();

        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['room', 'guest'])
            ->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 403);
        }

        return response()->json([
            'user' => $user,
            'guest_details' => $activeCheckIn->guest,
            'active_room' => $activeCheckIn->room
        ]);
    }

    /**
     * Menyimpan pesanan makanan baru dari tamu, mengurangi stok,
     * dan membuat pesanan dalam status 'pending'.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda harus sedang check-in untuk dapat membuat pesanan.'], 403);
        }
        $roomId = $activeCheckIn->room_id;

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $roomId, $user) {
            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::where('id', $item['menu_id'])->lockForUpdate()->firstOrFail();

                if ($menu->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok untuk menu '{$menu->name}' tidak mencukupi. Sisa stok: {$menu->stock}."
                    ]);
                }

                $menu->decrement('stock', $item['quantity']);

                $totalPrice += $menu->price * $item['quantity'];
                $orderItemsData[] = [ 'menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price ];
            }

            $order = Order::create([
                'room_id' => $roomId,
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            $order->items()->createMany($orderItemsData);

            return response()->json([
                'message' => 'Pesanan berhasil dibuat, silakan lanjutkan ke pembayaran.',
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

    /**
     * Menampilkan detail satu pesanan milik pengguna yang sedang login.
     */
    public function show(Order $order)
    {
        // Otorisasi: Pastikan pesanan ini milik user yang sedang login
        if (Auth::id() !== $order->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        return $order->load(['room', 'user', 'items.menu']);
    }

    /**
     * Memproses "pembayaran" untuk sebuah pesanan dengan mengubah statusnya.
     */
    public function processPayment(Order $order)
    {
        // Otorisasi: Pastikan pesanan ini milik user yang sedang login
        if (Auth::id() !== $order->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Pesanan ini tidak dapat dibayar.'], 422);
        }

        $order->status = 'paid';
        $order->save();

        return response()->json([
            'message' => 'Pembayaran berhasil dikonfirmasi!',
            'order' => $order
        ]);
    }
}