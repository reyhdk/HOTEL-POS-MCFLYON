<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage; // <-- 1. Jangan lupa import Storage

class FacilityController extends Controller
{
    /**
     * [TAMBAHKAN INI]
     * Menerapkan otorisasi berbasis permission secara otomatis.
     */
    public function __construct()
    {
        $this->authorizeResource(Facility::class, 'facility');
    }


    /**
     * Menampilkan daftar semua fasilitas.
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
        // 2. Sesuaikan validasi untuk menerima file gambar
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name',
            'icon' => 'nullable|image|mimes:svg,png,jpg|max:1024', // Validasi untuk gambar
            'description' => 'nullable|string',
        ]);

        // 3. Proses upload gambar jika ada
        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('public/facilities');
            $validatedData['icon'] = $path; // Simpan path file ke database
        }

        $facility = Facility::create($validatedData);

        return response()->json($facility, 201);
    }

    /**
     * Menampilkan satu fasilitas spesifik.
     */
    public function show(Facility $facility): JsonResponse
    {
        return response()->json($facility);
    }

    /**
     * Memperbarui fasilitas yang sudah ada.
     */
    public function update(Request $request, Facility $facility): JsonResponse
    {
        // 4. Sesuaikan validasi untuk update gambar
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:facilities,name,' . $facility->id,
            'icon' => 'nullable|sometimes|image|mimes:svg,png,jpg|max:1024',
            'description' => 'nullable|string',
        ]);

        // 5. Proses update gambar jika ada file baru
        if ($request->hasFile('icon')) {
            // Hapus gambar lama terlebih dahulu
            if ($facility->icon) {
                Storage::delete($facility->icon);
            }
            // Simpan gambar baru
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
        // 6. Hapus juga file gambar dari storage
        if ($facility->icon) {
            Storage::delete($facility->icon);
        }

        $facility->delete();
        return response()->json(null, 204);
    }
}
