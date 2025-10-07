<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use Illuminate\Http\Request;

class UserCheckInStatusController extends Controller
{
    /**
     * Mengambil status check-in aktif untuk pengguna yang sedang login.
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();

        // Cari data check-in yang statusnya 'is_active' = true
        // DAN dimiliki oleh booking dari user yang sedang login.
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
             ->with(['room', 'booking.user']) // <-- PERUBAHAN DI SINI
        ->first();


        return response()->json($activeCheckIn);
    }
}
