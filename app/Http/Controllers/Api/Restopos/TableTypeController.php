<?php

namespace App\Http\Controllers\Api\Restopos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TableTypeController extends Controller
{
    /**
     * Mendapatkan daftar tipe meja
     */
    public function index()
    {
        $types = DB::table('table_types')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($types);
    }

    /**
     * Menyimpan tipe meja baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:table_types,name',
            'description' => 'nullable|string|max:255'
        ]);

        $timestamp = Carbon::now()->toDateTimeString();

        $id = DB::table('table_types')->insertGetId([
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);

        return response()->json([
            'message' => 'Tipe meja berhasil ditambahkan',
            'data' => DB::table('table_types')->find($id)
        ], 201);
    }

    /**
     * Update tipe meja
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:table_types,name,' . $id,
            'description' => 'nullable|string|max:255'
        ]);

        DB::table('table_types')->where('id', $id)->update([
            'name' => $request->name,
            'description' => $request->description,
            'updated_at' => Carbon::now()->toDateTimeString()
        ]);

        return response()->json([
            'message' => 'Tipe meja berhasil diperbarui',
            'data' => DB::table('table_types')->find($id)
        ]);
    }

    /**
     * Hapus tipe meja
     */
    public function destroy($id)
    {
        // Cek apakah ada meja yang menggunakan tipe ini
        $tablesUsingType = DB::table('tables')->where('type_id', $id)->count();
        
        if ($tablesUsingType > 0) {
            return response()->json([
                'message' => "Tidak dapat menghapus tipe ini. Masih ada {$tablesUsingType} meja yang menggunakannya."
            ], 400);
        }

        DB::table('table_types')->where('id', $id)->delete();

        return response()->json(['message' => 'Tipe meja berhasil dihapus']);
    }
}