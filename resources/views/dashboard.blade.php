<x-layouts.app title="Dashboard">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Selamat Datang, {{ $user->name }} 👋</h1>
                <p class="text-slate-500" style="font-size: 0.85rem;">Dashboard Simulasi Investasi AKI/CAPEX Telkom Indonesia</p>
            </div>
            @if($user->hasAnyRole(['sales_marketing', 'engineering_capex']))
            <a href="{{ route('simulasi.create') }}" class="inline-flex items-center justify-center gap-2 bg-telkom-500 hover:bg-telkom-600 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all shadow-md hover:shadow-lg hover:scale-[1.01]">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Simulasi Baru</span>
            </a>
            @endif
        </div>

        {{-- RSO: Pending Review Alert --}}
        @if($user->hasRole('admin') && isset($pendingReview) && $pendingReview > 0)
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-center justify-between hover:shadow-sm transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-indigo-800">{{ $pendingReview }} Simulasi Menunggu Review Anda</p>
                    <p class="text-xs text-indigo-600">Periksa dan setujui atau minta revisi dokumen simulasi.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_review']) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all">
                Lihat &rarr;
            </a>
        </div>
        @endif

        {{-- User: Revisi Alert --}}
        @if(!$user->hasRole('admin') && isset($revisi) && $revisi > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center justify-between hover:shadow-sm transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-800">{{ $revisi }} Simulasi Perlu Direvisi</p>
                    <p class="text-xs text-amber-600">RSO meminta perbaikan pada simulasi Anda.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'revisi']) }}" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all">
                Lihat &rarr;
            </a>
        </div>
        @endif

        {{-- User: ED Pending Alert --}}
        @if($user->hasRole('engineering_capex') && isset($pendingEd) && $pendingEd > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex items-center justify-between hover:shadow-sm transition-shadow">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600">
                    <i data-lucide="wrench" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-blue-800">{{ $pendingEd }} Simulasi Menunggu Input CAPEX dari Engineering (ED)</p>
                    <p class="text-xs text-blue-600">Marketing telah mengisi data commercial. Silakan lengkapi parameter material & jasa.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_pihak_a']) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all">
                Lengkapi Data &rarr;
            </a>
        </div>
        @endif

        {{-- Highlight Cards --}}
        <div class="grid lg:grid-cols-3 sm:grid-cols-2 gap-4">
            <div class="bg-gradient-to-r from-telkom-500 to-telkom-600 rounded-xl p-5 text-white shadow-md flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        <span style="font-size: 0.85rem; font-weight: 500;">Total Simulasi</span>
                    </div>
                    <p style="font-size: 2rem; font-weight: 700;">{{ $totalSimulasi }}</p>
                </div>
                <p class="text-red-100 mt-3" style="font-size: 0.8rem;">Seluruh simulasi AKI/CAPEX yang tercatat</p>
            </div>

            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl p-5 text-white shadow-md flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        <span style="font-size: 0.85rem; font-weight: 500;">Layak Investasi</span>
                    </div>
                    <p style="font-size: 2rem; font-weight: 700;">{{ $layak }}</p>
                </div>
                @if($totalSimulasi > 0)
                <div class="mt-3">
                    <div class="w-full bg-emerald-700/50 rounded-full h-2 overflow-hidden">
                        <div class="bg-white h-full rounded-full transition-all duration-500" style="width: {{ round(($layak / max($totalSimulasi, 1)) * 100) }}%"></div>
                    </div>
                    <p class="text-emerald-100 mt-1.5" style="font-size: 0.75rem;">{{ round(($layak / max($totalSimulasi, 1)) * 100) }}% dari total simulasi</p>
                </div>
                @else
                <p class="text-emerald-100 mt-3" style="font-size: 0.8rem;">Belum ada data simulasi</p>
                @endif
            </div>

            <div class="bg-gradient-to-r from-rose-500 to-rose-600 rounded-xl p-5 text-white shadow-md flex flex-col justify-between hover:scale-[1.01] transition-transform duration-200">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="x-circle" class="w-5 h-5"></i>
                        <span style="font-size: 0.85rem; font-weight: 500;">Tidak Layak</span>
                    </div>
                    <p style="font-size: 2rem; font-weight: 700;">{{ $tidakLayak }}</p>
                </div>
                <p class="text-rose-100 mt-3" style="font-size: 0.8rem;">Simulasi yang belum memenuhi kriteria</p>
            </div>
        </div>

        {{-- Status Summary (admin / user specific) --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @if($user->hasRole('admin') && isset($pendingReview))
            <div class="bg-white rounded-xl p-5 border border-indigo-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-500">
                        <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-xs text-telkom-gray-400 font-medium block">Menunggu Review</span>
                        <span class="text-2xl font-extrabold text-indigo-700 mt-0.5 block">{{ $pendingReview }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if(!$user->hasRole('admin') && isset($revisi))
            <div class="bg-white rounded-xl p-5 border border-amber-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                        <i data-lucide="pencil-line" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="text-xs text-telkom-gray-400 font-medium block">Perlu Revisi</span>
                        <span class="text-2xl font-extrabold text-amber-700 mt-0.5 block">{{ $revisi }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Recent Simulasi Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-telkom-gray-800" style="font-size: 1rem; font-weight: 600;">Simulasi Terakhir</h2>
                <a href="{{ route('simulasi.riwayat') }}" class="text-xs font-semibold text-telkom-500 hover:text-telkom-600 transition-colors">Lihat Semua &rarr;</a>
            </div>

            @php $simList = $simulasis ?? $latestSimulasi ?? []; @endphp
            @if(count($simList) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size: 0.83rem;">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-100">
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Nama Proyek</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Status</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Kelayakan</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider text-right" style="font-size: 0.7rem;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($simList as $item)
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
                                @php
                                    $statusColors = [
                                        'menunggu_review' => 'bg-indigo-100 text-indigo-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'revisi' => 'bg-amber-100 text-amber-800',
                                        'menunggu_pihak_a' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                @endphp
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
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('simulasi.show', $item) }}" class="text-telkom-500 hover:text-telkom-600 font-semibold text-xs transition-colors">Detail &rarr;</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-12 text-center text-telkom-gray-400 text-sm flex flex-col items-center">
                <div class="w-14 h-14 bg-telkom-gray-100 rounded-2xl flex items-center justify-center text-telkom-gray-300 mb-4">
                    <i data-lucide="file-x" class="w-7 h-7"></i>
                </div>
                <p>Belum ada data simulasi.</p>
                @if($user->hasAnyRole(['sales_marketing', 'engineering_capex']))
                <a href="{{ route('simulasi.create') }}" class="mt-3 inline-flex items-center gap-2 bg-telkom-500 hover:bg-telkom-600 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-all shadow-sm">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    Buat Simulasi Pertama
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</x-layouts.app>
