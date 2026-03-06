<?php

namespace App\Http\Controllers\Api\Laundry;

use App\Http\Controllers\Controller;
use App\Models\LaundryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaundryServiceController extends Controller
{
    /**
     * Menampilkan daftar layanan laundry
     */
    public function index()
    {
        $services = LaundryService::orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $services]);
    }

    /**
     * Menambahkan layanan baru (beserta estimasi bahan gudang)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'unit' => 'required|in:kg,pcs',
            'price' => 'required|numeric|min:0',
            'estimated_materials' => 'nullable|array', // JSON input dari frontend
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $service = LaundryService::create($request->all());

        return response()->json(['success' => true, 'message' => 'Layanan berhasil ditambahkan', 'data' => $service], 201);
    }

    /**
     * Menampilkan detail layanan
     */
    public function show($id)
    {
        $service = LaundryService::findOrFail($id);
        return response()->json(['success' => true, 'data' => $service]);
    }

    /**
     * Update layanan
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'unit' => 'sometimes|required|in:kg,pcs',
            'price' => 'sometimes|required|numeric|min:0',
            'estimated_materials' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $service = LaundryService::findOrFail($id);
        $service->update($request->all());

        return response()->json(['success' => true, 'message' => 'Layanan berhasil diupdate', 'data' => $service]);
    }

    /**
     * Hapus layanan
     */
    public function destroy($id)
    {
        $service = LaundryService::findOrFail($id);
        $service->delete();

        return response()->json(['success' => true, 'message' => 'Layanan berhasil dihapus']);
    }

    /**
     * Set / Update Estimasi Bahan Saja (Sesuai rute /services/{id}/materials)
     */
    public function setMaterials(Request $request, $id)
    {
        $request->validate(['estimated_materials' => 'required|array']);
        
        $service = LaundryService::findOrFail($id);
        $service->update(['estimated_materials' => $request->estimated_materials]);

        return response()->json(['success' => true, 'message' => 'Estimasi bahan berhasil disimpan', 'data' => $service]);
    }
}