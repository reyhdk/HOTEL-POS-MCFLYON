<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('rooms', function (Blueprint $table) {
        $table->date('tersedia_mulai')->nullable()->after('price_per_night');
        $table->date('tersedia_sampai')->nullable()->after('tersedia_mulai');
    });
}

public function down(): void
{
    Schema::table('rooms', function (Blueprint $table) {
        $table->dropColumn(['tersedia_mulai', 'tersedia_sampai']);
    });
}
};
