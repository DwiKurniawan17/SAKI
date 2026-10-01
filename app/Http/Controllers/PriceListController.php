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
        } else {
            // Default 'terbaru': data yang baru di-upload/di-update langsung berada di urutan paling atas tanpa harus reset!
            $query->orderByDesc('updated_at')->orderBy('paketisasi')->orderBy('bandwidth');
        }

        $priceLists = $query->paginate(30)->withQueryString();
        $totalItems = PriceList::count();
        $uniquePaket = PriceList::distinct()->pluck('paketisasi');
        $uniqueWilayah = PriceList::distinct()->pluck('wilayah');

        return view('admin.price-list', compact('priceLists', 'totalItems', 'uniquePaket', 'uniqueWilayah'));
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
        $skippedCount = 0;
        $affectedIds = [];
        $affectedPakets = [];

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

            // 1. Deteksi Baris Header Utama (Mencari kolom Bandwidth di 20 baris pertama)
            $bwCol = null;
            $hRow1Idx = null;

            foreach ($rows as $idx => $row) {
                if ($idx > 20) break;
                foreach ($row as $colLetter => $cell) {
                    $cellVal = strtolower(trim((string)$cell));
                    if ($this->isBandwidthHeader($cellVal)) {
                        $bwCol = $colLetter;
                        $hRow1Idx = $idx;
                        break 2;
                    }
                }
            }

            if (!$hRow1Idx || !$bwCol) {
                continue; // Lewati sheet jika tidak memiliki kolom bandwidth
            }

            // 2. Periksa apakah terdapat baris Sub-Header (Format Multi-Level Header seperti Global / Domestik)
            $hRow2Idx = $hRow1Idx + 1;
            $isMultiLevel = false;

            if (isset($rows[$hRow2Idx])) {
                $subRow = $rows[$hRow2Idx];
                $subBwCell = trim((string)($subRow[$bwCol] ?? ''));

                // Jika kolom bandwidth di baris kedua bukan angka kecepatan valid
                // DAN terdapat sel lain yang berisi keyword sub-kategori (Global, Domestik, MRC, OTC, dsb)
                $hasSubKeywords = false;
                foreach ($subRow as $cLetter => $cVal) {
                    if ($cLetter === $bwCol) continue;
                    $cStr = strtolower(trim((string)$cVal));
                    if (str_contains($cStr, 'global') || str_contains($cStr, 'domestik') || str_contains($cStr, 'domestic') ||
                        str_contains($cStr, 'mrc') || str_contains($cStr, 'otc') || str_contains($cStr, '1:1') || str_contains($cStr, '1:4') ||
                        str_contains($cStr, 'iix') || str_contains($cStr, 'lokal') || str_contains($cStr, 'intl')) {
                        $hasSubKeywords = true;
                        break;
                    }
                }

                if ($hasSubKeywords && !$this->isValidBandwidthValue($subBwCell)) {
                    $isMultiLevel = true;
                }
            }

            // 3. Bangun Pemetaan Kolom (Column Mappings)
            $columnMappings = [];
            $dataStartRowIdx = $hRow1Idx + 1;

            if ($isMultiLevel) {
                $dataStartRowIdx = $hRow2Idx + 1;
                $row1 = $rows[$hRow1Idx];
                $row2 = $rows[$hRow2Idx];

                $lastParent = '';
                foreach ($row1 as $colLetter => $cellVal) {
                    if ($colLetter === $bwCol) continue;

                    // Ambil teks parent header (dari merged map jika ada, atau nilai sel langsung)
                    $parent = trim((string)($mergedMap[$hRow1Idx][$colLetter] ?? $cellVal));
                    if (!empty($parent)) {
                        $lastParent = $parent;
                    } else {
                        // Inherit nilai parent sebelumnya jika sel kosong karena merge
                        $parent = $lastParent;
                    }

                    $sub = trim((string)($row2[$colLetter] ?? ''));
                    if (empty($parent) && empty($sub)) continue;

                    $normWilayah = $this->normalizeWilayah($parent);
                    $normSub = $this->normalizeTipeLayanan($sub);

                    $columnMappings[$colLetter] = [
                        'wilayah' => $normWilayah,
                        'sub'     => $normSub,
                    ];
                }
            } else {
                // Cek apakah tabel berbentuk FLAT (memiliki kolom 'paket' dan 'tarif')
                $row1 = $rows[$hRow1Idx];
                $flatCols = [];

                foreach ($row1 as $colLetter => $cellVal) {
                    $lower = strtolower(trim((string)$cellVal));
                    if (str_contains($lower, 'paket') || str_contains($lower, 'produk') || str_contains($lower, 'layanan')) $flatCols['paket'] = $colLetter;
                    if (str_contains($lower, 'wilayah') || str_contains($lower, 'regional') || str_contains($lower, 'zona')) $flatCols['wilayah'] = $colLetter;
                    if (str_contains($lower, 'tarif') || str_contains($lower, 'harga') || str_contains($lower, 'mrc')) $flatCols['tarif'] = $colLetter;
                }

                if (isset($flatCols['tarif'])) {
                    // Mode Flat Table
                    foreach ($rows as $idx => $row) {
                        if ($idx < $dataStartRowIdx) continue;
                        $rawBw = trim((string)($row[$bwCol] ?? ''));
                        if (!$this->isValidBandwidthValue($rawBw) || $this->isJunkRow($row)) {
                            $skippedCount++;
                            continue;
                        }

                        $cleanBw = $this->normalizeBandwidth($rawBw);
                        $rawRowPaket = isset($flatCols['paket']) && !empty($row[$flatCols['paket']]) ? trim((string)$row[$flatCols['paket']]) : $basePaketName;
                        $rowPaket = $this->matchExistingPaket($rawRowPaket, $existingPakets);
                        $rowWilayah = isset($flatCols['wilayah']) && !empty($row[$flatCols['wilayah']]) ? $this->normalizeWilayah((string)$row[$flatCols['wilayah']]) : 'Semua Wilayah';
                        $price = $this->parseNumericPrice($row[$flatCols['tarif']] ?? null);

                        if ($price > 0 && $price < 100000000000) {
                            $existingRecord = PriceList::whereRaw('LOWER(TRIM(paketisasi)) = ?', [strtolower(trim($rowPaket))])
                                ->whereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower(trim($cleanBw))])
                                ->whereRaw('LOWER(TRIM(wilayah)) = ?', [strtolower(trim($rowWilayah))])
                                ->first();

                            if ($existingRecord) {
                                $existingRecord->update([
                                    'kategori' => 'connectivity',
                                    'tarif_normal_mrc' => $price,
                                ]);
                                $affectedIds[] = $existingRecord->id;
                                $affectedPakets[] = $existingRecord->paketisasi;
                                $updatedCount++;
                            } else {
                                $newRecord = PriceList::create([
                                    'paketisasi' => $rowPaket,
                                    'bandwidth' => $cleanBw,
                                    'wilayah' => $rowWilayah,
                                    'kategori' => 'connectivity',
                                    'tarif_normal_mrc' => $price,
                                ]);
                                $affectedIds[] = $newRecord->id;
                                $affectedPakets[] = $newRecord->paketisasi;
                                $importedCount++;
                            }
                        }
                    }
                    continue; // Lanjut ke sheet berikutnya
                }

                // Mode Matriks Standar (1 Baris Header)
                foreach ($row1 as $colLetter => $cellVal) {
                    if ($colLetter === $bwCol) continue;
                    $val = trim((string)$cellVal);
                    if (empty($val)) continue;

                    $normWilayah = $this->normalizeWilayah($val);
                    $columnMappings[$colLetter] = [
                        'wilayah' => $normWilayah,
                        'sub'     => null,
                    ];
                }
            }

            // 4. Proses Baris Data Tarif Matriks (baik 1-Level maupun 2-Level)
            if (!empty($columnMappings)) {
                foreach ($rows as $idx => $row) {
                    if ($idx < $dataStartRowIdx) continue;

                    $rawBw = trim((string)($row[$bwCol] ?? ''));
                    // Filter baris data: abaikan baris kosong, catatan kaki, subtotal, disclaimer
                    if (!$this->isValidBandwidthValue($rawBw) || $this->isJunkRow($row)) {
                        $skippedCount++;
                        continue;
                    }

                    $cleanBw = $this->normalizeBandwidth($rawBw);

                    foreach ($columnMappings as $colLetter => $meta) {
                        $rawPrice = $row[$colLetter] ?? null;
                        $price = $this->parseNumericPrice($rawPrice);

                        if ($price <= 0 || $price > 100000000000) {
                            continue;
                        }

                        $wilayah = $meta['wilayah'];
                        $sub = $meta['sub'];

                        // Integrasi Nama Paket Cerdas:
                        // Jika ada sub-header seperti 'Global' atau 'Domestik', buat nama paket spesifik: "Astinet Global"
                        if ($sub && in_array(strtolower($sub), ['global', 'domestik', 'domestic', 'lokal', 'intl', 'iix'])) {
                            $targetPaket = $basePaketName . ' ' . $sub;
                        } elseif ($sub) {
                            $targetPaket = $basePaketName . ' ' . $sub;
                        } else {
                            $targetPaket = $basePaketName;
                        }

                        // Cocokkan paket dengan database (toleran huruf besar-kecil & variasi spasi)
                        $targetPaket = $this->matchExistingPaket($targetPaket, $existingPakets);

                        // Cari data yang sudah ada secara case-insensitive agar tidak membuat baris duplikat
                        $existingRecord = PriceList::whereRaw('LOWER(TRIM(paketisasi)) = ?', [strtolower(trim($targetPaket))])
                            ->whereRaw('LOWER(TRIM(bandwidth)) = ?', [strtolower(trim($cleanBw))])
                            ->whereRaw('LOWER(TRIM(wilayah)) = ?', [strtolower(trim($wilayah))])
                            ->first();

                        if ($existingRecord) {
                            $existingRecord->update([
                                'kategori'         => 'connectivity',
                                'tarif_normal_mrc' => $price,
                                'keterangan'       => $sub ? "Tipe: {$sub}" : $existingRecord->keterangan,
                            ]);
                            $affectedIds[] = $existingRecord->id;
                            $affectedPakets[] = $existingRecord->paketisasi;
                            $updatedCount++;
                        } else {
                            $newRecord = PriceList::create([
                                'paketisasi'       => $targetPaket,
                                'bandwidth'        => $cleanBw,
                                'wilayah'          => $wilayah,
                                'kategori'         => 'connectivity',
                                'tarif_normal_mrc' => $price,
                                'keterangan'       => $sub ? "Tipe: {$sub}" : null,
                            ]);
                            $affectedIds[] = $newRecord->id;
                            $affectedPakets[] = $newRecord->paketisasi;
                            $importedCount++;
                        }
                    }
                }
            }
        }

        $totalProcessed = $importedCount + $updatedCount;
        if ($totalProcessed === 0) {
            return redirect()->route('admin.price-list')->with('error', 'Tidak ada data tarif yang berhasil diimpor. Pastikan header tabel memiliki kolom "Bandwidth" dan kolom wilayah (Jabodetabek, Jawa, Sumatera, Kalimantan / KTI).');
        }

        activity('admin')
            ->causedBy($request->user())
            ->log("Admin mengimpor Price List: {$importedCount} data baru, {$updatedCount} data diperbarui, {$skippedCount} baris dilewati");

        $statusMsg = "Berhasil memproses {$totalProcessed} tarif ({$importedCount} baru ditambahkan, {$updatedCount} diperbarui).";
        if ($skippedCount > 0) {
            $statusMsg .= " {$skippedCount} baris non-data/catatan dilewati secara otomatis.";
        }

        // Simpan daftar ID yang baru saja diproses ke flash session agar bisa di-highlight di tabel
        session()->flash('just_imported_ids', $affectedIds);

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
     * Hapus satu item price list.
     */
    public function destroy(PriceList $priceList)
    {
        $priceList->delete();
        return back()->with('status', 'Item tarif berhasil dihapus.');
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

        $record = PriceList::findTarif($paket, $bw, $wilayah, $tipe);

        if ($record) {
            return response()->json([
                'found'            => true,
                'paketisasi'       => $record->paketisasi,
                'bandwidth'        => $record->bandwidth,
                'wilayah'          => $record->wilayah,
                'tarif_normal_mrc' => (float) $record->tarif_normal_mrc,
                'tarif_normal_otc' => (float) $record->tarif_normal_otc,
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
     * Deteksi apakah suatu cell merupakan header kolom bandwidth.
     */
    private function isBandwidthHeader(string $val): bool
    {
        return str_contains($val, 'bandwidth') || $val === 'bw' || str_contains($val, 'kecepatan') ||
               str_contains($val, 'kapasitas') || (str_contains($val, 'mbps') && !str_contains($val, 'tarif'));
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
            str_contains($lower, 'keterangan') || str_contains($lower, 'syarat') || str_contains($lower, 'ketentuan')) {
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
     * Normalisasi nama wilayah Telkom cerdas (mengatasi typo, huruf besar-kecil, & variasi penulisan).
     */
    private function normalizeWilayah(string $raw): string
    {
        $val = trim($raw);
        $lower = strtolower($val);

        if (str_contains($lower, 'jabo') || str_contains($lower, 'dki') || str_contains($lower, 'jakarta')) {
            return 'Jabodetabek';
        }
        if (str_contains($lower, 'jawa') && !str_contains($lower, 'jabo')) {
            return 'Jawa';
        }
        if (str_contains($lower, 'sumatera') || str_contains($lower, 'sumatra')) {
            return 'Sumatera';
        }
        if (str_contains($lower, 'kalimantan') || str_contains($lower, 'kti') || str_contains($lower, 'timur') ||
            str_contains($lower, 'sulawesi') || str_contains($lower, 'papua') || str_contains($lower, 'maluku')) {
            return 'Kalimantan / KTI';
        }
        if (str_contains($lower, 'bali') || str_contains($lower, 'nusra') || str_contains($lower, 'ntb') || str_contains($lower, 'ntt')) {
            return 'Bali / Nusra';
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

        return ucwords(strtolower($val));
    }

    /**
     * Normalisasi string bandwidth ("10" -> "10 Mbps", "50 Mbps" -> "50 Mbps").
     */
    private function normalizeBandwidth(string $raw): string
    {
        $trimmed = trim($raw);
        if (preg_match('/^\d+(\.\d+)?$/', $trimmed)) {
            return $trimmed . ' Mbps';
        }
        return $trimmed;
    }

    /**
     * Konversi nilai uang dari cell Excel ke float.
     */
    private function parseNumericPrice($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $str = (string) $value;
        // Hapus 'Rp', spasi, strip
        $str = preg_replace('/[^\d.,]/', '', $str);

        // Atasi format titik/koma (misal 10.000.000 atau 10000000.00)
        if (substr_count($str, '.') > 1) {
            // Pemisah ribuan dengan titik: 10.000.000
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } elseif (substr_count($str, ',') > 1) {
            // Pemisah ribuan dengan koma: 10,000,000
            $str = str_replace(',', '', $str);
        } else {
            // Jika ada 1 koma di akhir (misal 10000,00), ubah ke titik
            if (str_contains($str, ',') && !str_contains($str, '.')) {
                $str = str_replace(',', '.', $str);
            }
        }

        return (float) $str;
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
}
