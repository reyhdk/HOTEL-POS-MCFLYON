<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB; 
use Throwable;

class ServiceRequestController extends Controller
{
    /**
     * Menampilkan riwayat permintaan layanan milik user yang sedang login.
     */
    public function index()
    {
        $user = Auth::user();
        $requests = ServiceRequest::where('user_id', $user->id)->with('room')->latest()->get();
        return response()->json($requests);
    }

    /**
     * Menyimpan permintaan layanan baru DAN menyinkronkan status kamar.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
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

        try {
            // 3. Gunakan DB Transaction untuk memastikan kedua aksi berhasil
            $serviceRequest = DB::transaction(function () use ($validated, $user, $activeCheckIn) {
                
                // Aksi 1: Buat record Permintaan Layanan
                $newRequest = ServiceRequest::create([
                    'room_id' => $activeCheckIn->room_id,
                    'user_id' => $user->id,
                    'service_name' => $validated['service_name'],
                    'quantity' => $validated['quantity'],
                    'notes' => $validated['notes'],
                    'cleaning_time' => $validated['cleaning_time'] ?? null,
                    'status' => 'pending',
                ]);

                // Aksi 2: [SINKRONISASI] Jika permintaannya adalah pembersihan, update status kamar
                if ($validated['service_name'] === 'Pembersihan Kamar') {
                    $room = Room::find($activeCheckIn->room_id);
                    // Pastikan kamar ada dan statusnya sedang 'occupied'
                    if ($room && $room->status === 'occupied') {
                        $room->update(['status' => 'request cleaning']);
                    }
                }
                
                return $newRequest;
            });
            
            return response()->json([
                'message' => 'Permintaan Anda telah berhasil dikirim.',
                'request' => $serviceRequest
            ], 201);

        } catch (Throwable $e) {
            Log::error('Gagal membuat permintaan layanan oleh tamu: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat membuat permintaan.'], 500);
        }
    }
}