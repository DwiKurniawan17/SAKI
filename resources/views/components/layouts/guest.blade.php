<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — Simulasi AKI/CAPEX Telkom Indonesia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-telkom-gray-50 font-sans text-telkom-gray-800 antialiased min-h-screen flex flex-col items-center justify-center px-4 py-8">

    <div class="w-full max-w-md">
        {{-- Logo Header --}}
        <div class="text-center mb-6">
            <a href="/" class="inline-block hover:opacity-95 transition-opacity">
                <x-logo class="h-10" />
            </a>
        </div>

        {{-- Auth Card --}}
        <div class="bg-white rounded-2xl shadow-lg ring-1 ring-telkom-gray-200/60 p-6 sm:p-7">
            {{ $slot }}
        </div>
    </div>

    <footer class="mt-8 mb-4">
        <p class="text-xs text-telkom-gray-400 text-center">
            &copy; {{ date('Y') }} PT Telkom Indonesia (Persero) Tbk
        </p>
    </footer>
</body>
</html>
