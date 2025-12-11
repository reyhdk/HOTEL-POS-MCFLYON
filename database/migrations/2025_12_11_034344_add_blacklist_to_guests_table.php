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

            $table->boolean('is_blacklisted')->default(false)->after('address');

            // Menambahkan kolom alasan blacklist (Boleh kosong/nullable)
            $table->text('blacklist_reason')->nullable()->after('is_blacklisted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Menghapus kolom jika migrasi di-rollback
            $table->dropColumn(['is_blacklisted', 'blacklist_reason']);
        });
    }
};
