<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulasi_aki', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('user_id_pihak_a')->constrained('users')->nullOnDelete();
            $table->text('catatan_revisi')->nullable()->after('layak');
        });
    }

    public function down(): void
    {
        Schema::table('simulasi_aki', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['reviewed_by', 'catatan_revisi']);
        });
    }
};
