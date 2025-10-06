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
            // Menambahkan kolom baru setelah kolom 'status'
            $table->string('midtrans_order_id')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Perintah untuk menghapus kolom jika migrasi di-rollback
            $table->dropColumn('midtrans_order_id');
        });
    }
};
