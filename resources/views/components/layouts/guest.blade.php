<!DOCTYPE html>
<html lang="id" class="scroll-smooth h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Autentikasi' }} — Simulasi AKI/CAPEX Telkom Indonesia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col justify-between selection:bg-red-500 selection:text-white">
    {{-- Top Corporate Accent Bar --}}
    <div class="h-1 bg-gradient-to-r from-red-700 via-red-600 to-red-500 w-full fixed top-0 left-0 z-50"></div>

    <div class="flex-1 flex flex-col items-center justify-center px-4 py-6 sm:py-8">
        <div class="w-full max-w-md">
            {{-- Logo Header --}}
            <div class="text-center mb-5">
                <a href="/" class="inline-block hover:opacity-95 transition-opacity">
                    <x-logo class="h-10" />
                </a>
            </div>

            {{-- Auth Card --}}
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/80 p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </div>

    <footer class="py-6 border-t border-slate-200/60 bg-white/50 text-center">
        <div class="max-w-md mx-auto px-4 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <span>&copy; {{ date('Y') }} PT Telkom Indonesia (Persero) Tbk</span>
            <span class="inline-flex items-center gap-1.5 text-slate-400">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                Portal Internal Terenkripsi
            </span>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>

