<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parameter_rdm') && !Schema::hasColumn('parameter_rdm', 'default_otc')) {
            Schema::table('parameter_rdm', function (Blueprint $table) {
                $table->decimal('default_otc', 15, 2)->default(2500000.00)->nullable()->after('wacc_pct');
            });

            DB::table('parameter_rdm')->whereNull('default_otc')->update(['default_otc' => 2500000.00]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parameter_rdm') && Schema::hasColumn('parameter_rdm', 'default_otc')) {
            Schema::table('parameter_rdm', function (Blueprint $table) {
                $table->dropColumn('default_otc');
            });
        }
    }
};
