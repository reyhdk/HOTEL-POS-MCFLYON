<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Jangan truncate, cek dulu apakah sudah ada data
        $existing = Setting::first();
        
        if (!$existing) {
            Setting::create([
                'app' => 'HotelMcflyon',
                'description' => 'McflyonHotel – Nyaman, Modern, dan Strategis.',
                'logo' => null, // ✅ NULL
                'bg_auth' => null, // ✅ NULL
                'bg_landing' => null, // ✅ NULL
                'pemerintah' => 'McFlyon Hotel',
                'dinas' => 'Hospitality Services',
                'alamat' => 'Jl. Bungkal Gg. II No. 25B Kec/Kel. Sambikerep Kota Surabaya',
                'telepon' => '085174323674',
                'email' => 'admin@mcflyon.co.id',
            ]);
        }
    }
}