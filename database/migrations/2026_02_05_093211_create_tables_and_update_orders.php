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
        // 1. Buat Tabel Meja (Tables)
        if (!Schema::hasTable('tables')) {
            Schema::create('tables', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // Contoh: "Meja 01", "VIP 1"
                // Status sesuai logika Frontend & Controller: available, occupied, maintenance
                $table->unsignedBigInteger('warehouse_table_id')->nullable();
            // Menyimpan ID barang dari gudang untuk Kursi
                $table->unsignedBigInteger('warehouse_chair_id')->nullable();
                $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
                $table->integer('capacity')->default(4)->nullable(); // Kapasitas kursi
                $table->string('location')->nullable(); // Contoh: "Indoor", "Outdoor", "Lantai 2"
                $table->timestamps();
            });
        }

        // 2. Update Tabel Orders (Menambahkan table_id)
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Tambahkan table_id (Nullable, karena pesanan bisa dari Kamar atau Meja)
                // Diletakkan setelah room_id agar rapi
                $table->foreignId('table_id')
                      ->nullable()
                      ->after('room_id')
                      ->constrained('tables')
                      ->nullOnDelete();

                // PENTING: Jika sebelumnya room_id di tabel orders bersifat wajib (NOT NULL),
                // Anda mungkin perlu mengubahnya menjadi NULLABLE agar pesanan Dine-In (tanpa kamar) bisa masuk.
                // $table->unsignedBigInteger('room_id')->nullable()->change(); 
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus kolom table_id dari orders
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Cek jika kolom ada sebelum drop (untuk menghindari error rollback)
                if (Schema::hasColumn('orders', 'table_id')) {
                    $table->dropForeign(['table_id']); // Hapus foreign key dulu
                    $table->dropColumn('table_id');
                }
            });
        }

        // 2. Hapus tabel tables
        Schema::dropIfExists('tables');
    }
};