<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulasi_aki', function (Blueprint $table) {
            if (!Schema::hasColumn('simulasi_aki', 'marketing_updated_at')) {
                $table->timestamp('marketing_updated_at')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('simulasi_aki', function (Blueprint $table) {
            if (Schema::hasColumn('simulasi_aki', 'marketing_updated_at')) {
                $table->dropColumn('marketing_updated_at');
            }
        });
    }
};
