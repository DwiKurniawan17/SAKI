<x-layouts.guest title="Lupa Kata Sandi">
    <div class="mb-6">
        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-3 ring-8 ring-red-50/50">
            <i data-lucide="key-round" class="w-6 h-6"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Lupa Kata Sandi?</h2>
        <p class="text-xs text-slate-500 mt-1">
            Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
        </p>
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

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Alamat Email Terdaftar *
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </div>
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus
                       placeholder="nama@telkom.co.id"
                       class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3.5 text-sm text-slate-800 placeholder-slate-400 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600 transition-colors">
            </div>
        </div>

        <button type="submit"
                class="h-10 w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-lg shadow-sm transition-all duration-150 text-sm cursor-pointer">
            <i data-lucide="send" class="w-4 h-4"></i>
            Kirim Tautan Atur Ulang Sandi
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Ingat kata sandi Anda?
            <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:text-red-700 hover:underline">
                Kembali ke Halaman Masuk
            </a>
        </p>
    </div>
</x-layouts.guest>
