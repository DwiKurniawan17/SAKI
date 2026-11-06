<x-layouts.app title="Revisi Simulasi">
    @php
        $inp = $simulasi->input_json ?? [];
        $rdmMaster = \App\Models\ParameterRdm::getActive();
        $isMarketing = auth()->user()->hasRole('sales_marketing');
        $isED = auth()->user()->hasRole('engineering_capex');
        $isAdmin = auth()->user()->hasRole('admin');
    @endphp

    <div class="space-y-6 max-w-4xl mx-auto" x-data="currencyForm({{ $rdmMaster->rdm_otc_pct ?? 0 }}, {{ $rdmMaster->rdm_mrc_pct ?? 0 }}, {{ $rdmMaster->default_otc ?? 2500000 }})">
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

        <form method="POST" action="{{ route('simulasi.update', $simulasi) }}" x-on:change="handleFormChange($event)" class="bg-white rounded-xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-7">
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
                    {{-- Paketisasi, Wilayah, & Bandwidth (dinamis katalog Price List) --}}
                    <x-produk-selector
                        prefix="mkt"
                        :kategori="old('kategori', $simulasi->kategori ?? $inp['kategori'] ?? '')"
                        :paketisasi="old('paketisasi', $simulasi->paketisasi ?? $inp['paketisasi'] ?? '')"
                        :wilayah="old('wilayah', $inp['wilayah'] ?? '')"
                        :bandwidth="old('bandwidth', $simulasi->bandwidth ?? $inp['bandwidth'] ?? '')"
                        :lokasi="$simulasi->lokasi ?? null"
                    />

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
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">Tarif OTC (One-Time Charge) (Rp) <span class="text-red-500">*</span></label>
                                <button type="button" @click="raw.otc = defaultOtc; display.otc = Number(defaultOtc).toLocaleString('id-ID')" class="text-[11px] text-slate-500 hover:text-red-600 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer" title="Gunakan tarif standar tahunan OTC">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Standar: Rp {{ number_format($rdmMaster->default_otc ?? 2500000, 0, ',', '.') }}</span>
                                </button>
                            </div>
                            <input type="text" inputmode="numeric" name="otc_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'otc')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'otc')"
                                   x-on:change.stop
                                   x-bind:value="display.otc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="otc" x-bind:value="raw.otc">
                            <div class="flex items-center justify-between mt-1 text-xs">
                                <span class="text-[11px] text-slate-400">Default standar (bisa diedit jika ada negosiasi).</span>
                                <span class="font-mono text-slate-600 font-medium" x-text="'Rp ' + formatRp(raw.otc)"></span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">Tarif MRC (Monthly Recurring) (Rp) <span class="text-red-500">*</span></label>
                                <button type="button" x-show="foundPriceList && raw.mrc !== tarifNormal" @click="applyCatalogPrice()" class="text-[11px] text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Gunakan Tarif Katalog</span>
                                </button>
                            </div>
                            <input type="text" inputmode="numeric" name="mrc_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'mrc')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'mrc')"
                                   x-on:change.stop
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
                            <div class="text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <span class="font-mono font-bold text-slate-900 text-sm" x-text="'Rp ' + formatRp(tarifNormal) + ' /bln'"></span>
                                    <button type="button" x-show="raw.mrc !== tarifNormal" @click="applyCatalogPrice()" class="text-[11px] text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                        <span>Gunakan Tarif Katalog</span>
                                    </button>
                                </div>
                                <template x-if="updatedAt">
                                    <span class="block text-[10px] text-slate-400 font-normal mt-0.5" x-text="'Diupdate: ' + updatedAt"></span>
                                </template>
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
                                <span>Peringatan: Diskon (<span x-text="diskonPct"></span>%) memotong seluruh batas target margin profit (<span x-text="rdmMrcPct"></span>%). Penawaran mengalami defisit margin profit (<span x-text="sisaProfitPct"></span>%).</span>
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
                            <input type="text" inputmode="numeric" name="material_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'material')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'material')"
                                   x-on:change.stop
                                   x-bind:value="display.material" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="material" x-bind:value="raw.material">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.material)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Jasa (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" name="jasa_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'jasa')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'jasa')"
                                   x-on:change.stop
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
                    {{-- Paketisasi, Wilayah, & Bandwidth (dinamis katalog Price List) --}}
                    <x-produk-selector
                        prefix="admin"
                        :kategori="old('kategori', $simulasi->kategori ?? $inp['kategori'] ?? '')"
                        :paketisasi="old('paketisasi', $simulasi->paketisasi ?? $inp['paketisasi'] ?? '')"
                        :wilayah="old('wilayah', $inp['wilayah'] ?? '')"
                        :bandwidth="old('bandwidth', $simulasi->bandwidth ?? $inp['bandwidth'] ?? '')"
                        :lokasi="$simulasi->lokasi ?? null"
                    />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">Tarif OTC (One-Time Charge) (Rp) <span class="text-red-500">*</span></label>
                                <button type="button" @click="raw.otc = defaultOtc; display.otc = Number(defaultOtc).toLocaleString('id-ID')" class="text-[11px] text-slate-500 hover:text-red-600 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer" title="Gunakan tarif standar tahunan OTC">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Standar: Rp {{ number_format($rdmMaster->default_otc ?? 2500000, 0, ',', '.') }}</span>
                                </button>
                            </div>
                            <input type="text" inputmode="numeric" name="otc_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'otc')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'otc')"
                                   x-on:change.stop
                                   x-bind:value="display.otc" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="otc" x-bind:value="raw.otc">
                            <div class="flex items-center justify-between mt-1 text-xs">
                                <span class="text-[11px] text-slate-400">Default standar (bisa diedit jika ada negosiasi).</span>
                                <span class="font-mono text-slate-600 font-medium" x-text="'Rp ' + formatRp(raw.otc)"></span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-700">Tarif MRC (Monthly Recurring) (Rp) <span class="text-red-500">*</span></label>
                                <button type="button" x-show="foundPriceList && raw.mrc !== tarifNormal" @click="applyCatalogPrice()" class="text-[11px] text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                    <span>Gunakan Tarif Katalog</span>
                                </button>
                            </div>
                            <input type="text" inputmode="numeric" name="mrc_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'mrc')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'mrc')"
                                   x-on:change.stop
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
                            <div class="text-right">
                                <div class="flex items-center gap-2 justify-end">
                                    <span class="font-mono font-bold text-slate-900 text-sm" x-text="'Rp ' + formatRp(tarifNormal) + ' /bln'"></span>
                                    <button type="button" x-show="raw.mrc !== tarifNormal" @click="applyCatalogPrice()" class="text-[11px] text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                        <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                                        <span>Gunakan Tarif Katalog</span>
                                    </button>
                                </div>
                                <template x-if="updatedAt">
                                    <span class="block text-[10px] text-slate-400 font-normal mt-0.5" x-text="'Diupdate: ' + updatedAt"></span>
                                </template>
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
                                <span>Peringatan: Diskon (<span x-text="diskonPct"></span>%) memotong seluruh batas target margin profit (<span x-text="rdmMrcPct"></span>%). Penawaran mengalami defisit margin profit (<span x-text="sisaProfitPct"></span>%).</span>
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
                            <input type="text" inputmode="numeric" name="material_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'material')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'material')"
                                   x-on:change.stop
                                   x-bind:value="display.material" required
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="material" x-bind:value="raw.material">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="'Rp ' + formatRp(raw.material)"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Jasa (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" name="jasa_display" data-currency="true"
                                   x-on:input="handleCurrency($event, 'jasa')"
                                   x-on:focus="selectAll($event)"
                                   x-on:blur="handleBlur($event, 'jasa')"
                                   x-on:change.stop
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
    function currencyForm(rdmOtcPct, rdmMrcPct, defaultOtcValue = 2500000) {
        const initialWilayah = '{{ old('wilayah', $inp['wilayah'] ?? '') }}';
        const initialPaket = '{{ old('paketisasi', $inp['paketisasi'] ?? ($simulasi->paketisasi ?? '')) }}';
        const initialBw = '{{ old('bandwidth', $inp['bandwidth'] ?? ($simulasi->bandwidth ?? '')) }}';
        const defaultOtcNum = Number(defaultOtcValue) || 2500000;
        const savedOtc = {{ old('otc', $inp['otc'] ?? 'null') ?? 'null' }};
        const initialOtc = (savedOtc !== null && savedOtc !== '' && Number(savedOtc) > 0) ? Number(savedOtc) : defaultOtcNum;
        const savedNormal = {{ !empty($inp['wilayah']) ? (old('tarif_normal_mrc', $inp['tarif_normal_mrc'] ?? 0)) : 0 }};
        const savedMrc = {{ !empty($inp['wilayah']) ? (old('mrc', $inp['mrc'] ?? ($simulasi->mrc ?? 0))) : 0 }};
        const isCustomInit = (savedNormal > 0 && savedMrc > 0 && Math.abs(savedMrc - savedNormal) > 1);

        const initRaw = {
            otc: initialOtc,
            mrc: savedMrc,
            material: {{ $inp['material'] ?? 0 }},
            jasa: {{ $inp['jasa'] ?? 0 }},
        };
        return {
            defaultOtc: defaultOtcNum,
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, v === 0 ? '0' : Number(v).toLocaleString('id-ID')])
            ),
            rdmOtcPct: parseFloat(rdmOtcPct) || 0,
            rdmMrcPct: parseFloat(rdmMrcPct) || 0,
            wilayah: initialWilayah,
            tarifNormal: savedNormal,
            updatedAt: '',
            updatedAtHuman: '',
            foundPriceList: {{ !empty($inp['wilayah']) && isset($inp['tarif_normal_mrc']) && $inp['tarif_normal_mrc'] > 0 ? 'true' : 'false' }},
            lastProductKey: `${initialPaket}|${initialBw}|${initialWilayah}`,
            userCustomizedMrc: isCustomInit,

            init() {
                this.$nextTick(() => {
                    if (this.wilayah) {
                        this.lookupPrice();
                    }
                });
            },

            handleFormChange(event) {
                const targetName = event?.target?.name || '';
                const targetId = event?.target?.id || '';

                // Abaikan perubahan dari input mata uang atau field non-katalog
                if (
                    ['mrc', 'otc', 'mrc_display', 'otc_display', 'material', 'jasa', 'durasi_kontrak', 'bulan_tahun1'].includes(targetName) ||
                    event?.target?.hasAttribute('data-currency')
                ) {
                    return;
                }

                // Hanya lakukan lookup jika elemen katalog produk yang berubah
                const isProductSelector = ['paketisasi', 'wilayah', 'bandwidth'].includes(targetName) ||
                    targetId.endsWith('_paketisasi') ||
                    targetId.endsWith('_wilayah') ||
                    targetId.endsWith('_bw_select') ||
                    targetId.endsWith('_bw_custom');

                if (isProductSelector) {
                    this.lookupPrice(null, true);
                }
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
                // Kalkulasi sisa margin: target/batas margin (misal 30%) dikurang persentase diskon harga asli
                const sisa = (this.rdmMrcPct || 0) - (parseFloat(this.diskonPct) || 0);
                return parseFloat(sisa.toFixed(1));
            },
            get isProfitDeficit() {
                return this.tarifNormal > 0 && this.sisaProfitPct < 0;
            },
            get profitKeterangan() {
                if (this.isProfitDeficit) {
                    return 'Defisit Margin Profit: ' + this.sisaProfitPct + '% (Target: ' + this.rdmMrcPct + '%)';
                }
                return 'Sisa Margin Profit: ' + this.sisaProfitPct + '% (Target: ' + this.rdmMrcPct + '%)';
            },

            applyCatalogPrice() {
                if (this.tarifNormal > 0) {
                    this.raw.mrc = this.tarifNormal;
                    this.display.mrc = this.tarifNormal.toLocaleString('id-ID');
                    this.userCustomizedMrc = false;
                }
            },

            async lookupPrice(preferredPrefix = null, forceAutoFill = false) {
                if (typeof preferredPrefix === 'boolean') {
                    forceAutoFill = preferredPrefix;
                    preferredPrefix = null;
                }
                const prefixes = preferredPrefix ? [preferredPrefix] : ['mkt', 'admin', 'revisi_mkt'];
                let paket = '';
                let bw = '';

                for (const pfx of prefixes) {
                    const elP = document.getElementById(pfx + '_paketisasi');
                    const elB = document.getElementById(pfx + '_bw_hidden');
                    const elBSelect = document.getElementById(pfx + '_bw_select');
                    const elW = document.getElementById(pfx + '_wilayah');
                    if (elP && elP.value) {
                        paket = elP.value;
                        bw = (elB && elB.value) ? elB.value : (elBSelect ? elBSelect.value : '');
                        if (elW && elW.value) {
                            this.wilayah = elW.value;
                        }
                        break;
                    }
                }

                const currentProductKey = `${paket}|${bw}|${this.wilayah}`;
                const productChanged = Boolean(this.lastProductKey && this.lastProductKey !== currentProductKey);
                this.lastProductKey = currentProductKey;

                if (!paket || !bw || !this.wilayah) {
                    const previousNormal = this.tarifNormal;
                    this.tarifNormal = 0;
                    this.updatedAt = '';
                    this.updatedAtHuman = '';
                    this.foundPriceList = false;
                    if (!this.wilayah && (forceAutoFill || (previousNormal > 0 && this.raw.mrc === previousNormal))) {
                        this.raw.mrc = 0;
                        this.display.mrc = '0';
                    }
                    return;
                }

                try {
                    const params = new URLSearchParams({
                        paket: paket,
                        bandwidth: bw,
                        wilayah: this.wilayah
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
                        this.updatedAt = data.updated_at || '';
                        this.updatedAtHuman = data.updated_at_human || '';
                        this.foundPriceList = true;

                        // Perbarui nilai Tarif MRC HANYA JIKA:
                        // 1. Nilai MRC saat ini masih kosong / 0
                        // 2. ATAU nilai MRC sebelumnya persis sama dengan tarif normal lama (user tidak menetapkan custom diskon)
                        // 3. ATAU produk sengaja diubah oleh user (productChanged)
                        // PENTING: Jika user sudah mengetik harga custom / diskon (userCustomizedMrc === true) dan produk TIDAK berubah,
                        // JANGAN PERNAH menimpa harga custom tersebut saat blur / klik luar kotak!
                        const isMrcEmpty = (!this.raw.mrc || this.raw.mrc === 0);
                        const wasCatalogPrice = (previousNormal > 0 && this.raw.mrc === previousNormal);

                        if (isMrcEmpty || wasCatalogPrice || productChanged) {
                            this.raw.mrc = data.tarif_normal_mrc;
                            this.display.mrc = data.tarif_normal_mrc.toLocaleString('id-ID');
                            if (productChanged) {
                                this.userCustomizedMrc = false;
                            }
                        }
                    } else {
                        this.foundPriceList = false;
                        this.tarifNormal = 0;
                        this.updatedAt = '';
                        this.updatedAtHuman = '';
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
                if (field === 'mrc') {
                    this.userCustomizedMrc = (this.tarifNormal > 0 ? (num !== this.tarifNormal) : true);
                }
                this.$nextTick(() => { event.target.value = this.display[field]; });
            },
            handleBlur(event, field) {
                if (!this.display[field] || this.display[field] === '') {
                    this.raw[field] = 0;
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
