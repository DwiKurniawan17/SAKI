import React from 'react';
import { formatRupiah } from './ParameterSlider';
import { Sparkles, ArrowRight, Zap, AlertTriangle, CheckCircle } from 'lucide-react';

export default function GoalSeekAdvisor({ goalSeek, currentParams, onApplyRecommendation }) {
    if (!goalSeek || !goalSeek.needed) return null;

    const minMrc = goalSeek.min_mrc ?? goalSeek.best_mrc;
    const minOtc = goalSeek.min_otc ?? goalSeek.best_otc;
    const maxCapex = goalSeek.max_capex ?? goalSeek.best_capex;
    const minDurasi = goalSeek.min_durasi ?? goalSeek.best_dur;

    const suggestions = [];

    if (minMrc && minMrc > currentParams.mrc) {
        suggestions.push({
            type: 'mrc',
            title: 'Naikkan Tarif Bulanan (MRC)',
            current: formatRupiah(currentParams.mrc),
            recommended: formatRupiah(minMrc),
            actionLabel: 'Terapkan MRC Baru',
            apply: () => onApplyRecommendation({ mrc: Math.ceil(minMrc) }),
            desc: 'Dengan menaikkan tarif bulanan ke batas ini, proyek langsung mencapai kelayakan.'
        });
    }

    if (maxCapex && maxCapex > 0 && maxCapex < (currentParams.material + currentParams.jasa)) {
        // Proportionally scale material & jasa
        const totalNow = currentParams.material + currentParams.jasa;
        const scale = totalNow > 0 ? maxCapex / totalNow : 1;
        suggestions.push({
            type: 'capex',
            title: 'Efisiensi CAPEX (Material & Jasa)',
            current: formatRupiah(totalNow),
            recommended: formatRupiah(maxCapex),
            actionLabel: 'Kurangi CAPEX Sesuai Batas',
            apply: () => onApplyRecommendation({
                material: Math.round(currentParams.material * scale),
                jasa: Math.round(currentParams.jasa * scale)
            }),
            desc: 'Total anggaran material + jasa tidak boleh melebihi nilai maksimal di atas.'
        });
    }

    if (minOtc && minOtc > currentParams.otc) {
        suggestions.push({
            type: 'otc',
            title: 'Naikkan Biaya Pasang (OTC)',
            current: formatRupiah(currentParams.otc),
            recommended: formatRupiah(minOtc),
            actionLabel: 'Terapkan OTC Baru',
            apply: () => onApplyRecommendation({ otc: Math.ceil(minOtc) }),
            desc: 'Alternatif jika tarif bulanan (MRC) sudah tidak dapat dinaikkan.'
        });
    }

    if (minDurasi && minDurasi > currentParams.durasi_kontrak) {
        suggestions.push({
            type: 'durasi',
            title: 'Perpanjang Durasi Kontrak',
            current: `${currentParams.durasi_kontrak} Bulan`,
            recommended: `${minDurasi} Bulan (${(minDurasi / 12).toFixed(1)} Thn)`,
            actionLabel: 'Perpanjang Kontrak',
            apply: () => onApplyRecommendation({ durasi_kontrak: minDurasi }),
            desc: 'Arus kas tahun berjalan belum menutup Capex; penambahan durasi kontrak akan mengembalikan modal.'
        });
    }

    return (
        <div className="bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-rose-500/10 rounded-2xl border border-amber-300/60 p-5 shadow-xs">
            <div className="flex items-center gap-2.5 mb-2">
                <div className="p-1.5 rounded-lg bg-amber-500 text-white shadow-xs">
                    <Sparkles className="w-4 h-4" />
                </div>
                <div>
                    <h4 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                        Smart Goal Seek: Rekomendasi Solusi Kelayakan
                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                            Rekomendasi Otomatis
                        </span>
                    </h4>
                    <p className="text-xs text-slate-500">
                        Sistem mendeteksi simulasi ini belum layak. Pilih salah satu alternatif penyesuaian di bawah untuk mencapai kelayakan seketika:
                    </p>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                {suggestions.map((s, idx) => (
                    <div
                        key={idx}
                        className="bg-white/90 backdrop-blur-xs p-3.5 rounded-xl border border-amber-200/80 shadow-2xs hover:border-amber-300 flex flex-col justify-between transition-all"
                    >
                        <div>
                            <div className="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1">
                                <span>{s.title}</span>
                            </div>
                            <p className="text-[11px] text-slate-500 mb-2 leading-relaxed">{s.desc}</p>
                            <div className="flex items-center gap-2 text-xs py-1 px-2 rounded-md bg-slate-50 border border-slate-200/80 mb-3 font-mono">
                                <span className="text-slate-400 line-through truncate">{s.current}</span>
                                <ArrowRight className="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                <span className="text-emerald-600 font-bold truncate">{s.recommended}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={s.apply}
                            className="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-all shadow-xs hover:shadow-sm cursor-pointer active:scale-98"
                        >
                            <Zap className="w-3.5 h-3.5" />
                            <span>{s.actionLabel}</span>
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}
