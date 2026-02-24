<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tables', function (Blueprint $table) {
            // Menyimpan ID barang dari gudang untuk Meja fisik
            $table->unsignedBigInteger('warehouse_table_id')->nullable()->after('status');
            // Menyimpan ID barang dari gudang untuk Kursi
            $table->unsignedBigInteger('warehouse_chair_id')->nullable()->after('warehouse_table_id');
            // Jumlah kursi per meja
        });
    }

    public function down()
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['warehouse_table_id', 'warehouse_chair_id']);
        });
    }
};