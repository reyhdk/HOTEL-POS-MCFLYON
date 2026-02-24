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
        // Tabel untuk menyimpan tipe-tipe meja
        Schema::create('table_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Tambahkan kolom type_id ke tabel tables
        Schema::table('tables', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable()->after('name');
            $table->foreign('type_id')->references('id')->on('table_types')->onDelete('set null');
        });

        // Insert beberapa tipe default
        DB::table('table_types')->insert([
            ['name' => 'Standar', 'description' => 'Meja standar biasa', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'VIP', 'description' => 'Meja VIP dengan fasilitas premium', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'VVIP', 'description' => 'Meja VVIP dengan fasilitas eksklusif', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
        });

        Schema::dropIfExists('table_types');
    }
};