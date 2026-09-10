<x-layouts.app title="Audit Log">
    <div class="space-y-6">
        <div>
            <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Audit Log & Aktivitas</h1>
            <p class="text-slate-500" style="font-size: 0.85rem;">Catatan aktivitas dan keamanan dalam sistem.</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-telkom-50 flex items-center justify-center text-telkom-500">
                    <i data-lucide="scroll-text" class="w-4 h-4"></i>
                </div>
                <h2 class="text-telkom-gray-800" style="font-size: 1rem; font-weight: 600;">Log Aktivitas Sistem</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left" style="font-size: 0.83rem;">
                    <thead>
                        <tr class="text-left border-b border-slate-100">
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Waktu</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Pelaku</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Aktivitas</th>
                            <th class="px-6 pb-3 pt-4 font-semibold text-telkom-gray-800 uppercase tracking-wider" style="font-size: 0.7rem;">Log Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr class="border-b border-slate-50 hover:bg-telkom-50/30 transition-colors">
                            <td class="px-6 py-3.5 text-xs font-mono text-telkom-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-telkom-100 flex items-center justify-center text-telkom-700 text-xs font-bold shrink-0">
                                        {{ $log->causer ? strtoupper(substr($log->causer->name, 0, 1)) : 'S' }}
                                    </div>
                                    <span class="font-semibold text-telkom-gray-800 text-xs">{{ $log->causer ? $log->causer->name : 'System' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-xs text-telkom-gray-700">{{ $log->description }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-telkom-gray-100 text-telkom-gray-700">
                                    {{ $log->log_name }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>
