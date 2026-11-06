import React from 'react';

export function formatRupiah(value) {
    if (value === null || value === undefined || isNaN(value)) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value);
}

export function formatPercent(value) {
    if (value === null || value === undefined || isNaN(value)) return '0.00%';
    return `${Number(value).toFixed(2)}%`;
}

export function formatNumber(value) {
    if (value === null || value === undefined || isNaN(value)) return '0';
    return new Intl.NumberFormat('id-ID').format(value);
}

export default function ParameterSlider({
    label,
    value,
    onChange,
    min = 0,
    max = 100,
    step = 1,
    unit = '',
    type = 'number', // 'currency', 'percent', 'number', 'months'
    description = '',
    badge = '',
    disabled = false
}) {
    const handleSliderChange = (e) => {
        onChange(Number(e.target.value));
    };

    const handleInputChange = (e) => {
        const val = Number(e.target.value.replace(/[^0-9.-]+/g, ''));
        onChange(isNaN(val) ? 0 : val);
    };

    const getDisplayValue = () => {
        if (type === 'currency') return formatRupiah(value);
        if (type === 'percent') return formatPercent(value);
        if (type === 'months') return `${value} Bulan (${(value / 12).toFixed(1)} Thn)`;
        return `${formatNumber(value)} ${unit}`.trim();
    };

    return (
        <div className="bg-white/80 backdrop-blur-xs p-3.5 rounded-xl border border-slate-200/80 shadow-2xs hover:border-slate-300 transition-all">
            <div className="flex items-center justify-between gap-2 mb-1.5">
                <div className="flex items-center gap-1.5">
                    <label className="text-xs font-semibold text-slate-700">{label}</label>
                    {badge && (
                        <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                            {badge}
                        </span>
                    )}
                </div>
                <span className="text-xs font-bold text-red-600 font-mono tracking-tight bg-red-50/80 px-2 py-0.5 rounded-md border border-red-100">
                    {getDisplayValue()}
                </span>
            </div>

            {description && (
                <p className="text-[11px] text-slate-400 mb-2 leading-tight">{description}</p>
            )}

            <div className="flex items-center gap-3">
                <input
                    type="range"
                    min={min}
                    max={max}
                    step={step}
                    value={value}
                    disabled={disabled}
                    onChange={handleSliderChange}
                    className="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                />

                <div className="relative shrink-0 w-28">
                    <input
                        type="number"
                        min={min}
                        step="any"
                        value={value}
                        disabled={disabled}
                        onChange={handleInputChange}
                        className="w-full text-right font-mono text-xs font-medium py-1 px-2 border border-slate-200 rounded-lg bg-slate-50/70 focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 disabled:opacity-50"
                    />
                </div>
            </div>
        </div>
    );
}
