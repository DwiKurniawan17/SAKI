import React, { useState } from 'react';
import { formatRupiah } from './ParameterSlider';
import { Table, ChevronDown, ChevronUp } from 'lucide-react';

export default function FinancialTable({ summary }) {
    const [isOpen, setIsOpen] = useState(false);

    if (!summary || !summary.rows || summary.rows.length === 0) return null;

    const { rows, grand_capex, fcf_list } = summary;

    const metrics = [
        { key: 'revenue', label: 'Pendapatan (Revenue)', isBold: true, highlight: 'text-blue-600' },
        { key: 'direct_cost', label: 'Biaya Langsung (COGS)', isBold: false },
        { key: 'gross_profit', label: 'Laba Kotor (Gross Profit)', isBold: true, highlight: 'text-slate-800' },
        { key: 'opex', label: 'Biaya Operasional (OPEX)', isBold: false },
        { key: 'ebitda', label: 'EBITDA', isBold: false },
        { key: 'depresiasi', label: 'Depresiasi', isBold: false },
        { key: 'ebit', label: 'EBIT', isBold: false },
        { key: 'pajak', label: 'Pajak Penghasilan', isBold: false },
        { key: 'net_income', label: 'Laba Bersih (Net Income)', isBold: true, highlight: 'text-emerald-600' },
        {
            key: 'free_cash_flow',
            label: 'Free Cash Flow (FCF)',
            isBold: true,
            highlight: 'text-red-600',
            getValue: (row, idx) => (fcf_list && fcf_list[idx] !== undefined ? fcf_list[idx] : row.free_cash_flow)
        },
        {
            key: 'cum_fcf',
            label: 'Arus Kas Kumulatif (Cum. FCF)',
            isBold: true,
            highlight: 'text-slate-900',
            getValue: (row, idx) => {
                if (summary.cum_fcf && summary.cum_fcf[idx] !== undefined) return summary.cum_fcf[idx];
                return rows.slice(0, idx + 1).reduce((acc, curr, cIdx) => {
                    const f = fcf_list && fcf_list[cIdx] !== undefined ? fcf_list[cIdx] : curr.free_cash_flow;
                    return acc + (Number(f) || 0);
                }, 0);
            },
            getTotal: () => {
                if (summary.cum_fcf && summary.cum_fcf.length > 0) {
                    return summary.cum_fcf[summary.cum_fcf.length - 1];
                }
                return null;
            }
        }
    ];

    const getRowTotal = (metric) => {
        if (metric.getTotal) return metric.getTotal();
        return rows.reduce((acc, r, idx) => {
            const val = metric.getValue ? metric.getValue(r, idx) : r[metric.key];
            return acc + (Number(val) || 0);
        }, 0);
    };

    return (
        <div className="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden transition-all">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full p-4 flex items-center justify-between bg-slate-50/70 hover:bg-slate-100/80 transition-colors text-left cursor-pointer"
            >
                <div className="flex items-center gap-2.5">
                    <div className="p-1.5 rounded-lg bg-slate-200/80 text-slate-700">
                        <Table className="w-4 h-4" />
                    </div>
                    <div>
                        <h4 className="text-sm font-bold text-slate-800">Tabel Proyeksi Finansial Tahunan</h4>
                        <p className="text-xs text-slate-400">Rincian pendapatan, laba kotor, EBITDA, pajak, dan arus kas per tahun</p>
                    </div>
                </div>

                <div className="flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <span>{isOpen ? 'Tutup Tabel' : 'Buka Rincian Tabel'}</span>
                    {isOpen ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
                </div>
            </button>

            {isOpen && (
                <div className="p-4 overflow-x-auto">
                    <table className="w-full text-xs text-left border-collapse font-sans">
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                                <th className="py-2.5 px-3">Komponen Finansial</th>
                                {rows.map((r, idx) => (
                                    <th key={idx} className="py-2.5 px-3 text-right">
                                        {r.col_name || `Tahun ${r.year || idx + 1}`}
                                    </th>
                                ))}
                                <th className="py-2.5 px-3 text-right bg-slate-100/80 text-slate-800">Total Akumulasi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {metrics.map((m, mIdx) => (
                                <tr key={mIdx} className="hover:bg-slate-50/60 transition-colors">
                                    <td className={`py-2 px-3 ${m.isBold ? 'font-bold text-slate-800' : 'text-slate-600'}`}>
                                        {m.label}
                                    </td>
                                    {rows.map((r, rIdx) => {
                                        const val = m.getValue ? m.getValue(r, rIdx) : r[m.key];
                                        return (
                                            <td
                                                key={rIdx}
                                                className={`py-2 px-3 text-right font-mono ${
                                                    m.isBold ? `font-bold ${m.highlight || 'text-slate-800'}` : 'text-slate-600'
                                                }`}
                                            >
                                                {formatRupiah(val)}
                                            </td>
                                        );
                                    })}
                                    <td
                                        className={`py-2 px-3 text-right font-mono bg-slate-50/50 ${
                                            m.isBold ? `font-black ${m.highlight || 'text-slate-900'}` : 'font-semibold text-slate-700'
                                        }`}
                                    >
                                        {formatRupiah(getRowTotal(m))}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
