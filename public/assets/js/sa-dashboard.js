/**
 * iSoftro ERP — Super Admin Dashboard Module
 */
(function (SuperAdmin) {
  "use strict";

  SuperAdmin.renderDashboard = async function () {
    const mainContent = document.getElementById("mainContent");
    if (!mainContent) return;

    try {
      const stats = await fetchSuperAdminStats();

      mainContent.innerHTML = `
                <div class="pg">
                    <div class="pg-head">
                        <div class="pg-left">
                            <div class="pg-ico"><i class="fa-solid fa-house"></i></div>
                            <div>
                                <h1 class="pg-title">Platform Overview</h1>
                                <p class="pg-sub">HAMRO LABS INTERNAL ACCESS | PLATFORM OWNER</p>
                            </div>
                        </div>
                        <div class="pg-acts">
                            <button class="btn bs d-none-mob"><i class="fa-solid fa-download"></i> Export Data</button>
                            <button class="btn bt" onclick="SuperAdmin.renderDashboard()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
                        </div>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <div class="sb-lbl" style="padding-left:0; margin-bottom:8px;">Quick Actions</div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
                            <a href="#" onclick="SuperAdmin.goNav('tenants', 'add')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                                <div style="width:36px; height:36px; border-radius:8px; background:var(--sa-primary-lt); color:var(--sa-primary); display:flex; align-items:center; justify-content:center; font-size:18px;">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                <span style="font-weight:600; font-size:14px;">Add New Institute</span>
                            </a>
                            <a href="#" onclick="SuperAdmin.goNav('plans', 'assign')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                                <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                    <i class="fa-solid fa-id-card"></i>
                                </div>
                                <span style="font-weight:600; font-size:14px;">Assign Plan</span>
                            </a>
                            <a href="#" onclick="SuperAdmin.goNav('system', 'announce')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                                <div style="width:36px; height:36px; border-radius:8px; background:#fef9e7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </div>
                                <span style="font-weight:600; font-size:14px;">Send Platform Announcement</span>
                            </a>
                            <a href="#" onclick="SuperAdmin.goNav('system', 'toggles')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                                <div style="width:36px; height:36px; border-radius:8px; background:#f3e8ff; color:#8141A5; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                    <i class="fa-solid fa-toggle-on"></i>
                                </div>
                                <span style="font-weight:600; font-size:14px;">Toggle Feature</span>
                            </a>
                        </div>
                    </div>

                    <div class="sg">
                        <div class="sc fu">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Active Tenants</span>
                                <span class="tag bg-t">+${stats.newTenantsThisMonth || 0} this month</span>
                            </div>
                            <div class="sc-val">${(stats.totalTenants || 0).toLocaleString()}</div>
                            <p class="sc-delta">Institutes currently on platform</p>
                        </div>

                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Subscribed Plans</span>
                            <div style="display:flex; align-items:center; gap:12px; margin-top:12px;">
                                <div style="flex:1;">
                                    <div class="sc-val" style="font-size:20px;">${Object.values(stats.planStats || {}).reduce((a, b) => a + b, 0)}</div>
                                    <div style="display:flex; gap:4px; margin-top:8px;">
                                        <div title="Starter" style="height:6px; flex:${stats.planStats?.starter || 1}; background:#e2e8f0; border-radius:3px;"></div>
                                        <div title="Growth" style="height:6px; flex:${stats.planStats?.growth || 1}; background:#3b82f6; border-radius:3px;"></div>
                                        <div title="Professional" style="height:6px; flex:${stats.planStats?.professional || 1}; background:var(--sa-primary); border-radius:3px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sc fu">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                                <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">SMS Consumption</span>
                                <span class="tag bg-r">${stats.sms?.consumedPercent || 0}% of quota</span>
                            </div>
                            <div class="sc-val">${Math.round((stats.sms?.usedCredits || 0) / 1000)}K</div>
                            <div style="height:6px; width:100%; background:#f1f5f9; border-radius:3px; margin-top:12px; overflow:hidden;">
                                <div style="height:100%; width:${stats.sms?.consumedPercent || 0}%; background:var(--red); border-radius:3px;"></div>
                            </div>
                        </div>

                        <div class="sc fu">
                            <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">System Health</span>
                            <div style="margin-top:12px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="font-size:11px; font-weight:600;">Uptime</span>
                                    <span style="font-size:11px; font-weight:700; color:var(--success);">${stats.health?.uptime || "99.9%"}</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span style="font-size:11px; font-weight:600;">API Latency</span>
                                    <span style="font-size:11px; font-weight:700;">${stats.health?.latency || "45ms"}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="g65">
                        <div class="sc fu" style="min-height:300px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
                                <div>
                                    <h3 style="font-size:16px; font-weight:800; color:var(--td);">Monthly Recurring Revenue (MRR)</h3>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:22px; font-weight:800; color:var(--td);">${stats.mrrFormatted || "रू 0"}</div>
                                    <div style="font-size:11px; color:var(--success); font-weight:700;"><i class="fa-solid fa-arrow-trend-up"></i> ${stats.yoyGrowth || 0}% YoY</div>
                                </div>
                            </div>
                            <div style="height:200px; position:relative;">
                                <canvas id="mrrChart"></canvas>
                            </div>
                        </div>

                        <div class="sc fu">
                            <h3 style="font-size:16px; font-weight:800; color:var(--td); margin-bottom:16px;">Support Tickets</h3>
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <div style="display:flex; align-items:center; gap:12px; padding:12px; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px;">
                                    <div style="width:8px; height:8px; border-radius:50%; background:var(--red);"></div>
                                    <div style="flex:1;">
                                        <div style="font-size:13px; font-weight:700; color:#9f1239;">Critical Priority</div>
                                        <div style="font-size:10px; color:#be123c;">${stats.tickets?.critical || 0} Tickets awaiting action</div>
                                    </div>
                                    <div style="font-size:18px; font-weight:800; color:#9f1239;">${stats.tickets?.critical || 0}</div>
                                </div>
                            </div>
                            <button class="btn bt" onclick="SuperAdmin.goNav('support')" style="width:100%; margin-top:20px; justify-content:center;">Manage Tickets</button>
                        </div>
                    </div>

                    <div class="g65">
                        <div class="sc fu">
                            <h3 style="font-size:16px; font-weight:800; color:var(--td); margin-bottom:20px;">Recent Signups</h3>
                            <div style="overflow-x:auto;">
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead>
                                        <tr style="text-align:left; border-bottom:1px solid var(--cb);">
                                            <th style="padding:12px 0; font-size:10px; color:var(--tl);">Institute</th>
                                            <th style="padding:12px 0; font-size:10px; color:var(--tl);">Plan</th>
                                            <th style="padding:12px 0; font-size:10px; color:var(--tl);">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${
                                          (stats.recentSignups || [])
                                            .map(
                                              (s) => `
                                            <tr>
                                                <td style="padding:14px 0;">
                                                    <div style="font-size:13px; font-weight:700; color:var(--td);">${s.name}</div>
                                                    <div style="font-size:10px; color:var(--tl);">${s.subdomain}.hamroerp.com</div>
                                                </td>
                                                <td style="padding:14px 0;"><span class="tag bg-p">${s.plan}</span></td>
                                                <td style="padding:14px 0;"><span class="tag bg-g">${s.status}</span></td>
                                            </tr>
                                        `,
                                            )
                                            .join("") ||
                                          '<tr><td colspan="3" style="text-align:center;padding:20px;">No recent signups</td></tr>'
                                        }
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="sc fu" style="background:#0F172A; border-color:#1e293b;">
                            <h3 style="font-size:16px; font-weight:800; color:#fff; margin-bottom:20px;">Security Center</h3>
                            <div style="display:flex; flex-direction:column; gap:16px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:12px; border-bottom:1px solid #1e293b;">
                                    <span style="font-size:12px; color:rgba(255,255,255,0.6); font-weight:600;">Failed Logins (24h)</span>
                                    <span style="color:#f43f5e; font-weight:800;">${stats.failedLogins || 0}</span>
                                </div>
                                <button onclick="SuperAdmin.goNav('logs', 'audit')" style="width:100%; padding:12px; border-radius:10px; border:1px solid #1e293b; color:#fff; background:rgba(255,255,255,0.03);">Review Audit Logs</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
      initDashboardCharts(stats.mrrTrend);
    } catch (err) {
      console.error("[SuperAdmin] Dashboard Error:", err);
      mainContent.innerHTML = `<div class="pg fu"><div class="card">Error loading dashboard: ${err.message}</div></div>`;
    }
  };

  async function fetchSuperAdminStats() {
    return SuperAdmin.fetchAPI("/api/super_admin_stats.php");
  }

  function initDashboardCharts(trendData = []) {
    // Wait for Chart.js to be available
    const waitForChart = (retries = 10) => {
      if (typeof Chart !== "undefined") {
        createChart(trendData);
        return;
      }
      if (retries > 0) {
        setTimeout(() => waitForChart(retries - 1), 100);
      } else {
        console.warn(
          "[SuperAdmin] Chart.js not loaded, skipping chart initialization",
        );
      }
    };
    waitForChart();
  }

  function createChart(trendData = []) {
    const canvas = document.getElementById("mrrChart");
    if (!canvas) return;
    const ctx = canvas.getContext("2d");

    const labels = trendData.map((d) => d.month);
    const data = trendData.map((d) => d.mrrK);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, "rgba(0, 158, 126, 0.3)");
    gradient.addColorStop(1, "rgba(0, 158, 126, 0.0)");

    SuperAdmin.charts["mrrChart"] = new Chart(ctx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Revenue (In K)",
            data: data,
            borderColor: "#009E7E",
            borderWidth: 3,
            fill: true,
            backgroundColor: gradient,
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { callback: (v) => "रू " + v + "K" } },
        },
      },
    });
  }
})(window.SuperAdmin);
