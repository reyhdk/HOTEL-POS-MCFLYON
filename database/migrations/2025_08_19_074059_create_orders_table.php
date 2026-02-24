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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // 1. Identifikasi & Kode
            $table->string('order_code', 50)->nullable()->index(); // Contoh: ORD/20260211/XXXXX
            $table->string('midtrans_order_id')->nullable()->index(); // ID Transaksi dari Midtrans (untuk callback)

            // 2. Relasi (Semua Nullable agar fleksibel untuk Dine In atau Room Service)
            // Menggunakan onDelete('set null') agar jika data master dihapus, riwayat pesanan tidak hilang (history tetap ada)
            $table->foreignId('chef_id')->nullable()->constrained('users')->onDelete('set null'); // Chef yang menangani pesanan
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Staff yang input
            $table->foreignId('guest_id')->nullable()->constrained('guests')->onDelete('set null'); // Link ke data tamu
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null'); // Link ke booking tamu (jika ada)
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null'); // Jika Room Service
            $table->foreignId('table_id')->nullable()->constrained('tables')->onDelete('set null'); // Jika Dine In

            // 3. Data Keuangan (Decimal lebih presisi untuk uang)
            $table->integer('estimate_time')->nullable(); 
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            
            // 4. Metode & Status
            $table->string('payment_method', 50)->default('cash'); // cash, midtrans, qris, transfer
            $table->string('status')->default('pending'); // pending, paid, completed, cancelled, failed
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};