<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission; // [TAMBAHAN] Import Model Permission

class RoleRequest extends FormRequest
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
        // 1. Ambil parameter 'role' dari URL (bisa berupa Object Model atau ID string)
        $role = $this->route('role');
        
        // 2. Ekstrak ID-nya secara aman.
        // Jika Route Binding aktif, $role adalah Object. Jika tidak, $role adalah ID (angka/string).
        $roleId = is_object($role) ? $role->id : $role;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // 3. PERBAIKAN: Ignore ID role ini saat pengecekan unique
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'full_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            
            // 4. PERBAIKAN VALIDASI PERMISSION (SOLUSI ERROR 422 "Field is required")
            // Kita hapus rule 'required' string, dan gunakan logic di dalam function
            'permissions.*' => [
                function ($attribute, $value, $fail) {
                    // Jika nilai kosong (null/empty string), kita skip/abaikan saja.
                    // Ini mengatasi error "permissions.0 field is required" jika frontend mengirim [""]
                    if (empty($value)) {
                        return;
                    }

                    $exists = false;
                    
                    // Jika input berupa angka, cek apakah ID permission ada
                    if (is_numeric($value)) {
                        $exists = Permission::where('id', $value)->exists();
                    } 
                    // Jika input berupa string, cek apakah Nama permission ada
                    elseif (is_string($value)) {
                        $exists = Permission::where('name', $value)->exists();
                    }

                    if (!$exists) {
                        $fail("Permission '$value' tidak valid (tidak ditemukan di database).");
                    }
                }
            ], 
        ];
    }
}