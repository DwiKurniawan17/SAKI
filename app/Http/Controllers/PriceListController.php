<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PriceListController extends Controller
{
    /**
     * Halaman Admin: Kelola Katalog Price List & Upload Excel.
     */
    public function index(Request $request)
    {
        $allPairs = PriceList::select('paketisasi', 'wilayah')
            ->whereNotNull('paketisasi')
            ->whereNotNull('wilayah')
            ->distinct()
            ->get();

        $uniquePaket = $allPairs->pluck('paketisasi')->unique()->sort()->values();

        // Buat pemetaan: [nama_paket => [wilayah1, wilayah2, ...]]
        $paketWilayahMap = [];
        foreach ($allPairs->groupBy('paketisasi') as $pkt => $pairs) {
            $paketWilayahMap[$pkt] = $pairs->pluck('wilayah')->unique()->sort()->values()->all();
        }

        // Tentukan opsi wilayah yang tersedia berdasarkan paket yang dipilih
        $selectedPaket = $request->input('paket');
        if (!empty($selectedPaket) && isset($paketWilayahMap[$selectedPaket])) {
            $uniqueWilayah = collect($paketWilayahMap[$selectedPaket]);
            // Jika wilayah yang sedang dipilih tidak ada pada paket ini, batalkan filter wilayah
            if ($request->filled('wilayah') && !$uniqueWilayah->contains($request->wilayah)) {
                $request->merge(['wilayah' => null]);
            }
        } else {
            $uniqueWilayah = $allPairs->pluck('wilayah')->unique()->sort()->values();
        }

        $query = PriceList::query();

        if ($request->filled('paket')) {
            $query->where('paketisasi', $request->paket);
        }

        if ($request->filled('wilayah')) {
            $query->where('wilayah', $request->wilayah);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('paketisasi', 'like', "%{$search}%")
                  ->orWhere('bandwidth', 'like', "%{$search}%")
                  ->orWhere('wilayah', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'terbaru');
        if ($sort === 'abjad') {
            $query->orderBy('paketisasi')->orderBy('bandwidth');
        } elseif ($sort === 'perubahan_tarif') {
            $query->whereNotNull('tarif_lama_mrc')->orderByDesc('updated_at');
        } else {
            // Default 'terbaru': data yang baru di-upload/di-update langsung berada di urutan paling atas tanpa harus reset!
            $query->orderByDesc('updated_at')->orderBy('paketisasi')->orderBy('bandwidth');
        }

        $priceLists = $query->paginate(30)->withQueryString();
        $totalItems = PriceList::count();
        $rdmMaster = \App\Models\ParameterRdm::getActive();

        return view('admin.price-list', compact('priceLists', 'totalItems', 'uniquePaket', 'uniqueWilayah', 'rdmMaster', 'paketWilayahMap'));
    }

    /**
     * Import Excel Price List Cerdas & Fleksibel:
     * - Mendukung Format Multi-Level Header (Header 2 Baris: Wilayah di baris 1, Global/Domestik di baris 2)
     * - Mendukung Format Matriks Standar (Header 1 Baris: Bandwidth x Wilayah)
     * - Mendukung Format Flat (Kolom Paket, Bandwidth, Wilayah, Tarif)
     * - Proteksi keamanan: Validasi angka bandwidth & tarif, pembersihan merged-cells, dan bypass otomatis baris catatan/kaki.
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'paket_default' => 'nullable|string|max:100',
        ]);

        $file = $request->file('excel_file');
        $paketDefault = trim((string)$request->input('paket_default', ''));

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheets = $spreadsheet->getAllSheets();
        } catch (\Throwable $e) {
            Log::error('PriceList Excel load error', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Gagal membaca file Excel. Pastikan file dalam format .xlsx atau .xls yang valid.');
        }

        $importedCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;
        $skippedCount = 0;
        $affectedIds = [];
        $affectedPakets = [];
        $priceChangedIds = [];
        $newlyCreatedIds = [];

        // Ambil daftar paket yang sudah ada di database untuk pencocokan cerdas
        $existingPakets = PriceList::distinct()->pluck('paketisasi')->filter()->values()->all();

        foreach ($sheets as $sheet) {
            $sheetTitle = trim($sheet->getTitle());
            $rows = $sheet->toArray(null, true, false, true);
            if (empty($rows)) {
                continue;
            }

            // Peta merged cells untuk mendistribusikan teks header yang di-merge
            $mergedMap = $this->buildMergedMap($sheet);

            // Tentukan nama paket dasar secara cerdas (toleran huruf besar/kecil, typo, dan nama sheet bawaan)
            $basePaketName = $this->normalizePaketName($sheetTitle, $paketDefault, $existingPakets);

            // Deteksi seluruh blok tabel di dalam sheet (mendukung multi-tabel seperti BEST EFFORT & CRITICAL dalam 1 sheet)
            $blocks = $this->detectTableBlocks($rows, $mergedMap, $basePaketName, $existingPakets);

            foreach ($blocks as $block) {
                $bwCol = $block['bw_col'];
                $unitCol = $block['unit_col'] ?? null;
                $columnMappings = $block['column_mappings'] ?? [];
                $dataStartRowIdx = $block['data_start_row'];
                $dataEndRowIdx = $block['data_end_row'];
                $blockPaket = $block['paket_name'];
                $isFlat = $block['is_flat'] ?? false;
                $flatCols = $block['flat_cols'] ?? [];

                if ($isFlat) {
                    // Mode Flat Table (Kolom: Paket, Bandwidth, Wilayah, Tarif)
                    for ($idx = $dataStartRowIdx; $idx <= $dataEndRowIdx; $idx++) {
                        if (!isset($rows[$idx])) continue;
                        $row = $rows[$idx];
                        $rawBw = trim((string)($row[$bwCol] ?? ''));
                        if (!$this->isValidBandwidthValue($rawBw) || $this->isJunkRow($row)) {
                            $skippedCount++;
                            continue;
                        }

                        $rawUnit = $unitCol ? trim((string)($row[$unitCol] ?? '')) : null;
                        $cleanBw = $this->normalizeBandwidth($rawBw, $rawUnit);
                        $rawRowPaket = isset($flatCols['paket']) && !empty($row[$flatCols['paket']]) ? trim((string)$row[$flatCols['paket']]) : $blockPaket;
                        $rowPaket = $this->matchExistingPaket($rawRowPaket, $existingPakets);
                        $rowWilayah = isset($flatCols['wilayah']) && !empty($row[$flatCols['wilayah']]) ? $this->normalizeWilayah((string)$row[$flatCols['wilayah']]) : 'Semua Wilayah';
                        $price = $this->parseNumericPrice($row[$flatCols['tarif']] ?? null);

                        if ($price > 0 && $price < 100000000000) {
                            $existingRecord = PriceList::whereRaw('LOWER(TRIM(paketisasi)) = ?', [strtolower(trim($rowPaket))])
                                ->whereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower(trim($cleanBw))])
                                ->whereRaw('LOWER(TRIM(wilayah)) = ?', [strtolower(trim($rowWilayah))])
                                ->first();

                            if ($existingRecord) {
                                $oldPrice = (float) $existingRecord->tarif_normal_mrc;
                                $isPriceChanged = abs($oldPrice - $price) > 0.01;

                                $updateData = [
                                    'kategori' => 'connectivity',
                                    'tarif_normal_mrc' => $price,
                                ];

                                if ($isPriceChanged) {
                                    $updateData['tarif_lama_mrc'] = $oldPrice;
                                    $priceChangedIds[] = $existingRecord->id;
                                    $updatedCount++;
                                } else {
                                    $unchangedCount++;
                                }

                                $existingRecord->update($updateData);
                                $affectedIds[] = $existingRecord->id;
                                $affectedPakets[] = $existingRecord->paketisasi;
                            } else {
                                $newRecord = PriceList::create([
                                    'paketisasi' => $rowPaket,
                                    'bandwidth' => $cleanBw,
                                    'wilayah' => $rowWilayah,
                                    'kategori' => 'connectivity',
                                    'tarif_normal_mrc' => $price,
                                    'tarif_lama_mrc' => null,
                                ]);
                                $newlyCreatedIds[] = $newRecord->id;
                                $affectedIds[] = $newRecord->id;
                                $affectedPakets[] = $newRecord->paketisasi;
                                $importedCount++;
                            }
                        }
                    }
                } elseif (!empty($columnMappings)) {
                    // Mode Matriks (Multi-Level atau Standar, didukung satuan di kolom terpisah)
                    for ($idx = $dataStartRowIdx; $idx <= $dataEndRowIdx; $idx++) {
                        if (!isset($rows[$idx])) continue;
                        $row = $rows[$idx];
                        $rawBw = trim((string)($row[$bwCol] ?? ''));

                        // Filter baris data: abaikan baris kosong, header blok berikutnya, catatan kaki
                        if (!$this->isValidBandwidthValue($rawBw) || $this->isJunkRow($row)) {
                            $skippedCount++;
                            continue;
                        }

                        $rawUnit = $unitCol ? trim((string)($row[$unitCol] ?? '')) : null;
                        $cleanBw = $this->normalizeBandwidth($rawBw, $rawUnit);

                        foreach ($columnMappings as $colLetter => $meta) {
                            $rawPrice = $row[$colLetter] ?? null;
                            $price = $this->parseNumericPrice($rawPrice);

                            if ($price <= 0 || $price > 100000000000) {
                                continue;
                            }

                            $wilayah = $meta['wilayah'];
                            $sub = $meta['sub'] ?? null;

                            // Integrasi Nama Paket Cerdas (contoh: "Astinet Best Effort", "Astinet Critical")
                            $targetPaket = $blockPaket;
                            if ($sub && !str_contains(strtolower($targetPaket), strtolower($sub))) {
                                $targetPaket .= ' ' . $sub;
                            }
                            $targetPaket = $this->matchExistingPaket($targetPaket, $existingPakets);

                            // Cari data yang sudah ada secara case-insensitive agar tidak membuat baris duplikat
                            $existingRecord = PriceList::whereRaw('LOWER(TRIM(paketisasi)) = ?', [strtolower(trim($targetPaket))])
                                ->whereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower(trim($cleanBw))])
                                ->whereRaw('LOWER(TRIM(wilayah)) = ?', [strtolower(trim($wilayah))])
                                ->first();

                            if ($existingRecord) {
                                $oldPrice = (float) $existingRecord->tarif_normal_mrc;
                                $isPriceChanged = abs($oldPrice - $price) > 0.01;

                                $updateData = [
                                    'kategori'         => 'connectivity',
                                    'tarif_normal_mrc' => $price,
                                    'keterangan'       => $sub ? "Tipe: {$sub}" : $existingRecord->keterangan,
                                ];

                                if ($isPriceChanged) {
                                    $updateData['tarif_lama_mrc'] = $oldPrice;
                                    $priceChangedIds[] = $existingRecord->id;
                                    $updatedCount++;
                                } else {
                                    $unchangedCount++;
                                }

                                $existingRecord->update($updateData);
                                $affectedIds[] = $existingRecord->id;
                                $affectedPakets[] = $existingRecord->paketisasi;
                            } else {
                                $newRecord = PriceList::create([
                                    'paketisasi'       => $targetPaket,
                                    'bandwidth'        => $cleanBw,
                                    'wilayah'          => $wilayah,
                                    'kategori'         => 'connectivity',
                                    'tarif_normal_mrc' => $price,
                                    'tarif_lama_mrc'   => null,
                                    'keterangan'       => $sub ? "Tipe: {$sub}" : null,
                                ]);
                                $newlyCreatedIds[] = $newRecord->id;
                                $affectedIds[] = $newRecord->id;
                                $affectedPakets[] = $newRecord->paketisasi;
                                $importedCount++;
                            }
                        }
                    }
                }
            }
        }

        $totalProcessed = $importedCount + $updatedCount + $unchangedCount;
        if ($totalProcessed === 0) {
            return redirect()->route('admin.price-list')->with('error', 'Tidak ada data tarif yang berhasil diimpor. Pastikan header tabel memiliki kolom "Bandwidth" dan kolom wilayah (Jabodetabek, Jawa, Sumatera, Kalimantan / KTI).');
        }

        activity('admin')
            ->causedBy($request->user())
            ->log("Admin mengimpor Price List: {$importedCount} data baru, {$updatedCount} tarif diperbarui, {$unchangedCount} tarif tetap, {$skippedCount} baris dilewati");

        $statusMsg = "Berhasil memproses {$totalProcessed} tarif: {$importedCount} baru ditambahkan";
        if ($updatedCount > 0) {
            $statusMsg .= ", {$updatedCount} tarif diperbarui";
        }
        if ($unchangedCount > 0) {
            $statusMsg .= ", {$unchangedCount} tarif tidak berubah";
        }
        if ($skippedCount > 0) {
            $statusMsg .= " ({$skippedCount} baris non-data dilewati).";
        } else {
            $statusMsg .= ".";
        }

        // Simpan daftar ID yang baru saja diproses ke flash session agar bisa di-highlight di tabel
        session()->flash('just_imported_ids', $affectedIds);
        session()->flash('price_changed_ids', $priceChangedIds);
        session()->flash('newly_created_ids', $newlyCreatedIds);

        $uniqueAffectedPakets = array_values(array_unique($affectedPakets));
        $redirectParams = [];

        // Jika upload spesifik untuk 1 paket (misal Astinet), langsung filter ke paket tersebut
        if (count($uniqueAffectedPakets) === 1) {
            $redirectParams['paket'] = $uniqueAffectedPakets[0];
        } else {
            $redirectParams['sort'] = 'terbaru';
        }

        return redirect()->route('admin.price-list', $redirectParams)->with('status', $statusMsg);
    }

    /**
     * Update tarif satu item secara langsung.
     */
    public function update(Request $request, PriceList $priceList)
    {
        $validated = $request->validate([
            'tarif_normal_mrc' => 'required|numeric|min:0',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        $newPrice = (float) $validated['tarif_normal_mrc'];
        $oldPrice = (float) $priceList->tarif_normal_mrc;

        $updateData = [
            'keterangan' => $validated['keterangan'] ?? $priceList->keterangan,
        ];

        if (abs($oldPrice - $newPrice) > 0.01) {
            $updateData['tarif_lama_mrc'] = $oldPrice;
            $updateData['tarif_normal_mrc'] = $newPrice;
            session()->flash('price_changed_ids', [$priceList->id]);
        }

        $priceList->update($updateData);

        activity('admin')
            ->causedBy($request->user())
            ->log("Admin memperbarui tarif {$priceList->paketisasi} ({$priceList->bandwidth}, {$priceList->wilayah}) dari Rp " . number_format($oldPrice, 0, ',', '.') . " ke Rp " . number_format($newPrice, 0, ',', '.'));

        $timeFormatted = $priceList->updated_at ? $priceList->updated_at->translatedFormat('d M Y, H:i') . ' WIB' : now()->translatedFormat('d M Y, H:i') . ' WIB';
        return back()->with('status', "Tarif {$priceList->paketisasi} ({$priceList->bandwidth}, {$priceList->wilayah}) berhasil diperbarui pada {$timeFormatted}.");
    }

    /**
     * Hapus satu item price list.
     */
    public function destroy(PriceList $priceList)
    {
        $priceList->delete();
        return back()->with('status', 'Item tarif berhasil dihapus.');
    }

    /**
     * Hapus data tarif tertentu berdasarkan paket atau filter yang sedang aktif.
     */
    public function deleteFiltered(Request $request)
    {
        $query = PriceList::query();
        $filtersApplied = [];

        if ($request->filled('paket')) {
            $query->where('paketisasi', $request->paket);
            $filtersApplied[] = "Paket: {$request->paket}";
        }

        if ($request->filled('wilayah')) {
            $query->where('wilayah', $request->wilayah);
            $filtersApplied[] = "Wilayah: {$request->wilayah}";
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('paketisasi', 'like', "%{$search}%")
                  ->orWhere('bandwidth', 'like', "%{$search}%")
                  ->orWhere('wilayah', 'like', "%{$search}%");
            });
            $filtersApplied[] = "Pencarian: '{$search}'";
        }

        if (empty($filtersApplied)) {
            return back()->with('error', 'Tidak ada filter yang dipilih untuk dihapus. Pilih paket atau filter terlebih dahulu.');
        }

        $count = $query->count();
        if ($count === 0) {
            return back()->with('error', 'Tidak ada data tarif yang sesuai dengan filter tersebut.');
        }

        // Eksekusi penghapusan
        $deleted = $query->delete();

        $filterStr = implode(', ', $filtersApplied);
        activity('admin')
            ->causedBy($request->user())
            ->log("Admin menghapus {$deleted} data tarif dengan filter ({$filterStr})");

        return redirect()->route('admin.price-list')->with('status', "Berhasil menghapus {$deleted} data tarif ({$filterStr}).");
    }

    /**
     * Hapus semua item price list (Reset).
     */
    public function clearAll(Request $request)
    {
        PriceList::truncate();
        activity('admin')
            ->causedBy($request->user())
            ->log('Admin mengosongkan katalog Price List');

        return back()->with('status', 'Seluruh data Price List berhasil dikosongkan.');
    }

    /**
     * API lookup untuk JavaScript di form Marketing / Edit.
     */
    public function apiLookup(Request $request)
    {
        $paket = $request->query('paket');
        $bw = $request->query('bandwidth');
        $wilayah = $request->query('wilayah');
        $tipe = $request->query('tipe');

        // Harus memilih wilayah terlebih dahulu sebelum tarif katalog dapat dimunculkan
        if (empty($wilayah)) {
            return response()->json(['found' => false]);
        }

        $record = PriceList::findTarif($paket, $bw, $wilayah, $tipe);

        if ($record) {
            return response()->json([
                'found'            => true,
                'paketisasi'       => $record->paketisasi,
                'bandwidth'        => $record->bandwidth,
                'wilayah'          => $record->wilayah,
                'tarif_normal_mrc' => (float) $record->tarif_normal_mrc,
                'tarif_normal_otc' => (float) $record->tarif_normal_otc,
                'updated_at'       => $record->updated_at ? $record->updated_at->translatedFormat('d M Y, H:i') . ' WIB' : null,
                'updated_at_human' => $record->updated_at ? $record->updated_at->diffForHumans() : null,
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * API untuk mengambil seluruh daftar harga (untuk offline / local cache di frontend).
     */
    public function apiAll()
    {
        $data = PriceList::select('paketisasi', 'bandwidth', 'wilayah', 'tarif_normal_mrc')->get();
        return response()->json($data);
    }

    /**
     * Peta sel yang di-merge (misal B1:C1 "JaBoDeTaBek") agar kolom C1 mewarisi nilai B1.
     */
    private function buildMergedMap($sheet): array
    {
        $map = [];
        try {
            foreach ($sheet->getMergeCells() as $range) {
                if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $range, $m)) {
                    $startCol = $m[1];
                    $startRow = (int)$m[2];
                    $endCol = $m[3];
                    $endRow = (int)$m[4];

                    $masterVal = trim((string)$sheet->getCell($startCol . $startRow)->getValue());
                    if ($masterVal !== '') {
                        $startColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCol);
                        $endColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endCol);

                        for ($r = $startRow; $r <= $endRow; $r++) {
                            for ($c = $startColIdx; $c <= $endColIdx; $c++) {
                                $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                                $map[$r][$colStr] = $masterVal;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Merge cells parse error', ['msg' => $e->getMessage()]);
        }
        return $map;
    }

    /**
     * Deteksi seluruh blok tabel di dalam sheet (mendukung multi-tabel seperti BEST EFFORT & CRITICAL dalam 1 sheet).
     */
    private function detectTableBlocks(array $rows, array $mergedMap, string $basePaketName, array $existingPakets): array
    {
        $blocks = [];
        $bwRows = [];

        // 1. Temukan seluruh baris yang mengandung header bandwidth
        foreach ($rows as $idx => $row) {
            foreach ($row as $colLetter => $cell) {
                $cellVal = strtolower(trim((string)$cell));
                if ($this->isBandwidthHeader($cellVal)) {
                    $bwRows[] = [
                        'row' => $idx,
                        'col' => $colLetter,
                    ];
                    break; // Hanya ambil kolom bandwidth pertama di baris tersebut
                }
            }
        }

        if (empty($bwRows)) {
            return [];
        }

        // 2. Kelompokkan baris header (jika baris $idx dan $idx+1 keduanya adalah header multi-level)
        $headerGroups = [];
        $countBw = count($bwRows);
        for ($i = 0; $i < $countBw; $i++) {
            $curr = $bwRows[$i];
            $next = ($i + 1 < $countBw) ? $bwRows[$i + 1] : null;

            if ($next && $next['row'] === $curr['row'] + 1) {
                // Multi-level header 2 baris (misal Baris 2 & Baris 3 keduanya mengandung 'Bandwidth')
                $headerGroups[] = [
                    'hRow1' => $curr['row'],
                    'hRow2' => $next['row'],
                    'bwCol' => $curr['col'],
                ];
                $i++; // Lewati next karena sudah menjadi sub-header
            } else {
                // Cek apakah baris berikutnya adalah sub-header (misal baris unit/sub-kategori yang tidak berlabel 'Bandwidth')
                $possibleSubRowIdx = $curr['row'] + 1;
                $isSubHeader = false;
                if (isset($rows[$possibleSubRowIdx])) {
                    $subRow = $rows[$possibleSubRowIdx];
                    $subBwCell = trim((string)($subRow[$curr['col']] ?? ''));
                    // Jika kolom bandwidth di baris kedua kosong/unit/label atau bukan angka valid
                    if (!$this->isValidBandwidthValue($subBwCell) && !$this->isJunkRow($subRow)) {
                        $isSubHeader = true;
                    }
                }

                $headerGroups[] = [
                    'hRow1' => $curr['row'],
                    'hRow2' => $isSubHeader ? $curr['row'] + 1 : null,
                    'bwCol' => $curr['col'],
                ];
            }
        }

        // 3. Bangun konfigurasi per blok tabel
        $maxRow = max(array_keys($rows));
        $totalGroups = count($headerGroups);

        for ($k = 0; $k < $totalGroups; $k++) {
            $grp = $headerGroups[$k];
            $hRow1Idx = $grp['hRow1'];
            $hRow2Idx = $grp['hRow2'];
            $bwCol = $grp['bwCol'];

            // Tentukan batas akhir data baris untuk blok ini
            $nextHRow1 = ($k + 1 < $totalGroups) ? $headerGroups[$k + 1]['hRow1'] : ($maxRow + 1);
            $dataStartRowIdx = $hRow2Idx ? ($hRow2Idx + 1) : ($hRow1Idx + 1);
            $dataEndRowIdx = $nextHRow1 - 1;

            // Deteksi nama seksi / sub-kategori (misal "BEST EFFORT" atau "CRITICAL")
            $sectionTitle = $this->extractSectionTitle($rows, $mergedMap, $hRow1Idx, $bwCol);

            // Deteksi kolom satuan bandwidth (misal kolom 'B' di sebelah kolom 'A')
            $unitCol = $this->detectUnitColumn($rows, $bwCol, $hRow1Idx, $hRow2Idx, $dataStartRowIdx, $dataEndRowIdx);

            // Cek apakah tabel berbentuk FLAT (memiliki kolom 'paket' dan 'tarif')
            $row1 = $rows[$hRow1Idx];
            $flatCols = [];
            foreach ($row1 as $colLetter => $cellVal) {
                $lower = strtolower(trim((string)$cellVal));
                if (str_contains($lower, 'paket') || str_contains($lower, 'produk') || str_contains($lower, 'layanan')) $flatCols['paket'] = $colLetter;
                if (str_contains($lower, 'wilayah') || str_contains($lower, 'regional') || str_contains($lower, 'zona')) $flatCols['wilayah'] = $colLetter;
                if (str_contains($lower, 'tarif') || str_contains($lower, 'harga') || str_contains($lower, 'mrc')) $flatCols['tarif'] = $colLetter;
            }

            $isFlat = isset($flatCols['tarif']);
            $columnMappings = [];

            if (!$isFlat) {
                // Mode Matriks (Petakan seluruh kolom wilayah setelah bwCol & unitCol)
                $colLetters = array_keys($rows[$hRow1Idx] ?? []);
                $bwColNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($bwCol);
                $unitColNum = $unitCol ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($unitCol) : null;

                // Deteksi apakah hRow2 berisi sub-tipe layanan (Global, Domestik, 1:1, dsb)
                $h2HasSubTypes = false;
                if ($hRow2Idx && isset($rows[$hRow2Idx])) {
                    foreach ($rows[$hRow2Idx] as $cellVal) {
                        if ($this->isSubServiceType((string)$cellVal)) {
                            $h2HasSubTypes = true;
                            break;
                        }
                    }
                }

                $lastParent = '';
                foreach ($colLetters as $colLetter) {
                    $cNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colLetter);
                    if ($cNum === $bwColNum || ($unitColNum && $cNum === $unitColNum)) {
                        continue;
                    }

                    $parent = trim((string)($mergedMap[$hRow1Idx][$colLetter] ?? $rows[$hRow1Idx][$colLetter] ?? ''));
                    $sub = $hRow2Idx ? trim((string)($rows[$hRow2Idx][$colLetter] ?? '')) : '';

                    if ($h2HasSubTypes) {
                        // KASUS 1: Baris 1 = Wilayah (JaBoDeTaBek, Jawa, Sumatera) & Baris 2 = Sub-Tipe (Global, Domestik)
                        $regionText = !empty($parent) ? $parent : $lastParent;
                        if (!empty($parent)) {
                            $lastParent = $parent;
                        }
                        $subType = !empty($sub) ? $this->normalizeTipeLayanan($sub) : null;
                    } elseif (!empty($sectionTitle) && (strtolower($parent) === strtolower($sectionTitle) || empty($parent))) {
                        // KASUS 2: Baris 1 = Section Title (BEST EFFORT / CRITICAL) & Baris 2 = Wilayah (JABODETABEK, JAWA)
                        $regionText = !empty($sub) ? $sub : $lastParent;
                        if (!empty($sub)) {
                            $lastParent = $sub;
                        }
                        $subType = null;
                    } else {
                        // KASUS UMUM: Evaluasi berdasarkan pengenalan nama wilayah
                        if (!empty($sub) && $this->isKnownWilayah($sub)) {
                            $regionText = $sub;
                            $subType = (!empty($parent) && !$this->isKnownWilayah($parent)) ? $this->normalizeTipeLayanan($parent) : null;
                        } else {
                            $regionText = !empty($parent) ? $parent : (!empty($sub) ? $sub : $lastParent);
                            $subType = (!empty($sub) && strtolower($sub) !== strtolower($regionText)) ? $this->normalizeTipeLayanan($sub) : null;
                        }
                        if (!empty($regionText)) {
                            $lastParent = $regionText;
                        }
                    }

                    if (empty($regionText) || $this->isBandwidthHeader($regionText) || $this->isUnitHeaderOrCell($regionText)) {
                        continue;
                    }

                    $normWilayah = $this->normalizeWilayah($regionText);
                    $columnMappings[$colLetter] = [
                        'wilayah' => $normWilayah,
                        'sub'     => $subType,
                    ];
                }
            }

            // Tentukan nama paket untuk blok ini
            $blockPaket = $this->buildPaketNameWithSub($basePaketName, $sectionTitle, $existingPakets);

            $blocks[] = [
                'h_row1'          => $hRow1Idx,
                'h_row2'          => $hRow2Idx,
                'bw_col'          => $bwCol,
                'unit_col'        => $unitCol,
                'section_title'   => $sectionTitle,
                'paket_name'      => $blockPaket,
                'is_flat'         => $isFlat,
                'flat_cols'       => $flatCols,
                'column_mappings' => $columnMappings,
                'data_start_row'  => $dataStartRowIdx,
                'data_end_row'    => $dataEndRowIdx,
            ];
        }

        return $blocks;
    }

    /**
     * Ekstraksi judul seksi (misal "BEST EFFORT", "CRITICAL", dsb) untuk suatu tabel.
     * Tidak akan pernah menganggap nama wilayah (Jabodetabek, Jawa, dsb) atau sub-tipe (Global, Domestik) sebagai seksi.
     */
    private function extractSectionTitle(array $rows, array $mergedMap, int $hRow1Idx, string $bwCol): ?string
    {
        $keywords = ['best effort', 'critical', 'interactive', 'dedicated', 'broadband', 'gold', 'silver', 'platinum', 'lite', 'pro', 'enterprise', 'corporate', 'bisnis'];

        // 1. Cek merged cells di baris header itu sendiri (misal C2:I2 tertulis BEST EFFORT, CRITICAL, INTERACTIVE)
        if (isset($rows[$hRow1Idx])) {
            // Pertama: cek kecocokan dengan keywords resmi seksi
            foreach ($rows[$hRow1Idx] as $col => $cellVal) {
                if ($col === $bwCol) continue;
                $val = trim((string)($mergedMap[$hRow1Idx][$col] ?? $cellVal));
                if ($this->isKnownWilayah($val) || $this->isSubServiceType($val)) {
                    continue;
                }
                $lower = strtolower($val);
                foreach ($keywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        return ucwords(strtolower($kw));
                    }
                }
            }

            // Kedua: jika ada teks merged yang merentang luas (>= 4 kolom) dan bukan nama wilayah / sub-tipe
            $mergedCounts = [];
            foreach ($rows[$hRow1Idx] as $col => $cellVal) {
                if ($col === $bwCol) continue;
                $val = trim((string)($mergedMap[$hRow1Idx][$col] ?? $cellVal));
                if (!empty($val) && !$this->isBandwidthHeader($val) && !$this->isUnitHeaderOrCell($val) &&
                    !$this->isKnownWilayah($val) && !$this->isSubServiceType($val)) {
                    $mergedCounts[$val] = ($mergedCounts[$val] ?? 0) + 1;
                }
            }

            foreach ($mergedCounts as $title => $cnt) {
                if ($cnt >= 4) {
                    return ucwords(strtolower($title));
                }
            }
        }

        // 2. Cek baris di atas header (1 sampai 4 baris sebelumnya)
        $startCheck = max(1, $hRow1Idx - 4);
        for ($r = $hRow1Idx - 1; $r >= $startCheck; $r--) {
            if (!isset($rows[$r])) continue;
            foreach ($rows[$r] as $cellVal) {
                $val = trim((string)$cellVal);
                if (empty($val)) continue;
                if ($this->isKnownWilayah($val) || $this->isSubServiceType($val)) continue;
                $lower = strtolower($val);
                foreach ($keywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        return ucwords(strtolower($kw));
                    }
                }
                // Jika teks pendek berdiri sendiri di atas tabel (misal "VPN IP", "METRO ETHERNET", "HYBRID CLOUD", dll.)
                if (strlen($val) >= 3 && strlen($val) <= 50 &&
                    !str_starts_with($lower, 'catatan') && !str_starts_with($lower, '*catatan') &&
                    !str_starts_with($lower, 'note') && !str_starts_with($lower, '*note') &&
                    !str_contains($lower, 'ppn') && !str_contains($lower, 'syarat') &&
                    !str_contains($lower, 'disclaimer') && !preg_match('/^\d+$/', $val)) {
                    return ucwords(strtolower($val));
                }
            }
        }

        return null;
    }

    /**
     * Deteksi apakah kolom di sebelah kolom Bandwidth merupakan kolom satuan (Kbps, Mbps, Gbps).
     */
    private function detectUnitColumn(array $rows, string $bwCol, int $hRow1Idx, ?int $hRow2Idx, int $dataStartRowIdx, int $dataEndRowIdx): ?string
    {
        $bwColNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($bwCol);
        $nextCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($bwColNum + 1);

        // 1. Cek header baris kedua atau baris pertama di kolom sebelah
        if ($hRow2Idx && isset($rows[$hRow2Idx][$nextCol])) {
            $h2Val = strtolower(trim((string)$rows[$hRow2Idx][$nextCol]));
            if ($this->isUnitHeaderOrCell($h2Val)) {
                return $nextCol;
            }
        }

        if (isset($rows[$hRow1Idx][$nextCol])) {
            $h1Val = strtolower(trim((string)$rows[$hRow1Idx][$nextCol]));
            if ($this->isUnitHeaderOrCell($h1Val)) {
                return $nextCol;
            }
        }

        // 2. Cek sampel 5 baris pertama data di kolom sebelah
        $checkLimit = min($dataEndRowIdx, $dataStartRowIdx + 5);
        $unitHits = 0;
        for ($r = $dataStartRowIdx; $r <= $checkLimit; $r++) {
            if (!isset($rows[$r][$nextCol])) continue;
            $cellVal = strtolower(trim((string)$rows[$r][$nextCol]));
            if ($this->isUnitHeaderOrCell($cellVal)) {
                $unitHits++;
            }
        }

        if ($unitHits >= 1) {
            return $nextCol;
        }

        return null;
    }

    /**
     * Deteksi apakah suatu cell merupakan keyword satuan bandwidth.
     */
    private function isUnitHeaderOrCell(string $val): bool
    {
        $v = strtolower(trim($val));
        if ($v === '') return false;
        return in_array($v, ['kbps', 'mbps', 'gbps', 'satuan', 'unit', 'speed', 'satuan bw', 'unit bw']);
    }

    /**
     * Gabungkan nama paket dasar dengan sub-kategori seksi (misal "Astinet" + "Best Effort" -> "Astinet Best Effort").
     */
    private function buildPaketNameWithSub(string $basePaket, ?string $sub, array $existingPakets): string
    {
        if (empty($sub)) {
            return $basePaket;
        }

        $cleanBase = trim($basePaket);
        $cleanSub = trim($sub);

        if (str_contains(strtolower($cleanBase), strtolower($cleanSub))) {
            return $this->matchExistingPaket($cleanBase, $existingPakets);
        }

        $combined = $cleanBase . ' ' . $cleanSub;
        return $this->matchExistingPaket($combined, $existingPakets);
    }

    /**
     * Deteksi apakah suatu cell merupakan header kolom bandwidth.
     */
    private function isBandwidthHeader(string $val): bool
    {
        $v = strtolower(trim($val));
        if ($v === '') return false;

        // Header resmi: Bandwidth, BW, Kecepatan, Kapasitas, Speed
        if (str_contains($v, 'bandwidth') || $v === 'bw' || str_contains($v, 'kecepatan') ||
            str_contains($v, 'kapasitas') || $v === 'speed') {
            return true;
        }

        // Variasi seperti "Link (Mbps)", "Speed (Kbps)"
        if ((str_contains($v, 'mbps') || str_contains($v, 'kbps')) &&
            (str_contains($v, 'link') || str_contains($v, 'besaran') || str_contains($v, 'layanan'))) {
            return true;
        }

        return false;
    }

    /**
     * Validasi apakah nilai cell merupakan bandwidth yang logis (anti baris catatan / disclaimer).
     */
    private function isValidBandwidthValue(string $raw): bool
    {
        $trimmed = trim($raw);
        if ($trimmed === '') return false;

        $lower = strtolower($trimmed);
        if (str_contains($lower, 'catatan') || str_contains($lower, 'total') || str_contains($lower, 'note') ||
            str_contains($lower, 'rata') || str_contains($lower, 'disclaimer') || str_contains($lower, 'ppn') ||
            str_contains($lower, 'keterangan') || str_contains($lower, 'syarat') || str_contains($lower, 'ketentuan') ||
            str_contains($lower, 'best effort') || str_contains($lower, 'critical') || str_contains($lower, 'bandwidth')) {
            return false;
        }

        // Harus mengandung angka
        if (!preg_match('/\d+/', $trimmed)) {
            return false;
        }

        $num = (float) preg_replace('/[^0-9.]/', '', $trimmed);
        return $num > 0 && $num <= 100000;
    }

    /**
     * Deteksi apakah baris adalah baris footer / disclaimer non-data.
     */
    private function isJunkRow(array $row): bool
    {
        foreach ($row as $cell) {
            $lower = strtolower(trim((string)$cell));
            if (str_starts_with($lower, 'catatan') || str_starts_with($lower, '*catatan') ||
                str_starts_with($lower, 'note') || str_starts_with($lower, '*note') ||
                str_contains($lower, 'belum termasuk ppn') || str_contains($lower, 'syarat dan ketentuan')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah teks merupakan nama wilayah yang dikenal di Telkom.
     */
    private function isKnownWilayah(string $raw): bool
    {
        $lower = strtolower(trim($raw));
        if ($lower === '') return false;
        $wilayahKeywords = [
            'jabo', 'dki', 'jakarta', 'jabar', 'jateng', 'jatim', 'balnus', 'bali', 'nusra',
            'jawa', 'sumatra', 'sumatera', 'kalimantan', 'sulawesi', 'palmal', 'papua',
            'maluku', 'kti', 'ntb', 'ntt', 'witel', 'regional'
        ];
        foreach ($wilayahKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah teks merupakan sub-tipe layanan (Global, Domestik, dsb).
     */
    private function isSubServiceType(string $raw): bool
    {
        $lower = strtolower(trim($raw));
        if ($lower === '') return false;
        return in_array($lower, ['global', 'domestik', 'domestic', 'iix', 'lokal', 'intl', 'internasional', '1:1', '1:4', '1:8']);
    }

    /**
     * Normalisasi nama wilayah Telkom cerdas (mengatasi typo, huruf besar-kecil, & variasi penulisan).
     */
    private function normalizeWilayah(string $raw): string
    {
        $val = trim($raw);
        $lower = strtolower($val);

        // Jabodetabek / DKI Jakarta
        if (str_contains($lower, 'jabo') || str_contains($lower, 'dki') || (str_contains($lower, 'jakarta') && !str_contains($lower, 'jabar'))) {
            return 'Jabodetabek';
        }

        // Jabar & Jateng (gabungan Jawa Barat & Jawa Tengah)
        if ((str_contains($lower, 'jabar') && str_contains($lower, 'jateng')) ||
            (str_contains($lower, 'barat') && str_contains($lower, 'tengah')) ||
            str_contains($lower, 'jabar & jateng') || str_contains($lower, 'jabar jateng')) {
            return 'Jabar & Jateng';
        }

        // Jatim & Balnus (gabungan Jawa Timur & Bali Nusra)
        if ((str_contains($lower, 'jatim') && (str_contains($lower, 'balnus') || str_contains($lower, 'bali'))) ||
            str_contains($lower, 'jatim & balnus') || str_contains($lower, 'jatim balnus')) {
            return 'Jatim & Balnus';
        }

        // Standalone Jawa
        if ($lower === 'jawa' || str_contains($lower, 'pulau jawa')) {
            return 'Jawa';
        }
        if (str_contains($lower, 'jabar') || str_contains($lower, 'jawa barat')) {
            return 'Jawa Barat';
        }
        if (str_contains($lower, 'jateng') || str_contains($lower, 'jawa tengah')) {
            return 'Jawa Tengah';
        }
        if (str_contains($lower, 'jatim') || str_contains($lower, 'jawa timur')) {
            return 'Jawa Timur';
        }

        // Sumatera / Sumatra
        if (str_contains($lower, 'sumatra') || str_contains($lower, 'sumatera')) {
            return 'Sumatera';
        }

        // Kalimantan
        if (str_contains($lower, 'kalimantan') && !str_contains($lower, 'kti')) {
            return 'Kalimantan';
        }

        // Sulawesi
        if (str_contains($lower, 'sulawesi')) {
            return 'Sulawesi';
        }

        // Palmal / Papua & Maluku
        if (str_contains($lower, 'palmal') || (str_contains($lower, 'papua') && str_contains($lower, 'maluku')) ||
            str_contains($lower, 'maluku') || str_contains($lower, 'papua')) {
            return 'Palmal (Papua & Maluku)';
        }

        // Bali & Nusra standalone
        if (str_contains($lower, 'bali') || str_contains($lower, 'nusra') || str_contains($lower, 'ntb') || str_contains($lower, 'ntt')) {
            return 'Bali / Nusra';
        }

        // Format legacy: Kalimantan / KTI
        if (str_contains($lower, 'kti')) {
            return 'Kalimantan / KTI';
        }

        return ucwords(strtolower($val));
    }

    /**
     * Normalisasi sub-tipe layanan (Global / Domestik / dsb).
     */
    private function normalizeTipeLayanan(?string $raw): ?string
    {
        if (!$raw) return null;
        $val = trim($raw);
        $lower = strtolower($val);

        if (str_contains($lower, 'global') || str_contains($lower, 'intl') || str_contains($lower, 'internasional')) {
            return 'Global';
        }
        if (str_contains($lower, 'domestik') || str_contains($lower, 'domestic') || str_contains($lower, 'lokal') || str_contains($lower, 'iix')) {
            return 'Domestik';
        }
        if (str_contains($lower, 'best effort')) {
            return 'Best Effort';
        }
        if (str_contains($lower, 'critical')) {
            return 'Critical';
        }
        if (str_contains($lower, 'interactive')) {
            return 'Interactive';
        }

        return ucwords(strtolower($val));
    }

    /**
     * Normalisasi string bandwidth ("32", "Kbps" -> "32 Kbps", "10" -> "10 Mbps").
     */
    private function normalizeBandwidth(string $raw, ?string $unit = null): string
    {
        $trimmed = trim($raw);
        $unitTrimmed = trim((string)$unit);

        // Jika satuan dikirim dari kolom terpisah
        if ($unitTrimmed !== '') {
            $uLower = strtolower($unitTrimmed);
            if (str_contains($uLower, 'gbps') || str_contains($uLower, 'giga')) {
                return $trimmed . ' Gbps';
            }
            if (str_contains($uLower, 'mbps') || str_contains($uLower, 'mega')) {
                return $trimmed . ' Mbps';
            }
            if (str_contains($uLower, 'kbps') || str_contains($uLower, 'kilo')) {
                return $trimmed . ' Kbps';
            }
            return $trimmed . ' ' . $unitTrimmed;
        }

        // Jika raw sudah mengandung satuan: "32 Kbps" atau "10 Mbps"
        if (preg_match('/^(\d+(?:\.\d+)?)\s*(kbps|mbps|gbps)$/i', $trimmed, $m)) {
            $num = $m[1];
            $u = strtolower($m[2]);
            $normUnit = match($u) {
                'kbps' => 'Kbps',
                'gbps' => 'Gbps',
                default => 'Mbps',
            };
            return $num . ' ' . $normUnit;
        }

        // Default jika angka murni tanpa satuan: default ke Mbps
        if (preg_match('/^\d+(\.\d+)?$/', $trimmed)) {
            return $trimmed . ' Mbps';
        }

        return $trimmed;
    }

    /**
     * Konversi nilai uang dari cell Excel ke float.
     * Mengatasi format titik/koma Indonesia (misal 634.000 atau 1.105.000)
     * serta nilai float yang di-parse otomatis oleh Excel dari string bertitik.
     */
    private function parseNumericPrice($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Jika sudah berupa integer/float nominal penuh (misal 634000)
        if ((is_int($value) || is_float($value)) && $value >= 10000) {
            return (float) $value;
        }

        $str = trim((string) $value);
        // Hapus 'Rp', 'IDR', spasi, strip
        $str = preg_replace('/[^\d.,]/', '', $str);

        if ($str === '') {
            return 0.0;
        }

        $dotCount = substr_count($str, '.');
        $commaCount = substr_count($str, ',');

        if ($dotCount > 0 && $commaCount > 0) {
            $lastDot = strrpos($str, '.');
            $lastComma = strrpos($str, ',');
            if ($lastComma > $lastDot) {
                // Format Indo: 1.250.000,00 -> titik ribuan, koma desimal
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // Format US: 1,250,000.00 -> koma ribuan, titik desimal
                $str = str_replace(',', '', $str);
            }
        } elseif ($dotCount > 1) {
            // Banyak titik: 1.105.000 -> pemisah ribuan
            $str = str_replace('.', '', $str);
        } elseif ($commaCount > 1) {
            // Banyak koma: 1,105,000 -> pemisah ribuan
            $str = str_replace(',', '', $str);
        } elseif ($dotCount === 1) {
            // 1 titik: misal 634.000 vs 634.50
            // Jika tepat 3 digit setelah titik (634.000), ini adalah pemisah ribuan di format Indonesia!
            if (preg_match('/^\d+\.\d{3}$/', $str)) {
                $str = str_replace('.', '', $str);
            }
        } elseif ($commaCount === 1) {
            // 1 koma: misal 634,000 vs 634,50
            if (preg_match('/^\d+,\d{3}$/', $str)) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace(',', '.', $str);
            }
        }

        $result = (float) $str;

        // Auto-koreksi jika angka ribuan di Excel terkonversi menjadi desimal kecil (misal 634.0 maksudnya Rp 634.000)
        // Di katalog Telkom, tarif internet tidak ada yang bernilai satuan ratusan rupiah (< Rp 10.000)
        if ($result > 0 && $result < 10000 && (is_float($value) || str_contains((string)$value, '.'))) {
            $result = $result * 1000;
        }

        return $result;
    }

    /**
     * Normalisasi nama paket dari judul sheet:
     * - Mengutamakan paket default jika diisi di form.
     * - Jika sheet bawaan default Excel ('sheet1' / 'worksheet', case-insensitive), default ke 'Astinet'.
     * - Case-insensitive match dengan nama paket yang sudah ada di database (misal: "astinet" atau "ASTINET" -> "Astinet").
     */
    private function normalizePaketName(string $sheetTitle, ?string $paketDefault = null, array $existingPakets = []): string
    {
        if (!empty($paketDefault)) {
            return $this->matchExistingPaket(trim($paketDefault), $existingPakets);
        }

        $cleanTitle = trim($sheetTitle);
        $lowerTitle = strtolower($cleanTitle);

        // Hanya sheet bawaan kosong/default Excel yang diarahkan ke 'Astinet'
        if ($lowerTitle === 'sheet1' || $lowerTitle === 'worksheet' || empty($cleanTitle)) {
            return $this->matchExistingPaket('Astinet', $existingPakets);
        }

        // Cocokkan perbedaan huruf besar/kecil (Astinet vs astinet vs ASTINET) dengan data yang ada di database
        return $this->matchExistingPaket($cleanTitle, $existingPakets);
    }

    /**
     * Cocokkan nama paket dengan database secara case-insensitive:
     * Menangani perbedaan huruf kapital / huruf kecil (contoh: "astinet" atau "ASTINET" akan dicocokkan ke "Astinet").
     */
    private function matchExistingPaket(string $name, array $existingPakets = []): string
    {
        $trimmed = trim($name);
        if (empty($trimmed)) {
            return 'Astinet';
        }

        $lowerName = strtolower($trimmed);

        // 1. Cek kecocokan case-insensitive dengan paket yang sudah ada di database
        foreach ($existingPakets as $existing) {
            if ($lowerName === strtolower(trim($existing))) {
                return $existing; // Gunakan penulisan resmi yang sudah ada di database
            }
        }

        // 2. Jika paket baru (belum ada di DB), rapikan format hurufnya (Title Case, misal "astinet" -> "Astinet")
        return ucwords(strtolower($trimmed));
    }

    /**
     * Update Tarif Standar Tahunan OTC (One-Time Charge) Marketing.
     */
    public function updateDefaultOtc(Request $request)
    {
        $validated = $request->validate([
            'default_otc' => 'required|numeric|min:0',
        ]);

        $rdm = \App\Models\ParameterRdm::getActive();
        $oldVal = $rdm->default_otc;
        $rdm->update([
            'default_otc' => $validated['default_otc'],
            'updated_by'  => $request->user()->id,
        ]);

        activity('admin')
            ->causedBy($request->user())
            ->withProperties(['old_default_otc' => $oldVal, 'new_default_otc' => $validated['default_otc']])
            ->log("Admin memperbarui Tarif Standar Tahunan OTC Marketing menjadi Rp " . number_format($validated['default_otc'], 0, ',', '.'));

        return back()->with('status', 'Tarif Standar Tahunan OTC Marketing berhasil diperbarui menjadi Rp ' . number_format($validated['default_otc'], 0, ',', '.'));
    }
}
