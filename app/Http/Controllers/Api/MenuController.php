<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    public function index()
    {
        return Menu::latest()->get();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name',
            'category' => 'required|string|max:255', // Hapus jika tidak ada kolom category
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // [DIUBAH] Simpan path file, bukan URL lengkap
            $path = $request->file('image')->store('public/menus');
            $validatedData['image'] = $path;
        }

        $menu = Menu::create($validatedData);

        return response()->json($menu, 201);
    }

    public function show(Menu $menu)
    {
        return $menu;
    }

    /**
     * [DIUBAH] Nama method diganti menjadi 'update' agar sesuai dengan standar Route::apiResource
     */
    public function update(Request $request, Menu $menu)
    {
       $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name,' . $menu->id,
            // 'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|sometimes', // 'sometimes' agar tidak wajib diupdate
        ]);

        if ($request->hasFile('image')) {
            // [DIUBAH] Logika hapus gambar menjadi lebih sederhana
            if ($menu->image) {
                Storage::delete($menu->image);
            }

            // [DIUBAH] Simpan path file, bukan URL lengkap
            $path = $request->file('image')->store('public/menus');
            $validatedData['image'] = $path;
        }

        $menu->update($validatedData);

        return response()->json($menu);
    }

    public function destroy(Menu $menu)
    {
        // [DIUBAH] Logika hapus gambar menjadi lebih sederhana
        if ($menu->image) {
            Storage::delete($menu->image);
        }
        $menu->delete();

        return response()->json(null, 204);
    }
}
