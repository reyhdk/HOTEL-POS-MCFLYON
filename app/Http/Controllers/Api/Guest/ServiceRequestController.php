<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    /**
     * Menampilkan riwayat permintaan layanan milik user yang sedang login.
     */
    public function index()
    {
        $user = Auth::user();

        $requests = ServiceRequest::where('user_id', $user->id)
                                  ->with('room') // Ambil info kamar terkait
                                  ->latest()     // Urutkan dari yang terbaru
                                  ->get();

        return response()->json($requests);
    }

    /**
     * Menyimpan permintaan layanan baru dari tamu.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasi bahwa tamu sedang check-in
        $activeCheckIn = CheckIn::where('is_active', true)
            ->whereHas('booking', fn($q) => $q->where('user_id', $user->id))
            ->first();

        if (!$activeCheckIn) {
            return response()->json(['message' => 'Anda harus sedang check-in untuk membuat permintaan.'], 403);
        }

        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'cleaning_time' => 'nullable|date_format:H:i',
        ]);

        $serviceRequest = ServiceRequest::create([
            'room_id' => $activeCheckIn->room_id,
            'user_id' => $user->id,
            'service_name' => $validated['service_name'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'],
            'cleaning_time' => $validated['cleaning_time'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan Anda telah berhasil dikirim.',
            'request' => $serviceRequest
        ], 201);
    }
}
