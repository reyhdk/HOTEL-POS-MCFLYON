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
        Schema::table('check_ins', function (Blueprint $table) {
            // Tambahkan kolom 'booking_id' setelah 'guest_id'
            // dan hubungkan sebagai foreign key ke tabel 'bookings'
            $table->foreignId('booking_id')
                  ->nullable()
                  ->after('guest_id')
                  ->constrained('bookings')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            // Hapus foreign key dan kolomnya jika migrasi di-rollback
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
};