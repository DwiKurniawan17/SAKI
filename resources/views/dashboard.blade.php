<x-layouts.app title="Dashboard">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Selamat Datang, {{ $user->name }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">Sistem Evaluasi & Simulasi Kelayakan Investasi AKI / CAPEX — PT Telkom Indonesia</p>
            </div>
            @if($user->hasRole('engineering_capex'))
            <a href="{{ route('simulasi.create') }}" class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition-all shadow-xs shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Simulasi Baru</span>
            </a>
            @endif
        </div>

        {{-- RSO: Pending Review Alert --}}
        @if($user->hasRole('admin') && isset($pendingReview) && $pendingReview > 0)
        <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center text-blue-700 shrink-0">
                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-blue-900">{{ $pendingReview }} Simulasi Menunggu Review RSO</p>
                    <p class="text-[11px] text-blue-700 mt-0.5">Lakukan evaluasi parameter, periksa preview format Excel, dan berikan persetujuan atau catatan revisi.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_review']) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors shrink-0">
                Review Sekarang &rarr;
            </a>
        </div>
        @endif

        {{-- RSO: Waiting Revisi oleh Divisi Marketing Alert --}}
        @if($user->hasRole('admin') && isset($waitingRevisiMarketing) && $waitingRevisiMarketing > 0)
        <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center text-amber-700 shrink-0">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-amber-900">{{ $waitingRevisiMarketing }} Simulasi Waiting Revisi oleh Divisi Marketing</p>
                    <p class="text-[11px] text-amber-700 mt-0.5">Catatan revisi telah dikirimkan ke Marketing karena simulasi belum layak. Menunggu penyesuaian tarif komersial dari Marketing.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'revisi']) }}" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors shrink-0">
                Pantau Status &rarr;
            </a>
        </div>
        @endif

        {{-- User: Revisi Alert --}}
        @if(!$user->hasRole('admin') && isset($revisi) && $revisi > 0)
        <div class="bg-rose-50/80 border border-rose-200 rounded-xl p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-rose-100 rounded-lg flex items-center justify-center text-rose-700 shrink-0">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-rose-900">{{ $revisi }} Usulan Perlu Direvisi (Belum Layak)</p>
                    <p class="text-[11px] text-rose-700 mt-0.5">RSO memberikan catatan perbaikan dan target tarif (Goal Seek). Silakan sesuaikan parameter komersial dan kirim ulang.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'revisi']) }}" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors shrink-0">
                Buka Revisi &rarr;
            </a>
        </div>
        @endif

        {{-- User: ED Pending Alert --}}
        @if(($user->hasRole('engineering_capex') || $user->hasRole('admin')) && isset($pendingEd) && $pendingEd > 0)
        <div class="bg-slate-100/90 border border-slate-200 rounded-xl p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-200 rounded-lg flex items-center justify-center text-slate-700 shrink-0">
                    <i data-lucide="wrench" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">{{ $pendingEd }} Simulasi Menunggu Input CAPEX dari Engineer Development (ED)</p>
                    <p class="text-[11px] text-slate-600 mt-0.5">Data komersial telah diisi oleh Marketing. Silakan lengkapi biaya fisik (Material & Jasa).</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_pihak_a']) }}" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors shrink-0">
                Lengkapi CAPEX &rarr;
            </a>
        </div>
        @endif

        {{-- User: Marketing Pending Alert --}}
        @if(($user->hasRole('sales_marketing') || $user->hasRole('admin')) && isset($pendingMarketing) && $pendingMarketing > 0)
        <div class="bg-slate-100/90 border border-slate-200 rounded-xl p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-200 rounded-lg flex items-center justify-center text-slate-700 shrink-0">
                    <i data-lucide="tag" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">{{ $pendingMarketing }} Simulasi Menunggu Input Tarif dari Marketing</p>
                    <p class="text-[11px] text-slate-600 mt-0.5">Engineer Development telah mengunggah data proyek & CAPEX. Silakan tentukan paketisasi, OTC, dan MRC.</p>
                </div>
            </div>
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_pihak_b']) }}" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors shrink-0">
                Isi Komersial &rarr;
            </a>
        </div>
        @endif

        {{-- Corporate Summary Metrics Cards --}}
        <div class="grid lg:grid-cols-3 sm:grid-cols-2 gap-4">
            {{-- Total Simulasi --}}
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Simulasi</span>
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                            <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-900">{{ $totalSimulasi }}</p>
                </div>
                <p class="text-xs text-slate-500 mt-3 pt-3 border-t border-slate-100">
                    Seluruh usulan investasi yang tercatat dalam sistem
                </p>
            </div>

            {{-- Layak Investasi --}}
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Layak Investasi</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-900">{{ $layak }}</p>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100">
                    @if($totalSimulasi > 0)
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                        <span>Tingkat Kelayakan</span>
                        <span class="font-semibold text-emerald-700">{{ round(($layak / max($totalSimulasi, 1)) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ round(($layak / max($totalSimulasi, 1)) * 100) }}%"></div>
                    </div>
                    @else
                    <p class="text-xs text-slate-400">Belum ada data evaluasi kelayakan</p>
                    @endif
                </div>
            </div>

            {{-- Tidak Layak --}}
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-red-700">Tidak Layak</span>
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-900">{{ $tidakLayak }}</p>
                </div>
                <p class="text-xs text-slate-500 mt-3 pt-3 border-t border-slate-100">
                    Memerlukan penyesuaian tarif komersial atau efisiensi CAPEX
                </p>
            </div>
        </div>

        {{-- Status Summary Mini Cards --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @if($user->hasRole('admin') && isset($pendingReview))
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 block uppercase tracking-wider">Menunggu Review</span>
                    <span class="text-xl font-bold text-slate-900 mt-0.5 block">{{ $pendingReview }}</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                </div>
            </div>
            @endif

            @if($user->hasRole('admin') && isset($waitingRevisiMarketing))
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 block uppercase tracking-wider">Waiting Revisi Mkt</span>
                    <span class="text-xl font-bold text-amber-700 mt-0.5 block">{{ $waitingRevisiMarketing }}</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            @endif

            @if(!$user->hasRole('admin') && isset($revisi))
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-500 block uppercase tracking-wider">Perlu Revisi</span>
                    <span class="text-xl font-bold text-rose-700 mt-0.5 block">{{ $revisi }}</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="pencil-line" class="w-4 h-4"></i>
                </div>
            </div>
            @endif
        </div>

        {{-- Recent Simulasi Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Simulasi Terakhir</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar usulan proyek terbaru yang masuk ke sistem</p>
                </div>
                <a href="{{ route('simulasi.riwayat') }}" class="text-xs font-semibold text-red-600 hover:text-red-700 transition-colors flex items-center gap-1">
                    <span>Lihat Semua Riwayat</span>
                    <span>&rarr;</span>
                </a>
            </div>

            @php $simList = $simulasis ?? $latestSimulasi ?? []; @endphp
            @if(count($simList) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px]">Nama Proyek</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px]">Status Proses</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px]">Hasil Evaluasi</th>
                            <th class="px-6 py-3 font-semibold uppercase tracking-wider text-[11px] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($simList as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0 mt-0.5">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('simulasi.show', $item) }}" class="font-semibold text-slate-900 hover:text-red-600 transition-colors">
                                                {{ $item->nama_proyek }}
                                            </a>
                                            {{-- ED (Penginput Proyek) --}}
                                            @if($item->pihakA)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200" title="Diinput oleh Engineering (ED): {{ $item->pihakA->name }}">
                                                <i data-lucide="wrench" class="w-2.5 h-2.5 text-slate-500"></i>
                                                <span class="text-slate-500 text-[9px] font-bold">ED:</span>
                                                {{ $item->pihakA->name }}
                                                @if(auth()->id() === $item->pihakA->id)
                                                <span class="text-[9px] text-slate-400 font-normal">(Anda)</span>
                                                @endif
                                            </span>
                                            @endif

                                            {{-- Marketing (Pengisi Komersial) --}}
                                            @if($item->pihakB)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200" title="Dilengkapi / Diupdate oleh Marketing: {{ $item->pihakB->name }}">
                                                <i data-lucide="tag" class="w-2.5 h-2.5 text-blue-500"></i>
                                                <span class="text-blue-500 text-[9px] font-bold">MKT:</span>
                                                {{ $item->pihakB->name }}
                                                @if(auth()->id() === $item->pihakB->id)
                                                <span class="text-[9px] text-blue-400 font-normal">(Anda)</span>
                                                @endif
                                            </span>
                                            @elseif($item->status === \App\Models\SimulasiAki::STATUS_MENUNGGU_PIHAK_B)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200" title="Menunggu Divisi Marketing mengisi tarif komersial">
                                                <i data-lucide="clock" class="w-2.5 h-2.5 text-amber-500"></i>
                                                <span>Menunggu Marketing</span>
                                            </span>
                                            @endif
                                            @if($item->lokasi)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                                                <i data-lucide="map-pin" class="w-2.5 h-2.5 text-slate-400"></i>
                                                {{ $item->lokasi }}
                                            </span>
                                            @endif
                                            @if($item->bandwidth)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <i data-lucide="activity" class="w-2.5 h-2.5 text-indigo-500"></i>
                                                {{ $item->bandwidth }}
                                            </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-400 mt-0.5">
                                            <i data-lucide="clock" class="w-3 h-3 text-slate-400 shrink-0"></i>
                                            <span title="{{ $item->created_at->translatedFormat('l, d F Y - H:i:s') }} WIB">
                                                {{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB &bull; <span class="font-medium text-slate-500">{{ $item->created_at->diffForHumans() }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5">
                                @php
                                    $statusBadges = [
                                        'menunggu_review'   => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'clock'],
                                        'approved'          => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle-2'],
                                        'revisi'            => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'icon' => 'alert-circle'],
                                        'menunggu_pihak_a'  => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'clock'],
                                        'menunggu_pihak_b'  => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'clock'],
                                        'draft_pihak_b'     => ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'file-text'],
                                        'lengkap_terhitung' => ['bg' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'check'],
                                    ];
                                    $badge = $statusBadges[$item->status] ?? ['bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'circle'];
                                    $statusLabel = $item->status_label;
                                    if ($item->status === \App\Models\SimulasiAki::STATUS_REVISI) {
                                        if (auth()->user()->hasRole('admin')) {
                                            $statusLabel = 'Waiting Revisi Marketing';
                                            $badge['bg'] = 'bg-amber-50 text-amber-800 border-amber-200';
                                            $badge['icon'] = 'clock';
                                        } else {
                                            $statusLabel = 'Perlu Revisi (Belum Layak)';
                                            $badge['bg'] = 'bg-rose-50 text-rose-700 border-rose-200';
                                        }
                                    }
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border {{ $badge['bg'] }}">
                                    <i data-lucide="{{ $badge['icon'] }}" class="w-3 h-3"></i>
                                    <span>{{ $statusLabel }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                @if($item->status === \App\Models\SimulasiAki::STATUS_APPROVED)
                                    @if($item->layak === true)
                                        <span class="inline-flex items-center gap-1 bg-emerald-50 border border-emerald-200 text-emerald-700 px-2.5 py-0.5 rounded text-[11px] font-bold">
                                            <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-600"></i>
                                            <span>LAYAK</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-red-50 border border-red-200 text-red-700 px-2.5 py-0.5 rounded text-[11px] font-bold">
                                            <i data-lucide="x-circle" class="w-3 h-3 text-red-600"></i>
                                            <span>TIDAK LAYAK</span>
                                        </span>
                                    @endif
                                @elseif(in_array($item->status, [\App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW, \App\Models\SimulasiAki::STATUS_LENGKAP_TERHITUNG]))
                                    <span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-700 px-2.5 py-0.5 rounded text-[11px] font-medium">
                                        <i data-lucide="clock" class="w-3 h-3 text-blue-600"></i>
                                        <span>Menunggu RSO</span>
                                    </span>
                                @elseif($item->status === \App\Models\SimulasiAki::STATUS_REVISI)
                                    <span class="inline-flex items-center gap-1 bg-rose-50 border border-rose-200 text-rose-700 px-2.5 py-0.5 rounded text-[11px] font-bold">
                                        <i data-lucide="alert-circle" class="w-3 h-3 text-rose-600"></i>
                                        <span>BELUM LAYAK (REVISI)</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-medium">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('simulasi.show', $item) }}" class="inline-flex items-center gap-1 text-slate-700 hover:text-red-600 font-semibold text-xs transition-colors">
                                    <span>Detail</span>
                                    <span class="text-slate-400">&rarr;</span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-12 text-center text-slate-400 text-xs flex flex-col items-center">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 mb-3">
                    <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
                <p class="font-semibold text-slate-700 text-sm">Belum Ada Data Simulasi</p>
                <p class="text-slate-400 mt-1 max-w-sm">Belum terdapat data simulasi proyek yang tercatat dalam sistem untuk akun Anda.</p>
                @if($user->hasAnyRole(['sales_marketing', 'engineering_capex']))
                <a href="{{ route('simulasi.create') }}" class="mt-4 inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-all shadow-xs">
                    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                    <span>Buat Simulasi Pertama</span>
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</x-layouts.app>
