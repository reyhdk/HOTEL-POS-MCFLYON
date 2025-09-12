<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Guest;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    /**
     * Membuat catatan check-in baru (mendaftarkan tamu ke kamar).
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_id' => 'required|exists:guests,id',
        ]);

        $room = Room::find($validated['room_id']);

        // 2. Pastikan kamar tersedia
        if ($room->status !== 'available') {
            return response()->json(['message' => 'Kamar tidak tersedia untuk check-in.'], 409); // 409 Conflict
        }

        // Memulai transaksi database untuk memastikan kedua aksi (buat checkin & update kamar) berhasil
        DB::beginTransaction();

        try {
            // 3. Buat catatan check-in baru
            CheckIn::create([
                'room_id' => $room->id,
                'guest_id' => $validated['guest_id'],
                'check_in_time' => Carbon::now(),
                'is_active' => true,
            ]);

            // 4. Update status kamar menjadi 'occupied'
            $room->status = 'occupied';
            $room->save();

            DB::commit(); // Konfirmasi transaksi jika semua berhasil

            return response()->json(['message' => 'Tamu berhasil check-in.'], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika terjadi error

            return response()->json(['message' => 'Gagal melakukan check-in.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Menyelesaikan proses check-in (check-out tamu dari kamar).
     */
    public function checkout(Room $room)
    {
        // 1. Cari catatan check-in yang masih aktif untuk kamar ini
        $activeCheckIn = CheckIn::where('room_id', $room->id)
            ->where('is_active', true)
            ->first();

        // 2. Jika tidak ada yang aktif, kembalikan error
        if (!$activeCheckIn) {
            return response()->json(['message' => 'Tidak ada tamu yang aktif check-in di kamar ini.'], 404);
        }

        DB::beginTransaction();
        try {
            // 3. Update catatan check-in
            $activeCheckIn->update([
                'check_out_time' => Carbon::now(),
                'is_active' => false,
            ]);

            // 4. Update status kamar kembali menjadi 'available'
            $room->update(['status' => 'available']);

            DB::commit();

            return response()->json(['message' => 'Tamu berhasil check-out.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal melakukan check-out.', 'error' => $e->getMessage()], 500);
        }
    }
}