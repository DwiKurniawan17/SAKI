<x-layouts.app title="Kelola Pengguna">
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola verifikasi akun pengguna, penetapan unit kerja/role, dan status hak akses.</p>
        </div>

        @if(count($pending) > 0)
        <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center text-amber-700">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-amber-900">Menunggu Persetujuan Akun</h2>
                        <p class="text-xs text-amber-700">Terdapat {{ count($pending) }} pendaftar baru yang memerlukan verifikasi hak akses.</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-200/80 text-amber-900">
                    {{ count($pending) }} Pending
                </span>
            </div>
            <div class="space-y-2.5">
                @foreach($pending as $pUser)
                <div class="bg-white p-3.5 rounded-lg border border-amber-200/60 flex items-center justify-between hover:shadow-xs transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold shrink-0 border border-slate-200">
                            {{ strtoupper(substr($pUser->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $pUser->name }}</p>
                            <p class="text-xs text-slate-500">{{ $pUser->email }} • Role diminta: <span class="font-semibold text-slate-700">{{ $pUser->role_display_name }}</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.users.approve', $pUser) }}">
                            @csrf
                            <button type="submit" class="h-8 inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold px-3 rounded-lg text-xs transition-colors cursor-pointer">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.reject', $pUser) }}">
                            @csrf
                            <button type="submit" class="h-8 inline-flex items-center gap-1.5 bg-white hover:bg-red-50 text-red-600 border border-red-200 font-semibold px-3 rounded-lg text-xs transition-colors cursor-pointer">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                Tolak
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-900">Daftar Pengguna Terdaftar</h2>
                </div>
                <span class="text-xs text-slate-500 font-medium">Total: {{ $allUsers->total() }} Pengguna</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Nama Pengguna</th>
                            <th class="px-6 py-3">Alamat Email</th>
                            <th class="px-6 py-3">Role / Unit Kerja</th>
                            <th class="px-6 py-3">Status Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($allUsers as $u)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-slate-900">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-xs text-slate-600 font-mono">{{ $u->email }}</td>
                            <td class="px-6 py-3.5">
                                <form method="POST" action="{{ route('admin.users.updateRole', $u) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-300 px-2.5 py-1.5 bg-slate-50 text-slate-800 font-medium focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none cursor-pointer">
                                        <option value="sales_marketing" {{ $u->hasRole('sales_marketing') ? 'selected' : '' }}>Sales Marketing</option>
                                        <option value="engineering_capex" {{ $u->hasRole('engineering_capex') ? 'selected' : '' }}>Engineer Development</option>
                                        <option value="admin" {{ $u->hasRole('admin') ? 'selected' : '' }}>RSO (Admin)</option>
                                    </select>
                                </form>
                            </td>

                            <td class="px-6 py-3.5">
                                @if($u->is_approved)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                                        Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $allUsers->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>

