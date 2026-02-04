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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); // TRX/001/2024
            $table->foreignId('warehouse_item_id')->constrained('warehouse_items')->onDelete('cascade');
            $table->enum('transaction_type', ['in', 'out', 'adjustment', 'transfer']); // masuk, keluar, koreksi, transfer
            $table->decimal('quantity', 10, 2); // Jumlah
            $table->decimal('unit_price', 12, 2)->default(0); // Harga per unit
            $table->decimal('total_price', 12, 2)->default(0); // Total harga
            $table->enum('reference_type', ['purchase', 'production', 'waste', 'sale', 'audit', 'other'])->default('other');
            $table->unsignedBigInteger('reference_id')->nullable(); // ID dari order, menu, purchase, etc
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index untuk pencarian dan reporting
            $table->index(['transaction_code']);
            $table->index(['warehouse_item_id']);
            $table->index(['transaction_type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['transaction_date']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};