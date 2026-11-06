<x-layouts.app title="Katalog Price List">
    <div class="space-y-6 max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Katalog Komersial</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-xs text-slate-500">Tarif Resmi & Zonasi Wilayah</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Katalog Price List & Tarif Normal</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Kelola daftar tarif resmi layanan Telkom untuk acuan deteksi diskon otomatis saat input Marketing.
                </p>
            </div>
            @if($totalItems > 0)
            <form method="POST" action="{{ route('admin.price-list.clear') }}" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan SELURUH data Price List?');">
                @csrf
                <button type="submit" class="h-9 px-3.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 hover:border-rose-300 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-xs">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Kosongkan Semua</span>
                </button>
            </form>
            @endif
        </div>

        {{-- Flash Messages --}}
        @if(session('status'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-800 flex items-center gap-2.5 shadow-xs">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
            <span>{{ session('status') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-xs text-rose-800 flex items-center gap-2.5 shadow-xs">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif


        {{-- Row Atas: Ringkasan & Form Upload Excel --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri (2 Kolom): Form Upload Excel --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Upload / Update File Excel Price List</h2>
                        <p class="text-xs text-slate-500">Mendukung format matriks regional Telkom (otomatis update jika tarif diperbarui).</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.price-list.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Paket Layanan Default
                            </label>
                            <input type="text" name="paket_default" placeholder="Contoh: Astinet (opsional jika nama sheet sesuai)"
                                   class="w-full h-10 rounded-lg border border-slate-300 px-3.5 text-xs text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white">
                            <p class="text-[11px] text-slate-400 mt-1">Kosongkan jika tiap Sheet di Excel sudah dinamai nama produk (Astinet, Indibiz, dll).</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Pilih File Excel (.xlsx / .xls) <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required
                                   class="w-full h-10 text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 border border-slate-300 rounded-lg cursor-pointer bg-white">
                            <p class="text-[11px] text-slate-400 mt-1">Maksimal 10 MB.</p>
                        </div>
                    </div>

                    {{-- Panduan Format Tabel Header --}}
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-[11px] text-slate-600 space-y-2" x-data="{ guideTab: 'multi' }">
                        <div class="flex items-center justify-between border-b border-slate-200/70 pb-2">
                            <p class="font-semibold text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="info" class="w-3.5 h-3.5 text-slate-500"></i>
                                <span>Format Header Excel yang Didukung Otomatis:</span>
                            </p>
                            <div class="flex gap-1 bg-slate-200/80 p-0.5 rounded-md text-[10px]">
                                <button type="button" @click="guideTab = 'multi'"
                                        :class="guideTab === 'multi' ? 'bg-white text-slate-800 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                        class="px-2 py-0.5 rounded transition">
                                    Header 2 Baris (Global & Domestik)
                                </button>
                                <button type="button" @click="guideTab = 'single'"
                                        :class="guideTab === 'single' ? 'bg-white text-slate-800 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                                        class="px-2 py-0.5 rounded transition">
                                    Header 1 Baris (Matriks Sederhana)
                                </button>
                            </div>
                        </div>

                        {{-- Tab 1: Header 2 Baris (Global & Domestik) --}}
                        <div x-show="guideTab === 'multi'" class="overflow-x-auto">
                            <table class="min-w-full text-center font-mono text-[10px] bg-white border border-slate-200 rounded">
                                <thead class="bg-slate-100 text-slate-700">
                                    <tr>
                                        <th rowspan="2" class="p-1.5 border-r border-b border-slate-200 align-middle">Bandwidth (Mbps)</th>
                                        <th colspan="2" class="p-1 border-r border-b border-slate-200 bg-sky-50/70 text-sky-800">JaBoDeTaBek</th>
                                        <th colspan="2" class="p-1 border-r border-b border-slate-200 bg-indigo-50/70 text-indigo-800">Jawa</th>
                                        <th colspan="2" class="p-1 border-r border-b border-slate-200 bg-emerald-50/70 text-emerald-800">Sumatera</th>
                                        <th colspan="2" class="p-1 border-b border-slate-200 bg-amber-50/70 text-amber-800">Kalimantan & KTI</th>
                                    </tr>
                                    <tr class="bg-slate-50 text-[9px] text-slate-600 font-semibold">
                                        <th class="p-1 border-r border-slate-200">Global</th>
                                        <th class="p-1 border-r border-slate-200">Domestik</th>
                                        <th class="p-1 border-r border-slate-200">Global</th>
                                        <th class="p-1 border-r border-slate-200">Domestik</th>
                                        <th class="p-1 border-r border-slate-200">Global</th>
                                        <th class="p-1 border-r border-slate-200">Domestik</th>
                                        <th class="p-1 border-r border-slate-200">Global</th>
                                        <th class="p-1">Domestik</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-500 text-[10px]">
                                    <tr>
                                        <td class="p-1.5 border-r border-slate-200 font-bold">10 Mbps</td>
                                        <td class="p-1.5 border-r border-slate-200">15.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">10.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">16.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">11.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">17.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">12.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">18.500.000</td>
                                        <td class="p-1.5">13.500.000</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="text-[10px] text-slate-400 mt-1 italic">
                                * Otomatis membagi menjadi dua varian paket (misal: <strong>Astinet Global</strong> & <strong>Astinet Domestik</strong>) secara cerdas.
                            </p>
                        </div>

                        {{-- Tab 2: Header 1 Baris (Matriks Sederhana) --}}
                        <div x-show="guideTab === 'single'" class="overflow-x-auto" style="display: none;">
                            <table class="min-w-full text-left font-mono text-[10px] bg-white border border-slate-200 rounded">
                                <thead class="bg-slate-100 text-slate-700">
                                    <tr>
                                        <th class="p-1.5 border-r border-slate-200">Bandwidth (Mbps)</th>
                                        <th class="p-1.5 border-r border-slate-200">Jabodetabek</th>
                                        <th class="p-1.5 border-r border-slate-200">Jawa</th>
                                        <th class="p-1.5 border-r border-slate-200">Sumatera</th>
                                        <th class="p-1.5">Kalimantan / KTI</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-500">
                                    <tr>
                                        <td class="p-1.5 border-r border-slate-200 font-bold">10 Mbps</td>
                                        <td class="p-1.5 border-r border-slate-200">10.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">11.000.000</td>
                                        <td class="p-1.5 border-r border-slate-200">12.000.000</td>
                                        <td class="p-1.5">13.500.000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-[10px] text-slate-500 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                            <span>Sistem dilengkapi pengaman cerdas: otomatis menormalkan ejaan wilayah, memotong baris catatan/kaki, dan mencegah data saling menimpa.</span>
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="h-10 px-5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg text-xs transition-colors shadow-xs inline-flex items-center gap-2 cursor-pointer">
                            <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                            <span>Import & Sinkronkan Price List</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Kolom Kanan (1 Kolom): Stats Card --}}
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <p class="text-xs font-semibold text-slate-500">Total Tarif Terdaftar</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalItems, 0, ',', '.') }}</p>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                        <span>Paket Layanan:</span>
                        <span class="font-bold text-slate-800">{{ $uniquePaket->count() }} Paket</span>
                    </div>
                    <div class="mt-1.5 flex items-center justify-between text-xs text-slate-600">
                        <span>Wilayah / Zona:</span>
                        <span class="font-bold text-slate-800">{{ $uniqueWilayah->count() }} Wilayah</span>
                    </div>
                    @php
                        $updatedTarifCount = \App\Models\PriceList::whereNotNull('tarif_lama_mrc')->count();
                        $latestPriceUpdate = \App\Models\PriceList::whereNotNull('tarif_lama_mrc')->latest('updated_at')->first()?->updated_at;
                    @endphp
                    @if($updatedTarifCount > 0)
                    <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex flex-col gap-1 text-xs text-amber-800 bg-amber-50/80 -mx-1 px-2.5 py-1.5 rounded-lg border border-amber-200">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 font-semibold">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-amber-600"></i>
                                Perubahan Tarif:
                            </span>
                            <a href="{{ route('admin.price-list', ['sort' => 'perubahan_tarif']) }}" class="font-black underline hover:text-amber-900" title="Klik untuk memfilter daftar tarif yang diupdate">
                                {{ $updatedTarifCount }} Item Diupdate
                            </a>
                        </div>
                        @if($latestPriceUpdate)
                        <div class="text-[10px] text-amber-700 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i>
                            <span>Update Terakhir: <strong>{{ $latestPriceUpdate->translatedFormat('d M Y, H:i') }} WIB</strong> ({{ $latestPriceUpdate->diffForHumans() }})</span>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Tarif Standar OTC Tahunan Marketing --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs space-y-3" x-data="{ editingOtc: false, otcVal: '{{ number_format($rdmMaster->default_otc ?? 2500000, 0, ',', '.') }}', otcRaw: {{ $rdmMaster->default_otc ?? 2500000 }} }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center border border-red-100">
                                <i data-lucide="tag" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-900">Standar Tarif OTC (Marketing)</h3>
                                <p class="text-[10px] text-slate-400">Default otomatis tahunan form marketing</p>
                            </div>
                        </div>
                        <button type="button" @click="editingOtc = !editingOtc" class="text-xs text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 cursor-pointer">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span x-text="editingOtc ? 'Batal' : 'Ubah'"></span>
                        </button>
                    </div>

                    <div x-show="!editingOtc">
                        <p class="text-2xl font-black font-mono text-slate-900">Rp {{ number_format($rdmMaster->default_otc ?? 2500000, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Nilai ini otomatis terisi saat Marketing membuka form komersial, dan tetap dapat diedit oleh Marketing jika ada negosiasi proyek.
                        </p>
                    </div>

                    <form x-show="editingOtc" method="POST" action="{{ route('admin.parameter.update-otc') }}" class="space-y-3 pt-1" style="display: none;">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tarif Standar OTC Baru (Rp)</label>
                            <input type="text" inputmode="numeric"
                                   x-on:input="let d = $event.target.value.replace(/\D/g, ''); otcRaw = parseInt(d, 10) || 0; otcVal = d === '' ? '' : otcRaw.toLocaleString('id-ID'); $nextTick(() => { $event.target.value = otcVal; });"
                                   :value="otcVal" required
                                   class="w-full h-9 rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-900 text-right focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white font-mono">
                            <input type="hidden" name="default_otc" :value="otcRaw">
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" @click="editingOtc = false" class="px-3 h-8 rounded-lg border border-slate-200 text-xs text-slate-600 hover:bg-slate-50 cursor-pointer">Batal</button>
                            <button type="submit" class="px-3.5 h-8 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs cursor-pointer">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>

                <div class="bg-gradient-to-br from-red-50 to-amber-50 rounded-xl border border-red-100 p-4 text-xs text-slate-700 space-y-2">
                    <div class="flex items-center gap-1.5 font-bold text-red-900">
                        <i data-lucide="sparkles" class="w-4 h-4 text-red-600"></i>
                        <span>Otomatisasi Diskon Marketing</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-600">
                        Data tarif di tabel ini akan otomatis dibaca saat Divisi Marketing melengkapi form simulasi. Jika marketing input harga lebih rendah dari tarif ini, persentase diskon dan sisa profit 30% akan langsung dihitung secara real-time.
                    </p>
                </div>
            </div>
        </div>

        {{-- Filter & Daftar Tabel --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-slate-500"></i>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Tarif Normal</h2>
                    @if(request('paket') || request('wilayah') || request('search'))
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-800">
                            {{ $priceLists->total() }} Data Terfilter
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('admin.price-list') }}" class="flex flex-wrap items-center gap-2 flex-1 lg:flex-initial">
                        <select name="sort" onchange="this.form.submit()" class="h-8 rounded-lg border border-slate-300 px-2.5 text-xs text-slate-700 bg-white">
                            <option value="terbaru" {{ request('sort', 'terbaru') === 'terbaru' ? 'selected' : '' }}>Terbaru Diupdate</option>
                            <option value="perubahan_tarif" {{ request('sort') === 'perubahan_tarif' ? 'selected' : '' }}>Hanya Perubahan Tarif</option>
                            <option value="abjad" {{ request('sort') === 'abjad' ? 'selected' : '' }}>Nama Paket (A-Z)</option>
                        </select>

                        <select name="paket" id="filterPaket" class="h-8 rounded-lg border border-slate-300 px-2.5 text-xs text-slate-700 bg-white">
                            <option value="">-- Semua Paket --</option>
                            @foreach($uniquePaket as $pkt)
                                <option value="{{ $pkt }}" {{ request('paket') === $pkt ? 'selected' : '' }}>{{ $pkt }}</option>
                            @endforeach
                        </select>

                        <select name="wilayah" id="filterWilayah" onchange="this.form.submit()" class="h-8 rounded-lg border border-slate-300 px-2.5 text-xs text-slate-700 bg-white">
                            <option value="">-- Semua Wilayah --</option>
                            @foreach($uniqueWilayah as $wly)
                                <option value="{{ $wly }}" {{ request('wilayah') === $wly ? 'selected' : '' }}>{{ $wly }}</option>
                            @endforeach
                        </select>

                        <div class="relative flex-1 sm:w-44">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..."
                                   class="w-full h-8 pl-7 pr-2.5 rounded-lg border border-slate-300 text-xs text-slate-900 focus:border-red-600 outline-none bg-white">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2"></i>
                        </div>

                        @if(request('paket') || request('wilayah') || request('search') || request('sort') === 'abjad' || request('sort') === 'perubahan_tarif')
                        <a href="{{ route('admin.price-list') }}" class="h-8 px-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium inline-flex items-center">
                            Reset
                        </a>
                        @endif
                    </form>

                    {{-- Tombol Hapus Paket / Filter Aktif --}}
                    @if((request('paket') || request('wilayah') || request('search')) && $priceLists->total() > 0)
                    <form method="POST" action="{{ route('admin.price-list.delete-filtered') }}"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh ({{ $priceLists->total() }}) data tarif yang sesuai filter saat ini ({{ request('paket') ? 'Paket: ' . request('paket') : 'Filter Terpilih' }})? Tindakan ini tidak dapat dibatalkan.');"
                          class="inline-block">
                        @csrf
                        @if(request('paket')) <input type="hidden" name="paket" value="{{ request('paket') }}"> @endif
                        @if(request('wilayah')) <input type="hidden" name="wilayah" value="{{ request('wilayah') }}"> @endif
                        @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                        <button type="submit" class="h-8 px-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 hover:border-rose-400 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition-colors shadow-xs" title="Hapus semua data tarif yang sesuai dengan filter saat ini">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5 text-rose-600"></i>
                            <span>Hapus {{ request('paket') ? 'Paket: ' . request('paket') : 'Hasil Filter' }} ({{ $priceLists->total() }})</span>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-semibold">
                        <tr>
                            <th class="py-3 px-4 text-left">Paket Layanan</th>
                            <th class="py-3 px-4 text-left">Bandwidth / Kapasitas</th>
                            <th class="py-3 px-4 text-left">Wilayah / Zona</th>
                            <th class="py-3 px-4 text-right">Tarif Normal MRC (Bulan)</th>
                            <th class="py-3 px-4 text-left">Terakhir Diupdate</th>
                            <th class="py-3 px-4 text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @php
                            $justImportedIds = session('just_imported_ids', []);
                            $priceChangedIds = session('price_changed_ids', []);
                            $newlyCreatedIds = session('newly_created_ids', []);
                        @endphp
                        @forelse($priceLists as $item)
                        @php
                            $hasPriceUpdate = in_array($item->id, $priceChangedIds) || (!empty($item->tarif_lama_mrc) && abs((float)$item->tarif_lama_mrc - (float)$item->tarif_normal_mrc) > 0.01);
                            $isNewlyCreated = in_array($item->id, $newlyCreatedIds);
                        @endphp
                        <tr class="{{ $hasPriceUpdate ? 'bg-amber-50/40 hover:bg-amber-100/40 border-l-4 border-l-amber-500' : ($isNewlyCreated ? 'bg-emerald-50/30 hover:bg-emerald-100/30' : 'hover:bg-slate-50/80') }} transition-colors">
                            <td class="py-2.5 px-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <span>{{ $item->paketisasi }}</span>
                                    @if($isNewlyCreated)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shrink-0">
                                        <i data-lucide="plus" class="w-2.5 h-2.5 text-blue-600"></i>
                                        <span>Baru Ditambahkan</span>
                                    </span>
                                    @endif
                                </div>
                                @if($item->keterangan)
                                <span class="mt-0.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ str_contains(strtolower($item->keterangan), 'global') ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    {{ $item->keterangan }}
                                </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 font-mono text-slate-700">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800">{{ $item->bandwidth }}</span>
                                    @if($hasPriceUpdate)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shrink-0 shadow-2xs" title="Tarif untuk bandwidth ini telah diperbarui">
                                        <i data-lucide="refresh-cw" class="w-2.5 h-2.5 text-amber-700"></i>
                                        <span>Update Tarif</span>
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $item->wilayah }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono">
                                @if($hasPriceUpdate && !empty($item->tarif_lama_mrc))
                                    @php
                                        $oldPrice = (float) $item->tarif_lama_mrc;
                                        $newPrice = (float) $item->tarif_normal_mrc;
                                        $diff = $newPrice - $oldPrice;
                                        $pct = $oldPrice > 0 ? abs($diff) / $oldPrice * 100 : 0;
                                    @endphp
                                    <div class="space-y-0.5">
                                        <div class="flex items-center justify-end gap-1.5 text-[11px] text-slate-400">
                                            <span class="line-through">Rp {{ number_format($oldPrice, 0, ',', '.') }}</span>
                                            @if($diff < 0)
                                                <span class="inline-flex items-center px-1 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200" title="Tarif Turun {{ number_format($pct, 1) }}%">
                                                    ↓ -{{ number_format($pct, 1) }}%
                                                </span>
                                            @elseif($diff > 0)
                                                <span class="inline-flex items-center px-1 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200" title="Tarif Naik {{ number_format($pct, 1) }}%">
                                                    ↑ +{{ number_format($pct, 1) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <span class="font-black text-xs text-emerald-700">Rp {{ number_format($newPrice, 0, ',', '.') }}</span>
                                            <span class="inline-flex items-center gap-0.5 px-1 py-0.2 rounded text-[9px] font-bold bg-emerald-600 text-white shadow-2xs" title="Harga Aktif Baru">
                                                <i data-lucide="check" class="w-2.5 h-2.5"></i> Baru
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="font-bold text-slate-900">
                                        Rp {{ number_format($item->tarif_normal_mrc, 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    @if($hasPriceUpdate)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-300 w-fit shadow-2xs">
                                            <i data-lucide="clock" class="w-3 h-3 text-amber-700"></i>
                                            <span>{{ $item->updated_at->diffForHumans() }}</span>
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-mono mt-0.5" title="Waktu update persis">
                                            {{ $item->updated_at->translatedFormat('d M Y, H:i') }} WIB
                                        </span>
                                    @else
                                        <span class="text-[11px] font-medium text-slate-700">
                                            {{ $item->updated_at->translatedFormat('d M Y, H:i') }} WIB
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            {{ $item->updated_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Edit Button --}}
                                    <button type="button" 
                                            onclick="openEditPriceModal({{ $item->id }}, '{{ addslashes($item->paketisasi) }}', '{{ addslashes($item->bandwidth) }}', '{{ addslashes($item->wilayah) }}', {{ (float)$item->tarif_normal_mrc }}, '{{ addslashes($item->keterangan ?? '') }}', '{{ $item->updated_at->translatedFormat('d M Y, H:i') }} WIB ({{ $item->updated_at->diffForHumans() }})')"
                                            class="text-slate-400 hover:text-blue-600 p-1 rounded hover:bg-blue-50 transition-colors" 
                                            title="Edit Tarif">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>

                                    {{-- Delete Form --}}
                                    <form method="POST" action="{{ route('admin.price-list.destroy', $item) }}" onsubmit="return confirm('Hapus tarif ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-600 p-1 rounded hover:bg-rose-50 transition-colors" title="Hapus">
                                            <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Belum ada data tarif resmi. Silakan upload file Excel price list di atas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($priceLists->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $priceLists->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- Modal Edit Tarif (Single Item) --}}
    <div id="editPriceModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden transform transition-all">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">Edit Tarif Layanan</h3>
                </div>
                <button type="button" onclick="closeEditPriceModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <form id="editPriceForm" method="POST" action="" class="p-5 space-y-4">
                @csrf
                @method('PATCH')
                
                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Paket Layanan:</span>
                        <span id="modalPaket" class="font-bold text-slate-800"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Bandwidth:</span>
                        <span id="modalBandwidth" class="font-mono font-bold text-slate-800"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Wilayah / Zona:</span>
                        <span id="modalWilayah" class="font-medium text-slate-800"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Terakhir Diupdate:</span>
                        <span id="modalUpdatedAt" class="font-medium text-slate-800"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-200">
                        <span>Tarif Saat Ini (Lama):</span>
                        <span id="modalOldPrice" class="font-mono font-bold text-slate-600"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Tarif Baru MRC (Rp / Bulan) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                        <input type="number" name="tarif_normal_mrc" id="modalInputPrice" required min="0" step="1"
                               class="w-full h-9 pl-9 pr-3 rounded-lg border border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Jika diubah, sistem akan otomatis mencatat tarif lama dan memberi tag update pada bandwidth & harga.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Keterangan (Opsional)
                    </label>
                    <input type="text" name="keterangan" id="modalInputKeterangan" placeholder="Contoh: Promo Q4, Tipe Global"
                           class="w-full h-9 px-3 rounded-lg border border-slate-300 text-xs text-slate-900 focus:border-red-600 outline-none">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" onclick="closeEditPriceModal()" class="h-9 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="h-9 px-4 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs inline-flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditPriceModal(id, paket, bandwidth, wilayah, price, keterangan, updatedAt) {
            const modal = document.getElementById('editPriceModal');
            const form = document.getElementById('editPriceForm');
            form.action = `/admin/price-list/${id}`;
            document.getElementById('modalPaket').textContent = paket;
            document.getElementById('modalBandwidth').textContent = bandwidth;
            document.getElementById('modalWilayah').textContent = wilayah;
            document.getElementById('modalUpdatedAt').textContent = updatedAt || '-';
            document.getElementById('modalOldPrice').textContent = 'Rp ' + Number(price).toLocaleString('id-ID');
            document.getElementById('modalInputPrice').value = price;
            document.getElementById('modalInputKeterangan').value = keterangan || '';
            modal.classList.remove('hidden');
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeEditPriceModal() {
            document.getElementById('editPriceModal').classList.add('hidden');
        }

        document.getElementById('editPriceModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditPriceModal();
            }
        });

        // Filter Wilayah Dinamis Sesuai Paket Terpilih
        document.addEventListener('DOMContentLoaded', function () {
            const elPaket = document.getElementById('filterPaket');
            const elWilayah = document.getElementById('filterWilayah');
            const mapPaketWilayah = @json($paketWilayahMap ?? []);

            if (elPaket && elWilayah) {
                elPaket.addEventListener('change', function () {
                    const selectedPaket = this.value;
                    const currentWilayah = elWilayah.value;

                    let availableWilayah = [];
                    if (selectedPaket && mapPaketWilayah[selectedPaket]) {
                        availableWilayah = mapPaketWilayah[selectedPaket];
                    } else if (!selectedPaket) {
                        const setW = new Set();
                        Object.values(mapPaketWilayah).forEach(arr => arr.forEach(w => setW.add(w)));
                        availableWilayah = Array.from(setW).sort();
                    }

                    elWilayah.innerHTML = '<option value="">-- Semua Wilayah --</option>';
                    availableWilayah.forEach(function (w) {
                        const opt = document.createElement('option');
                        opt.value = w;
                        opt.textContent = w;
                        if (w === currentWilayah) opt.selected = true;
                        elWilayah.appendChild(opt);
                    });

                    // Jika wilayah sebelumnya tidak terdaftar di paket yang baru dipilih, kosongkan
                    if (currentWilayah && !availableWilayah.includes(currentWilayah)) {
                        elWilayah.value = '';
                    }

                    this.form.submit();
                });
            }
        });
    </script>
</x-layouts.app>
