<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // 1. Ambil object user yang sedang diedit dari URL/Route
        // Karena di api.php: Route::apiResource('users', ...), parameternya bernama 'user'
        $user = $this->route('user'); 
        
        // Pastikan kita mendapatkan ID-nya (baik jika Route Binding aktif maupun tidak)
        $userId = $user instanceof \App\Models\User ? $user->id : $user;

        return [
            'name' => 'required|string|min:3|max:255',
            
            'email' => [
                'required',
                'email',
                // 2. PERBAIKAN: Ignore ID dari user yang sedang DIEDIT ($userId), bukan user yang login ($this->user->id)
                Rule::unique('users', 'email')->ignore($userId)
            ],
            
            'phone' => [
                'nullable', // Ubah ke nullable jika phone opsional
                'string',
                'max:20',
                // Ignore juga untuk phone
                Rule::unique('users', 'phone')->ignore($userId)
            ],

            // 3. PERBAIKAN: Gunakan 'role_name' sesuai yang dikirim Form.vue
            // Validasi bahwa role_name tersebut ada di tabel roles kolom name
            'role_name' => 'required|string|exists:roles,name',
            
            'photo' => 'nullable|image|max:2048', // Max 2MB
            
            // Password opsional saat update
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}