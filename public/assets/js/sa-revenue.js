/**
 * Hamro ERP — Super Admin Revenue Module
 */
(function(SuperAdmin) {
    "use strict";

    SuperAdmin.renderRevenue = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

        try {
            const result = await SuperAdmin.fetchAPI('RevenueApi.php?action=summary');
            const data = result.data || {};
            
            mainContent.innerHTML = `
                <div class="pg">
                    <div class="pg-head">
                        <div class="pg-left">
                            <div class="pg-ico"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            <div>
                                <h1 class="pg-title">Revenue Analytics</h1>
                                <p class="pg-sub">Historical growth and financial projections</p>
                            </div>
                        </div>
                    </div>

                    <div class="sg">
                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Monthly Recurring (MRR)</span>
                            <div class="sc-val" style="margin:12px 0;">${data.mrr_formatted || 'रू 0'}</div>
                            <span class="tag bg-g">+${data.mrr_growth || 0}% this month</span>
                        </div>
                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Annual Recurring (ARR)</span>
                            <div class="sc-val" style="margin:12px 0;">${data.arr_formatted || 'रू 0'}</div>
                            <span class="tag bg-t">Projected for FY 2026</span>
                        </div>
                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Avg Revenue Per User (ARPU)</span>
                            <div class="sc-val" style="margin:12px 0;">${data.arpu_formatted || 'रू 0'}</div>
                            <p class="sc-delta">Across all institutes</p>
                        </div>
                    </div>

                    <div class="card" style="margin-top:24px; min-height:400px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                            <h3>Growth Trend</h3>
                            <div class="pg-acts">
                                <button class="btn bs" onclick="SuperAdmin.exportRevenueReport()">Export CSV</button>
                            </div>
                        </div>
                        <div style="height:300px; position:relative;">
                            <canvas id="revenueGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
            `;
            initRevenueCharts(data.history || []);
        } catch (err) {
            console.error("[SuperAdmin] Revenue Error:", err);
            mainContent.innerHTML = `<div class="pg fu"><p style="color:red;">Error loading revenue analytics.</p></div>`;
        }
    };

    function initRevenueCharts(history = []) {
        const canvas = document.getElementById('revenueGrowthChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        SuperAdmin.charts['revenueGrowthChart'] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: history.map(h => h.month),
                datasets: [{
                    label: 'Collection',
                    data: history.map(h => h.total),
                    borderColor: '#009E7E',
                    backgroundColor: 'rgba(0, 158, 126, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => 'रू ' + v } }
                }
            }
        });
    }

    SuperAdmin.exportRevenueReport = () => SuperAdmin.showNotification("Exporting... your file is being generated", "success");

})(window.SuperAdmin);
