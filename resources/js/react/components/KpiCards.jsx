import React from 'react';
import { formatRupiah, formatPercent } from './ParameterSlider';
import {
    CheckCircle2,
    XCircle,
    TrendingUp,
    Clock,
    DollarSign,
    Percent,
    PieChart,
    ShieldCheck
} from 'lucide-react';

export default function KpiCards({ summary, waccPct, isCalculating }) {
    if (!summary) {
        return (
            <div className="p-8 text-center bg-white rounded-2xl border border-slate-200 shadow-2xs">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3 animate-pulse">
                    <TrendingUp className="w-6 h-6" />
                </div>
                <h4 className="text-sm font-semibold text-slate-700">Menunggu Kalkulasi...</h4>
                <p className="text-xs text-slate-400 mt-1">Ubah parameter di panel kiri untuk melihat indikator kelayakan.</p>
            </div>
        );
    }

    const {
        layak,
        npv,
        irr,
        pbp,
        gpm,
        nim,
        grand_capex,
        total_revenue,
        total_gross_profit,
        total_net_income
    } = summary;

    const waccTarget = (waccPct || 10.9) + 2.0;
    const isNpvOk = npv > 0;
    const allPosCashflow = summary.fcf_list && summary.fcf_list.length > 0 && summary.fcf_list.every(f => f >= 0) && npv > 0;
    const isIrrOk = (irr !== null && irr > waccTarget / 100) || allPosCashflow;
    const isGpmOk = gpm >= 7.0;
    const isNimOk = nim >= 2.0;

    return (
        <div className="space-y-4">
            {/* Feasibility Verdict Banner */}
            <div
                className={`relative overflow-hidden p-5 rounded-2xl border transition-all duration-300 ${
                    layak
                        ? 'bg-gradient-to-br from-emerald-500/10 via-emerald-600/5 to-teal-500/10 border-emerald-500/30 shadow-xs shadow-emerald-500/5'
                        : 'bg-gradient-to-br from-rose-500/10 via-rose-600/5 to-orange-500/10 border-rose-500/30 shadow-xs shadow-rose-500/5'
                }`}
            >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-start sm:items-center gap-3.5">
                        <div
                            className={`w-12 h-12 rounded-xl flex items-center justify-center shrink-0 ${
                                layak
                                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/20'
                                    : 'bg-rose-500 text-white shadow-md shadow-rose-500/20'
                            }`}
                        >
                            {layak ? <CheckCircle2 className="w-6 h-6" /> : <XCircle className="w-6 h-6" />}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    Status Evaluasi Kelayakan
                                </span>
                                {isCalculating && (
                                    <span className="inline-flex items-center gap-1 text-[10px] text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                        Menghitung...
                                    </span>
                                )}
                            </div>
                            <h3
                                className={`text-xl font-black tracking-tight ${
                                    layak ? 'text-emerald-700' : 'text-rose-700'
                                }`}
                            >
                                {layak ? 'PROYEK LAYAK INVESTASI (FEASIBLE)' : 'PROYEK BELUM MEMENUHI SYARAT'}
                            </h3>
                            <p className="text-xs text-slate-500 mt-0.5">
                                {layak
                                    ? 'Parameter memenuhi seluruh 4 kriteria kelayakan Telkom Indonesia (NPV, MIRR, GPM, NIM).'
                                    : 'Satu atau lebih kriteria kelayakan finansial belum tercapai. Periksa rekomendasi di bawah.'}
                            </p>
                        </div>
                    </div>

                    {/* Criteria Check Chips */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px]">
                        <div
                            className={`px-2.5 py-1.5 rounded-lg border flex items-center gap-1.5 font-medium ${
                                isNpvOk
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border-rose-200'
                            }`}
                        >
                            {isNpvOk ? <CheckCircle2 className="w-3.5 h-3.5 shrink-0 text-emerald-600" /> : <XCircle className="w-3.5 h-3.5 shrink-0 text-rose-600" />}
                            <span>NPV &gt; 0</span>
                        </div>

                        <div
                            className={`px-2.5 py-1.5 rounded-lg border flex items-center gap-1.5 font-medium ${
                                isIrrOk
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border-rose-200'
                            }`}
                        >
                            {isIrrOk ? <CheckCircle2 className="w-3.5 h-3.5 shrink-0 text-emerald-600" /> : <XCircle className="w-3.5 h-3.5 shrink-0 text-rose-600" />}
                            <span>{allPosCashflow && irr === null ? 'Cashflow Positif' : `MIRR > ${waccTarget.toFixed(1)}%`}</span>
                        </div>

                        <div
                            className={`px-2.5 py-1.5 rounded-lg border flex items-center gap-1.5 font-medium ${
                                isGpmOk
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border-rose-200'
                            }`}
                        >
                            {isGpmOk ? <CheckCircle2 className="w-3.5 h-3.5 shrink-0 text-emerald-600" /> : <XCircle className="w-3.5 h-3.5 shrink-0 text-rose-600" />}
                            <span>GPM &ge; 7%</span>
                        </div>

                        <div
                            className={`px-2.5 py-1.5 rounded-lg border flex items-center gap-1.5 font-medium ${
                                isNimOk
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border-rose-200'
                            }`}
                        >
                            {isNimOk ? <CheckCircle2 className="w-3.5 h-3.5 shrink-0 text-emerald-600" /> : <XCircle className="w-3.5 h-3.5 shrink-0 text-rose-600" />}
                            <span>NIM &ge; 2%</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Grid 6 KPI Cards */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                {/* 1. NPV */}
                <div className="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                    <div className="flex items-center justify-between text-slate-400 mb-1">
                        <span className="text-[11px] font-semibold tracking-tight uppercase text-slate-500">NPV</span>
                        <DollarSign className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className={`text-base font-black font-mono tracking-tight truncate ${npv >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                        {formatRupiah(npv)}
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                        <span>Net Present Value</span>
                    </div>
                </div>

                {/* 2. MIRR */}
                <div className="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                    <div className="flex items-center justify-between text-slate-400 mb-1">
                        <span className="text-[11px] font-semibold tracking-tight uppercase text-slate-500">MIRR</span>
                        <Percent className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className={`text-base font-black font-mono tracking-tight ${isIrrOk ? 'text-emerald-600' : 'text-slate-800'}`}>
                        {irr !== null ? formatPercent(irr * 100) : (allPosCashflow ? 'N/A (Positif)' : 'N/A')}
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                        <span>Batas WACC+2%: {waccTarget.toFixed(2)}%</span>
                    </div>
                </div>

                {/* 3. Payback Period */}
                <div className="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                    <div className="flex items-center justify-between text-slate-400 mb-1">
                        <span className="text-[11px] font-semibold tracking-tight uppercase text-slate-500">PBP</span>
                        <Clock className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className="text-base font-black font-mono text-slate-800 tracking-tight">
                        {pbp !== null ? `${Number(pbp).toFixed(2)} Thn` : '> Kontrak'}
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                        <span>Balik Modal</span>
                    </div>
                </div>

                {/* 4. GPM */}
                <div className="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                    <div className="flex items-center justify-between text-slate-400 mb-1">
                        <span className="text-[11px] font-semibold tracking-tight uppercase text-slate-500">GPM</span>
                        <PieChart className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className={`text-base font-black font-mono tracking-tight ${isGpmOk ? 'text-emerald-600' : 'text-rose-600'}`}>
                        {formatPercent(gpm)}
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                        <span>Gross Profit Margin</span>
                    </div>
                </div>

                {/* 5. NIM */}
                <div className="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                    <div className="flex items-center justify-between text-slate-400 mb-1">
                        <span className="text-[11px] font-semibold tracking-tight uppercase text-slate-500">NIM</span>
                        <TrendingUp className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className={`text-base font-black font-mono tracking-tight ${isNimOk ? 'text-emerald-600' : 'text-rose-600'}`}>
                        {formatPercent(nim)}
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                        <span>Net Income Margin</span>
                    </div>
                </div>

                {/* 6. Grand CAPEX */}
                <div className="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                    <div className="flex items-center justify-between text-slate-400 mb-1">
                        <span className="text-[11px] font-semibold tracking-tight uppercase text-slate-500">Grand CAPEX</span>
                        <ShieldCheck className="w-4 h-4 text-slate-400" />
                    </div>
                    <div className="text-base font-black font-mono text-red-600 tracking-tight truncate">
                        {formatRupiah(grand_capex)}
                    </div>
                    <div className="flex items-center gap-1 mt-1 text-[10px] text-slate-400">
                        <span>Total Nilai Investasi</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
