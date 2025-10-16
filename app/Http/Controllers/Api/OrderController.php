<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:cash,midtrans,pay_at_checkout',
        ]);

        try {
            $order = DB::transaction(function () use ($validated) {
                $activeCheckIn = CheckIn::where('room_id', $validated['room_id'])
                                        ->where('is_active', true)
                                        ->with('booking')
                                        ->first();

                if (!$activeCheckIn) {
                    throw new \Exception("Tidak ada sesi check-in yang aktif untuk kamar ini.");
                }

                $totalPrice = 0;
                foreach ($validated['items'] as $item) {
                    $menu = Menu::findOrFail($item['menu_id']);
                    if ($menu->stock < $item['quantity']) {
                        throw new \Exception("Stok untuk menu '{$menu->name}' tidak mencukupi.");
                    }
                    $totalPrice += $menu->price * $item['quantity'];
                }

                // PERBAIKAN: Standarisasi status order
                // - 'pending' = belum dibayar, akan masuk folio
                // - 'paid' = sudah dibayar tunai
                $status = 'pending'; // Default untuk midtrans DAN pay_at_checkout
                if ($validated['payment_method'] === 'cash') {
                    $status = 'paid'; // Langsung lunas untuk cash
                }

                $order = Order::create([
                    'room_id' => $validated['room_id'],
                    'total_price' => $totalPrice,
                    'status' => $status,
                    'user_id' => $activeCheckIn->booking->user_id,
                    'guest_id' => $activeCheckIn->guest_id,
                    'booking_id' => $activeCheckIn->booking_id,
                ]);

                $orderItemsData = collect($validated['items'])->map(function($item) {
                    $menu = Menu::find($item['menu_id']);
                    return ['menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price];
                });

                $order->items()->createMany($orderItemsData);

                // Kurangi stok
                foreach ($order->items as $item) {
                    Menu::find($item->menu_id)->decrement('stock', $item->quantity);
                }

                return $order;
            });

            if ($validated['payment_method'] === 'midtrans') {
                // ... (logika midtrans Anda)
            }

            return response()->json(['message' => 'Pesanan berhasil dibuat.', 'order' => $order], 201);

        } catch (Throwable $e) {
            Log::error('Gagal membuat pesanan POS: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
