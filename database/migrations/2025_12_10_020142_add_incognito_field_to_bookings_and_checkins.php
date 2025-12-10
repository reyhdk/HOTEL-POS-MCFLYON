<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    // Menambahkan kolom status incognito ke tabel bookings
    Schema::table('bookings', function (Blueprint $table) {
        $table->boolean('is_incognito')->default(false)->after('status');
    });

    // Menambahkan kolom status incognito ke tabel check_ins (agar cepat diakses saat operasional)
    Schema::table('check_ins', function (Blueprint $table) {
        $table->boolean('is_incognito')->default(false)->after('is_active');
    });
}

public function down()
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropColumn('is_incognito');
    });
    Schema::table('check_ins', function (Blueprint $table) {
        $table->dropColumn('is_incognito');
    });
}
};
