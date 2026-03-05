<?php

namespace App\Http\Controllers\Api\Admin;

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
            'serviceItem:id,name,category,warehouse_item_id',
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
     * Memperbarui status sebuah permintaan layanan dan MEMOTONG STOK GUDANG otomatis.
     */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $serviceRequest->status;
            $serviceRequest->update(['status' => $validated['status']]);

            // ==========================================
            // LOGIKA PEMOTONGAN STOK GUDANG OTOMATIS
            // ==========================================
            // Jika status diubah ke 'completed' dan sebelumnya bukan 'completed'
            if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
                
                $serviceItem = \App\Models\ServiceItem::find($serviceRequest->service_item_id);
                
                // Cek apakah item layanan ini nge-link ke barang gudang
                if ($serviceItem && $serviceItem->warehouse_item_id) {
                    $warehouseItem = \App\Models\WarehouseItem::find($serviceItem->warehouse_item_id);
                    
                    if ($warehouseItem) {
                        $qtyToDeduct = $serviceRequest->quantity;
                        $oldStock = $warehouseItem->current_stock;
                        $newStock = $oldStock - $qtyToDeduct;

                        // 1. Potong Stok Langsung di tabel warehouse_items
                        DB::table('warehouse_items')
                            ->where('id', $warehouseItem->id)
                            ->decrement('current_stock', $qtyToDeduct);

                        // 2. Catat riwayat di tabel stock_transactions
                        $trxCode = 'SVC/' . date('Ymd') . '/' . strtoupper(Str::random(5));
                        
                        DB::table('stock_transactions')->insert([
                            'transaction_code' => $trxCode,
                            'warehouse_item_id' => $warehouseItem->id,
                            'transaction_type' => 'out',
                            'quantity' => $qtyToDeduct,
                            'unit_price' => $warehouseItem->cost_price,
                            'total_price' => $qtyToDeduct * $warehouseItem->cost_price,
                            'reference_type' => 'other', // <-- UBAH KE 'other' AGAR TIDAK ERROR ENUM
                            'notes' => "Penggunaan Layanan Tamu: {$serviceRequest->service_name} (Kamar " . ($serviceRequest->room->room_number ?? '-') . ")",
                            'transaction_date' => now(),
                            'created_by' => auth()->id(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            // Sinkronisasi: jika cleaning request selesai → update status kamar
            if ($validated['status'] === 'completed' && $serviceRequest->service_name === 'Pembersihan Kamar') {
                $room = $serviceRequest->room;
                if ($room) {
                    $hasActiveCheckIn = $room->checkIns()->where('is_active', true)->exists();
                    $room->update(['status' => $hasActiveCheckIn ? 'occupied' : 'available']);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Status permintaan berhasil diperbarui.',
                'request' => $serviceRequest,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Gagal update status permintaan layanan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui status.'], 500);
        }
    }

    /**
     * Assign petugas ke permintaan layanan.
     */
    public function assignStaff(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        try {
            $serviceRequest->update([
                'assigned_to' => $validated['assigned_to'],
                'status'      => 'processing',
            ]);

            return response()->json([
                'message' => 'Petugas berhasil ditugaskan.',
                'request' => $serviceRequest->load('assignedTo:id,name'),
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal assign staff: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menugaskan petugas.'], 500);
        }
    }
}