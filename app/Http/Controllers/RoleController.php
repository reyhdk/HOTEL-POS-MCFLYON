<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Spatie\Permission\Models\Role; // [DIBENARKAN] Menggunakan model Spatie langsung
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Menampilkan daftar role (bisa paginasi atau list lengkap).
     */
    public function index(Request $request)
    {
        // Jika hanya butuh list untuk dropdown
        if ($request->has('list_only')) {
            return Role::where('name', '!=', 'admin')->get();
        }

        // Logika paginasi untuk tabel
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Role::where('name', '!=', 'admin'); // Jangan tampilkan role superadmin

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Menyimpan role baru.
     */
    public function store(RoleRequest $request)
    {
        $validatedData = $request->validated();

        $role = Role::create([
            'name' => $validatedData['name'],
            'guard_name' => 'api',
            'full_name' => $validatedData['full_name']
        ]);

        if (!empty($validatedData['permissions'])) {
            $permissions = Permission::whereIn('name', $validatedData['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return response()->json($role, 201);
    }

    /**
     * Menampilkan satu role spesifik.
     */
    public function show(Role $role)
    {
        // Mengembalikan role beserta nama-nama permission-nya
        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'full_name' => $role->full_name,
            'permissions' => $role->permissions()->pluck('name')
        ]);
    }

    /**
     * Memperbarui role yang ada.
     */
    public function update(RoleRequest $request, Role $role)
    {
        $validatedData = $request->validated();

        $role->update([
            'name' => $validatedData['name'],
            'full_name' => $validatedData['full_name']
        ]);

        $permissions = Permission::whereIn('name', $validatedData['permissions'] ?? [])->get();
        $role->syncPermissions($permissions);

        return response()->json($role);
    }

    /**
     * Menghapus role.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return response()->json(['message' => 'Role Admin tidak dapat dihapus.'], 403);
        }

        $role->delete();

        return response()->json(null, 204);
    }
}
