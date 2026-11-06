import React, { useState } from 'react';
import {
    ResponsiveContainer,
    ComposedChart,
    BarChart,
    Bar,
    Line,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ReferenceLine,
    Cell
} from 'recharts';
import { formatRupiah } from './ParameterSlider';
import { BarChart3, LineChart as LineIcon } from 'lucide-react';

export default function CashflowChart({ summary }) {
    const [chartMode, setChartMode] = useState('cumulative'); // 'cumulative' | 'breakdown'

    if (!summary || !summary.rows || summary.rows.length === 0) {
        return null;
    }

    const { rows, grand_capex, fcf_list } = summary;

    // 1. Prepare Cumulative Free Cash Flow Data (matching engine.py & Payback Period exactly)
    // In engine.py:
    // fcf_list[0] = NCF_1 - grand_capex (Year 1 FCF after deducting initial investment)
    // fcf_list[idx] = NCF_idx (subsequent operating free cash flows)
    // cum_fcf[idx] = cum_fcf[idx-1] + fcf_list[idx]
    let runningCum = -grand_capex;
    const cumulativeData = [
        {
            year: 'Tahun 0',
            fcf: -grand_capex,
            cumulative: -grand_capex,
            capex: grand_capex,
            netIncome: 0
        },
        ...rows.map((r, idx) => {
            const rawFcf = (fcf_list && fcf_list[idx] !== undefined) ? fcf_list[idx] : (r.free_cash_flow || 0);
            const operatingFcf = idx === 0 ? (rawFcf + grand_capex) : rawFcf;
            runningCum += operatingFcf;

            return {
                year: r.col_name || `Tahun ${r.tahun || idx + 1}`,
                fcf: rawFcf,
                operatingCashflow: operatingFcf,
                cumulative: runningCum,
                revenue: r.revenue,
                directCost: r.direct_cost,
                netIncome: r.net_income
            };
        })
    ];

    // 2. Prepare Annual Breakdown Data
    const breakdownData = rows.map((r, idx) => ({
        year: r.col_name || `Tahun ${r.tahun || idx + 1}`,
        revenue: r.revenue,
        directCost: r.direct_cost,
        grossProfit: r.gross_profit,
        netIncome: r.net_income,
        fcf: fcf_list && fcf_list[idx] !== undefined ? fcf_list[idx] : r.free_cash_flow
    }));

    const CustomTooltip = ({ active, payload, label }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-slate-900/95 backdrop-blur-md text-white p-3 rounded-xl shadow-xl border border-slate-700 text-xs">
                    <p className="font-bold text-slate-200 mb-2 border-b border-slate-700/80 pb-1">{label}</p>
                    {payload.map((entry, index) => (
                        <div key={`item-${index}`} className="flex items-center justify-between gap-4 py-0.5">
                            <span className="flex items-center gap-1.5 text-slate-300">
                                <span className="w-2 h-2 rounded-full" style={{ backgroundColor: entry.color }}></span>
                                {entry.name}:
                            </span>
                            <span className="font-mono font-semibold" style={{ color: entry.color }}>
                                {formatRupiah(entry.value)}
                            </span>
                        </div>
                    ))}
                </div>
            );
        }
        return null;
    };

    return (
        <div className="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-2xs">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h4 className="text-sm font-bold text-slate-800">Visualisasi Proyeksi Arus Kas & Finansial</h4>
                    <p className="text-xs text-slate-400">Analisis pergerakan modal investasi, arus kas bebas, dan profit tahunan</p>
                </div>

                <div className="inline-flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs">
                    <button
                        type="button"
                        onClick={() => setChartMode('cumulative')}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-all cursor-pointer ${
                            chartMode === 'cumulative'
                                ? 'bg-white text-red-600 font-bold shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <LineIcon className="w-3.5 h-3.5" />
                        <span>Arus Kas & Payback (FCF)</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => setChartMode('breakdown')}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-all cursor-pointer ${
                            chartMode === 'breakdown'
                                ? 'bg-white text-red-600 font-bold shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <BarChart3 className="w-3.5 h-3.5" />
                        <span>Pendapatan vs Biaya</span>
                    </button>
                </div>
            </div>

            <div className="h-72 w-full">
                <ResponsiveContainer width="100%" height="100%">
                    {chartMode === 'cumulative' ? (
                        <ComposedChart data={cumulativeData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                            <defs>
                                <linearGradient id="colorCum" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#10b981" stopOpacity={0.25} />
                                    <stop offset="95%" stopColor="#10b981" stopOpacity={0.0} />
                                </linearGradient>
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                            <XAxis dataKey="year" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={{ stroke: '#cbd5e1' }} />
                            <YAxis
                                tick={{ fontSize: 10, fill: '#64748b' }}
                                axisLine={false}
                                tickFormatter={(val) => {
                                    if (Math.abs(val) >= 1_000_000_000) return `${(val / 1_000_000_000).toFixed(1)}M`;
                                    if (Math.abs(val) >= 1_000_000) return `${(val / 1_000_000).toFixed(0)}jt`;
                                    return val;
                                }}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend wrapperStyle={{ fontSize: '11px', paddingTop: '10px' }} />
                            <ReferenceLine y={0} stroke="#94a3b8" strokeDasharray="3 3" />
                            <Bar dataKey="fcf" name="Free Cash Flow Tahunan" radius={[4, 4, 0, 0]} maxBarSize={40}>
                                {cumulativeData.map((entry, index) => (
                                    <Cell key={`cell-${index}`} fill={entry.fcf >= 0 ? '#10b981' : '#ef4444'} />
                                ))}
                            </Bar>
                            <Area
                                type="monotone"
                                dataKey="cumulative"
                                name="Akumulasi FCF (Payback)"
                                stroke="#10b981"
                                strokeWidth={2.5}
                                fillOpacity={1}
                                fill="url(#colorCum)"
                            />
                        </ComposedChart>
                    ) : (
                        <BarChart data={breakdownData} margin={{ top: 10, right: 10, left: 10, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" vertical={false} />
                            <XAxis dataKey="year" tick={{ fontSize: 11, fill: '#64748b' }} axisLine={{ stroke: '#cbd5e1' }} />
                            <YAxis
                                tick={{ fontSize: 10, fill: '#64748b' }}
                                axisLine={false}
                                tickFormatter={(val) => {
                                    if (Math.abs(val) >= 1_000_000_000) return `${(val / 1_000_000_000).toFixed(1)}M`;
                                    if (Math.abs(val) >= 1_000_000) return `${(val / 1_000_000).toFixed(0)}jt`;
                                    return val;
                                }}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend wrapperStyle={{ fontSize: '11px', paddingTop: '10px' }} />
                            <Bar dataKey="revenue" name="Pendapatan (Revenue)" fill="#3b82f6" radius={[4, 4, 0, 0]} maxBarSize={30} />
                            <Bar dataKey="directCost" name="Biaya Langsung (COGS)" fill="#f59e0b" radius={[4, 4, 0, 0]} maxBarSize={30} />
                            <Bar dataKey="netIncome" name="Laba Bersih (Net Income)" fill="#10b981" radius={[4, 4, 0, 0]} maxBarSize={30} />
                        </BarChart>
                    )}
                </ResponsiveContainer>
            </div>
        </div>
    );
}
