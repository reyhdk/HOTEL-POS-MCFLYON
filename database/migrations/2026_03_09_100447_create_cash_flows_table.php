<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->dateTime('transaction_date');
            $table->enum('type', ['income', 'expense']);
            $table->enum('category', ['booking', 'resto', 'laundry', 'warehouse', 'other']);
            $table->string('description');
            $table->string('payment_method'); // Cash, Midtrans, Transfer, dll
            $table->decimal('amount', 15, 2);
            $table->string('reference_id')->nullable(); // Order ID atau Booking ID
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Siapa yang mencatat
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_flows');
    }
};