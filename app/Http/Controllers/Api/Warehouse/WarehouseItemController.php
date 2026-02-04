<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WarehouseItemController extends Controller
{
    /**
     * Helper: Generate Kode Barang Otomatis (B001, B002, dst)
     */
    public function getNextCode()
    {
        $lastItem = WarehouseItem::orderBy('id', 'desc')->first();

        if (!$lastItem) {
            return response()->json(['code' => 'B001']);
        }

        if (preg_match('/([A-Za-z]+)(\d+)/', $lastItem->code, $matches)) {
            $prefix = $matches[1];
            $number = intval($matches[2]);
            $newNumber = $number + 1;
            $padLength = strlen($matches[2]); 
            $newCode = $prefix . str_pad($newNumber, $padLength, '0', STR_PAD_LEFT);
        } else {
            $newCode = 'B' . str_pad($lastItem->id + 1, 3, '0', STR_PAD_LEFT);
        }

        while (WarehouseItem::where('code', $newCode)->exists()) {
             $number = intval(filter_var($newCode, FILTER_SANITIZE_NUMBER_INT)) + 1;
             $newCode = 'B' . str_pad($number, 3, '0', STR_PAD_LEFT);
        }

        return response()->json(['code' => $newCode]);
    }

    /**
     * Menampilkan daftar barang dengan filter & pencarian
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 12);
            $search = $request->input('search');
            $category = $request->input('category');
            $status = $request->input('status');

            $query = WarehouseItem::query();

            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'low_stock') {
                $query->where('is_active', true)
                      ->whereColumn('current_stock', '<=', 'min_stock');
            }

            if ($category && $category !== 'all') {
                $query->where('category', $category);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('supplier', 'like', "%{$search}%");
                });
            }

            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $items = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $items->items(),
                'meta' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan barang baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:warehouse_items,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $item = WarehouseItem::create([
                'code' => $request->code,
                'name' => $request->name,
                'category' => $request->category,
                'unit' => $request->unit,
                'min_stock' => $request->min_stock,
                'current_stock' => $request->current_stock,
                'cost_price' => $request->cost_price,
                'supplier' => $request->supplier,
                'notes' => $request->notes,
                'is_active' => $request->is_active ?? true
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Barang berhasil ditambahkan', 'data' => $item], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan barang', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Data Barang
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:warehouse_items,code,' . $id,
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'unit' => 'required|string|max:20',
            'min_stock' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $item = WarehouseItem::findOrFail($id);
            $item->update($request->only([
                'code', 'name', 'category', 'unit', 'min_stock', 
                'cost_price', 'supplier', 'notes', 'is_active'
            ]));

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Barang berhasil diperbarui', 'data' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui barang', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show Detail Barang
     */
    public function show($id)
    {
        try {
            $item = WarehouseItem::with(['stockTransactions' => function($q) {
                $q->latest()->limit(10);
            }])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $item]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan'], 404);
        }
    }

    /**
     * Hapus Barang
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $item = WarehouseItem::findOrFail($id);
            if ($item->stockTransactions()->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus barang yang memiliki transaksi stok'], 400);
            }
            $item->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Barang berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus barang', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Adjust Stock - FIXED VERSION (BYPASS EVENTS)
     * 
     * Masalah: Ada Observer/Event yang otomatis update stok lagi setelah save()
     * Solusi: Update langsung ke database menggunakan DB::table() untuk bypass model events
     */
    public function adjustStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|numeric',
            'type' => 'required|in:increment,decrement,set',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Ambil data item
            $item = WarehouseItem::findOrFail($id);
            $oldStock = floatval($item->current_stock);
            $qty = floatval($request->quantity);
            
            // Validasi quantity
            if ($request->type === 'set') {
                if ($qty < 0) {
                    return response()->json(['success' => false, 'message' => 'Stok tidak boleh negatif'], 400);
                }
            } else {
                if ($qty <= 0) {
                    return response()->json(['success' => false, 'message' => 'Jumlah harus lebih dari 0'], 400);
                }
            }

            // Inisialisasi variabel
            $transactionType = 'adjustment';
            $quantityChanged = 0;
            $newStock = $oldStock;

            // Perhitungan berdasarkan type
            if ($request->type === 'increment') {
                $newStock = $oldStock + $qty;
                $quantityChanged = $qty;
                $transactionType = 'in';
                
            } elseif ($request->type === 'decrement') {
                if ($oldStock < $qty) {
                    return response()->json(['success' => false, 'message' => 'Stok tidak mencukupi'], 400);
                }
                $newStock = $oldStock - $qty;
                $quantityChanged = $qty;
                $transactionType = 'out';
                
            } elseif ($request->type === 'set') {
                $newStock = $qty;
                
                if ($newStock > $oldStock) {
                    $transactionType = 'in';
                    $quantityChanged = $newStock - $oldStock;
                } elseif ($newStock < $oldStock) {
                    $transactionType = 'out';
                    $quantityChanged = $oldStock - $newStock;
                } else {
                    $quantityChanged = 0;
                }
            }

            // ============================================
            // FIX: Update langsung ke database 
            // Bypass Model Events/Observers
            // ============================================
            DB::table('warehouse_items')
                ->where('id', $id)
                ->update([
                    'current_stock' => $newStock,
                    'updated_at' => now()
                ]);

            // Catat transaksi hanya jika ada perubahan
            if ($quantityChanged > 0) {
                $trxCode = 'ADJ/' . date('Ymd') . '/' . strtoupper(Str::random(5));

                DB::table('stock_transactions')->insert([
                    'transaction_code' => $trxCode,
                    'warehouse_item_id' => $id,
                    'transaction_type' => $transactionType,
                    'quantity' => $quantityChanged,
                    'unit_price' => $item->cost_price,
                    'total_price' => $quantityChanged * $item->cost_price,
                    'reference_type' => 'other',
                    'notes' => $request->notes ?? "Penyesuaian stok: {$oldStock} → {$newStock}",
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            
            // Refresh item untuk mendapatkan data terbaru
            $item->refresh();
            
            return response()->json([
                'success' => true, 
                'message' => 'Stok berhasil disesuaikan',
                'data' => [
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'quantity_changed' => $quantityChanged,
                    'transaction_type' => $transactionType
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyesuaikan stok: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Daftar Kategori & Satuan (Dinamis dari DB)
     */
    public function getCategories()
    {
        $categories = \DB::table('warehouse_categories')->get()->map(function($item) {
            return [
                'id' => $item->id,
                'value' => $item->name,
                'label' => $item->name,
                'units' => json_decode($item->units)
            ];
        });

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * Get Daftar Barang Stok Rendah
     */
    public function getLowStock()
    {
         $items = WarehouseItem::where('is_active', true)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->orderBy('current_stock', 'asc')
                ->get();
        return response()->json(['success' => true, 'data' => $items, 'count' => $items->count()]);
    }

    /**
     * Get Valuasi Stok (Total Nilai Aset Gudang)
     */
    public function getStockValue()
    {
        try {
            $totalValue = WarehouseItem::where('is_active', true)->sum(DB::raw('current_stock * cost_price'));
            $totalItems = WarehouseItem::where('is_active', true)->count();
            $lowStockItems = WarehouseItem::where('is_active', true)->whereColumn('current_stock', '<=', 'min_stock')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_value' => (float) $totalValue,
                    'total_items' => $totalItems,
                    'low_stock_items' => $lowStockItems,
                    'formatted_value' => 'Rp ' . number_format($totalValue, 0, ',', '.')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghitung valuasi stok'], 500);
        }
    }
}