<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('simulasi_aki') && !Schema::hasColumn('simulasi_aki', 'kategori')) {
            Schema::table('simulasi_aki', function (Blueprint $table) {
                // 'connectivity' atau 'non_connectivity'
                $table->string('kategori')->nullable()->after('lokasi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('simulasi_aki') && Schema::hasColumn('simulasi_aki', 'kategori')) {
            Schema::table('simulasi_aki', function (Blueprint $table) {
                $table->dropColumn('kategori');
            });
        }
    }
};
