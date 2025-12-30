<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserCheckInStatusController extends Controller
{
    /**
     * ✅ FIXED: Mengambil status check-in aktif untuk user yang login
     * Menggunakan 3 strategi fallback untuk memastikan data ditemukan
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(null);
        }

        Log::info('🔍 [USER-CHECKIN-STATUS] Fetching for user', [
            'user_id' => $user->id,
            'user_email' => $user->email
        ]);

        // === STRATEGI 1: Cari dari booking user yang status checked_in ===
        $activeBooking = Booking::where('user_id', $user->id)
            ->where('status', 'checked_in')
            ->with(['room', 'guest'])
            ->orderBy('checked_in_at', 'desc')
            ->first();

        if ($activeBooking) {
            Log::info('✅ [STRATEGI-1] Found booking with checked_in status', [
                'booking_id' => $activeBooking->id,
                'guest_id' => $activeBooking->guest_id
            ]);

            // Cari CheckIn yang terkait
            $activeCheckIn = CheckIn::where('booking_id', $activeBooking->id)
                ->where('is_active', true)
                ->with(['room', 'booking.user', 'guest'])
                ->first();

            if ($activeCheckIn) {
                Log::info('✅ [STRATEGI-1] CheckIn record found', [
                    'checkin_id' => $activeCheckIn->id
                ]);
                return response()->json($activeCheckIn);
            }
        }

        // === STRATEGI 2: Cari berdasarkan guest_id dari semua booking user ===
        $guestIds = Booking::where('user_id', $user->id)
            ->pluck('guest_id')
            ->unique()
            ->filter()
            ->toArray();

        if (!empty($guestIds)) {
            Log::info('🔍 [STRATEGI-2] Searching by guest_ids', [
                'guest_ids' => $guestIds
            ]);

            $activeCheckIn = CheckIn::where('is_active', true)
                ->whereIn('guest_id', $guestIds)
                ->with(['room', 'booking.user', 'guest'])
                ->orderBy('check_in_time', 'desc')
                ->first();

            if ($activeCheckIn) {
                Log::info('✅ [STRATEGI-2] CheckIn found by guest_id', [
                    'checkin_id' => $activeCheckIn->id,
                    'guest_id' => $activeCheckIn->guest_id
                ]);
                return response()->json($activeCheckIn);
            }
        }

        // === STRATEGI 3: Cari melalui relasi CheckIn->Booking->User ===
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['room', 'booking.user', 'guest'])
            ->orderBy('check_in_time', 'desc')
            ->first();

        if ($activeCheckIn) {
            Log::info('✅ [STRATEGI-3] CheckIn found via booking relation', [
                'checkin_id' => $activeCheckIn->id
            ]);
            return response()->json($activeCheckIn);
        }

        // === TIDAK DITEMUKAN ===
        Log::info('❌ [USER-CHECKIN-STATUS] No active check-in found', [
            'user_id' => $user->id,
            'guest_ids_checked' => $guestIds ?? []
        ]);

        return response()->json(null);
    }

    /**
     * 🔧 DEBUG ENDPOINT: Untuk troubleshooting
     * Bisa diakses di: GET /api/debug-check-in-status
     */
    public function debugStatus(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        // Ambil semua booking user
        $bookings = Booking::where('user_id', $user->id)
            ->with(['room', 'guest'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil semua guest_ids
        $guestIds = $bookings->pluck('guest_id')->unique()->filter()->toArray();

        // Ambil semua check-in aktif terkait guest_ids
        $checkIns = CheckIn::where('is_active', true)
            ->whereIn('guest_id', $guestIds)
            ->with(['booking', 'room', 'guest'])
            ->get();

        // Ambil booking dengan status checked_in
        $checkedInBookings = Booking::where('user_id', $user->id)
            ->where('status', 'checked_in')
            ->with(['room', 'guest'])
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name
            ],
            'total_bookings' => $bookings->count(),
            'bookings' => $bookings->map(fn($b) => [
                'id' => $b->id,
                'status' => $b->status,
                'guest_id' => $b->guest_id,
                'guest_name' => $b->guest->name ?? 'N/A',
                'room_number' => $b->room->room_number ?? 'N/A',
                'checked_in_at' => $b->checked_in_at
            ]),
            'guest_ids' => $guestIds,
            'checked_in_bookings_count' => $checkedInBookings->count(),
            'checked_in_bookings' => $checkedInBookings->map(fn($b) => [
                'id' => $b->id,
                'guest_id' => $b->guest_id,
                'status' => $b->status
            ]),
            'active_checkins_count' => $checkIns->count(),
            'active_checkins' => $checkIns->map(fn($c) => [
                'id' => $c->id,
                'booking_id' => $c->booking_id,
                'guest_id' => $c->guest_id,
                'room_id' => $c->room_id,
                'is_active' => $c->is_active,
                'check_in_time' => $c->check_in_time
            ])
        ]);
    }
}
