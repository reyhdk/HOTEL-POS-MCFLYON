<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolioController extends Controller
{
    /**
     * Mengambil data folio untuk semua kamar yang sedang ditempati.
     */
    public function index()
    {
        // 1. Ambil semua kamar yang ditempati, beserta SEMUA relasinya (check-in, tamu, pesanan)
        $occupiedRooms = Room::whereHas('checkIns', function ($query) {
            $query->where('is_active', true);
        })
        ->with([
            'checkIns' => function ($query) {
                $query->where('is_active', true)->with('guest');
            },
            'orders.items.menu'
        ])
        ->get();

        // [PERBAIKAN UTAMA]
        // 2. Proses setiap kamar untuk menyaring pesanan yang relevan saja
        $occupiedRooms->each(function ($room) {
            // Cari data check-in yang aktif untuk kamar ini
            $activeCheckIn = $room->checkIns->firstWhere('is_active', true);

            if ($activeCheckIn) {
                // Saring koleksi 'orders' yang sudah di-load.
                // Hanya ambil pesanan yang dibuat SETELAH tamu ini check-in.
                $relevantOrders = $room->orders->where('created_at', '>=', $activeCheckIn->created_at);
                
                // Ganti relasi 'orders' yang lama dengan pesanan yang sudah disaring
                $room->setRelation('orders', $relevantOrders);
            } else {
                // Jika (karena alasan aneh) tidak ada check-in aktif, kosongkan pesanannya
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
        // Cari check-in yang aktif untuk kamar ini
        $activeCheckIn = $room->checkIns()->where('is_active', true)->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Tidak ada tamu yang aktif di kamar ini.'], 404);
        }

        // Ambil semua pesanan yang statusnya 'pending' untuk kamar ini
        $pendingOrders = $room->orders()->where('status', 'pending')->get();

        // Mulai transaksi database untuk memastikan semua proses aman
        DB::beginTransaction();
        try {
            // 1. Proses setiap pesanan yang belum lunas (jika ada)
            foreach ($pendingOrders as $order) {
                $order->status = 'completed';
                $order->save();
            }

            // 2. Proses check-out
            $activeCheckIn->update([
                'check_out_time' => now(),
                'is_active' => false,
            ]);

            // 3. Ubah status kamar menjadi 'needs cleaning'
            $room->update(['status' => 'needs cleaning']);

            DB::commit(); // Konfirmasi semua perubahan jika berhasil

            return response()->json(['message' => 'Pembayaran berhasil dan tamu telah check-out.']);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika ada error
            return response()->json(['message' => 'Terjadi kesalahan saat memproses folio.', 'error' => $e->getMessage()], 500);
        }
    }
}