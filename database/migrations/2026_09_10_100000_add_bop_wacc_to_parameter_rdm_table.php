<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parameter_rdm') && !Schema::hasColumn('parameter_rdm', 'bop_pct')) {
            Schema::table('parameter_rdm', function (Blueprint $table) {
                $table->decimal('bop_pct', 5, 2)->default(40.00)->nullable()->after('rdm_mrc_pct');
                $table->integer('lifetime')->default(4)->nullable()->after('bop_pct');
                $table->decimal('om_pct', 5, 2)->default(12.00)->nullable()->after('lifetime');
                $table->decimal('tax_pct', 5, 2)->default(22.00)->nullable()->after('om_pct');
                $table->decimal('wacc_pct', 5, 2)->default(10.90)->nullable()->after('tax_pct');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parameter_rdm') && Schema::hasColumn('parameter_rdm', 'bop_pct')) {
            Schema::table('parameter_rdm', function (Blueprint $table) {
                $table->dropColumn(['bop_pct', 'lifetime', 'om_pct', 'tax_pct', 'wacc_pct']);
            });
        }
    }
};
