{{--
    Komponen: x-produk-selector
    Props:
      - prefix     : string — unik per form (mis: 'mkt', 'admin', 'show_mkt', 'show_revisi')
      - kategori   : string|null — nilai awal dari server (disimpan via hidden input untuk backward-compatibility)
      - paketisasi : string|null — nilai awal dari server
      - wilayah    : string|null — nilai awal dari server
      - bandwidth  : string|null — nilai awal dari server
      - lokasi     : string|null — keterangan lokasi proyek (opsional)
--}}
@props([
    'prefix'     => 'produk',
    'kategori'   => 'connectivity',
    'paketisasi' => '',
    'wilayah'    => '',
    'bandwidth'  => '',
    'lokasi'     => null,
])

<div class="space-y-4">
    {{-- Hidden Kategori Produk agar controller & database lama tetap valid tanpa error --}}
    <input type="hidden" id="{{ $prefix }}_kategori" name="kategori" value="{{ old('kategori', $kategori ?: 'connectivity') }}">

    {{-- Baris: Paket Produk + Wilayah + Bandwidth (3 kolom responsif side-by-side) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- 1. Paket Produk (hanya produk dari katalog Price List RSO) --}}
        <div>
            <label for="{{ $prefix }}_paketisasi" class="block text-xs font-semibold text-slate-700 mb-1.5">
                Paket Produk <span class="text-red-500">*</span>
            </label>
            <select id="{{ $prefix }}_paketisasi" name="paketisasi" required
                    class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900
                           focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white transition">
                <option value="">-- Pilih Paket Produk --</option>
            </select>
        </div>

        {{-- 2. Wilayah / Zona Tarif Telkom (Dinamis sesuai Paket dari Katalog Price List) --}}
        <div>
            <label for="{{ $prefix }}_wilayah" class="block text-xs font-semibold text-slate-700 mb-1.5">
                Wilayah / Zona Tarif <span class="text-red-500">*</span>
            </label>
            <select id="{{ $prefix }}_wilayah" name="wilayah" x-model="wilayah" required
                    class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900
                           focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white transition">
                <option value="">-- Pilih Paket Terlebih Dahulu --</option>
            </select>
            @if($lokasi)
                <p class="text-[11px] text-slate-400 mt-1 truncate" title="Lokasi ED: {{ $lokasi }}">Lokasi Proyek: {{ $lokasi }}</p>
            @endif
        </div>

        {{-- 3. Bandwidth (hanya bandwidth yang terdaftar di katalog Excel matriks RSO) --}}
        <div id="{{ $prefix }}_bw_wrap">
            <label id="{{ $prefix }}_bw_label" class="block text-xs font-semibold text-slate-700 mb-1.5">
                Bandwidth / Kapasitas <span class="text-red-500">*</span>
            </label>

            {{-- Dropdown pilihan bandwidth sesuai matriks RSO --}}
            <select id="{{ $prefix }}_bw_select"
                    class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900
                           focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white transition">
                <option value="">-- Pilih Paket Terlebih Dahulu --</option>
            </select>

            {{-- Input bebas cadangan jika ada item non-matriks / custom --}}
            <input type="text" id="{{ $prefix }}_bw_custom"
                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900
                          focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white transition hidden"
                   placeholder="Isi kapasitas / keterangan">

            {{-- Hidden input yang dikirim ke server --}}
            <input type="hidden" id="{{ $prefix }}_bw_hidden" name="bandwidth" value="{{ old('bandwidth', $bandwidth) }}">
        </div>
    </div>
</div>

@php
    try {
        $priceLists = \App\Models\PriceList::select('kategori', 'paketisasi', 'bandwidth', 'wilayah', 'tarif_normal_mrc')
            ->whereNotNull('paketisasi')
            ->where('paketisasi', '!=', '')
            ->get();

        $allWilayah = $priceLists->pluck('wilayah')->filter()->unique()->values()->toArray();
        sort($allWilayah);

        $catalogByPaket = [];
        foreach ($priceLists->groupBy('paketisasi') as $paketName => $items) {
            $kat = $items->first()->kategori ?: 'connectivity';
            $bws = $items->pluck('bandwidth')->filter()->unique()->values()->toArray();
            $wilayahs = $items->pluck('wilayah')->filter()->unique()->values()->toArray();
            sort($wilayahs);

            // Pengurutan numerik alami (128 Kbps < 1 Mbps < 2 Mbps < 10 Mbps < 100 Mbps < 1000 Mbps < 10 Gbps)
            usort($bws, function ($a, $b) {
                preg_match('/([\d\.]+)\s*(Mbps|Gbps|Kbps)?/i', $a, $mA);
                preg_match('/([\d\.]+)\s*(Mbps|Gbps|Kbps)?/i', $b, $mB);
                $vA = isset($mA[1]) ? (float)$mA[1] : 0;
                if (strtolower($mA[2] ?? '') === 'gbps') $vA *= 1000;
                if (strtolower($mA[2] ?? '') === 'kbps') $vA /= 1000;

                $vB = isset($mB[1]) ? (float)$mB[1] : 0;
                if (strtolower($mB[2] ?? '') === 'gbps') $vB *= 1000;
                if (strtolower($mB[2] ?? '') === 'kbps') $vB /= 1000;

                return $vA <=> $vB;
            });

            // Matriks tarif cepat: [bandwidth][wilayah] => tarif_normal_mrc
            $priceMatrix = [];
            foreach ($items as $item) {
                if ($item->bandwidth && $item->wilayah) {
                    $priceMatrix[$item->bandwidth][$item->wilayah] = (float) $item->tarif_normal_mrc;
                }
            }

            $catalogByPaket[$paketName] = [
                'kategori' => $kat,
                'bw'       => array_values($bws),
                'wilayah'  => array_values($wilayahs),
                'prices'   => $priceMatrix,
            ];
        }
    } catch (\Throwable $e) {
        $catalogByPaket = [];
        $allWilayah = [];
    }
@endphp

{{-- Init script untuk instance form ini --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof initProdukSelector === 'function') {
            initProdukSelector('{{ $prefix }}', {
                kategori:   '{{ old('kategori', $kategori ?: 'connectivity') }}',
                paketisasi: '{{ old('paketisasi', $paketisasi) }}',
                wilayah:    '{{ old('wilayah', $wilayah) }}',
                bandwidth:  '{{ old('bandwidth', $bandwidth) }}',
            }, {!! json_encode($catalogByPaket) !!}, {!! json_encode($allWilayah) !!});
        }
    });
</script>
