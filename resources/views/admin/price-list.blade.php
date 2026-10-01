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
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-slate-500"></i>
                    <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Tarif Normal</h2>
                </div>

                {{-- Filter Form --}}
                <form method="GET" action="{{ route('admin.price-list') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <select name="sort" onchange="this.form.submit()" class="h-8 rounded-lg border border-slate-300 px-2.5 text-xs text-slate-700 bg-white">
                        <option value="terbaru" {{ request('sort', 'terbaru') === 'terbaru' ? 'selected' : '' }}>Terbaru Diupdate</option>
                        <option value="abjad" {{ request('sort') === 'abjad' ? 'selected' : '' }}>Nama Paket (A-Z)</option>
                    </select>

                    <select name="paket" onchange="this.form.submit()" class="h-8 rounded-lg border border-slate-300 px-2.5 text-xs text-slate-700 bg-white">
                        <option value="">-- Semua Paket --</option>
                        @foreach($uniquePaket as $pkt)
                            <option value="{{ $pkt }}" {{ request('paket') === $pkt ? 'selected' : '' }}>{{ $pkt }}</option>
                        @endforeach
                    </select>

                    <select name="wilayah" onchange="this.form.submit()" class="h-8 rounded-lg border border-slate-300 px-2.5 text-xs text-slate-700 bg-white">
                        <option value="">-- Semua Wilayah --</option>
                        @foreach($uniqueWilayah as $wly)
                            <option value="{{ $wly }}" {{ request('wilayah') === $wly ? 'selected' : '' }}>{{ $wly }}</option>
                        @endforeach
                    </select>

                    <div class="relative flex-1 sm:w-48">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..."
                               class="w-full h-8 pl-7 pr-2.5 rounded-lg border border-slate-300 text-xs text-slate-900 focus:border-red-600 outline-none bg-white">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-2"></i>
                    </div>

                    @if(request('paket') || request('wilayah') || request('search') || request('sort') === 'abjad')
                    <a href="{{ route('admin.price-list') }}" class="h-8 px-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium inline-flex items-center">
                        Reset
                    </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-semibold">
                        <tr>
                            <th class="py-3 px-4 text-left">Paket Layanan</th>
                            <th class="py-3 px-4 text-left">Bandwidth / Kapasitas</th>
                            <th class="py-3 px-4 text-left">Wilayah / Zona</th>
                            <th class="py-3 px-4 text-right">Tarif Normal MRC (Bulan)</th>
                            <th class="py-3 px-4 text-center w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @php
                            $justImportedIds = session('just_imported_ids', []);
                        @endphp
                        @forelse($priceLists as $item)
                        @php
                            $isJustUpdated = in_array($item->id, $justImportedIds) || ($item->updated_at && $item->updated_at->diffInMinutes() <= 10);
                        @endphp
                        <tr class="{{ $isJustUpdated ? 'bg-emerald-50/50 hover:bg-emerald-100/50' : 'hover:bg-slate-50/80' }} transition-colors">
                            <td class="py-2.5 px-4 font-semibold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <span>{{ $item->paketisasi }}</span>
                                    @if($isJustUpdated)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                                        <i data-lucide="sparkles" class="w-3 h-3 text-emerald-600"></i>
                                        <span>Baru Diupdate</span>
                                    </span>
                                    @endif
                                </div>
                                @if($item->keterangan)
                                <span class="mt-0.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ str_contains(strtolower($item->keterangan), 'global') ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    {{ $item->keterangan }}
                                </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 font-mono text-slate-700">{{ $item->bandwidth }}</td>
                            <td class="py-2.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $item->wilayah }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                                <span class="{{ $isJustUpdated ? 'text-emerald-700 font-black' : '' }}">
                                    Rp {{ number_format($item->tarif_normal_mrc, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                <form method="POST" action="{{ route('admin.price-list.destroy', $item) }}" onsubmit="return confirm('Hapus tarif ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 p-1 rounded hover:bg-rose-50 transition-colors" title="Hapus">
                                        <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
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
</x-layouts.app>
