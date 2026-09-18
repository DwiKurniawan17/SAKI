<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('simulasi_aki') && !Schema::hasColumn('simulasi_aki', 'bandwidth')) {
            Schema::table('simulasi_aki', function (Blueprint $table) {
                $table->string('bandwidth')->nullable()->after('paketisasi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('simulasi_aki') && Schema::hasColumn('simulasi_aki', 'bandwidth')) {
            Schema::table('simulasi_aki', function (Blueprint $table) {
                $table->dropColumn('bandwidth');
            });
        }
    }
};
