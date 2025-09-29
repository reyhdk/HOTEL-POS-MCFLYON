<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * Mengambil data ringkas untuk halaman konfirmasi checkout.
     */
    public function getFolio()
    {
        $user = Auth::user();
        $activeCheckIn = $this->getActiveCheckIn($user);

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 403);
        }

        // Karena semua sudah dibayar, kita hanya kirim data konfirmasi
        return response()->json([
            'folio' => [
                'guest_name' => $activeCheckIn->guest->name,
                'room_number' => $activeCheckIn->room->room_number,
                'check_in_date' => $activeCheckIn->check_in_time,
                'unpaid_orders' => [], // <-- Tambahkan baris ini
                'total_bill' => 0,
                'is_fully_paid' => true,
            ]
        ]);
    }

    /**
     * Memproses checkout untuk tamu yang sedang login.
     */
    public function processCheckout()
    {
        $user = Auth::user();
        $activeCheckIn = $this->getActiveCheckIn($user);

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda tidak memiliki sesi check-in yang aktif.'], 403);
        }

        DB::beginTransaction();
        try {
            $activeCheckIn->is_active = false;
            $activeCheckIn->check_out_time = now();
            $activeCheckIn->save();

            $room = $activeCheckIn->room;
            $room->status = 'dirty';
            $room->save();

            DB::commit();

            return response()->json(['message' => 'Checkout berhasil! Terima kasih telah menginap.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan saat proses checkout.'], 500);
        }
    }

    /**
     * Helper function untuk mendapatkan data check-in yang aktif.
     */
    private function getActiveCheckIn($user)
    {
        return CheckIn::where('is_active', true)
            ->whereHas('booking', fn($q) => $q->where('user_id', $user->id))
            ->with(['guest', 'room'])
            ->first();
    }
}
