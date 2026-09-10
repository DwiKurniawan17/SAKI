<x-layouts.app title="Riwayat Simulasi">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Riwayat Simulasi AKI/CAPEX</h1>
                <p class="text-slate-500" style="font-size: 0.85rem;">Daftar seluruh simulasi investasi yang pernah dibuat.</p>
            </div>
            @if(auth()->user()->hasAnyRole(['sales_marketing', 'engineering_capex']))
            <a href="{{ route('simulasi.create') }}" class="inline-flex items-center justify-center gap-2 bg-telkom-500 hover:bg-telkom-600 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all shadow-md hover:shadow-lg hover:scale-[1.01]">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Simulasi Baru</span>
            </a>
            @endif
        </div>

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('simulasi.riwayat') }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama proyek..."
                           class="w-full rounded-xl border border-telkom-gray-300 pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>
                <select name="status" class="rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none min-w-[180px]">
                    <option value="">Semua Status</option>
                    @foreach(\App\Models\SimulasiAki::statusLabels() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-telkom-gray-800 hover:bg-telkom-900 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-all">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Cari
                </button>
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            @if(count($simulasis) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size: 0.83rem;">
                    <thead>
                        <tr class="text-left border-b border-slate-100">
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Nama Proyek</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Status</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Hasil</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">NPV</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider text-right" style="font-size: 0.7rem;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($simulasis as $item)
                        <tr class="border-b border-slate-50 hover:bg-telkom-50/30 transition-colors">
                            <td class="px-6 py-3.5 font-semibold text-telkom-gray-800">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="file-text" class="w-4 h-4 text-telkom-400 shrink-0"></i>
                                    <div>
                                        {{ $item->nama_proyek }}
                                        <span class="block text-xs text-telkom-gray-400 font-normal">{{ $item->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                @php $statusColors = [
                                    'menunggu_review' => 'bg-indigo-100 text-indigo-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'revisi' => 'bg-amber-100 text-amber-800',
                                    'menunggu_pihak_a' => 'bg-yellow-100 text-yellow-800',
                                    'draft_pihak_b' => 'bg-telkom-gray-100 text-telkom-gray-700',
                                    'lengkap_terhitung' => 'bg-blue-100 text-blue-800',
                                ]; @endphp
                                <span class="{{ $statusColors[$item->status] ?? 'bg-telkom-gray-100 text-telkom-gray-700' }} px-2.5 py-1 rounded-full inline-block font-semibold whitespace-nowrap" style="font-size: 0.72rem;">
                                    {{ $item->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                @if($item->layak === true)
                                    <span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-full inline-block font-bold whitespace-nowrap" style="font-size: 0.72rem;">LAYAK</span>
                                @elseif($item->layak === false)
                                    <span class="bg-red-100 text-red-800 px-2.5 py-1 rounded-full inline-block font-bold whitespace-nowrap" style="font-size: 0.72rem;">TIDAK LAYAK</span>
                                @else
                                    <span class="bg-telkom-gray-100 text-telkom-gray-500 px-2.5 py-1 rounded-full inline-block font-medium whitespace-nowrap" style="font-size: 0.72rem;">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 font-mono text-xs font-bold">
                                {{ $item->npv ? 'Rp ' . number_format($item->npv, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right space-x-2">
                                @if($item->status === \App\Models\SimulasiAki::STATUS_REVISI && ($item->user_id_pihak_b === auth()->id() || $item->user_id_pihak_a === auth()->id()))
                                    <a href="{{ route('simulasi.edit', $item) }}" class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-semibold text-xs transition-colors">
                                        <i data-lucide="pencil" class="w-3 h-3"></i>
                                        Revisi
                                    </a>
                                @endif
                                <a href="{{ route('simulasi.show', $item) }}" class="text-telkom-500 hover:text-telkom-600 font-semibold text-xs transition-colors">Detail &rarr;</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100">
                {{ $simulasis->links() }}
            </div>
            @else
            <div class="p-12 text-center text-telkom-gray-400 text-sm flex flex-col items-center">
                <div class="w-14 h-14 bg-telkom-gray-100 rounded-2xl flex items-center justify-center text-telkom-gray-300 mb-4">
                    <i data-lucide="search-x" class="w-7 h-7"></i>
                </div>
                <p>Tidak ada data simulasi ditemukan.</p>
            </div>
            @endif
        </div>
    </div>
</x-layouts.app>
