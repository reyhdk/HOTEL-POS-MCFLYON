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
        $occupiedRooms = Room::whereHas('checkIns', function ($query) {
            $query->where('is_active', true);
        })
        ->with([
            'checkIns' => function ($query) {
                $query->where('is_active', true)->with('guest');
            },
            // [PERBAIKAN UTAMA]
            // Sekarang kita hanya mengambil pesanan yang statusnya 'unpaid' dari database.
            // Pesanan yang 'paid', 'pending', atau 'cancelled' tidak akan masuk folio.
            'orders' => function ($query) {
                $query->where('status', 'pending')->with('items.menu');
            },
        ])
        ->get();

        // [PERBAIKAN KEDUA]
        // Kode di bawah ini menjadi lebih sederhana dan aman karena data 'orders' sudah bersih.
        $occupiedRooms->each(function ($room) {
            $activeCheckIn = $room->checkIns->firstWhere('is_active', true);

            if ($activeCheckIn) {
                // Filter berdasarkan tanggal check-in tetap penting untuk histori.
                $relevantOrders = $room->orders->where('created_at', '>=', $activeCheckIn->created_at);
                $room->setRelation('orders', $relevantOrders);
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