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
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis
            $table->integer('amount'); // Kolom untuk menyimpan jumlah pembayaran
            
            // Tambahkan kolom lain yang mungkin kamu butuhkan, contoh:
            // $table->foreignId('order_id')->constrained(); // Jika pembayaran terhubung ke order
            // $table->string('status')->default('pending'); // Status pembayaran
            // $table->string('method'); // Metode pembayaran (mis: 'cash', 'transfer')

            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
