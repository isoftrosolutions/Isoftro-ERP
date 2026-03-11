/**
 * Hamro ERP — Super Admin Analytics Module
 */
(function(SuperAdmin) {
    "use strict";

    SuperAdmin.renderAnalytics = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

        try {
            // Placeholder API call
            // const result = await SuperAdmin.fetchAPI('AnalyticsApi.php?action=overview');
            
            mainContent.innerHTML = `
                <div class="pg">
                    <div class="pg-head">
                        <div class="pg-left">
                            <div class="pg-ico"><i class="fa-solid fa-chart-pie"></i></div>
                            <div>
                                <h1 class="pg-title">Platform Analytics</h1>
                                <p class="pg-sub">User behavior, feature adoption, and system performance</p>
                            </div>
                        </div>
                    </div>

                    <div class="sg">
                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Monthly Active Users</span>
                            <div class="sc-val" style="margin:12px 0;">45.2K</div>
                            <span class="tag bg-g">+18% vs last month</span>
                        </div>
                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Avg Session Time</span>
                            <div class="sc-val" style="margin:12px 0;">12m 45s</div>
                            <span class="tag bg-t">Stable adoption</span>
                        </div>
                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Top used module</span>
                            <div class="sc-val" style="margin:12px 0; font-size:24px;">Exams & Result</div>
                            <p class="sc-delta">78% of tenants active</p>
                        </div>
                    </div>

                    <div class="g65" style="margin-top:24px;">
                        <div class="sc fu">
                            <h3>Feature Adoption Heatmap</h3>
                            <div style="height:300px; position:relative;">
                                <canvas id="adoptionChart"></canvas>
                            </div>
                        </div>
                        <div class="sc fu">
                            <h3>User Segment Distribution</h3>
                            <div style="height:300px; position:relative;">
                                <canvas id="segmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            initAnalyticsCharts();
        } catch (err) {
            console.error("[SuperAdmin] Analytics Error:", err);
            mainContent.innerHTML = `<div class="pg fu"><p style="color:red;">Error loading analytics.</p></div>`;
        }
    };

    function initAnalyticsCharts() {
        if (typeof Chart === 'undefined') return;

        const ctx1 = document.getElementById('adoptionChart')?.getContext('2d');
        if (ctx1) {
            SuperAdmin.charts['adoptionChart'] = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Adoption Rate %',
                        data: [65, 59, 80, 81, 56, 95],
                        backgroundColor: 'rgba(0, 158, 126, 0.7)',
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        const ctx2 = document.getElementById('segmentChart')?.getContext('2d');
        if (ctx2) {
            SuperAdmin.charts['segmentChart'] = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Silver', 'Gold', 'Platinum', 'Enterprise'],
                    datasets: [{
                        data: [300, 50, 100, 40],
                        backgroundColor: ['#e2e8f0', '#fbbf24', '#3b82f6', '#009E7E']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }

})(window.SuperAdmin);
