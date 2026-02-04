<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index()
    {
        // Load ingredients agar bisa diedit di frontend
        return Menu::with('ingredients')->latest()->get();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            // Validasi Array Ingredients
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|exists:warehouse_items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('public/menus');
                $validatedData['image'] = $path;
            }

            $menu = Menu::create($validatedData);

            // Simpan Resep/Ingredients
            if (!empty($request->ingredients)) {
                $ingredientsData = [];
                foreach ($request->ingredients as $ing) {
                    $ingredientsData[$ing['id']] = ['quantity' => $ing['quantity']];
                }
                $menu->ingredients()->sync($ingredientsData);
            }

            DB::commit();
            return response()->json($menu->load('ingredients'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan menu: ' . $e->getMessage()], 500);
        }
    }

    public function show(Menu $menu)
    {
        return $menu->load('ingredients');
    }

    public function update(Request $request, Menu $menu)
    {
       $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name,' . $menu->id,
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|sometimes',
            // Validasi Ingredients
            'ingredients' => 'nullable|array',
            'ingredients.*.id' => 'required|exists:warehouse_items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                if ($menu->image) {
                    Storage::delete($menu->image);
                }
                $path = $request->file('image')->store('public/menus');
                $validatedData['image'] = $path;
            }

            $menu->update($validatedData);

            // Sync Resep/Ingredients (Hapus yang lama, ganti yang baru sesuai input)
            if (isset($request->ingredients)) {
                $ingredientsData = [];
                foreach ($request->ingredients as $ing) {
                    $ingredientsData[$ing['id']] = ['quantity' => $ing['quantity']];
                }
                $menu->ingredients()->sync($ingredientsData);
            }

            DB::commit();
            return response()->json($menu->load('ingredients'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update menu: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Menu $menu)
    {
        if ($menu->image) {
            Storage::delete($menu->image);
        }
        // Ingredients di pivot table otomatis terhapus karena cascading di migration
        $menu->delete();

        return response()->json(null, 204);
    }
}