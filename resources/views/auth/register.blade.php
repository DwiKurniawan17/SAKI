<x-layouts.guest title="Daftar Akun">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Registrasi Akun Baru</h2>
        <p class="text-xs text-slate-500 mt-1">Daftarkan akun Anda untuk akses modul simulasi AKI/CAPEX</p>
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

    <form method="POST" action="{{ route('register') }}" x-data="{ role: '{{ old('role', 'engineering_capex') }}' }" class="space-y-4">
        @csrf

        {{-- Role Selection --}}
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Pilih Divisi / Unit Kerja *</label>
            <input type="hidden" name="role" x-model="role">
            <div class="grid grid-cols-2 gap-3">
                {{-- ED --}}
                <button type="button" @click="role = 'engineering_capex'"
                        :class="role === 'engineering_capex' 
                            ? 'border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10' 
                            : 'border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50'"
                        class="flex flex-col items-center justify-center p-3 h-20 rounded-xl cursor-pointer text-center transition-all duration-150">
                    <i data-lucide="cpu" class="w-5 h-5 mb-1.5 transition-colors" :class="role === 'engineering_capex' ? 'text-red-600' : 'text-slate-400'"></i>
                    <span class="text-xs font-bold block" :class="role === 'engineering_capex' ? 'text-red-900' : 'text-slate-700'">Engineer Development (ED)</span>
                    <span class="text-[10px] text-slate-400">Input Biaya CAPEX</span>
                </button>

                {{-- Marketing --}}
                <button type="button" @click="role = 'sales_marketing'"
                        :class="role === 'sales_marketing' 
                            ? 'border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10' 
                            : 'border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50'"
                        class="flex flex-col items-center justify-center p-3 h-20 rounded-xl cursor-pointer text-center transition-all duration-150">
                    <i data-lucide="trending-up" class="w-5 h-5 mb-1.5 transition-colors" :class="role === 'sales_marketing' ? 'text-red-600' : 'text-slate-400'"></i>
                    <span class="text-xs font-bold block" :class="role === 'sales_marketing' ? 'text-red-900' : 'text-slate-700'">Sales Marketing</span>
                    <span class="text-[10px] text-slate-400">Parameter Komersial</span>
                </button>
            </div>
        </div>

        {{-- Nama Lengkap --}}
        <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       placeholder="Nama lengkap sesuai identitas"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
            </div>
        </div>

        {{-- Alamat Email --}}
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email Perusahaan *</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       placeholder="nama@telkom.co.id"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
            </div>
        </div>

        {{-- Nomor Telepon --}}
        <div>
            <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nomor WhatsApp / HP</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                </div>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                       placeholder="081234567890"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
            </div>
        </div>

        {{-- Password --}}
        <div x-data="{ showPwd: false }">
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi (Min. 8 Karakter) *</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <input id="password" :type="showPwd ? 'text' : 'password'" name="password" required
                       placeholder="••••••••"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                <button type="button" @click="showPwd = !showPwd"
                        class="absolute right-0 top-0 h-10 px-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                        :title="showPwd ? 'Sembunyikan' : 'Tampilkan'">
                    <i :data-lucide="showPwd ? 'eye-off' : 'eye'" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        {{-- Konfirmasi Password --}}
        <div x-data="{ showPwdConf: false }">
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Konfirmasi Kata Sandi *</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <input id="password_confirmation" :type="showPwdConf ? 'text' : 'password'" name="password_confirmation" required
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
                class="h-10 w-full mt-3 inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all duration-150 text-sm cursor-pointer">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Daftarkan Akun
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-700 hover:underline">Masuk ke Sistem</a>
        </p>
    </div>
</x-layouts.guest>
