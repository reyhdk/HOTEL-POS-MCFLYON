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
        Schema::table('guests', function (Blueprint $table) {
            // Menambahkan kolom status verifikasi tamu
            // Default false (belum terverifikasi)
            $table->boolean('is_verified')->default(false)->after('phone_number');

            // Pastikan kolom ktp_image juga ada (jika belum)
            if (!Schema::hasColumn('guests', 'ktp_image')) {
                $table->string('ktp_image')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['is_verified']);
            // $table->dropColumn('ktp_image'); // Opsional jika ingin drop ktp_image juga
        });
    }
};
