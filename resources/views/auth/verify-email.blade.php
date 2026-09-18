<x-layouts.guest title="Verifikasi Email">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Verifikasi Email Anda</h2>
        <p class="text-xs text-slate-500 mt-1">Kami telah mengirim tautan konfirmasi ke alamat email Anda. Silakan periksa kotak masuk (atau folder spam).</p>
    </div>

    @if(session('status') == 'verification-link-sent')
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mb-5 flex items-start gap-2.5">
        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"></i>
        <p class="text-xs text-emerald-700 font-medium">Tautan verifikasi baru telah berhasil dikirim ke email Anda.</p>
    </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="h-10 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all text-sm cursor-pointer">
            <i data-lucide="mail" class="w-4 h-4"></i>
            Kirim Ulang Email Verifikasi
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 font-medium hover:underline cursor-pointer">
            Keluar dari Sesi (Logout)
        </button>
    </form>
</x-layouts.guest>

