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
        return Menu::with('ingredients')->latest()->get();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            // [UPDATE] Tambah validasi estimasi
            'cooking_estimation_time' => 'required|integer|min:1', 
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            // [UPDATE] Tambah validasi estimasi
            'cooking_estimation_time' => 'required|integer|min:1',
            'image' => 'nullable|sometimes',
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
        $menu->delete();
        return response()->json(null, 204);
    }
}