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
        Schema::create('room_facility', function (Blueprint $table) {
            // Kolom untuk menghubungkan ke tabel 'rooms'
            $table->foreignId('room_id')->constrained()->onDelete('cascade');

            // Kolom untuk menghubungkan ke tabel 'facilities'
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');

            // Menjadikan kedua kolom sebagai primary key untuk mencegah duplikasi
            $table->primary(['room_id', 'facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_facility');
    }
};
