@props(['class' => 'h-9'])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 ' . $class]) }}>
    <div class="relative w-9 h-9 bg-gradient-to-tr from-red-700 via-red-600 to-red-500 rounded-xl shadow-sm flex items-center justify-center text-white ring-2 ring-red-100">
        {{-- Modern Telkom stylized 'T' symbol --}}
        <svg class="w-5 h-5 text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
    </div>
    <div class="flex flex-col text-left leading-none">
        <span class="text-base font-black tracking-tight text-telkom-gray-900">
            Telkom<span class="text-telkom-500">Indonesia</span>
        </span>
        <span class="text-[10px] font-semibold tracking-wider uppercase text-telkom-gray-400 mt-0.5">
            AKI / CAPEX Engine
        </span>
    </div>
</div>
