<x-layouts.app title="Pengaturan Akun & Keamanan">
    <div class="space-y-6 max-w-2xl">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Akun & Keamanan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola informasi profil akun dan perbarui kata sandi keamanan Anda.</p>
        </div>

        {{-- Profil Summary --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </div>
                <h2 class="text-sm font-bold text-slate-900">Informasi Profil Pengguna</h2>
            </div>
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-full bg-slate-900 flex items-center justify-center text-white text-lg font-bold shadow-xs shrink-0 ring-4 ring-slate-100">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm flex-1">
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Nama Lengkap</span>
                        <span class="font-semibold text-slate-900 text-sm">{{ $user->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Email Perusahaan</span>
                        <span class="font-mono text-xs text-slate-700">{{ $user->email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Role / Unit</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 mt-0.5">
                            {{ $user->role_display_name }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Status Akun</span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 mt-0.5">
                            <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                            Aktif & Terverifikasi
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Update Password Form --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Perbarui Kata Sandi</h2>
                    <p class="text-xs text-slate-500">Pastikan akun Anda menggunakan kombinasi kata sandi yang kuat dan aman.</p>
                </div>
            </div>
            
            <form method="POST" action="{{ route('user-password.update') }}" class="space-y-4 max-w-md">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi Saat Ini</label>
                    <input id="current_password" type="password" name="current_password" required
                           placeholder="••••••••"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi Baru (Min. 8 karakter)</label>
                    <input id="password" type="password" name="password" required
                           placeholder="••••••••"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Konfirmasi Kata Sandi Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           placeholder="••••••••"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                </div>

                <div class="pt-2">
                    <button type="submit" class="h-10 inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold px-5 rounded-lg text-sm shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Simpan Perubahan Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>

