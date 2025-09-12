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
    Schema::create('check_ins', function (Blueprint $table) {
        $table->id();
        $table->foreignId('room_id')->constrained('rooms');
        $table->foreignId('guest_id')->constrained('guests');
        $table->timestamp('check_in_time');
        $table->timestamp('check_out_time')->nullable();
        $table->boolean('is_active')->default(true); // Menandakan apakah tamu masih aktif menginap
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
