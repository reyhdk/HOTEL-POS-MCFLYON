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
        Schema::create('rooms', function (Blueprint $table) {
        $table->id();
        $table->string('room_number')->unique(); // No Kamar
        $table->string('type'); // Contoh: Standard, Deluxe, Suite
        $table->string('status')->default('available'); // Contoh: available, occupied, maintenance
        $table->integer('price_per_night'); // Harga per malam
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
