<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class UserCheckInStatusController extends Controller
{
    /**
     * Cek apakah user memiliki status check-in aktif.
     * Digunakan untuk Gatekeeper akses menu kamar & Dashboard Tamu.
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['is_active' => false]);
        }

        // 1. Cari Booking dengan status 'checked_in' milik user ini
        $activeBooking = Booking::where('user_id', $user->id)
            ->where('status', 'checked_in')
            ->with(['room', 'guest'])
            ->latest()
            ->first();

        // 2. Validasi
        if ($activeBooking) {
            $today = now()->format('Y-m-d');

            // Jika hari ini sudah lewat tanggal checkout, anggap tidak aktif (kecuali diperpanjang)
            if ($today > $activeBooking->check_out_date) {
                return response()->json([
                    'is_active' => false,
                    'message' => 'Masa menginap telah berakhir'
                ]);
            }

            // [PERBAIKAN DISINI] 
            // Kita tambahkan 'check_in_date' dan 'check_out_date' ke response JSON
            return response()->json([
                'is_active'      => true,
                'booking_id'     => $activeBooking->id,
                // Handle jika room_number beda nama kolom di DB
                'room_number'    => $activeBooking->room->room_number ?? $activeBooking->room->number,
                'guest_name'     => $activeBooking->guest->name,
                'status'         => $activeBooking->status,

                // DATA TANGGAL YANG DIBUTUHKAN DASHBOARD:
                'check_in_date'  => $activeBooking->check_in_date,
                'check_out_date' => $activeBooking->check_out_date,
            ]);
        }

        // Default: Tidak ada check-in aktif
        return response()->json([
            'is_active' => false,
            'message' => 'Belum check-in'
        ]);
    }
}
