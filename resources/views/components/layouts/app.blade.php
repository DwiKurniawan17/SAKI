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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-slate-100 font-sans text-telkom-gray-800 antialiased" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" @resize.window="sidebarOpen = window.innerWidth >= 1024">

<div class="flex h-screen overflow-hidden relative">

    {{-- Mobile Overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden" x-transition.opacity x-cloak></div>

    {{-- ===== SIDEBAR ===== --}}
    @auth
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-telkom-700 via-telkom-700 to-telkom-800 text-white flex flex-col transition-transform duration-300 lg:static lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        {{-- Brand / Logo --}}
        <div class="p-5 border-b border-white/10">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 mb-4 group">
                <div class="w-9 h-9 bg-white/15 rounded-xl flex items-center justify-center shrink-0 group-hover:bg-white/25 transition-colors">
                    <svg class="w-5 h-5 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="text-white tracking-wide" style="font-size:1.1rem;font-weight:700">Telkom<span class="text-red-200">Indonesia</span></span>
                    <span class="text-red-300/70 mt-0.5" style="font-size:0.6rem;font-weight:600;letter-spacing:0.08em">AKI / CAPEX ENGINE</span>
                </div>
            </a>

            {{-- User Card --}}
            <a href="{{ route('profile.security') }}" class="block bg-white/10 rounded-lg p-3 hover:bg-white/15 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0 text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-white/90 truncate" style="font-size:0.85rem;font-weight:500">{{ auth()->user()->name }}</p>
                        <p class="text-red-200/70 mt-0.5" style="font-size:0.7rem">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
            <p class="px-3 pt-2 pb-1 text-red-300/50 uppercase tracking-wider" style="font-size:0.65rem;font-weight:600">Menu Utama</p>

            {{-- Dashboard --}}
            @php $active = request()->routeIs('dashboard'); @endphp
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="layout-dashboard" class="w-[18px] h-[18px] shrink-0"></i>
                Dashboard
            </a>

            {{-- Buat Simulasi (Sales & Engineering only) --}}
            @if(auth()->user()->hasAnyRole(['sales_marketing', 'engineering_capex', 'admin']))
            @php $active = request()->routeIs('simulasi.create'); @endphp
            <a href="{{ route('simulasi.create') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="plus-circle" class="w-[18px] h-[18px] shrink-0"></i>
                Buat Simulasi
            </a>
            @endif

            {{-- Riwayat --}}
            @php $active = request()->routeIs('simulasi.riwayat'); @endphp
            <a href="{{ route('simulasi.riwayat') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="history" class="w-[18px] h-[18px] shrink-0"></i>
                Riwayat Simulasi
            </a>

            {{-- Admin-only Section --}}
            @if(auth()->user()->hasRole('admin'))
            <p class="px-3 pt-4 pb-1 text-red-300/50 uppercase tracking-wider" style="font-size:0.65rem;font-weight:600">Administrator</p>

            {{-- Review --}}
            @php
                $active = request()->routeIs('simulasi.riwayat') && request('status') === 'menunggu_review';
                $pendingCount = \App\Models\SimulasiAki::where('status', 'menunggu_review')->count();
            @endphp
            <a href="{{ route('simulasi.riwayat', ['status' => 'menunggu_review']) }}"
               class="flex items-center justify-between px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <div class="flex items-center gap-3">
                    <i data-lucide="clipboard-check" class="w-[18px] h-[18px] shrink-0"></i>
                    Review Simulasi
                </div>
                @if($pendingCount > 0)
                <span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $pendingCount }}</span>
                @endif
            </a>

            {{-- Kelola User --}}
            @php $active = request()->routeIs('admin.users'); @endphp
            <a href="{{ route('admin.users') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="users" class="w-[18px] h-[18px] shrink-0"></i>
                Kelola Pengguna
            </a>

            {{-- Parameter RDM --}}
            @php $active = request()->routeIs('admin.rdm'); @endphp
            <a href="{{ route('admin.rdm') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="sliders" class="w-[18px] h-[18px] shrink-0"></i>
                Parameter RDM
            </a>

            {{-- Audit Log --}}
            @php $active = request()->routeIs('admin.audit-log'); @endphp
            <a href="{{ route('admin.audit-log') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $active ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="scroll-text" class="w-[18px] h-[18px] shrink-0"></i>
                Audit Log
            </a>
            @endif
        </nav>

        {{-- Bottom: Profile & Logout --}}
        <div class="p-3 border-t border-white/10 space-y-0.5">
            <a href="{{ route('profile.security') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ request()->routeIs('profile.*') ? 'bg-white/15 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white/90' }}"
               style="font-size:0.85rem">
                <i data-lucide="settings" class="w-[18px] h-[18px] shrink-0"></i>
                Pengaturan Akun
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-white/60 hover:bg-red-500/20 hover:text-red-200 w-full transition-all"
                        style="font-size:0.85rem">
                    <i data-lucide="log-out" class="w-[18px] h-[18px]"></i>
                    Keluar
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
            <button @click="sidebarOpen = !sidebarOpen" class="text-slate-500 hover:text-telkom-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600 hidden sm:block">{{ auth()->user()->name }}</span>
                <div class="w-8 h-8 rounded-full bg-telkom-500 text-white flex items-center justify-center text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-1">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-md border border-slate-200 hover:border-red-200 transition-colors"
                            title="Logout">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </header>
        @endauth

        {{-- Main Content --}}
        <main class="flex-1 overflow-y-auto p-6">
            {{-- Flash Messages --}}
            @if(session('status'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ session('status') }}
                    </div>
                    <button @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
                </div>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                    <button @click="show = false" class="text-green-600 hover:text-green-800">&times;</button>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ session('error') }}
                    </div>
                    <button @click="show = false" class="text-red-600 hover:text-red-800">&times;</button>
                </div>
            </div>
            @endif

            @if(session('warning'))
            <div class="mb-4" x-data="{ show: true }" x-show="show" x-transition>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        {{ session('warning') }}
                    </div>
                    <button @click="show = false" class="text-amber-600 hover:text-amber-800">&times;</button>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                <div class="flex items-center gap-2 font-semibold mb-1">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Terdapat kesalahan:
                </div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-slate-200 bg-white py-4 shrink-0">
            <div class="px-6 text-center">
                <p class="text-xs text-telkom-gray-400">
                    &copy; {{ date('Y') }} PT Telkom Indonesia (Persero) Tbk — Simulasi AKI/CAPEX
                </p>
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
