<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Snap;
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
            'payment_method' => 'required|string|in:cash,midtrans,pay_at_checkout', // Tiga pilihan
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $request) {
                $activeCheckIn = CheckIn::where('room_id', $validated['room_id'])
                                        ->where('is_active', true)
                                        ->with('booking')
                                        ->first();

                if (!$activeCheckIn) {
                    throw new \Exception("Tidak ada sesi check-in yang aktif untuk kamar ini.");
                }

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

                // Tentukan status pesanan berdasarkan metode pembayaran
                $status = 'pending'; // Default untuk midtrans
                if ($validated['payment_method'] === 'cash') {
                    $status = 'paid';
                } else if ($validated['payment_method'] === 'pay_at_checkout') {
                    $status = 'unpaid';
                }

                $order = Order::create([
                    'room_id' => $validated['room_id'],
                    'total_price' => $totalPrice,
                    'status' => $status,
                    'user_id' => $activeCheckIn->booking->user_id,
                    'guest_id' => $activeCheckIn->guest_id,
                ]);

                $order->items()->createMany($orderItemsData);

                // Langsung kurangi stok karena pesanan sudah dikonfirmasi
                foreach ($order->items as $item) {
                    Menu::find($item->menu_id)->decrement('stock', $item->quantity);
                }

                return $order;
            });

            // Jika pembayaran via Midtrans, buat snap token
            if ($validated['payment_method'] === 'midtrans') {
                $midtransOrderId = 'ORDER-' . $order->id . '-' . time();
                $order->midtrans_order_id = $midtransOrderId;
                $order->save();

                $guest = $order->guest;
                $params = [
                    'transaction_details' => ['order_id' => $midtransOrderId, 'gross_amount' => (int) $order->total_price],
                    'customer_details' => [
                        'first_name' => $guest->name,
                        'email' => $guest->email,
                        'phone' => $guest->phone_number,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);
                return response()->json(['snap_token' => $snapToken]);
            }

            // Jika cash atau bayar saat checkout
            return response()->json(['message' => 'Pesanan berhasil dibuat.'], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Gagal membuat pesanan POS: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }
}
