<x-layouts.app title="Pengaturan Akun & Keamanan">
    <div class="space-y-6 max-w-3xl pb-10" x-data="{
        copiedSecret: false,
        copiedCodes: false,
        showDisableForm: false,
        showRegenForm: false,
        copyToClipboard(text, type) {
            navigator.clipboard.writeText(text).then(() => {
                if (type === 'secret') {
                    this.copiedSecret = true;
                    setTimeout(() => this.copiedSecret = false, 2500);
                } else if (type === 'codes') {
                    this.copiedCodes = true;
                    setTimeout(() => this.copiedCodes = false, 2500);
                }
            });
        },
        downloadCodes(codesArray) {
            const content = 'KODE PEMULIHAN DARURAT (RECOVERY CODES) 2FA\n' +
                'Aplikasi: Simulasi AKI/CAPEX PT Telkom Indonesia\n' +
                'Akun: {{ $user->email }}\n' +
                'Dibuat pada: ' + new Date().toLocaleString('id-ID') + '\n\n' +
                'PERINGATAN: Simpan file ini di tempat yang sangat aman. Setiap kode hanya dapat digunakan satu kali jika Anda kehilangan akses ke aplikasi Google Authenticator.\n\n' +
                codesArray.map((c, i) => (i + 1) + '. ' + c).join('\n') + '\n';
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'telkom-aki-2fa-recovery-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    }">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Akun & Keamanan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola identitas pengguna, amankan akun dengan Google Authenticator (2FA), dan perbarui kata sandi Anda.</p>
        </div>

        {{-- Flash Messages --}}
        @if (session('status'))
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-xs text-emerald-800 flex items-start gap-3 shadow-xs">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
                <div class="flex-1 font-medium">{{ session('status') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-900 flex items-start gap-3 shadow-xs">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-bold">Perhatian Keamanan Diperlukan</p>
                    <p class="mt-0.5 text-amber-800">{{ session('warning') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-xs text-rose-800 flex items-start gap-3 shadow-xs">
                <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"></i>
                <div class="flex-1 font-medium">{{ session('error') }}</div>
            </div>
        @endif

        {{-- 1. Profil Summary --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-900">Informasi Profil Pengguna</h2>
                </div>
                <div>
                    @if($user->has2FAEnabled())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            2FA Aktif
                        </span>
                    @elseif($user->mustEnable2FA())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            2FA Wajib Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            2FA Belum Aktif
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <div class="w-14 h-14 rounded-full bg-slate-900 flex items-center justify-center text-white text-lg font-bold shadow-xs shrink-0 ring-4 ring-slate-100">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm flex-1 w-full">
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Nama Lengkap</span>
                        <span class="font-semibold text-slate-900 text-sm">{{ $user->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Email Perusahaan</span>
                        <span class="font-mono text-xs text-slate-700">{{ $user->email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Role & Divisi</span>
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

        {{-- 2. Two-Factor Authentication (Google Authenticator) --}}
        <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-xs space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Autentikasi Dua Langkah (Google Authenticator)</h2>
                        <p class="text-xs text-slate-500">Standar keamanan TOTP (RFC 6238) menggunakan aplikasi Google Authenticator.</p>
                    </div>
                </div>
                <div>
                    @if($user->has2FAEnabled())
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            Terproteksi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            Tidak Aktif
                        </span>
                    @endif
                </div>
            </div>

            {{-- Policy Notice for Mandatory 2FA --}}
            @if($user->mustEnable2FA() && !$user->has2FAEnabled())
                <div class="bg-amber-50/70 border border-amber-200/80 rounded-lg p-3.5 flex items-start gap-3 text-xs text-amber-900">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold">Wajib Diaktifkan:</span>
                        Role <strong>{{ $user->role_display_name }}</strong> memiliki kewenangan akses data keuangan, kalkulasi CAPEX, atau persetujuan RSO. Sistem mewajibkan 2FA aktif untuk melanjutkan operasional simulasi.
                    </div>
                </div>
            @endif

            {{-- CASE A: 2FA BELUM AKTIF DAN TIDAK DALAM MODE SETUP --}}
            @if(! $user->has2FAEnabled() && ! $setupSecret)
                <div class="space-y-4">
                    <div class="rounded-lg bg-slate-50 border border-slate-200/80 p-4 text-xs text-slate-600 space-y-2">
                        <p class="font-semibold text-slate-800">Mengapa Menggunakan Google Authenticator?</p>
                        <ul class="list-disc list-inside space-y-1 text-slate-500">
                            <li>Melindungi akun Anda bahkan jika kata sandi bocor atau diketahui pihak lain.</li>
                            <li>Kode OTP 6-digit berganti setiap 30 detik secara aman di perangkat smartphone Anda.</li>
                            <li>Bekerja secara offline tanpa perlu koneksi internet atau SMS.</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('profile.security.2fa.enable') }}">
                        @csrf
                        <button type="submit" class="h-10 inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold px-5 rounded-lg text-xs shadow-xs transition-colors cursor-pointer">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                            Aktifkan Google Authenticator
                        </button>
                    </form>
                </div>
            @endif

            {{-- CASE B: DALAM PROSES KONFIGURASI / SETUP 2FA --}}
            @if(! $user->has2FAEnabled() && $setupSecret)
                <div class="space-y-6 bg-slate-50/50 border border-slate-200 rounded-xl p-5">
                    <div class="flex items-center justify-between border-b border-slate-200/70 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">1</span>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Setup Google Authenticator</h3>
                        </div>
                        <form method="POST" action="{{ route('profile.security.2fa.cancel') }}">
                            @csrf
                            <button type="submit" class="text-xs text-slate-400 hover:text-slate-600 underline cursor-pointer">Batalkan</button>
                        </form>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- QR Code Display --}}
                        <div class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3">
                            <div class="p-2 bg-white rounded-lg border border-slate-100 flex items-center justify-center">
                                @if(!empty($qrSvg))
                                    <div class="w-44 h-44 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                                        {!! $qrSvg !!}
                                    </div>
                                @elseif(!empty($qrUrl))
                                    <img src="{{ $qrUrl }}" alt="QR Code 2FA Google Authenticator" class="w-44 h-44 object-contain">
                                @else
                                    <div class="w-44 h-44 bg-slate-100 flex items-center justify-center text-xs text-slate-400 text-center p-4">
                                        Gunakan Kunci Rahasia Manual di samping
                                    </div>
                                @endif
                            </div>
                            <span class="text-[11px] font-semibold text-slate-500 text-center flex items-center gap-1">
                                <i data-lucide="scan" class="w-3.5 h-3.5 text-slate-400"></i>
                                Pindai QR di aplikasi Google Authenticator
                            </span>
                        </div>

                        {{-- Manual Secret & Steps --}}
                        <div class="space-y-4 text-xs">
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Langkah 1: Buka Google Authenticator</h4>
                                <p class="text-slate-500 leading-relaxed">Buka aplikasi Google Authenticator di smartphone Anda, lalu tekan tombol <strong>+</strong> dan pilih <strong>Scan a QR code</strong>.</p>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Atau Masukkan Kunci Manual:</h4>
                                <p class="text-slate-500 mb-1.5">Jika kamera tidak berfungsi, pilih <em>Enter a setup key</em> dan masukkan kode di bawah:</p>
                                <div class="flex items-center gap-2">
                                    <code class="px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono text-sm font-bold text-slate-800 tracking-wider flex-1 text-center select-all">
                                        {{ $formattedSecret }}
                                    </code>
                                    <button type="button" @click="copyToClipboard('{{ $setupSecret }}', 'secret')"
                                            class="h-9 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium inline-flex items-center gap-1.5 transition-colors cursor-pointer border border-slate-200">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        <span x-text="copiedSecret ? 'Tersalin!' : 'Salin'"></span>
                                    </button>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    Nama Akun: <span class="font-mono text-slate-600">{{ $user->email }}</span> &bull; Jenis: Berbasis Waktu (TOTP)
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Konfirmasi OTP Code --}}
                    <div class="border-t border-slate-200/70 pt-5">
                        <div class="max-w-md space-y-3">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Langkah 2: Masukkan 6 Digit Kode Verifikasi</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Ketik 6 digit kode yang tampil di Google Authenticator untuk mengonfirmasi aktivasi.</p>
                            </div>

                            <form method="POST" action="{{ route('profile.security.2fa.confirm') }}" class="space-y-4">
                                @csrf
                                <div>
                                    <input type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="6"
                                           placeholder="123456"
                                           value="{{ old('code') }}"
                                           class="h-12 w-full max-w-[220px] rounded-lg border {{ $errors->has('code') ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-300 focus:border-red-600 focus:ring-red-600' }} bg-white px-4 text-center tracking-[0.4em] font-mono text-2xl font-bold text-slate-900 focus:outline-none focus:ring-1 transition-colors">
                                    
                                    @error('code')
                                        <p class="text-xs text-rose-600 font-medium mt-1.5 flex items-center gap-1">
                                            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="submit" class="h-10 inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold px-5 rounded-lg text-xs shadow-xs transition-colors cursor-pointer">
                                        <i data-lucide="check-shield" class="w-4 h-4"></i>
                                        Konfirmasi & Aktifkan 2FA
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- CASE C: 2FA SUDAH AKTIF --}}
            @if($user->has2FAEnabled())
                <div class="space-y-5">
                    {{-- Fresh Recovery Codes Alert (Ditampilkan setelah baru aktif atau setelah baru diregenerate) --}}
                    @if(session('fresh_recovery_codes'))
                        <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-5 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-200/80 flex items-center justify-center text-amber-800 shrink-0">
                                    <i data-lucide="key" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-950">Simpan Kode Pemulihan Darurat Anda Sekarang!</h4>
                                    <p class="text-xs text-amber-800 mt-0.5 leading-relaxed">
                                        Kode pemulihan ini dapat digunakan untuk login jika smartphone atau aplikasi Google Authenticator Anda hilang.
                                        <strong>Kode-kode ini hanya ditampilkan sekarang. Simpan di tempat yang aman (misalnya password manager).</strong>
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 font-mono text-xs font-bold text-slate-800 bg-white p-4 rounded-lg border border-amber-200">
                                @foreach(session('fresh_recovery_codes') as $code)
                                    <div class="bg-slate-50 px-2.5 py-1.5 rounded border border-slate-200 text-center select-all">
                                        {{ $code }}
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <button type="button" @click="copyToClipboard('{{ implode("\n", session('fresh_recovery_codes')) }}', 'codes')"
                                        class="h-9 inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-medium px-4 rounded-lg text-xs transition-colors cursor-pointer">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span x-text="copiedCodes ? 'Semua Kode Disalin!' : 'Salin Semua Kode'"></span>
                                </button>

                                <button type="button" @click="downloadCodes({{ json_encode(session('fresh_recovery_codes')) }})"
                                        class="h-9 inline-flex items-center gap-2 bg-white hover:bg-slate-100 text-slate-800 font-medium px-4 rounded-lg text-xs border border-slate-300 transition-colors cursor-pointer">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    Unduh File Teks (.txt)
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-emerald-50/60 rounded-xl border border-emerald-200 text-xs gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700 shrink-0">
                                <i data-lucide="shield-check" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="font-bold text-emerald-900">Google Authenticator Aktif</p>
                                <p class="text-emerald-700 text-[11px] mt-0.5">
                                    Terkonfirmasi sejak: {{ $user->two_factor_confirmed_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Recovery Codes Section & Regeneration --}}
                    <div class="rounded-xl border border-slate-200 p-4 space-y-3 bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Kode Pemulihan Darurat (Recovery Codes)</h4>
                                <p class="text-[11px] text-slate-500">Digunakan untuk login jika Anda tidak membawa smartphone.</p>
                            </div>
                            <button type="button" @click="showRegenForm = !showRegenForm"
                                    class="text-xs text-red-600 hover:text-red-700 font-semibold underline cursor-pointer">
                                <span x-text="showRegenForm ? 'Tutup Form' : 'Regenerasi Kode Baru'"></span>
                            </button>
                        </div>

                        {{-- Form Regenerate Recovery Codes --}}
                        <div x-show="showRegenForm" x-transition class="pt-3 border-t border-slate-100 space-y-3">
                            <p class="text-xs text-amber-700 bg-amber-50 p-2.5 rounded border border-amber-200">
                                <strong>Peringatan:</strong> Meregenerasi kode akan membatalkan semua kode pemulihan yang lama. Masukkan kata sandi saat ini untuk melanjutkan:
                            </p>
                            <form method="POST" action="{{ route('profile.security.2fa.recovery-codes') }}" class="space-y-3 max-w-sm">
                                @csrf
                                <div>
                                    <input type="password" name="password_for_recovery" required placeholder="Kata sandi saat ini"
                                           class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600">
                                    @error('password_for_recovery')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="h-8 px-4 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium inline-flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                    Buat 8 Kode Pemulihan Baru
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Form Nonaktifkan 2FA --}}
                    <div class="rounded-xl border border-slate-200 p-4 space-y-3 bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-slate-900">Nonaktifkan 2FA</h4>
                                <p class="text-[11px] text-slate-500">Matikan verifikasi Google Authenticator jika ingin mengganti perangkat.</p>
                            </div>
                            <button type="button" @click="showDisableForm = !showDisableForm"
                                    class="text-xs text-rose-600 hover:text-rose-700 font-semibold underline cursor-pointer">
                                <span x-text="showDisableForm ? 'Batal' : 'Nonaktifkan 2FA'"></span>
                            </button>
                        </div>

                        <div x-show="showDisableForm" x-transition class="pt-3 border-t border-slate-100 space-y-3">
                            <p class="text-xs text-rose-700 bg-rose-50 p-2.5 rounded border border-rose-200">
                                <strong>Peringatan Keamanan:</strong> Menghapus proteksi 2FA membuat akun Anda rentan terhadap pembajakan sandi. Masukkan kata sandi saat ini untuk konfirmasi:
                            </p>
                            <form method="POST" action="{{ route('profile.security.2fa.disable') }}" class="space-y-3 max-w-sm">
                                @csrf
                                <div>
                                    <input type="password" name="password_for_disable" required placeholder="Kata sandi saat ini"
                                           class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-rose-600 focus:outline-none focus:ring-1 focus:ring-rose-600">
                                    @error('password_for_disable')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="h-8 px-4 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    Konfirmasi Nonaktifkan 2FA
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- 3. Update Password Form --}}
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
                    <label for="current_password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi Saat Ini <span class="text-red-500">*</span></label>
                    <input id="current_password" type="password" name="current_password" required
                           placeholder="••••••••"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                    @error('current_password', 'updatePassword')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi Baru (Min. 8 karakter) <span class="text-red-500">*</span></label>
                    <input id="password" type="password" name="password" required
                           placeholder="••••••••"
                           class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
                    @error('password', 'updatePassword')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Konfirmasi Kata Sandi Baru <span class="text-red-500">*</span></label>
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
