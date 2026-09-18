<x-layouts.guest title="Masuk">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Masuk ke Sistem</h2>
        <p class="text-xs text-slate-500 mt-1">Portal Simulasi AKI & Evaluasi Investasi CAPEX</p>
    </div>

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

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
            <div class="relative">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="nama@telkom.co.id"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
            </div>
        </div>

        <div x-data="{ showPassword: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Kata Sandi</label>
                @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-red-600 hover:text-red-700 font-medium hover:underline">
                    Lupa sandi?
                </a>
                @endif
            </div>
            <div class="relative">
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                       placeholder="••••••••"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-0 top-0 h-10 px-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                        :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex items-center pt-1">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-600">
                <span class="text-xs text-slate-600">Ingat sesi saya di perangkat ini</span>
            </label>
        </div>

        <button type="submit"
                class="h-10 w-full mt-2 inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all duration-150 text-sm cursor-pointer">
            <i data-lucide="log-in" class="w-4 h-4"></i>
            Masuk
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Belum memiliki akun?
            <a href="{{ route('register') }}" class="font-semibold text-red-600 hover:text-red-700 hover:underline">Registrasi Akun Baru</a>
        </p>
    </div>
</x-layouts.guest>

