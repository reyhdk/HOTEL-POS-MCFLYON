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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('user_id')->nullable()->constrained('users'); // Untuk tamu yang sudah punya akun
            $table->string('guest_name'); // Untuk tamu yang booking tanpa login
            $table->string('guest_email');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('total_price');
            $table->string('status')->default('confirmed'); // Contoh: confirmed, checked_in, checked_out, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};