import React, { useState, useEffect, useRef, useCallback } from 'react';
import axios from 'axios';
import ParameterSlider, { formatRupiah, formatPercent } from './components/ParameterSlider';
import KpiCards from './components/KpiCards';
import CashflowChart from './components/CashflowChart';
import GoalSeekAdvisor from './components/GoalSeekAdvisor';
import FinancialTable from './components/FinancialTable';
import {
    Sliders,
    Save,
    RotateCcw,
    Sparkles,
    Building2,
    MapPin,
    Package,
    Wifi,
    Calendar,
    DollarSign,
    Layers,
    FileSpreadsheet,
    Check,
    AlertCircle,
    Info,
    ExternalLink
} from 'lucide-react';

export default function SimulationStudio({
    initialData = null,
    defaultRdm = null,
    catalog = {},
    userRole = 'admin',
    simulasiId = null,
    initialSummary = null,
    wilayahList = []
}) {
    // Default values fallback
    const rdm = defaultRdm || {
        rdm_otc_pct: 25.0,
        rdm_mrc_pct: 30.0,
        bop_pct: 0.4,
        lifetime: 4,
        om_pct: 12.0,
        tax_pct: 22.0,
        wacc_pct: 10.9
    };

    const initialParams = {
        nama_proyek: initialData?.nama_proyek || 'Pengadaan Jaringan Telkom',
        lokasi: initialData?.lokasi || 'Kawasan Bisnis Sudirman, Jakarta',
        wilayah: initialData?.wilayah || (wilayahList && wilayahList.length > 0 ? wilayahList[0] : ''),
        kategori: initialData?.kategori || 'connectivity',
        paketisasi: initialData?.paketisasi || (Object.keys(catalog)[0] || 'Astinet'),
        bandwidth: initialData?.bandwidth || '',
        durasi_kontrak: Number(initialData?.durasi_kontrak ?? 48),
        bulan_tahun1: Number(initialData?.bulan_tahun1 ?? 6),
        otc: Number(initialData?.otc ?? 15000000),
        mrc: Number(initialData?.mrc ?? 28000000),
        material: Number(initialData?.material ?? 85000000),
        jasa: Number(initialData?.jasa ?? 35000000),
        rdm_otc_pct: Number(initialData?.rdm_otc_pct ?? rdm.rdm_otc_pct),
        rdm_mrc_pct: Number(initialData?.rdm_mrc_pct ?? rdm.rdm_mrc_pct),
        bop_pct: Number(initialData?.bop_pct ?? rdm.bop_pct),
        lifetime: Number(initialData?.lifetime ?? rdm.lifetime),
        om_pct: Number(initialData?.om_pct ?? rdm.om_pct),
        tax_pct: Number(initialData?.tax_pct ?? rdm.tax_pct),
        wacc_pct: Number(initialData?.wacc_pct ?? rdm.wacc_pct)
    };

    const [params, setParams] = useState(initialParams);
    const [summary, setSummary] = useState(initialSummary || null);
    const [isCalculating, setIsCalculating] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState(null); // { type: 'success' | 'error', message: '' }
    const [activeTab, setActiveTab] = useState('commercial'); // 'commercial' | 'capex' | 'macro'
    const debounceTimerRef = useRef(null);

    // Get available bandwidths and regions for selected package
    const currentPkg = catalog[params.paketisasi];
    const availableBandwidths = currentPkg?.bw || [];
    const availableWilayahList = (currentPkg?.wilayah && currentPkg.wilayah.length > 0) ? currentPkg.wilayah : wilayahList;

    // Find official price from price list catalog
    const catalogKey = `${params.bandwidth}|${params.wilayah}`;
    const officialPrice = currentPkg?.prices?.[catalogKey] || null;
    const officialPriceUpdatedAt = currentPkg?.updated_at?.[catalogKey] || null;
    const diffPct = officialPrice && params.mrc ? ((params.mrc - officialPrice) / officialPrice) * 100 : null;

    // Helper to update individual parameter
    const updateParam = (key, value) => {
        setParams((prev) => ({
            ...prev,
            [key]: value
        }));
    };

    // Calculate live against Laravel API
    const performCalculation = useCallback(async (currentParams) => {
        setIsCalculating(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post('/api/simulasi/calculate-live', currentParams, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            if (res.data) {
                setSummary(res.data);
            }
        } catch (err) {
            console.error('Calculation error:', err);
        } finally {
            setIsCalculating(false);
        }
    }, []);

    // Debounced recalculation on param change
    useEffect(() => {
        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
        }

        debounceTimerRef.current = setTimeout(() => {
            performCalculation(params);
        }, 300);

        return () => {
            if (debounceTimerRef.current) clearTimeout(debounceTimerRef.current);
        };
    }, [params, performCalculation]);

    // Handle package change
    const handlePackageChange = (pkgName) => {
        const pkgData = catalog[pkgName];
        const newBw = (pkgData?.bw && pkgData.bw.length > 0) ? pkgData.bw[0] : '';
        const pkgWilayah = (pkgData?.wilayah && pkgData.wilayah.length > 0) ? pkgData.wilayah : wilayahList;
        const newWilayah = pkgWilayah.includes(params.wilayah) ? params.wilayah : (pkgWilayah[0] || '');
        setParams((prev) => ({
            ...prev,
            paketisasi: pkgName,
            bandwidth: newBw,
            wilayah: newWilayah
        }));
    };

    // Apply smart recommendation from GoalSeekAdvisor
    const handleApplyRecommendation = (recommendations) => {
        setParams((prev) => ({
            ...prev,
            ...recommendations
        }));
    };

    // Reset to defaults
    const handleReset = () => {
        setParams(initialParams);
    };

    // Scenario Presets
    const applyPreset = (presetName) => {
        if (presetName === 'optimistic') {
            setParams((prev) => ({
                ...prev,
                mrc: Math.round(prev.mrc * 1.2),
                material: Math.round(prev.material * 0.9)
            }));
        } else if (presetName === 'efficient_capex') {
            setParams((prev) => ({
                ...prev,
                material: Math.round(prev.material * 0.8),
                jasa: Math.round(prev.jasa * 0.8)
            }));
        } else if (presetName === 'long_contract') {
            setParams((prev) => ({
                ...prev,
                durasi_kontrak: 60
            }));
        }
    };

    // Save simulation to Database
    const handleSave = async () => {
        setIsSaving(true);
        setSaveStatus(null);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post('/api/simulasi/save-studio', {
                ...params,
                simulasi_id: simulasiId
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            if (res.data && res.data.success) {
                setSaveStatus({
                    type: 'success',
                    message: res.data.message || 'Simulasi berhasil disimpan ke database!'
                });
                if (res.data.redirect_url) {
                    setTimeout(() => {
                        window.location.href = res.data.redirect_url;
                    }, 1200);
                }
            } else {
                setSaveStatus({
                    type: 'error',
                    message: res.data?.error || 'Gagal menyimpan simulasi.'
                });
            }
        } catch (err) {
            setSaveStatus({
                type: 'error',
                message: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan.'
            });
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="space-y-6">
            {/* Top Header Card */}
            <div className="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs">
                <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">
                                <Sparkles className="w-3 h-3 text-red-600" />
                                STUDIO SIMULASI INTERAKTIF
                            </span>
                            <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">
                                REACT 19 SPA
                            </span>
                            {simulasiId && (
                                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                    ID: #{simulasiId}
                                </span>
                            )}
                        </div>
                        <h2 className="text-xl font-black text-slate-900 tracking-tight">
                            Interactive Investment & Feasibility Engine
                        </h2>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Ubah variabel tarif, CAPEX, dan parameter makro secara instan. Hasil NPV, MIRR, dan grafik arus kas diperbarui seketika tanpa refresh halaman.
                        </p>
                    </div>

                    {/* Quick Presets & Actions */}
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="hidden sm:flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 text-xs">
                            <span className="text-[10px] font-bold uppercase text-slate-400 px-2">Preset:</span>
                            <button
                                type="button"
                                onClick={() => applyPreset('optimistic')}
                                className="px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200/80 shadow-2xs transition-all cursor-pointer text-[11px]"
                            >
                                +20% MRC
                            </button>
                            <button
                                type="button"
                                onClick={() => applyPreset('efficient_capex')}
                                className="px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200/80 shadow-2xs transition-all cursor-pointer text-[11px]"
                            >
                                -20% Capex
                            </button>
                            <button
                                type="button"
                                onClick={() => applyPreset('long_contract')}
                                className="px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200/80 shadow-2xs transition-all cursor-pointer text-[11px]"
                            >
                                Kontrak 5 Thn
                            </button>
                        </div>

                        <button
                            type="button"
                            onClick={handleReset}
                            className="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition-all cursor-pointer"
                            title="Reset Parameter ke Awal"
                        >
                            <RotateCcw className="w-4 h-4" />
                        </button>

                        <button
                            type="button"
                            onClick={handleSave}
                            disabled={isSaving}
                            className="flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold text-xs rounded-xl shadow-xs hover:shadow-md transition-all cursor-pointer disabled:opacity-50 active:scale-98"
                        >
                            <Save className="w-4 h-4" />
                            <span>{isSaving ? 'Menyimpan...' : 'Simpan Simulasi'}</span>
                        </button>
                    </div>
                </div>

                {/* Save Feedback Alert */}
                {saveStatus && (
                    <div
                        className={`mt-4 p-3 rounded-xl border flex items-center justify-between text-xs ${saveStatus.type === 'success'
                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                : 'bg-rose-50 text-rose-800 border-rose-200'
                            }`}
                    >
                        <div className="flex items-center gap-2">
                            {saveStatus.type === 'success' ? <Check className="w-4 h-4" /> : <AlertCircle className="w-4 h-4" />}
                            <span>{saveStatus.message}</span>
                        </div>
                    </div>
                )}
            </div>

            {/* Main Interactive Studio Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {/* Left Column: Interactive Controllers (5 Cols) */}
                <div className="lg:col-span-5 space-y-4">
                    {/* Project & Product Meta Card */}
                    <div className="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs space-y-3">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <Building2 className="w-3.5 h-3.5 text-red-600" />
                                Informasi Proyek & Layanan
                            </span>
                            <span className="text-[11px] text-slate-400 font-mono">Langkah 1</span>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-600 mb-1 block">Nama Proyek / Pelanggan</label>
                            <input
                                type="text"
                                value={params.nama_proyek}
                                onChange={(e) => updateParam('nama_proyek', e.target.value)}
                                className="w-full text-xs font-medium px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 bg-slate-50/50 focus:bg-white"
                                placeholder="Contoh: Pengadaan Internet Kantor Pusat"
                            />
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-600 mb-1 block">Lokasi Instalasi</label>
                            <input
                                type="text"
                                value={params.lokasi}
                                onChange={(e) => updateParam('lokasi', e.target.value)}
                                className="w-full text-xs font-medium px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 bg-slate-50/50 focus:bg-white"
                                placeholder="Contoh: Sudirman, Jakarta Selatan"
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                            <div>
                                <label className="text-[11px] font-semibold text-slate-600 mb-1 block">Wilayah</label>
                                <select
                                    value={params.wilayah || ''}
                                    onChange={(e) => updateParam('wilayah', e.target.value)}
                                    className="w-full text-xs font-medium px-2.5 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 bg-white"
                                >
                                    <option value="">-- Pilih Wilayah --</option>
                                    {availableWilayahList && availableWilayahList.length > 0 ? (
                                        availableWilayahList.map((w) => (
                                            <option key={w} value={w}>{w}</option>
                                        ))
                                    ) : (
                                        <option value="Wilayah 1">Wilayah 1</option>
                                    )}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-slate-600 mb-1 block">Paket Produk</label>
                                <select
                                    value={params.paketisasi}
                                    onChange={(e) => handlePackageChange(e.target.value)}
                                    className="w-full text-xs font-medium px-2.5 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 bg-white"
                                >
                                    {Object.keys(catalog).length > 0 ? (
                                        Object.keys(catalog).map((pkg) => (
                                            <option key={pkg} value={pkg}>{pkg}</option>
                                        ))
                                    ) : (
                                        <>
                                            <option value="Astinet">Astinet</option>
                                            <option value="Indibiz">Indibiz</option>
                                            <option value="VPN IP">VPN IP</option>
                                        </>
                                    )}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-slate-600 mb-1 block">Bandwidth</label>
                                <select
                                    value={params.bandwidth}
                                    onChange={(e) => updateParam('bandwidth', e.target.value)}
                                    className="w-full text-xs font-medium px-2.5 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 bg-white"
                                >
                                    <option value="">-- Pilih Bandwidth --</option>
                                    {availableBandwidths.length > 0 ? (
                                        availableBandwidths.map((bw) => (
                                            <option key={bw} value={bw}>{bw}</option>
                                        ))
                                    ) : (
                                        <>
                                            <option value="50 Mbps">50 Mbps</option>
                                            <option value="100 Mbps">100 Mbps</option>
                                            <option value="200 Mbps">200 Mbps</option>
                                            <option value="500 Mbps">500 Mbps</option>
                                            <option value="1 Gbps">1 Gbps</option>
                                        </>
                                    )}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Tabs for Parameter Groups */}
                    <div className="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-2xs space-y-4">
                        <div className="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-semibold">
                            <button
                                type="button"
                                onClick={() => setActiveTab('commercial')}
                                className={`flex-1 py-1.5 rounded-lg transition-all cursor-pointer ${activeTab === 'commercial' ? 'bg-white text-red-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                    }`}
                            >
                                Tarif & Komersial
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('capex')}
                                className={`flex-1 py-1.5 rounded-lg transition-all cursor-pointer ${activeTab === 'capex' ? 'bg-white text-red-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                    }`}
                            >
                                CAPEX & Investasi
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('macro')}
                                className={`flex-1 py-1.5 rounded-lg transition-all cursor-pointer ${activeTab === 'macro' ? 'bg-white text-red-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'
                                    }`}
                            >
                                Parameter RSO
                            </button>
                        </div>

                        {/* Tab 1: Commercial (Tariff & Contract) */}
                        {activeTab === 'commercial' && (
                            <div className="space-y-3">
                                {officialPrice ? (
                                    <div className="p-3 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl flex items-center justify-between text-xs shadow-2xs">
                                        <div>
                                            <div className="flex items-center gap-1.5 font-bold text-amber-900 flex-wrap">
                                                <DollarSign className="w-3.5 h-3.5 text-amber-700" />
                                                <span>Tarif Resmi Price List: {formatRupiah(officialPrice)}/bln</span>
                                                {officialPriceUpdatedAt && (
                                                    <span className="text-[10px] font-normal text-amber-700 bg-amber-100/70 px-1.5 py-0.2 rounded border border-amber-200">
                                                        Diupdate: {officialPriceUpdatedAt}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-[11px] mt-0.5">
                                                {diffPct === 0 && <span className="font-semibold text-emerald-700">✓ Sesuai dengan tarif resmi katalog</span>}
                                                {diffPct !== null && diffPct < 0 && (
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <span className="font-semibold text-rose-700">
                                                            Diskon {Math.abs(diffPct).toFixed(1)}% dari tarif normal
                                                        </span>
                                                        <span className="text-[10px] text-amber-900 bg-amber-100/90 px-1.5 py-0.5 rounded border border-amber-300 font-medium">
                                                            Sisa Margin: {parseFloat((30 - Math.abs(diffPct)).toFixed(1))}% (Target: 30%)
                                                        </span>
                                                    </div>
                                                )}
                                                {diffPct !== null && diffPct > 0 && (
                                                    <span className="font-semibold text-blue-700">
                                                        Markup +{diffPct.toFixed(1)}% di atas tarif normal
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        {params.mrc !== officialPrice && (
                                            <button
                                                type="button"
                                                onClick={() => updateParam('mrc', officialPrice)}
                                                className="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-600 hover:bg-amber-700 text-white shadow-2xs transition cursor-pointer active:scale-98"
                                            >
                                                Terapkan
                                            </button>
                                        )}
                                    </div>
                                ) : null}
                                <ParameterSlider
                                    label="Tarif Bulanan (MRC)"
                                    type="currency"
                                    value={params.mrc}
                                    min={1000000}
                                    max={200000000}
                                    step={500000}
                                    onChange={(v) => updateParam('mrc', v)}
                                    description="Monthly Recurring Charge yang ditagihkan ke pelanggan per bulan."
                                />

                                <ParameterSlider
                                    label="Biaya Pasang (OTC)"
                                    type="currency"
                                    value={params.otc}
                                    min={0}
                                    max={100000000}
                                    step={500000}
                                    onChange={(v) => updateParam('otc', v)}
                                    description="One Time Charge aktivasi & instalasi awal."
                                />

                                <ParameterSlider
                                    label="Durasi Kontrak (Bulan)"
                                    type="months"
                                    value={params.durasi_kontrak}
                                    min={12}
                                    max={120}
                                    step={6}
                                    onChange={(v) => updateParam('durasi_kontrak', v)}
                                    description="Masa kontrak berlangganan pelanggan."
                                />

                                <ParameterSlider
                                    label="Bulan Aktif Tahun 1"
                                    type="number"
                                    unit="Bulan"
                                    value={params.bulan_tahun1}
                                    min={1}
                                    max={12}
                                    step={1}
                                    onChange={(v) => updateParam('bulan_tahun1', v)}
                                    description="Estimasi bulan layanan aktif di tahun pertama instalasi."
                                />
                            </div>
                        )}

                        {/* Tab 2: CAPEX (Material & Jasa) */}
                        {activeTab === 'capex' && (
                            <div className="space-y-3">
                                <ParameterSlider
                                    label="Biaya Material (Capex)"
                                    type="currency"
                                    value={params.material}
                                    min={0}
                                    max={500000000}
                                    step={1000000}
                                    onChange={(v) => updateParam('material', v)}
                                    description="Anggaran perangkat keras, kabel fiber optic, OLT, ONT, switch."
                                />

                                <ParameterSlider
                                    label="Biaya Jasa & Penarikan"
                                    type="currency"
                                    value={params.jasa}
                                    min={0}
                                    max={300000000}
                                    step={1000000}
                                    onChange={(v) => updateParam('jasa', v)}
                                    description="Biaya instalasi fisik, penarikan kabel, konfigurasi teknisi."
                                />

                                <ParameterSlider
                                    label="BOP / Overhead (%)"
                                    type="percent"
                                    value={params.bop_pct}
                                    min={0}
                                    max={100}
                                    step={1}
                                    onChange={(v) => updateParam('bop_pct', v)}
                                    description="Biaya Operasional Proyek (BOP) standar RSO Telkom."
                                />

                                <div className="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                                    <div className="flex justify-between font-semibold text-slate-700">
                                        <span>Total CAPEX Dasar:</span>
                                        <span className="font-mono">{formatRupiah(params.material + params.jasa)}</span>
                                    </div>
                                    <div className="flex justify-between text-slate-500 mt-1">
                                        <span>BOP ({params.bop_pct}%):</span>
                                        <span className="font-mono">{formatRupiah((params.material + params.jasa) * (params.bop_pct / 100))}</span>
                                    </div>
                                    <div className="flex justify-between font-black text-red-600 border-t border-slate-200 mt-1.5 pt-1.5 text-sm">
                                        <span>Grand CAPEX:</span>
                                        <span className="font-mono">{formatRupiah((params.material + params.jasa) * (1 + params.bop_pct / 100))}</span>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Tab 3: Macro & RSO Parameters */}
                        {activeTab === 'macro' && (
                            <div className="space-y-3">
                                <ParameterSlider
                                    label="WACC / Discount Rate"
                                    type="percent"
                                    value={params.wacc_pct}
                                    min={5}
                                    max={25}
                                    step={0.1}
                                    onChange={(v) => updateParam('wacc_pct', v)}
                                    badge="Standar 10.9%"
                                    description="Weighted Average Cost of Capital acuan kelayakan."
                                />

                                <ParameterSlider
                                    label="O&M (Operasi & Maintenance)"
                                    type="percent"
                                    value={params.om_pct}
                                    min={0}
                                    max={30}
                                    step={0.5}
                                    onChange={(v) => updateParam('om_pct', v)}
                                    badge="Standar 12%"
                                    description="Persentase biaya pemeliharaan tahunan dari CAPEX."
                                />

                                <ParameterSlider
                                    label="Pajak Penghasilan (PPh)"
                                    type="percent"
                                    value={params.tax_pct}
                                    min={0}
                                    max={35}
                                    step={0.5}
                                    onChange={(v) => updateParam('tax_pct', v)}
                                    badge="Standar 22%"
                                    description="Tarif pajak korporasi."
                                />

                                <ParameterSlider
                                    label="Lifetime Baterai / Aset"
                                    type="number"
                                    unit="Tahun"
                                    value={params.lifetime}
                                    min={1}
                                    max={10}
                                    step={1}
                                    onChange={(v) => updateParam('lifetime', v)}
                                    badge="Standar 4 Thn"
                                    description="Masa manfaat depresiasi aset perangkat."
                                />

                                <div className="grid grid-cols-2 gap-2">
                                    <ParameterSlider
                                        label="RDM OTC %"
                                        type="percent"
                                        value={params.rdm_otc_pct}
                                        min={0}
                                        max={100}
                                        step={1}
                                        onChange={(v) => updateParam('rdm_otc_pct', v)}
                                    />
                                    <ParameterSlider
                                        label="RDM MRC %"
                                        type="percent"
                                        value={params.rdm_mrc_pct}
                                        min={0}
                                        max={100}
                                        step={1}
                                        onChange={(v) => updateParam('rdm_mrc_pct', v)}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Right Column: Live KPIs, Charts & Goal Seek (7 Cols) */}
                <div className="lg:col-span-7 space-y-5">
                    {/* Live KPIs */}
                    <KpiCards
                        summary={summary}
                        waccPct={params.wacc_pct}
                        isCalculating={isCalculating}
                    />

                    {/* Smart Goal Seek Recommendation (Shown only if not feasible) */}
                    {summary && !summary.layak && summary.goal_seek && (
                        <GoalSeekAdvisor
                            goalSeek={summary.goal_seek}
                            currentParams={params}
                            onApplyRecommendation={handleApplyRecommendation}
                        />
                    )}

                    {/* Interactive Chart */}
                    <CashflowChart summary={summary} />

                    {/* Financial Statement Table */}
                    <FinancialTable summary={summary} />
                </div>
            </div>
        </div>
    );
}
