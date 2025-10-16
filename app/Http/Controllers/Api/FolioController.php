<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FolioController extends Controller
{
    /**
     * Mengambil data folio untuk semua kamar yang sedang ditempati.
     */
    public function index()
    {
        $occupiedRooms = Room::whereHas('checkIns', function ($query) {
            $query->where('is_active', true);
        })
        ->with([
            'checkIns' => function ($query) {
                $query->where('is_active', true)->with('guest');
            },
            'orders.items.menu',
        ])
        ->get();

        $occupiedRooms->each(function ($room) {
            $activeCheckIn = $room->checkIns->firstWhere('is_active', true);

            if ($activeCheckIn) {
                // Daftar status yang dianggap sebagai tagihan aktif
                $billableStatuses = ['pending', 'processing', 'delivering', 'completed'];

                $relevantOrders = $room->orders->filter(function ($order) use ($activeCheckIn, $billableStatuses) {
                    return $order->created_at >= $activeCheckIn->created_at
                           && in_array($order->status, $billableStatuses);
                });

                $room->setRelation('orders', $relevantOrders->values());
            } else {
                $room->setRelation('orders', collect());
            }
        });

        return response()->json($occupiedRooms);
    }

    /**
     * Memproses semua tagihan, lalu melakukan check-out.
     */
    public function processFolioPaymentAndCheckout(Room $room)
    {
        $activeCheckIn = $room->checkIns()->where('is_active', true)->with('booking')->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Tidak ada tamu yang aktif di kamar ini.'], 404);
        }

        DB::beginTransaction();
        try {
            // [PERBAIKAN UTAMA DI SINI]
            // Kita ambil SEMUA order yang statusnya termasuk tagihan, tidak hanya 'pending'.
            $billableStatuses = ['pending', 'processing', 'delivering', 'completed'];

            $ordersToPay = $room->orders()
                ->whereIn('status', $billableStatuses) // Gunakan whereIn agar semua status tagihan terambil
                ->where('created_at', '>=', $activeCheckIn->created_at)
                ->get();

            // Ubah status semua pesanan yang ditagih menjadi 'paid'
            foreach ($ordersToPay as $order) {
                $order->status = 'paid';
                $order->save();
            }

            // Tandai booking sebagai 'completed' agar pendapatannya juga masuk dasbor
            if ($activeCheckIn->booking) {
                $activeCheckIn->booking->status = 'completed';
                $activeCheckIn->booking->save();
            }

            // Proses check-out
            $activeCheckIn->update(['check_out_time' => now(), 'is_active' => false]);
            $room->update(['status' => 'dirty']);

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran berhasil dan tamu telah check-out.',
                'orders_paid' => $ordersToPay->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal memproses folio: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat memproses folio.'], 500);
        }
    }
    /**
     * TEST ENDPOINT - Cek folio untuk room tertentu
     */
    public function testRoom($roomId)
    {
        $room = Room::with([
            'checkIns' => function ($query) {
                $query->where('is_active', true)->with('guest');
            },
            'orders.items.menu',
        ])->findOrFail($roomId);

        $activeCheckIn = $room->checkIns->firstWhere('is_active', true);

        if (!$activeCheckIn) {
            return response()->json([
                'message' => 'No active check-in',
                'room' => $room,
                'orders' => []
            ]);
        }

        $allOrders = $room->orders;
        $pendingOrders = $room->orders->filter(function ($order) use ($activeCheckIn) {
            return $order->created_at >= $activeCheckIn->created_at
                   && $order->status === 'pending';
        })->values();

        return response()->json([
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'checkin_time' => $activeCheckIn->created_at,
            'total_orders' => $allOrders->count(),
            'pending_orders_count' => $pendingOrders->count(),
            'all_orders' => $allOrders->map(function($o) {
                return [
                    'id' => $o->id,
                    'status' => $o->status,
                    'created_at' => $o->created_at,
                    'total_price' => $o->total_price,
                ];
            }),
            'pending_orders' => $pendingOrders,
        ]);
    }
}
