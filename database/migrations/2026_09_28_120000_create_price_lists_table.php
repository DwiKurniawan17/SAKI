<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('kategori')->default('connectivity');
            $table->string('paketisasi')->index(); // misal: Astinet, Indibiz, Metro-E
            $table->string('bandwidth')->index();  // misal: 10 Mbps, 50 Mbps
            $table->string('wilayah')->index();    // misal: Jabodetabek, Jawa, Sumatera, Kalimantan / KTI
            $table->decimal('tarif_normal_mrc', 15, 2)->default(0); // Harga normal bulanan
            $table->decimal('tarif_normal_otc', 15, 2)->default(0); // Biaya pasang baru (jika ada)
            $table->string('keterangan')->nullable();
            $table->timestamps();

            // Unique composite index untuk mencegah duplikasi & mendukung upsert updateOrCreate
            $table->unique(['paketisasi', 'bandwidth', 'wilayah'], 'uniq_paket_bw_wilayah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
