<x-layouts.app title="Manajemen Parameter RDM">
    <div class="space-y-6 max-w-2xl mx-auto">
        <div>
            <h1 class="text-telkom-gray-800 mb-1" style="font-size: 1.5rem; font-weight: 700;">Manajemen Parameter RDM</h1>
            <p class="text-slate-500" style="font-size: 0.85rem;">Atur persetujuan Revenue Discount Margin (RDM) yang digunakan untuk menghitung Direct Cost (COGS) otomatis pada simulasi.</p>
        </div>

        <form method="POST" action="{{ route('admin.rdm.update') }}" class="bg-white rounded-2xl p-6 border border-telkom-gray-200 shadow-sm space-y-6">
            @csrf

            <div class="bg-telkom-50 border border-telkom-200 rounded-xl p-4 text-xs text-telkom-gray-700 space-y-1">
                <p class="font-bold text-telkom-gray-800">📌 Ketentuan Rumus Direct Cost:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    <li>COGS Install / OTC = Tarif OTC &times; (1 &minus; RDM OTC%)</li>
                    <li>COGS Bulanan / MRC = Tarif MRC &times; (1 &minus; RDM MRC%)</li>
                </ul>
                <p class="text-telkom-gray-500 pt-1">Perubahan parameter hanya berlaku untuk simulasi baru. Simulasi yang sudah disetujui sebelumnya tidak terpengaruh.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">RDM Tarif OTC (%) *</label>
                    <div class="relative">
                        <input type="number" name="rdm_otc_pct" value="{{ old('rdm_otc_pct', $rdm->rdm_otc_pct) }}" required step="0.01" min="0" max="100"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <span class="absolute right-4 top-2.5 text-slate-400 text-sm font-semibold">%</span>
                    </div>
                    <p class="text-xs text-telkom-gray-400 mt-1">Default Excel: 25.00%</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-telkom-gray-700 mb-1">RDM Tarif MRC (%) *</label>
                    <div class="relative">
                        <input type="number" name="rdm_mrc_pct" value="{{ old('rdm_mrc_pct', $rdm->rdm_mrc_pct) }}" required step="0.01" min="0" max="100"
                               class="w-full rounded-xl border border-telkom-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-telkom-500 outline-none">
                        <span class="absolute right-4 top-2.5 text-slate-400 text-sm font-semibold">%</span>
                    </div>
                    <p class="text-xs text-telkom-gray-400 mt-1">Default Excel: 30.00%</p>
                </div>
            </div>

            @if($rdm->updatedBy)
            <p class="text-xs text-slate-400 italic">
                Terakhir diperbarui oleh {{ $rdm->updatedBy->name }} pada {{ $rdm->updated_at->format('d M Y H:i') }}
            </p>
            @endif

            <button type="submit" class="w-full bg-telkom-500 hover:bg-telkom-600 text-white font-bold py-3.5 rounded-xl transition-all shadow-md text-sm">
                Simpan Perubahan Parameter RDM
            </button>
        </form>
    </div>
</x-layouts.app>
