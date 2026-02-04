<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Mengambil semua permission untuk Form.vue
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

        // PERBAIKAN 1: Tambahkan with('permissions') agar data permission terload di index
        // Ini mengatasi masalah "0 Hak Akses" di tampilan awal
        $query = Role::with('permissions')->where('name', '!=', 'admin');

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
            // Controller mengirim array of strings: ['view_users', 'edit_roles']
            'permissions' => $role->permissions->pluck('name'), 
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