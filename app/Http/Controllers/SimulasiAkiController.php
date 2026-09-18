<?php

namespace App\Http\Controllers;

use App\Models\ParameterRdm;
use App\Models\RevisiLog;
use App\Models\SimulasiAki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SimulasiAkiController extends Controller
{
    /** Form baru simulasi — HANYA Engineering Development (ED). */
    public function create(Request $request)
    {
        $user = $request->user();
        // Hanya Engineering Development yang boleh membuat simulasi baru.
        // Marketing, RSO/Admin tidak bisa akses halaman ini.
        if (!$user->hasRole('engineering_capex')) {
            abort(403, 'Hanya Divisi Engineering Development (ED) yang dapat membuat simulasi baru.');
        }

        $rdm = ParameterRdm::getActive();

        // Daftar simulasi milik ED ini yang masih menunggu Marketing melengkapi
        $pendingSimulasi = SimulasiAki::where('status', SimulasiAki::STATUS_MENUNGGU_PIHAK_B)
            ->where('user_id_pihak_a', $user->id)
            ->latest()
            ->get();

        return view('simulasi.create', compact('user', 'rdm', 'pendingSimulasi'));
    }

    /**
     * Simpan simulasi baru — HANYA Engineering Development (ED).
     * Marketing tidak lagi bisa menjadi pemula simulasi.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        // Guard: hanya ED yang boleh membuat simulasi baru
        if (!$user->hasRole('engineering_capex')) {
            abort(403, 'Hanya Divisi Engineering Development (ED) yang dapat membuat simulasi baru.');
        }

        $rdm = ParameterRdm::getActive();

        // Engineering Development: input nama proyek, lokasi, biaya material dan jasa.
        // Tarif OTC/MRC akan dilengkapi oleh Marketing pada langkah berikutnya.
        $validated = $request->validate([
            'nama_proyek'    => 'required|string|max:255',
            'lokasi'         => 'nullable|string|max:255',
            'material'       => 'required|numeric|min:0',
            'jasa'           => 'required|numeric|min:0',
        ]);

        $inputData = [
            'nama_proyek'    => $validated['nama_proyek'],
            'lokasi'         => $validated['lokasi'] ?? null,
            'material'       => floatval($validated['material']),
            'jasa'           => floatval($validated['jasa']),
            // Parameter RSO (defaults, akan disesuaikan RSO saat review)
            'durasi_kontrak' => $rdm->lifetime * 12,
            'bulan_tahun1'   => 6,
            'bop_pct'        => $rdm->bop_pct,
            'lifetime'       => $rdm->lifetime,
            'om_pct'         => $rdm->om_pct,
            'tax_pct'        => $rdm->tax_pct,
            'wacc_pct'       => $rdm->wacc_pct,
            'rdm_otc_pct'    => $rdm->rdm_otc_pct,
            'rdm_mrc_pct'    => $rdm->rdm_mrc_pct,
        ];

        $simulasi = SimulasiAki::create([
            'user_id_pihak_b' => null, // Menunggu input dari Marketing
            'user_id_pihak_a' => $user->id,
            'nama_proyek'     => $validated['nama_proyek'],
            'lokasi'          => $validated['lokasi'] ?? null,
            'input_json'      => $inputData,
            'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
        ]);

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->log("Engineering ({$user->name}) membuat simulasi baru (menunggu input tarif & paketisasi dari Marketing)");

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', 'Data proyek & CAPEX berhasil disimpan! Menunggu divisi Marketing mengisi tarif OTC, MRC, dan paketisasi.');
    }

    /** Engineering: Import Excel multi-lokasi. */
    public function storeExcel(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('engineering_capex')) {
            abort(403, 'Hanya Engineering yang dapat menggunakan fitur import Excel.');
        }

        $request->validate([
            'excel_file'  => 'required|file|mimes:xlsx,xls|max:5120',
            'nama_proyek' => 'nullable|string|max:255',
        ]);

        $rdm = ParameterRdm::getActive();
        $file = $request->file('excel_file');

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, false, true);
        } catch (\Throwable $e) {
            Log::error('Excel parse error', ['msg' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Gagal membaca file Excel. Pastikan format file .xlsx/.xls valid.');
        }

        // Detect header row (cari baris yang mengandung "Material" dan "Jasa")
        $headerRowIdx = null;
        $colMap = [];
        $detectedNamaProyek = null;

        foreach ($rows as $idx => $row) {
            $tempMap = [];
            foreach ($row as $colLetter => $cell) {
                $cellVal = strtolower(trim((string)$cell));
                if (str_contains($cellVal, 'regional')) $tempMap['regional'] = $colLetter;
                if ($cellVal === 'lop' || (str_contains($cellVal, 'lop') && !str_contains($cellVal, 'lokasi'))) $tempMap['lop'] = $colLetter;
                if (str_contains($cellVal, 'lokasi') || str_contains($cellVal, 'location') || str_contains($cellVal, 'kota')) $tempMap['lokasi'] = $colLetter;
                if (str_contains($cellVal, 'id ihld') || str_contains($cellVal, 'ihld'))  $tempMap['id_ihld'] = $colLetter;
                if (str_contains($cellVal, 'material')) $tempMap['material'] = $colLetter;
                if (str_contains($cellVal, 'jasa'))     $tempMap['jasa'] = $colLetter;
            }
            if (isset($tempMap['material']) && isset($tempMap['jasa'])) {
                $headerRowIdx = $idx;
                $colMap = $tempMap;
                break;
            }

            // Cari potensi nama proyek di baris sebelum header (panjang > 10, bukan 'investasi')
            if (!$detectedNamaProyek) {
                foreach ($row as $cell) {
                    $val = trim((string)$cell);
                    if (strlen($val) > 10 && strtolower($val) !== 'investasi') {
                        $detectedNamaProyek = $val;
                        break;
                    }
                }
            }
        }

        if (!$headerRowIdx) {
            return back()->withInput()->with('error', 'Kolom "Material" dan "Jasa" tidak ditemukan di file Excel. Pastikan header tabel sesuai format.');
        }

        $namaProyek = trim((string)$request->input('nama_proyek'));
        if (empty($namaProyek)) {
            $namaProyek = $detectedNamaProyek;
        }
        if (empty($namaProyek)) {
            return back()->withInput()->with('error', 'Nama proyek wajib diisi. Masukkan nama proyek di form.');
        }

        // Parse data rows (skip header row & "Total" row)
        $dataRows = [];
        $warnings = [];
        $currentRegional = '';
        $currentLop = '';

        foreach ($rows as $idx => $row) {
            if ($idx <= $headerRowIdx) continue;

            // Skip row jika ada cell bertuliskan "Total"
            $isTotalRow = false;
            foreach ($row as $cell) {
                $cellVal = strtolower(trim((string)$cell));
                if ($cellVal === 'total' || str_starts_with($cellVal, 'total')) {
                    $isTotalRow = true;
                    break;
                }
            }
            if ($isTotalRow) continue;

            $regionalRaw = isset($colMap['regional']) ? trim((string)($row[$colMap['regional']] ?? '')) : '';
            $lopRaw      = isset($colMap['lop']) ? trim((string)($row[$colMap['lop']] ?? '')) : '';
            $lokasiRaw   = isset($colMap['lokasi']) ? trim((string)($row[$colMap['lokasi']] ?? '')) : '';
            $idIhldRaw   = isset($colMap['id_ihld']) ? trim((string)($row[$colMap['id_ihld']] ?? '')) : '';
            $material    = isset($colMap['material']) ? $this->parseExcelNumber($row[$colMap['material']] ?? null) : null;
            $jasa        = isset($colMap['jasa']) ? $this->parseExcelNumber($row[$colMap['jasa']] ?? null) : null;

            // Skip empty spacer rows: jika material & jasa kosong dan lokasi kosong
            if ($material === null && $jasa === null && empty($lokasiRaw) && empty($lopRaw) && empty($regionalRaw)) {
                continue;
            }
            if ($material === null && $jasa === null && empty($lokasiRaw)) {
                continue;
            }

            // Forward fill regional & lop (1 LOP dengan banyak lokasi)
            if (!empty($regionalRaw)) {
                $currentRegional = $regionalRaw;
            }
            if (!empty($lopRaw)) {
                $currentLop = $lopRaw;
            }

            $regional = !empty($regionalRaw) ? $regionalRaw : $currentRegional;
            $lop      = !empty($lopRaw) ? $lopRaw : $currentLop;
            $lokasi   = $lokasiRaw;

            $rowNum = count($dataRows) + 1;
            $rowWarnings = [];
            $lokasiLabel = $lokasi ?: ($lop ? "{$lop} (Baris {$rowNum})" : "Baris {$rowNum}");

            if ($material === null || $material < 0) {
                $rowWarnings[] = "Material kosong atau bukan angka valid";
            }
            if ($jasa === null || $jasa < 0) {
                $rowWarnings[] = "Jasa kosong atau bukan angka valid";
            }
            if (empty($lop) && empty($lokasi)) {
                $rowWarnings[] = "LOP atau Lokasi tidak boleh kosong";
            }

            if (!empty($rowWarnings)) {
                $warnings[] = "Lokasi \"{$lokasiLabel}\": " . implode('; ', $rowWarnings);
            }

            $dataRows[] = [
                'regional' => $regional,
                'lop'      => $lop,
                'lokasi'   => $lokasi,
                'id_ihld'  => $idIhldRaw,
                'material' => $material,
                'jasa'     => $jasa,
            ];
        }

        if (empty($dataRows)) {
            return back()->withInput()->with('error', 'Tidak ditemukan baris data valid di file Excel. Pastikan tabel memiliki data di bawah header.');
        }

        if (!empty($warnings)) {
            return back()->withInput()->with('excel_warnings', $warnings)->with('error', 'Ditemukan ' . count($warnings) . ' baris bermasalah di file Excel. Perbaiki file dan upload ulang.');
        }

        // Semua valid — buat simulasi per baris lokasi
        $createdCount = 0;
        $firstSimulasi = null;

        foreach ($dataRows as $row) {
            if (!empty($row['lokasi'])) {
                $lokasiStr = $row['regional'] ? ($row['regional'] . ' - ' . $row['lokasi']) : $row['lokasi'];
            } else {
                $lokasiStr = trim(implode(' - ', array_filter([$row['regional'], $row['lop']]))) ?: null;
            }

            $inputData = [
                'nama_proyek'    => $namaProyek,
                'lokasi'         => $lokasiStr,
                'lokasi_detail'  => $row['lokasi'],
                'regional'       => $row['regional'],
                'lop'            => $row['lop'],
                'id_ihld'        => $row['id_ihld'],
                'material'       => floatval($row['material']),
                'jasa'           => floatval($row['jasa']),
                // Defaults RSO (akan disesuaikan saat review)
                'durasi_kontrak' => $rdm->lifetime * 12,
                'bulan_tahun1'   => 6,
                'bop_pct'        => $rdm->bop_pct,
                'lifetime'       => $rdm->lifetime,
                'om_pct'         => $rdm->om_pct,
                'tax_pct'        => $rdm->tax_pct,
                'wacc_pct'       => $rdm->wacc_pct,
                'rdm_otc_pct'    => $rdm->rdm_otc_pct,
                'rdm_mrc_pct'    => $rdm->rdm_mrc_pct,
            ];

            $simulasi = SimulasiAki::create([
                'user_id_pihak_b' => null, // Menunggu input dari Marketing
                'user_id_pihak_a' => $user->id,
                'nama_proyek'     => $namaProyek,
                'lokasi'          => $lokasiStr,
                'input_json'      => $inputData,
                'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            ]);

            if (!$firstSimulasi) $firstSimulasi = $simulasi;
            $createdCount++;
        }

        activity('simulasi')
            ->causedBy($user)
            ->log("Engineering import Excel: {$createdCount} lokasi dari proyek \"{$namaProyek}\"");

        if ($createdCount === 1) {
            return redirect()->route('simulasi.show', $firstSimulasi)
                ->with('status', "1 simulasi berhasil dibuat dari file Excel. Menunggu Marketing mengisi tarif OTC, MRC, dan paketisasi.");
        }

        return redirect()->route('simulasi.riwayat')
            ->with('status', "{$createdCount} simulasi berhasil dibuat dari file Excel. Menunggu Marketing mengisi tarif OTC, MRC, dan paketisasi.");
    }

    /** Parse angka dari cell Excel (support numeric murni, format US 8,291,983, dan format Indo 8.291.983) */
    private function parseExcelNumber($value): ?float
    {
        if ($value === null) return null;
        $s = trim((string)$value);
        if ($s === '') return null;

        // Clean currency symbols or spaces
        $s = preg_replace('/[^\d.,\-]/', '', $s);
        if ($s === '' || $s === '-') return null;

        // Already pure numeric
        if (is_numeric($s)) return floatval($s);

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastDot > $lastComma) {
                // US format: 1,234,567.89 (comma is thousand, dot is decimal)
                $s = str_replace(',', '', $s);
            } else {
                // Indo/EU format: 1.234.567,89 (dot is thousand, comma is decimal)
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            }
        } elseif ($hasComma) {
            // Only comma: jika lebih dari 1 koma, pasti pemisah ribuan "8,291,983"
            if (substr_count($s, ',') > 1) {
                $s = str_replace(',', '', $s);
            } else {
                $parts = explode(',', $s);
                if (strlen($parts[1]) === 3 && strlen($parts[0]) <= 3) {
                    $s = str_replace(',', '', $s);
                } else {
                    $s = str_replace(',', '.', $s);
                }
            }
        } elseif ($hasDot) {
            // Only dot: jika lebih dari 1 titik, pasti pemisah ribuan "8.291.983"
            if (substr_count($s, '.') > 1) {
                $s = str_replace('.', '', $s);
            } else {
                $parts = explode('.', $s);
                if (strlen($parts[1]) === 3 && strlen($parts[0]) <= 3) {
                    $s = str_replace('.', '', $s);
                }
            }
        }

        return is_numeric($s) ? floatval($s) : null;
    }

    /** Marketing melengkapi parameter komersial (OTC, MRC, Paketisasi, Bandwidth). */
    public function completePihakB(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();

        if (!$user->hasRole('sales_marketing') && !$user->hasRole('admin')) {
            abort(403);
        }

        if ($simulasi->status !== SimulasiAki::STATUS_MENUNGGU_PIHAK_B) {
            return back()->with('error', 'Simulasi ini tidak dalam status menunggu input Marketing.');
        }

        $validated = $request->validate([
            'paketisasi'      => 'required|string|max:255',
            'bandwidth'       => 'nullable|string|max:100',
            'otc'             => 'required|numeric|min:0',
            'mrc'             => 'required|numeric|min:0',
            'durasi_kontrak'  => 'required|integer|min:1|max:120',
            'bulan_tahun1'    => 'required|integer|min:1|max:12',
        ]);

        $rdm = ParameterRdm::getActive();
        $cogsInstall = $validated['otc'] * (1 - ($rdm->rdm_otc_pct / 100));
        $cogsMonthly = $validated['mrc'] * (1 - ($rdm->rdm_mrc_pct / 100));

        $commercialData = [
            'paketisasi'     => $validated['paketisasi'],
            'bandwidth'      => $validated['bandwidth'] ?? null,
            'otc'            => floatval($validated['otc']),
            'mrc'            => floatval($validated['mrc']),
            'durasi_kontrak' => intval($validated['durasi_kontrak']),
            'bulan_tahun1'   => intval($validated['bulan_tahun1']),
            'cogs_install'   => $cogsInstall,
            'cogs_monthly'   => $cogsMonthly,
            'rdm_otc_pct'    => $rdm->rdm_otc_pct,
            'rdm_mrc_pct'    => $rdm->rdm_mrc_pct,
            // Parameter RSO (BOP - WACC)
            'bop_pct'        => $rdm->bop_pct,
            'lifetime'       => $rdm->lifetime,
            'om_pct'         => $rdm->om_pct,
            'tax_pct'        => $rdm->tax_pct,
            'wacc_pct'       => $rdm->wacc_pct,
        ];

        $fullInput = array_merge($simulasi->input_json ?? [], $commercialData);

        $simulasi->user_id_pihak_b = $user->id;
        $simulasi->paketisasi      = $validated['paketisasi'];
        $simulasi->bandwidth       = $validated['bandwidth'] ?? null;
        $simulasi->catatan_revisi  = null;

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->log("Marketing ({$user->name}) melengkapi data komersial (OTC, MRC, Paketisasi, Bandwidth)");

        return $this->calculateAndSave($simulasi, $fullInput, $user);
    }

    /**
     * @deprecated Jalur ini tidak lagi digunakan.
     * Alur lama: Marketing starter → Engineering melengkapi CAPEX (STATUS_MENUNGGU_PIHAK_A).
     * Alur baru: ED selalu jadi pemula (STATUS_MENUNGGU_PIHAK_B) → Marketing melengkapi.
     * Route simulasi.complete telah dihapus dari web.php.
     * Method ini dipertahankan untuk referensi historis saja.
     */
    public function completePihakA(Request $request, SimulasiAki $simulasi)
    {
        abort(404, 'Jalur ini sudah tidak aktif. Simulasi selalu dimulai oleh Engineering Development.');
    }

    /** Detail / lihat simulasi. */
    public function show(Request $request, SimulasiAki $simulasi)
    {
        $this->authorizeAccess($simulasi, $request->user());
        $simulasi->loadMissing(['pihakA', 'pihakB', 'reviewer']);

        return view('simulasi.show', [
            'simulasi' => $simulasi,
            'user'     => $request->user(),
        ]);
    }

    /** Riwayat simulasi. */
    public function riwayat(Request $request)
    {
        $user = $request->user();
        $query = SimulasiAki::forUser($user)->with(['pihakA', 'pihakB']);

        if ($request->filled('q')) {
            $query->where('nama_proyek', 'like', '%' . $request->input('q') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $simulasis = $query->latest()->paginate(15)->withQueryString();

        return view('simulasi.riwayat', compact('simulasis', 'user'));
    }

    /**
     * Hapus simulasi.
     * Aturan:
     * - Admin (RSO) boleh hapus simulasi apapun.
     * - Engineering (ED) hanya boleh hapus simulasi MILIKNYA (user_id_pihak_a === dia)
     *   dan HANYA selagi status masih `menunggu_pihak_b` (sebelum Marketing melengkapi).
     * - Marketing TIDAK boleh hapus simulasi apapun.
     */
    public function destroy(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            // Admin boleh hapus semua
        } elseif ($user->hasRole('engineering_capex')) {
            // ED hanya boleh hapus simulasi miliknya yang masih menunggu_pihak_b
            if ($simulasi->user_id_pihak_a !== $user->id) {
                abort(403, 'Anda hanya dapat menghapus simulasi yang Anda buat sendiri.');
            }
            if ($simulasi->status !== SimulasiAki::STATUS_MENUNGGU_PIHAK_B) {
                abort(403, 'Simulasi tidak dapat dihapus setelah Marketing melengkapi data atau RSO mulai mereview.');
            }
        } else {
            // Marketing dan role lain tidak boleh hapus
            abort(403, 'Anda tidak memiliki hak untuk menghapus simulasi.');
        }

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->log('Simulasi dihapus oleh ' . ($user->hasRole('admin') ? 'Admin/RSO' : 'Engineering'));

        $simulasi->delete();

        return redirect()->route('simulasi.riwayat')
            ->with('status', 'Simulasi berhasil dihapus.');
    }

    /** Download Excel proxy ke Python API — hanya bisa setelah disetujui RSO. */
    public function download(Request $request, SimulasiAki $simulasi)
    {
        $this->authorizeAccess($simulasi, $request->user());

        if (!$simulasi->input_json) {
            abort(400, 'Data simulasi belum lengkap.');
        }

        // PRD FR-12 / NFR-05: Hanya bisa download jika sudah disetujui RSO
        if ($simulasi->status !== SimulasiAki::STATUS_APPROVED) {
            return back()->with('error', 'Simulasi harus disetujui RSO (status disetujui_rso) terlebih dahulu sebelum bisa didownload.');
        }

        $response = Http::withHeaders($this->apiHeaders())
            ->timeout(20)
            ->post(config('services.calc_api.url') . '/api/export-excel', $simulasi->input_json);

        if ($response->failed()) {
            Log::error('Export Excel gagal', ['status' => $response->status()]);
            return back()->with('error', 'Gagal membuat file Excel dari mesin hitung. Pastikan API Python berjalan.');
        }

        $filename = 'Resume_AKI_' . str_replace(' ', '_', $simulasi->nama_proyek) . '.xlsx';

        return response($response->body(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /** RSO: Preview Excel file before approving or printing. */
    public function previewExcel(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $existing = $simulasi->input_json ?? [];

        // Support overrides from live RSO panel if submitted via POST
        $params = $request->only([
            'durasi_kontrak', 'bulan_tahun1', 'bop_pct', 'lifetime',
            'om_pct', 'tax_pct', 'wacc_pct', 'rdm_otc_pct', 'rdm_mrc_pct'
        ]);

        $merged = array_merge($existing, array_filter($params, fn($v) => $v !== null && $v !== ''));

        // Recalculate COGS if RDM changed
        $otc = floatval($merged['otc'] ?? 0);
        $mrc = floatval($merged['mrc'] ?? 0);
        $rdmOtc = floatval($merged['rdm_otc_pct'] ?? 0);
        $rdmMrc = floatval($merged['rdm_mrc_pct'] ?? 0);
        $merged['cogs_install'] = $otc * (1 - ($rdmOtc / 100));
        $merged['cogs_monthly'] = $mrc * (1 - ($rdmMrc / 100));
        $merged['nama_proyek']  = $simulasi->nama_proyek;
        $merged['lokasi']       = $simulasi->lokasi ?? '';
        $merged['durasi_kontrak'] = intval($merged['durasi_kontrak'] ?? 48);
        $merged['bulan_tahun1']   = intval($merged['bulan_tahun1'] ?? 6);
        $merged['lifetime']       = intval($merged['lifetime'] ?? 4);

        $response = Http::withHeaders($this->apiHeaders())
            ->timeout(25)
            ->post(config('services.calc_api.url') . '/api/export-excel', $merged);

        if ($response->failed()) {
            Log::error('Preview Excel gagal', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Gagal membuat file Excel preview dari mesin hitung.'], 500);
        }

        $filename = 'Preview_Resume_AKI_' . str_replace(' ', '_', $simulasi->nama_proyek) . '.xlsx';

        return response($response->body(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    /** RSO: Real-time recalculate (AJAX) — returns JSON, does NOT save. */
    public function recalculate(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'bop_pct'     => 'required|numeric|min:0|max:100',
            'lifetime'    => 'required|integer|min:1|max:20',
            'om_pct'      => 'required|numeric|min:0|max:50',
            'tax_pct'     => 'required|numeric|min:0|max:50',
            'wacc_pct'    => 'required|numeric|min:0|max:50',
            'rdm_otc_pct' => 'required|numeric|min:0|max:100',
            'rdm_mrc_pct' => 'required|numeric|min:0|max:100',
        ]);

        $existing = $simulasi->input_json ?? [];

        // Recalculate COGS based on new RDM values
        $otc = floatval($existing['otc'] ?? 0);
        $mrc = floatval($existing['mrc'] ?? 0);
        $cogsInstall = $otc * (1 - ($validated['rdm_otc_pct'] / 100));
        $cogsMonthly = $mrc * (1 - ($validated['rdm_mrc_pct'] / 100));

        // durasi_kontrak & bulan_tahun1 dipertahankan dari input_json (diisi Marketing)
        $input = array_merge($existing, $validated, [
            'cogs_install' => $cogsInstall,
            'cogs_monthly' => $cogsMonthly,
        ]);

        $response = Http::withHeaders($this->apiHeaders())
            ->timeout(30)
            ->post(config('services.calc_api.url') . '/api/calculate', $input);

        if ($response->failed()) {
            return response()->json(['error' => 'Gagal menghubungi mesin hitung.'], 500);
        }

        return response()->json($response->json());
    }

    /** RSO: Approve simulasi — save adjusted params, recalculate, then approve. */
    public function approve(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        if (!in_array($simulasi->status, [SimulasiAki::STATUS_MENUNGGU_REVIEW, SimulasiAki::STATUS_LENGKAP_TERHITUNG])) {
            return back()->with('error', 'Simulasi ini tidak dalam status menunggu review.');
        }

        // Validate RSO params (durasi_kontrak & bulan_tahun1 diambil dari input_json Marketing)
        $validated = $request->validate([
            'bop_pct'     => 'required|numeric|min:0|max:100',
            'lifetime'    => 'required|integer|min:1|max:20',
            'om_pct'      => 'required|numeric|min:0|max:50',
            'tax_pct'     => 'required|numeric|min:0|max:50',
            'wacc_pct'    => 'required|numeric|min:0|max:50',
            'rdm_otc_pct' => 'required|numeric|min:0|max:100',
            'rdm_mrc_pct' => 'required|numeric|min:0|max:100',
        ]);

        $existing = $simulasi->input_json ?? [];
        $otc = floatval($existing['otc'] ?? 0);
        $mrc = floatval($existing['mrc'] ?? 0);
        $cogsInstall = $otc * (1 - ($validated['rdm_otc_pct'] / 100));
        $cogsMonthly = $mrc * (1 - ($validated['rdm_mrc_pct'] / 100));

        // durasi_kontrak & bulan_tahun1 tetap dari input_json (tidak diubah RSO)
        $input = array_merge($existing, $validated, [
            'cogs_install' => $cogsInstall,
            'cogs_monthly' => $cogsMonthly,
        ]);

        // Final recalculation before approve
        $response = Http::withHeaders($this->apiHeaders())
            ->timeout(30)
            ->post(config('services.calc_api.url') . '/api/calculate', $input);

        if ($response->failed()) {
            return back()->with('error', 'Gagal menghubungi mesin hitung saat final approval.');
        }

        $summary = $response->json();

        $simulasi->input_json     = $input;
        $simulasi->hasil_json     = $summary;
        $simulasi->npv            = $summary['npv'] ?? null;
        $simulasi->mirr           = $summary['irr'] ?? null;
        $simulasi->payback_period = $summary['pbp'] ?? null;
        $simulasi->layak          = $summary['layak'] ?? null;
        $simulasi->status         = SimulasiAki::STATUS_APPROVED;
        $simulasi->reviewed_by    = $user->id;
        $simulasi->reviewed_at    = now();
        $simulasi->catatan_revisi = null;
        $simulasi->save();

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->withProperties(['layak' => $simulasi->layak])
            ->log('RSO menyetujui simulasi dengan parameter: BOP=' . $validated['bop_pct'] . '%, WACC=' . $validated['wacc_pct'] . '%');

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', 'Simulasi telah disetujui dengan parameter RSO. Dokumen siap diunduh dan dicetak.');
    }

    /** RSO: Reject / minta revisi simulasi — kirim kembali ke Marketing. */
    public function reject(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
            'target_revisi'  => 'nullable|string|in:marketing,engineering',
        ]);

        $targetRevisi = $validated['target_revisi'] ?? 'marketing';

        // Selalu ubah status ke STATUS_REVISI agar alur konsisten dan status revisi muncul di Marketing
        $simulasi->status = SimulasiAki::STATUS_REVISI;
        $simulasi->layak = false; // RSO meminta revisi karena hasil evaluasi belum layak
        $simulasi->reviewed_by = $user->id;
        $simulasi->reviewed_at = now();
        $simulasi->catatan_revisi = $validated['catatan_revisi'];
        $simulasi->save();

        // Catat ke revisi_log (riwayat siklus revisi)
        RevisiLog::create([
            'simulasi_id'  => $simulasi->id,
            'requested_by' => $user->id,
            'catatan'      => $validated['catatan_revisi'],
        ]);

        $targetLabel = $targetRevisi === 'marketing' ? 'Marketing' : 'Engineer Development';

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->withProperties(['catatan' => $validated['catatan_revisi'], 'target' => $targetRevisi, 'layak' => false])
            ->log("RSO meminta revisi simulasi karena belum layak (dikembalikan ke {$targetLabel})");

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', "Simulasi berstatus Belum Layak dan telah dikembalikan untuk revisi ke Divisi {$targetLabel}. Menunggu revisi selesai.");
    }

    /** User: Edit/revisi simulasi yang direject RSO. */
    public function edit(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        $this->authorizeAccess($simulasi, $user);

        if ($simulasi->status !== SimulasiAki::STATUS_REVISI) {
            return back()->with('error', 'Simulasi ini tidak dalam status revisi.');
        }

        return view('simulasi.edit', [
            'simulasi' => $simulasi,
            'user'     => $user,
        ]);
    }

    /** User: Submit revisi simulasi. */
    public function update(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        $this->authorizeAccess($simulasi, $user);

        if ($simulasi->status !== SimulasiAki::STATUS_REVISI) {
            return back()->with('error', 'Simulasi ini tidak dalam status revisi.');
        }

        // Saat status REVISI: hanya Marketing yang boleh mengerjakan revisi
        if ($simulasi->status === SimulasiAki::STATUS_REVISI && $user->hasRole('engineering_capex')) {
            return back()->with('error', 'Revisi hanya dapat dilakukan oleh Divisi Marketing.');
        }

        $rdm = ParameterRdm::getActive();
        $existing = $simulasi->input_json ?? [];

        if ($user->hasRole('engineering_capex')) {
            $validated = $request->validate([
                'nama_proyek'    => 'required|string|max:255',
                'lokasi'         => 'nullable|string|max:255',
                'material'       => 'required|numeric|min:0',
                'jasa'           => 'required|numeric|min:0',
            ]);

            $fullInput = array_merge($existing, $validated, [
                'durasi_kontrak' => $existing['durasi_kontrak'] ?? ($rdm->lifetime * 12),
                'bulan_tahun1'   => $existing['bulan_tahun1'] ?? 6,
                'bop_pct'     => $rdm->bop_pct,
                'lifetime'    => $rdm->lifetime,
                'om_pct'      => $rdm->om_pct,
                'tax_pct'     => $rdm->tax_pct,
                'wacc_pct'    => $rdm->wacc_pct,
                'rdm_otc_pct' => $rdm->rdm_otc_pct,
                'rdm_mrc_pct' => $rdm->rdm_mrc_pct,
            ]);
        } elseif ($user->hasRole('sales_marketing')) {
            $validated = $request->validate([
                'paketisasi'      => 'required|string|max:255',
                'bandwidth'       => 'nullable|string|max:100',
                'otc'             => 'required|numeric|min:0',
                'mrc'             => 'required|numeric|min:0',
                'durasi_kontrak'  => 'nullable|integer|min:1|max:120',
                'bulan_tahun1'    => 'nullable|integer|min:1|max:12',
            ]);

            $cogsInstall = $validated['otc'] * (1 - ($rdm->rdm_otc_pct / 100));
            $cogsMonthly = $validated['mrc'] * (1 - ($rdm->rdm_mrc_pct / 100));

            $fullInput = array_merge($existing, [
                'paketisasi'     => $validated['paketisasi'],
                'bandwidth'      => $validated['bandwidth'] ?? ($existing['bandwidth'] ?? null),
                'otc'            => floatval($validated['otc']),
                'mrc'            => floatval($validated['mrc']),
                'durasi_kontrak' => intval($validated['durasi_kontrak'] ?? $existing['durasi_kontrak'] ?? 48),
                'bulan_tahun1'   => intval($validated['bulan_tahun1'] ?? $existing['bulan_tahun1'] ?? 6),
                'cogs_install'   => $cogsInstall,
                'cogs_monthly'   => $cogsMonthly,
                'rdm_otc_pct'    => $rdm->rdm_otc_pct,
                'rdm_mrc_pct'    => $rdm->rdm_mrc_pct,
                'bop_pct'        => $rdm->bop_pct,
                'lifetime'       => $rdm->lifetime,
                'om_pct'         => $rdm->om_pct,
                'tax_pct'        => $rdm->tax_pct,
                'wacc_pct'       => $rdm->wacc_pct,
            ]);
            $simulasi->user_id_pihak_b = $user->id; // Rekam user marketing yang mengupdate revisi
            $simulasi->paketisasi      = $validated['paketisasi'];
            $simulasi->bandwidth       = $validated['bandwidth'] ?? ($existing['bandwidth'] ?? null);
            activity('simulasi')
                ->causedBy($user)
                ->performedOn($simulasi)
                ->log("Marketing ({$user->name}) memperbarui data revisi komersial");
        } else {
            $fullInput = $this->validateAllFields($request);
            if (!empty($fullInput['paketisasi'])) {
                $simulasi->paketisasi = $fullInput['paketisasi'];
            }
            if (!empty($fullInput['bandwidth'])) {
                $simulasi->bandwidth = $fullInput['bandwidth'];
            }
        }

        $simulasi->nama_proyek = $fullInput['nama_proyek'] ?? $simulasi->nama_proyek;
        $simulasi->lokasi = $fullInput['lokasi'] ?? $simulasi->lokasi;
        $simulasi->catatan_revisi = null;

        return $this->calculateAndSave($simulasi, $fullInput, $user);
    }

    // ---- Private helpers ----

    private function calculateAndSave(SimulasiAki $simulasi, array $input, $user)
    {
        $response = Http::withHeaders($this->apiHeaders())
            ->timeout(30)
            ->post(config('services.calc_api.url') . '/api/calculate', $input);

        if ($response->failed()) {
            Log::error('Calc API gagal', ['status' => $response->status(), 'body' => $response->body()]);
            return back()->withInput()
                ->with('error', 'Gagal menghubungi mesin hitung. Pastikan API Python berjalan di ' . config('services.calc_api.url'));
        }

        $summary = $response->json();

        $simulasi->input_json      = $input;
        $simulasi->hasil_json      = $summary;
        $simulasi->npv             = $summary['npv'] ?? null;
        $simulasi->mirr            = $summary['irr'] ?? null;
        $simulasi->payback_period  = $summary['pbp'] ?? null;
        // Jangan langsung cap layak/tidak layak saat user input data.
        // Kelayakan resmi ditentukan oleh RSO setelah review dan memasukkan parameter yang sesuai.
        $simulasi->layak           = null;
        $simulasi->status          = SimulasiAki::STATUS_MENUNGGU_REVIEW;
        $simulasi->save();

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->withProperties(['status' => $simulasi->status])
            ->log('Data simulasi disimpan, diteruskan untuk penyesuaian parameter dan review RSO');

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', 'Data simulasi berhasil disimpan dan dikirim ke RSO. Menunggu RSO menentukan parameter dan melakukan evaluasi kelayakan.');
    }

    private function authorizeAccess(SimulasiAki $simulasi, $user): void
    {
        if ($user->hasRole('admin')) return;

        if ($user->hasRole('sales_marketing')) {
            if ($simulasi->user_id_pihak_b !== $user->id &&
                $simulasi->user_id_pihak_a !== $user->id &&
                $simulasi->status !== SimulasiAki::STATUS_MENUNGGU_PIHAK_B &&
                $simulasi->status !== SimulasiAki::STATUS_REVISI) {
                abort(403, 'Anda tidak memiliki akses ke simulasi ini.');
            }
        }

        if ($user->hasRole('engineering_capex')) {
            if ($simulasi->user_id_pihak_a !== $user->id &&
                $simulasi->user_id_pihak_b !== $user->id &&
                $simulasi->status !== SimulasiAki::STATUS_MENUNGGU_PIHAK_A) {
                abort(403, 'Anda tidak memiliki akses ke simulasi ini.');
            }
        }
    }

    private function apiHeaders(): array
    {
        $key = config('services.calc_api.key');
        return $key ? ['X-API-KEY' => $key] : [];
    }

    private function validateAllFields(Request $request): array
    {
        $rdm = ParameterRdm::getActive();

        $validated = $request->validate([
            'nama_proyek'    => 'required|string|max:255',
            'lokasi'         => 'nullable|string|max:255',
            'durasi_kontrak' => 'required|integer|min:1|max:120',
            'bulan_tahun1'   => 'required|integer|min:1|max:12',
            'paketisasi'     => 'nullable|string|max:255',
            'bandwidth'      => 'nullable|string|max:100',
            'otc'            => 'required|numeric|min:0',
            'mrc'            => 'required|numeric|min:0',
            'material'       => 'required|numeric|min:0',
            'jasa'           => 'required|numeric|min:0',
            'bop_pct'        => 'nullable|numeric|min:0|max:100',
            'lifetime'       => 'nullable|integer|min:1|max:20',
            'om_pct'         => 'nullable|numeric|min:0|max:50',
            'tax_pct'        => 'nullable|numeric|min:0|max:50',
            'wacc_pct'       => 'nullable|numeric|min:0|max:50',
        ]);

        $cogsInstall = $validated['otc'] * (1 - ($rdm->rdm_otc_pct / 100));
        $cogsMonthly = $validated['mrc'] * (1 - ($rdm->rdm_mrc_pct / 100));

        return array_merge($validated, [
            'cogs_install' => $cogsInstall,
            'cogs_monthly' => $cogsMonthly,
            'rdm_otc_pct'  => $rdm->rdm_otc_pct,
            'rdm_mrc_pct'  => $rdm->rdm_mrc_pct,
            'bop_pct'      => $validated['bop_pct'] ?? $rdm->bop_pct,
            'lifetime'     => $validated['lifetime'] ?? $rdm->lifetime,
            'om_pct'       => $validated['om_pct'] ?? $rdm->om_pct,
            'tax_pct'      => $validated['tax_pct'] ?? $rdm->tax_pct,
            'wacc_pct'     => $validated['wacc_pct'] ?? $rdm->wacc_pct,
        ]);
    }
}
