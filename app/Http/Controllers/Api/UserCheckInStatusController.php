<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Guest; // [TAMBAHAN] Import Model Guest
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

        // ------------------------------------------------------------------
        // STRATEGI 1: Cari berdasarkan user_id (Normal Flow)
        // ------------------------------------------------------------------
        $activeBooking = Booking::where('user_id', $user->id)
            ->where('status', 'checked_in')
            ->with(['room', 'guest'])
            ->latest()
            ->first();

        // ------------------------------------------------------------------
        // STRATEGI 2 (FIX BUG): Fallback via Guest Data
        // Jika user_id null (misal Check-In via Admin/Walk-In), 
        // cari booking berdasarkan kesamaan Email/No HP antara User & Guest.
        // ------------------------------------------------------------------
        if (!$activeBooking) {
            // Cari data Guest yang cocok dengan User yang sedang login
            $linkedGuest = Guest::where(function($q) use ($user) {
                    $q->where('email', $user->email);
                    // Asumsi: di User kolomnya 'phone', di Guest 'phone_number'
                    if ($user->phone) {
                        $q->orWhere('phone_number', $user->phone);
                    }
                })
                ->first();

            if ($linkedGuest) {
                // Cari booking aktif milik guest tersebut
                $activeBooking = Booking::where('guest_id', $linkedGuest->id)
                    ->where('status', 'checked_in')
                    ->with(['room', 'guest'])
                    ->latest()
                    ->first();

                // [SELF-HEALING] 
                // Jika ketemu, update kolom user_id di booking agar query berikutnya lebih cepat
                // dan data menjadi konsisten.
                if ($activeBooking && is_null($activeBooking->user_id)) {
                    $activeBooking->user_id = $user->id;
                    $activeBooking->save();
                }
            }
        }

        // ------------------------------------------------------------------
        // 3. Validasi Data & Response
        // ------------------------------------------------------------------
        if ($activeBooking) {
            $today = now()->format('Y-m-d');

            // Jika hari ini sudah lewat tanggal checkout, anggap tidak aktif (kecuali diperpanjang)
            if ($today > $activeBooking->check_out_date) {
                return response()->json([
                    'is_active' => false,
                    'message' => 'Masa menginap telah berakhir'
                ]);
            }

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
    
    /**
     * Endpoint Debugging (Opsional)
     * Untuk mengecek kenapa status checkin tidak terdeteksi
     */
    public function debugStatus(Request $request)
    {
        $user = $request->user();
        
        $guest = Guest::where('email', $user->email)
                ->orWhere('phone_number', $user->phone)
                ->first();

        return response()->json([
            'user_info' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone
            ],
            'matched_guest' => $guest,
            'bookings_by_user_id' => Booking::where('user_id', $user->id)->where('status', 'checked_in')->count(),
            'bookings_by_guest_id' => $guest ? Booking::where('guest_id', $guest->id)->where('status', 'checked_in')->count() : 0,
        ]);
    }
}