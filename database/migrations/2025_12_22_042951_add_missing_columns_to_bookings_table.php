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
        Schema::table('bookings', function (Blueprint $table) {
            // Hanya tambahkan kolom yang belum ada

            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('status');
            }

            if (!Schema::hasColumn('bookings', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('bookings', 'midtrans_checkout_id')) {
                $table->string('midtrans_checkout_id')->nullable()->after('midtrans_order_id');
            }

            if (!Schema::hasColumn('bookings', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verification_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('bookings', 'payment_method')) {
                $columns[] = 'payment_method';
            }
            if (Schema::hasColumn('bookings', 'checked_in_at')) {
                $columns[] = 'checked_in_at';
            }
            if (Schema::hasColumn('bookings', 'midtrans_checkout_id')) {
                $columns[] = 'midtrans_checkout_id';
            }
            if (Schema::hasColumn('bookings', 'rejection_reason')) {
                $columns[] = 'rejection_reason';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
