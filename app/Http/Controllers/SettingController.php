<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        
        if (!$setting) {
            return response()->json([
                'app' => '',
                'description' => '',
                'logo' => null,
                'bg_auth' => null,
                'bg_landing' => null
            ]);
        }

        return response()->json($setting);
    }

    public function update(Request $request)
    {
        $request->validate([
            'app' => 'required|string|max:255',
            'description' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'bg_auth' => 'nullable|image|mimes:jpeg,png,jpg|max:8192',
            'bg_landing' => 'nullable|image|mimes:jpeg,png,jpg|max:8192',
        ]);

        $setting = Setting::first();

        if (!$setting) {
            $setting = new Setting();
        }

        $data = $request->only(['app', 'description']);

        // --- UPLOAD LOGO ---
        if ($request->hasFile('logo')) {
            // Hapus logo lama
            if ($setting->logo) {
                $oldPath = str_replace('/storage/', '', $setting->logo);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            // Upload logo baru dengan nama unik
            $logoFile = $request->file('logo');
            $logoName = 'logo_' . time() . '.' . $logoFile->getClientOriginalExtension();
            $logoPath = $logoFile->storeAs('setting', $logoName, 'public');
            $data['logo'] = '/storage/' . $logoPath;
        }

        // --- UPLOAD BACKGROUND AUTH ---
        if ($request->hasFile('bg_auth')) {
            // Hapus background lama
            if ($setting->bg_auth) {
                $oldPath = str_replace('/storage/', '', $setting->bg_auth);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            // Upload baru dengan nama unik
            $bgAuthFile = $request->file('bg_auth');
            $bgAuthName = 'bg_auth_' . time() . '.' . $bgAuthFile->getClientOriginalExtension();
            $bgAuthPath = $bgAuthFile->storeAs('setting', $bgAuthName, 'public');
            $data['bg_auth'] = '/storage/' . $bgAuthPath;
        }

        // --- UPLOAD BACKGROUND LANDING ---
        if ($request->hasFile('bg_landing')) {
            // Hapus background lama
            if ($setting->bg_landing) {
                $oldPath = str_replace('/storage/', '', $setting->bg_landing);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            // Upload baru dengan nama unik
            $bgLandingFile = $request->file('bg_landing');
            $bgLandingName = 'bg_landing_' . time() . '.' . $bgLandingFile->getClientOriginalExtension();
            $bgLandingPath = $bgLandingFile->storeAs('setting', $bgLandingName, 'public');
            $data['bg_landing'] = '/storage/' . $bgLandingPath;
        }

        // Simpan ke database
        if (!$setting->exists) {
            $setting->fill($data);
            $setting->save();
        } else {
            $setting->update($data);
        }

        return response()->json([
            'message' => 'Berhasil memperbarui konfigurasi website',
            'data' => $setting->fresh() 
        ]);
    }
}