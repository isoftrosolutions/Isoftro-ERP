/**
 * Hamro ERP — Super Admin JavaScript Module
 * Refactored to use unified class names matching institute-admin.
 *
 * Sidebar classes : .sb, body.sb-active, body.sb-collapsed
 * Header classes  : .hdr, .sb-toggle, .hbtn
 * Nav classes     : .nb-btn, .nbc, .sub-menu, .sub-btn
 */

window.SuperAdmin = window.SuperAdmin || (function () {
  "use strict";

  let charts     = {};
  let dataTables = {};

  /* ============================================================
     STATE & CONFIG
     ============================================================ */

  const getInitialPage = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    if (pageParam) return pageParam;
    
    const pathParts = window.location.pathname.split('/');
    let lastPart = pathParts[pathParts.length - 1];
    
    // If empty last part (trailing slash), look at the previous one
    if (!lastPart && pathParts.length > 1) {
      lastPart = pathParts[pathParts.length - 2];
    }
    
    // If we're at the root of super-admin or on index.php, default to overview
    if (!lastPart || lastPart === 'index' || lastPart === 'super-admin' || lastPart === 'index.php' || lastPart === 'erp') {
      return 'overview';
    }
    
    // Otherwise, use the filename as the page name (strip .php)
    return lastPart.replace('.php', '');
  };

  let initialPage = getInitialPage();
  let activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
  let activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;

  /* ============================================================
     INIT
     ============================================================ */

  function init() {
    // Re-check initial page in case it changed since script load (rare)
    initialPage = getInitialPage();
    activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
    activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;

    initSidebar();
    initDropdowns();
    initCharts();
    initModals();
    renderPage();
    
    // Auto-refresh dashboard data every 2 minutes if on overview
    setInterval(() => {
        if (activeNav === 'overview' || activeNav === 'index') {
            console.log("[SuperAdmin] Periodic refresh...");
            renderDashboard();
        }
    }, 120000);

    console.log("[SuperAdmin] Module initialised");
  }

  /* ============================================================
     PAGE RENDERING
     ============================================================ */

  function renderPage() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    // If not SPA mode and not the dashboard, keep the server-rendered content
    // This prevents hijacking specialized specialized PHP pages like add-tenant.php
    const currentParams = new URLSearchParams(window.location.search);
    if (!currentParams.has('page') && activeNav !== 'overview' && activeNav !== 'index') {
        console.log("[SuperAdmin] Server-rendered page detected, skipping JS overwrite:", activeNav);
        // Still need to init components that might be in the PHP page
        initCharts();
        return;
    }

    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

    // Route to appropriate page
    switch(activeNav) {
      case 'overview':
        renderDashboard();
        break;
      case 'tenants':
        renderTenants();
        break;
      case 'plans':
        if (activeSub === 'sub-plans') renderPlans();
        else if (activeSub === 'flags') renderFlags();
        else if (activeSub === 'assign') renderPlanAssign();
        else renderPlans();
        break;
      case 'revenue':
        if (activeSub === 'mrr') renderRevenue();
        else if (activeSub === 'payments') renderPayments();
        else if (activeSub === 'invoices') renderInvoices();
        else renderRevenue();
        break;
      case 'analytics':
        if (activeSub === 'users') renderUsers();
        else if (activeSub === 'heatmap') renderHeatmap();
        else if (activeSub === 'sms') renderSmsCredits();
        else renderUsers();
        break;
      case 'support':
        renderSupport();
        break;
      case 'system':
        if (activeSub === 'toggles') renderFlags();
        else if (activeSub === 'maintenance') renderMaintenance();
        else if (activeSub === 'announce') renderAnnouncements();
        else if (activeSub === 'email-cfg') renderEmailConfig();
        else fetchAndRender('pages/super_admin/flags.php');
        break;
      case 'logs':
        if (activeSub === 'audit' || activeSub === 'errors' || activeSub === 'api') renderLogs();
        else if (activeSub === 'db') renderDbInsights();
        else renderLogs();
        break;
      case 'settings':
        if (activeSub === 'branding') renderBranding();
        else if (activeSub === 'sms-tpl') renderSmsTemplates();
        else if (activeSub === 'email-cfg') renderEmailConfig();
        else renderGenericPage();
        break;
      case 'profile':
        if (activeSub === 'view') renderProfile();
        else if (activeSub === 'password') renderChangePassword();
        else if (activeSub === 'activity') renderActivityLog();
        else renderProfile();
        break;
      default:
        fetchGenericPage(activeNav);
    }
  }

  // API Base URL
  const API_BASE = window.APP_URL ? window.APP_URL + '/api/superadmin/' : '/api/superadmin/';
  
  // Generic fetch wrapper
  async function fetchAPI(endpoint, options = {}) {
    try {
      const url = API_BASE + endpoint;
      const response = await fetch(url, {
        ...options,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          ...options.headers
        },
        credentials: 'same-origin'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'API error');
      }
      
      return result;
    } catch (err) {
      console.error('[SuperAdmin API Error]:', err);
      throw err;
    }
  }

  // Helper function to fetch and render a PHP page (legacy - kept for fallback)
  function fetchAndRender(pagePath) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    fetch(pagePath)
      .then(response => response.text())
      .then(html => {
        mainContent.innerHTML = html;
      })
      .catch(err => {
        console.error('Error loading page:', err);
        mainContent.innerHTML = '<div class="pg fu"><p>Error loading page</p></div>';
      });
  }

  // Helper function to show generic page
  function fetchGenericPage(title) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = `
      <div class="pg fu">
        <div style="text-align:center;padding:60px 20px;">
          <i class="fa-solid fa-tools" style="font-size:4rem;color:var(--tl);margin-bottom:20px;"></i>
          <h2>${title} Module</h2>
          <p style="color:var(--tl);margin-top:10px;">This module is being prepared.</p>
        </div>
      </div>
    `;
  }

  function renderDashboard() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    // Fetch dashboard data via API
    fetchSuperAdminStats().then(stats => {
      mainContent.innerHTML = `
        <div class="pg fu">
            <!-- Page Header -->
            <div class="pg-head">
                <div class="pg-left">
                    <div class="pg-ico"><i class="fa-solid fa-house"></i></div>
                    <div>
                        <div class="pg-title">Platform Overview</div>
                        <div class="pg-sub">HAMRO LABS INTERNAL ACCESS | PLATFORM OWNER</div>
                    </div>
                </div>
                <div class="pg-acts">
                    <button class="btn bs d-none-mob"><i class="fa-solid fa-download"></i> Export Data</button>
                    <button class="btn bt" onclick="window.location.reload()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
                </div>
            </div>

            <!-- ── QUICK ACTIONS ── -->
            <div style="margin-bottom: 24px;">
                <div class="sb-lbl" style="padding-left:0; margin-bottom:8px;">Quick Actions</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
                    <button onclick="SuperAdmin.goNav('tenants', 'add')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit; border:1px solid var(--cb); background:white; border-radius:12px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:var(--sa-primary-lt); color:var(--sa-primary); display:flex; align-items:center; justify-content:center; font-size:18px;">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <span style="font-weight:600; font-size:14px;">Add New Institute</span>
                    </button>
                    <button onclick="SuperAdmin.goNav('plans', 'assign')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit; border:1px solid var(--cb); background:white; border-radius:12px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:18px;">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <span style="font-weight:600; font-size:14px;">Assign Plan</span>
                    </button>
                    <button onclick="SuperAdmin.goNav('system', 'announce')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit; border:1px solid var(--cb); background:white; border-radius:12px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:#fef9e7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px;">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <span style="font-weight:600; font-size:14px;">Platform Announcement</span>
                    </button>
                    <button onclick="SuperAdmin.goNav('system', 'toggles')" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit; border:1px solid var(--cb); background:white; border-radius:12px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:#f3e8ff; color:#8141A5; display:flex; align-items:center; justify-content:center; font-size:18px;">
                            <i class="fa-solid fa-toggle-on"></i>
                        </div>
                        <span style="font-weight:600; font-size:14px;">Toggle Feature</span>
                    </button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="sg">
                <div class="sc fu">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Active Tenants</span>
                        <span class="tag bg-t">+${stats.newTenantsThisMonth || 0} this month</span>
                    </div>
                    <div class="sc-val">${stats.totalTenants || 0}</div>
                    <p class="sc-delta">Institutes currently on platform</p>
                </div>
                <div class="sc fu">
                    <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Subscribed Plans</span>
                    <div style="display:flex; align-items:center; gap:12px; margin-top:12px;">
                        <div style="flex:1;">
                            <div class="sc-val" style="font-size:20px;">${Object.values(stats.planStats || {}).reduce((a, b) => a + b, 0) || 0}</div>
                            <div style="display:flex; gap:4px; margin-top:8px;">
                                <div title="Starter: ${stats.planStats?.starter || 0}" style="height:6px; flex:${stats.planStats?.starter || 1}; background:#e2e8f0; border-radius:3px;"></div>
                                <div title="Growth: ${stats.planStats?.growth || 0}" style="height:6px; flex:${stats.planStats?.growth || 1}; background:#3b82f6; border-radius:3px;"></div>
                                <div title="Professional: ${stats.planStats?.professional || 0}" style="height:6px; flex:${stats.planStats?.professional || 1}; background:var(--sa-primary); border-radius:3px;"></div>
                                <div title="Enterprise: ${stats.planStats?.enterprise || 0}" style="height:6px; flex:${stats.planStats?.enterprise || 1}; background:#1e293b; border-radius:3px;"></div>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:9px; margin-top:8px; font-weight:700;">
                        <span>S: ${stats.planStats?.starter || 0}</span> 
                        <span>G: ${stats.planStats?.growth || 0}</span> 
                        <span>P: ${stats.planStats?.professional || 0}</span> 
                        <span>E: ${stats.planStats?.enterprise || 0}</span>
                    </div>
                </div>
                <div class="sc fu">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">SMS Consumption</span>
                        <span class="tag bg-r">${stats.sms?.consumedPercent || 0}% of quota</span>
                    </div>
                    <div class="sc-val">${(stats.sms?.usedCredits / 1000).toFixed(1)}K</div>
                    <div style="height:6px; width:100%; background:#f1f5f9; border-radius:3px; margin-top:12px; overflow:hidden;">
                        <div style="height:100%; width:${stats.sms?.consumedPercent || 0}%; background:var(--red); border-radius:3px;"></div>
                    </div>
                    <p class="sc-delta">Monthly platform-wide usage</p>
                </div>
                <div class="sc fu">
                    <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">System Health</span>
                    <div style="margin-top:12px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:11px; font-weight:600;">Uptime</span>
                            <span style="font-size:11px; font-weight:700; color:var(--success);">${stats.health?.uptime || '99.9%'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span style="font-size:11px; font-weight:600;">Latency</span>
                            <span style="font-size:11px; font-weight:700;">${stats.health?.latency || '0ms'}</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="font-size:11px; font-weight:600;">Redis Mem</span>
                            <span style="font-size:11px; font-weight:700;">${stats.health?.redis || '0GB'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid Row 2 -->
            <div class="g65">
                <!-- Revenue Analytics -->
                <div class="sc fu" style="min-height:300px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
                        <div>
                            <h3 style="font-size:16px; font-weight:800; color:var(--td);">Monthly Recurring Revenue (MRR)</h3>
                            <p style="font-size:12px; color:var(--tl);">Revenue trends with Year-over-Year comparison</p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:22px; font-weight:800; color:var(--td);">${stats.mrrFormatted || '₹ 0'}</div>
                            <div style="font-size:11px; color:var(--success); font-weight:700;"><i class="fa-solid fa-arrow-trend-up"></i> ${stats.yoyGrowth || 0}% YoY</div>
                        </div>
                    </div>
                    <div style="height:200px; position:relative;">
                        <canvas id="mrrChart"></canvas>
                    </div>
                </div>

                <!-- Support Tickets -->
                <div class="sc fu">
                    <h3 style="font-size:16px; font-weight:800; color:var(--td); margin-bottom:16px;">Support Tickets</h3>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px;">
                            <div style="width:8px; height:8px; border-radius:50%; background:var(--red); animation: pulse 2s infinite;"></div>
                            <div style="flex:1;">
                                <div style="font-size:13px; font-weight:700; color:#9f1239;">Critical Priority</div>
                                <div style="font-size:10px; color:#be123c;">${stats.tickets?.critical || 0} Tickets awaiting action</div>
                            </div>
                            <div style="font-size:18px; font-weight:800; color:#9f1239;">${stats.tickets?.critical || 0}</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--cb); border-radius:12px; background:#fff;">
                            <div style="width:8px; height:8px; border-radius:50%; background:var(--amber);"></div>
                            <div style="flex:1;">
                                <div style="font-size:13px; font-weight:700; color:var(--td);">High Priority</div>
                                <div style="font-size:10px; color:var(--tl);">${stats.tickets?.high || 0} Pending tickets</div>
                            </div>
                            <div style="font-size:18px; font-weight:800; color:var(--td);">${stats.tickets?.high || 0}</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--cb); border-radius:12px; background:#fff;">
                            <div style="width:8px; height:8px; border-radius:50%; background:var(--blue);"></div>
                            <div style="flex:1;">
                                <div style="font-size:13px; font-weight:700; color:var(--td);">Standard</div>
                                <div style="font-size:10px; color:var(--tl);">${stats.tickets?.normal || 0} Open tickets</div>
                            </div>
                            <div style="font-size:18px; font-weight:800; color:var(--td);">${stats.tickets?.normal || 0}</div>
                        </div>
                    </div>
                    <button onclick="SuperAdmin.goNav('support')" class="btn bt" style="width:100%; margin-top:20px; justify-content:center;">Manage Tickets</button>
                </div>
            </div>

            <!-- Content Grid Row 3 -->
            <div class="g65">
                <!-- Recent Signups -->
                <div class="sc fu">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                        <h3 style="font-size:16px; font-weight:800; color:var(--td);">Recent Institute Signups</h3>
                        <button onclick="SuperAdmin.goNav('tenants')" class="btn bs" style="padding:4px 12px; font-size:11px;">View Ledger</button>
                    </div>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="text-align:left; border-bottom:1px solid var(--cb);">
                                    <th style="padding:12px 0; font-size:10px; color:var(--tl);">Institute Name</th>
                                    <th style="padding:12px 0; font-size:10px; color:var(--tl);">Plan Tier</th>
                                    <th style="padding:12px 0; font-size:10px; color:var(--tl);">Joined At</th>
                                    <th style="padding:12px 0; font-size:10px; color:var(--tl);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${(stats.recentSignups || []).map(s => `
                                    <tr>
                                        <td style="padding:14px 0;">
                                            <div style="font-size:13px; font-weight:700; color:var(--td);">${s.name}</div>
                                            <div style="font-size:10px; color:var(--tl);">${s.province || 'Nepal'}</div>
                                        </td>
                                        <td style="padding:14px 0;"><span class="tag bg-p">${s.plan}</span></td>
                                        <td style="padding:14px 0; font-size:12px; font-weight:500;">${new Date(s.created_at).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'})}</td>
                                        <td style="padding:14px 0;"><span class="tag bg-g">${s.status}</span></td>
                                    </tr>
                                `).join('') || '<tr><td colspan="4" style="text-align:center; padding:20px;">No recent signups</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Security Alert Panel -->
                <div class="sc fu" style="background:#0F172A; border-color:#1e293b;">
                    <h3 style="font-size:16px; font-weight:800; color:#fff; margin-bottom:20px;">Security Alert Center</h3>
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:12px; border-bottom:1px solid #1e293b;">
                            <span style="font-size:12px; color:rgba(255,255,255,0.6); font-weight:600;">Failed Logins (Prev 24h)</span>
                            <span style="background:rgba(225,29,72,0.15); color:#f43f5e; padding:2px 10px; border-radius:12px; font-size:11px; font-weight:800; border:1px solid rgba(225,29,72,0.2);">${stats.failedLogins || 0} Incidents</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div style="width:36px; height:36px; border-radius:10px; background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.15); display:flex; align-items:center; justify-content:center; color:#f87171;">
                                <i class="fa-solid fa-shield-virus"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px; color:#fff; font-weight:700;">Intrusion Detection</div>
                                <div style="font-size:10px; color:rgba(255,255,255,0.4);">Monitoring active...</div>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <div style="width:36px; height:36px; border-radius:10px; background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.15); display:flex; align-items:center; justify-content:center; color:#fbbf24;">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px; color:#fff; font-weight:700;">System Watchdog</div>
                                <div style="font-size:10px; color:rgba(255,255,255,0.4);">No critical failures</div>
                            </div>
                        </div>
                        <button onclick="SuperAdmin.goNav('logs', 'audit')" style="margin-top:8px; text-align:center; display:block; padding:12px; border-radius:10px; border:1px solid #1e293b; color:#fff; font-size:12px; text-decoration:none; font-weight:700; background:rgba(255,255,255,0.03); transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.07)'" onmouseout="this.style.background='rgba(255,255,255,0.03)'">Investigate Audit Logs</button>
                    </div>
                </div>
            </div>

            <!-- ── DAILY WORKFLOW ── -->
            <div style="margin-bottom: 24px;">
                <div class="sc fu" style="background:var(--bg-card); border-left:4px solid var(--sa-primary);">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                        <div>
                            <h3 style="font-size:16px; font-weight:800; color:var(--td); margin:0;">Daily Workflow</h3>
                            <p style="font-size:12px; color:var(--tl); margin:4px 0 0;">Platform initialization checklist</p>
                        </div>
                        <span class="tag bg-g" id="workflowStatus">0 / 5 Completed</span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="SuperAdmin.updateWorkflow()">
                            <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--td);">1. Check System Health</div>
                                <div style="font-size:12px; color:var(--tl);">Review health widgets for overnight alerts</div>
                            </div>
                        </label>
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="SuperAdmin.updateWorkflow()">
                            <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--td);">2. Process New Signups</div>
                                <div style="font-size:12px; color:var(--tl);">Review pending institutes and plans</div>
                            </div>
                        </label>
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="SuperAdmin.updateWorkflow()">
                            <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                            <div>
                                <div style="font-size:14px; font-weight:600; color:var(--td);">3. Manage Support Tickets</div>
                                <div style="font-size:12px; color:var(--tl);">Process critical priority tickets first</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
      `;
      // Re-init chart after rendering HTML
      initDashboardCharts(stats.mrrTrend);
    }).catch(err => {
      console.error("[SuperAdmin] Error loading dashboard:", err);
      mainContent.innerHTML = `<div class="pg fu">Error loading dashboard</div>`;
    });
  }

  function initDashboardCharts(trendData = []) {
    const canvas = document.getElementById('mrrChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Fallback if no data
    if (!trendData || trendData.length === 0) {
        trendData = [
            {month: 'Jan', mrr: 1000},
            {month: 'Feb', mrr: 1200},
            {month: 'Mar', mrr: 1500}
        ];
    }

    const labels = trendData.map(d => d.month);
    const data   = trendData.map(d => d.mrrK); // Use Kilo for better granularity on small datasets

    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(0, 158, 126, 0.3)');
    gradient.addColorStop(1, 'rgba(0, 158, 126, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'MRR (In Millions रू)',
                    data: data,
                    borderColor: '#009E7E',
                    borderWidth: 3,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        font: { size: 10 }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(value) { return 'रू ' + value + 'K'; },
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
  }

  function renderRecentActivity(activity) {
    if (!activity || activity.length === 0) {
      return '<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--tl);">No recent activity found in audit logs.</td></tr>';
    }
    
    return activity.map(act => `
      <tr>
        <td style="font-weight:600;">${act.level || act.action || 'N/A'}</td>
        <td style="font-size:12px;">${act.user_id ? 'User #' + act.user_id : 'System'}</td>
        <td>${act.ip_address || '-'}</td>
        <td style="font-size:12px; color:var(--tl);">${act.time ? new Date(act.time).toLocaleString('en-US', {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : '-'}</td>
        <td><span class="tag bg-t">Logged</span></td>
      </tr>
    `).join('');
  }

  function renderGenericPage() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = `
      <div class="pg fu">
        <div class="pg-head">
          <div class="pg-left">
            <div class="pg-ico ic-t"><i class="fa-solid fa-folder"></i></div>
            <div>
              <div class="pg-title">${activeSub ? activeNav + ' - ' + activeSub : activeNav.charAt(0).toUpperCase() + activeNav.slice(1)}</div>
              <div class="pg-sub">Module under development</div>
            </div>
          </div>
        </div>
        <div class="card" style="text-align:center; padding:100px 40px;">
          <i class="fa-solid fa-cubes-stacked" style="font-size:3rem; color:var(--tl); margin-bottom:20px;"></i>
          <h2>${activeSub ? activeNav + ' ' + activeSub : activeNav.toUpperCase()} Module</h2>
          <p style="color:var(--tb); margin-top:10px;">This specific view is being developed.</p>
        </div>
      </div>
    `;
  }

  /* ============================================================
     TENANTS PAGE RENDERER
     ============================================================ */
  async function renderTenants() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';
    
    try {
      const result = await fetchAPI('TenantsApi.php?action=list&limit=50');
      const tenants = result.data || [];
      
      mainContent.innerHTML = `
        <div class="pg fu">
          <div class="pg-head">
            <div class="pg-left">
              <div class="pg-ico"><i class="fa-solid fa-building"></i></div>
              <div>
                <div class="pg-title">Tenant Management</div>
                <div class="pg-sub">Manage all institutes on the platform</div>
              </div>
            </div>
            <div class="pg-acts">
              <button class="btn bt" onclick="SuperAdmin.goNav('tenants', 'add')"><i class="fa-solid fa-plus"></i> Add Institute</button>
            </div>
          </div>
          
          <div class="card">
            <div style="display:flex;gap:12px;margin-bottom:20px;">
              <input type="text" id="tenantSearch" placeholder="Search institutes..." style="flex:1;padding:10px 15px;border:1px solid var(--cb);border-radius:8px;" onkeyup="SuperAdmin.filterTenants()">
              <select id="tenantStatusFilter" style="padding:10px 15px;border:1px solid var(--cb);border-radius:8px;" onchange="SuperAdmin.filterTenants()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="trial">Trial</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            
            <div style="overflow-x:auto;">
              <table style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr style="border-bottom:2px solid var(--cb);">
                    <th style="text-align:left;padding:12px;font-size:11px;color:var(--tl);">Institute</th>
                    <th style="text-align:left;padding:12px;font-size:11px;color:var(--tl);">Plan</th>
                    <th style="text-align:left;padding:12px;font-size:11px;color:var(--tl);">Status</th>
                    <th style="text-align:left;padding:12px;font-size:11px;color:var(--tl);">Users</th>
                    <th style="text-align:left;padding:12px;font-size:11px;color:var(--tl);">Joined</th>
                    <th style="text-align:right;padding:12px;font-size:11px;color:var(--tl);">Actions</th>
                  </tr>
                </thead>
                <tbody id="tenantsTableBody">
                  ${tenants.length === 0 ? '<tr><td colspan="6" style="text-align:center;padding:30px;">No tenants found</td></tr>' : 
                    tenants.map(t => `
                      <tr style="border-bottom:1px solid var(--cb);">
                        <td style="padding:14px 12px;">
                          <div style="font-weight:600;color:var(--td);">${t.name}</div>
                          <div style="font-size:11px;color:var(--tl);">${t.subdomain}.hamroerp.com</div>
                        </td>
                        <td style="padding:14px 12px;"><span class="tag bg-p">${(t.plan || 'starter').charAt(0).toUpperCase() + (t.plan || 'starter').slice(1)}</span></td>
                        <td style="padding:14px 12px;"><span class="tag ${t.status === 'active' ? 'bg-g' : t.status === 'trial' ? 'bg-t' : 'bg-r'}">${t.status}</span></td>
                        <td style="padding:14px 12px;font-size:12px;">${t.user_count || 0}</td>
                        <td style="padding:14px 12px;font-size:12px;">${new Date(t.created_at).toLocaleDateString()}</td>
                        <td style="padding:14px 12px;text-align:right;">
                          <button class="btn-icon" title="View" onclick="SuperAdmin.viewTenant(${t.id})"><i class="fa-solid fa-eye"></i></button>
                          <button class="btn-icon" title="Edit" onclick="SuperAdmin.editTenant(${t.id})"><i class="fa-solid fa-pen"></i></button>
                          <button class="btn-icon" title="Impersonate" onclick="SuperAdmin.impersonateTenant(${t.id})"><i class="fa-solid fa-user-secret"></i></button>
                        </td>
                      </tr>
                    `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      `;
    } catch (err) {
      console.error('[SuperAdmin] Error loading tenants:', err);
      mainContent.innerHTML = `<div class="pg fu"><div class="card"><p style="color:red;">Error loading tenants: ${err.message}</p></div></div>`;
    }
  }

  /* ============================================================
     PLANS PAGE RENDERER  
     ============================================================ */
  async function renderPlans() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';
    
    try {
      const result = await fetchAPI('PlansApi.php?action=list');
      const plans = result.data || [];
      
      mainContent.innerHTML = `
        <div class="pg fu">
          <div class="pg-head">
            <div class="pg-left">
              <div class="pg-ico"><i class="fa-solid fa-layer-group"></i></div>
              <div>
                <div class="pg-title">Subscription Plans</div>
                <div class="pg-sub">Manage pricing and plan features</div>
              </div>
            </div>
          </div>
          
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px;">
            ${plans.map(plan => `
              <div class="card" style="border:1px solid var(--cb);border-radius:12px;padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
                  <div>
                    <h3 style="font-size:18px;font-weight:700;color:var(--td);margin:0;">${plan.name}</h3>
                    <div style="font-size:24px;font-weight:800;color:var(--sa-primary);margin-top:8px;">Rs ${plan.price.toLocaleString()}<span style="font-size:12px;font-weight:400;color:var(--tl);">/mo</span></div>
                  </div>
                  <span class="tag bg-p">${plan.tenant_count || 0} tenants</span>
                </div>
                <ul style="list-style:none;padding:0;margin:20px 0;">
                  <li style="padding:6px 0;font-size:13px;"><i class="fa-solid fa-check" style="color:var(--success);margin-right:8px;"></i> Up to ${plan.student_limit === -1 ? 'Unlimited' : plan.student_limit} students</li>
                  <li style="padding:6px 0;font-size:13px;"><i class="fa-solid fa-check" style="color:var(--success);margin-right:8px;"></i> ${plan.admin_accounts === -1 ? 'Unlimited' : plan.admin_accounts} admin accounts</li>
                  <li style="padding:6px 0;font-size:13px;"><i class="fa-solid fa-check" style="color:var(--success);margin-right:8px;"></i> ${typeof plan.features.sms === 'number' ? plan.features.sms + ' SMS' : plan.features.sms} credits</li>
                  ${plan.features.lms ? '<li style="padding:6px 0;font-size:13px;"><i class="fa-solid fa-check" style="color:var(--success);margin-right:8px;"></i> LMS Module</li>' : ''}
                  ${plan.features.reports ? '<li style="padding:6px 0;font-size:13px;"><i class="fa-solid fa-check" style="color:var(--success);margin-right:8px;"></i> Advanced Reports</li>' : ''}
                  ${plan.features.api ? '<li style="padding:6px 0;font-size:13px;"><i class="fa-solid fa-check" style="color:var(--success);margin-right:8px;"></i> API Access</li>' : ''}
                </ul>
                <button class="btn bt" style="width:100%;" onclick="SuperAdmin.goNav('plans', 'flags&id=${plan.id}')">Manage Features</button>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    } catch (err) {
      console.error('[SuperAdmin] Error loading plans:', err);
      mainContent.innerHTML = `<div class="pg fu"><div class="card"><p style="color:red;">Error loading plans</p></div></div>`;
    }
  }

  /* ============================================================
     REVENUE PAGE RENDERER
     ============================================================ */
  async function renderRevenue() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';
    
    try {
      const result = await fetchAPI('RevenueApi.php?action=mrr');
      const data = result.data || {};
      
      mainContent.innerHTML = `
        <div class="pg fu">
          <div class="pg-head">
            <div class="pg-left">
              <div class="pg-ico"><i class="fa-solid fa-chart-line"></i></div>
              <div>
                <div class="pg-title">Revenue Analytics</div>
                <div class="pg-sub">Monthly Recurring Revenue (MRR)</div>
              </div>
            </div>
          </div>
          
          <div class="sg">
            <div class="sc fu">
              <span style="font-size:12px;font-weight:700;color:var(--tl);text-transform:uppercase;">Current MRR</span>
              <div class="sc-val" style="font-size:28px;margin-top:8px;">${data.mrr_formatted || 'Rs 0'}</div>
              <p class="sc-delta"><i class="fa-solid fa-arrow-trend-up" style="color:var(--success);"></i> ${data.yoy_growth || 0}% YoY</p>
            </div>
            <div class="sc fu">
              <span style="font-size:12px;font-weight:700;color:var(--tl);text-transform:uppercase;">Total Revenue</span>
              <div class="sc-val" style="font-size:28px;margin-top:8px;">Rs ${((data.total_mrr || 0) / 1000).toFixed(1)}K</div>
              <p class="sc-delta">Last 12 months</p>
            </div>
          </div>
          
          <div class="card" style="min-height:300px;margin-top:20px;">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:20px;">MRR Trend</h3>
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      `;
      
      // Render chart
      if (data.mrr_trend && data.mrr_trend.length > 0) {
        const ctx = document.getElementById('revenueChart')?.getContext('2d');
        if (ctx) {
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.mrr_trend.map(d => d.month),
              datasets: [{
                label: 'MRR',
                data: data.mrr_trend.map(d => d.mrrK),
                backgroundColor: '#009E7E',
                borderRadius: 4
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v + 'K' } },
                x: { grid: { display: false } }
              }
            }
          });
        }
      }
    } catch (err) {
      console.error('[SuperAdmin] Error loading revenue:', err);
      mainContent.innerHTML = `<div class="pg fu"><div class="card"><p style="color:red;">Error loading revenue data</p></div></div>`;
    }
  }

  /* ============================================================
     SUPPORT PAGE RENDERER
     ============================================================ */
  async function renderSupport() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';
    
    try {
      const result = await fetchAPI('SupportApi.php?action=list&status=open');
      const tickets = result.data || [];
      const statusCounts = result.status_counts || {};
      
      mainContent.innerHTML = `
        <div class="pg fu">
          <div class="pg-head">
            <div class="pg-left">
              <div class="pg-ico"><i class="fa-solid fa-headset"></i></div>
              <div>
                <div class="pg-title">Support Tickets</div>
                <div class="pg-sub">Manage institute support requests</div>
              </div>
            </div>
          </div>
          
          <div style="display:flex;gap:12px;margin-bottom:20px;">
            <button class="btn ${activeSub === 'open' || !activeSub ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('support', 'open')">Open (${statusCounts.open || 0})</button>
            <button class="btn ${activeSub === 'resolved' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('support', 'resolved')">Resolved (${statusCounts.resolved || 0})</button>
            <button class="btn ${activeSub === 'all' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('support', 'all')">All</button>
          </div>
          
          <div class="card">
            ${tickets.length === 0 ? 
              '<div style="text-align:center;padding:40px;color:var(--tl);">No support tickets</div>' : 
              tickets.map(t => `
                <div style="padding:16px;border-bottom:1px solid var(--cb);display:flex;align-items:center;gap:16px;">
                  <div style="width:10px;height:10px;border-radius:50%;background:${t.priority === 'critical' ? 'var(--red)' : t.priority === 'high' ? 'var(--amber)' : 'var(--blue)'}"></div>
                  <div style="flex:1;">
                    <div style="font-weight:600;">${t.subject || 'No Subject'}</div>
                    <div style="font-size:11px;color:var(--tl);">${t.tenant_name || 'N/A'} - ${new Date(t.created_at).toLocaleDateString()}</div>
                  </div>
                  <span class="tag ${t.status === 'open' ? 'bg-r' : t.status === 'in_progress' ? 'bg-t' : 'bg-g'}">${t.status}</span>
                  <button class="btn-icon" onclick="SuperAdmin.viewTicket(${t.id})"><i class="fa-solid fa-eye"></i></button>
                </div>
              `).join('')
            }
          </div>
        </div>
      `;
    } catch (err) {
      console.error('[SuperAdmin] Error loading support:', err);
      mainContent.innerHTML = `<div class="pg fu"><div class="card"><p style="color:red;">Error loading tickets</p></div></div>`;
    }
  }

  // Placeholder functions for other pages - will be implemented as needed
  async function renderFlags() { fetchAndRender('pages/super_admin/flags.php'); }
  async function renderPlanAssign() { fetchAndRender('pages/super_admin/plan-assign.php'); }
  async function renderPayments() { fetchAndRender('pages/super_admin/payments.php'); }
  async function renderInvoices() { fetchAndRender('pages/super_admin/invoices.php'); }
  async function renderUsers() { fetchAndRender('pages/super_admin/users.php'); }
  async function renderHeatmap() { fetchAndRender('pages/super_admin/heatmap.php'); }
  async function renderSmsCredits() { fetchAndRender('pages/super_admin/sms-credits.php'); }
  async function renderMaintenance() { fetchAndRender('pages/super_admin/maintenance.php'); }
  async function renderAnnouncements() { fetchAndRender('pages/super_admin/announcements.php'); }
  async function renderLogs() { fetchAndRender('pages/super_admin/logs.php'); }
  async function renderDbInsights() { fetchAndRender('pages/super_admin/db-insights.php'); }
  async function renderBranding() { fetchAndRender('pages/super_admin/branding.php'); }
  async function renderSmsTemplates() { fetchAndRender('pages/super_admin/sms-templates.php'); }
  async function renderEmailConfig() { fetchAndRender('pages/super_admin/email-config.php'); }
  async function renderProfile() { fetchAndRender('pages/super_admin/profile.php'); }
  async function renderChangePassword() { fetchAndRender('pages/super_admin/change-password.php'); }
  async function renderActivityLog() { fetchAndRender('pages/super_admin/activity-log.php'); }

  async function fetchSuperAdminStats() {
    try {
      const url = typeof window.APP_URL !== 'undefined' ? window.APP_URL + '/api/super_admin_stats.php' : '/api/super_admin_stats.php';
      const response = await fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch dashboard stats');
      }
      
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'API returned error');
      }
      
      return result.data;
    } catch (err) {
      console.error("[SuperAdmin] API Error:", err);
      // Return default values on error
      return {
        totalInstitutes: 0,
        totalUsers: 0,
        activeStudents: 0,
        pendingApprovals: 0,
        recentActivity: []
      };
    }
  }

