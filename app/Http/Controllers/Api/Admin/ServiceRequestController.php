<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * Menampilkan daftar semua permintaan layanan.
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::with('room', 'user')->latest();

        // Fitur filter berdasarkan status (opsional tapi bagus)
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        return $query->paginate(10);
    }

    /**
     * Memperbarui status sebuah permintaan layanan.
     */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $serviceRequest->status = $validated['status'];
        $serviceRequest->save();

        return response()->json([
            'message' => 'Status permintaan berhasil diperbarui.',
            'request' => $serviceRequest,
        ]);
    }
}
