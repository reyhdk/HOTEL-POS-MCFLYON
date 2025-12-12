<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Helper function to create user response.
     */
    private function createAuthResponse($user, $token = null)
    {
        $response = [
            'status' => true,
            'data' => [
                // Load relasi 'roles' agar data role selalu ada
                'user' => $user->load('roles'),
                // Ambil semua nama permission yang dimiliki user
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]
        ];

        if ($token) {
            $response['data']['token'] = $token;
        }

        return response()->json($response);
    }

    /**
     * Mengambil data user yang sedang login.
     */
    public function me()
    {
        return $this->createAuthResponse(auth()->user());
    }

    /**
     * Proses login user.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Email atau Password salah!'
            ], 401);
        }

        return $this->createAuthResponse(auth()->user(), $token);
    }

    /**
     * Proses registrasi user baru.
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        try {
            $validatedData['password'] = Hash::make($validatedData['password']);

            $user = User::create($validatedData);

            // Berikan role 'user' secara default
            $user->assignRole('user');

            $token = auth()->login($user);

            return response()->json([
                'status' => true,
                'message' => 'Registrasi berhasil!',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registrasi Gagal: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada server, silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Update Profile (Nama, Password, Foto)
     * Endpoint: /api/auth/update-profile
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */ // <-- INI SOLUSI GARIS MERAHNYA
        $user = auth()->user();

        // 1. Validasi Input
        $request->validate([
            'name'     => 'required|string|max:255',
            'password' => 'nullable|string|min:6',
            'photo'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // 2. Update Nama
            $user->name = $request->name;

            // 3. Update Password (Hanya jika diisi)
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // 4. Update Foto
            if ($request->hasFile('photo')) {
                // Hapus foto lama jika ada (opsional, pastikan path benar)
                if ($user->photo && file_exists(public_path($user->photo))) {
                    // unlink(public_path($user->photo)); 
                }

                // Simpan file baru ke public/storage/avatars
                $path = $request->file('photo')->store('avatars', 'public');

                // Simpan path lengkap agar bisa diakses frontend
                $user->photo = '/storage/' . $path;
            }

            // Sekarang editor tahu $user adalah User Model, jadi save() tidak akan merah
            $user->save();

            return response()->json([
                'status'  => true,
                'message' => 'Profil berhasil diperbarui.',
                'data'    => [
                    'user' => $user->fresh(), // Ini juga tidak akan merah lagi
                    'photo_url' => $user->photo
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update Profile Gagal: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui profil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses logout user.
     */
    public function logout()
    {
        auth()->logout();
        return response()->json([
            'status' => true,
            'message' => 'Logout berhasil.'
        ]);
    }
}
