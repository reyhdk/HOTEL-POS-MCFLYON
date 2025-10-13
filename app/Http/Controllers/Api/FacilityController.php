<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FacilityController extends Controller
{
    /**
     * [DIHAPUS] Method __construct() dihapus untuk menghindari error.
     */

    /**
     * Menampilkan daftar semua fasilitas (PUBLIK).
     * Tidak ada otorisasi di sini.
     */
    public function index(): JsonResponse
    {
        $facilities = Facility::latest()->get();
        return response()->json($facilities);
    }

    /**
     * Menyimpan fasilitas baru ke database.
     */
    public function store(Request $request): JsonResponse
    {
        // Otorisasi manual: Periksa apakah user punya izin 'create facilities'
        $this->authorize('create', Facility::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name',
            'icon' => 'nullable|image|mimes:svg,png,jpg|max:1024',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('public/facilities');
            $validatedData['icon'] = $path;
        }

        $facility = Facility::create($validatedData);
        return response()->json($facility, 201);
    }

    /**
     * Menampilkan satu fasilitas spesifik.
     */
    public function show(Facility $facility): JsonResponse
    {
        // Otorisasi manual: Periksa apakah user punya izin 'view facilities'
        $this->authorize('view', $facility);
        return response()->json($facility);
    }

    /**
     * Memperbarui fasilitas yang sudah ada.
     */
    public function update(Request $request, Facility $facility): JsonResponse
    {
        // Otorisasi manual: Periksa apakah user punya izin 'edit facilities'
        $this->authorize('update', $facility);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name,' . $facility->id,
            'icon' => 'nullable|sometimes|image|mimes:svg,png,jpg|max:1024',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('icon')) {
            if ($facility->icon) {
                Storage::delete($facility->icon);
            }
            $path = $request->file('icon')->store('public/facilities');
            $validatedData['icon'] = $path;
        }

        $facility->update($validatedData);
        return response()->json($facility);
    }

    /**
     * Menghapus sebuah fasilitas.
     */
    public function destroy(Facility $facility): JsonResponse
    {
        // Otorisasi manual: Periksa apakah user punya izin 'delete facilities'
        $this->authorize('delete', $facility);

        if ($facility->icon) {
            Storage::delete($facility->icon);
        }

        $facility->delete();
        return response()->json(null, 204);
    }
}
