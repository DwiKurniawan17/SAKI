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
        'tarif_lama_mrc',
        'tarif_normal_otc',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tarif_normal_mrc' => 'float',
            'tarif_lama_mrc'   => 'float',
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

        // Jika user menyertakan tipe layanan secara eksplisit (Global / Domestik / Best Effort / Critical)
        if ($tipeLayanan) {
            $cleanPaket = $cleanPaket . ' ' . trim($tipeLayanan);
        }

        $lowerPaket = strtolower($cleanPaket);

        // Deteksi satuan bandwidth (Kbps / Mbps / Gbps)
        $bwUnit = '';
        if (preg_match('/(kbps|mbps|gbps)/i', $cleanBw, $mUnit)) {
            $bwUnit = strtolower($mUnit[1]);
        }

        $query = static::where(function ($q) use ($lowerPaket) {
            // Prioritas 1: Kecocokan persis
            $q->whereRaw('LOWER(TRIM(paketisasi)) = ?', [$lowerPaket])
              // Prioritas 2: Awalan paket (misal "Astinet" cocok ke "Astinet Best Effort")
              ->orWhereRaw('LOWER(TRIM(paketisasi)) LIKE ?', [$lowerPaket . ' %'])
              // Prioritas 3: Mengandung nama paket
              ->orWhereRaw('LOWER(TRIM(paketisasi)) LIKE ?', ['%' . $lowerPaket . '%']);
        })
        ->where(function ($q) use ($cleanBw, $bwNum, $bwUnit) {
            $q->whereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($cleanBw)])
              ->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum)]);

            if ($bwUnit === 'kbps') {
                $q->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . ' kbps')])
                  ->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . 'kbps')]);
            } elseif ($bwUnit === 'gbps') {
                $q->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . ' gbps')])
                  ->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . 'gbps')]);
            } else {
                // Default Mbps atau pure number
                $q->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . ' mbps')])
                  ->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . 'mbps')]);
                if (empty($bwUnit)) {
                    $q->orWhereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower($bwNum . ' kbps')]);
                }
            }
        });

        if ($wilayah) {
            $cleanWilayah = trim($wilayah);
            $lowerW = strtolower($cleanWilayah);

            // Deteksi daftar sinonim / alias wilayah Telkom
            $regionAliases = [];
            if (str_contains($lowerW, 'jabo') || str_contains($lowerW, 'jakarta') || str_contains($lowerW, 'dki') || str_contains($lowerW, 'bogor') || str_contains($lowerW, 'bekasi') || str_contains($lowerW, 'tangerang') || str_contains($lowerW, 'depok')) {
                $regionAliases[] = 'jabodetabek';
            }
            if (str_contains($lowerW, 'jabar') || str_contains($lowerW, 'jateng') || str_contains($lowerW, 'bandung') || str_contains($lowerW, 'semarang') || str_contains($lowerW, 'solo') || str_contains($lowerW, 'cirebon') || str_contains($lowerW, 'jogja') || str_contains($lowerW, 'yogyakarta')) {
                $regionAliases[] = 'jabar & jateng';
                $regionAliases[] = 'jawa';
                $regionAliases[] = 'jawa barat';
                $regionAliases[] = 'jawa tengah';
            }
            if (str_contains($lowerW, 'jatim') || str_contains($lowerW, 'surabaya') || str_contains($lowerW, 'malang') || str_contains($lowerW, 'balnus') || str_contains($lowerW, 'bali') || str_contains($lowerW, 'denpasar') || str_contains($lowerW, 'lombok') || str_contains($lowerW, 'mataram') || str_contains($lowerW, 'kupang') || str_contains($lowerW, 'ntb') || str_contains($lowerW, 'ntt')) {
                $regionAliases[] = 'jatim & balnus';
                $regionAliases[] = 'bali / nusra';
                $regionAliases[] = 'jawa timur';
                $regionAliases[] = 'jawa';
            }
            if (str_contains($lowerW, 'jawa') && empty($regionAliases)) {
                $regionAliases[] = 'jawa';
                $regionAliases[] = 'jabar & jateng';
                $regionAliases[] = 'jatim & balnus';
            }
            if (str_contains($lowerW, 'sumat') || str_contains($lowerW, 'medan') || str_contains($lowerW, 'palembang') || str_contains($lowerW, 'pekanbaru') || str_contains($lowerW, 'padang') || str_contains($lowerW, 'lampung') || str_contains($lowerW, 'batam') || str_contains($lowerW, 'aceh')) {
                $regionAliases[] = 'sumatera';
                $regionAliases[] = 'sumatra';
            }
            if (str_contains($lowerW, 'kalimantan') || str_contains($lowerW, 'balikpapan') || str_contains($lowerW, 'samarinda') || str_contains($lowerW, 'banjarmasin') || str_contains($lowerW, 'pontianak') || str_contains($lowerW, 'ikn')) {
                $regionAliases[] = 'kalimantan';
                $regionAliases[] = 'kalimantan / kti';
            }
            if (str_contains($lowerW, 'sulawesi') || str_contains($lowerW, 'makassar') || str_contains($lowerW, 'manado') || str_contains($lowerW, 'palu') || str_contains($lowerW, 'kendari')) {
                $regionAliases[] = 'sulawesi';
                $regionAliases[] = 'kalimantan / kti';
            }
            if (str_contains($lowerW, 'palmal') || str_contains($lowerW, 'papua') || str_contains($lowerW, 'maluku') || str_contains($lowerW, 'jayapura') || str_contains($lowerW, 'ambon') || str_contains($lowerW, 'sorong') || str_contains($lowerW, 'ternate')) {
                $regionAliases[] = 'palmal';
                $regionAliases[] = 'palmal (papua & maluku)';
                $regionAliases[] = 'kalimantan / kti';
            }
            if (str_contains($lowerW, 'kti')) {
                $regionAliases[] = 'kalimantan / kti';
                $regionAliases[] = 'kalimantan';
                $regionAliases[] = 'sulawesi';
                $regionAliases[] = 'palmal';
            }

            $record = (clone $query)->where(function ($q) use ($cleanWilayah, $regionAliases) {
                $q->whereRaw('LOWER(TRIM(wilayah)) = ?', [strtolower($cleanWilayah)])
                  ->orWhereRaw('LOWER(TRIM(wilayah)) LIKE ?', ['%' . strtolower($cleanWilayah) . '%']);
                foreach ($regionAliases as $alias) {
                    $q->orWhereRaw('LOWER(TRIM(wilayah)) LIKE ?', ['%' . $alias . '%']);
                }
            })
            // Prioritas: kecocokan nama paket paling persis, lalu kecocokan wilayah paling persis
            ->orderByRaw("CASE WHEN LOWER(TRIM(paketisasi)) = ? THEN 1 WHEN LOWER(TRIM(paketisasi)) LIKE ? THEN 2 ELSE 3 END", [$lowerPaket, $lowerPaket . ' %'])
            ->orderByRaw("CASE WHEN LOWER(TRIM(wilayah)) = ? THEN 1 ELSE 2 END", [$lowerW])
            ->first();

            if ($record) {
                return $record;
            }

            // Jika user memilih wilayah spesifik dan tidak ditemukan pada paket/bandwidth ini, jangan fallback ke wilayah lain!
            return null;
        }

        // Fallback jika wilayah memang tidak diisi sama sekali
        return $query->orderByRaw("CASE WHEN LOWER(TRIM(paketisasi)) = ? THEN 1 WHEN LOWER(TRIM(paketisasi)) LIKE ? THEN 2 ELSE 3 END", [$lowerPaket, $lowerPaket . ' %'])->first();
    }
}
