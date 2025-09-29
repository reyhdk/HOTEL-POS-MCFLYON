<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->time('cleaning_time')->nullable()->after('notes');
        });
    }
    public function down(): void {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('cleaning_time');
        });
    }
};
