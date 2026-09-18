<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulasi_aki', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id_pihak_b')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('simulasi_aki', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id_pihak_b')->nullable(false)->change();
        });
    }
};
