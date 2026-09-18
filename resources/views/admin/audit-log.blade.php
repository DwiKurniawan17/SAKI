<x-layouts.app title="Audit Log">
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Audit Log & Aktivitas Sistem</h1>
            <p class="text-xs text-slate-500 mt-0.5">Catatan rekam jejak aktivitas operasional pengguna, otorisasi peran, dan keamanan sistem.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600">
                        <i data-lucide="scroll-text" class="w-4 h-4"></i>
                    </div>
                    <h2 class="text-sm font-bold text-slate-900">Rekam Jejak Aktivitas</h2>
                </div>
                <span class="text-xs text-slate-500 font-medium">Menampilkan {{ $logs->count() }} dari {{ $logs->total() }} aktivitas</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Waktu Transaksi</th>
                            <th class="px-6 py-3">Pelaku / Pengguna</th>
                            <th class="px-6 py-3">Deskripsi Aktivitas</th>
                            <th class="px-6 py-3">Kategori Log</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-3.5 text-xs font-mono text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 text-xs font-bold shrink-0">
                                        {{ $log->causer ? strtoupper(substr($log->causer->name, 0, 1)) : 'S' }}
                                    </div>
                                    <span class="font-semibold text-slate-900 text-xs">{{ $log->causer ? $log->causer->name : 'System / Auto' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-xs text-slate-700">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $log->log_name }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-xs text-slate-500">
                                Belum ada catatan aktivitas sistem yang terekam.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>

