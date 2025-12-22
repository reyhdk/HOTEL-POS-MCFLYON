<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_xx_xx_add_verification_columns_to_bookings_table.php

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Cek apakah kolom ktp_image sudah ada? Jika belum, buat.
            if (!Schema::hasColumn('bookings', 'ktp_image')) {
                $table->string('ktp_image')->nullable()->after('guest_id');
            }

            // Cek apakah kolom verification_status sudah ada?
            if (!Schema::hasColumn('bookings', 'verification_status')) {
                $table->string('verification_status')->default('pending')->after('status');
            }

            // Cek apakah kolom rejection_reason sudah ada?
            if (!Schema::hasColumn('bookings', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verification_status');
            }
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['ktp_image', 'verification_status', 'rejection_reason']);
        });
    }
};
