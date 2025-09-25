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

            // Berikan role 'user' (atau 'tamu' jika Anda punya) secara default
            $user->assignRole('user');

            $token = auth()->login($user);

            return response()->json([
                'status' => true,
                'message' => 'Registrasi berhasil!',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201); // Kode 201 menandakan "Created"

        } catch (\Exception $e) {
            Log::error('Registrasi Gagal: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan pada server, silakan coba lagi.'
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