<x-layouts.app title="Form Simulasi Baru">
    <div class="space-y-6 max-w-4xl mx-auto" x-data="currencyForm()">
        {{-- Header Section: hanya Engineering Development --}}
        <div class="border-b border-slate-200 pb-5">
            <div class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    Engineer Development (ED)
                </span>
                <span class="text-slate-300">•</span>
                <span class="text-xs text-slate-500 font-medium">Input Parameter Proyek & Biaya CAPEX</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Form Simulasi Engineer Development</h1>
            <p class="text-sm text-slate-500 mt-1">Input data proyek, lokasi, dan estimasi biaya material serta jasa, atau impor file Excel multi-lokasi.</p>
        </div>

        {{-- Pending Alert Banner (simulasi milik ED yang menunggu Marketing) --}}
        @if(isset($pendingSimulasi) && count($pendingSimulasi) > 0)
        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-4 text-sm space-y-3">
            <div class="flex items-center gap-2">
                <i data-lucide="clock" class="w-4 h-4 text-amber-600 shrink-0"></i>
                <h2 class="font-semibold text-amber-900">Simulasi Menunggu Input Komersial (Marketing)</h2>
            </div>
            <p class="text-xs text-amber-800">
                Simulasi berikut telah Anda buat dan menunggu Divisi Marketing mengisi tarif OTC, MRC, dan paketisasi:
            </p>
            <div class="space-y-1.5 pt-1">
                @foreach($pendingSimulasi as $pending)
                    <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-amber-200 text-xs">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-slate-800">{{ $pending->nama_proyek }}</span>
                            @php $creator = $pending->pihakA ?? $pending->pihakB; @endphp
                            @if($creator)
                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Diinput oleh: {{ $creator->name }}">
                                <i data-lucide="user" class="w-3 h-3 text-slate-500"></i>
                                <span>{{ $creator->name }}</span>
                            </span>
                            @endif
                        </div>
                        <a href="{{ route('simulasi.show', $pending) }}" class="text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1">
                            Lihat Detail
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Error / Warning Messages --}}
        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm">
            <div class="flex items-start gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
                <div class="space-y-1">
                    <p class="font-semibold text-rose-900">{{ session('error') }}</p>
                    @if(session('excel_warnings'))
                    <ul class="mt-2 space-y-1 text-xs text-rose-700 list-disc list-inside">
                        @foreach(session('excel_warnings') as $w)
                            <li>{{ $w }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- ========== TAB SWITCHER (Engineering only) ========== --}}
        @if(auth()->user()->hasRole('engineering_capex'))
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 max-w-sm">
            <button type="button"
                @click="activeTab = 'manual'"
                :class="activeTab === 'manual' ? 'bg-white shadow-sm text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs transition-all">
                <i data-lucide="edit-3" class="w-4 h-4 text-slate-500"></i>
                Input Manual
            </button>
            <button type="button"
                @click="activeTab = 'excel'"
                :class="activeTab === 'excel' ? 'bg-white shadow-sm text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs transition-all">
                <i data-lucide="file-spreadsheet" class="w-4 h-4 text-slate-500"></i>
                Import Excel
            </button>
        </div>
        @endif

        {{-- ========== TAB 1: MANUAL INPUT ========== --}}
        <div x-show="activeTab === 'manual'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
            <form method="POST" action="{{ route('simulasi.store') }}" class="bg-white rounded-xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-8">
                @csrf

                {{-- 1. Identitas / Informasi Proyek --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">1</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Informasi Proyek</h2>
                    </div>
                    <div class="grid grid-cols-1 {{ auth()->user()->hasRole('engineering_capex') ? 'sm:grid-cols-2' : '' }} gap-5">
                        <div class="{{ auth()->user()->hasRole('engineering_capex') ? '' : 'sm:col-span-2' }}">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Proyek <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_proyek" value="{{ old('nama_proyek') }}" required placeholder="Contoh: Modernisasi Fiber Optik Witel Jakarta"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>

                        {{-- Khusus ED: Lokasi --}}
                        @if(auth()->user()->hasRole('engineering_capex'))
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi Proyek <span class="text-red-500">*</span></label>
                            <input type="text" name="lokasi" value="{{ old('lokasi') }}" required placeholder="Contoh: Witel Jakarta Barat"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        </div>
                        @endif
                    </div>
                </div>

                {{-- 2. Input Engineering: Biaya Material & Jasa --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
                        <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-700">2</span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Biaya Material dan Jasa (CAPEX)</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Material (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'material')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'material')"
                                   x-bind:value="display.material" required placeholder="0"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="material" x-bind:value="raw.material">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="raw.material !== '' ? 'Rp ' + formatRp(raw.material) : 'Rp 0'"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Biaya Jasa (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" x-on:input="handleCurrency($event, 'jasa')" x-on:focus="selectAll($event)" x-on:blur="handleBlur($event, 'jasa')"
                                   x-bind:value="display.jasa" required placeholder="0"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm font-medium text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                            <input type="hidden" name="jasa" x-bind:value="raw.jasa">
                            <p class="text-xs text-slate-500 mt-1 text-right font-mono" x-text="raw.jasa !== '' ? 'Rp ' + formatRp(raw.jasa) : 'Rp 0'"></p>
                        </div>
                    </div>

                    {{-- Real-time Total Base CAPEX summary card --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Total Base CAPEX (Material + Jasa)</span>
                            <p class="text-xs text-slate-500">Estimasi total penggelaran fisik proyek sebelum komponen non-fisik.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-bold font-mono text-slate-900" x-text="'Rp ' + formatRp((Number(raw.material) || 0) + (Number(raw.jasa) || 0))"></span>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full sm:w-auto px-6 h-11 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors shadow-sm text-sm inline-flex items-center justify-center gap-2">
                        <span>Kirim Simulasi ke Marketing</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>
        </div>

        {{-- ========== TAB 2: EXCEL IMPORT (Engineering only) ========== --}}
        @if(auth()->user()->hasRole('engineering_capex'))
        <div x-show="activeTab === 'excel'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             x-data="excelImport()">

            <div class="bg-white rounded-xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
                <div class="border-b border-slate-200 pb-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="file-spreadsheet" class="w-5 h-5 text-slate-700"></i>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Import Simulasi dari File Excel</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        Unggah file spreadsheet (.xlsx / .xls) sesuai format standar: kolom <strong>REGIONAL, LOP, ID IHLD, Material, Jasa, Total</strong>. Sistem akan membuat simulasi terpisah untuk masing-masing baris lokasi secara otomatis.
                    </p>
                </div>

                <form method="POST" action="{{ route('simulasi.storeExcel') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- Nama Proyek --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Proyek Induk <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_proyek" x-model="namaProyek" required
                               placeholder="Contoh: Penyediaan Layanan Jaringan Tambahan Lokasi Tasikmalaya"
                               class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                        <p class="text-xs text-slate-500 mt-1">Nama proyek ini akan disematkan sebagai identitas seluruh baris lokasi yang diimpor.</p>
                    </div>

                    {{-- File Upload --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih File Excel <span class="text-red-500">*</span></label>
                        <div class="relative border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-red-500 transition-colors cursor-pointer bg-slate-50/50 hover:bg-red-50/20"
                             :class="fileName ? 'border-emerald-400 bg-emerald-50/40' : ''"
                             @dragover.prevent="$el.classList.add('border-red-500', 'bg-red-50/30')"
                             @dragleave.prevent="$el.classList.remove('border-red-500', 'bg-red-50/30')"
                             @drop.prevent="handleDrop($event)"
                             @click="$refs.fileInput.click()">
                            <input type="file" name="excel_file" accept=".xlsx,.xls" required
                                   x-ref="fileInput" class="sr-only"
                                   @change="handleFileSelect($event)">
                            <template x-if="!fileName">
                                <div class="space-y-2.5">
                                    <div class="w-12 h-12 mx-auto bg-slate-100 rounded-xl flex items-center justify-center text-slate-400">
                                        <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">Klik atau drag & drop file Excel di sini</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Format file yang didukung: .xlsx atau .xls (Maksimum 5MB)</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="fileName">
                                <div class="space-y-2">
                                    <div class="w-12 h-12 mx-auto bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600">
                                        <i data-lucide="file-check" class="w-6 h-6"></i>
                                    </div>
                                    <p class="text-sm font-bold text-emerald-900" x-text="fileName"></p>
                                    <p class="text-xs text-emerald-600 font-medium">File siap diproses. Klik untuk memilih file lain.</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Preview Table (client-side parsing via SheetJS) --}}
                    <template x-if="previewRows.length > 0">
                        <div class="space-y-4 pt-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="table" class="w-4 h-4 text-slate-500"></i>
                                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Preview Data Excel (<span x-text="previewRows.length"></span> Lokasi Terdeteksi)
                                    </h3>
                                </div>
                                <template x-if="previewWarnings.length > 0">
                                    <span class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-rose-200">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                                        <span x-text="previewWarnings.length + ' catatan validasi'"></span>
                                    </span>
                                </template>
                                <template x-if="previewWarnings.length === 0">
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-emerald-200">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Semua data valid</span>
                                    </span>
                                </template>
                            </div>

                            {{-- Warnings --}}
                            <template x-if="previewWarnings.length > 0">
                                <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-xs space-y-1.5">
                                    <div class="flex items-center gap-1.5 font-semibold text-rose-800">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                                        <span>Catatan Validasi Data:</span>
                                    </div>
                                    <ul class="list-disc list-inside text-rose-700 space-y-0.5 pl-1">
                                        <template x-for="w in previewWarnings" :key="w">
                                            <li x-text="w"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            {{-- Table --}}
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600">
                                            <th class="px-3 py-2.5 text-left font-semibold">#</th>
                                            <th class="px-3 py-2.5 text-left font-semibold">Regional</th>
                                            <th class="px-3 py-2.5 text-left font-semibold">LOP</th>
                                            <th class="px-3 py-2.5 text-left font-semibold text-slate-900">Lokasi</th>
                                            <th class="px-3 py-2.5 text-left font-semibold">ID IHLD</th>
                                            <th class="px-3 py-2.5 text-right font-semibold">Material</th>
                                            <th class="px-3 py-2.5 text-right font-semibold">Jasa</th>
                                            <th class="px-3 py-2.5 text-right font-semibold">Total Base</th>
                                            <th class="px-3 py-2.5 text-center font-semibold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(row, idx) in previewRows" :key="idx">
                                            <tr :class="row.hasWarning ? 'bg-rose-50/60' : 'hover:bg-slate-50/70 transition-colors'">
                                                <td class="px-3 py-2 text-slate-400" x-text="idx + 1"></td>
                                                <td class="px-3 py-2 font-medium text-slate-700" x-text="row.regional || '-'"></td>
                                                <td class="px-3 py-2 font-medium text-slate-700" x-text="row.lop || '-'"></td>
                                                <td class="px-3 py-2 font-bold text-slate-900" x-text="row.lokasi || '-'"></td>
                                                <td class="px-3 py-2 text-slate-500 font-mono" x-text="row.id_ihld || '-'"></td>
                                                <td class="px-3 py-2 text-right font-mono" :class="row.materialWarning ? 'text-rose-600 font-bold' : 'text-slate-700'"
                                                    x-text="row.material !== null ? formatRpPreview(row.material) : 'Kosong'"></td>
                                                <td class="px-3 py-2 text-right font-mono" :class="row.jasaWarning ? 'text-rose-600 font-bold' : 'text-slate-700'"
                                                    x-text="row.jasa !== null ? formatRpPreview(row.jasa) : 'Kosong'"></td>
                                                <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900"
                                                    x-text="(row.material !== null && row.jasa !== null) ? formatRpPreview((row.material || 0) + (row.jasa || 0)) : '-'"></td>
                                                <td class="px-3 py-2 text-center">
                                                    <span x-show="!row.hasWarning" class="inline-flex items-center text-emerald-700 font-medium text-[11px] bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                        Valid
                                                    </span>
                                                    <span x-show="row.hasWarning" class="inline-flex items-center text-rose-700 font-medium text-[11px] bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                                        Perlu Cek
                                                    </span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot x-show="previewRows.length > 0">
                                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold text-slate-900">
                                            <td colspan="5" class="px-3 py-2.5 text-right text-xs uppercase tracking-wider text-slate-600">Total Keseluruhan</td>
                                            <td class="px-3 py-2.5 text-right font-mono" x-text="formatRpPreview(totalMaterial)"></td>
                                            <td class="px-3 py-2.5 text-right font-mono" x-text="formatRpPreview(totalJasa)"></td>
                                            <td class="px-3 py-2.5 text-right font-mono text-slate-900" x-text="formatRpPreview(totalMaterial + totalJasa)"></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Auto-detect nama proyek info --}}
                            <template x-if="detectedNamaProyek">
                                <div class="bg-blue-50/80 border border-blue-200 rounded-xl p-3.5 text-xs text-blue-800 flex items-center gap-2.5">
                                    <i data-lucide="info" class="w-4 h-4 shrink-0 text-blue-600"></i>
                                    <span>Nama proyek terdeteksi dari dokumen: <strong x-text="detectedNamaProyek"></strong></span>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Submit --}}
                    <div class="pt-2">
                        <button type="submit" class="w-full sm:w-auto px-6 h-11 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors shadow-sm text-sm inline-flex items-center justify-center gap-2"
                                :disabled="previewRows.length === 0 || previewWarnings.length > 0 || !namaProyek">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span x-text="previewRows.length > 0 ? 'Kirim ' + previewRows.length + ' Lokasi ke Marketing' : 'Upload Excel & Kirim ke Marketing'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    {{-- SheetJS CDN for client-side Excel preview --}}
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

    <script>
    function currencyForm() {
        const rdmOtcPct = {{ $rdm->rdm_otc_pct }};
        const rdmMrcPct = {{ $rdm->rdm_mrc_pct }};

        const initRaw = {
            otc: {!! json_encode(old('otc', '')) !!},
            mrc: {!! json_encode(old('mrc', '')) !!},
            material: {!! json_encode(old('material', '')) !!},
            jasa: {!! json_encode(old('jasa', '')) !!},
        };

        return {
            activeTab: 'manual',
            raw: { ...initRaw },
            display: Object.fromEntries(
                Object.entries(initRaw).map(([k, v]) => [k, (v === '' || v === null) ? '' : Number(v).toLocaleString('id-ID')])
            ),

            get cogsOtc() {
                return Math.round((Number(this.raw.otc) || 0) * (1 - (rdmOtcPct / 100)));
            },

            get cogsMrc() {
                return Math.round((Number(this.raw.mrc) || 0) * (1 - (rdmMrcPct / 100)));
            },

            get totalCapexBase() {
                return (Number(this.raw.material) || 0) + (Number(this.raw.jasa) || 0);
            },

            handleCurrency(event, field) {
                let digits = event.target.value.replace(/\D/g, '');
                if (digits === '') {
                    this.raw[field] = '';
                    this.display[field] = '';
                } else {
                    let num = parseInt(digits, 10) || 0;
                    this.raw[field] = num;
                    this.display[field] = num.toLocaleString('id-ID');
                }

                this.$nextTick(() => {
                    event.target.value = this.display[field];
                });
            },

            handleBlur(event, field) {
                if (!this.display[field] || this.display[field] === '') {
                    this.display[field] = '';
                    this.raw[field] = '';
                    event.target.value = '';
                }
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
                return (v || 0) + ' ' + unit;
            }
        };
    }

    function excelImport() {
        return {
            fileName: '',
            namaProyek: '{{ old("nama_proyek", "") }}',
            previewRows: [],
            previewWarnings: [],
            detectedNamaProyek: '',
            totalMaterial: 0,
            totalJasa: 0,

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) this.parseFile(file);
            },

            handleDrop(event) {
                event.target.classList.remove('border-telkom-500', 'bg-telkom-50');
                const file = event.dataTransfer.files[0];
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.fileInput.files = dt.files;
                    this.parseFile(file);
                }
            },

            parseFile(file) {
                this.fileName = file.name;
                this.previewRows = [];
                this.previewWarnings = [];
                this.detectedNamaProyek = '';
                this.totalMaterial = 0;
                this.totalJasa = 0;

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const workbook = XLSX.read(e.target.result, { type: 'array' });
                        const sheetName = workbook.SheetNames[0];
                        const sheet = workbook.Sheets[sheetName];
                        const data = XLSX.utils.sheet_to_json(sheet, { header: 'A', defval: '' });

                        this.processExcelData(data);
                    } catch (err) {
                        this.previewWarnings = ['Gagal membaca file Excel: ' + err.message];
                    }
                };
                reader.readAsArrayBuffer(file);
            },

            processExcelData(data) {
                let headerRowIdx = -1;
                let colMap = {};

                for (let i = 0; i < data.length; i++) {
                    const row = data[i];
                    let tempMap = {};
                    for (const [col, val] of Object.entries(row)) {
                        const lv = String(val).toLowerCase().trim();
                        if (lv.includes('regional')) tempMap.regional = col;
                        if (lv === 'lop' || (lv.includes('lop') && !lv.includes('lokasi'))) tempMap.lop = col;
                        if (lv.includes('lokasi') || lv.includes('location') || lv.includes('kota')) tempMap.lokasi = col;
                        if (lv.includes('id ihld') || lv.includes('ihld')) tempMap.id_ihld = col;
                        if (lv.includes('material')) tempMap.material = col;
                        if (lv.includes('jasa')) tempMap.jasa = col;
                    }

                    if (tempMap.material && tempMap.jasa) {
                        headerRowIdx = i;
                        colMap = tempMap;
                        break;
                    }

                    // Try detect nama proyek from non-header rows (panjang > 10, bukan 'investasi')
                    const values = Object.values(row).map(v => String(v).trim());
                    const longVal = values.find(v => v.length > 10 && v.toLowerCase() !== 'investasi');
                    if (longVal && !this.detectedNamaProyek) {
                        this.detectedNamaProyek = longVal;
                        if (!this.namaProyek) {
                            this.namaProyek = longVal;
                        }
                    }
                }

                if (headerRowIdx === -1) {
                    this.previewWarnings = ['Kolom "Material" dan "Jasa" tidak ditemukan di file Excel. Pastikan header tabel sesuai format.'];
                    return;
                }

                const rows = [];
                const warnings = [];
                let totalM = 0, totalJ = 0;
                let currentRegional = '';
                let currentLop = '';

                for (let i = headerRowIdx + 1; i < data.length; i++) {
                    const row = data[i];

                    // Skip "Total" row jika ada cell yang isinya "total"
                    let isTotalRow = false;
                    for (const val of Object.values(row)) {
                        const cellStr = String(val).toLowerCase().trim();
                        if (cellStr === 'total' || cellStr.startsWith('total')) {
                            isTotalRow = true;
                            break;
                        }
                    }
                    if (isTotalRow) continue;

                    const regionalRaw = colMap.regional ? String(row[colMap.regional] ?? '').trim() : '';
                    const lopRaw = colMap.lop ? String(row[colMap.lop] ?? '').trim() : '';
                    const lokasiRaw = colMap.lokasi ? String(row[colMap.lokasi] ?? '').trim() : '';
                    const idIhldRaw = colMap.id_ihld ? String(row[colMap.id_ihld] ?? '').trim() : '';
                    const materialRaw = colMap.material ? row[colMap.material] : null;
                    const jasaRaw = colMap.jasa ? row[colMap.jasa] : null;

                    const material = this.parseNum(materialRaw);
                    const jasa = this.parseNum(jasaRaw);

                    // Skip empty spacer rows: jika material & jasa kosong dan lokasi kosong
                    if (material === null && jasa === null && !lokasiRaw && !lopRaw && !regionalRaw) {
                        continue;
                    }
                    if (material === null && jasa === null && !lokasiRaw) {
                        continue;
                    }

                    // Forward-fill REGIONAL & LOP (1 LOP dengan banyak lokasi)
                    if (regionalRaw) currentRegional = regionalRaw;
                    if (lopRaw) currentLop = lopRaw;

                    const regional = regionalRaw || currentRegional;
                    const lop = lopRaw || currentLop;
                    const lokasi = lokasiRaw;
                    const id_ihld = idIhldRaw;

                    const rIdx = rows.length + 1;
                    let hasWarning = false;
                    let materialWarning = false, jasaWarning = false, lokasiWarning = false;
                    const lokasiLabel = lokasi || (lop ? (lop + ' (Baris ' + rIdx + ')') : ('Baris ' + rIdx));

                    if (material === null || material < 0) {
                        warnings.push('Lokasi "' + lokasiLabel + '": Material kosong atau bukan angka valid');
                        hasWarning = true;
                        materialWarning = true;
                    }
                    if (jasa === null || jasa < 0) {
                        warnings.push('Lokasi "' + lokasiLabel + '": Jasa kosong atau bukan angka valid');
                        hasWarning = true;
                        jasaWarning = true;
                    }
                    if (!lop && !lokasi) {
                        warnings.push('Baris ' + rIdx + ': LOP atau Lokasi tidak boleh kosong');
                        hasWarning = true;
                        lokasiWarning = true;
                    }

                    totalM += (material || 0);
                    totalJ += (jasa || 0);

                    rows.push({
                        regional,
                        lop,
                        lokasi,
                        id_ihld,
                        material,
                        jasa,
                        hasWarning,
                        materialWarning,
                        jasaWarning,
                        lokasiWarning
                    });
                }

                this.previewRows = rows;
                this.previewWarnings = warnings;
                this.totalMaterial = totalM;
                this.totalJasa = totalJ;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            },

            parseNum(val) {
                if (val === null || val === undefined || String(val).trim() === '') return null;
                if (typeof val === 'number') return val;
                let cleaned = String(val).replace(/\./g, '').replace(/\s/g, '').replace(',', '.');
                const num = parseFloat(cleaned);
                return isNaN(num) ? null : num;
            },

            formatRpPreview(value) {
                if (value === null || value === undefined) return '-';
                return 'Rp ' + Number(value).toLocaleString('id-ID');
            }
        };
    }
    </script>
</x-layouts.app>
