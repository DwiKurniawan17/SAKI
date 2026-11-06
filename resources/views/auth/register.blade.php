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

    <form id="form-register" method="POST" action="{{ route('register') }}"
          x-data="{
              role: '{{ old('role', 'engineering_capex') }}',
              setRole(r) {
                  this.role = r;
                  const el = document.getElementById('role-input');
                  if (el) {
                      el.value = r;
                      el.dispatchEvent(new Event('input', { bubbles: true }));
                      el.dispatchEvent(new Event('change', { bubbles: true }));
                  }
              }
          }"
          class="space-y-4">
        @csrf

        {{-- Role Selection --}}
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Pilih Divisi / Unit Kerja *</label>
            <input type="hidden" id="role-input" name="role" x-model="role" :value="role" value="{{ old('role', 'engineering_capex') }}">
            <div class="grid grid-cols-2 gap-3">
                {{-- ED (Engineering Development) --}}
                <button type="button"
                        id="btn-role-ed"
                        @click="setRole('engineering_capex')"
                        onclick="window.selectRoleFallback('engineering_capex')"
                        :class="role === 'engineering_capex' 
                            ? 'border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10' 
                            : 'border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50'"
                        class="division-btn flex flex-col items-center justify-center p-2.5 min-h-[5.5rem] rounded-xl cursor-pointer text-center transition-all duration-150 select-none {{ old('role', 'engineering_capex') === 'engineering_capex' ? 'border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10' : 'border border-slate-200 bg-white text-slate-600' }}">
                    <svg class="w-6 h-6 mb-1 shrink-0 transition-colors {{ old('role', 'engineering_capex') === 'engineering_capex' ? 'text-red-600' : 'text-slate-400' }}" :class="role === 'engineering_capex' ? 'text-red-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-xs font-bold block" :class="role === 'engineering_capex' ? 'text-red-900' : 'text-slate-700'">Engineer Development (ED)</span>
                    <span class="text-[10px] text-slate-400">Input Biaya CAPEX</span>
                </button>

                {{-- Marketing (Sales Marketing) --}}
                <button type="button"
                        id="btn-role-marketing"
                        @click="setRole('sales_marketing')"
                        onclick="window.selectRoleFallback('sales_marketing')"
                        :class="role === 'sales_marketing' 
                            ? 'border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10' 
                            : 'border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50'"
                        class="division-btn flex flex-col items-center justify-center p-2.5 min-h-[5.5rem] rounded-xl cursor-pointer text-center transition-all duration-150 select-none {{ old('role') === 'sales_marketing' ? 'border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10' : 'border border-slate-200 bg-white text-slate-600' }}">
                    <svg class="w-6 h-6 mb-1 shrink-0 transition-colors {{ old('role') === 'sales_marketing' ? 'text-red-600' : 'text-slate-400' }}" :class="role === 'sales_marketing' ? 'text-red-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M11 5.882a3 3 0 00-4.242-2.822L4 3.5v10.5l2.758.44A3 3 0 0011 11.618M11 5.882v5.736m7-2.618a3 3 0 01-3 3M15 12h.01" />
                    </svg>
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
            <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nomor WhatsApp / HP <span class="text-red-500">*</span></label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                </div>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
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
                    <svg x-show="!showPwd" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPwd" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
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
                    <svg x-show="!showPwdConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPwdConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit"
                class="h-10 w-full mt-3 inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all duration-150 text-sm cursor-pointer">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Daftarkan Akun
        </button>
    </form>

    <script>
        window.selectRoleFallback = function(roleName) {
            const input = document.getElementById('role-input');
            if (input) {
                input.value = roleName;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const form = document.getElementById('form-register');
            if (form && window.Alpine) {
                try {
                    const data = window.Alpine.$data(form);
                    if (data) data.role = roleName;
                } catch (e) {}
            }
            const btnEd = document.getElementById('btn-role-ed');
            const btnMkt = document.getElementById('btn-role-marketing');
            const isEd = roleName === 'engineering_capex';
            if (btnEd) {
                btnEd.className = isEd
                    ? 'division-btn flex flex-col items-center justify-center p-2.5 min-h-[5.5rem] rounded-xl cursor-pointer text-center transition-all duration-150 select-none border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10'
                    : 'division-btn flex flex-col items-center justify-center p-2.5 min-h-[5.5rem] rounded-xl cursor-pointer text-center transition-all duration-150 select-none border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50';
                const iconEd = btnEd.querySelector('svg');
                if (iconEd) iconEd.setAttribute('class', isEd ? 'w-6 h-6 mb-1 shrink-0 transition-colors text-red-600' : 'w-6 h-6 mb-1 shrink-0 transition-colors text-slate-400');
            }
            if (btnMkt) {
                btnMkt.className = !isEd
                    ? 'division-btn flex flex-col items-center justify-center p-2.5 min-h-[5.5rem] rounded-xl cursor-pointer text-center transition-all duration-150 select-none border-2 border-red-600 bg-red-50/50 text-red-900 shadow-sm ring-1 ring-red-600/10'
                    : 'division-btn flex flex-col items-center justify-center p-2.5 min-h-[5.5rem] rounded-xl cursor-pointer text-center transition-all duration-150 select-none border border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50';
                const iconMkt = btnMkt.querySelector('svg');
                if (iconMkt) iconMkt.setAttribute('class', !isEd ? 'w-6 h-6 mb-1 shrink-0 transition-colors text-red-600' : 'w-6 h-6 mb-1 shrink-0 transition-colors text-slate-400');
            }
        };
    </script>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-700 hover:underline">Masuk ke Sistem</a>
        </p>
    </div>
</x-layouts.guest>
