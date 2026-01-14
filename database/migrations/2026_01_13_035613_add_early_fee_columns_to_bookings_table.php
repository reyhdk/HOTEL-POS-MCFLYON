<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // 1. Kita cek dulu apakah kolom total_price ada, untuk penempatan
            $afterColumn = Schema::hasColumn('bookings', 'total_price') ? 'total_price' : null;

            // 2. Buat kolom fee
            if ($afterColumn) {
                $table->decimal('early_check_in_fee', 15, 2)->default(0)->after($afterColumn);
            } else {
                $table->decimal('early_check_in_fee', 15, 2)->default(0);
            }

            // 3. Buat kolom status, letakkan SETELAH early_check_in_fee (Pasti aman)
            $table->string('early_payment_status')->default('unpaid')->after('early_check_in_fee');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['early_check_in_fee', 'early_payment_status']);
        });
    }
};
