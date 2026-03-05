<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ServiceRequestController extends Controller
{
    /**
     * Menampilkan semua permintaan layanan untuk panel admin.
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::with([
            'room:id,room_number,type',
            'user:id,name,email',
            'assignedTo:id,name',       
            'serviceItem:id,name,category,warehouse_item_id', // Ambil info relasi gudang
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('room_number', 'like', '%' . $request->search . '%');
            })->orWhereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })->orWhere('service_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $raw = $query->paginate(10);

        $raw->getCollection()->transform(function ($req) {
            return [
                'id'              => $req->id,
                'room_number'     => $req->room?->room_number ?? '-',
                'guest_name'      => $req->user?->name ?? '-',
                'service_name'    => $req->service_name,
                'category'        => $req->category ?? $req->serviceItem?->category ?? '-',
                'quantity'        => $req->quantity,
                'notes'           => $req->notes,
                'status'          => $req->status,
                'schedule_time'   => $req->schedule_time,
                'assigned_to'     => $req->assignedTo ? [
                    'id'   => $req->assignedTo->id,
                    'name' => $req->assignedTo->name,
                ] : null,
                'created_at'      => $req->created_at,
            ];
        });

        return response()->json($raw);
    }

    /**
     * Membuat permintaan layanan baru dari sisi Tamu
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name'  => 'required|string|max:150',
            'quantity'      => 'required|integer|min:1',
            'notes'         => 'nullable|string|max:500',
            'cleaning_time' => 'nullable|string',
        ]);

        $user = $request->user();

        // Cari sesi CheckIn yang aktif
        $activeCheckIn = \App\Models\CheckIn::where('is_active', true)
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$activeCheckIn) {
            return response()->json([
                'message' => 'Anda tidak memiliki sesi check-in yang aktif.'
            ], 403);
        }

        try {
            // Coba ambil kategori dari ServiceItem (bisa jadi ini custom request, jadi bisa null)
            $serviceItem = \App\Models\ServiceItem::where('name', $validated['service_name'])->first();

            $serviceRequest = \App\Models\ServiceRequest::create([
                'room_id'       => $activeCheckIn->room_id,
                'user_id'       => $user->id,
                'service_item_id'=> $serviceItem ? $serviceItem->id : null,
                'service_name'  => $validated['service_name'],
                'category'      => $serviceItem ? $serviceItem->category : 'Permintaan Khusus', // Jika tidak ada, masuk kategori Permintaan Khusus
                'quantity'      => $validated['quantity'],
                'notes'         => $validated['notes'],
                'schedule_time' => $validated['cleaning_time'],
                'status'        => 'pending',
            ]);

            return response()->json([
                'message' => 'Permintaan berhasil dikirim.',
                'data'    => $serviceRequest
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Gagal membuat service request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan permintaan.'], 500);
        }
    }
}