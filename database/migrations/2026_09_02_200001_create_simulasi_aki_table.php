<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulasi_aki', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id_pihak_b')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id_pihak_a')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft_pihak_b');
            $table->string('nama_proyek');
            $table->string('lokasi')->nullable();
            // Encrypted at-rest via Laravel cast 'encrypted:array'
            $table->longText('input_json')->nullable();
            $table->longText('hasil_json')->nullable();
            $table->decimal('npv', 20, 4)->nullable();
            $table->decimal('mirr', 10, 6)->nullable();
            $table->decimal('payback_period', 10, 4)->nullable();
            $table->boolean('layak')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id_pihak_b');
            $table->index('user_id_pihak_a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulasi_aki');
    }
};