function goNav(id, subId = null) {
    activeNav = id;
    activeSub = subId;
    
    // Ensure we are on the dashboard shell when navigating via SPA
    const baseUrl = window.APP_URL ? window.APP_URL + '/dash/super-admin/' : '/erp/dash/super-admin/';
    const url = new URL(baseUrl, window.location.origin);
    const pageVal = subId ? `${id}-${subId}` : id;
    url.searchParams.set('page', pageVal);
    
    window.history.pushState({ pageVal }, '', url.toString());
    
    if (window.innerWidth < 1024) {
      const sidebar = document.getElementById('sidebar');
      if (sidebar) document.body.classList.remove('sb-active');
    }
    
    // Update sidebar active states
    updateSidebarActiveStates(id, subId);
    
    renderPage();
  }

  /**
   * Update sidebar active states after navigation
   * This highlights the current menu item and opens parent submenus
   */
  function updateSidebarActiveStates(navId, subId) {
    // Remove all active classes first
    document.querySelectorAll('.nb-btn.active').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sub-btn.active').forEach(el => el.classList.remove('active'));
    
    // Find and activate the current nav item
    const navButtons = document.querySelectorAll('.nb-btn');
    navButtons.forEach(btn => {
      const btnText = btn.querySelector('.nbl')?.textContent?.toLowerCase() || '';
      
      // Map nav IDs to readable menu labels
      const navToLabelMap = {
        'overview': 'overview',
        'tenants': 'tenant management',
        'plans': 'plan management',
        'revenue': 'revenue analytics',
        'analytics': 'platform analytics',
        'support': 'support tickets',
        'system': 'system config',
        'logs': 'system logs',
        'settings': 'platform settings',
        'profile': 'profile'
      };
      
      const expectedLabel = navToLabelMap[navId] || navId;
      if (btnText.includes(expectedLabel) || btnText === expectedLabel) {
        btn.classList.add('active');
        // Open parent submenu if exists
        const submenu = btn.nextElementSibling;
        if (submenu && submenu.classList.contains('sub-menu')) {
          submenu.style.display = 'block';
          const chevron = submenu.previousElementSibling.querySelector('.nbc');
          if (chevron) chevron.classList.add('open');
        }
      }
    });
    
    // If we have a subId, find and activate the specific submenu item
    if (subId) {
      const subButtons = document.querySelectorAll('.sub-btn');
      subButtons.forEach(btn => {
        const btnText = btn.textContent?.toLowerCase().trim() || '';
        
        // Map subIds to button text patterns
        const subToTextMap = {
          'all': 'all institutes',
          'add': 'add new',
          'suspended': 'suspended',
          'sub-plans': 'subscription plans',
          'flags': 'feature flags',
          'assign': 'plan assignment',
          'mrr': 'mrr / arr dashboard',
          'payments': 'payment history',
          'invoices': 'invoice generator',
          'users': 'active users',
          'heatmap': 'feature heatmap',
          'sms': 'sms credit consumption',
          'open': 'open tickets',
          'resolved': 'resolved',
          'impersonate': 'impersonation log',
          'toggles': 'feature toggles',
          'maintenance': 'maintenance mode',
          'announce': 'push announcements',
          'email-cfg': 'email config',
          'audit': 'audit logs',
          'errors': 'error logs',
          'api': 'api request logs',
          'db': 'db insights',
          'branding': 'platform branding',
          'sms-tpl': 'sms templates',
          'view': 'profile',
          'password': 'change password',
          'activity': 'activity log'
        };
        
        const expectedText = subToTextMap[subId];
        if (expectedText && btnText.includes(expectedText)) {
          btn.classList.add('active');
          // Ensure parent submenu is open
          const parentSubmenu = btn.closest('.sub-menu');
          if (parentSubmenu) {
            parentSubmenu.style.display = 'block';
            const chevron = parentSubmenu.previousElementSibling?.querySelector('.nbc');
            if (chevron) chevron.classList.add('open');
          }
        }
      });
    }
  }

  // Expose goNav globally for sidebar onclick handlers
  window.goNav = goNav;
  window.toggleMenu = toggleMenu;

