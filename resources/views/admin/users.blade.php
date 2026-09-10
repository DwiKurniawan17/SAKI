<x-layouts.app title="Kelola User">
    <div class="space-y-6">
        <div>
            <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Manajemen Pengguna</h1>
            <p class="text-slate-500" style="font-size: 0.85rem;">Kelola persetujuan dan daftar pengguna aplikasi.</p>
        </div>

        @if(count($pending) > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                </div>
                <h2 class="text-sm font-bold text-amber-900">Menunggu Persetujuan ({{ count($pending) }})</h2>
            </div>
            <div class="space-y-2">
                @foreach($pending as $pUser)
                <div class="bg-white p-4 rounded-xl border border-amber-200 flex items-center justify-between hover:shadow-sm transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 text-sm font-bold shrink-0">
                            {{ strtoupper(substr($pUser->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold text-telkom-gray-800 text-sm">{{ $pUser->name }}</p>
                            <p class="text-xs text-telkom-gray-500">{{ $pUser->email }} | Role: {{ $pUser->getRoleNames()->first() }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.users.approve', $pUser) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white font-semibold px-3 py-1.5 rounded-lg text-xs transition-colors">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.reject', $pUser) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-3 py-1.5 rounded-lg text-xs transition-colors">
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

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-telkom-50 flex items-center justify-center text-telkom-500">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
                <h2 class="text-telkom-gray-800" style="font-size: 1rem; font-weight: 600;">Semua Pengguna</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size: 0.83rem;">
                    <thead>
                        <tr class="text-left border-b border-slate-100">
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Nama</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Email</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Role</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allUsers as $u)
                        <tr class="border-b border-slate-50 hover:bg-telkom-50/30 transition-colors">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-telkom-100 flex items-center justify-center text-telkom-700 text-xs font-bold shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-telkom-gray-800">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-xs text-telkom-gray-600">{{ $u->email }}</td>
                            <td class="px-6 py-3.5">
                                <form method="POST" action="{{ route('admin.users.updateRole', $u) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" onchange="this.form.submit()" class="text-xs rounded-lg border border-slate-200 px-2 py-1 bg-slate-50 focus:ring-1 focus:ring-telkom-500 outline-none font-semibold">
                                        <option value="sales_marketing" {{ $u->hasRole('sales_marketing') ? 'selected' : '' }}>Sales Marketing</option>
                                        <option value="engineering_capex" {{ $u->hasRole('engineering_capex') ? 'selected' : '' }}>Engineering (ED)</option>
                                        <option value="admin" {{ $u->hasRole('admin') ? 'selected' : '' }}>RSO (Admin)</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-3.5">
                                @if($u->is_approved)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                        <i data-lucide="check-circle-2" class="w-3 h-3 mr-1"></i>
                                        Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        <i data-lucide="clock" class="w-3 h-3 mr-1"></i>
                                        Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $allUsers->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
