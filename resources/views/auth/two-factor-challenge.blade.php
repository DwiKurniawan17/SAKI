<x-layouts.guest title="Verifikasi 2FA">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Two-Factor Authentication</h2>
        <p class="text-xs text-slate-500 mt-1">Masukkan 6 digit kode keamanan dari aplikasi authenticator Anda.</p>
    </div>

    <div x-data="{ useRecovery: false }">
        {{-- OTP Code Form --}}
        <form method="POST" action="{{ url('/two-factor-challenge') }}" x-show="!useRecovery" x-transition class="space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kode OTP <span class="text-red-500">*</span></label>
                <input id="code" type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6"
                       placeholder="••••••"
                       class="h-12 w-full rounded-lg border border-slate-300 px-4 text-center tracking-[0.5em] font-mono text-xl text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition-colors">
            </div>
            <button type="submit" class="h-10 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all text-sm cursor-pointer">
                <i data-lucide="check-shield" class="w-4 h-4"></i>
                Verifikasi Kode
            </button>
        </form>

        {{-- Recovery Code Form --}}
        <form method="POST" action="{{ url('/two-factor-challenge') }}" x-show="useRecovery" x-transition class="space-y-4">
            @csrf
            <div>
                <label for="recovery_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kode Pemulihan Darurat <span class="text-red-500">*</span></label>
                <input id="recovery_code" type="text" name="recovery_code" required
                       placeholder="xxxxx-xxxxx-xxxxx"
                       class="h-10 w-full rounded-lg border border-slate-300 px-4 text-sm font-mono text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none transition-colors">
            </div>
            <button type="submit" class="h-10 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all text-sm cursor-pointer">
                <i data-lucide="key" class="w-4 h-4"></i>
                Verifikasi Pemulihan
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <button @click="useRecovery = !useRecovery" class="text-xs text-red-600 hover:text-red-700 font-semibold hover:underline cursor-pointer">
                <span x-text="useRecovery ? 'Gunakan kode OTP authenticator' : 'Gunakan kode pemulihan darurat (recovery code)'"></span>
            </button>
        </div>
    </div>
</x-layouts.guest>

