<x-layouts.app title="Detail Simulasi">
    <div class="space-y-6 max-w-3xl mx-auto">
        {{-- Top Bar --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('simulasi.riwayat') }}" class="text-xs font-semibold text-telkom-gray-500 hover:text-telkom-800 flex items-center gap-1">
                &larr; Kembali ke Riwayat
            </a>
            @if($simulasi->status === \App\Models\SimulasiAki::STATUS_APPROVED)
            <a href="{{ route('simulasi.download', $simulasi) }}" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all shadow-sm">
                📥 Download Excel (.xlsx)
            </a>
            @else
            <div class="inline-flex items-center gap-1.5 bg-slate-100 border border-slate-200 text-slate-400 text-xs font-semibold px-3 py-1.5 rounded-xl cursor-not-allowed" title="Download Excel hanya aktif setelah disetujui RSO">
                🔒 Download Excel (Menunggu Approval RSO)
            </div>
            @endif
        </div>

        {{-- Header Status --}}
        <div class="bg-white rounded-2xl p-6 border border-telkom-gray-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">{{ $simulasi->nama_proyek }}</h1>
                <p class="text-slate-500" style="font-size: 0.85rem;">Lokasi: {{ $simulasi->lokasi ?? '-' }} | Dibuat: {{ $simulasi->created_at->format('d M Y H:i') }}</p>
            </div>
            <div>
                @if($simulasi->layak === true)
                    <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-2 rounded-2xl text-center">
                        <span class="text-xs font-semibold block">HASIL EVALUASI</span>
                        <span class="text-lg font-black block">LAYAK ✅</span>
                    </div>
                @elseif($simulasi->layak === false)
                    <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-2 rounded-2xl text-center">
                        <span class="text-xs font-semibold block">HASIL EVALUASI</span>
                        <span class="text-lg font-black block">TIDAK LAYAK ❌</span>
                    </div>
                @else
                    <div class="bg-amber-100 border border-amber-200 text-amber-800 px-4 py-2 rounded-2xl text-center">
                        <span class="text-xs font-semibold block">STATUS</span>
                        <span class="text-sm font-bold block">{{ $simulasi->status_label }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Review Status Badge --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_APPROVED)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="text-sm font-bold text-green-800">Disetujui oleh RSO</p>
                <p class="text-xs text-green-600">Dokumen siap dicetak/diunduh. Disetujui oleh {{ $simulasi->reviewer?->name ?? 'Admin' }} {{ $simulasi->reviewed_at ? 'pada ' . $simulasi->reviewed_at->format('d M Y, H:i') : '' }}.</p>
            </div>
        </div>
        @endif

        {{-- Catatan Revisi dari RSO --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_REVISI && $simulasi->catatan_revisi)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 space-y-3">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <p class="text-sm font-bold text-red-800">Perlu Revisi dari RSO</p>
                    <p class="text-xs text-red-600">Direview oleh {{ $simulasi->reviewer?->name ?? 'Admin' }} {{ $simulasi->reviewed_at ? 'pada ' . $simulasi->reviewed_at->format('d M Y, H:i') : '' }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-3 border border-red-200 text-sm text-red-700">
                {{ $simulasi->catatan_revisi }}
            </div>
            @if($simulasi->user_id_pihak_b === $user->id || $simulasi->user_id_pihak_a === $user->id)
            <a href="{{ route('simulasi.edit', $simulasi) }}" class="inline-flex items-center gap-2 bg-telkom-500 hover:bg-telkom-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-all shadow-sm">
                ✏️ Edit & Kirim Revisi
            </a>
            @endif
        </div>
        @endif

        {{-- Riwayat Siklus Revisi (FR-11) --}}
        @if($simulasi->revisiLogs && $simulasi->revisiLogs->count() > 0)
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4 text-telkom-500"></i>
                Riwayat Siklus Revisi ({{ $simulasi->revisiLogs->count() }} Kali Revisi)
            </h2>
            <div class="space-y-2">
                @foreach($simulasi->revisiLogs as $log)
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1">
                    <div class="flex items-center justify-between text-slate-500">
                        <span class="font-semibold text-slate-700">Oleh: {{ $log->reviewer?->name ?? 'RSO' }}</span>
                        <span>{{ $log->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <p class="text-slate-800 bg-white p-2 rounded-lg border border-slate-100 font-mono">{{ $log->catatan }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- RSO Review Actions (admin only, saat status menunggu_review) --}}
        @if($user->hasRole('admin') && in_array($simulasi->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 space-y-4" x-data="{ showReject: false }">
            <div class="flex items-start gap-3">
                <span class="text-2xl">📋</span>
                <div>
                    <p class="text-sm font-bold text-indigo-800">Review Dokumen Simulasi</p>
                    <p class="text-xs text-indigo-600">Periksa hasil perhitungan, lalu setujui atau minta revisi.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('simulasi.approve', $simulasi) }}">
                    @csrf
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm transition-all shadow-sm">
                        ✅ Setujui & Cetak
                    </button>
                </form>
                <button @click="showReject = !showReject" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold px-5 py-2.5 rounded-xl text-sm transition-all">
                    ↩️ Minta Revisi
                </button>
            </div>
            <div x-show="showReject" x-transition class="border-t border-indigo-200 pt-4">
                <form method="POST" action="{{ route('simulasi.reject', $simulasi) }}" class="space-y-3">
                    @csrf
                    <label class="block text-xs font-semibold text-indigo-800">Catatan Revisi untuk User *</label>
                    <textarea name="catatan_revisi" required rows="3" placeholder="Jelaskan apa yang perlu diperbaiki..."
                              class="w-full rounded-xl border border-indigo-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none"></textarea>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2 rounded-xl text-sm transition-all">
                        Kirim Permintaan Revisi
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- If status is MENUNGGU_PIHAK_A and user is engineering/admin, show form to complete --}}
        @if($simulasi->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_A && (auth()->user()->hasRole('engineering_capex') || auth()->user()->hasRole('admin')))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-amber-900">Lengkapi Parameter Pihak A (Engineering & CAPEX)</h2>
            <form method="POST" action="{{ route('simulasi.complete', $simulasi) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Biaya Material (Rp)</label>
                        <input type="number" name="material" value="{{ old('material', 0) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Biaya Jasa (Rp)</label>
                        <input type="number" name="jasa" value="{{ old('jasa', 0) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">BOP (%)</label>
                        <input type="number" name="bop_pct" value="{{ old('bop_pct', 40) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Lifetime (Tahun)</label>
                        <input type="number" name="lifetime" value="{{ old('lifetime', 4) }}" required min="1" max="20"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">O&M (%)</label>
                        <input type="number" name="om_pct" value="{{ old('om_pct', 12) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Pajak (%)</label>
                        <input type="number" name="tax_pct" value="{{ old('tax_pct', 22) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">WACC (%)</label>
                        <input type="number" name="wacc_pct" value="{{ old('wacc_pct', 10.9) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-md">
                    Proses & Hitung Kelayakan
                </button>
            </form>
        </div>
        @endif

        {{-- Financial Summary Indicators --}}
        @if($simulasi->hasil_json)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">Net Present Value (NPV)</span>
                <span class="text-xl font-extrabold text-telkom-gray-800 mt-1 block">
                    Rp {{ number_format($simulasi->npv ?? 0, 0, ',', '.') }}
                </span>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">IRR / MIRR</span>
                <span class="text-xl font-extrabold text-telkom-gray-800 mt-1 block">
                    {{ number_format(($simulasi->mirr ?? 0) * 100, 2) }}%
                </span>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">Payback Period (PBP)</span>
                <span class="text-xl font-extrabold text-telkom-gray-800 mt-1 block">
                    {{ number_format($simulasi->payback_period ?? 0, 2) }} Tahun
                </span>
            </div>
        </div>

        {{-- Additional KPIs --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-4 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">Total Revenue</span>
                <span class="text-sm font-bold text-telkom-gray-800 mt-1 block">Rp {{ number_format($simulasi->hasil_json['total_revenue'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">GPM</span>
                <span class="text-sm font-bold text-telkom-gray-800 mt-1 block">{{ number_format($simulasi->hasil_json['gpm'] ?? 0, 2) }}%</span>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">Net Income</span>
                <span class="text-sm font-bold text-telkom-gray-800 mt-1 block">Rp {{ number_format($simulasi->hasil_json['total_net_income'] ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-telkom-gray-200 shadow-sm">
                <span class="text-xs text-telkom-gray-400 font-semibold block">NIM</span>
                <span class="text-sm font-bold text-telkom-gray-800 mt-1 block">{{ number_format($simulasi->hasil_json['nim'] ?? 0, 2) }}%</span>
            </div>
        </div>
        @endif

        {{-- ===== GOAL SEEK: Rekomendasi "Kurang Berapa Lagi" ===== --}}
        @if($simulasi->hasil_json && $simulasi->layak === false && isset($simulasi->hasil_json['goal_seek']) && $simulasi->hasil_json['goal_seek']['needed'])
        @php $gs = $simulasi->hasil_json['goal_seek']; @endphp
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6 shadow-sm space-y-4">
            <div class="flex items-start gap-3">
                <span class="text-2xl">💡</span>
                <div>
                    <h2 class="text-sm font-bold text-amber-900">Rekomendasi: "Kurang Berapa Lagi?" (Goal Seek)</h2>
                    <p class="text-xs text-amber-700 mt-0.5">Berikut skenario minimal agar simulasi menjadi <strong>LAYAK INVESTASI</strong> (MIRR ≥ WACC+2%, NPV > 0, GPM ≥ 7%, NIM ≥ 2%):</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {{-- a) Minimal MRC --}}
                @if($gs['min_mrc'])
                <div class="bg-white rounded-xl p-4 border border-amber-200 space-y-1">
                    <span class="text-xs font-bold text-amber-800 flex items-center gap-1">📈 Minimal Tarif Bulanan (MRC)</span>
                    <span class="text-lg font-extrabold text-telkom-gray-800 block">Rp {{ number_format($gs['min_mrc'], 0, ',', '.') }}</span>
                    <span class="text-xs text-amber-600">Naik <strong>Rp {{ number_format($gs['delta_mrc'], 0, ',', '.') }}</strong> dari MRC saat ini</span>
                </div>
                @endif

                {{-- b) Minimal OTC --}}
                @if($gs['min_otc'])
                <div class="bg-white rounded-xl p-4 border border-amber-200 space-y-1">
                    <span class="text-xs font-bold text-amber-800 flex items-center gap-1">💰 Minimal Biaya Pasang (OTC)</span>
                    <span class="text-lg font-extrabold text-telkom-gray-800 block">Rp {{ number_format($gs['min_otc'], 0, ',', '.') }}</span>
                    <span class="text-xs text-amber-600">Naik <strong>Rp {{ number_format($gs['delta_otc'], 0, ',', '.') }}</strong> dari OTC saat ini</span>
                </div>
                @endif

                {{-- c) Target Efisiensi CAPEX --}}
                @if($gs['max_capex'])
                <div class="bg-white rounded-xl p-4 border border-amber-200 space-y-1">
                    <span class="text-xs font-bold text-amber-800 flex items-center gap-1">🔧 Target Efisiensi CAPEX</span>
                    <span class="text-lg font-extrabold text-telkom-gray-800 block">Rp {{ number_format($gs['max_capex'], 0, ',', '.') }}</span>
                    <span class="text-xs text-amber-600">Pangkas <strong>Rp {{ number_format($gs['delta_capex'], 0, ',', '.') }}</strong> dari CAPEX saat ini</span>
                </div>
                @endif

                {{-- d) Tambahan Durasi Kontrak --}}
                @if($gs['min_durasi'])
                <div class="bg-white rounded-xl p-4 border border-amber-200 space-y-1">
                    <span class="text-xs font-bold text-amber-800 flex items-center gap-1">📅 Tambahan Durasi Kontrak</span>
                    <span class="text-lg font-extrabold text-telkom-gray-800 block">{{ $gs['min_durasi'] }} Bulan</span>
                    <span class="text-xs text-amber-600">Perpanjang <strong>+{{ $gs['delta_durasi'] }} bulan</strong> dari durasi saat ini</span>
                </div>
                @endif
            </div>

            <p class="text-xs text-amber-600 italic border-t border-amber-200 pt-3">
                ⚠️ Setiap skenario di atas dihitung secara independen. Misalnya, cukup naikkan MRC <em>atau</em> turunkan CAPEX — tidak harus keduanya sekaligus.
            </p>
        </div>
        @endif

        {{-- Input JSON Detail --}}
        <div class="bg-white rounded-2xl p-6 border border-telkom-gray-200 shadow-sm space-y-3">
            <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Rincian Parameter Input</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                @if(isset($simulasi->input_json))
                    @foreach($simulasi->input_json as $key => $val)
                        @if(!in_array($key, ['nama_proyek', 'lokasi']))
                        <div class="bg-telkom-gray-50 p-2.5 rounded-xl border border-telkom-gray-100">
                            <span class="text-telkom-gray-400 block font-mono">{{ $key }}</span>
                            <span class="font-bold text-telkom-gray-800">{{ is_numeric($val) ? number_format($val, 2, ',', '.') : $val }}</span>
                        </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
