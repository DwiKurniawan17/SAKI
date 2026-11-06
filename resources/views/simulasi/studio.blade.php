<x-layouts.app :title="'Studio Simulasi Interaktif (React)'">
    {{-- Breadcrumb & Quick Nav --}}
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <a href="{{ route('dashboard') }}" class="hover:text-red-600 transition-colors">Dashboard</a>
            <span>/</span>
            <a href="{{ route('simulasi.riwayat') }}" class="hover:text-red-600 transition-colors">Riwayat Simulasi</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Studio Simulasi (React)</span>
        </div>

        <div class="flex items-center gap-2">
            @if(isset($simulasi))
                <a href="{{ route('simulasi.show', $simulasi) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-all shadow-2xs">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Kembali ke Detail #{{ $simulasi->id }}</span>
                </a>
            @else
                <a href="{{ route('simulasi.riwayat') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-all shadow-2xs">
                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                    <span>Riwayat Simulasi</span>
                </a>
            @endif
        </div>
    </div>

    {{-- React Mounting Point --}}
    <div id="react-simulation-studio"
         data-default-rdm='@json($defaultRdm)'
         data-catalog='@json($catalog)'
         data-user-role="{{ $userRole }}"
         data-initial-data='@json($initialData)'
         data-initial-summary='@json($initialSummary ?? null)'
         data-wilayah-list='@json($uniqueWilayah ?? [])'
         data-simulasi-id="{{ $simulasiId ?? '' }}">

        {{-- Fallback Loading State (before React mounts) --}}
        <div className="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs text-center space-y-3">
            <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-50 text-red-600 animate-spin mx-auto">
                <i data-lucide="loader-2" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Memuat Studio Simulasi React...</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                Menghubungkan komponen reaktif dengan FastAPI engine dan katalog tarif Telkom...
            </p>
        </div>
    </div>
</x-layouts.app>
