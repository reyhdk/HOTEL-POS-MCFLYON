<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ServiceItemController extends Controller
{
    /**
     * Daftar semua service item (dengan filter kategori & status).
     */
    public function index(Request $request)
    {
        // Tambahkan relasi warehouseItem untuk mengecek koneksi ke gudang
        $query = ServiceItem::with(['assignedUser:id,name,email', 'warehouseItem:id,name,current_stock'])->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $items = $query->paginate(15);

        // Transformasi data untuk memastikan format full URL foto
        $items->getCollection()->transform(function ($item) {
            $item->photo_url = $item->photo_url 
                ? (str_starts_with($item->photo_url, 'http') ? $item->photo_url : asset('storage/' . $item->photo_url))
                : null;
            return $item;
        });

        return response()->json($items);
    }

    /**
     * Buat service item baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'category'         => 'required|string|max:50',
            'description'      => 'nullable|string|max:500',
            'max_quantity'     => 'required|integer|min:1|max:99',
            'is_active'        => 'boolean',
            'assigned_user_id' => 'nullable|exists:users,id',
            'warehouse_item_id'=> 'nullable|exists:warehouse_items,id', // Tambahan relasi ke gudang
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', 
        ]);

        try {
            // Proses Upload Foto Baru
            if ($request->hasFile('photo')) {
                $validated['photo_url'] = $request->file('photo')->store('service_items', 'public');
            }
            unset($validated['photo']);

            $item = ServiceItem::create($validated);
            
            $item->photo_url = $item->photo_url 
                ? (str_starts_with($item->photo_url, 'http') ? $item->photo_url : asset('storage/' . $item->photo_url)) 
                : null;

            return response()->json([
                'message' => 'Item layanan berhasil ditambahkan.',
                'data'    => $item->load('assignedUser:id,name', 'warehouseItem:id,name'),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Gagal buat service item: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Detail satu service item.
     */
    public function show(ServiceItem $serviceItem)
    {
        $serviceItem->photo_url = $serviceItem->photo_url 
                ? (str_starts_with($serviceItem->photo_url, 'http') ? $serviceItem->photo_url : asset('storage/' . $serviceItem->photo_url))
                : null;

        return response()->json($serviceItem->load('assignedUser:id,name,email', 'warehouseItem:id,name'));
    }

    /**
     * Update service item.
     */
    public function update(Request $request, ServiceItem $serviceItem)
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:100',
            'category'         => 'sometimes|required|string|max:50',
            'description'      => 'nullable|string|max:500',
            'max_quantity'     => 'sometimes|required|integer|min:1|max:99',
            'is_active'        => 'boolean',
            'assigned_user_id' => 'nullable|exists:users,id',
            'warehouse_item_id'=> 'nullable|exists:warehouse_items,id', // Tambahan relasi ke gudang
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            if ($request->hasFile('photo')) {
                if ($serviceItem->photo_url && !str_starts_with($serviceItem->photo_url, 'http') && Storage::disk('public')->exists($serviceItem->photo_url)) {
                    Storage::disk('public')->delete($serviceItem->photo_url);
                }
                $validated['photo_url'] = $request->file('photo')->store('service_items', 'public');
            }
            unset($validated['photo']);

            $serviceItem->update($validated);
            
            $serviceItem->photo_url = $serviceItem->photo_url 
                ? (str_starts_with($serviceItem->photo_url, 'http') ? $serviceItem->photo_url : asset('storage/' . $serviceItem->photo_url))
                : null;

            return response()->json([
                'message' => 'Item layanan berhasil diperbarui.',
                'data'    => $serviceItem->load('assignedUser:id,name', 'warehouseItem:id,name'),
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal update service item: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Hapus service item.
     */
    public function destroy(ServiceItem $serviceItem)
    {
        try {
            if ($serviceItem->photo_url && !str_starts_with($serviceItem->photo_url, 'http') && Storage::disk('public')->exists($serviceItem->photo_url)) {
                Storage::disk('public')->delete($serviceItem->photo_url);
            }

            $serviceItem->delete();
            return response()->json(['message' => 'Item layanan berhasil dihapus.']);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Gagal menghapus item (mungkin masih ada permintaan aktif).'], 409);
        }
    }

    /**
     * Daftar kategori unik yang sudah ada.
     */
    public function categories()
    {
        $cats = ServiceItem::distinct()->pluck('category')->filter()->values();
        return response()->json($cats);
    }

    /**
     * Daftar user yang bisa dijadikan petugas.
     * UPDATE: HANYA mengambil user dengan role 'housekeeping' atau 'house-keeping'
     */
    public function eligibleStaff()
    {
        $staff = User::whereHas('roles', function ($q) {
            // Sesuaikan nama role dengan yang ada di database Anda (misal: 'housekeeping' atau 'house-keeping')
            $q->whereIn('name', ['housekeeping', 'house-keeping']);
        })->select('id', 'name', 'email')->get();

        return response()->json($staff);
    }
}