<x-layouts.guest title="Verifikasi Email">
    <h2 class="text-lg font-bold text-telkom-gray-800 mb-1">Verifikasi Email Anda</h2>
    <p class="text-sm text-telkom-gray-500 mb-6">Kami telah mengirim link verifikasi ke email Anda. Silakan cek inbox (dan folder spam).</p>

    @if(session('status') == 'verification-link-sent')
    <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-4">
        <p class="text-sm text-green-700">Link verifikasi baru telah dikirim ke email Anda.</p>
    </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-semibold py-3.5 rounded-xl transition-all text-sm">
            Kirim Ulang Email Verifikasi
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-center text-sm text-telkom-gray-500 hover:text-telkom-gray-700 font-medium">
            Logout
        </button>
    </form>
</x-layouts.guest>
