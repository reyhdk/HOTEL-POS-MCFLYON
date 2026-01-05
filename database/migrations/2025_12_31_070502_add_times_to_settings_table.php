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
        Schema::table('settings', function (Blueprint $table) {
            // Tambahkan kolom waktu jika belum ada
            if (!Schema::hasColumn('settings', 'check_in_time')) {
                $table->string('check_in_time')->default('14:00')->after('description');
            }
            if (!Schema::hasColumn('settings', 'check_out_time')) {
                $table->string('check_out_time')->default('12:00')->after('check_in_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['check_in_time', 'check_out_time']);
        });
    }
};