<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Menambahkan kolom checked_in_at setelah kolom status
            // Harus nullable (boleh kosong) karena saat booking dibuat, tamu belum datang
            $table->timestamp('checked_in_at')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Untuk menghapus kolom jika rollback
            $table->dropColumn('checked_in_at');
        });
    }
};
