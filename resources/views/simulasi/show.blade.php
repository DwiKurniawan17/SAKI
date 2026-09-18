<x-layouts.app title="Detail Simulasi">
    <div class="space-y-6 max-w-6xl mx-auto"
         @if($user->hasRole('admin'))
         x-data="rsoReviewForm()"
         @if(in_array($simulasi->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
         x-init="recalculate()"
         @endif
         @endif
    >
        {{-- Top Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <a href="{{ route('simulasi.riwayat') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Riwayat</span>
            </a>
            @if($simulasi->status === \App\Models\SimulasiAki::STATUS_APPROVED)
            <div class="flex items-center gap-2">
                @if($user->hasRole('admin'))
                <button type="button" 
                        @click="openExcelPreview()" 
                        :disabled="previewLoading" 
                        class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-50 text-xs font-semibold px-4 h-10 rounded-lg border border-slate-300 shadow-sm transition-all">
                    <template x-if="!previewLoading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                            <span>Preview Excel</span>
                        </span>
                    </template>
                    <template x-if="previewLoading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-slate-500"></i>
                            <span>Memuat...</span>
                        </span>
                    </template>
                </button>
                @endif
                <a href="{{ route('simulasi.download', $simulasi) }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 h-10 rounded-lg shadow-sm transition-all">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Download Excel (.xlsx)</span>
                </a>
            </div>
            @else
            <div class="flex items-center gap-2">
                @if($user->hasRole('admin'))
                <button type="button" 
                        @click="openExcelPreview()" 
                        :disabled="previewLoading" 
                        class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-50 text-xs font-semibold px-4 h-10 rounded-lg border border-slate-300 shadow-sm transition-all">
                    <template x-if="!previewLoading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                            <span>Preview Format Excel</span>
                        </span>
                    </template>
                    <template x-if="previewLoading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-slate-500"></i>
                            <span>Memuat Preview...</span>
                        </span>
                    </template>
                </button>
                @endif
                <div class="inline-flex items-center gap-2 bg-slate-100 border border-slate-200 text-slate-400 text-xs font-medium px-3.5 h-10 rounded-lg cursor-not-allowed" title="Download Excel hanya aktif setelah disetujui RSO">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Download Excel (Menunggu Approval)</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Header Status Card --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                    <span class="font-mono">ID: #{{ $simulasi->id }}</span>
                    <span>•</span>
                    <span>Dibuat: <strong>{{ $simulasi->created_at->translatedFormat('d M Y, H:i') }} WIB</strong> ({{ $simulasi->created_at->diffForHumans() }})</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">{{ $simulasi->nama_proyek }}</h1>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600 mt-2">
                    {{-- ED (Penginput Proyek & CAPEX) --}}
                    @if($simulasi->pihakA)
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200" title="Diinput oleh Engineering Development (ED)">
                        <i data-lucide="wrench" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Penginput (ED): <strong class="text-slate-900">{{ $simulasi->pihakA->name }}</strong>@if(auth()->id() === $simulasi->pihakA->id) <span class="text-slate-400 font-normal text-[11px]">(Anda)</span>@endif</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    @endif

                    {{-- Marketing (Pengisi / Pengupdate Komersial) --}}
                    @if($simulasi->pihakB)
                    <span class="inline-flex items-center gap-1.5 font-medium text-blue-800 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200" title="Parameter komersial dilengkapi oleh Divisi Marketing">
                        <i data-lucide="tag" class="w-3.5 h-3.5 text-blue-600"></i>
                        <span>Diupdate Komersial (MKT): <strong class="text-blue-900">{{ $simulasi->pihakB->name }}</strong>@if(auth()->id() === $simulasi->pihakB->id) <span class="text-blue-400 font-normal text-[11px]">(Anda)</span>@endif</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    @elseif($simulasi->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_B)
                    <span class="inline-flex items-center gap-1.5 font-medium text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200" title="Menunggu Divisi Marketing melengkapi tarif komersial">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Menunggu Input Komersial Marketing</span>
                    </span>
                    <span class="text-slate-300">•</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Lokasi: <strong>{{ $simulasi->lokasi ?? '-' }}</strong></span>
                    </span>
                    @if($simulasi->paketisasi)
                    <span class="text-slate-300">•</span>
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="package" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Paket: <strong class="text-slate-800">{{ $simulasi->paketisasi }}</strong></span>
                    </span>
                    @endif
                    @if($simulasi->bandwidth)
                    <span class="text-slate-300">•</span>
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="gauge" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Bandwidth: <strong class="text-slate-800">{{ $simulasi->bandwidth }}</strong></span>
                    </span>
                    @endif
                </div>
            </div>
            <div>
                @if($simulasi->status === \App\Models\SimulasiAki::STATUS_APPROVED)
                    @if($simulasi->layak === true)
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-2.5 rounded-lg text-center shadow-sm min-w-[140px]">
                            <span class="text-[10px] font-semibold text-emerald-600 block tracking-wider uppercase">Hasil Evaluasi</span>
                            <span class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-800 mt-0.5">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                                <span>LAYAK</span>
                            </span>
                        </div>
                    @else
                        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-2.5 rounded-lg text-center shadow-sm min-w-[140px]">
                            <span class="text-[10px] font-semibold text-rose-600 block tracking-wider uppercase">Hasil Evaluasi</span>
                            <span class="inline-flex items-center gap-1.5 text-sm font-bold text-rose-800 mt-0.5">
                                <i data-lucide="x-circle" class="w-4 h-4 text-rose-600"></i>
                                <span>TIDAK LAYAK</span>
                            </span>
                        </div>
                    @endif
                @elseif(in_array($simulasi->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-5 py-2.5 rounded-lg text-center shadow-sm min-w-[140px]">
                        <span class="text-[10px] font-semibold text-blue-600 block tracking-wider uppercase">Hasil Evaluasi</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-800 mt-0.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-600"></i>
                            <span>Menunggu Review RSO</span>
                        </span>
                    </div>
                @elseif($simulasi->status === \App\Models\SimulasiAki::STATUS_REVISI)
                    @if($user->hasRole('admin'))
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-5 py-2.5 rounded-lg text-center shadow-sm min-w-[140px]">
                        <span class="text-[10px] font-semibold text-amber-600 block tracking-wider uppercase">Status Alur</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 mt-0.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Waiting Revisi Marketing</span>
                        </span>
                    </div>
                    @else
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-5 py-2.5 rounded-lg text-center shadow-sm min-w-[140px]">
                        <span class="text-[10px] font-semibold text-amber-600 block tracking-wider uppercase">Hasil Evaluasi</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 mt-0.5">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Perlu Revisi (Belum Layak)</span>
                        </span>
                    </div>
                    @endif
                @else
                    <div class="bg-slate-50 border border-slate-200 text-slate-800 px-5 py-2.5 rounded-lg text-center shadow-sm min-w-[140px]">
                        <span class="text-[10px] font-semibold text-slate-500 block tracking-wider uppercase">Status Alur</span>
                        <span class="text-xs font-bold block text-slate-800 mt-0.5">{{ $simulasi->status_label }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Review Status Badge --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_APPROVED)
        <div class="bg-emerald-50/80 border border-emerald-200 rounded-xl p-4 flex items-center gap-3.5">
            <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700 shrink-0 border border-emerald-200">
                <i data-lucide="check-check" class="w-4 h-4"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-emerald-900">Disetujui oleh RSO</p>
                <p class="text-xs text-emerald-700 mt-0.5">Dokumen resmi disetujui oleh {{ $simulasi->reviewer?->name ?? 'Admin' }} {{ $simulasi->reviewed_at ? 'pada ' . $simulasi->reviewed_at->format('d M Y, H:i') . ' WIB' : '' }}. Siap dicetak/diunduh.</p>
            </div>
        </div>
        @endif

        {{-- Catatan Revisi & Status Waiting --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_REVISI)
            {{-- Tampilan RSO (Admin): Waiting Revisi oleh Marketing --}}
            @if($user->hasRole('admin'))
            <div class="bg-amber-50/90 border border-amber-200 rounded-xl p-5 sm:p-6 space-y-3.5 shadow-sm">
                <div class="flex items-start gap-3.5 border-b border-amber-200/80 pb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0 border border-amber-200">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-200 text-amber-900">Waiting Revisi</span>
                            <span class="text-xs text-amber-800 font-semibold">• Divisi Marketing</span>
                        </div>
                        <h2 class="text-base font-bold text-amber-950 mt-1">Waiting Revisi oleh Divisi Marketing</h2>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Usulan simulasi dinilai <strong>belum layak investasi</strong> dan telah dikembalikan ke Divisi Marketing {{ $simulasi->reviewed_at ? 'pada ' . $simulasi->reviewed_at->translatedFormat('d M Y, H:i') . ' WIB' : '' }}. Menunggu pihak Marketing melakukan penyesuaian parameter komersial.
                        </p>
                    </div>
                </div>
                @if($simulasi->catatan_revisi)
                <div class="bg-white rounded-lg p-3.5 border border-amber-200 text-xs text-slate-800 space-y-1 shadow-xs">
                    <span class="font-bold text-[11px] text-amber-900 block uppercase tracking-wider">Catatan Revisi yang Dikirimkan ke Marketing:</span>
                    <p class="leading-relaxed font-sans text-slate-800">{{ $simulasi->catatan_revisi }}</p>
                    <span class="text-[10px] text-slate-400 block pt-1">Direview oleh {{ $simulasi->reviewer?->name ?? 'Admin RSO' }} &bull; {{ $simulasi->reviewed_at ? $simulasi->reviewed_at->translatedFormat('d M Y, H:i') . ' WIB' : '' }}</span>
                </div>
                @endif
            </div>
            @elseif($user->hasRole('sales_marketing'))
            {{-- Tampilan Marketing: Permintaan Revisi Belum Layak --}}
            <div class="bg-amber-50/90 border border-amber-200 rounded-xl p-5 sm:p-6 space-y-3.5 shadow-sm">
                <div class="flex items-start gap-3.5 border-b border-amber-200/80 pb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0 border border-amber-200">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-700"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200">Status: Perlu Revisi</span>
                            <span class="text-xs text-amber-800 font-semibold">• Belum Layak Investasi</span>
                        </div>
                        <h2 class="text-base font-bold text-amber-950 mt-1">Permintaan Revisi dari RSO (Belum Layak Investasi)</h2>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Pihak RSO mengembalikan usulan ini untuk direvisi karena parameter saat ini dinilai <strong>belum mencapai kelayakan investasi</strong>. Silakan periksa catatan RSO di bawah dan sesuaikan parameter komersial Anda.
                        </p>
                    </div>
                </div>
                @if($simulasi->catatan_revisi)
                <div class="bg-white rounded-lg p-3.5 border border-amber-200 text-xs text-slate-800 space-y-1 shadow-xs">
                    <span class="font-bold text-[11px] text-amber-900 block uppercase tracking-wider">Catatan Revisi dari RSO:</span>
                    <p class="leading-relaxed font-sans text-slate-800">{{ $simulasi->catatan_revisi }}</p>
                    <span class="text-[10px] text-slate-400 block pt-1">Direview oleh {{ $simulasi->reviewer?->name ?? 'Admin RSO' }} &bull; {{ $simulasi->reviewed_at ? $simulasi->reviewed_at->translatedFormat('d M Y, H:i') . ' WIB' : '' }}</span>
                </div>
                @endif
            </div>
            @else
            {{-- Tampilan ED: Informasi Revisi Marketing --}}
            <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-4 flex items-center gap-3 text-xs text-amber-900">
                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-700 shrink-0"></i>
                <div>
                    <p class="font-bold">Status: Perlu Revisi oleh Divisi Marketing</p>
                    <p class="text-[11px] text-amber-700 mt-0.5">Usulan dikembalikan oleh RSO ke Divisi Marketing untuk penyesuaian tarif/komersial. Data CAPEX fisik Anda tetap tersimpan.</p>
                </div>
            </div>
            @endif
        @endif

        {{-- Riwayat Siklus Revisi (FR-11) --}}
        @if($simulasi->revisiLogs && $simulasi->revisiLogs->count() > 0)
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                <span>Riwayat Siklus Revisi ({{ $simulasi->revisiLogs->count() }} Kali)</span>
            </h2>
            <div class="space-y-2">
                @foreach($simulasi->revisiLogs as $log)
                <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 text-xs space-y-1.5">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="font-semibold text-slate-700">Reviewer: {{ $log->reviewer?->name ?? 'RSO' }}</span>
                        <span>{{ $log->created_at->format('d M Y, H:i') }} WIB</span>
                    </div>
                    <p class="text-slate-800 bg-white p-2.5 rounded-md border border-slate-200 text-xs leading-relaxed">{{ $log->catatan }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- RSO Review Actions (admin only, saat status menunggu_review) --}}
        @if($user->hasRole('admin') && in_array($simulasi->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
        @php
            $rdmMaster = \App\Models\ParameterRdm::getActive();
            $inp = $simulasi->input_json ?? [];
        @endphp
        <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7 shadow-sm space-y-6">

            <div class="flex items-start gap-3.5 border-b border-slate-200 pb-4">
                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-700 shrink-0 border border-slate-200">
                    <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Review & Penyesuaian Parameter Keuangan (RSO)</h2>
                    <p class="text-xs text-slate-500 mt-1">Sesuaikan parameter BOP, Lifetime, O&M, Pajak, WACC, dan RDM per proyek. Hasil evaluasi kelayakan akan diperbarui secara <strong>real-time</strong>.</p>
                </div>
            </div>

            {{-- Ringkasan Input User --}}
            <div class="bg-slate-50 rounded-lg border border-slate-200 p-4 space-y-2 text-xs">
                <span class="font-bold text-slate-700 block text-[11px] uppercase tracking-wider">Data Kolaborasi Antar Divisi (ED & Marketing)</span>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 pt-1">
                    <div>
                        <span class="text-slate-500 block text-[11px]">Penginput CAPEX (ED)</span>
                        <span class="font-semibold text-slate-900 inline-flex items-center gap-1 mt-0.5">
                            <i data-lucide="wrench" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>{{ $simulasi->pihakA?->name ?? '-' }}</span>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Pengisi Komersial (MKT)</span>
                        <span class="font-semibold text-slate-900 inline-flex items-center gap-1 mt-0.5">
                            <i data-lucide="tag" class="w-3.5 h-3.5 text-blue-500"></i>
                            <span>{{ $simulasi->pihakB?->name ?? 'Belum Diisi' }}</span>
                        </span>
                    </div>
                    <div><span class="text-slate-500 block text-[11px]">Nama Proyek</span><span class="font-semibold text-slate-900">{{ $simulasi->nama_proyek }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Lokasi</span><span class="font-semibold text-slate-900">{{ $simulasi->lokasi ?? '-' }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Paketisasi</span><span class="font-semibold text-slate-900">{{ $simulasi->paketisasi ?? '-' }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Bandwidth</span><span class="font-semibold text-slate-900">{{ $simulasi->bandwidth ?? '-' }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Tarif OTC</span><span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['otc'] ?? 0, 0, ',', '.') }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Tarif MRC</span><span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['mrc'] ?? 0, 0, ',', '.') }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Biaya Material</span><span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['material'] ?? 0, 0, ',', '.') }}</span></div>
                    <div><span class="text-slate-500 block text-[11px]">Biaya Jasa</span><span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['jasa'] ?? 0, 0, ',', '.') }}</span></div>
                </div>
            </div>

            {{-- Parameter RSO (Editable) --}}
            <div class="bg-slate-50/60 rounded-lg border border-slate-200 p-5 space-y-4">
                <div class="flex items-center gap-2 font-bold text-slate-800 text-xs uppercase tracking-wider">
                    <i data-lucide="settings-2" class="w-4 h-4 text-slate-500"></i>
                    <span>Parameter Proyek & Asumsi Keuangan</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">BOP (%)</label>
                        <input type="number" x-model.number="params.bop_pct" @input.debounce.500ms="recalculate()" step="any" min="0" max="100"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lifetime (Tahun)</label>
                        <input type="number" x-model.number="params.lifetime" @input.debounce.500ms="recalculate()" min="1" max="20"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">O&M (%)</label>
                        <input type="number" x-model.number="params.om_pct" @input.debounce.500ms="recalculate()" step="any" min="0" max="50"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pajak (%)</label>
                        <input type="number" x-model.number="params.tax_pct" @input.debounce.500ms="recalculate()" step="any" min="0" max="50"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">WACC (%)</label>
                        <input type="number" x-model.number="params.wacc_pct" @input.debounce.500ms="recalculate()" step="any" min="0" max="50"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">RDM OTC (%)</label>
                        <input type="number" x-model.number="params.rdm_otc_pct" @input.debounce.500ms="recalculate()" step="0.01" min="0" max="100"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">RDM MRC (%)</label>
                        <input type="number" x-model.number="params.rdm_mrc_pct" @input.debounce.500ms="recalculate()" step="0.01" min="0" max="100"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 bg-white focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                    </div>
                </div>
            </div>

            {{-- Live Results Card --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs uppercase tracking-wider flex items-center gap-2 text-slate-800">
                        <i data-lucide="activity" class="w-4 h-4 text-slate-500"></i>
                        <span>Hasil Simulasi Real-Time</span>
                    </span>
                    <template x-if="loading">
                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-600 font-medium">
                            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                            <span>Menghitung ulang...</span>
                        </span>
                    </template>
                    <template x-if="!loading && result.layak !== null">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold"
                              :class="result.layak ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
                            <i :data-lucide="result.layak ? 'check-circle-2' : 'x-circle'" class="w-3.5 h-3.5"></i>
                            <span x-text="result.layak ? 'LAYAK INVESTASI' : 'TIDAK LAYAK'"></span>
                        </span>
                    </template>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                    <div class="bg-white rounded-lg p-3.5 border border-slate-200 text-center shadow-xs">
                        <span class="text-slate-500 block text-[11px] font-medium uppercase tracking-wider">NPV</span>
                        <span class="font-bold text-sm block mt-1 font-mono" :class="(result.npv || 0) >= 0 ? 'text-emerald-700' : 'text-rose-700'" x-text="'Rp ' + formatRp(result.npv || 0)"></span>
                    </div>
                    <div class="bg-white rounded-lg p-3.5 border border-slate-200 text-center shadow-xs">
                        <span class="text-slate-500 block text-[11px] font-medium uppercase tracking-wider">IRR / MIRR</span>
                        <span class="font-bold text-sm block mt-1 font-mono text-slate-900" x-text="((result.irr || 0) * 100).toFixed(2) + '%'"></span>
                    </div>
                    <div class="bg-white rounded-lg p-3.5 border border-slate-200 text-center shadow-xs">
                        <span class="text-slate-500 block text-[11px] font-medium uppercase tracking-wider">PBP</span>
                        <span class="font-bold text-sm block mt-1 font-mono text-slate-900" x-text="(result.pbp || 0).toFixed(2) + ' Thn'"></span>
                    </div>
                    <div class="bg-white rounded-lg p-3.5 border border-slate-200 text-center shadow-xs">
                        <span class="text-slate-500 block text-[11px] font-medium uppercase tracking-wider">GPM</span>
                        <span class="font-bold text-sm block mt-1 font-mono" :class="(result.gpm || 0) >= 7 ? 'text-emerald-700' : 'text-rose-700'" x-text="(result.gpm || 0).toFixed(2) + '%'"></span>
                    </div>
                    <div class="bg-white rounded-lg p-3.5 border border-slate-200 text-center shadow-xs">
                        <span class="text-slate-500 block text-[11px] font-medium uppercase tracking-wider">NIM</span>
                        <span class="font-bold text-sm block mt-1 font-mono" :class="(result.nim || 0) >= 2 ? 'text-emerald-700' : 'text-rose-700'" x-text="(result.nim || 0).toFixed(2) + '%'"></span>
                    </div>
                </div>

                <template x-if="error">
                    <div class="bg-rose-50 text-rose-700 text-xs p-3 rounded-lg border border-rose-200" x-text="error"></div>
                </template>
            </div>

            {{-- Live Goal Seek Recommendations (shown when tidak layak) --}}
            <template x-if="!loading && result.layak === false && result.goal_seek && result.goal_seek.needed">
                <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-5 space-y-4">
                    <div class="flex items-start gap-3 border-b border-amber-200/80 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0 border border-amber-200">
                            <i data-lucide="compass" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Rekomendasi Penyesuaian (Goal Seek)</h3>
                            <p class="text-[11px] text-amber-800 mt-0.5">Batas parameter minimal agar memenuhi kriteria <strong>LAYAK INVESTASI</strong> (MIRR ≥ WACC+2%, NPV > 0, GPM ≥ 7%, NIM ≥ 2%):</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-if="result.goal_seek.min_mrc">
                            <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                                <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                                    <i data-lucide="trending-up" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Minimal Tarif Bulanan (MRC)</span>
                                </span>
                                <span class="text-sm font-bold font-mono text-slate-900 block" x-text="'Rp ' + formatRp(result.goal_seek.min_mrc)"></span>
                                <span class="text-[11px] text-amber-700" x-text="'Naik Rp ' + formatRp(result.goal_seek.delta_mrc) + ' dari MRC saat ini'"></span>
                            </div>
                        </template>
                        <template x-if="result.goal_seek.min_otc">
                            <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                                <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                                    <i data-lucide="coins" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Minimal Biaya Pasang (OTC)</span>
                                </span>
                                <span class="text-sm font-bold font-mono text-slate-900 block" x-text="'Rp ' + formatRp(result.goal_seek.min_otc)"></span>
                                <span class="text-[11px] text-amber-700" x-text="'Naik Rp ' + formatRp(result.goal_seek.delta_otc) + ' dari OTC saat ini'"></span>
                            </div>
                        </template>
                        <template x-if="result.goal_seek.max_capex">
                            <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                                <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                                    <i data-lucide="scissors" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Target Efisiensi CAPEX</span>
                                </span>
                                <span class="text-sm font-bold font-mono text-slate-900 block" x-text="'Rp ' + formatRp(result.goal_seek.max_capex)"></span>
                                <span class="text-[11px] text-amber-700" x-text="'Pangkas Rp ' + formatRp(result.goal_seek.delta_capex) + ' dari CAPEX saat ini'"></span>
                            </div>
                        </template>
                        <template x-if="result.goal_seek.min_durasi">
                            <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                                <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Tambahan Durasi Kontrak</span>
                                </span>
                                <span class="text-sm font-bold font-mono text-slate-900 block" x-text="result.goal_seek.min_durasi + ' Bulan'"></span>
                                <span class="text-[11px] text-amber-700" x-text="'Perpanjang +' + result.goal_seek.delta_durasi + ' bulan dari durasi saat ini'"></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-2 text-[11px] text-amber-800 border-t border-amber-200/80 pt-2.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                        <p>Setiap skenario dihitung secara independen. Penyesuaian salah satu parameter sudah mencukupi untuk mencapai batas kelayakan.</p>
                    </div>
                </div>
            </template>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-2" x-data="{ showReject: false }">
                <form id="rso-approve-form" method="POST" action="{{ route('simulasi.approve', $simulasi) }}" class="flex-1">
                    @csrf
                    <input type="hidden" name="bop_pct" :value="params.bop_pct">
                    <input type="hidden" name="lifetime" :value="params.lifetime">
                    <input type="hidden" name="om_pct" :value="params.om_pct">
                    <input type="hidden" name="tax_pct" :value="params.tax_pct">
                    <input type="hidden" name="wacc_pct" :value="params.wacc_pct">
                    <input type="hidden" name="rdm_otc_pct" :value="params.rdm_otc_pct">
                    <input type="hidden" name="rdm_mrc_pct" :value="params.rdm_mrc_pct">
                    <button type="submit" :disabled="loading" class="w-full h-11 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium px-5 rounded-lg text-sm transition-colors shadow-sm flex items-center justify-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                        <span>Setujui dengan Parameter Ini & Cetak</span>
                    </button>
                </form>

                <button type="button" 
                        @click="openExcelPreview()" 
                        :disabled="previewLoading" 
                        class="h-11 bg-white hover:bg-slate-50 text-slate-700 disabled:opacity-50 font-medium px-5 rounded-lg text-sm border border-slate-300 transition-colors shadow-sm flex items-center justify-center gap-2">
                    <template x-if="!previewLoading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4 text-emerald-600"></i>
                            <span>Preview Format Excel</span>
                        </span>
                    </template>
                    <template x-if="previewLoading">
                        <span class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-slate-500"></i>
                            <span>Memuat Preview...</span>
                        </span>
                    </template>
                </button>

                <div class="space-y-3">
                    <button @click="showReject = !showReject" class="w-full sm:w-auto h-11 bg-white hover:bg-rose-50 text-rose-700 font-medium px-4 rounded-lg text-sm border border-rose-200 transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>Revisi ke Marketing</span>
                    </button>
                    <div x-show="showReject" x-transition class="bg-white rounded-xl border border-rose-200 p-4 shadow-sm space-y-3">
                        {{-- Auto-fill hint from goal seek --}}
                        <template x-if="result.layak === false && result.goal_seek && result.goal_seek.needed">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900 space-y-1">
                                <span class="font-bold flex items-center gap-1.5">
                                    <i data-lucide="lightbulb" class="w-3.5 h-3.5 text-amber-600"></i>
                                    <span>Saran revisi berdasarkan Goal Seek:</span>
                                </span>
                                <ul class="list-disc list-inside text-[11px] text-amber-800 space-y-0.5 pl-1">
                                    <template x-if="result.goal_seek.min_mrc">
                                        <li x-text="'Naikkan MRC minimal ke Rp ' + formatRp(result.goal_seek.min_mrc) + ' (+Rp ' + formatRp(result.goal_seek.delta_mrc) + ')'"></li>
                                    </template>
                                    <template x-if="result.goal_seek.min_otc">
                                        <li x-text="'Naikkan OTC minimal ke Rp ' + formatRp(result.goal_seek.min_otc) + ' (+Rp ' + formatRp(result.goal_seek.delta_otc) + ')'"></li>
                                    </template>
                                    <template x-if="result.goal_seek.min_durasi">
                                        <li x-text="'Perpanjang durasi kontrak minimal ke ' + result.goal_seek.min_durasi + ' bulan (+' + result.goal_seek.delta_durasi + ' bulan)'"></li>
                                    </template>
                                    <template x-if="result.goal_seek.max_capex">
                                        <li x-text="'Efisiensi CAPEX ke Rp ' + formatRp(result.goal_seek.max_capex) + ' (pangkas Rp ' + formatRp(result.goal_seek.delta_capex) + ')'"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <form method="POST" action="{{ route('simulasi.reject', $simulasi) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="target_revisi" value="marketing">
                            <label class="block text-xs font-semibold text-rose-900">Catatan Revisi untuk Marketing <span class="text-rose-500">*</span></label>
                            <textarea name="catatan_revisi" required rows="3" placeholder="Jelaskan penyesuaian yang diperlukan (OTC, MRC, Durasi Kontrak, dsb)..."
                                      class="w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none"></textarea>
                            <button type="submit" class="h-9 bg-red-600 hover:bg-red-700 text-white font-medium px-4 rounded-lg text-xs transition-colors shadow-sm inline-flex items-center gap-1.5">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                Kirim Revisi ke Marketing
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ================= FORM ROLE MARKETING / REVISI / ED ================= --}}

        {{-- 1. JIKA STATUS REVISI: Khusus Role Marketing yang melihat form revisi komersial --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_REVISI && auth()->user()->hasRole('sales_marketing'))
        @php
            $rdmMaster = \App\Models\ParameterRdm::getActive();
            $inp = $simulasi->input_json ?? [];
        @endphp

        {{-- Goal Seek Recommendations for Marketing (from previous calculation) --}}
        @if($simulasi->hasil_json && isset($simulasi->hasil_json['goal_seek']) && $simulasi->hasil_json['goal_seek']['needed'])
        @php $gs = $simulasi->hasil_json['goal_seek']; @endphp
        <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-5 space-y-3">
            <div class="flex items-start gap-3 border-b border-amber-200/80 pb-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0 border border-amber-200">
                    <i data-lucide="compass" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Rekomendasi Penyesuaian (Goal Seek)</h3>
                    <p class="text-[11px] text-amber-800 mt-0.5">Batas parameter minimal agar simulasi memenuhi kriteria <strong>LAYAK INVESTASI</strong>:</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if(!empty($gs['min_mrc']))
                <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Minimal Tarif Bulanan (MRC)</span>
                    </span>
                    <span class="text-sm font-bold font-mono text-slate-900 block">Rp {{ number_format($gs['min_mrc'], 0, ',', '.') }}</span>
                    <span class="text-[11px] text-amber-700">Naik <strong>Rp {{ number_format($gs['delta_mrc'], 0, ',', '.') }}</strong> dari MRC sebelumnya</span>
                </div>
                @endif
                @if(!empty($gs['min_otc']))
                <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="coins" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Minimal Biaya Pasang (OTC)</span>
                    </span>
                    <span class="text-sm font-bold font-mono text-slate-900 block">Rp {{ number_format($gs['min_otc'], 0, ',', '.') }}</span>
                    <span class="text-[11px] text-amber-700">Naik <strong>Rp {{ number_format($gs['delta_otc'], 0, ',', '.') }}</strong> dari OTC sebelumnya</span>
                </div>
                @endif
                @if(!empty($gs['min_durasi']))
                <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Tambahan Durasi Kontrak</span>
                    </span>
                    <span class="text-sm font-bold font-mono text-slate-900 block">{{ $gs['min_durasi'] }} Bulan</span>
                    <span class="text-[11px] text-amber-700">Perpanjang <strong>+{{ $gs['delta_durasi'] }} bulan</strong> dari durasi sebelumnya</span>
                </div>
                @endif
                @if(!empty($gs['max_capex']))
                <div class="bg-white rounded-lg p-3.5 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="scissors" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Target Efisiensi CAPEX</span>
                    </span>
                    <span class="text-sm font-bold font-mono text-slate-900 block">Rp {{ number_format($gs['max_capex'], 0, ',', '.') }}</span>
                    <span class="text-[11px] text-amber-700">Pangkas <strong>Rp {{ number_format($gs['delta_capex'], 0, ',', '.') }}</strong> dari CAPEX saat ini</span>
                </div>
                @endif
            </div>
            <div class="flex items-center gap-2 text-[11px] text-amber-800 border-t border-amber-200/80 pt-2.5">
                <i data-lucide="info" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                <p>Setiap skenario dihitung independen. Penyesuaian salah satu parameter sudah mencukupi untuk mencapai batas kelayakan.</p>
            </div>
        </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7 shadow-sm space-y-5" x-data="mktCompleteForm({{ $rdmMaster->rdm_otc_pct ?? 0 }}, {{ $rdmMaster->rdm_mrc_pct ?? 0 }})">
            <div class="border-b border-slate-200 pb-4">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Divisi Marketing</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-xs font-semibold text-amber-700">Form Revisi Parameter Komersial</span>
                </div>
                <h2 class="text-base font-bold text-slate-900">Revisi Parameter Komersial Proyek</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Silakan sesuaikan tarif OTC, tarif MRC, durasi kontrak, atau bandwidth berdasarkan catatan RSO dan rekomendasi Goal Seek di atas.
                </p>
            </div>

            <form method="POST" action="{{ route('simulasi.update', $simulasi) }}" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Paketisasi & Bandwidth --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Paketisasi Layanan <span class="text-red-500">*</span></label>
                        <input type="text" name="paketisasi" list="paketisasi-options-show" value="{{ old('paketisasi', $simulasi->paketisasi) }}" required
                               placeholder="Pilih atau ketik paket (Contoh: Bundling Managed Service)"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                        <datalist id="paketisasi-options-show">
                            <option value="Paket Connectivity Only">
                            <option value="Paket Bundling Managed Service">
                            <option value="Paket Enterprise Silver">
                            <option value="Paket Enterprise Gold">
                            <option value="Paket Enterprise Platinum">
                            <option value="Paket Custom Solusi">
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bandwidth / Kapasitas Layanan <span class="text-red-500">*</span></label>
                        <input type="text" name="bandwidth" list="bandwidth-options-show" value="{{ old('bandwidth', $simulasi->bandwidth) }}" required
                               placeholder="Contoh: 100 Mbps, 1 Gbps, 500 Mbps"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                        <datalist id="bandwidth-options-show">
                            <option value="10 Mbps">
                            <option value="20 Mbps">
                            <option value="50 Mbps">
                            <option value="100 Mbps">
                            <option value="200 Mbps">
                            <option value="500 Mbps">
                            <option value="1 Gbps">
                            <option value="2 Gbps">
                            <option value="10 Gbps">
                        </datalist>
                    </div>
                </div>

                {{-- Durasi Kontrak & Bulan di Tahun 1 --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Durasi Kontrak (Bulan) <span class="text-red-500">*</span></label>
                        <input type="number" name="durasi_kontrak" value="{{ old('durasi_kontrak', $simulasi->input_json['durasi_kontrak'] ?? 48) }}" required min="1" max="120"
                               placeholder="Contoh: 48"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                        <p class="text-xs text-slate-500 mt-1">Lama kontrak layanan dalam bulan.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bulan di Tahun 1 <span class="text-red-500">*</span></label>
                        <input type="number" name="bulan_tahun1" value="{{ old('bulan_tahun1', $simulasi->input_json['bulan_tahun1'] ?? 6) }}" required min="1" max="12"
                               placeholder="Contoh: 6"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                        <p class="text-xs text-slate-500 mt-1">Jumlah bulan operasional di tahun pertama.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif OTC (One-Time Charge) (Rp) <span class="text-red-500">*</span></label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'otc')" x-on:focus="selectAll($event)"
                               x-bind:value="display.otc" required
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                        <input type="hidden" name="otc" x-bind:value="raw.otc">
                        <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.otc)"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif MRC (Monthly Recurring) (Rp) <span class="text-red-500">*</span></label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'mrc')" x-on:focus="selectAll($event)"
                               x-bind:value="display.mrc" required
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                        <input type="hidden" name="mrc" x-bind:value="raw.mrc">
                        <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.mrc)"></p>
                    </div>
                </div>

                <div class="pt-1 flex items-center gap-3">
                    <button type="submit" class="h-11 px-6 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim Revisi ke RSO untuk Review Ulang</span>
                    </button>
                    <a href="{{ route('simulasi.edit', $simulasi) }}" class="h-11 px-4 bg-white hover:bg-slate-50 text-slate-700 font-medium rounded-lg text-sm border border-slate-300 transition-colors shadow-xs inline-flex items-center justify-center gap-1.5">
                        <i data-lucide="external-link" class="w-4 h-4 text-slate-400"></i>
                        <span>Buka Halaman Revisi Lengkap</span>
                    </a>
                </div>
            </form>
        </div>
        @endif

        {{-- 2. JIKA STATUS MENUNGGU MARKETING (Pihak B): Hanya Marketing yang melihat form input --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_B)
            @if(auth()->user()->hasRole('sales_marketing'))
            @php $rdmMaster = \App\Models\ParameterRdm::getActive(); @endphp
            <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7 shadow-sm space-y-5" x-data="mktCompleteForm({{ $rdmMaster->rdm_otc_pct ?? 0 }}, {{ $rdmMaster->rdm_mrc_pct ?? 0 }})">
                <div class="border-b border-slate-200 pb-4">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Divisi Marketing</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-slate-500">Lengkapi Parameter Komersial</span>
                    </div>
                    <h2 class="text-base font-bold text-slate-900">Lengkapi Parameter Komersial Proyek</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Divisi Engineer Development (<strong>{{ $simulasi->pihakA?->name ?? 'ED' }}</strong>) telah memasukkan data estimasi biaya CAPEX. Silakan tentukan skema paketisasi, bandwidth, tarif OTC, dan tarif MRC.
                    </p>
                </div>

                <form method="POST" action="{{ route('simulasi.completeMarketing', $simulasi) }}" class="space-y-5">
                    @csrf
                    {{-- Paketisasi & Bandwidth --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Paketisasi Layanan <span class="text-red-500">*</span></label>
                            <input type="text" name="paketisasi" list="paketisasi-options-show" value="{{ old('paketisasi', $simulasi->paketisasi) }}" required
                                   placeholder="Pilih atau ketik paket (Contoh: Bundling Managed Service)"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <datalist id="paketisasi-options-show">
                                <option value="Paket Connectivity Only">
                                <option value="Paket Bundling Managed Service">
                                <option value="Paket Enterprise Silver">
                                <option value="Paket Enterprise Gold">
                                <option value="Paket Enterprise Platinum">
                                <option value="Paket Custom Solusi">
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bandwidth / Kapasitas Layanan <span class="text-red-500">*</span></label>
                            <input type="text" name="bandwidth" list="bandwidth-options-show" value="{{ old('bandwidth', $simulasi->bandwidth) }}" required
                                   placeholder="Contoh: 100 Mbps, 1 Gbps, 500 Mbps"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <datalist id="bandwidth-options-show">
                                <option value="10 Mbps">
                                <option value="20 Mbps">
                                <option value="50 Mbps">
                                <option value="100 Mbps">
                                <option value="200 Mbps">
                                <option value="500 Mbps">
                                <option value="1 Gbps">
                                <option value="2 Gbps">
                                <option value="10 Gbps">
                            </datalist>
                        </div>
                    </div>

                    {{-- Durasi Kontrak & Bulan di Tahun 1 --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Durasi Kontrak (Bulan) <span class="text-red-500">*</span></label>
                            <input type="number" name="durasi_kontrak" value="{{ old('durasi_kontrak', $simulasi->input_json['durasi_kontrak'] ?? 48) }}" required min="1" max="120"
                                   placeholder="Contoh: 48"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <p class="text-xs text-slate-500 mt-1">Lama kontrak layanan dalam bulan.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bulan di Tahun 1 <span class="text-red-500">*</span></label>
                            <input type="number" name="bulan_tahun1" value="{{ old('bulan_tahun1', $simulasi->input_json['bulan_tahun1'] ?? 6) }}" required min="1" max="12"
                                   placeholder="Contoh: 6"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <p class="text-xs text-slate-500 mt-1">Jumlah bulan operasional di tahun pertama.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif OTC (One-Time Charge) (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'otc')" x-on:focus="selectAll($event)"
                                   x-bind:value="display.otc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <input type="hidden" name="otc" x-bind:value="raw.otc">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.otc)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif MRC (Monthly Recurring) (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'mrc')" x-on:focus="selectAll($event)"
                                   x-bind:value="display.mrc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <input type="hidden" name="mrc" x-bind:value="raw.mrc">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.mrc)"></p>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="w-full sm:w-auto h-11 px-6 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                            <span>Proses & Hitung Kelayakan AKI</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>
            @else
            {{-- Jika RSO atau ED melihat status MENUNGGU_PIHAK_B: Tampilkan pesan informatif read-only, bukan form --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-xs text-slate-600 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-slate-200/80 flex items-center justify-center text-slate-600 shrink-0">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-800">Menunggu Input Komersial dari Divisi Marketing</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        @if(auth()->user()->hasRole('admin'))
                            Divisi Engineer Development telah melengkapi data proyek & CAPEX. Saat ini usulan sedang menunggu Divisi Marketing untuk menentukan paketisasi, bandwidth, dan tarif OTC/MRC.
                        @else
                            Data CAPEX fisik yang Anda input telah disimpan. Saat ini menunggu Divisi Marketing untuk melengkapi skema paket dan tarif komersial.
                        @endif
                    </p>
                </div>
            </div>
            @endif
        @endif

        {{-- 3. JIKA STATUS MENUNGGU CAPEX (Pihak A): Hanya ED yang melihat form input --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_A)
            @if(auth()->user()->hasRole('engineering_capex'))
            <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-7 shadow-sm space-y-5" x-data="edCompleteForm()">
                <div class="border-b border-slate-200 pb-4">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">Engineer Development (ED)</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-slate-500">Lengkapi Data Proyek & CAPEX</span>
                    </div>
                    <h2 class="text-base font-bold text-slate-900">Lengkapi Parameter Proyek dan Biaya CAPEX</h2>
                    <p class="text-xs text-slate-500 mt-1">Divisi Marketing telah mengisi tarif komersial dan paketisasi. Silakan masukkan lokasi serta rincian biaya material dan jasa.</p>
                </div>

                <form method="POST" action="{{ route('simulasi.complete', $simulasi) }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi Proyek</label>
                        <input type="text" name="lokasi" value="{{ old('lokasi', $simulasi->lokasi) }}" placeholder="Contoh: Witel Jakarta Barat"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Material (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'material')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'material')"
                                   x-bind:value="display.material" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <input type="hidden" name="material" x-bind:value="raw.material">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.material)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Jasa (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'jasa')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'jasa')"
                                   x-bind:value="display.jasa" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <input type="hidden" name="jasa" x-bind:value="raw.jasa">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.jasa)"></p>
                        </div>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700 uppercase tracking-wider">Total Base CAPEX (Material + Jasa)</span>
                        <span class="font-bold text-sm font-mono text-slate-900" x-text="'Rp ' + formatRp((Number(raw.material) || 0) + (Number(raw.jasa) || 0))"></span>
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="w-full sm:w-auto h-11 px-6 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                            <span>Proses & Hitung Kelayakan AKI</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>
            @else
            {{-- Jika RSO atau Marketing melihat status MENUNGGU_PIHAK_A: Pesan informatif read-only --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 text-xs text-slate-600 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-slate-200/80 flex items-center justify-center text-slate-600 shrink-0">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-800">Menunggu Input CAPEX dari Engineer Development</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        @if(auth()->user()->hasRole('admin'))
                            Divisi Marketing telah mengisi parameter komersial. Saat ini usulan sedang menunggu divisi Engineer Development untuk melengkapi lokasi proyek dan estimasi biaya material serta jasa.
                        @else
                            Data komersial yang Anda input telah disimpan. Saat ini menunggu divisi Engineer Development untuk melengkapi data CAPEX (Material & Jasa).
                        @endif
                    </p>
                </div>
            </div>
            @endif
        @endif

        {{-- Financial Summary Indicators --}}
        @if($simulasi->hasil_json)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">Net Present Value (NPV)</span>
                <span class="text-2xl font-bold font-mono mt-1.5 block" :class="({{ $simulasi->npv ?? 0 }}) >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                    Rp {{ number_format($simulasi->npv ?? 0, 0, ',', '.') }}
                </span>
                <p class="text-xs text-slate-500 mt-1">Nilai tambah ekonomi saat ini</p>
            </div>
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">IRR / MIRR</span>
                <span class="text-2xl font-bold font-mono text-slate-900 mt-1.5 block">
                    {{ number_format(($simulasi->mirr ?? 0) * 100, 2) }}%
                </span>
                <p class="text-xs text-slate-500 mt-1">Tingkat pengembalian investasi</p>
            </div>
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">Payback Period (PBP)</span>
                <span class="text-2xl font-bold font-mono text-slate-900 mt-1.5 block">
                    {{ number_format($simulasi->payback_period ?? 0, 2) }} Tahun
                </span>
                <p class="text-xs text-slate-500 mt-1">Waktu pemulihan modal belanja</p>
            </div>
        </div>

        {{-- Additional KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">Total Revenue</span>
                <span class="text-sm font-bold font-mono text-slate-900 mt-1 block">Rp {{ number_format($simulasi->hasil_json['total_revenue'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">GPM</span>
                <span class="text-sm font-bold font-mono mt-1 block" :class="({{ $simulasi->hasil_json['gpm'] ?? 0 }}) >= 7 ? 'text-emerald-700' : 'text-rose-700'">
                    {{ number_format($simulasi->hasil_json['gpm'] ?? 0, 2) }}%
                </span>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">Net Income</span>
                <span class="text-sm font-bold font-mono text-slate-900 mt-1 block">Rp {{ number_format($simulasi->hasil_json['total_net_income'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
                <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider block">NIM</span>
                <span class="text-sm font-bold font-mono mt-1 block" :class="({{ $simulasi->hasil_json['nim'] ?? 0 }}) >= 2 ? 'text-emerald-700' : 'text-rose-700'">
                    {{ number_format($simulasi->hasil_json['nim'] ?? 0, 2) }}%
                </span>
            </div>
        </div>
        @endif

        {{-- ===== GOAL SEEK: Rekomendasi "Kurang Berapa Lagi" (Hanya saat sudah review/approved) ===== --}}
        @if($simulasi->hasil_json && $simulasi->status === \App\Models\SimulasiAki::STATUS_APPROVED && $simulasi->layak === false && isset($simulasi->hasil_json['goal_seek']) && $simulasi->hasil_json['goal_seek']['needed'])
        @php $gs = $simulasi->hasil_json['goal_seek']; @endphp
        <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-6 shadow-sm space-y-4">
            <div class="flex items-start gap-3.5 border-b border-amber-200/80 pb-3.5">
                <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0 border border-amber-200">
                    <i data-lucide="compass" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-amber-950 uppercase tracking-wider">Rekomendasi Penyesuaian (Goal Seek)</h2>
                    <p class="text-xs text-amber-800 mt-0.5">Berikut batas parameter minimal agar simulasi memenuhi kriteria <strong>LAYAK INVESTASI</strong> (MIRR ≥ WACC+2%, NPV > 0, GPM ≥ 7%, NIM ≥ 2%):</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {{-- a) Minimal MRC --}}
                @if($gs['min_mrc'])
                <div class="bg-white rounded-lg p-4 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Minimal Tarif Bulanan (MRC)</span>
                    </span>
                    <span class="text-base font-bold font-mono text-slate-900 block">Rp {{ number_format($gs['min_mrc'], 0, ',', '.') }}</span>
                    <span class="text-xs text-amber-700">Naik <strong>Rp {{ number_format($gs['delta_mrc'], 0, ',', '.') }}</strong> dari MRC saat ini</span>
                </div>
                @endif

                {{-- b) Minimal OTC --}}
                @if($gs['min_otc'])
                <div class="bg-white rounded-lg p-4 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="coins" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Minimal Biaya Pasang (OTC)</span>
                    </span>
                    <span class="text-base font-bold font-mono text-slate-900 block">Rp {{ number_format($gs['min_otc'], 0, ',', '.') }}</span>
                    <span class="text-xs text-amber-700">Naik <strong>Rp {{ number_format($gs['delta_otc'], 0, ',', '.') }}</strong> dari OTC saat ini</span>
                </div>
                @endif

                {{-- c) Target Efisiensi CAPEX --}}
                @if($gs['max_capex'])
                <div class="bg-white rounded-lg p-4 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="scissors" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Target Efisiensi CAPEX</span>
                    </span>
                    <span class="text-base font-bold font-mono text-slate-900 block">Rp {{ number_format($gs['max_capex'], 0, ',', '.') }}</span>
                    <span class="text-xs text-amber-700">Pangkas <strong>Rp {{ number_format($gs['delta_capex'], 0, ',', '.') }}</strong> dari CAPEX saat ini</span>
                </div>
                @endif

                {{-- d) Tambahan Durasi Kontrak --}}
                @if($gs['min_durasi'])
                <div class="bg-white rounded-lg p-4 border border-amber-200 space-y-1 shadow-xs">
                    <span class="text-xs font-semibold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-600"></i>
                        <span>Tambahan Durasi Kontrak</span>
                    </span>
                    <span class="text-base font-bold font-mono text-slate-900 block">{{ $gs['min_durasi'] }} Bulan</span>
                    <span class="text-xs text-amber-700">Perpanjang <strong>+{{ $gs['delta_durasi'] }} bulan</strong> dari durasi saat ini</span>
                </div>
                @endif
            </div>

            <div class="flex items-center gap-2 text-xs text-amber-800 border-t border-amber-200/80 pt-3">
                <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0"></i>
                <p>Setiap skenario di atas dihitung secara independen. Penyesuaian salah satu parameter sudah mencukupi untuk mencapai batas kelayakan.</p>
            </div>
        </div>
        @endif



        {{-- ================= EXCEL PREVIEW MODAL (ADMIN ONLY) ================= --}}
        @if($user->hasRole('admin'))
        <div x-show="previewOpen" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-excel-preview" 
             role="dialog" 
             aria-modal="true">
            
            {{-- Backdrop --}}
            <div x-show="previewOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"
                 @click="previewOpen = false"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto p-2 sm:p-4 md:p-6 flex min-h-full items-center justify-center">
                <div x-show="previewOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-7xl bg-white rounded-xl shadow-2xl overflow-hidden border border-slate-300 flex flex-col max-h-[92vh]"
                     @click.stop>
                    
                    {{-- Modal Header: Microsoft Excel Corporate Green Bar --}}
                    <div class="bg-emerald-800 text-white px-6 py-3.5 flex items-center justify-between shadow-sm shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center shrink-0 border border-white/20 text-white">
                                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-sm sm:text-base text-white truncate">
                                        Preview Dokumen Excel: {{ $simulasi->nama_proyek }}
                                    </h3>
                                    <span class="hidden sm:inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-950/60 text-emerald-200 border border-emerald-500/40">
                                        Format Resmi: 6. Resume
                                    </span>
                                </div>
                                <p class="text-xs text-emerald-100/80 truncate mt-0.5">
                                    Format hasil cetak kelayakan investasi Telkom AKI/CAPEX • Periksa angka sebelum disetujui & dicetak
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Download Button from Preview --}}
                            <button type="button" 
                                    @click="downloadPreviewExcel()" 
                                    class="inline-flex items-center gap-1.5 bg-white text-emerald-800 hover:bg-emerald-50 text-xs font-semibold px-3.5 h-8 rounded-lg transition-colors shadow-xs">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">Unduh Excel (.xlsx)</span>
                            </button>

                            {{-- Close Button --}}
                            <button type="button" 
                                    @click="previewOpen = false" 
                                    class="text-emerald-100 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- View Mode Selector Bar --}}
                    <div class="bg-slate-100 border-b border-slate-200 px-6 py-1.5 flex items-center justify-between shrink-0 select-none">
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="previewViewTab = 'template'"
                                    :class="previewViewTab === 'template' 
                                        ? 'bg-white text-emerald-800 font-bold border-b-2 border-emerald-600 shadow-xs' 
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200 font-medium'"
                                    class="px-3.5 py-1.5 text-xs rounded-t-lg transition-all flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="previewViewTab === 'template' ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-700"></i>
                                    <span>Template Resume AKI (Format Cetak Excel)</span>
                                </span>
                            </button>

                            <button type="button"
                                    @click="previewViewTab = 'raw'"
                                    :class="previewViewTab === 'raw' 
                                        ? 'bg-white text-emerald-800 font-bold border-b-2 border-emerald-600 shadow-xs' 
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200 font-medium'"
                                    class="px-3.5 py-1.5 text-xs rounded-t-lg transition-all flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="previewViewTab === 'raw' ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="sheet" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Lembar Kerja Mentah (.xlsx Multi-Sheet)</span>
                                </span>
                            </button>
                        </div>

                        <div class="text-[11px] text-slate-500 hidden md:block">
                            WACC Proyek: <strong class="text-slate-800" x-text="(params.wacc_pct || 0).toFixed(2) + '%'"></strong>
                        </div>
                    </div>

                    {{-- ================= VIEW 1: TEMPLATE RESUME AKI (DEFAULT & RESMI) ================= --}}
                    <div x-show="previewViewTab === 'template'" class="flex-1 overflow-auto bg-slate-100 p-4 sm:p-6 space-y-4">
                        <div class="bg-white rounded-xl shadow border border-slate-300 overflow-hidden">
                            
                            {{-- Sheet Header: Metadata Proyek --}}
                            <div class="bg-slate-50 border-b border-slate-200 p-4 font-sans text-xs">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-4xl">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center">
                                            <span class="font-bold text-slate-500 w-36">Nama Project</span>
                                            <span class="font-semibold text-slate-800">: Pengadaan Layanan Internet</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="font-bold text-slate-500 w-36">Nama Pelanggan</span>
                                            <span class="font-bold text-emerald-800">: {{ $simulasi->nama_proyek }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="font-bold text-slate-500 w-36">Durasi Kontrak</span>
                                            <span class="font-bold text-slate-800">: <span x-text="Math.floor((params.durasi_kontrak || 0)/12) + ' Tahun ' + ((params.durasi_kontrak || 0)%12) + ' Bulan (' + params.durasi_kontrak + ' Bulan)'"></span></span>
                                        </div>
                                        @if($simulasi->lokasi)
                                        <div class="flex items-center">
                                            <span class="font-bold text-slate-500 w-36">Lokasi Proyek</span>
                                            <span class="font-semibold text-slate-700">: {{ $simulasi->lokasi }}</span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col justify-center sm:items-end space-y-1">
                                        <span class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider">Kesimpulan Kelayakan</span>
                                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-black shadow-sm"
                                             :class="result.layak ? 'bg-emerald-100 text-emerald-900 border border-emerald-400' : 'bg-red-100 text-red-900 border border-red-400'">
                                            <i :data-lucide="result.layak ? 'check-circle-2' : 'x-circle'" class="w-4 h-4"></i>
                                            <span x-text="result.layak ? 'LAYAK INVESTASI' : 'TIDAK LAYAK INVESTASI'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Top Metrics Row (Baris 6-7 di Template Excel Resume) --}}
                            <div class="p-4 bg-slate-50/60 border-b border-slate-200 overflow-x-auto">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <i data-lucide="target" class="w-3.5 h-3.5 text-slate-500"></i>
                                    <span>Ringkasan Parameter Utama (Baris 6 - 7 Excel Resume)</span>
                                </div>
                                <table class="w-full border-collapse border border-slate-300 text-xs bg-white font-sans shadow-sm">
                                    <thead>
                                        <tr class="bg-emerald-800 text-white font-bold text-center">
                                            <th class="border border-emerald-900 px-3 py-2 text-center">Revenue</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">DIR</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">Gross Profit</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">GPM</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">Net Income</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">NIM</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">NPV</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">MIRR</th>
                                            <th class="border border-emerald-900 px-3 py-2 text-center">Payback Period</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="text-right font-mono font-bold bg-white text-slate-800 hover:bg-emerald-50/30">
                                            <td class="border border-slate-300 px-3 py-2 text-slate-900" x-text="'Rp ' + formatRp(result.total_revenue)"></td>
                                            <td class="border border-slate-300 px-3 py-2 text-slate-800" x-text="'Rp ' + formatRp(result.total_direct_cost)"></td>
                                            <td class="border border-slate-300 px-3 py-2 text-emerald-800" x-text="'Rp ' + formatRp(result.total_gross_profit)"></td>
                                            <td class="border border-slate-300 px-3 py-2" :class="(result.gpm || 0) >= 7 ? 'text-emerald-700' : 'text-red-700'" x-text="formatPct(result.gpm)"></td>
                                            <td class="border border-slate-300 px-3 py-2 text-slate-900" x-text="'Rp ' + formatRp(result.total_net_income)"></td>
                                            <td class="border border-slate-300 px-3 py-2" :class="(result.nim || 0) >= 2 ? 'text-emerald-700' : 'text-red-700'" x-text="formatPct(result.nim)"></td>
                                            <td class="border border-slate-300 px-3 py-2" :class="(result.npv || 0) >= 0 ? 'text-emerald-700' : 'text-red-700'" x-text="'Rp ' + formatRp(result.npv)"></td>
                                            <td class="border border-slate-300 px-3 py-2 text-slate-900" x-text="formatPct(result.irr * 100)"></td>
                                            <td class="border border-slate-300 px-3 py-2 text-slate-900" x-text="formatDecimal(result.pbp) + ' Thn'"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Main Two-Column Tables (Side-by-Side like in Excel) --}}
                            <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                                
                                {{-- Left Table: Analisa Kelayakan Investasi (5 Tahun) --}}
                                <div class="lg:col-span-7 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                                        <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-600"></i>
                                            <span>Analisa Kelayakan Investasi (dalam Rp, kecuali angka persentase)</span>
                                        </h4>
                                        <span class="text-[10px] text-slate-400 font-mono">Row 9 - 20</span>
                                    </div>
                                    
                                    <div class="overflow-x-auto rounded-lg border border-slate-300 shadow-sm bg-white">
                                        <table class="w-full text-xs font-sans border-collapse">
                                            <thead>
                                                <tr class="bg-slate-700 text-white font-bold text-center">
                                                    <th class="border border-slate-600 px-3 py-2 text-left w-36">Akun</th>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <th class="border border-slate-600 px-2 py-2 min-w-[85px]" x-text="'Tahun ' + (idx + 1)"></th>
                                                    </template>
                                                    <th class="border border-slate-600 px-3 py-2 bg-slate-800 text-amber-300 min-w-[95px]">TOTAL</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200">
                                                {{-- Revenue --}}
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-700 border-r border-slate-200">Revenue</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono border-r border-slate-200" x-text="formatRp(row.revenue)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-50 text-slate-900" x-text="formatRp(result.total_revenue)"></td>
                                                </tr>
                                                {{-- Direct Cost --}}
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-700 border-r border-slate-200">Direct Cost</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono border-r border-slate-200" x-text="formatRp(row.direct_cost)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-50 text-slate-900" x-text="formatRp(result.total_direct_cost)"></td>
                                                </tr>
                                                {{-- Gross Margin --}}
                                                <tr class="bg-emerald-50/50 hover:bg-emerald-50">
                                                    <td class="px-3 py-1.5 font-bold text-emerald-900 border-r border-slate-200">Gross Margin</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono font-bold text-emerald-800 border-r border-slate-200" x-text="formatRp(row.gross_profit)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-emerald-100/70 text-emerald-900" x-text="formatRp(result.total_gross_profit)"></td>
                                                </tr>
                                                {{-- GP Margin (%) --}}
                                                <tr class="hover:bg-slate-50 bg-slate-50/30 italic">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-600 border-r border-slate-200">GP Margin</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono text-slate-600 border-r border-slate-200" x-text="row.revenue > 0 ? ((row.gross_profit / row.revenue) * 100).toFixed(2) + '%' : '0.00%'"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-100" :class="(result.gpm || 0) >= 7 ? 'text-emerald-700' : 'text-red-700'" x-text="formatPct(result.gpm)"></td>
                                                </tr>
                                                {{-- OPEX --}}
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-700 border-r border-slate-200">OPEX (O&M)</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono border-r border-slate-200" x-text="formatRp(row.opex)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-50 text-slate-900" x-text="formatRp(sumField('opex'))"></td>
                                                </tr>
                                                {{-- Depresiasi --}}
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-700 border-r border-slate-200">Depresiasi</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono border-r border-slate-200" x-text="formatRp(row.depresiasi)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-50 text-slate-900" x-text="formatRp(sumField('depresiasi'))"></td>
                                                </tr>
                                                {{-- Tax --}}
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-700 border-r border-slate-200">Tax</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono border-r border-slate-200" x-text="formatRp(row.pajak)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-50 text-slate-900" x-text="formatRp(sumField('pajak'))"></td>
                                                </tr>
                                                {{-- Net Income --}}
                                                <tr class="bg-indigo-50/50 hover:bg-indigo-50">
                                                    <td class="px-3 py-1.5 font-bold text-indigo-900 border-r border-slate-200">Net Income</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono font-bold text-indigo-800 border-r border-slate-200" x-text="formatRp(row.net_income)"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-indigo-100/70 text-indigo-900" x-text="formatRp(result.total_net_income)"></td>
                                                </tr>
                                                {{-- NI Margin (%) --}}
                                                <tr class="hover:bg-slate-50 bg-slate-50/30 italic">
                                                    <td class="px-3 py-1.5 font-semibold text-slate-600 border-r border-slate-200">NI Margin</td>
                                                    <template x-for="(row, idx) in (result.rows || [])" :key="idx">
                                                        <td class="px-2 py-1.5 text-right font-mono text-slate-600 border-r border-slate-200" x-text="row.revenue > 0 ? ((row.net_income / row.revenue) * 100).toFixed(2) + '%' : '0.00%'"></td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-slate-100" :class="(result.nim || 0) >= 2 ? 'text-emerald-700' : 'text-red-700'" x-text="formatPct(result.nim)"></td>
                                                </tr>
                                                {{-- CAPEX --}}
                                                <tr class="hover:bg-slate-50 font-bold bg-amber-50/40">
                                                    <td class="px-3 py-1.5 text-amber-900 border-r border-slate-200">CAPEX</td>
                                                    <td class="px-2 py-1.5 text-right font-mono text-amber-800 border-r border-slate-200" x-text="formatRp(result.grand_capex || (result.capex_base + (result.bop || 0)))"></td>
                                                    <template x-for="i in 4" :key="i">
                                                        <td class="px-2 py-1.5 text-right font-mono text-slate-400 border-r border-slate-200">0</td>
                                                    </template>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold bg-amber-100/60 text-amber-900" x-text="formatRp(result.grand_capex || (result.capex_base + (result.bop || 0)))"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Summary Bullets from Row 23-25 in Excel --}}
                                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-3 space-y-1 text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-700 w-24">• NPV =</span>
                                            <span class="font-extrabold font-mono" :class="(result.npv || 0) >= 0 ? 'text-emerald-700' : 'text-red-700'" x-text="'Rp ' + formatRp(result.npv)"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-700 w-24">• MIRR =</span>
                                            <span class="font-extrabold font-mono text-slate-900" x-text="formatPct(result.irr * 100)"></span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-700 w-24">• PP =</span>
                                            <span class="font-extrabold font-mono text-slate-900" x-text="formatDecimal(result.pbp) + ' Tahun'"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Right Table: Simulasi Perhitungan Usulan Capex Delivery --}}
                                <div class="lg:col-span-5 space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                                        <h4 class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                                            <i data-lucide="file-check-2" class="w-4 h-4 text-slate-600"></i>
                                            <span>Simulasi Usulan Capex Delivery</span>
                                        </h4>
                                        <span class="text-[10px] text-slate-400 font-mono">Row 10 - 25</span>
                                    </div>

                                    <div class="overflow-hidden rounded-lg border border-slate-300 shadow-sm bg-white">
                                        <div class="bg-slate-100 px-3 py-2 border-b border-slate-300 text-center font-bold text-xs text-slate-800">
                                            Simulasi Perhitungan Usulan Capex Delivery {{ $simulasi->nama_proyek }}
                                        </div>
                                        <table class="w-full text-xs font-sans border-collapse">
                                            <tbody class="divide-y divide-slate-200">
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-medium text-slate-600 bg-slate-50/60 w-40">Revenue</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-slate-800" x-text="'Rp ' + formatRp(result.total_revenue)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] text-slate-400">Total</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-medium text-slate-600 bg-slate-50/60">Direct Cost</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-slate-800" x-text="'Rp ' + formatRp(result.total_direct_cost)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] text-slate-400">Total</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-medium text-slate-600 bg-slate-50/60">Gross Profit</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-emerald-800" x-text="'Rp ' + formatRp(result.total_gross_profit)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] text-slate-400">Total</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-bold text-slate-700 bg-slate-50/60">GPM</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold" :class="(result.gpm || 0) >= 7 ? 'text-emerald-700' : 'text-red-700'" x-text="formatPct(result.gpm)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] font-semibold text-slate-500">Min 7%</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-medium text-slate-600 bg-slate-50/60">Capex (incl BoP Lakwas)</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-amber-800" x-text="'Rp ' + formatRp(result.grand_capex || (result.capex_base + (result.bop || 0)))"></td>
                                                    <td class="px-2 py-1.5 text-[10px] text-slate-400">Investasi</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-medium text-slate-600 bg-slate-50/60">Net Income</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-indigo-800" x-text="'Rp ' + formatRp(result.total_net_income)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] text-slate-400">Total</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-bold text-slate-700 bg-slate-50/60">NIM</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold" :class="(result.nim || 0) >= 2 ? 'text-emerald-700' : 'text-red-700'" x-text="formatPct(result.nim)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] font-semibold text-slate-500">Min 2%</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-bold text-slate-700 bg-slate-50/60">NPV</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold" :class="(result.npv || 0) >= 0 ? 'text-emerald-700' : 'text-red-700'" x-text="'Rp ' + formatRp(result.npv)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] font-semibold text-slate-500">> 0</td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-bold text-slate-700 bg-slate-50/60">IRR (MIRR)</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-bold text-slate-800" x-text="formatPct(result.irr * 100)"></td>
                                                    <td class="px-2 py-1.5 text-[10px] font-semibold text-slate-500" x-text="'Min ' + ((params.wacc_pct || 0) + 2).toFixed(2) + '%'"></td>
                                                </tr>
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-1.5 font-medium text-slate-600 bg-slate-50/60">Payback Period</td>
                                                    <td class="px-3 py-1.5 text-right font-mono font-semibold text-slate-800" x-text="formatDecimal(result.pbp) + ' Thn'"></td>
                                                    <td class="px-2 py-1.5 text-[10px] text-slate-400">PBP</td>
                                                </tr>
                                                <tr class="bg-slate-50 font-bold">
                                                    <td class="px-3 py-2 text-slate-800">Verdict Kelayakan</td>
                                                    <td colspan="2" class="px-3 py-2 text-right">
                                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-black"
                                                              :class="result.layak ? 'bg-emerald-200 text-emerald-900 border border-emerald-400' : 'bg-red-200 text-red-900 border border-red-400'"
                                                              x-text="result.layak ? 'LAYAK INVESTASI' : 'TIDAK LAYAK INVESTASI'">
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Yellow Callout Box from Excel Row 24-25 --}}
                                    <div class="bg-amber-100/70 border border-amber-300 rounded-xl p-3 text-xs text-amber-900 space-y-1 shadow-sm">
                                        <div class="font-semibold flex items-center gap-1.5">
                                            <i data-lucide="info" class="w-3.5 h-3.5 text-amber-700"></i>
                                            <span>WACC yang dipakai: <strong x-text="(params.wacc_pct || 0).toFixed(2) + '%'"></strong></span>
                                        </div>
                                        <div class="text-[11px] text-amber-800 pl-5">
                                            Syarat MIRR minimal = WACC + 2% = <strong x-text="((params.wacc_pct || 0) + 2).toFixed(2) + '%'"></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ================= VIEW 2: LEMBAR KERJA MENTAH (.XLSX MULTI-SHEET) ================= --}}
                    <div x-show="previewViewTab === 'raw'" class="flex-1 flex flex-col overflow-hidden bg-slate-50">
                        {{-- Sheet Tabs Bar --}}
                        <div class="bg-slate-200 border-b border-slate-300 px-4 pt-2.5 flex items-center justify-between gap-2 overflow-x-auto shrink-0 select-none">
                            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-thin">
                                <template x-for="name in sheetNames" :key="name">
                                    <button type="button"
                                            @click="activeSheet = name"
                                            :class="activeSheet === name 
                                                ? 'bg-white text-emerald-800 font-bold border-t-2 border-emerald-600 shadow-sm' 
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-300 font-medium'"
                                            class="px-3.5 py-1.5 text-xs rounded-t-lg transition-all whitespace-nowrap flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full" :class="activeSheet === name ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        <span x-text="name"></span>
                                        <span class="text-[10px] text-slate-400" x-text="'(' + (sheets[name] ? sheets[name].length : 0) + ' baris)'"></span>
                                    </button>
                                </template>
                            </div>

                            <div class="text-[11px] text-slate-500 font-medium whitespace-nowrap pb-2 hidden md:block">
                                Lembar aktif: <strong class="text-slate-800" x-text="activeSheet"></strong>
                            </div>
                        </div>

                        {{-- Spreadsheet Content Table Container --}}
                        <div class="flex-1 overflow-auto bg-slate-50 relative min-h-[350px] p-3">
                            <template x-if="sheets[activeSheet] && sheets[activeSheet].length > 0">
                                <div class="inline-block min-w-full align-middle bg-white shadow rounded-lg border border-slate-200 overflow-hidden">
                                    <table class="min-w-full border-collapse text-left font-sans text-xs">
                                        <thead class="bg-slate-100 sticky top-0 z-20 border-b border-slate-300">
                                            <tr>
                                                <th class="sticky left-0 z-30 bg-slate-200 text-slate-500 w-12 px-2 py-1.5 text-center font-mono text-[10px] border-r border-b border-slate-300 select-none">
                                                    #
                                                </th>
                                                <template x-for="(col, colIndex) in getColHeaders(sheets[activeSheet])" :key="colIndex">
                                                    <th class="px-3 py-1.5 text-slate-600 font-mono text-[11px] font-semibold border-r border-slate-300 text-center select-none bg-slate-100 min-w-[90px]"
                                                        x-text="getColLetter(colIndex)">
                                                    </th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            <template x-for="(row, rowIndex) in sheets[activeSheet]" :key="rowIndex">
                                                <tr class="hover:bg-amber-50/50 transition-colors" :class="isHeaderRow(row) ? 'bg-slate-50 font-bold' : ''">
                                                    <td class="sticky left-0 z-10 bg-slate-100 text-slate-500 px-2 py-1 text-center font-mono text-[10px] border-r border-slate-200 select-none font-semibold"
                                                        x-text="rowIndex + 1">
                                                    </td>
                                                    <template x-for="(cellVal, cellIndex) in padRow(row, getColHeaders(sheets[activeSheet]).length)" :key="cellIndex">
                                                        <td class="px-3 py-1.5 text-slate-800 border-r border-slate-200 text-xs whitespace-nowrap"
                                                            :class="getCellClass(cellVal)"
                                                            x-text="formatCell(cellVal)">
                                                        </td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                            <template x-if="!sheets[activeSheet] || sheets[activeSheet].length === 0">
                                <div class="h-64 flex flex-col items-center justify-center text-slate-400 space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                        <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                                    </div>
                                    <p class="text-sm">Lembar kerja ini kosong atau tidak memiliki data tabel.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-slate-50 border-t border-slate-200 px-6 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shrink-0">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Preview format dokumen Excel siap cetak sebelum disetujui RSO.</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    @click="previewOpen = false" 
                                    class="px-4 h-9 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors border border-slate-300 bg-white">
                                Tutup Preview
                            </button>

                            <button type="button" 
                                    @click="downloadPreviewExcel()" 
                                    class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold px-4 h-9 rounded-lg transition-colors border border-slate-300 shadow-xs">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Unduh .xlsx</span>
                            </button>

                            @if(in_array($simulasi->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
                            <button type="button" 
                                    @click="submitApproveFromModal()" 
                                    class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 h-9 rounded-lg transition-colors shadow-xs">
                                <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                                <span>Sudah Sesuai & Setujui</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <script>
    function mktCompleteForm(rdmOtcPct, rdmMrcPct) {
        const initRaw = {
            otc: {{ old('otc', $simulasi->input_json['otc'] ?? 0) }},
            mrc: {{ old('mrc', $simulasi->input_json['mrc'] ?? 0) }},
        };

        return {
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, v === 0 ? '0' : Number(v).toLocaleString('id-ID')])
            ),
            rdmOtcPct: parseFloat(rdmOtcPct) || 0,
            rdmMrcPct: parseFloat(rdmMrcPct) || 0,
            get cogsOtc() {
                return (this.raw.otc || 0) * (1 - (this.rdmOtcPct / 100));
            },
            get cogsMrc() {
                return (this.raw.mrc || 0) * (1 - (this.rdmMrcPct / 100));
            },
            handleCurrency(event, field) {
                let digits = event.target.value.replace(/\D/g, '');
                let num = parseInt(digits, 10) || 0;
                this.raw[field] = num;
                this.display[field] = num.toLocaleString('id-ID');
                this.$nextTick(() => {
                    event.target.value = this.display[field];
                });
            },
            selectAll(event) {
                event.target.select();
            },
            formatRp(value) {
                let num = parseFloat(value) || 0;
                return num.toLocaleString('id-ID');
            }
        };
    }

    function edCompleteForm() {
        const initRaw = {
            material: {{ old('material', 0) }},
            jasa: {{ old('jasa', 0) }},
        };

        return {
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, v === 0 ? '0' : Number(v).toLocaleString('id-ID')])
            ),
            get totalCapexBase() {
                return (Number(this.raw.material) || 0) + (Number(this.raw.jasa) || 0);
            },
            handleCurrency(event, field) {
                let digits = event.target.value.replace(/\D/g, '');
                let num = parseInt(digits, 10) || 0;
                this.raw[field] = num;
                this.display[field] = digits === '' ? '' : num.toLocaleString('id-ID');
                this.$nextTick(() => {
                    event.target.value = this.display[field];
                });
            },
            handleBlur(event, field) {
                if (!this.display[field] || this.display[field] === '') {
                    this.display[field] = '0';
                    event.target.value = '0';
                }
            },
            selectAll(event) {
                event.target.select();
            },
            formatRp(value) {
                let num = parseFloat(value) || 0;
                return num.toLocaleString('id-ID');
            }
        };
    }

    function rsoReviewForm() {
        @php
            $rdmDefaults = \App\Models\ParameterRdm::getActive();
            $inp = $simulasi->input_json ?? [];
            $initialResult = $simulasi->hasil_json ?? [
                'rows' => [],
                'total_revenue' => 0,
                'total_direct_cost' => 0,
                'total_gross_profit' => 0,
                'gpm' => 0,
                'total_net_income' => 0,
                'nim' => 0,
                'npv' => $simulasi->npv ?? 0,
                'irr' => $simulasi->mirr ?? 0,
                'pbp' => $simulasi->payback_period ?? 0,
                'layak' => $simulasi->layak,
                'grand_capex' => 0,
            ];
        @endphp

        return {
            params: {
                durasi_kontrak: {{ $inp['durasi_kontrak'] ?? 48 }},
                bulan_tahun1: {{ $inp['bulan_tahun1'] ?? 6 }},
                bop_pct: {{ $inp['bop_pct'] ?? $rdmDefaults->bop_pct ?? 10 }},
                lifetime: {{ $inp['lifetime'] ?? $rdmDefaults->lifetime ?? 5 }},
                om_pct: {{ $inp['om_pct'] ?? $rdmDefaults->om_pct ?? 5 }},
                tax_pct: {{ $inp['tax_pct'] ?? $rdmDefaults->tax_pct ?? 11 }},
                wacc_pct: {{ $inp['wacc_pct'] ?? $rdmDefaults->wacc_pct ?? 12 }},
                rdm_otc_pct: {{ $inp['rdm_otc_pct'] ?? $rdmDefaults->rdm_otc_pct ?? 5 }},
                rdm_mrc_pct: {{ $inp['rdm_mrc_pct'] ?? $rdmDefaults->rdm_mrc_pct ?? 7.5 }},
            },
            result: @json($initialResult),
            loading: false,
            error: null,

            // Excel Preview State
            previewOpen: false,
            previewLoading: false,
            previewError: null,
            previewBlob: null,
            previewFilename: 'Preview_Resume_AKI_{{ addslashes($simulasi->nama_proyek) }}.xlsx',
            previewViewTab: 'template',
            sheets: {},
            sheetNames: [],
            activeSheet: '6. Resume',

            async recalculate() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch('{{ route("simulasi.recalculate", $simulasi) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.params),
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.error || err.message || 'Gagal menghitung.');
                    }
                    this.result = await res.json();
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },

            async openExcelPreview() {
                this.previewLoading = true;
                this.previewError = null;
                try {
                    // Pastikan hasil perhitungan terbaru sudah tersedia
                    if (!this.result || !this.result.rows || this.result.rows.length === 0) {
                        await this.recalculate();
                    }

                    // Ambil file binary excel untuk opsi unduh & raw preview
                    const res = await fetch('{{ route("simulasi.previewExcel", $simulasi) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json',
                        },
                        body: JSON.stringify(this.params),
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        this.previewBlob = blob;

                        if (typeof XLSX !== 'undefined') {
                            const arrayBuffer = await blob.arrayBuffer();
                            const workbook = XLSX.read(arrayBuffer, { type: 'array' });
                            this.sheetNames = workbook.SheetNames || [];
                            this.sheets = {};

                            this.sheetNames.forEach(name => {
                                const worksheet = workbook.Sheets[name];
                                let rawData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
                                while (rawData.length > 0) {
                                    const lastRow = rawData[rawData.length - 1];
                                    const isEmpty = !lastRow || lastRow.every(cell => cell === '' || cell === null || cell === undefined);
                                    if (isEmpty) {
                                        rawData.pop();
                                    } else {
                                        break;
                                    }
                                }
                                this.sheets[name] = rawData;
                            });

                            if (this.sheetNames.includes('6. Resume')) {
                                this.activeSheet = '6. Resume';
                            } else if (this.sheetNames.length > 0) {
                                this.activeSheet = this.sheetNames[0];
                            }
                        }
                    }

                    this.previewViewTab = 'template';
                    this.previewOpen = true;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } catch (e) {
                    this.previewError = e.message;
                    alert('Gagal memuat preview Excel: ' + e.message);
                } finally {
                    this.previewLoading = false;
                }
            },

            downloadPreviewExcel() {
                if (!this.previewBlob) return;
                const url = window.URL.createObjectURL(this.previewBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = this.previewFilename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => window.URL.revokeObjectURL(url), 1000);
            },

            submitApproveFromModal() {
                const form = document.getElementById('rso-approve-form');
                if (form) {
                    form.submit();
                } else {
                    this.previewOpen = false;
                }
            },

            sumField(field) {
                if (!this.result || !this.result.rows || !Array.isArray(this.result.rows)) return 0;
                return this.result.rows.reduce((acc, r) => acc + (parseFloat(r[field]) || 0), 0);
            },

            formatPct(val) {
                if (val === null || val === undefined || isNaN(val)) return '0.00%';
                let num = parseFloat(val);
                return num.toFixed(2) + '%';
            },

            formatDecimal(val) {
                if (val === null || val === undefined || isNaN(val)) return '0.00';
                let num = parseFloat(val);
                return num.toFixed(2);
            },

            getColHeaders(grid) {
                if (!grid || grid.length === 0) return [];
                let maxCols = 0;
                grid.forEach(row => {
                    if (row && row.length > maxCols) {
                        maxCols = row.length;
                    }
                });
                return Array.from({ length: Math.max(maxCols, 1) });
            },

            getColLetter(colIndex) {
                let temp = colIndex;
                let letter = '';
                while (temp >= 0) {
                    letter = String.fromCharCode((temp % 26) + 65) + letter;
                    temp = Math.floor(temp / 26) - 1;
                }
                return letter;
            },

            padRow(row, targetLen) {
                const r = Array.isArray(row) ? [...row] : [];
                while (r.length < targetLen) {
                    r.push('');
                }
                return r;
            },

            formatCell(val) {
                if (val === null || val === undefined || val === '') return '';
                if (typeof val === 'number') {
                    if (Number.isInteger(val)) {
                        return val.toLocaleString('id-ID');
                    }
                    if (Math.abs(val) > 0 && Math.abs(val) < 1) {
                        return (val * 100).toFixed(2) + '%';
                    }
                    return val.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                return String(val);
            },

            getCellClass(val) {
                if (val === null || val === undefined || val === '') return 'text-center text-slate-300';
                if (typeof val === 'number') {
                    return 'text-right font-mono text-slate-800 font-medium';
                }
                const s = String(val).trim().toUpperCase();
                if (s === 'LAYAK' || s === 'YA' || s === 'LAYAK INVESTASI') {
                    return 'text-emerald-700 font-bold bg-emerald-50 text-center';
                }
                if (s === 'TIDAK LAYAK' || s === 'TIDAK') {
                    return 'text-red-700 font-bold bg-red-50 text-center';
                }
                return 'text-left font-sans text-slate-800';
            },

            isHeaderRow(row) {
                if (!row || !Array.isArray(row)) return false;
                const str = row.join(' ').toLowerCase();
                return str.includes('tahun 1') || 
                       str.includes('analisa kelayakan') || 
                       str.includes('gross profit') || 
                       str.includes('simulasi perhitungan');
            },

            formatRp(value) {
                let num = parseFloat(value) || 0;
                return num.toLocaleString('id-ID');
            }
        };
    }
    </script>
    <script src="{{ asset('vendor/sheetjs/xlsx.full.min.js') }}"></script>
</x-layouts.app>

