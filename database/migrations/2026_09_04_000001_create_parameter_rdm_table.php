<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameter_rdm', function (Blueprint $table) {
            $table->id();
            $table->decimal('rdm_otc_pct', 5, 2)->default(25.00);
            $table->decimal('rdm_mrc_pct', 5, 2)->default(30.00);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Insert default initial row
        DB::table('parameter_rdm')->insert([
            'rdm_otc_pct' => 25.00,
            'rdm_mrc_pct' => 30.00,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parameter_rdm');
    }
};
