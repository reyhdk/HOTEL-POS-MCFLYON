<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role; // [DITAMBAHKAN] Import model Role

class UserController extends Controller
{
    /**
     * Menampilkan daftar user dengan filter & pencarian.
     */
    public function index(Request $request)
    {
        // Ambil parameter filter
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $roleFilter = $request->input('role'); // <--- Tambahan Filter

        // Query Dasar (Eager Load Roles)
        $query = User::with('roles')
            // Sembunyikan user 'admin' utama dari list agar tidak terhapus tidak sengaja
            ->whereHas('roles', fn($q) => $q->where('name', '!=', 'admin'));

        // Logic Filter Role
        if ($roleFilter) {
            $query->whereHas('roles', fn($q) => $q->where('name', $roleFilter));
        }

        // Logic Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Menyimpan user baru.
     */
    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);

        // --- TAMBAHAN LOGIC UPLOAD FOTO ---
        if ($request->hasFile('photo')) {
            // Simpan ke storage/public/photos
            $path = $request->file('photo')->store('photos', 'public');
            // Simpan url lengkap agar frontend mudah mengakses
            $validatedData['photo'] = '/storage/' . $path;
        }
        // ----------------------------------

        $user = User::create($validatedData);

        // Assign Role
        if (isset($validatedData['role_name'])) {
            $user->assignRole($validatedData['role_name']);
        } else {
            $user->assignRole('user'); // Default role
        }

        return response()->json($user->load('roles'), 201);
    }

    /**
     * Menampilkan satu user spesifik.
     */
    public function show(User $user)
    {
        return $user->load('roles');
    }

    /**
     * Memperbarui user yang ada.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Gunakan rules dari request, tapi password nullable di frontend
        $validatedData = $request->validated();

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        // --- TAMBAHAN LOGIC UPLOAD FOTO ---
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada & file fisiknya ada
            if ($user->photo && file_exists(public_path($user->photo))) {
                // Hati-hati menghapus, pastikan path sesuai. 
                // Karena kita simpan '/storage/photos/..', kita perlu parsing pathnya
                $relativePath = str_replace('/storage/', '', $user->photo);
                Storage::disk('public')->delete($relativePath);
            }

            $path = $request->file('photo')->store('photos', 'public');
            $validatedData['photo'] = '/storage/' . $path;
        }
        // ----------------------------------

        $user->update($validatedData);

        if (isset($validatedData['role_name'])) {
            $user->syncRoles([$validatedData['role_name']]);
        }

        return response()->json($user->load('roles'));
    }

    /**
     * Menghapus user.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 403);
        }

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }
        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * [DITAMBAHKAN] Method untuk mengambil semua role untuk dropdown di form.
     */
    public function getAllRoles()
    {
        // Ambil semua role kecuali 'admin' dan 'user' (tamu)
        return Role::whereNotIn('name', ['admin', 'user'])->get();
    }
}
