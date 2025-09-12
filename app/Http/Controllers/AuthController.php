<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Mengambil data user yang sedang login.
     */
    public function me()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'user' => auth()->user()
            ]
        ]);
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

        return response()->json([
            'status' => true,
            'data' => [
                'user' => auth()->user(),
                'token' => $token
            ]
        ]);
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