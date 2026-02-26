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
        Schema::create('chefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('specialization')->nullable(); // Spesialisasi chef (Italian, Chinese, Pastry, dll)
            $table->boolean('is_active')->default(true); // Status aktif/nonaktif
            $table->integer('max_concurrent_orders')->default(5); // Maksimal pesanan bersamaan
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->timestamps();
            
            // Index untuk performa
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chefs');
    }
};