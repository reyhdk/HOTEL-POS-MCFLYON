<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * [STANDARISASI] Mengambil daftar semua resource untuk select/dropdown.
     * Method 'get' tidak standar, bisa digabungkan ke 'index'.
     */
    public function index(Request $request)
    {
        // Jika permintaan hanya butuh list tanpa paginasi (untuk dropdown)
        if ($request->has('list_only')) {
            return response()->json([
                'success' => true,
                'data' => User::when($request->role_id, function (Builder $query, string $role_id) {
                    $query->role($role_id);
                })->get()
            ]);
        }

        // Logika paginasi yang sudah ada
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = User::query()->with('roles'); // Selalu muat relasi roles

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $users = $query->latest()->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Menyimpan resource baru.
     */
    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('photo')) {
            $validatedData['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $user = User::create($validatedData);
        $user->assignRole($validatedData['role_id']);

        return response()->json(['success' => true, 'user' => $user], 201);
    }

    /**
     * Menampilkan satu resource spesifik.
     */
    public function show(User $user)
    {
        // Memuat relasi agar role_id selalu ada di respons
        $user->load('roles'); 
        $user['role_id'] = $user->roles->first()->id ?? null;

        return response()->json(['user' => $user]);
    }

    /**
     * Memperbarui resource yang sudah ada.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validatedData = $request->validated();

        if ($request->hasFile('photo')) {
            if ($user->photo) Storage::disk('public')->delete($user->photo);
            $validatedData['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $user->update($validatedData);
        $user->syncRoles([$validatedData['role_id']]);

        return response()->json(['success' => true, 'user' => $user]);
    }

    /**
     * Menghapus resource.
     */
    public function destroy(User $user)
    {
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        return response()->json(['success' => true], 204); // 204 No Content lebih tepat
    }
}