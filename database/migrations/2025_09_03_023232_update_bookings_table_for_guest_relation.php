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
        Schema::table('bookings', function (Blueprint $table) {
            // 1. Tambahkan kolom foreign key untuk guest_id
            $table->foreignId('guest_id')->nullable()->after('room_id')->constrained('guests')->onDelete('cascade');

            // 2. Hapus kolom-kolom lama (guest_phone sudah dihapus dari sini)
            $table->dropColumn(['guest_name', 'guest_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // 1. Tambahkan kembali kolom-kolom lama jika migrasi di-rollback
            $table->string('guest_name');
            $table->string('guest_email');

            // 2. Hapus foreign key dan kolom guest_id
            $table->dropForeign(['guest_id']);
            $table->dropColumn('guest_id');
        });
    }
};