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
     * Mendapatkan profil tamu yang sedang aktif Check-In (Untuk validasi di halaman Guest)
     */
    public function getProfile(Request $request)
    {
        $user = Auth::user();

        // Cari sesi CheckIn yang aktif berdasarkan User -> Booking -> CheckIn
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['room', 'guest'])
            ->latest()
            ->first();

        // Jika tidak ada CheckIn aktif, kembalikan 403
        if (!$activeCheckIn) {
            return response()->json([
                'message' => 'Anda tidak memiliki sesi check-in yang aktif.',
                'debug_info' => 'Pastikan admin sudah melakukan Check-In untuk booking Anda.'
            ], 403);
        }

        return response()->json([
            'guest_details' => $activeCheckIn->guest,
            'active_room' => $activeCheckIn->room,
            'check_in_time' => $activeCheckIn->created_at,
            'check_in_id' => $activeCheckIn->id
        ]);
    }

    /**
     * Membuat Order baru dari sisi Tamu
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:pay_now_midtrans,pay_at_checkout',
        ]);

        // Verifikasi kepemilikan kamar (Security Check)
        $activeCheckIn = CheckIn::where('room_id', $validated['room_id'])
            ->where('is_active', true)
            ->whereHas('booking', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Akses ditolak. Kamar ini bukan milik sesi Anda.'], 403);
        }

        try {
            $order = DB::transaction(function () use ($validated, $activeCheckIn, $user) {
                $totalPrice = 0;
                $orderItemsData = [];
                
                // Hitung total dan cek stok
                foreach ($validated['items'] as $item) {
                    $menu = Menu::findOrFail($item['menu_id']);
                    if ($menu->stock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => "Stok untuk menu '{$menu->name}' tidak mencukupi."
                        ]);
                    }
                    $totalPrice += $menu->price * $item['quantity'];
                    $orderItemsData[] = ['menu_id' => $menu->id, 'quantity' => $item['quantity'], 'price' => $menu->price];
                }

                // Buat Order (Status default 'pending')
                $order = Order::create([
                    'room_id' => $validated['room_id'],
                    'user_id' => $user->id,
                    'guest_id' => $activeCheckIn->guest_id,
                    'booking_id' => $activeCheckIn->booking_id,
                    'total_price' => $totalPrice,
                    'status' => 'pending', 
                    'payment_method' => $validated['payment_method']
                ]);

                // Buat Order Items
                $order->items()->createMany($orderItemsData);

                // Kurangi Stok
                foreach ($order->items as $item) {
                    Menu::find($item->menu_id)->decrement('stock', $item->quantity);
                }

                return $order;
            });

            // Logika Midtrans (Jika Bayar Sekarang)
            if ($validated['payment_method'] === 'pay_now_midtrans') {
                $midtransOrderId = 'ORDER-' . $order->id . '-' . time();
                $order->midtrans_order_id = $midtransOrderId;
                $order->save();

                $params = [
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => (int) $order->total_price,
                    ],
                    'customer_details' => [
                        'first_name' => $user->name,
                        'email' => $user->email,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);
                return response()->json(['snap_token' => $snapToken, 'order' => $order], 201);
            }

            return response()->json(['message' => 'Pesanan berhasil ditambahkan ke tagihan kamar.', 'order' => $order], 201);

        } catch (Throwable $e) {
            Log::error('Guest Order Error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Menampilkan riwayat pesanan untuk kamar tamu yang sedang aktif.
     */
    public function getOrderHistory(Request $request)
    {
        $orders = $request->user()
                          ->orders()
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

    /**
     * Fungsi ini akan dipanggil oleh Webhook Midtrans setelah pembayaran berhasil.
     */
    public static function handleSuccessfulPayment(Order $order)
    {
        // Stok sudah dikurangi saat order dibuat (store), jadi di sini mungkin update status saja
        // atau logika lain jika diperlukan.
        Log::info("Pembayaran berhasil untuk Order ID: {$order->id}");
    }
}