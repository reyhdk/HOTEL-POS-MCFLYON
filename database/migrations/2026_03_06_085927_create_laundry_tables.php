<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tabel Layanan Laundry (Termasuk Estimasi Bahan JSON)
        Schema::create('laundry_services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // cth: Cuci Kiloan Lengkap
            $table->enum('unit', ['kg', 'pcs']);
            $table->decimal('price', 12, 2);
            
            // Kolom JSON pengganti tabel pivot estimasi bahan. 
            // Nanti isinya array: [{"warehouse_item_id": 1, "qty": 50}, {"warehouse_item_id": 2, "qty": 30}]
            $table->json('estimated_materials')->nullable(); 
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Request/Order Laundry dari Tamu
        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users'); // Tamu
            $table->string('room_number');
            $table->foreignId('staff_id')->nullable()->constrained('users'); // Petugas
            $table->enum('status', ['requested', 'picked_up', 'processing', 'delivered', 'canceled'])->default('requested');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();
        });

        // 3. Detail Item yang di laundry
        Schema::create('laundry_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laundry_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('laundry_service_id')->constrained();
            $table->decimal('qty', 8, 2); // Jumlah kg/pcs
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('laundry_order_items');
        Schema::dropIfExists('laundry_orders');
        Schema::dropIfExists('laundry_services');
    }
};