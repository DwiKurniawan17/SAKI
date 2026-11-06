import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import SimulationStudio from './SimulationStudio';
import CashflowChart from './components/CashflowChart';

export function SimulationVisualizer({ initialSummary }) {
    const [summary, setSummary] = useState(initialSummary);

    useEffect(() => {
        const handleRecalculated = (e) => {
            if (e.detail) {
                setSummary(e.detail);
            }
        };
        window.addEventListener('simulation-recalculated', handleRecalculated);
        return () => window.removeEventListener('simulation-recalculated', handleRecalculated);
    }, []);

    if (!summary || !summary.rows || summary.rows.length === 0) {
        return null;
    }

    return (
        <div className="mt-2">
            <CashflowChart summary={summary} />
        </div>
    );
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mount Cashflow Chart Visualizer (Embedded directly in show.blade.php)
    const chartEl = document.getElementById('react-cashflow-chart');
    if (chartEl) {
        let initialSummary = null;
        try {
            if (chartEl.dataset.summary) {
                initialSummary = JSON.parse(chartEl.dataset.summary);
            }
        } catch (e) {
            console.error('Failed to parse chart summary JSON:', e);
        }

        const chartRoot = createRoot(chartEl);
        chartRoot.render(<SimulationVisualizer initialSummary={initialSummary} />);
    }

    // 2. Mount Simulation Studio (if container is present)
    const rootEl = document.getElementById('react-simulation-studio');
    if (!rootEl) return;

    let initialData = null;
    let initialSummary = null;
    let defaultRdm = null;
    let catalog = {};
    let wilayahList = [];
    let userRole = 'admin';
    let simulasiId = null;

    try {
        if (rootEl.dataset.initialData) {
            initialData = JSON.parse(rootEl.dataset.initialData);
        }
    } catch (e) {
        console.error('Failed to parse initialData JSON:', e);
    }

    try {
        if (rootEl.dataset.initialSummary) {
            initialSummary = JSON.parse(rootEl.dataset.initialSummary);
        }
    } catch (e) {
        console.error('Failed to parse initialSummary JSON:', e);
    }

    try {
        if (rootEl.dataset.defaultRdm) {
            defaultRdm = JSON.parse(rootEl.dataset.defaultRdm);
        }
    } catch (e) {
        console.error('Failed to parse defaultRdm JSON:', e);
    }

    try {
        if (rootEl.dataset.catalog) {
            catalog = JSON.parse(rootEl.dataset.catalog);
        }
    } catch (e) {
        console.error('Failed to parse catalog JSON:', e);
    }

    try {
        if (rootEl.dataset.wilayahList) {
            wilayahList = JSON.parse(rootEl.dataset.wilayahList);
        }
    } catch (e) {
        console.error('Failed to parse wilayahList JSON:', e);
    }

    if (rootEl.dataset.userRole) {
        userRole = rootEl.dataset.userRole;
    }

    if (rootEl.dataset.simulasiId) {
        simulasiId = rootEl.dataset.simulasiId;
    }

    const root = createRoot(rootEl);
    root.render(
        <SimulationStudio
            initialData={initialData}
            initialSummary={initialSummary}
            defaultRdm={defaultRdm}
            catalog={catalog}
            wilayahList={wilayahList}
            userRole={userRole}
            simulasiId={simulasiId}
        />
    );
});
