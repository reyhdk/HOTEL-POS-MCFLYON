<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Nama barang: "Handuk Tambahan", "Sikat Gigi"
            $table->string('photo_url')->nullable();                   // URL foto barang (jika ada)
            $table->string('category');                     // Kategori: "Amenities", "Laundry", "Housekeeping"
            $table->text('description')->nullable();        // Deskripsi singkat
            $table->integer('max_quantity')->default(5);    // Maksimal order per request
            $table->boolean('is_active')->default(true);    // Aktif/nonaktif
            $table->foreignId('assigned_user_id')           // Petugas yang bertugas handle item ini
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });

        // Tambah kolom service_item_id & assigned_to ke service_requests
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('service_item_id')
                ->nullable()
                ->after('user_id')
                ->constrained('service_items')
                ->nullOnDelete();
            $table->foreignId('assigned_to')               // Petugas yang di-assign untuk request ini
                ->nullable()
                ->after('service_item_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('category')->nullable()->after('service_name'); // Cache category dari item
            $table->timestamp('schedule_time')->nullable()->after('notes'); // Jadwal pengerjaan
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['service_item_id']);
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['service_item_id', 'assigned_to', 'category', 'schedule_time']);
        });
        Schema::dropIfExists('service_items');
    }
};