<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Ubah kolom logo dan bg_auth jadi nullable
            $table->string('logo')->nullable()->change();
            $table->string('bg_auth')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('logo')->nullable(false)->change();
            $table->string('bg_auth')->nullable(false)->change();
        });
    }
};  