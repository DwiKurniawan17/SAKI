<x-layouts.app title="Riwayat Simulasi">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Riwayat Simulasi AKI/CAPEX</h1>
                <p class="text-sm text-slate-500 mt-1">Daftar seluruh rekam simulasi investasi proyek dan status evaluasi kelayakan.</p>
            </div>
            @if(auth()->user()->hasAnyRole(['sales_marketing', 'engineering_capex']))
            <a href="{{ route('simulasi.create') }}" class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 h-10 rounded-lg transition-colors shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat Simulasi Baru</span>
            </a>
            @endif
        </div>

        {{-- Search & Filter Bar --}}
        <form method="GET" action="{{ route('simulasi.riwayat') }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama proyek atau lokasi..."
                           class="w-full h-10 rounded-lg border border-slate-300 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition">
                </div>
                <select name="status" class="h-10 rounded-lg border border-slate-300 px-3 text-xs text-slate-700 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none bg-white min-w-[200px]">
                    <option value="">Semua Status</option>
                    @php
                        $filterStatuses = [
                            \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_B  => 'Menunggu Marketing',
                            \App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW   => 'Menunggu Review RSO',
                            \App\Models\SimulasiAki::STATUS_REVISI             => 'Perlu Revisi',
                            \App\Models\SimulasiAki::STATUS_APPROVED           => 'Disetujui',
                        ];
                    @endphp
                    @foreach($filterStatuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-medium px-5 h-10 rounded-lg text-xs transition-colors shadow-sm">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Cari</span>
                </button>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            @if(count($simulasis) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px]">Nama Proyek</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px]">Status Alur</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px]">Hasil Evaluasi</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px] text-right">Nilai NPV</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($simulasis as $item)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0 mt-0.5 border border-slate-200">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('simulasi.show', $item) }}" class="font-semibold text-slate-900 hover:text-red-600 transition-colors">
                                            {{ $item->nama_proyek }}
                                        </a>
                                        <div class="flex items-center gap-2 flex-wrap mt-1">
                                            {{-- ED (Penginput Proyek & CAPEX) --}}
                                            @if($item->pihakA)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Diinput oleh Engineering (ED): {{ $item->pihakA->name }}">
                                                <i data-lucide="wrench" class="w-3 h-3 text-slate-500"></i>
                                                <span class="text-slate-500 text-[10px] font-semibold">ED:</span>
                                                <span class="font-medium">{{ $item->pihakA->name }}</span>
                                                @if(auth()->id() === $item->pihakA->id)
                                                <span class="text-[10px] text-slate-400 font-normal">(Anda)</span>
                                                @endif
                                            </span>
                                            @endif

                                            {{-- Marketing (Pengisi / Pengupdate Komersial OTC, MRC) --}}
                                            @if($item->pihakB)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200" title="Dilengkapi / Diupdate oleh Marketing: {{ $item->pihakB->name }}">
                                                <i data-lucide="tag" class="w-3 h-3 text-blue-500"></i>
                                                <span class="text-blue-500 text-[10px] font-semibold">MKT:</span>
                                                <span class="font-medium">{{ $item->pihakB->name }}</span>
                                                @if(auth()->id() === $item->pihakB->id)
                                                <span class="text-[10px] text-blue-400 font-normal">(Anda)</span>
                                                @endif
                                            </span>
                                            @elseif($item->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_B)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/80" title="Menunggu Divisi Marketing mengisi tarif OTC, MRC & paketisasi">
                                                <i data-lucide="clock" class="w-3 h-3 text-amber-500"></i>
                                                <span>Menunggu Marketing</span>
                                            </span>
                                            @endif

                                            <span class="text-slate-300">•</span>
                                            @if($item->lokasi)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                                                <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                                                <span>{{ $item->lokasi }}</span>
                                            </span>
                                            <span class="text-slate-300">•</span>
                                            @endif
                                            @if($item->bandwidth)
                                            <span class="inline-flex items-center gap-1 text-[11px] text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded font-medium border border-indigo-100">
                                                <i data-lucide="activity" class="w-3 h-3 text-indigo-500"></i>
                                                <span>{{ $item->bandwidth }}</span>
                                            </span>
                                            <span class="text-slate-300">•</span>
                                            @endif
                                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-400" title="{{ $item->created_at->translatedFormat('l, d F Y - H:i:s') }} WIB">
                                                <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>
                                                <span>{{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB &bull; <strong class="font-medium text-slate-500">{{ $item->created_at->diffForHumans() }}</strong></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                @php
                                    $statusColors = [
                                        'menunggu_review' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'revisi' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'menunggu_pihak_a' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'menunggu_pihak_b' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'draft_pihak_b' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'lengkap_terhitung' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    ];
                                    $statusText = $item->status_label;
                                    $badgeColor = $statusColors[$item->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    if ($item->status === \App\Models\SimulasiAki::STATUS_REVISI) {
                                        if (auth()->user()->hasRole('admin')) {
                                            $statusText = 'Waiting Revisi Marketing';
                                            $badgeColor = 'bg-amber-50 text-amber-800 border-amber-200';
                                        } else {
                                            $statusText = 'Perlu Revisi (Belum Layak)';
                                            $badgeColor = 'bg-rose-50 text-rose-700 border-rose-200';
                                        }
                                    }
                                @endphp
                                <span class="{{ $badgeColor }} border px-2.5 py-0.5 rounded-md inline-block font-semibold text-[11px] whitespace-nowrap">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                @if($item->status === \App\Models\SimulasiAki::STATUS_APPROVED)
                                    @if($item->layak === true)
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-md inline-flex items-center gap-1 font-bold text-[11px] whitespace-nowrap">
                                            <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                            <span>LAYAK</span>
                                        </span>
                                    @else
                                        <span class="bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 rounded-md inline-flex items-center gap-1 font-bold text-[11px] whitespace-nowrap">
                                            <i data-lucide="x-circle" class="w-3 h-3"></i>
                                            <span>TIDAK LAYAK</span>
                                        </span>
                                    @endif
                                @elseif(in_array($item->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
                                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded-md inline-flex items-center gap-1 font-medium text-[11px] whitespace-nowrap">
                                        <i data-lucide="clock" class="w-3 h-3 text-blue-600"></i>
                                        <span>Menunggu RSO</span>
                                    </span>
                                @elseif($item->status === \App\Models\SimulasiAki::STATUS_REVISI)
                                    <span class="bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 rounded-md inline-flex items-center gap-1 font-bold text-[11px] whitespace-nowrap">
                                        <i data-lucide="alert-circle" class="w-3 h-3 text-rose-600"></i>
                                        <span>BELUM LAYAK (REVISI)</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right font-mono font-semibold {{ ($item->npv ?? 0) >= 0 ? 'text-slate-900' : 'text-rose-600' }}">
                                {{ $item->npv ? 'Rp ' . number_format($item->npv, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">

                                    {{-- Edit: User hanya saat status revisi miliknya --}}
                                    @if($item->status === \App\Models\SimulasiAki::STATUS_REVISI && (auth()->user()->hasRole('sales_marketing') || $item->user_id_pihak_b === auth()->id()))
                                    <a href="{{ route('simulasi.edit', $item) }}"
                                       class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-semibold text-xs transition-colors">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                        <span>Revisi</span>
                                    </a>
                                    @endif

                                    {{-- Edit (admin bisa edit simulasi apapun yang belum approved) --}}
                                    @if(auth()->user()->hasRole('admin') && $item->status !== \App\Models\SimulasiAki::STATUS_APPROVED)
                                    <a href="{{ route('simulasi.edit', $item) }}"
                                       class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-700 font-semibold text-xs transition-colors">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        <span>Edit</span>
                                    </a>
                                    @endif

                                    {{-- Detail --}}
                                    <a href="{{ route('simulasi.show', $item) }}"
                                       class="text-red-600 hover:text-red-700 font-semibold text-xs transition-colors inline-flex items-center gap-0.5">
                                        <span>Detail</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>

                                    {{-- Hapus: HANYA Engineering, HANYA simulasi miliknya, HANYA saat status menunggu_pihak_b.
                                         Admin boleh hapus semua. Marketing TIDAK boleh hapus. --}}
                                    @if(auth()->user()->hasRole('admin') ||
                                        (auth()->user()->hasRole('engineering_capex')
                                         && $item->user_id_pihak_a === auth()->id()
                                         && $item->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_B))
                                    <form method="POST" action="{{ route('simulasi.destroy', $item) }}"
                                          onsubmit="return confirm('Hapus simulasi \'{{ addslashes($item->nama_proyek) }}\' secara permanen?')"
                                          class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-rose-500 hover:text-rose-700 font-semibold text-xs transition-colors cursor-pointer">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $simulasis->links() }}
            </div>
            @else
            <div class="p-12 text-center text-slate-400 text-sm flex flex-col items-center">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 mb-3 border border-slate-200">
                    <i data-lucide="search-x" class="w-6 h-6"></i>
                </div>
                <p class="font-medium text-slate-600">Tidak ada data simulasi ditemukan.</p>
                <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci pencarian atau filter status yang berbeda.</p>
            </div>
            @endif
        </div>
    </div>
</x-layouts.app>
