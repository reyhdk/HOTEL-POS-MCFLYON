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
    // Di dalam file app/Http/Controllers/Api/OrderController.php

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
        $order = DB::transaction(function () use ($validated, $request) {
            $activeCheckIn = CheckIn::where('room_id', $validated['room_id'])
                                    ->where('is_active', true)
                                    ->with('booking') // Eager load booking
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

            // [PERBAIKAN LOGIKA STATUS]
            $status = 'pending'; // Default untuk midtrans
            if ($validated['payment_method'] === 'cash') {
                $status = 'paid';
            } else if ($validated['payment_method'] === 'pay_at_checkout') {
                $status = 'unpaid'; // Status yang benar agar masuk ke folio
            }

            $order = Order::create([
                'room_id' => $validated['room_id'],
                'total_price' => $totalPrice,
                'status' => $status,
                'user_id' => $activeCheckIn->booking->user_id,
                'guest_id' => $activeCheckIn->guest_id,
            ]);

            $orderItemsData = collect($validated['items'])->map(function($item) {
                $menu = Menu::find($item['menu_id']);
                return ['menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price];
            });

            $order->items()->createMany($orderItemsData);

            // Langsung kurangi stok
            foreach ($order->items as $item) {
                Menu::find($item->menu_id)->decrement('stock', $item->quantity);
            }

            return $order;
        });

        if ($validated['payment_method'] === 'midtrans') {
            // ... (logika midtrans Anda sudah benar)
        }

        return response()->json(['message' => 'Pesanan berhasil dibuat.'], 201);

    } catch (Throwable $e) {
        // DB::rollBack(); // Tidak perlu karena DB::transaction sudah otomatis rollback
        Log::error('Gagal membuat pesanan POS: ' . $e->getMessage());
        return response()->json(['message' => $e->getMessage()], 422); // Gunakan 422 untuk error validasi/proses
    }
}
}
