<?php

namespace App\Http\Controllers\Api\Restopos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WarehouseItem;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TableController extends Controller
{
    /**
     * Mengambil daftar meja
     */
    public function index()
    {
        $tables = DB::table('tables')
            ->leftJoin('warehouse_items as t_item', 'tables.warehouse_table_id', '=', 't_item.id')
            ->leftJoin('warehouse_items as c_item', 'tables.warehouse_chair_id', '=', 'c_item.id')
            ->leftJoin('table_types', 'tables.type_id', '=', 'table_types.id')
            ->select(
                'tables.*', 
                't_item.name as table_item_name',
                'c_item.name as chair_item_name',
                'table_types.name as type_name'
            )
            ->orderBy('tables.name', 'asc')
            ->get();

        $formattedTables = $tables->map(function($table) {
            $statusLabel = 'Kosong';
            if ($table->status === 'occupied') $statusLabel = 'Terisi';
            if ($table->status === 'maintenance') $statusLabel = 'Perbaikan';
            
            return [
                'id' => $table->id,
                'name' => $table->name,
                'type_id' => $table->type_id,
                'type_name' => $table->type_name,
                'capacity' => $table->capacity,
                'status' => $table->status, 
                'status_label' => $statusLabel,
                'inventory' => [
                    'table_id' => $table->warehouse_table_id,
                    'table_name' => $table->table_item_name,
                    'chair_id' => $table->warehouse_chair_id,
                    'chair_name' => $table->chair_item_name
                ]
            ];
        });

        return response()->json($formattedTables);
    }

