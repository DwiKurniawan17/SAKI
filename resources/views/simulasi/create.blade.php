<x-layouts.app title="Form Simulasi Baru">
    <div class="space-y-6 max-w-2xl mx-auto" x-data="currencyForm()">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-telkom-500 mb-1">
                <span>Langkah 1 dari 2</span>
                <span>&bull;</span>
                <span>Pengisian Komersial (Sales Marketing)</span>
            </div>
            <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Form Simulasi AKI/CAPEX</h1>
            <p class="text-slate-500" style="font-size: 0.85rem;">Isi tarif dan informasi proyek. Direct Cost (COGS) akan dihitung otomatis oleh sistem dari RDM.</p>
        </div>

        @if(isset($pendingSimulasi) && count($pendingSimulasi) > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm space-y-2">
            <h2 class="font-bold text-amber-900">Simulasi Menunggu Input Pihak A (Engineering)</h2>
            <p class="text-xs text-amber-700">Ada simulasi yang dibuat Pihak B dan memerlukan kelengkapan data CAPEX dari Anda:</p>
            <div class="space-y-1">
                @foreach($pendingSimulasi as $pending)
                    <div class="flex items-center justify-between bg-white p-2.5 rounded-xl border border-amber-200 text-xs">
                        <span class="font-semibold text-telkom-gray-800">{{ $pending->nama_proyek }}</span>
                        <a href="{{ route('simulasi.show', $pending) }}" class="text-telkom-500 hover:underline font-bold">Lengkapi Data &rarr;</a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('simulasi.store') }}" class="bg-white rounded-2xl p-6 border border-telkom-gray-200 shadow-sm space-y-6">
            @csrf

            {{-- Informasi Proyek --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Informasi Proyek</h2>
                <div>
                    <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Nama Proyek *</label>
                    <input type="text" name="nama_proyek" value="{{ old('nama_proyek') }}" required placeholder="Contoh: Modernisasi Fiber Optik Witel Jakarta"
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Lokasi Proyek</label>
                    <input type="text" name="lokasi" value="{{ old('lokasi') }}" placeholder="Contoh: Witel Jakarta Barat"
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Durasi Kontrak (Bulan) *</label>
                        <input type="number" name="durasi_kontrak" value="{{ old('durasi_kontrak', 48) }}" required min="1" max="120"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="formatUnit({{ old('durasi_kontrak', 48) }}, 'durasi_kontrak', 'bulan')"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Bulan di Tahun 1 *</label>
                        <input type="number" name="bulan_tahun1" value="{{ old('bulan_tahun1', 6) }}" required min="1" max="12"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="formatUnit({{ old('bulan_tahun1', 6) }}, 'bulan_tahun1', 'bulan')"></p>
                    </div>
                </div>
            </div>

            {{-- Input Commercial & Revenue --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Parameter Tarif & Revenue (Marketing)</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Tarif OTC (One-Time Charge) (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'otc')" x-on:focus="selectAll($event)"
                               x-bind:value="display.otc"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="otc" x-bind:value="raw.otc">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.otc)"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Tarif MRC (Monthly Recurring) (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'mrc')" x-on:focus="selectAll($event)"
                               x-bind:value="display.mrc"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="mrc" x-bind:value="raw.mrc">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.mrc)"></p>
                    </div>
                </div>

                {{-- Direct Cost Auto Calculation Card (RDM DB) --}}
                <div class="bg-gradient-to-r from-slate-50 to-telkom-50/50 border border-slate-200 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calculator" class="w-4 h-4 text-telkom-500"></i>
                            <span class="text-xs font-bold text-telkom-gray-800">Preview Direct Cost (Otomatis RDM RSO)</span>
                        </div>
                        <span class="bg-telkom-100 text-telkom-800 text-[11px] font-semibold px-2 py-0.5 rounded-full">
                            RDM OTC: {{ number_format($rdm->rdm_otc_pct, 1) }}% | RDM MRC: {{ number_format($rdm->rdm_mrc_pct, 1) }}%
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="bg-white p-3 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block">COGS Install / OTC</span>
                            <span class="text-sm font-bold text-telkom-gray-800 mt-0.5 block" x-text="'Rp ' + formatRp(cogsOtc)"></span>
                            <span class="text-[10px] text-slate-400">OTC &times; (100% - {{ $rdm->rdm_otc_pct }}%)</span>
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-slate-200">
                            <span class="text-slate-500 block">COGS Monthly / MRC</span>
                            <span class="text-sm font-bold text-telkom-gray-800 mt-0.5 block" x-text="'Rp ' + formatRp(cogsMrc)"></span>
                            <span class="text-[10px] text-slate-400">MRC &times; (100% - {{ $rdm->rdm_mrc_pct }}%)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input Engineering & CAPEX (Only shown for ED / Admin) --}}
            @if(auth()->user()->hasRole('engineering_capex') || auth()->user()->hasRole('admin'))
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Parameter Pihak A (Engineering & CAPEX)</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Biaya Material (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'material')" x-on:focus="selectAll($event)"
                               x-bind:value="display.material"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="material" x-bind:value="raw.material">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.material)"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Biaya Jasa (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'jasa')" x-on:focus="selectAll($event)"
                               x-bind:value="display.jasa"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="jasa" x-bind:value="raw.jasa">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.jasa)"></p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">BOP (%)</label>
                        <input type="number" name="bop_pct" value="{{ old('bop_pct', 40) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none"
                               x-on:input="$el.nextElementSibling.textContent = parseFloat($el.value || 0).toLocaleString('id-ID', {minimumFractionDigits:1, maximumFractionDigits:2}) + '%'">
                        <p class="text-xs text-telkom-gray-400 mt-1">{{ number_format(old('bop_pct', 40), 1, ',', '.') }}%</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Lifetime (Tahun)</label>
                        <input type="number" name="lifetime" value="{{ old('lifetime', 4) }}" required min="1" max="20"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none"
                               x-on:input="$el.nextElementSibling.textContent = ($el.value || 0) + ' tahun'">
                        <p class="text-xs text-telkom-gray-400 mt-1">{{ old('lifetime', 4) }} tahun</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">O&M (%)</label>
                        <input type="number" name="om_pct" value="{{ old('om_pct', 12) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none"
                               x-on:input="$el.nextElementSibling.textContent = parseFloat($el.value || 0).toLocaleString('id-ID', {minimumFractionDigits:1, maximumFractionDigits:2}) + '%'">
                        <p class="text-xs text-telkom-gray-400 mt-1">{{ number_format(old('om_pct', 12), 1, ',', '.') }}%</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Pajak / Tax (%)</label>
                        <input type="number" name="tax_pct" value="{{ old('tax_pct', 22) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none"
                               x-on:input="$el.nextElementSibling.textContent = parseFloat($el.value || 0).toLocaleString('id-ID', {minimumFractionDigits:1, maximumFractionDigits:2}) + '%'">
                        <p class="text-xs text-telkom-gray-400 mt-1">{{ number_format(old('tax_pct', 22), 1, ',', '.') }}%</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">WACC (%)</label>
                        <input type="number" name="wacc_pct" value="{{ old('wacc_pct', 10.9) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none"
                               x-on:input="$el.nextElementSibling.textContent = parseFloat($el.value || 0).toLocaleString('id-ID', {minimumFractionDigits:1, maximumFractionDigits:2}) + '%'">
                        <p class="text-xs text-telkom-gray-400 mt-1">{{ number_format(old('wacc_pct', 10.9), 1, ',', '.') }}%</p>
                    </div>
                </div>
            </div>
            @endif

            <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-md text-sm">
                @if(auth()->user()->hasRole('sales_marketing'))
                    Kirim Simulasi ke Engineering (ED) &rarr;
                @else
                    Hitung & Simpan Simulasi
                @endif
            </button>
        </form>
    </div>

    <script>
    function currencyForm() {
        const rdmOtcPct = {{ $rdm->rdm_otc_pct }};
        const rdmMrcPct = {{ $rdm->rdm_mrc_pct }};

        const initRaw = {
            otc: {{ old('otc', 0) }},
            mrc: {{ old('mrc', 0) }},
            material: {{ old('material', 0) }},
            jasa: {{ old('jasa', 0) }},
        };

        return {
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, v === 0 ? '0' : Number(v).toLocaleString('id-ID')])
            ),

            get cogsOtc() {
                return Math.round(this.raw.otc * (1 - (rdmOtcPct / 100)));
            },

            get cogsMrc() {
                return Math.round(this.raw.mrc * (1 - (rdmMrcPct / 100)));
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
            },

            formatUnit(value, field, unit) {
                let el = document.querySelector(`[name="${field}"]`);
                let v = el ? el.value : value;
                return v + ' ' + unit;
            }
        };
    }
    </script>
</x-layouts.app>
