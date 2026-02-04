<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menu_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('warehouse_item_id')->constrained('warehouse_items')->onDelete('cascade');
            // Jumlah yang dipakai (mengikuti satuan di warehouse, misal KG)
            // Contoh: Jika butuh 100gram dan satuan gudang KG, simpan 0.1
            $table->decimal('quantity', 10, 3); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_ingredients');
    }
};