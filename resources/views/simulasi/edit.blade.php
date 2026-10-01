<x-layouts.app title="Revisi Simulasi">
    @php
        $inp = $simulasi->input_json ?? [];
        $rdmMaster = \App\Models\ParameterRdm::getActive();
        $isMarketing = auth()->user()->hasRole('sales_marketing');
        $isED = auth()->user()->hasRole('engineering_capex');
        $isAdmin = auth()->user()->hasRole('admin');
    @endphp

    <div class="space-y-6 max-w-4xl mx-auto" x-data="currencyForm({{ $rdmMaster->rdm_otc_pct ?? 0 }}, {{ $rdmMaster->rdm_mrc_pct ?? 0 }})">
        <div class="border-b border-slate-200 pb-5">
            <div class="flex items-center gap-2 mb-2">
                @if($isMarketing)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Divisi Marketing</span>
                @elseif($isED)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">Engineer Development (ED)</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">RSO / Administrator</span>
                @endif
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500 font-medium">Revisi Parameter Simulasi</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Revisi Simulasi: {{ $simulasi->nama_proyek }}</h1>
                @if($simulasi->pihakA)
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200" title="Diinput oleh Engineering Development (ED)">
                    <i data-lucide="wrench" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Penginput (ED): <strong class="text-slate-900">{{ $simulasi->pihakA->name }}</strong></span>
                </span>
                @endif
                @if($simulasi->pihakB)
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-800 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200" title="Dilengkapi oleh Marketing">
                    <i data-lucide="tag" class="w-3.5 h-3.5 text-blue-600"></i>
                    <span>Pengisi Komersial (MKT): <strong class="text-blue-900">{{ $simulasi->pihakB->name }}</strong></span>
                </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">Lakukan penyesuaian parameter proyek sesuai permintaan RSO, lalu kirim ulang untuk evaluasi ulang kelayakan.</p>
        </div>

        @if($simulasi->catatan_revisi)
        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-5 space-y-2.5">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-900 uppercase tracking-wider">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                <span>Catatan Permintaan Revisi dari RSO</span>
            </div>
            <p class="text-xs text-amber-900 bg-white p-3.5 rounded-lg border border-amber-200/80 leading-relaxed font-sans">{{ $simulasi->catatan_revisi }}</p>
        </div>
        @endif

        <form method="POST" action="{{ route('simulasi.update', $simulasi) }}" x-on:change="lookupPrice()" class="bg-white rounded-xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-7">
            @csrf
            @method('PUT')

            {{-- 1. JIKA MARKETING: Tampilkan ringkasan proyek & capex (read-only), lalu form input komersial --}}
            @if($isMarketing && !$isAdmin)
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-2 text-xs">
                    <span class="font-bold text-slate-700 block uppercase tracking-wider text-[11px]">Parameter Proyek & CAPEX (Dari Engineer Development)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 pt-1">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Penginput (ED)</span>
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
                        <div>
                            <span class="text-slate-500 block text-[11px]">Nama Proyek</span>
                            <span class="font-semibold text-slate-900">{{ $simulasi->nama_proyek }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Lokasi</span>
                            <span class="font-semibold text-slate-900">{{ $simulasi->lokasi ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Biaya Material</span>
                            <span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['material'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Biaya Jasa</span>
                            <span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['jasa'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Parameter Komersial: Paketisasi, OTC, MRC --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">1</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Parameter Komersial (Marketing)</h2>
                    </div>
                    {{-- Paketisasi & Bandwidth (dinamis) --}}
                    <x-produk-selector
                        prefix="mkt"
                        :kategori="old('kategori', $simulasi->kategori ?? $inp['kategori'] ?? '')"
                        :paketisasi="old('paketisasi', $simulasi->paketisasi ?? $inp['paketisasi'] ?? '')"
                        :bandwidth="old('bandwidth', $simulasi->bandwidth ?? $inp['bandwidth'] ?? '')"
                    />

                    {{-- Wilayah Zonasi Tarif Telkom --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Wilayah / Zona Tarif Telkom
                        </label>
                        <select name="wilayah" x-model="wilayah" x-on:change="lookupPrice('mkt')"
                                class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <option value="">-- Pilih Wilayah (Otomatis jika kosong) --</option>
                            <option value="Jabodetabek" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Jabodetabek' || str_contains(strtolower($simulasi->lokasi ?? ''), 'jakarta') || str_contains(strtolower($simulasi->lokasi ?? ''), 'bogor') || str_contains(strtolower($simulasi->lokasi ?? ''), 'depok') || str_contains(strtolower($simulasi->lokasi ?? ''), 'tangerang') || str_contains(strtolower($simulasi->lokasi ?? ''), 'bekasi')) ? 'selected' : '' }}>Jabodetabek</option>
                            <option value="Jawa" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Jawa' || str_contains(strtolower($simulasi->lokasi ?? ''), 'jawa') || str_contains(strtolower($simulasi->lokasi ?? ''), 'bandung') || str_contains(strtolower($simulasi->lokasi ?? ''), 'surabaya') || str_contains(strtolower($simulasi->lokasi ?? ''), 'semarang')) ? 'selected' : '' }}>Jawa</option>
                            <option value="Sumatera" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Sumatera' || str_contains(strtolower($simulasi->lokasi ?? ''), 'sumatera') || str_contains(strtolower($simulasi->lokasi ?? ''), 'medan') || str_contains(strtolower($simulasi->lokasi ?? ''), 'palembang')) ? 'selected' : '' }}>Sumatera</option>
                            <option value="Kalimantan / KTI" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Kalimantan / KTI' || str_contains(strtolower($simulasi->lokasi ?? ''), 'kalimantan') || str_contains(strtolower($simulasi->lokasi ?? ''), 'sulawesi') || str_contains(strtolower($simulasi->lokasi ?? ''), 'papua') || str_contains(strtolower($simulasi->lokasi ?? ''), 'makassar') || str_contains(strtolower($simulasi->lokasi ?? ''), 'balikpapan')) ? 'selected' : '' }}>Kalimantan / KTI</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Wilayah acuan pencocokan tarif katalog (Lokasi proyek: {{ $simulasi->lokasi ?? '-' }}).</p>
                    </div>

                    {{-- Durasi Kontrak & Bulan di Tahun 1 --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Durasi Kontrak (Bulan) <span class="text-red-500">*</span></label>
                            <input type="number" name="durasi_kontrak" value="{{ old('durasi_kontrak', $inp['durasi_kontrak'] ?? 48) }}" required min="1" max="120"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <p class="text-xs text-slate-500 mt-1">Lama kontrak layanan dalam bulan.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bulan di Tahun 1 <span class="text-red-500">*</span></label>
                            <input type="number" name="bulan_tahun1" value="{{ old('bulan_tahun1', $inp['bulan_tahun1'] ?? 6) }}" required min="1" max="12"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <p class="text-xs text-slate-500 mt-1">Jumlah bulan operasional di tahun pertama.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif OTC (One-Time Charge) (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'otc')" x-on:focus="selectAll($event)"
                                   x-bind:value="display.otc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="otc" x-bind:value="raw.otc">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.otc)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif MRC (Monthly Recurring) (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'mrc')" x-on:focus="selectAll($event)"
                                   x-bind:value="display.mrc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="mrc" x-bind:value="raw.mrc">
                            <input type="hidden" name="tarif_normal_mrc" x-bind:value="tarifNormal">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.mrc)"></p>
                        </div>
                    </div>

                    {{-- Widget Informasi Diskon Realtime --}}
                    <div x-show="tarifNormal > 0" x-transition class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-4 h-4 text-red-600"></i>
                                <span>Tarif Resmi Katalog (Price List):</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-slate-900 text-sm" x-text="'Rp ' + formatRp(tarifNormal) + ' /bln'"></span>
                                <button type="button" x-show="raw.mrc !== tarifNormal" @click="applyCatalogPrice()" class="text-[11px] text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Gunakan Tarif Katalog</span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <template x-if="diskonNominal > 0">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <i data-lucide="percent" class="w-3.5 h-3.5"></i>
                                        <span x-text="'Diskon ' + diskonPct + '% (Potongan Rp ' + formatRp(diskonNominal) + ')'"></span>
                                    </span>
                                </template>
                                <template x-if="diskonNominal === 0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                        <span>Tarif Normal (Tanpa Diskon)</span>
                                    </span>
                                </template>
                                <template x-if="diskonNominal < 0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                        <span x-text="'Markup +' + Math.abs(diskonPct) + '% (+Rp ' + formatRp(Math.abs(diskonNominal)) + ')'"></span>
                                    </span>
                                </template>
                            </div>

                            <template x-if="diskonNominal > 0">
                                <span class="text-xs font-medium" :class="isProfitDeficit ? 'text-rose-600 font-bold' : 'text-amber-800'">
                                    <span x-text="profitKeterangan"></span>
                                </span>
                            </template>
                        </div>

                        <template x-if="isProfitDeficit">
                            <div class="text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded-lg p-2.5 font-medium flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                                <span>Peringatan: Diskon memotong seluruh target profit 30%. Harga penawaran berada di bawah modal estimasi COGS (Rp <span x-text="formatRp(cogsStandar)"></span>).</span>
                            </div>
                        </template>
                    </div>
                </div>
            @endif

            {{-- 2. JIKA ENGINEERING: Tampilkan ringkasan komersial (read-only), lalu form input 6 parameter --}}
            @if($isED && !$isAdmin)
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 space-y-2 text-xs">
                    <span class="font-bold text-slate-700 block uppercase tracking-wider text-[11px]">Parameter Komersial (Dari Marketing)</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        <div>
                            <span class="text-slate-500 block text-[11px]">Paketisasi</span>
                            <span class="font-semibold text-slate-900">{{ $simulasi->paketisasi ?? $inp['paketisasi'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Bandwidth</span>
                            <span class="font-semibold text-slate-900">{{ $simulasi->bandwidth ?? $inp['bandwidth'] ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Tarif OTC</span>
                            <span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['otc'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block text-[11px]">Tarif MRC</span>
                            <span class="font-semibold font-mono text-slate-900">Rp {{ number_format($inp['mrc'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Parameter Proyek & CAPEX: Nama, Lokasi, Material, Jasa --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">1</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Parameter Proyek & CAPEX (Engineering)</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Proyek <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_proyek" value="{{ old('nama_proyek', $inp['nama_proyek'] ?? $simulasi->nama_proyek) }}" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi Proyek <span class="text-red-500">*</span></label>
                            <input type="text" name="lokasi" value="{{ old('lokasi', $inp['lokasi'] ?? $simulasi->lokasi) }}" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Material (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'material')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'material')"
                                   x-bind:value="display.material" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="material" x-bind:value="raw.material">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.material)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Jasa (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'jasa')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'jasa')"
                                   x-bind:value="display.jasa" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="jasa" x-bind:value="raw.jasa">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.jasa)"></p>
                        </div>
                    </div>

                    {{-- Total Base CAPEX Preview --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-700 block uppercase tracking-wider">Total Base CAPEX (Material + Jasa)</span>
                            <span class="text-slate-500 text-[11px]">Estimasi biaya fisik belum termasuk BOP & PPN</span>
                        </div>
                        <span class="text-base font-bold font-mono text-slate-900" x-text="'Rp ' + formatRp((Number(raw.material) || 0) + (Number(raw.jasa) || 0))"></span>
                    </div>
                </div>
            @endif

            {{-- 3. JIKA ADMIN: Tampilkan semua parameter secara komprehensif --}}
            @if($isAdmin)
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">1</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Informasi Proyek</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Proyek <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_proyek" value="{{ old('nama_proyek', $inp['nama_proyek'] ?? $simulasi->nama_proyek) }}" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi Proyek <span class="text-red-500">*</span></label>
                            <input type="text" name="lokasi" value="{{ old('lokasi', $inp['lokasi'] ?? $simulasi->lokasi) }}" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Durasi Kontrak (Bulan) <span class="text-red-500">*</span></label>
                            <input type="number" name="durasi_kontrak" value="{{ old('durasi_kontrak', $inp['durasi_kontrak'] ?? 48) }}" required min="1" max="120"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bulan di Tahun 1 <span class="text-red-500">*</span></label>
                            <input type="number" name="bulan_tahun1" value="{{ old('bulan_tahun1', $inp['bulan_tahun1'] ?? 6) }}" required min="1" max="12"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">2</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Parameter Komersial (Marketing)</h2>
                    </div>
                    {{-- Paketisasi & Bandwidth (dinamis) --}}
                    <x-produk-selector
                        prefix="admin"
                        :kategori="old('kategori', $simulasi->kategori ?? $inp['kategori'] ?? '')"
                        :paketisasi="old('paketisasi', $simulasi->paketisasi ?? $inp['paketisasi'] ?? '')"
                        :bandwidth="old('bandwidth', $simulasi->bandwidth ?? $inp['bandwidth'] ?? '')"
                    />
                    {{-- Wilayah Zonasi Tarif Telkom --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Wilayah / Zona Tarif Telkom
                        </label>
                        <select name="wilayah" x-model="wilayah" x-on:change="lookupPrice('admin')"
                                class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <option value="">-- Pilih Wilayah (Otomatis jika kosong) --</option>
                            <option value="Jabodetabek" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Jabodetabek' || str_contains(strtolower($simulasi->lokasi ?? ''), 'jakarta') || str_contains(strtolower($simulasi->lokasi ?? ''), 'bogor') || str_contains(strtolower($simulasi->lokasi ?? ''), 'depok') || str_contains(strtolower($simulasi->lokasi ?? ''), 'tangerang') || str_contains(strtolower($simulasi->lokasi ?? ''), 'bekasi')) ? 'selected' : '' }}>Jabodetabek</option>
                            <option value="Jawa" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Jawa' || str_contains(strtolower($simulasi->lokasi ?? ''), 'jawa') || str_contains(strtolower($simulasi->lokasi ?? ''), 'bandung') || str_contains(strtolower($simulasi->lokasi ?? ''), 'surabaya') || str_contains(strtolower($simulasi->lokasi ?? ''), 'semarang')) ? 'selected' : '' }}>Jawa</option>
                            <option value="Sumatera" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Sumatera' || str_contains(strtolower($simulasi->lokasi ?? ''), 'sumatera') || str_contains(strtolower($simulasi->lokasi ?? ''), 'medan') || str_contains(strtolower($simulasi->lokasi ?? ''), 'palembang')) ? 'selected' : '' }}>Sumatera</option>
                            <option value="Kalimantan / KTI" {{ (old('wilayah', $inp['wilayah'] ?? '') == 'Kalimantan / KTI' || str_contains(strtolower($simulasi->lokasi ?? ''), 'kalimantan') || str_contains(strtolower($simulasi->lokasi ?? ''), 'sulawesi') || str_contains(strtolower($simulasi->lokasi ?? ''), 'papua') || str_contains(strtolower($simulasi->lokasi ?? ''), 'makassar') || str_contains(strtolower($simulasi->lokasi ?? ''), 'balikpapan')) ? 'selected' : '' }}>Kalimantan / KTI</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Wilayah acuan pencocokan tarif katalog (Lokasi proyek: {{ $simulasi->lokasi ?? '-' }}).</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif OTC (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'otc')" x-on:focus="selectAll($event)"
                                   x-bind:value="display.otc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="otc" x-bind:value="raw.otc">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.otc)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tarif MRC (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'mrc')" x-on:focus="selectAll($event)"
                                   x-bind:value="display.mrc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="mrc" x-bind:value="raw.mrc">
                            <input type="hidden" name="tarif_normal_mrc" x-bind:value="tarifNormal">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.mrc)"></p>
                        </div>
                    </div>

                    {{-- Widget Informasi Diskon Realtime --}}
                    <div x-show="tarifNormal > 0" x-transition class="rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-4 h-4 text-red-600"></i>
                                <span>Tarif Resmi Katalog (Price List):</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-slate-900 text-sm" x-text="'Rp ' + formatRp(tarifNormal) + ' /bln'"></span>
                                <button type="button" x-show="raw.mrc !== tarifNormal" @click="applyCatalogPrice()" class="text-[11px] text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Gunakan Tarif Katalog</span>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <template x-if="diskonNominal > 0">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        <i data-lucide="percent" class="w-3.5 h-3.5"></i>
                                        <span x-text="'Diskon ' + diskonPct + '% (Potongan Rp ' + formatRp(diskonNominal) + ')'"></span>
                                    </span>
                                </template>
                                <template x-if="diskonNominal === 0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                        <span>Tarif Normal (Tanpa Diskon)</span>
                                    </span>
                                </template>
                                <template x-if="diskonNominal < 0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                        <span x-text="'Markup +' + Math.abs(diskonPct) + '% (+Rp ' + formatRp(Math.abs(diskonNominal)) + ')'"></span>
                                    </span>
                                </template>
                            </div>

                            <template x-if="diskonNominal > 0">
                                <span class="text-xs font-medium" :class="isProfitDeficit ? 'text-rose-600 font-bold' : 'text-amber-800'">
                                    <span x-text="profitKeterangan"></span>
                                </span>
                            </template>
                        </div>

                        <template x-if="isProfitDeficit">
                            <div class="text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded-lg p-2.5 font-medium flex items-center gap-2">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                                <span>Peringatan: Diskon memotong seluruh target profit 30%. Harga penawaran berada di bawah modal estimasi COGS (Rp <span x-text="formatRp(cogsStandar)"></span>).</span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">3</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Biaya Material dan Jasa (CAPEX)</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Material (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'material')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'material')"
                                   x-bind:value="display.material" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="material" x-bind:value="raw.material">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.material)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Jasa (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'jasa')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'jasa')"
                                   x-bind:value="display.jasa" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="jasa" x-bind:value="raw.jasa">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.jasa)"></p>
                        </div>
                    </div>

                    {{-- Real-time Total Base CAPEX summary card for admin --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-700 block uppercase tracking-wider">Total Base CAPEX (Material + Jasa)</span>
                            <span class="text-slate-500 text-[11px]">Estimasi biaya fisik belum termasuk BOP & PPN</span>
                        </div>
                        <span class="text-base font-bold font-mono text-slate-900" x-text="'Rp ' + formatRp((Number(raw.material) || 0) + (Number(raw.jasa) || 0))"></span>
                    </div>
                </div>

                <div class="space-y-4 pt-2" x-data="{ showAdvanced: false }">
                    <button type="button" @click="showAdvanced = !showAdvanced" class="flex items-center justify-between w-full text-xs font-bold text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-200">
                        <span>Parameter Kebijakan Finansial (BOP, Lifetime, O&M, Pajak, WACC)</span>
                        <span x-text="showAdvanced ? 'Tutup ▲' : 'Ubah ▼'"></span>
                    </button>
                    <div x-show="showAdvanced" class="space-y-4 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">BOP (%) <span class="text-red-500">*</span></label>
                                <input type="number" name="bop_pct" value="{{ old('bop_pct', $inp['bop_pct'] ?? $rdmMaster->bop_pct) }}" required step="any"
                                       class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lifetime (Tahun) <span class="text-red-500">*</span></label>
                                <input type="number" name="lifetime" value="{{ old('lifetime', $inp['lifetime'] ?? $rdmMaster->lifetime) }}" required min="1" max="20"
                                       class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">O&M (%) <span class="text-red-500">*</span></label>
                                <input type="number" name="om_pct" value="{{ old('om_pct', $inp['om_pct'] ?? $rdmMaster->om_pct) }}" required step="any"
                                       class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pajak (%) <span class="text-red-500">*</span></label>
                                <input type="number" name="tax_pct" value="{{ old('tax_pct', $inp['tax_pct'] ?? $rdmMaster->tax_pct) }}" required step="any"
                                       class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">WACC (%) <span class="text-red-500">*</span></label>
                                <input type="number" name="wacc_pct" value="{{ old('wacc_pct', $inp['wacc_pct'] ?? $rdmMaster->wacc_pct) }}" required step="any"
                                       class="w-full h-10 rounded-lg border border-slate-300 px-3 text-sm text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="pt-2">
                <button type="submit" class="w-full sm:w-auto px-6 h-11 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors shadow-sm text-sm inline-flex items-center justify-center gap-2">
                    <span>Hitung Ulang & Kirim untuk Review RSO</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
    function currencyForm(rdmOtcPct, rdmMrcPct) {
        const initRaw = {
            otc: {{ $inp['otc'] ?? 0 }},
            mrc: {{ $inp['mrc'] ?? 0 }},
            material: {{ $inp['material'] ?? 0 }},
            jasa: {{ $inp['jasa'] ?? 0 }},
        };
        return {
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, v === 0 ? '0' : Number(v).toLocaleString('id-ID')])
            ),
            rdmOtcPct: parseFloat(rdmOtcPct) || 0,
            rdmMrcPct: parseFloat(rdmMrcPct) || 0,
            wilayah: '{{ old('wilayah', $inp['wilayah'] ?? '') }}',
            tarifNormal: {{ old('tarif_normal_mrc', $inp['tarif_normal_mrc'] ?? 0) }},
            foundPriceList: {{ isset($inp['tarif_normal_mrc']) && $inp['tarif_normal_mrc'] > 0 ? 'true' : 'false' }},

            init() {
                this.$nextTick(() => {
                    this.lookupPrice();
                });
            },

            get cogsOtc() {
                return (this.raw.otc || 0) * (1 - (this.rdmOtcPct / 100));
            },
            get cogsMrc() {
                return (this.raw.mrc || 0) * (1 - (this.rdmMrcPct / 100));
            },
            get totalCapexBase() {
                return (Number(this.raw.material) || 0) + (Number(this.raw.jasa) || 0);
            },
            get diskonNominal() {
                return this.tarifNormal > 0 ? (this.tarifNormal - (this.raw.mrc || 0)) : 0;
            },
            get diskonPct() {
                if (this.tarifNormal <= 0 || this.diskonNominal === 0) return 0;
                return ((this.diskonNominal / this.tarifNormal) * 100).toFixed(1);
            },
            get cogsStandar() {
                return this.tarifNormal > 0 ? (this.tarifNormal * (1 - (this.rdmMrcPct / 100))) : 0;
            },
            get sisaProfitNominal() {
                return (this.raw.mrc || 0) - this.cogsStandar;
            },
            get sisaProfitPct() {
                if (!this.raw.mrc || this.raw.mrc <= 0) return 0;
                return ((this.sisaProfitNominal / this.raw.mrc) * 100).toFixed(1);
            },
            get isProfitDeficit() {
                return this.tarifNormal > 0 && this.sisaProfitNominal < 0;
            },
            get profitKeterangan() {
                if (this.isProfitDeficit) {
                    return 'Defisit Profit (Harga di bawah modal COGS)';
                }
                return 'Sisa Margin Profit: ' + this.sisaProfitPct + '% (Target: ' + this.rdmMrcPct + '%)';
            },

            applyCatalogPrice() {
                if (this.tarifNormal > 0) {
                    this.raw.mrc = this.tarifNormal;
                    this.display.mrc = this.tarifNormal.toLocaleString('id-ID');
                }
            },

            async lookupPrice(preferredPrefix = null, forceAutoFill = false) {
                const prefixes = preferredPrefix ? [preferredPrefix] : ['mkt', 'admin', 'revisi_mkt'];
                let paket = '';
                let bw = '';

                for (const pfx of prefixes) {
                    const elP = document.getElementById(pfx + '_paketisasi');
                    const elB = document.getElementById(pfx + '_bw_hidden');
                    if (elP && elP.value) {
                        paket = elP.value;
                        bw = elB ? elB.value : '';
                        break;
                    }
                }

                if (!paket || !bw) return;

                try {
                    const params = new URLSearchParams({
                        paket: paket,
                        bandwidth: bw,
                        wilayah: this.wilayah || ''
                    });
                    const res = await fetch(`/api/price-list/lookup?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();

                    if (data.found) {
                        const previousNormal = this.tarifNormal;
                        this.tarifNormal = data.tarif_normal_mrc;
                        this.foundPriceList = true;
                        if (forceAutoFill || !this.raw.mrc || this.raw.mrc === 0 || this.raw.mrc === previousNormal) {
                            this.raw.mrc = data.tarif_normal_mrc;
                            this.display.mrc = data.tarif_normal_mrc.toLocaleString('id-ID');
                        }
                    } else {
                        this.foundPriceList = false;
                        this.tarifNormal = 0;
                    }
                } catch (e) {
                    console.error('Error saat lookup katalog price list:', e);
                }
            },

            handleCurrency(event, field) {
                let digits = event.target.value.replace(/\D/g, '');
                let num = parseInt(digits, 10) || 0;
                this.raw[field] = num;
                this.display[field] = digits === '' ? '' : num.toLocaleString('id-ID');
                this.$nextTick(() => { event.target.value = this.display[field]; });
            },
            handleBlur(event, field) {
                if (!this.display[field] || this.display[field] === '') {
                    this.display[field] = '0';
                    event.target.value = '0';
                }
            },
            selectAll(event) { event.target.select(); },
            formatRp(value) { return (parseFloat(value) || 0).toLocaleString('id-ID'); },
        };
    }
    </script>
    <script src="{{ asset('js/produk-selector.js') }}"></script>
</x-layouts.app>
