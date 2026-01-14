<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Mengambil konfigurasi website.
     * Jika belum ada di database, kembalikan nilai default.
     */
    public function index()
    {
        $setting = Setting::first();

        // Jika data belum ada, return default values
        if (!$setting) {
            return response()->json([
                'app' => 'Nama Hotel',
                'description' => 'Deskripsi hotel default...',
                'logo' => null,
                'bg_auth' => null,
                'bg_landing' => null,
                'check_in_time' => '14:00', // Default Check-in
                'check_out_time' => '12:00', // Default Check-out
                'early_check_in_fee' => 0,   // Default Fee
            ]);
        }

        return response()->json($setting);
    }

    /**
     * Memperbarui konfigurasi website (termasuk Waktu Check-in/out, Fee & Gambar).
     */
    public function update(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'app' => 'required|string|max:255',
            'description' => 'required|string',

            // Validasi Format Jam (HH:MM) contoh: 14:00
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i',

            // [BARU] Validasi Fee Early Check-in (Boleh kosong, harus angka, minimal 0)
            'early_check_in_fee' => 'nullable|integer|min:0',

            // Validasi Gambar
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
            'bg_auth' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096', // Max 4MB
            'bg_landing' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Ambil atau Buat Data Setting (Single Row)
        $setting = Setting::first();
        if (!$setting) {
            $setting = new Setting();
        }

        // 3. Update Data Teks
        $setting->app = $request->app;
        $setting->description = $request->description;
        $setting->check_in_time = $request->check_in_time;
        $setting->check_out_time = $request->check_out_time;

        // [BARU] Simpan Fee
        // Jika user tidak mengisi (kosong), kita set jadi 0 agar aman
        $setting->early_check_in_fee = $request->filled('early_check_in_fee') ? $request->early_check_in_fee : 0;

        // 4. Proses Upload Gambar (Helper Logic)

        // --- LOGO ---
        if ($request->hasFile('logo')) {
            // Hapus file lama jika ada
            if ($setting->logo) {
                $oldPath = str_replace('/storage/', '', $setting->logo);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            // Simpan file baru
            $file = $request->file('logo');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('settings', $filename, 'public');
            $setting->logo = '/storage/' . $path;
        }

        // --- BACKGROUND AUTH ---
        if ($request->hasFile('bg_auth')) {
            if ($setting->bg_auth) {
                $oldPath = str_replace('/storage/', '', $setting->bg_auth);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $file = $request->file('bg_auth');
            $filename = 'bg_auth_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('settings', $filename, 'public');
            $setting->bg_auth = '/storage/' . $path;
        }

        // --- BACKGROUND LANDING ---
        if ($request->hasFile('bg_landing')) {
            if ($setting->bg_landing) {
                $oldPath = str_replace('/storage/', '', $setting->bg_landing);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $file = $request->file('bg_landing');
            $filename = 'bg_landing_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('settings', $filename, 'public');
            $setting->bg_landing = '/storage/' . $path;
        }

        // 5. Simpan ke Database
        $setting->save();

        return response()->json([
            'message' => 'Pengaturan berhasil diperbarui!',
            'data' => $setting
        ]);
    }
}
