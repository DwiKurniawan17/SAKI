<x-layouts.guest title="Login">
    <h2 class="text-lg font-bold text-telkom-gray-800 mb-1">Masuk ke Akun Anda</h2>
    <p class="text-sm text-telkom-gray-500 mb-6">Gunakan email dan password yang terdaftar.</p>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4">
        @foreach($errors->all() as $error)
            <p class="text-sm text-red-700">{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-telkom-gray-700 mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-xl border border-telkom-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
            </div>

            <div x-data="{ showPassword: false }">
                <label for="password" class="block text-sm font-medium text-telkom-gray-700 mb-1">Password</label>
                <div style="position: relative; width: 100%;">
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-3 pr-12 text-sm focus:ring-2 focus:ring-telkom-500 focus:border-telkom-500 outline-none transition-all">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-0 top-0 h-full px-3 flex items-center text-telkom-gray-400 hover:text-telkom-gray-600 transition-colors focus:outline-none"
                            :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                        {{-- Eye icon (show) --}}
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{-- Eye-off icon (hide) --}}
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>


            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-telkom-gray-300 text-telkom-500 focus:ring-telkom-500">
                    <span class="text-sm text-telkom-gray-600">Ingat saya</span>
                </label>

                @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-telkom-500 hover:text-telkom-600 font-medium">
                    Lupa password?
                </a>
                @endif
            </div>

            <button type="submit"
                    class="w-full bg-telkom-500 hover:bg-telkom-600 active:bg-telkom-700 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-md hover:shadow-lg text-sm">
                Masuk
            </button>
        </div>
    </form>

    <p class="text-center text-sm text-telkom-gray-500 mt-6">
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-telkom-500 hover:text-telkom-600 font-semibold">Daftar di sini</a>
    </p>
</x-layouts.guest>
