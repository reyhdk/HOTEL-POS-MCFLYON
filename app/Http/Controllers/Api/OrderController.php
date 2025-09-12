<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'status' => 'pending', // Pesanan dicatat sebagai tagihan
            ]);

            $order->items()->createMany($orderItemsData);
            
            // [DIHAPUS] Logika pengurangan stok dipindahkan ke FolioController
            
            DB::commit();

            return response()->json([
                'message' => 'Pesanan berhasil ditambahkan ke tagihan kamar.',
                'order' => $order->load('items.menu')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}