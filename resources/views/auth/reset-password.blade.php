<x-layouts.guest title="Atur Ulang Kata Sandi">
    <div class="mb-6">
        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-3 ring-8 ring-red-50/50">
            <i data-lucide="lock" class="w-6 h-6"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Atur Ulang Kata Sandi</h2>
        <p class="text-xs text-slate-500 mt-1">
            Buat kata sandi baru untuk akun Anda. Pastikan minimal 8 karakter.
        </p>
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

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        {{-- Hidden Reset Token --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        {{-- Alamat Email --}}
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Alamat Email *
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email', $request->email) }}" 
                       required 
                       autofocus
                       placeholder="nama@telkom.co.id"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
            </div>
        </div>

        {{-- Password Baru --}}
        <div x-data="{ showPwd: false }">
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Kata Sandi Baru (Min. 8 Karakter) *
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <input id="password" 
                       :type="showPwd ? 'text' : 'password'" 
                       name="password" 
                       required
                       placeholder="••••••••"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                <button type="button" @click="showPwd = !showPwd"
                        class="absolute right-0 top-0 h-10 px-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                        :title="showPwd ? 'Sembunyikan' : 'Tampilkan'">
                    <i :data-lucide="showPwd ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Konfirmasi Password Baru --}}
        <div x-data="{ showPwdConf: false }">
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Konfirmasi Kata Sandi Baru *
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <input id="password_confirmation" 
                       :type="showPwdConf ? 'text' : 'password'" 
                       name="password_confirmation" 
                       required
                       placeholder="••••••••"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                <button type="button" @click="showPwdConf = !showPwdConf"
                        class="absolute right-0 top-0 h-10 px-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                        :title="showPwdConf ? 'Sembunyikan' : 'Tampilkan'">
                    <i :data-lucide="showPwdConf ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <button type="submit"
                class="h-10 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all duration-150 text-sm cursor-pointer">
            <i data-lucide="check" class="w-4 h-4"></i>
            Simpan Kata Sandi Baru
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-700 hover:underline">
                Batal dan Kembali ke Halaman Masuk
            </a>
        </p>
    </div>
</x-layouts.guest>
