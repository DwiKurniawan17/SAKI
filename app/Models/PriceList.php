<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    protected $table = 'price_lists';

    protected $fillable = [
        'kategori',
        'paketisasi',
        'bandwidth',
        'wilayah',
        'tarif_normal_mrc',
        'tarif_normal_otc',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tarif_normal_mrc' => 'float',
            'tarif_normal_otc' => 'float',
        ];
    }

    /**
     * Cari tarif normal berdasarkan paket, bandwidth, dan wilayah.
     * Pencarian dibuat fleksibel & cerdas:
     * - Case-insensitive & pembersihan whitespace
     * - Toleransi penulisan bandwidth ("10", "10 Mbps", "10Mbps")
     * - Toleransi penamaan paket (misal cari "Astinet" tetap cocok ke "Astinet Global")
     * - Toleransi alias wilayah (misal "Witel Jakarta" cocok ke "Jabodetabek")
     */
    public static function findTarif(?string $paket, ?string $bandwidth, ?string $wilayah, ?string $tipeLayanan = null): ?self
    {
        if (!$paket || !$bandwidth) {
            return null;
        }

        $cleanPaket = trim($paket);
        $cleanBw = trim($bandwidth);
        $bwNum = preg_replace('/[^0-9.]/', '', $cleanBw);

        // Jika user menyertakan tipe layanan secara eksplisit (Global / Domestik)
        if ($tipeLayanan) {
            $cleanPaket = $cleanPaket . ' ' . trim($tipeLayanan);
        }

        $lowerPaket = strtolower($cleanPaket);

        $query = static::where(function ($q) use ($lowerPaket) {
            // Prioritas 1: Kecocokan persis
            $q->whereRaw('LOWER(TRIM(paketisasi)) = ?', [$lowerPaket])
              // Prioritas 2: Awalan paket (misal "Astinet" cocok ke "Astinet Global")
              ->orWhereRaw('LOWER(TRIM(paketisasi)) LIKE ?', [$lowerPaket . ' %'])
              // Prioritas 3: Mengandung nama paket
              ->orWhereRaw('LOWER(TRIM(paketisasi)) LIKE ?', ['%' . $lowerPaket . '%']);
        })
        ->where(function ($q) use ($cleanBw, $bwNum) {
            $q->whereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($cleanBw)])
              ->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . ' mbps')])
              ->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum)]);
        });

        if ($wilayah) {
            $cleanWilayah = trim($wilayah);
            $lowerW = strtolower($cleanWilayah);

            // Deteksi sinonim/alias wilayah umum Telkom
            $aliasW = null;
            if (str_contains($lowerW, 'jabo') || str_contains($lowerW, 'jakarta') || str_contains($lowerW, 'dki') || str_contains($lowerW, 'bogor') || str_contains($lowerW, 'bekasi') || str_contains($lowerW, 'tangerang')) {
                $aliasW = 'jabodetabek';
            } elseif (str_contains($lowerW, 'jawa') || str_contains($lowerW, 'bandung') || str_contains($lowerW, 'semarang') || str_contains($lowerW, 'surabaya')) {
                $aliasW = 'jawa';
            } elseif (str_contains($lowerW, 'sumat') || str_contains($lowerW, 'medan') || str_contains($lowerW, 'palembang')) {
                $aliasW = 'sumatera';
            } elseif (str_contains($lowerW, 'kalimantan') || str_contains($lowerW, 'kti') || str_contains($lowerW, 'sulawesi') || str_contains($lowerW, 'makassar') || str_contains($lowerW, 'papua')) {
                $aliasW = 'kalimantan';
            }

            $record = (clone $query)->where(function ($q) use ($cleanWilayah, $aliasW) {
                $q->whereRaw('LOWER(TRIM(wilayah)) = ?', [strtolower($cleanWilayah)])
                  ->orWhereRaw('LOWER(TRIM(wilayah)) LIKE ?', ['%' . strtolower($cleanWilayah) . '%']);
                if ($aliasW) {
                    $q->orWhereRaw('LOWER(TRIM(wilayah)) LIKE ?', ['%' . $aliasW . '%']);
                }
            })
            // Utamakan yang namanya paling persis cocok
            ->orderByRaw("CASE WHEN LOWER(TRIM(paketisasi)) = '{$lowerPaket}' THEN 1 ELSE 2 END")
            ->first();

            if ($record) {
                return $record;
            }
        }

        // Fallback jika wilayah tidak spesifik atau tidak ditemukan
        return $query->orderByRaw("CASE WHEN LOWER(TRIM(paketisasi)) = '{$lowerPaket}' THEN 1 ELSE 2 END")->first();
    }
}
