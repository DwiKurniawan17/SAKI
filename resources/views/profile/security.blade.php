<x-layouts.app title="Pengaturan Akun & Password">
    <div class="space-y-6 max-w-2xl">
        <div>
            <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Pengaturan Akun & Password</h1>
            <p class="text-slate-500" style="font-size: 0.85rem;">Kelola informasi profil dan kata sandi akun Anda.</p>
        </div>

        {{-- Profil Summary --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-8 h-8 rounded-lg bg-telkom-50 flex items-center justify-center text-telkom-500">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <h2 class="text-telkom-gray-800" style="font-size: 1rem; font-weight: 600;">Informasi Pengguna</h2>
            </div>
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-telkom-500 to-telkom-600 flex items-center justify-center text-white text-2xl font-bold shadow-md shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm flex-1">
                    <div>
                        <span class="text-telkom-gray-400 block text-xs">Nama Lengkap</span>
                        <span class="font-semibold text-telkom-gray-800">{{ $user->name }}</span>
                    </div>
                    <div>
                        <span class="text-telkom-gray-400 block text-xs">Email</span>
                        <span class="font-semibold text-telkom-gray-800">{{ $user->email }}</span>
                    </div>
                    <div>
                        <span class="text-telkom-gray-400 block text-xs">Role</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-telkom-50 text-telkom-700">
                            {{ $user->getRoleNames()->first() ?? 'User' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Update Password Form --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-8 h-8 rounded-lg bg-telkom-50 flex items-center justify-center text-telkom-500">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <h2 class="text-telkom-gray-800" style="font-size: 1rem; font-weight: 600;">Ubah Kata Sandi</h2>
            </div>
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4 max-w-md">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-xs font-medium text-telkom-gray-700 mb-1">Password Saat Ini</label>
                    <input id="current_password" type="password" name="current_password" required
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-telkom-gray-700 mb-1">Password Baru (Min. 8 karakter)</label>
                    <input id="password" type="password" name="password" required
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-telkom-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                </div>

                <button type="submit" class="inline-flex items-center gap-2 bg-telkom-gray-800 hover:bg-telkom-900 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-all">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Password Baru
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
