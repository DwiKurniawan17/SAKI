<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisi_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulasi_id')->constrained('simulasi_aki')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('catatan');
            $table->timestamps();

            $table->index('simulasi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisi_log');
    }
};
