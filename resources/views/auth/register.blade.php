<x-layouts.guest title="Daftar Akun">
    <div class="text-center mb-5">
        <h2 class="text-xl font-bold text-telkom-gray-800">Buat Akun Baru</h2>
        <p class="text-xs text-telkom-gray-500 mt-1">Daftarkan diri untuk mulai menggunakan layanan</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 space-y-1">
        @foreach($errors->all() as $error)
            <p class="text-xs text-red-700 font-medium">• {{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('register') }}" x-data="{ role: '{{ old('role', 'engineering_capex') }}' }">
        @csrf

        <div class="space-y-4">
            {{-- Role Selection: Guaranteed 2-Column Grid (Kanan Kiri) --}}
            <div>
                <label class="block text-xs font-semibold text-telkom-gray-700 mb-2">Daftar Sebagai *</label>
                <input type="hidden" name="role" x-model="role">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; width: 100%;">
                    {{-- Kiri: ED (Engineering Development) --}}
                    <button type="button" @click="role = 'engineering_capex'"
                            :class="role === 'engineering_capex' 
                                ? 'border-2 border-amber-500 bg-amber-50/60 text-amber-900 shadow-sm' 
                                : 'border border-telkom-gray-200 hover:border-telkom-gray-300 bg-white text-telkom-gray-400'"
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 14px 8px; height: 90px; border-radius: 16px; cursor: pointer; text-align: center; transition: all 0.2s;">
                        <svg class="w-7 h-7 mb-1.5 transition-colors" :class="role === 'engineering_capex' ? 'text-amber-600' : 'text-telkom-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-xs font-bold block" :class="role === 'engineering_capex' ? 'text-amber-900' : 'text-telkom-gray-600'">ED</span>
                    </button>

                    {{-- Kanan: Marketing (Megaphone Icon) --}}
                    <button type="button" @click="role = 'sales_marketing'"
                            :class="role === 'sales_marketing' 
                                ? 'border-2 border-amber-500 bg-amber-50/60 text-amber-900 shadow-sm' 
                                : 'border border-telkom-gray-200 hover:border-telkom-gray-300 bg-white text-telkom-gray-400'"
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 14px 8px; height: 90px; border-radius: 16px; cursor: pointer; text-align: center; transition: all 0.2s;">
                        <svg class="w-7 h-7 mb-1.5 transition-colors" :class="role === 'sales_marketing' ? 'text-amber-600' : 'text-telkom-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M11 5.882a3 3 0 00-4.242-2.822L4 3.5v10.5l2.758.44A3 3 0 0011 11.618M11 5.882v5.736m7-2.618a3 3 0 01-3 3M15 12h.01" />
                        </svg>
                        <span class="text-xs font-bold block" :class="role === 'sales_marketing' ? 'text-amber-900' : 'text-telkom-gray-600'">Marketing</span>
                    </button>
                </div>
            </div>

            {{-- Nama Lengkap --}}
            <div>
                <label for="name" class="block text-xs font-semibold text-telkom-gray-700 mb-1">Nama Lengkap *</label>
                <div style="position: relative; display: flex; align-items: center; width: 100%;">
                    <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Masukkan nama lengkap"
                           style="padding-left: 36px;"
                           class="w-full rounded-xl border border-telkom-gray-200 pr-4 py-2.5 text-xs focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
                </div>
            </div>

            {{-- Alamat Email --}}
            <div>
                <label for="email" class="block text-xs font-semibold text-telkom-gray-700 mb-1">Alamat Email *</label>
                <div style="position: relative; display: flex; align-items: center; width: 100%;">
                    <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required placeholder="email@example.com"
                           style="padding-left: 36px;"
                           class="w-full rounded-xl border border-telkom-gray-200 pr-4 py-2.5 text-xs focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
                </div>
            </div>

            {{-- Nomor Telepon --}}
            <div>
                <label for="phone" class="block text-xs font-semibold text-telkom-gray-700 mb-1">Nomor Telepon</label>
                <div style="position: relative; display: flex; align-items: center; width: 100%;">
                    <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx"
                           style="padding-left: 36px;"
                           class="w-full rounded-xl border border-telkom-gray-200 pr-4 py-2.5 text-xs focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
                </div>
            </div>

            {{-- Password --}}
            <div x-data="{ showPwd: false }">
                <label for="password" class="block text-xs font-semibold text-telkom-gray-700 mb-1">Password *</label>
                <div style="position: relative; display: flex; align-items: center; width: 100%;">
                    <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input id="password" :type="showPwd ? 'text' : 'password'" name="password" required placeholder="Masukkan password"
                           style="padding-left: 36px;"
                           class="w-full rounded-xl border border-telkom-gray-200 pr-12 py-2.5 text-xs focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
                    <button type="button" @click="showPwd = !showPwd"
                            class="absolute right-0 top-0 h-full px-3 flex items-center text-telkom-gray-400 hover:text-telkom-gray-600 transition-colors focus:outline-none"
                            :title="showPwd ? 'Sembunyikan' : 'Tampilkan'">
                        <svg x-show="!showPwd" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showPwd" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Konfirmasi Password --}}
            <div x-data="{ showPwdConf: false }">
                <label for="password_confirmation" class="block text-xs font-semibold text-telkom-gray-700 mb-1">Konfirmasi Password *</label>
                <div style="position: relative; display: flex; align-items: center; width: 100%;">
                    <div style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input id="password_confirmation" :type="showPwdConf ? 'text' : 'password'" name="password_confirmation" required placeholder="Konfirmasi password"
                           style="padding-left: 36px;"
                           class="w-full rounded-xl border border-telkom-gray-200 pr-12 py-2.5 text-xs focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
                    <button type="button" @click="showPwdConf = !showPwdConf"
                            class="absolute right-0 top-0 h-full px-3 flex items-center text-telkom-gray-400 hover:text-telkom-gray-600 transition-colors focus:outline-none"
                            :title="showPwdConf ? 'Sembunyikan' : 'Tampilkan'">
                        <svg x-show="!showPwdConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showPwdConf" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit"
                    class="w-full bg-telkom-500 hover:bg-telkom-600 active:bg-telkom-700 text-white font-bold py-3 rounded-xl transition-all duration-200 shadow-md hover:shadow-lg text-xs mt-2">
                Daftar
            </button>
        </div>
    </form>

    <p class="text-center text-xs text-telkom-gray-500 mt-5">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-telkom-500 hover:text-telkom-600 font-bold">Masuk di sini</a>
    </p>
</x-layouts.guest>