// Handle browser back/forward navigation
  window.addEventListener('popstate', (e) => {
    let pageVal;
    if (e.state && e.state.pageVal) {
      pageVal = e.state.pageVal;
    } else {
      const urlParams = new URLSearchParams(window.location.search);
      pageVal = urlParams.get('page') || 'overview';
    }
    activeNav = pageVal.includes('-') ? pageVal.split('-')[0] : pageVal;
    activeSub = pageVal.includes('-') ? pageVal.split('-')[1] : null;
    
    // Update sidebar active states for back/forward navigation
    updateSidebarActiveStates(activeNav, activeSub);
    
    renderPage();
  });

  /* ============================================================
     SIDEBAR — mirrors institute-admin sidebar behaviour
     Uses body.sb-active  (mobile open)
         body.sb-collapsed (desktop icon rail)
     ============================================================ */

  function initSidebar() {
    const toggleBtns = document.querySelectorAll(".sb-toggle");
    const overlay    = document.getElementById("sbOverlay");

    toggleBtns.forEach((btn) => {
      btn.addEventListener("click", function () {
        toggleSidebar();
      });
    });

    // Close on overlay click (mobile)
    if (overlay) {
      overlay.addEventListener("click", function () {
        document.body.classList.remove("sb-active");
        overlay.classList.remove("active");
      });
    }

    // On desktop, restore collapsed state from localStorage
    if (window.innerWidth >= 1024) {
      if (localStorage.getItem("sa-sb-collapsed") === "1") {
        document.body.classList.add("sb-collapsed");
      }
    }
  }

  /**
   * Toggle sidebar open/closed
   * Mobile  → toggles body.sb-active
   * Desktop → toggles body.sb-collapsed
   */
  function toggleSidebar() {
    const overlay = document.getElementById("sbOverlay");

    if (window.innerWidth < 1024) {
      // Mobile: slide-in drawer
      document.body.classList.toggle("sb-active");
      if (overlay) overlay.classList.toggle("active");
    } else {
      // Desktop: collapse to icon rail
      document.body.classList.toggle("sb-collapsed");
      const isCollapsed = document.body.classList.contains("sb-collapsed");
      localStorage.setItem("sa-sb-collapsed", isCollapsed ? "1" : "0");
    }
  }

  /**
   * Toggle a submenu open/closed
   * @param {string} menuId - ID of the submenu element
   */
  function toggleMenu(menuId) {
    const submenu = document.getElementById(menuId);
    const chevron = document.getElementById("chev-" + menuId);

    if (!submenu) return;

    const isOpen = submenu.style.display !== "none" && submenu.style.display !== "";
    submenu.style.display = isOpen ? "none" : "block";

    if (chevron) {
      chevron.classList.toggle("open", !isOpen);
    }
  }

  /* ============================================================
     DROPDOWNS — same pattern as institute-admin
     ============================================================ */

  function initDropdowns() {
    const chip     = document.getElementById("userChip");
    const dropdown = document.getElementById("userDropdown");
    const notifChip = document.getElementById("notifChip");
    const notifDropdown = document.getElementById("notifDropdown");

    if (chip && dropdown) {
      chip.addEventListener("click", function (e) {
        e.stopPropagation();
        if (notifDropdown) notifDropdown.classList.remove("active");
        dropdown.classList.toggle("active");
      });
    }

    if (notifChip && notifDropdown) {
      notifChip.addEventListener("click", function (e) {
        e.stopPropagation();
        if (dropdown) dropdown.classList.remove("active");
        notifDropdown.classList.toggle("active");
      });
    }

    document.addEventListener("click", function () {
      if (dropdown) dropdown.classList.remove("active");
      if (notifDropdown) notifDropdown.classList.remove("active");
    });
  }

  /* ============================================================
     CHARTS
     ============================================================ */

  function initCharts() {
    if (typeof Chart === "undefined") return;

    document.querySelectorAll("[data-chart]").forEach((canvas) => {
      const type     = canvas.dataset.chart;
      const rawData  = canvas.dataset.chartData;
      const chartData = rawData ? JSON.parse(rawData) : {};

      charts[canvas.id] = new Chart(canvas, {
        type: type,
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: "bottom" },
          },
        },
      });
    });
  }

  /* ============================================================
     MODALS
     ============================================================ */

  function initModals() {
    document.querySelectorAll("[data-modal]").forEach((trigger) => {
      trigger.addEventListener("click", function (e) {
        e.preventDefault();
        openModal(this.dataset.modal);
      });
    });
  }

  function openModal(modalId) {
    const modal   = document.getElementById(modalId);
    const overlay = document.getElementById("sbOverlay");
    if (modal)   modal.classList.add("active");
    if (overlay) overlay.classList.add("active");
  }

  function closeModal(modalId) {
    const modal   = document.getElementById(modalId);
    const overlay = document.getElementById("sbOverlay");
    if (modal)   modal.classList.remove("active");
    if (overlay) overlay.classList.remove("active");
  }

  function closeAllModals() {
    document.querySelectorAll(".sa-modal.active").forEach((m) =>
      m.classList.remove("active")
    );
    const overlay = document.getElementById("sbOverlay");
    if (overlay) overlay.classList.remove("active");
  }

  /* ============================================================
     NOTIFICATIONS — SweetAlert2 toast
     ============================================================ */

  function showNotification(message, type = "info") {
    if (typeof Swal === "undefined") {
      console.warn("[SuperAdmin] SweetAlert2 not loaded");
      return;
    }
    Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
    }).fire({ icon: type, title: message });
  }

  /* ============================================================
     API HELPER
     ============================================================ */

  async function fetchAPI(url, options = {}) {
    const defaults = {
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    };

    let fullUrl = url;
    if (!url.includes('://')) {
        if (url.startsWith('/')) {
            fullUrl = (window.APP_URL || '') + url;
        } else {
            fullUrl = (window.APP_URL || '') + '/api/superadmin/' + url;
        }
    }
    
    try {
      const res  = await fetch(fullUrl, { ...defaults, ...options });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "An error occurred");
      return data;
    } catch (err) {
      console.error("[SuperAdmin] API Error:", err);
      showNotification(err.message, "error");
      throw err;
    }
  }

  /* ============================================================
     CONFIRM DIALOG
     ============================================================ */

  async function confirmAction(
    title             = "Are you sure?",
    text              = "This action cannot be undone.",
    confirmButtonText = "Yes, proceed"
  ) {
    return await Swal.fire({
      title,
      text,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#009E7E",
      cancelButtonColor: "#E2E8F0",
      confirmButtonText,
      cancelButtonText: "Cancel",
    });
  }

  /* ============================================================
     STAT CARD UPDATER
     ============================================================ */

  function updateStatCard(cardId, value, change = null) {
    const card = document.getElementById(cardId);
    if (!card) return;

    const valEl    = card.querySelector(".sc-val");
    const deltaEl  = card.querySelector(".sc-delta");

    if (valEl)   valEl.textContent = value.toLocaleString();

    if (deltaEl && change !== null) {
      deltaEl.textContent = (change > 0 ? "+" : "") + change + "% vs last month";
      deltaEl.className   = "sc-delta " + (change >= 0 ? "positive" : "negative");
    }
  }

  function updateWorkflow() {
    setTimeout(() => {
        const checks = document.querySelectorAll('.wf-check');
        let done = 0;
        checks.forEach(c => { if(c.checked) done++; });
        const statusEl = document.getElementById('workflowStatus');
        if (statusEl) statusEl.textContent = `${done} / 5 Completed`;
    }, 50);
  }

  /* ============================================================
     PUBLIC API
     ============================================================ */

  return {
    init,
    goNav,
    toggleSidebar,
    toggleMenu,
    showNotification,
    confirmAction,
    fetchAPI,
    openModal,
    closeModal,
    closeAllModals,
    updateStatCard,
    updateWorkflow,
    // Page renderers
    renderTenants,
    renderPlans,
    renderRevenue,
    renderSupport,
    // Helper functions exposed globally
    filterTenants: () => { /* Will be implemented */ },
    viewTenant: (id) => { console.log('View tenant:', id); },
    editTenant: (id) => { console.log('Edit tenant:', id); },
    impersonateTenant: (id) => { console.log('Impersonate tenant:', id); },
    viewTicket: (id) => { console.log('View ticket:', id); },
    charts,
    dataTables,
  };
})();

// Auto-init on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  SuperAdmin.init();
});

// Make goNav available globally for onclick handlers
window.goNav = function(id, subId) {
  SuperAdmin.goNav(id, subId);
};

// CommonJS export guard
if (typeof module !== "undefined" && module.exports) {
  module.exports = SuperAdmin;
}
