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
        Schema::table('orders', function (Blueprint $table) {
            // Menambahkan kolom booking_id setelah guest_id
            // nullable() agar tidak error jika ada order tanpa booking
            // constrained() agar terhubung ke tabel bookings
            if (!Schema::hasColumn('orders', 'booking_id')) {
                $table->foreignId('booking_id')
                      ->nullable()
                      ->after('guest_id')
                      ->constrained('bookings')
                      ->nullOnDelete(); 
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'booking_id')) {
                // Drop foreign key dulu baru kolomnya
                $table->dropForeign(['booking_id']);
                $table->dropColumn('booking_id');
            }
        });
    }
};