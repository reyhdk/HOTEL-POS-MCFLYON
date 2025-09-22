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
     * Menampilkan daftar user (bisa paginasi atau list lengkap).
     */
    public function index(Request $request)
    {
        if ($request->has('list_only')) {
            return User::whereHas('roles', fn($q) => $q->where('name', '!=', 'admin'))->get();
        }

        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = User::with('roles')->whereHas('roles', fn($q) => $q->where('name', '!=', 'admin'));

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

        if ($request->hasFile('photo')) {
            $validatedData['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $user = User::create($validatedData);
        $user->assignRole($validatedData['role_name']);

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
        $validatedData = $request->validated();

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) Storage::disk('public')->delete($user->photo);
            $validatedData['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $user->update($validatedData);
        $user->syncRoles([$validatedData['role_name']]);

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
