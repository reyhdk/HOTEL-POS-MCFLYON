<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ServiceRequestController extends Controller
{
    /**
     * Method #1: Menampilkan semua permintaan layanan untuk panel admin,
     * dengan filter status.
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::with(['room', 'user'])->latest();

        // Tambahkan filter berdasarkan status jika ada
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $serviceRequests = $query->paginate(10); // Gunakan paginasi

        return response()->json($serviceRequests);
    }

    /**
     * Method #2: Memperbarui status sebuah permintaan layanan.
     */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        try {
            $serviceRequest->update(['status' => $validated['status']]);

            // [LOGIKA SINKRONISASI BARU]
            // Jika status diubah menjadi 'completed' dan nama layanannya adalah 'Pembersihan Kamar'
            if ($validated['status'] === 'completed' && $serviceRequest->service_name === 'Pembersihan Kamar') {
                $room = $serviceRequest->room;
                if ($room) {
                    // Periksa apakah kamar masih ditempati atau tidak
                    $hasActiveCheckIn = $room->checkIns()->where('is_active', true)->exists();
                    $newRoomStatus = $hasActiveCheckIn ? 'occupied' : 'available';
                    $room->update(['status' => $newRoomStatus]);
                }
            }

            return response()->json([
                'message' => 'Status permintaan berhasil diperbarui.',
                'request' => $serviceRequest,
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal update status permintaan layanan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui status.'], 500);
        }
    }
}