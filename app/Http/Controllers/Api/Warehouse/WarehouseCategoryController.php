<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseCategory; // Pastikan Anda membuat Model ini: php artisan make:model WarehouseCategory
use Illuminate\Http\Request;

class WarehouseCategoryController extends Controller
{
    public function index()
    {
        // Ambil semua kategori beserta satuannya
        $categories = \DB::table('warehouse_categories')->get()->map(function($item) {
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
            'units' => 'required|array|min:1', // Harus array satuan
            'units.*' => 'required|string'
        ]);

        $id = \DB::table('warehouse_categories')->insertGetId([
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
        ]);

        \DB::table('warehouse_categories')->where('id', $id)->update([
            'name' => $request->name,
            'units' => json_encode($request->units),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Kategori berhasil diperbarui']);
    }

    public function destroy($id)
    {
        \DB::table('warehouse_categories')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Kategori dihapus']);
    }
}