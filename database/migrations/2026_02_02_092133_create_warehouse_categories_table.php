<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Contoh: Bahan Baku, Peralatan
            $table->json('units'); // Menyimpan array satuan: ["kg", "gram", "ton"]
            $table->timestamps();
        });

        // Seed data awal (Optional, agar tidak kosong saat pertama kali)
        DB::table('warehouse_categories')->insert([
            [
                'name' => 'Bahan Baku',
                'units' => json_encode(['kg', 'gram', 'liter', 'ml']),
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Peralatan',
                'units' => json_encode(['pcs', 'unit', 'set']),
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Konsumsi',
                'units' => json_encode(['box', 'pack', 'pcs']),
                'created_at' => now(), 'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_categories');
    }
};