    /**
     * Store (Tambah Meja Baru & Potong Stok Gudang)
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'name' => 'required|string',
            'type_id' => 'required|integer|exists:table_types,id',
            'status' => 'required|in:available,occupied,maintenance',
            'capacity' => 'required|integer|min:1',
            'warehouse_table_id' => 'required|integer|exists:warehouse_items,id',
            'warehouse_chair_id' => 'required|integer|exists:warehouse_items,id',
            'additional_items' => 'nullable|array',
            'additional_items.*.id' => 'required|exists:warehouse_items,id',
            'additional_items.*.qty' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // 1. Cek Stok Meja Utama
            $tableItem = WarehouseItem::lockForUpdate()->find($request->warehouse_table_id);
            if ($tableItem->current_stock < 1) {
                return response()->json(['message' => "Stok '{$tableItem->name}' tidak cukup (Sisa: {$tableItem->current_stock})."], 400);
            }

            // 2. Cek Stok Kursi Utama
            $chairItem = WarehouseItem::lockForUpdate()->find($request->warehouse_chair_id);
            if ($chairItem->current_stock < $request->capacity) {
                return response()->json(['message' => "Stok '{$chairItem->name}' tidak cukup untuk {$request->capacity} kursi (Sisa: {$chairItem->current_stock})."], 400);
            }

            // 3. Cek Stok Item Tambahan
            $additionalItemsToProcess = [];
            if ($request->has('additional_items')) {
                foreach ($request->additional_items as $addItem) {
                    $whItem = WarehouseItem::lockForUpdate()->find($addItem['id']);
                    $needed = $addItem['qty'];
                    
                    if ($whItem->current_stock < $needed) {
                        return response()->json(['message' => "Stok tambahan '{$whItem->name}' tidak cukup (Butuh: {$needed}, Sisa: {$whItem->current_stock})."], 400);
                    }
                    
                    $additionalItemsToProcess[] = [
                        'item' => $whItem,
                        'qty' => $needed
                    ];
                }
            }

            // 4. Cek duplikasi nama meja
            if (DB::table('tables')->where('name', $request->name)->exists()) {
                return response()->json(['message' => 'Nama meja sudah digunakan.'], 400);
            }

            // 5. Eksekusi Potong Stok & Log
            
            // Meja
            $tableItem->decrement('current_stock', 1);
            $this->logTransaction($tableItem->id, 'out', 1, "Pembuatan Unit: {$request->name}");

            // Kursi
            $chairItem->decrement('current_stock', $request->capacity);
            $this->logTransaction($chairItem->id, 'out', $request->capacity, "Kursi untuk {$request->name}");

            // Item Tambahan
            foreach ($additionalItemsToProcess as $process) {
                $process['item']->decrement('current_stock', $process['qty']);
                $this->logTransaction($process['item']->id, 'out', $process['qty'], "Setup Tambahan {$request->name}: {$process['item']->name}");
            }

            // 6. Insert Database Meja
            $timestamp = Carbon::now()->toDateTimeString();
            
            $id = DB::table('tables')->insertGetId([
                'name' => $request->name,
                'type_id' => (int) $request->type_id,
                'capacity' => (int) $request->capacity,
                'status' => $request->status,
                'warehouse_table_id' => (int) $request->warehouse_table_id,
                'warehouse_chair_id' => (int) $request->warehouse_chair_id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ]);

            DB::commit();
            return response()->json(['message' => 'Meja berhasil ditambahkan', 'id' => $id], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Error create table: " . $e->getMessage());
            return response()->json(['message' => 'Gagal menambah meja: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'type_id' => 'required|integer|exists:table_types,id',
            'status' => 'required|in:available,occupied,maintenance'
        ]);

        // Cek duplikasi nama (kecuali untuk meja yang sedang diedit)
        $exists = DB::table('tables')
            ->where('name', $request->name)
            ->where('id', '!=', $id)
            ->exists();
            
        if ($exists) {
            return response()->json(['message' => 'Nama meja sudah digunakan.'], 400);
        }

        DB::table('tables')->where('id', $id)->update([
            'name' => $request->name,
            'type_id' => (int) $request->type_id,
            'status' => $request->status,
            'updated_at' => Carbon::now()->toDateTimeString()
        ]);

        return response()->json(['message' => 'Data meja diperbarui']);
    }

    public function replaceItem(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:table,chair',
            'quantity' => 'required|integer|min:1'
        ]);

        $table = DB::table('tables')->where('id', $id)->first();
        if (!$table) return response()->json(['message' => 'Meja tidak ditemukan'], 404);

        if ($table->status !== 'maintenance') {
            return response()->json(['message' => 'Penggantian hanya bisa dilakukan saat status meja "Perbaikan"'], 400);
        }

        $itemId = ($request->type === 'table') ? $table->warehouse_table_id : $table->warehouse_chair_id;
        if (!$itemId) return response()->json(['message' => 'Data inventaris meja/kursi belum terhubung.'], 400);

        DB::beginTransaction();
        try {
            $item = WarehouseItem::lockForUpdate()->find($itemId);
            
            if ($item->current_stock < $request->quantity) {
                return response()->json(['message' => "Stok gudang tidak cukup."], 400);
            }

            $item->decrement('current_stock', $request->quantity);
            
            $note = "Perbaikan {$table->name} (Ganti " . ($request->type === 'table' ? 'Meja' : 'Kursi') . ")";
            $this->logTransaction($item->id, 'out', $request->quantity, $note);

            DB::commit();
            return response()->json(['message' => "Berhasil mengganti unit. Stok gudang berkurang {$request->quantity}."]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses penggantian: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('tables')->where('id', $id)->delete();
        return response()->json(['message' => 'Meja berhasil dihapus']);
    }

    private function logTransaction($itemId, $type, $qty, $notes)
    {
        $item = WarehouseItem::find($itemId);
        $totalPrice = $qty * $item->cost_price;
        $timestamp = Carbon::now()->toDateTimeString();
        
        DB::table('stock_transactions')->insert([
            'transaction_code' => 'SYS/' . date('Ymd') . '/' . strtoupper(Str::random(5)),
            'warehouse_item_id' => $itemId,
            'transaction_type' => $type,
            'quantity' => $qty,
            'unit_price' => $item->cost_price,
            'total_price' => $totalPrice,
            'reference_type' => 'other',
            'notes' => $notes,
            'transaction_date' => $timestamp,
            'created_by' => auth()->id() ?? 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
    }
}