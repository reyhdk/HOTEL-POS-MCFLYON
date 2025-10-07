<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Midtrans\Snap;
use Throwable;

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
     * Menyimpan pesanan makanan baru dari tamu dengan status 'pending'.
     * Stok BELUM dikurangi di sini.
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

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $activeCheckIn, $user) {
                $totalPrice = 0;
                $orderItemsData = [];

                foreach ($validated['items'] as $item) {
                    $menu = Menu::find($item['menu_id']);
                    if ($menu->stock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => "Stok untuk menu '{$menu->name}' tidak mencukupi. Sisa stok: {$menu->stock}."
                        ]);
                    }
                    $totalPrice += $menu->price * $item['quantity'];
                    $orderItemsData[] = ['menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price];
                }

                $order = Order::create([
                    'room_id' => $activeCheckIn->room_id,
                    'user_id' => $user->id,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                ]);

                $order->items()->createMany($orderItemsData);
                return $order;
            });

            return response()->json($order->load('items.menu'), 201);
        } catch (Throwable $e) {
            Log::error('Gagal membuat pesanan makanan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat pesanan.'], 500);
        }
    }

    /**
     * Memproses pembayaran Midtrans untuk pesanan yang sudah ada.
     * Fungsi ini akan menghasilkan snap_token.
     */
    public function processPayment(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id || $order->status !== 'pending') {
            return response()->json(['message' => 'Pesanan ini tidak valid untuk dibayar.'], 403);
        }

        try {
            $midtransOrderId = 'ORDER-' . $order->id . '-' . time();

            $params = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $order->total_price,
                ],
                'customer_details' => [
                    'first_name' => $request->user()->name,
                    'email' => $request->user()->email,
                ],
            ];
            
            $snapToken = Snap::getSnapToken($params);
            
            $order->midtrans_order_id = $midtransOrderId;
            $order->save();

            return response()->json(['snap_token' => $snapToken]);

        } catch (Throwable $e) {
            Log::error('Gagal memproses pembayaran pesanan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses pembayaran.'], 500);
        }
    }

    /**
     * Fungsi ini akan dipanggil oleh Webhook Midtrans setelah pembayaran berhasil.
     * Tugasnya: mengurangi stok.
     */
    public static function handleSuccessfulPayment(Order $order)
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $menu = Menu::where('id', $item->menu_id)->lockForUpdate()->first();
                if ($menu) {
                    $menu->decrement('stock', $item->quantity);
                }
            }
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
        if (Auth::id() !== $order->user_id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        return $order->load(['room', 'user', 'items.menu']);
    }
}