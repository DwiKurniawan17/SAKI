<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Simulasi & Evaluasi Kelayakan Investasi AKI/CAPEX — Telkom Indonesia">

    <title>{{ $title ?? 'Simulasi AKI/CAPEX' }} — Telkom Indonesia</title>

    <!-- Inter Font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        /* Sidebar Smooth Collapse & Fullscreen Transition */
        #app-sidebar {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease-in-out !important;
            will-change: margin-left, transform;
        }

        /* Desktop View (>= 1024px) */
        @media (min-width: 1024px) {
            #app-sidebar {
                position: static !important;
                transform: none !important;
                margin-left: 0 !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }
            html.sidebar-closed #app-sidebar,
            body.sidebar-closed #app-sidebar {
                margin-left: -16rem !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
            #mobile-sidebar-backdrop {
                display: none !important;
            }
        }

        /* Mobile View (< 1024px) */
        @media (max-width: 1023px) {
            #app-sidebar {
                position: fixed !important;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 50;
                transform: translateX(-100%) !important;
                margin-left: 0 !important;
                width: 16rem !important;
            }
            html.sidebar-open #app-sidebar,
            body.sidebar-open #app-sidebar {
                transform: translateX(0) !important;
            }
            #mobile-sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background-color: rgba(15, 23, 42, 0.5);
                z-index: 40;
                backdrop-filter: blur(2px);
                transition: opacity 0.2s ease-in-out;
            }
            html.sidebar-open #mobile-sidebar-backdrop,
            body.sidebar-open #mobile-sidebar-backdrop {
                display: block !important;
            }
        }
    </style>

    <script>
        // Apply saved desktop preference immediately to prevent UI flash
        (function() {
            try {
                if (window.innerWidth >= 1024 && localStorage.getItem('sidebar_desktop_closed') === 'true') {
                    document.documentElement.classList.add('sidebar-closed');
                }
            } catch (e) {}
        })();

        function toggleAppSidebar() {
            const isDesktop = window.innerWidth >= 1024;
            if (isDesktop) {
                const isClosed = document.documentElement.classList.toggle('sidebar-closed');
                document.body.classList.toggle('sidebar-closed', isClosed);
                try {
                    localStorage.setItem('sidebar_desktop_closed', isClosed ? 'true' : 'false');
                } catch (e) {}
            } else {
                const isOpen = document.documentElement.classList.toggle('sidebar-open');
                document.body.classList.toggle('sidebar-open', isOpen);
            }
        }

        function closeMobileSidebar() {
            document.documentElement.classList.remove('sidebar-open');
            document.body.classList.remove('sidebar-open');
        }

        document.addEventListener('DOMContentLoaded', function() {
            try {
                if (window.innerWidth >= 1024 && localStorage.getItem('sidebar_desktop_closed') === 'true') {
                    document.body.classList.add('sidebar-closed');
                }
            } catch (e) {}
        });
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden relative">

    {{-- Mobile Overlay --}}
    <div id="mobile-sidebar-backdrop" onclick="closeMobileSidebar()"></div>

    {{-- ===== SIDEBAR ===== --}}
    @auth
    <aside id="app-sidebar" class="w-64 bg-gradient-to-b from-red-600 via-red-700 to-red-800 text-white flex flex-col border-r border-red-800/80 select-none shrink-0">

        {{-- Brand / Logo --}}
        <div class="p-4 border-b border-red-800/80">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 bg-white text-red-600 rounded-lg flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-white text-sm font-black tracking-tight">Telkom<span class="text-red-200">Indonesia</span></span>
                        <span class="text-red-100 text-[10px] font-semibold tracking-wider uppercase mt-0.5">AKI / CAPEX Engine</span>
                    </div>
                </a>
                <button type="button" onclick="closeMobileSidebar()" class="lg:hidden text-red-200 hover:text-white p-1 rounded-lg transition-colors cursor-pointer" title="Tutup Menu">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            {{-- User Card --}}
            <a href="{{ route('profile.security') }}" class="mt-3 block bg-red-800/80 hover:bg-red-800 border border-red-500/50 rounded-lg p-2.5 transition-colors">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center shrink-0 text-xs font-bold ring-1 ring-white/30">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-xs font-bold truncate leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-red-100 text-[11px] font-medium truncate leading-tight mt-0.5">{{ auth()->user()->role_display_name }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            <p class="px-2.5 pt-2 pb-1 text-[10px] font-bold text-red-200 uppercase tracking-wider">Menu Utama</p>

            {{-- Dashboard --}}
            @php $active = request()->routeIs('dashboard'); @endphp
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                <span>Dashboard</span>
            </a>

            {{-- Buat Simulasi (Engineering Development only) --}}
            @if(auth()->user()->hasRole('engineering_capex'))
            @php $active = request()->routeIs('simulasi.create'); @endphp
            <a href="{{ route('simulasi.create') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="plus-circle" class="w-4 h-4 shrink-0"></i>
                <span>Buat Simulasi</span>
            </a>
            @endif

            {{-- Riwayat --}}
            @php $active = request()->routeIs('simulasi.riwayat') && !request('status'); @endphp
            <a href="{{ route('simulasi.riwayat') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="history" class="w-4 h-4 shrink-0"></i>
                <span>Riwayat Simulasi</span>
            </a>

            {{-- Admin-only Section (RSO) --}}
            @if(auth()->user()->hasRole('admin'))
            <p class="px-2.5 pt-4 pb-1 text-[10px] font-bold text-red-200 uppercase tracking-wider">Administrator (RSO)</p>

            {{-- Review --}}
            @php
                $active = request()->routeIs('simulasi.riwayat') && request('status') === 'menunggu_review';
                $pendingCount = \App\Models\SimulasiAki::where('status', 'menunggu_review')->count();
            @endphp
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_review']) }}"
               class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="clipboard-check" class="w-4 h-4 shrink-0"></i>
                    <span>Review Simulasi</span>
                </div>
                @if($pendingCount > 0)
                <span class="{{ $active ? 'bg-red-700 text-white' : 'bg-white text-red-700' }} text-[10px] font-bold px-1.5 py-0.5 rounded-full shadow-xs">{{ $pendingCount }}</span>
                @endif
            </a>

            {{-- Kelola User --}}
            @php $active = request()->routeIs('admin.users'); @endphp
            <a href="{{ route('admin.users') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                <span>Kelola Pengguna</span>
            </a>

            {{-- Katalog Price List --}}
            @php $active = request()->routeIs('admin.price-list*'); @endphp
            <a href="{{ route('admin.price-list') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="tag" class="w-4 h-4 shrink-0"></i>
                <span>Katalog Price List</span>
            </a>

            {{-- Audit Log --}}
            @php $active = request()->routeIs('admin.audit-log'); @endphp
            <a href="{{ route('admin.audit-log') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $active ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="scroll-text" class="w-4 h-4 shrink-0"></i>
                <span>Audit Log</span>
            </a>
            @endif
        </nav>

        {{-- Bottom: Profile & Logout --}}
        <div class="p-3 border-t border-red-800/80 space-y-1">
            <a href="{{ route('profile.security') }}"
               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('profile.*') ? 'bg-white text-red-700 font-bold shadow-xs' : 'text-red-100 hover:text-white hover:bg-red-800/60' }}">
                <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
                <span>Pengaturan Akun</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-red-100 hover:text-white hover:bg-red-800/60 w-full transition-colors cursor-pointer">
                    <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>
    @endauth

    {{-- ===== MAIN AREA (Header + Content + Footer) ===== --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top Header Bar --}}
        @auth
        <header class="bg-white border-b border-slate-200 h-14 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-3">
                <button type="button"
                        id="btn-sidebar-toggle"
                        onclick="toggleAppSidebar()"
                        class="text-slate-600 hover:text-slate-900 hover:bg-slate-100 p-2 rounded-lg transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500/20 active:scale-95"
                        title="Sembunyikan / Tampilkan Menu (Full Screen)">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="hidden md:flex items-center gap-2 text-xs text-slate-500 font-medium">
                    <span>Sistem Informasi Simulasi AKI / CAPEX</span>
                    <span>&bull;</span>
                    <span class="text-slate-700 font-semibold">PT Telkom Indonesia</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200">
                    <div class="w-7 h-7 rounded-lg bg-red-50 text-red-700 border border-red-200 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <span class="text-xs font-semibold text-slate-800 block leading-tight">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">{{ auth()->user()->role_display_name }}</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-1">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-lg border border-slate-200 transition-colors cursor-pointer"
                            title="Keluar dari sistem">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </header>
        @endauth


        {{-- Main Content --}}
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8">
            {{-- Flash Messages --}}
            @if(session('status'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg px-4 py-3 flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 ml-3">&times;</button>
                </div>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg px-4 py-3 flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-600 hover:text-emerald-800 ml-3">&times;</button>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 shrink-0"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-600 hover:text-red-800 ml-3">&times;</button>
                </div>
            </div>
            @endif

            @if(session('warning'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                    <button @click="show = false" class="text-amber-600 hover:text-amber-800 ml-3">&times;</button>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-xs">
                <div class="flex items-center gap-2 font-semibold mb-1">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 shrink-0"></i>
                    <span>Terdapat kesalahan validasi:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 pl-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200 bg-white py-3 shrink-0">
            <div class="px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} PT Telkom Indonesia (Persero) Tbk — Evaluasi Kelayakan AKI/CAPEX</p>
                <p class="text-[11px]">Production Standard Environment</p>
            </div>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) lucide.createIcons();
    });
</script>

@livewireScripts
@stack('scripts')
</body>
</html>
