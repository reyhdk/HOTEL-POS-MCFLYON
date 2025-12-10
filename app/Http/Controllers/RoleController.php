<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // <--- 1. JANGAN LUPA IMPORT INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Method BARU untuk mengambil semua permission
     * Digunakan oleh Form.vue di frontend
     */
    public function getAllPermissions()
    {
        $permissions = Permission::all();
        return response()->json($permissions);
    }

    public function index(Request $request)
    {
        if ($request->has('list_only')) {
            return Role::where('name', '!=', 'admin')->get();
        }

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Role::where('name', '!=', 'admin');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function store(RoleRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validatedData['name'],
                'guard_name' => 'api',
                'full_name' => $validatedData['full_name']
            ]);

            if (!empty($validatedData['permissions'])) {
                // Frontend mengirim array nama permission ['view users', 'edit roles']
                // Kita sync berdasarkan nama
                $role->syncPermissions($validatedData['permissions']);
            }

            DB::commit();
            return response()->json($role, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan role.'], 500);
        }
    }

    public function show(Role $role)
    {
        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'full_name' => $role->full_name,
            // Mengirimkan daftar nama permission yang dimiliki role ini
            'permissions' => $role->permissions->pluck('name'), 
            // Atau jika ingin object lengkap: $role->permissions
        ]);
    }

    public function update(RoleRequest $request, Role $role)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            $role->update([
                'name' => $validatedData['name'],
                'full_name' => $validatedData['full_name']
            ]);

            // Sync permissions jika ada
            if (isset($validatedData['permissions'])) {
                $role->syncPermissions($validatedData['permissions']);
            }

            DB::commit();
            return response()->json($role);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui role.'], 500);
        }
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return response()->json(['message' => 'Role Admin tidak dapat dihapus.'], 403);
        }

        $role->delete();
        return response()->json(null, 204);
    }
}