<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Menu;
use App\Models\Order;
use App\Models\WarehouseItem;
use App\Models\CashFlow; // [TAMBAHAN] Import CashFlow
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; 
use Midtrans\Config;
use Midtrans\Snap;
use Throwable;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Order Store Request Data:', $request->all());

        $validated = $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'table_id' => 'nullable|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:cash,midtrans,pay_at_checkout',
        ]);

        if (empty($validated['room_id']) && empty($validated['table_id'])) {
            return response()->json(['message' => 'Harus memilih Kamar atau Meja.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($validated, $request) {
                
                $userId = Auth::id() ?? ($request->user() ? $request->user()->id : 1);
                $guestId = null;
                $bookingId = null;
                $roomId = $validated['room_id'] ?? null;
                $tableId = $validated['table_id'] ?? null;

                if ($roomId) {
                    $activeCheckIn = CheckIn::where('room_id', $roomId)
                                            ->where('is_active', true)
                                            ->with('booking')
                                            ->first();
                    if (!$activeCheckIn) {
                        throw new \Exception("Tidak ada sesi check-in yang aktif untuk kamar ini.");
                    }
                    $bookingId = $activeCheckIn->booking_id;
                    $guestId = $activeCheckIn->guest_id;
                }
                
                if ($tableId) {
                    $table = DB::table('tables')->where('id', $tableId)->first();
                    if ($table && $table->status === 'available') {
                        DB::table('tables')->where('id', $tableId)->update(['status' => 'occupied']);
                    }
                }

                $totalPrice = 0;
                $orderItems = [];

                foreach ($validated['items'] as $item) {
                    $menu = Menu::findOrFail($item['menu_id']);
                    $totalPrice += $menu->price * $item['quantity'];
                    
                    $orderItems[] = [
                        'menu_id' => $menu->id,
                        'quantity' => $item['quantity'],
                        'price' => $menu->price
                    ];
                }

                $status = 'pending'; 
                $paidAmount = 0;
                
                if ($validated['payment_method'] === 'cash') {
                    $status = 'paid'; 
                    $paidAmount = $totalPrice;
                }

                $orderCode = 'ORD/' . date('Ymd') . '/' . strtoupper(Str::random(5));

                $order = new Order();
                $order->order_code = $orderCode;
                $order->room_id = $roomId;
                $order->table_id = $tableId;
                $order->total_price = $totalPrice;
                $order->paid_amount = $paidAmount;
                $order->change_amount = 0;
                $order->payment_method = $validated['payment_method'];
                $order->status = $status;
                $order->user_id = $userId;
                $order->guest_id = $guestId; 
                $order->booking_id = $bookingId;
                $order->save();

                $order->items()->createMany($orderItems);

                if ($validated['payment_method'] === 'cash') {
                    $this->deductIngredients($order);

                    // [TAMBAHAN] OTOMATIS CATAT CASH FLOW JIKA BAYAR CASH DI KASIR
                    CashFlow::create([
                        'transaction_date' => now(),
                        'type' => 'income',
                        'category' => 'resto',
                        'description' => 'Pesanan Resto/Room Service Kasir (Tunai)',
                        'payment_method' => 'Cash',
                        'amount' => $totalPrice,
                        'reference_id' => $orderCode,
                        'user_id' => $userId
                    ]);
                }

                $snapToken = null;
                if ($validated['payment_method'] === 'midtrans') {
                    Config::$serverKey = config('services.midtrans.server_key');
                    Config::$isProduction = config('services.midtrans.is_production');
                    Config::$isSanitized = true;
                    Config::$is3ds = true;

                    $params = [
                        'transaction_details' => [
                            'order_id' => $orderCode,
                            'gross_amount' => $totalPrice,
                        ],
                        'customer_details' => [
                            'first_name' => 'Tamu / Meja',
                        ]
                    ];

                    $snapToken = Snap::getSnapToken($params);
                    $order->midtrans_order_id = $orderCode; 
                    $order->save();
                }

                return ['order' => $order, 'snap_token' => $snapToken];
            });

            return response()->json([
                'message' => 'Pesanan berhasil dibuat.', 
                'order' => $result['order'],
                'snap_token' => $result['snap_token'] 
            ], 201);

        } catch (Throwable $e) {
            Log::error('CRITICAL ORDER ERROR: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal Database: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function deductIngredients(Order $order)
    {
        $order->load('items.menu.ingredients');

        foreach ($order->items as $orderItem) {
            $menu = $orderItem->menu;
            
            if($menu->stock >= $orderItem->quantity){
               $menu->decrement('stock', $orderItem->quantity);
            }

            if ($menu->ingredients) {
                foreach ($menu->ingredients as $ingredient) {
                    $totalDeduction = $ingredient->pivot->quantity * $orderItem->quantity;
                    
                    $warehouseItem = WarehouseItem::find($ingredient->id);
                    if ($warehouseItem) {
                        $warehouseItem->decrement('current_stock', $totalDeduction);

                        DB::table('stock_transactions')->insert([
                            'transaction_code' => 'OUT/' . date('Ymd') . '/' . strtoupper(Str::random(5)),
                            'warehouse_item_id' => $warehouseItem->id,
                            'transaction_type' => 'out',
                            'quantity' => $totalDeduction,
                            'unit_price' => $warehouseItem->cost_price,
                            'total_price' => $totalDeduction * $warehouseItem->cost_price,
                            'reference_type' => 'sale',
                            'notes' => "Terjual via Pesanan {$order->order_code} (Menu: {$menu->name})",
                            'transaction_date' => now(),
                            'created_by' => $order->user_id ?? 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }
        }
    }
}