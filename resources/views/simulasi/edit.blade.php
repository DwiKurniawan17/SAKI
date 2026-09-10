<x-layouts.app title="Revisi Simulasi">
    <div class="space-y-6 max-w-2xl mx-auto" x-data="currencyForm()">
        <div>
            <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Revisi Simulasi: {{ $simulasi->nama_proyek }}</h1>
            <p class="text-slate-500" style="font-size: 0.85rem;">Perbaiki parameter sesuai catatan RSO, lalu kirim ulang.</p>
        </div>

        @if($simulasi->catatan_revisi)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 space-y-2">
            <p class="text-sm font-bold text-red-800">📝 Catatan Revisi dari RSO:</p>
            <p class="text-sm text-red-700">{{ $simulasi->catatan_revisi }}</p>
        </div>
        @endif

        @php $inp = $simulasi->input_json ?? []; @endphp

        <form method="POST" action="{{ route('simulasi.update', $simulasi) }}" class="bg-white rounded-2xl p-6 border border-telkom-gray-200 shadow-sm space-y-6">
            @csrf
            @method('PUT')

            {{-- Informasi Proyek --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Informasi Proyek</h2>
                <div>
                    <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Nama Proyek *</label>
                    <input type="text" name="nama_proyek" value="{{ old('nama_proyek', $inp['nama_proyek'] ?? $simulasi->nama_proyek) }}" required
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Lokasi Proyek</label>
                    <input type="text" name="lokasi" value="{{ old('lokasi', $inp['lokasi'] ?? $simulasi->lokasi) }}"
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Durasi Kontrak (Bulan) *</label>
                        <input type="number" name="durasi_kontrak" value="{{ old('durasi_kontrak', $inp['durasi_kontrak'] ?? 48) }}" required min="1" max="120"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Bulan di Tahun 1 *</label>
                        <input type="number" name="bulan_tahun1" value="{{ old('bulan_tahun1', $inp['bulan_tahun1'] ?? 6) }}" required min="1" max="12"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                </div>
            </div>

            {{-- Revenue --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Parameter Revenue</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">OTC (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'otc')" x-on:focus="selectAll($event)"
                               x-bind:value="display.otc"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="otc" x-bind:value="raw.otc">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.otc)"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">MRC (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'mrc')" x-on:focus="selectAll($event)"
                               x-bind:value="display.mrc"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="mrc" x-bind:value="raw.mrc">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.mrc)"></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">COGS Install (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'cogs_install')" x-on:focus="selectAll($event)"
                               x-bind:value="display.cogs_install"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="cogs_install" x-bind:value="raw.cogs_install">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.cogs_install)"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">COGS Monthly (Rp)</label>
                        <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'cogs_monthly')" x-on:focus="selectAll($event)"
                               x-bind:value="display.cogs_monthly"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <input type="hidden" name="cogs_monthly" x-bind:value="raw.cogs_monthly">
                        <p class="text-xs text-telkom-gray-400 mt-1" x-text="'Rp ' + formatRp(raw.cogs_monthly)"></p>
                    </div>
                </div>
            </div>

            {{-- CAPEX --}}
            <div class="space-y-4">
                <h2 class="text-sm font-bold text-telkom-gray-800 border-b pb-2">Parameter CAPEX</h2>
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
                        <input type="number" name="bop_pct" value="{{ old('bop_pct', $inp['bop_pct'] ?? 40) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Lifetime (Tahun)</label>
                        <input type="number" name="lifetime" value="{{ old('lifetime', $inp['lifetime'] ?? 4) }}" required min="1" max="20"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">O&M (%)</label>
                        <input type="number" name="om_pct" value="{{ old('om_pct', $inp['om_pct'] ?? 12) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">Pajak (%)</label>
                        <input type="number" name="tax_pct" value="{{ old('tax_pct', $inp['tax_pct'] ?? 22) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">WACC (%)</label>
                        <input type="number" name="wacc_pct" value="{{ old('wacc_pct', $inp['wacc_pct'] ?? 10.9) }}" required step="any"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-md text-sm">
                Hitung Ulang & Kirim untuk Review
            </button>
        </form>
    </div>

    <script>
    function currencyForm() {
        const initRaw = {
            otc: {{ $inp['otc'] ?? 0 }},
            mrc: {{ $inp['mrc'] ?? 0 }},
            cogs_install: {{ $inp['cogs_install'] ?? 0 }},
            cogs_monthly: {{ $inp['cogs_monthly'] ?? 0 }},
            material: {{ $inp['material'] ?? 0 }},
            jasa: {{ $inp['jasa'] ?? 0 }},
        };
        return {
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, v === 0 ? '0' : Number(v).toLocaleString('id-ID')])
            ),
            handleCurrency(event, field) {
                let digits = event.target.value.replace(/\D/g, '');
                let num = parseInt(digits, 10) || 0;
                this.raw[field] = num;
                this.display[field] = num.toLocaleString('id-ID');
                this.$nextTick(() => { event.target.value = this.display[field]; });
            },
            selectAll(event) { event.target.select(); },
            formatRp(value) { return (parseFloat(value) || 0).toLocaleString('id-ID'); },
        };
    }
    </script>
</x-layouts.app>
