<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseCategoryController extends Controller
{
    public function index()
    {
        // Ambil semua kategori beserta satuannya
        $categories = DB::table('warehouse_categories')->get()->map(function($item) {
            $item->units = json_decode($item->units);
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:warehouse_categories,name',
            'units' => 'required|array|min:1', 
            // HAPUS atau UBAH validasi 'units.*' => 'required|string'
            // Ganti dengan validasi struktur object:
            'units.*.name' => 'required|string',
            'units.*.precision' => 'required|integer' 
        ]);

        $id = DB::table('warehouse_categories')->insertGetId([
            'name' => $request->name,
            'units' => json_encode($request->units),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Kategori berhasil disimpan']);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:warehouse_categories,name,' . $id,
            'units' => 'required|array',
            // Tambahkan validasi konsisten (opsional tapi disarankan)
            'units.*.name' => 'required|string',
            'units.*.precision' => 'required|integer'
        ]);

        DB::table('warehouse_categories')->where('id', $id)->update([
            'name' => $request->name,
            'units' => json_encode($request->units),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Kategori berhasil diperbarui']);
    }

    public function destroy($id)
    {
        DB::table('warehouse_categories')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Kategori dihapus']);
    }

    public function updateIcons(Request $request)
    {
        $request->validate([
            'icons' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Looping data map { "Makanan": "ki-burger", "Minuman": "ki-coffee" }
            foreach ($request->icons as $categoryName => $iconName) {
                DB::table('warehouse_categories')
                    ->where('name', $categoryName)
                    ->update([
                        'icon' => $iconName,
                        'updated_at' => now()
                    ]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ikon kategori berhasil diperbarui']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui ikon', 'error' => $e->getMessage()], 500);
        }
    }
}