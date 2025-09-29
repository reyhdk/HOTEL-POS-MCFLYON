<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use Illuminate\Http\Request;

class CheckoutHistoryController extends Controller
{
    /**
     * Menampilkan riwayat check-in yang sudah tidak aktif (checkout).
     */
    public function index(Request $request)
    {
        $query = CheckIn::where('is_active', false)
                        ->with(['guest', 'room', 'booking.user'])
                        ->latest('check_out_time');

        // Fitur pencarian (opsional tapi sangat berguna)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('guest', fn($guestQuery) => $guestQuery->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('room', fn($roomQuery) => $roomQuery->where('room_number', 'like', "%{$search}%"));
            });
        }

        return $query->paginate(10);
    }
}
