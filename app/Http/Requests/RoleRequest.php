<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Pastikan ini di-import

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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Ambil ID role dari route, jika ada (saat proses update)
        $roleId = $this->route('role') ? $this->route('role')->id : null;

        return [
            'full_name' => 'required|string|max:255',

            // Aturan ini sudah benar di kode Anda
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',

            // [PERBAIKAN KECIL TAPI PENTING]
            // Aturan untuk 'name' yang lebih aman
            'name' => [
                'required',
                'string',
                'max:255',
                // Pastikan 'name' unik, tapi abaikan ID role yang sedang diedit
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
        ];
    }
}
