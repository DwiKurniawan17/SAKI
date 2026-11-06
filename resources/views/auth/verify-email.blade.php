<x-layouts.guest title="Verifikasi Email">
    <div class="mb-5 text-center">
        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3 ring-8 ring-red-50/50">
            <i data-lucide="shield-check" class="w-6 h-6"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Verifikasi Email Anda</h2>
        <p class="text-xs text-slate-500 mt-1.5">
            Kami telah mengirimkan 6 digit kode verifikasi ke alamat email:
        </p>
        <p class="mt-1">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md">
                <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-500"></i>
                {{ auth()->user()->email ?? 'email terdaftar' }}
            </span>
        </p>
    </div>

    @if(session('status') == 'verification-link-sent' || session('status') == 'verification-code-sent')
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mb-5 flex items-start gap-2.5">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
        <p class="text-xs text-emerald-700 font-medium">Kode verifikasi baru telah berhasil dikirim ke email Anda. Silakan periksa inbox atau spam.</p>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-3.5 mb-5 flex items-start gap-2.5">
        <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
        <div class="space-y-0.5">
            @foreach($errors->all() as $error)
                <p class="text-xs text-red-700 font-medium">{{ $error }}</p>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Form Verifikasi Kode OTP --}}
    <form method="POST" action="{{ route('verification.verify-code') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2 text-center">
                Masukkan 6 Digit Kode Verifikasi *
            </label>
            <div class="relative max-w-xs mx-auto">
                <input id="code" 
                       type="text" 
                       name="code" 
                       maxlength="6" 
                       pattern="[0-9]{6}" 
                       inputmode="numeric" 
                       autocomplete="one-time-code"
                       required 
                       autofocus
                       placeholder="••••••"
                       class="h-12 w-full text-center text-2xl font-bold tracking-[0.5em] rounded-lg border border-slate-300 bg-white px-3 text-slate-900 placeholder-slate-300 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-600/20 transition-all font-mono">
            </div>
            <p class="text-[11px] text-slate-400 text-center mt-1.5">Kode berlaku selama 15 menit</p>
        </div>

        <button type="submit" class="h-10 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all text-sm cursor-pointer">
            <i data-lucide="check" class="w-4 h-4"></i>
            Verifikasi Kode Sekarang
        </button>
    </form>

    {{-- Kirim Ulang Kode --}}
    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500 mb-2">Tidak menerima email kode verifikasi?</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline cursor-pointer inline-flex items-center gap-1.5">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                Kirim Ulang Kode Verifikasi
            </button>
        </form>
    </div>

    @if(config('app.debug') && config('mail.default') === 'log')
    <div class="mt-4 p-3 bg-amber-50 border border-amber-200/80 rounded-lg text-left">
        <div class="flex items-start gap-2">
            <i data-lucide="info" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
            <div class="text-[11px] text-amber-800 leading-relaxed">
                <strong>Mode Pengujian Lokal (Log Driver):</strong> Email tidak dikirim ke internet asli. Kode verifikasi tercatat di file <code class="bg-amber-100 px-1 py-0.5 rounded font-mono text-[10px]">storage/logs/laravel.log</code>.
            </div>
        </div>
    </div>
    @endif

    {{-- Logout --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs text-slate-400 hover:text-slate-600 font-medium hover:underline cursor-pointer inline-flex items-center gap-1">
            <i data-lucide="log-out" class="w-3 h-3"></i>
            Gunakan Akun / Email Lain (Keluar)
        </button>
    </form>
</x-layouts.guest>
