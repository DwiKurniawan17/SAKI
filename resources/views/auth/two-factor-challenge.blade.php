<x-layouts.guest title="Verifikasi 2FA">
    <h2 class="text-lg font-bold text-telkom-gray-800 mb-1">Two-Factor Authentication</h2>
    <p class="text-sm text-telkom-gray-500 mb-6">Masukkan kode 6 digit dari aplikasi authenticator Anda.</p>

    <div x-data="{ useRecovery: false }">
        {{-- OTP Code Form --}}
        <form method="POST" action="{{ url('/two-factor-challenge') }}" x-show="!useRecovery" x-transition>
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-telkom-gray-700 mb-1">Kode OTP</label>
                    <input id="code" type="text" name="code" autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6"
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-3 text-sm text-center tracking-[0.5em] font-mono text-lg focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none">
                </div>
                <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-semibold py-3.5 rounded-xl transition-all text-sm">
                    Verifikasi
                </button>
            </div>
        </form>

        {{-- Recovery Code Form --}}
        <form method="POST" action="{{ url('/two-factor-challenge') }}" x-show="useRecovery" x-transition>
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="recovery_code" class="block text-sm font-medium text-telkom-gray-700 mb-1">Recovery Code</label>
                    <input id="recovery_code" type="text" name="recovery_code"
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none">
                </div>
                <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-semibold py-3.5 rounded-xl transition-all text-sm">
                    Verifikasi
                </button>
            </div>
        </form>

        <button @click="useRecovery = !useRecovery" class="w-full text-center text-sm text-telkom-500 hover:text-telkom-600 font-medium mt-4">
            <span x-text="useRecovery ? 'Gunakan kode OTP' : 'Gunakan recovery code'"></span>
        </button>
    </div>
</x-layouts.guest>
