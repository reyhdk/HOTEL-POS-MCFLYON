<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique(); // Kolom ini otomatis bernama 'uuid'

            $table->string('app');
            $table->text('description')->nullable(); // Tambahkan nullable jaga-jaga deskripsi kosong

            // Kolom-kolom gambar ini harus nullable karena di Seeder diset NULL
            $table->string('logo')->nullable();     // ✅ Tambahkan ->nullable()
            $table->string('banner')->nullable();   // ✅ Tambahkan ->nullable() (Solusi Error Utama)
            $table->string('bg_auth')->nullable();  // ✅ Tambahkan ->nullable()

            $table->string('dinas')->nullable();
            $table->string('pemerintah')->nullable();
            $table->string('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
