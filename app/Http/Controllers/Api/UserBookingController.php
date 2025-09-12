<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserBookingController extends Controller
{
    /**
     * Mengambil riwayat booking milik pengguna yang sedang login
     * langsung melalui relasi User->bookings.
     */
    public function index(Request $request)
    {
        // Langsung ambil booking dari user yang terotentikasi melalui relasi
$bookings = $request->user()->bookings()->with(['room', 'guest'])->latest()->get();

        return response()->json($bookings);
    }
}