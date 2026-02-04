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
        Schema::create('warehouse_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Kode unik: B001, A001
            $table->string('name');
            // UBAH: Category jadi string agar dinamis (relasi ke warehouse_categories nama atau id)
            // Disini saya simpan namanya saja agar query simpel, atau bisa diganti foreignId jika mau strict
            $table->string('category'); 
            $table->string('unit'); // kg, gram, pcs, liter, etc
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0); // Harga beli per unit
            // HAPUS: selling_price dihapus sesuai request
            
            $table->string('supplier')->nullable(); 
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['code']);
            $table->index(['name']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_items');
    }
};