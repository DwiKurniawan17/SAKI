<?php

namespace App\Http\Controllers;

use App\Models\ParameterRdm;
use App\Models\RevisiLog;
use App\Models\SimulasiAki;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SimulasiAkiController extends Controller
{
    /** Form baru simulasi. */
    public function create(Request $request)
    {
        $user = $request->user();
        $rdm = ParameterRdm::getActive();

        // Pihak A bisa juga melihat simulasi yang "menunggu pihak a" untuk dilengkapi
        if ($user->hasRole('engineering_capex')) {
            $pendingSimulasi = SimulasiAki::where('status', SimulasiAki::STATUS_MENUNGGU_PIHAK_A)
                ->whereNull('user_id_pihak_a')
                ->latest()
                ->get();
            return view('simulasi.create', compact('user', 'rdm', 'pendingSimulasi'));
        }

        return view('simulasi.create', compact('user', 'rdm'));
    }

    /** Simpan simulasi baru (Pihak B: draft/submit, Pihak A: buat langsung). */
    public function store(Request $request)
    {
        $user = $request->user();
        $rdm = ParameterRdm::getActive();

        if ($user->hasRole('sales_marketing')) {
            $validated = $request->validate([
                'nama_proyek'    => 'required|string|max:255',
                'lokasi'         => 'nullable|string|max:255',
                'durasi_kontrak' => 'required|integer|min:1|max:120',
                'bulan_tahun1'   => 'required|integer|min:1|max:12',
                'otc'            => 'required|numeric|min:0',
                'mrc'            => 'required|numeric|min:0',
            ]);

            // Hitung otomatis Direct Cost dari RDM
            $cogsInstall = $validated['otc'] * (1 - ($rdm->rdm_otc_pct / 100));
            $cogsMonthly = $validated['mrc'] * (1 - ($rdm->rdm_mrc_pct / 100));

            $inputData = array_merge($validated, [
                'cogs_install' => $cogsInstall,
                'cogs_monthly' => $cogsMonthly,
                'rdm_otc_pct'  => $rdm->rdm_otc_pct,
                'rdm_mrc_pct'  => $rdm->rdm_mrc_pct,
            ]);

            $simulasi = SimulasiAki::create([
                'user_id_pihak_b' => $user->id,
                'nama_proyek'     => $validated['nama_proyek'],
                'lokasi'          => $validated['lokasi'] ?? null,
                'input_json'      => $inputData,
                'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_A,
            ]);

            activity('simulasi')
                ->causedBy($user)
                ->performedOn($simulasi)
                ->log('Pihak B (Marketing) membuat simulasi baru');

            return redirect()->route('simulasi.show', $simulasi)
                ->with('status', 'Simulasi berhasil disimpan. Menunggu Pihak A (ED) melengkapi data CAPEX.');

        } elseif ($user->hasRole('engineering_capex')) {
            // Pihak A bisa buat sendiri (isi semua field)
            $validated = $this->validateAllFields($request);

            // Auto COGS jika tidak diinput khusus
            if (!isset($validated['cogs_install']) || $validated['cogs_install'] == 0) {
                $validated['cogs_install'] = $validated['otc'] * (1 - ($rdm->rdm_otc_pct / 100));
            }
            if (!isset($validated['cogs_monthly']) || $validated['cogs_monthly'] == 0) {
                $validated['cogs_monthly'] = $validated['mrc'] * (1 - ($rdm->rdm_mrc_pct / 100));
            }
            $validated['rdm_otc_pct'] = $rdm->rdm_otc_pct;
            $validated['rdm_mrc_pct'] = $rdm->rdm_mrc_pct;

            $simulasi = SimulasiAki::create([
                'user_id_pihak_b' => $user->id,
                'user_id_pihak_a' => $user->id,
                'nama_proyek'     => $validated['nama_proyek'],
                'lokasi'          => $validated['lokasi'] ?? null,
                'input_json'      => $validated,
                'status'          => SimulasiAki::STATUS_DRAFT_PIHAK_B,
            ]);

            return $this->calculateAndSave($simulasi, $validated, $user);
        }

        abort(403);
    }

    /** Pihak A melengkapi simulasi yang dibuat Pihak B. */
    public function completePihakA(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();

        if (!$user->hasRole('engineering_capex') && !$user->hasRole('admin')) {
            abort(403);
        }

        if ($simulasi->status !== SimulasiAki::STATUS_MENUNGGU_PIHAK_A) {
            return back()->with('error', 'Simulasi ini tidak dalam status menunggu Pihak A.');
        }

        $validated = $request->validate([
            'material'  => 'required|numeric|min:0',
            'jasa'      => 'required|numeric|min:0',
            'bop_pct'   => 'required|numeric|min:0|max:100',
            'lifetime'  => 'required|integer|min:1|max:20',
            'om_pct'    => 'required|numeric|min:0|max:50',
            'tax_pct'   => 'required|numeric|min:0|max:50',
            'wacc_pct'  => 'required|numeric|min:0|max:50',
        ]);

        // Merge Pihak B input + Pihak A input
        $fullInput = array_merge($simulasi->input_json ?? [], $validated);

        $simulasi->user_id_pihak_a = $user->id;

        return $this->calculateAndSave($simulasi, $fullInput, $user);
    }

    /** Detail / lihat simulasi. */
    public function show(Request $request, SimulasiAki $simulasi)
    {
        $this->authorizeAccess($simulasi, $request->user());

        return view('simulasi.show', [
            'simulasi' => $simulasi,
            'user'     => $request->user(),
        ]);
    }

    /** Riwayat simulasi. */
    public function riwayat(Request $request)
    {
        $user = $request->user();
        $query = SimulasiAki::forUser($user);

        if ($request->filled('q')) {
            $query->where('nama_proyek', 'like', '%' . $request->input('q') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $simulasis = $query->latest()->paginate(15)->withQueryString();

        return view('simulasi.riwayat', compact('simulasis', 'user'));
    }

    /** Hapus simulasi. */
    public function destroy(Request $request, SimulasiAki $simulasi)
    {
        $this->authorizeAccess($simulasi, $request->user());

        activity('simulasi')
            ->causedBy($request->user())
            ->performedOn($simulasi)
            ->log('Simulasi dihapus');

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

    /** RSO: Approve simulasi — set status approved. */
    public function approve(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        if (!in_array($simulasi->status, [SimulasiAki::STATUS_MENUNGGU_REVIEW, SimulasiAki::STATUS_LENGKAP_TERHITUNG])) {
            return back()->with('error', 'Simulasi ini tidak dalam status menunggu review.');
        }

        $simulasi->status = SimulasiAki::STATUS_APPROVED;
        $simulasi->reviewed_by = $user->id;
        $simulasi->reviewed_at = now();
        $simulasi->catatan_revisi = null;
        $simulasi->save();

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->log('RSO menyetujui simulasi');

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', '✅ Simulasi telah disetujui. Dokumen siap diunduh/cetak.');
    }

    /** RSO: Reject / minta revisi simulasi. */
    public function reject(Request $request, SimulasiAki $simulasi)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $simulasi->status = SimulasiAki::STATUS_REVISI;
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

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->withProperties(['catatan' => $validated['catatan_revisi']])
            ->log('RSO meminta revisi simulasi');

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', '⚠️ Simulasi dikembalikan untuk revisi.');
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

        $validated = $this->validateAllFields($request);

        $simulasi->catatan_revisi = null;

        return $this->calculateAndSave($simulasi, $validated, $user);
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
        $simulasi->layak           = $summary['layak'] ?? null;
        $simulasi->status          = SimulasiAki::STATUS_MENUNGGU_REVIEW;
        $simulasi->save();

        activity('simulasi')
            ->causedBy($user)
            ->performedOn($simulasi)
            ->withProperties(['layak' => $simulasi->layak])
            ->log('Simulasi dihitung dan disimpan');

        return redirect()->route('simulasi.show', $simulasi)
            ->with('status', 'Simulasi berhasil dihitung! ' . ($simulasi->layak ? '✅ LAYAK' : '❌ TIDAK LAYAK') . ' — Menunggu review RSO.');
    }

    private function authorizeAccess(SimulasiAki $simulasi, $user): void
    {
        if ($user->hasRole('admin')) return;

        if ($user->hasRole('sales_marketing') && $simulasi->user_id_pihak_b !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke simulasi ini.');
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
        return $request->validate([
            'nama_proyek'    => 'required|string|max:255',
            'lokasi'         => 'nullable|string|max:255',
            'durasi_kontrak' => 'required|integer|min:1|max:120',
            'bulan_tahun1'   => 'required|integer|min:1|max:12',
            'otc'            => 'required|numeric|min:0',
            'mrc'            => 'required|numeric|min:0',
            'cogs_install'   => 'required|numeric|min:0',
            'cogs_monthly'   => 'required|numeric|min:0',
            'material'       => 'required|numeric|min:0',
            'jasa'           => 'required|numeric|min:0',
            'bop_pct'        => 'required|numeric|min:0|max:100',
            'lifetime'       => 'required|integer|min:1|max:20',
            'om_pct'         => 'required|numeric|min:0|max:50',
            'tax_pct'        => 'required|numeric|min:0|max:50',
            'wacc_pct'       => 'required|numeric|min:0|max:50',
        ]);
    }
}
