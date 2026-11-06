<x-layouts.guest title="Masuk">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Masuk ke Sistem</h2>
        <p class="text-xs text-slate-500 mt-1">Portal Simulasi AKI & Evaluasi Investasi CAPEX</p>
    </div>

    @if(session('status'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mb-5 flex items-start gap-2.5">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
        <p class="text-xs text-emerald-700 font-medium leading-relaxed">{{ session('status') }}</p>
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

    <div id="login-root" x-data="{
        email: '{{ old('email') }}',
        password: '',
        showPassword: false,
        selectedEmail: '{{ old('email') }}',
        fill(e, p, autoSubmit = false) {
            this.email = e;
            this.password = p;
            this.selectedEmail = e;

            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            const formEl = document.getElementById('form-login');

            if (emailInput) {
                emailInput.value = e;
                emailInput.dispatchEvent(new Event('input', { bubbles: true }));
                emailInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (passInput) {
                passInput.value = p;
                passInput.dispatchEvent(new Event('input', { bubbles: true }));
                passInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (autoSubmit && formEl) {
                formEl.submit();
                return;
            }

            $nextTick(() => {
                const submitBtn = document.getElementById('btn-submit-login');
                if (submitBtn) {
                    submitBtn.focus();
                }
            });
        }
    }">
        <form id="form-login" method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <div class="relative">
                    <input id="email" type="email" name="email" x-model="email" value="{{ old('email') }}" required autofocus
                           placeholder="nama@telkom.co.id"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Kata Sandi</label>
                    @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-red-600 hover:text-red-700 font-medium hover:underline">
                        Lupa sandi?
                    </a>
                    @endif
                </div>
                <div class="relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required
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

            <button id="btn-submit-login" type="submit"
                    class="h-10 w-full mt-2 inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all duration-150 text-sm cursor-pointer">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                Masuk
            </button>
        </form>

        {{-- ── AKUN UJI COBA CEPAT (TESTING / SEED USERS) ────────────── --}}
        <div class="mt-6 pt-5 border-t border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-3.5 bg-red-600 rounded-full"></div>
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Akun Uji Coba (Testing)</span>
                </div>
                <span class="text-[10px] text-slate-400">Klik untuk isi otomatis</span>
            </div>

            <div class="space-y-2">
                @php
                    $testAccounts = [
                        [
                            'name' => 'RSO Administrator (Admin)',
                            'email' => 'admin@telkom.co.id',
                            'role' => 'Review & Approval Akun',
                            'icon' => 'shield-check',
                            'badgeClass' => 'bg-purple-50 text-purple-700 border-purple-200',
                            'hoverClass' => 'hover:border-purple-300 hover:bg-purple-50/40',
                        ],
                        [
                            'name' => 'Engineer Development (ED)',
                            'email' => 'ed@telkom.co.id',
                            'role' => 'Pihak A (Input CAPEX)',
                            'icon' => 'hard-hat',
                            'badgeClass' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'hoverClass' => 'hover:border-blue-300 hover:bg-blue-50/40',
                        ],
                        [
                            'name' => 'Sales Marketing',
                            'email' => 'marketing@telkom.co.id',
                            'role' => 'Pihak B (Revenue & Tarif)',
                            'icon' => 'trending-up',
                            'badgeClass' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'hoverClass' => 'hover:border-emerald-300 hover:bg-emerald-50/40',
                        ],
                    ];
                @endphp

                @foreach($testAccounts as $acc)
                <div data-email="{{ $acc['email'] }}"
                     onclick="window.fillTestAccount('{{ $acc['email'] }}', 'password', false)"
                     @click="fill('{{ $acc['email'] }}', 'password', false)"
                     :class="selectedEmail === '{{ $acc['email'] }}' ? 'border-red-600 bg-red-50/30 ring-1 ring-red-600/30' : 'border-slate-200 bg-white {{ $acc['hoverClass'] }}'"
                     class="test-account-item w-full flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all shadow-xs group cursor-pointer {{ old('email') === $acc['email'] ? 'border-red-600 bg-red-50/30 ring-1 ring-red-600/30' : 'border-slate-200 bg-white ' . $acc['hoverClass'] }}">
                    <div class="flex items-center gap-2.5 text-left flex-1 min-w-0 pr-2 pointer-events-none">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center border {{ $acc['badgeClass'] }} shrink-0">
                            <i data-lucide="{{ $acc['icon'] }}" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-900 group-hover:text-red-600 transition-colors truncate">{{ $acc['name'] }}</span>
                                <span x-show="selectedEmail === '{{ $acc['email'] }}'" class="selected-badge text-[9px] font-bold text-red-700 bg-red-100 px-1.5 py-0.5 rounded uppercase tracking-wider" style="display: none;">Terpilih</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-mono truncate">{{ $acc['email'] }}</p>
                        </div>
                    </div>
                    <button type="button"
                            onclick="event.stopPropagation(); window.fillTestAccount('{{ $acc['email'] }}', 'password', true);"
                            @click.stop="fill('{{ $acc['email'] }}', 'password', true)"
                            title="Klik untuk langsung masuk sebagai {{ $acc['name'] }}"
                            class="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-slate-700 hover:text-white bg-slate-100 hover:bg-red-600 active:bg-red-700 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:border-red-600 transition-all cursor-pointer shadow-xs">
                        <span>Masuk</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </button>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <script>
        window.fillTestAccount = function(e, p, autoSubmit = false) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            const formEl = document.getElementById('form-login');

            if (emailInput) {
                emailInput.value = e;
                emailInput.dispatchEvent(new Event('input', { bubbles: true }));
                emailInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (passInput) {
                passInput.value = p;
                passInput.dispatchEvent(new Event('input', { bubbles: true }));
                passInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const loginRoot = document.getElementById('login-root');
            if (loginRoot && window.Alpine) {
                try {
                    const data = window.Alpine.$data(loginRoot);
                    if (data) {
                        data.email = e;
                        data.password = p;
                        data.selectedEmail = e;
                    }
                } catch(err) {}
            }

            // Visual active highlight
            document.querySelectorAll('.test-account-item').forEach(card => {
                const isMatch = card.dataset.email === e;
                const badge = card.querySelector('.selected-badge');
                if (isMatch) {
                    card.classList.add('border-red-600', 'bg-red-50/30', 'ring-1', 'ring-red-600/30');
                    card.classList.remove('border-slate-200', 'bg-white');
                    if (badge) badge.style.display = 'inline-block';
                } else {
                    card.classList.remove('border-red-600', 'bg-red-50/30', 'ring-1', 'ring-red-600/30');
                    card.classList.add('border-slate-200', 'bg-white');
                    if (badge) badge.style.display = 'none';
                }
            });

            if (autoSubmit && formEl) {
                formEl.submit();
                return;
            }

            const submitBtn = document.getElementById('btn-submit-login');
            if (submitBtn) {
                submitBtn.focus();
            }
        };
    </script>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Belum memiliki akun?
            <a href="{{ route('register') }}" class="font-semibold text-red-600 hover:text-red-700 hover:underline">Registrasi Akun Baru</a>
        </p>
    </div>
</x-layouts.guest>

