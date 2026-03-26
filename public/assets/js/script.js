/**
 * Hamro ERP — Super Admin Panel
 * Platform Blueprint V3.0 — Implementation
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page') || 'overview';
    
    window.activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
    window.activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;
    let expanded = { tenants: true, plans: false, revenue: false, analytics: false, support: false, system: false, logs: false, settings: false };
    
    // ── LIVE DATA STATE ──
    let platformStats = null;
    let tenantData = [];
    let dbInsights = null;
    let isLoading = false;
    let realtimeMetricsInterval = null;
    const REALTIME_POLL_MS = 15000; // 15 seconds

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbClose = document.getElementById('sbClose');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── SIDEBAR TOGGLE LOGIC ──
    const toggleSidebar = () => {
        if (window.innerWidth >= 1024) {
            document.body.classList.toggle('sb-collapsed');
        } else {
            document.body.classList.toggle('sb-active');
        }
    };

    const closeSidebar = () => {
        document.body.classList.remove('sb-active');
    };

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbClose) sbClose.addEventListener('click', closeSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // ── DATA ──
    const NAV = [
        { id: "overview", icon: "fa-house", label: "Overview", sub: null, sec: "MAIN" },
        { id: "tenants", icon: "fa-building-columns", label: "Tenant Management", sub: [
            { id: "all", l: "All Institutes", icon: "fa-list" },
            { id: "add", l: "Add New Institute", icon: "fa-plus" },
            { id: "suspended", l: "Suspended Institutes", icon: "fa-ban" }
        ], sec: "MANAGEMENT" },
        { id: "plans", icon: "fa-layer-group", label: "Plan Management", sub: [
            { id: "sub-plans", l: "Subscription Plans", icon: "fa-credit-card" },
            { id: "flags", l: "Feature Flags", icon: "fa-toggle-on" },
            { id: "assign", l: "Plan Assignment", icon: "fa-tag" }
        ], sec: "MANAGEMENT" },
        { id: "revenue", icon: "fa-chart-line", label: "Revenue Analytics", sub: [
            { id: "mrr", l: "MRR / ARR Dashboard", icon: "fa-coins" },
            { id: "payments", l: "Payment History", icon: "fa-receipt" },
            { id: "invoices", l: "Invoice Generator", icon: "fa-file-invoice" }
        ], sec: "ANALYTICS" },
        { id: "analytics", icon: "fa-chart-bar", label: "Platform Analytics", sub: [
            { id: "users", l: "Active Users", icon: "fa-users" },
            { id: "heatmap", l: "Feature Usage Heatmap", icon: "fa-fire" },
            { id: "sms", l: "SMS Credit Consumption", icon: "fa-message" }
        ], sec: "ANALYTICS" },
        { id: "support", icon: "fa-headset", label: "Support Tickets", sub: [
            { id: "open", l: "Open Tickets", icon: "fa-ticket" },
            { id: "impersonate", l: "Tenant Impersonation Log", icon: "fa-user-secret" },
            { id: "resolved", l: "Resolved History", icon: "fa-check-circle" }
        ], sec: "SUPPORT" },
        { id: "system", icon: "fa-sliders", label: "System Configuration", sub: [
            { id: "toggles", l: "Feature Toggles", icon: "fa-toggle-off" },
            { id: "maintenance", l: "Maintenance Mode", icon: "fa-tools" },
            { id: "announce", l: "Push Announcements", icon: "fa-bullhorn" }
        ], sec: "SYSTEM" },
        { id: "logs", icon: "fa-scroll", label: "System Logs", sub: [
            { id: "audit", l: "Audit Logs", icon: "fa-clock-rotate-left" },
            { id: "errors", l: "Error Logs", icon: "fa-triangle-exclamation" },
            { id: "api", l: "API Request Logs", icon: "fa-code" },
            { id: "db", l: "Database Insights", icon: "fa-database" }
        ], sec: "SYSTEM" },
        { id: "settings", icon: "fa-gear", label: "Settings", sub: [
            { id: "branding", l: "Platform Branding", icon: "fa-palette" },
            { id: "sms-tpl", l: "Default SMS Templates", icon: "fa-comment-dots" },
            { id: "email-cfg", l: "Email Config", icon: "fa-envelope" }
        ], sec: "SYSTEM" },
    ];

    const RECENT_SIGNUPS = [
        { name: "Global Academic Center", plan: "Professional", date: "2081-08-15", status: "Active" },
        { name: "Sagarmatha Public School", plan: "Growth", date: "2081-08-14", status: "Active" },
        { name: "Everest Nursing College", plan: "Enterprise", date: "2081-08-12", status: "Trial" },
        { name: "Lumbini Technical Hub", plan: "Starter", date: "2081-08-10", status: "Active" },
        { name: "KTM Entrance Center", plan: "Growth", date: "2081-08-08", status: "Active" }
    ];

    const MRR_DATA = [
        { m: "Jan", v: 85, y: 72 }, { m: "Feb", v: 92, y: 78 }, { m: "Mar", v: 108, y: 82 },
        { m: "Apr", v: 115, y: 88 }, { m: "May", v: 128, y: 94 }, { m: "Jun", v: 142, y: 98 },
        { m: "Jul", v: 138, y: 105 }, { m: "Aug", v: 155, y: 112 }, { m: "Sep", v: 168, y: 118 },
        { m: "Oct", v: 175, y: 125 }, { m: "Nov", v: 188, y: 132 }, { m: "Dec", v: 205, y: 140 }
    ];

    // ── API FETCHERS ──
    async function loadStats() {
        isLoading = true;
        try {
            const r = await window.authFetch(`${window.APP_URL}/api/super_admin_stats.php`);
            const res = await r.json();
            if (res.success) {
                platformStats = res.data;
                window.platformStatsData = res.data; // Expose for partials
            }
        } catch (e) { console.error(e); }
        isLoading = false;
    }

    async function loadTenants(onlySuspended = false) {
        isLoading = true;
        try {
            const r = await window.authFetch(`${window.APP_URL}/api/tenants.php?status=${onlySuspended ? 'suspended' : ''}`);
            const res = await r.json();
            if (res.success) tenantData = res.data;
        } catch (e) { console.error(e); }
        isLoading = false;
    }

    async function loadDatabaseInsights() {
        isLoading = true;
        try {
            const r = await window.authFetch(`${window.APP_URL}/api/database_insights.php`);
            const res = await r.json();
            if (res.success) dbInsights = res.data;
        } catch (e) { console.error(e); }
        isLoading = false;
    }

    function stopRealtimeMetrics() {
        if (realtimeMetricsInterval) {
            clearInterval(realtimeMetricsInterval);
            realtimeMetricsInterval = null;
        }
    }

    function startRealtimeMetrics() {
        stopRealtimeMetrics();
        realtimeMetricsInterval = setInterval(() => {
            if (window.activeNav === 'overview') {
                loadStats().then(() => { if (window.activeNav === 'overview') renderOverview(); });
            } else if (window.activeNav === 'logs' && window.activeSub === 'db') {
                loadDatabaseInsights().then(() => { if (window.activeNav === 'logs' && window.activeSub === 'db') renderPage(); });
            }
        }, REALTIME_POLL_MS);
    }

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subId = null) => {
        window.activeNav = id;
        window.activeSub = subId;

        // Update URL via pushState
        const url = new URL(window.location);
        const pageVal = subId ? `${id}-${subId}` : id;
        url.searchParams.set('page', pageVal);
        window.history.pushState({ pageVal }, '', url);

        if (window.innerWidth < 1024) closeSidebar();
        renderSidebar();
        
        // Trigger data load based on route and real-time polling
        stopRealtimeMetrics();
        if (id === 'overview') {
            loadStats().then(renderPage);
            startRealtimeMetrics();
        } else if (id === 'tenants' && (subId === 'all' || subId === 'suspended')) {
            loadTenants(subId === 'suspended').then(renderPage);
        } else if (id === 'logs' && subId === 'db') {
            loadDatabaseInsights().then(renderPage);
            startRealtimeMetrics();
        } else {
            renderPage();
        }
    };

    // Handle Browser Back/Forward
    window.addEventListener('popstate', (e) => {
        let pageVal;
        if (e.state && e.state.pageVal) {
            pageVal = e.state.pageVal;
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            pageVal = urlParams.get('page') || 'overview';
        }
        window.activeNav = pageVal.includes('-') ? pageVal.split('-')[0] : pageVal;
        window.activeSub = pageVal.includes('-') ? pageVal.split('-')[1] : null;
        
        renderSidebar();
        stopRealtimeMetrics();
        if (window.activeNav === 'overview') {
            loadStats().then(renderPage);
            startRealtimeMetrics();
        } else if (window.activeNav === 'tenants' && (window.activeSub === 'all' || window.activeSub === 'suspended')) {
            loadTenants(window.activeSub === 'suspended').then(renderPage);
        } else if (window.activeNav === 'logs' && window.activeSub === 'db') {
            loadDatabaseInsights().then(renderPage);
            startRealtimeMetrics();
        } else {
            renderPage();
        }
    });

    window.toggleExp = (id) => {
        expanded[id] = !expanded[id];
        renderSidebar();
    };

    function renderSidebar() {
        const sections = [...new Set(NAV.map(n => n.sec))];
        let html = '';

        sections.forEach(sec => {
            html += `<div class="sb-sec"><div class="sb-sec-lbl">${sec}</div>`;

            NAV.filter(n => n.sec === sec).forEach(nav => {
                const isActive = window.activeNav === nav.id && !window.activeSub;
                const isExp = expanded[nav.id];

                html += `<div class="sb-item">
                    <button class="sb-btn ${isActive ? 'active' : ''}" onclick="${nav.sub ? `toggleExp('${nav.id}')` : `goNav('${nav.id}')`}">
                        <i class="fa-solid ${nav.icon}"></i>
                        <span class="sb-lbl">${nav.label}</span>
                        ${nav.sub ? `<i class="fa-solid fa-chevron-right sb-chev ${isExp ? 'open' : ''}"></i>` : ''}
                    </button>`;

                if (nav.sub && isExp) {
                    html += `<div class="sub-menu">`;
                    nav.sub.forEach(s => {
                        const isSubActive = window.activeNav === nav.id && window.activeSub === s.id;
                        html += `<button class="sub-btn ${isSubActive ? 'active' : ''}" onclick="goNav('${nav.id}', '${s.id}')">
                            <i class="fa-solid ${s.icon}"></i>
                            ${s.l}
                        </button>`;
                    });
                    html += `</div>`;
                }
                html += `</div>`;
            });
            html += `</div>`;
        });

        // Append Install App Button
        html += `
            <div class="sb-install-box">
                <button class="install-btn-trigger" onclick="openPwaModal()">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <span>Install App</span>
                </button>
            </div>
        `;

        sbBody.innerHTML = html;
        renderBottomNav();
    }

    function renderBottomNav() {
        let bNav = document.getElementById('bottomNav');
        if (!bNav) {
            bNav = document.createElement('nav');
            bNav.id = 'bottomNav';
            bNav.className = 'mobile-bottom-nav';
            document.body.appendChild(bNav);
        }

        const items = [
            { id: 'overview', icon: 'fa-house', label: 'Home', action: "goNav('overview')" },
            { id: 'tenants', icon: 'fa-building-columns', label: 'Tenants', action: "goNav('tenants', 'all')" },
            { id: 'plans', icon: 'fa-layer-group', label: 'Plans', action: "goNav('plans', 'sub-plans')" },
            { id: 'revenue', icon: 'fa-chart-line', label: 'Revenue', action: "goNav('revenue', 'mrr')" },
            { id: 'support', icon: 'fa-headset', label: 'Support', action: "goNav('support', 'open')" }
        ];

        let html = '';
        items.forEach(item => {
            const isActive = window.activeNav === item.id;
            html += `<button class="mb-nav-btn ${isActive ? 'active' : ''}" onclick="${item.action}">
                <i class="fa-solid ${item.icon}"></i>
                <span>${item.label}</span>
            </button>`;
        });
        bNav.innerHTML = html;
    }

    // ── PAGE RENDERING ──
    function renderPage() {
        mainContent.innerHTML = '<div class="page fu">Loading...</div>';

        if (window.activeNav === 'overview') {
            renderOverview();
            return;
        }

        // Tenant Management
        if (window.activeNav === 'tenants') {
            if (window.activeSub === 'all' || window.activeSub === 'suspended') {
                renderTenantsList(window.activeSub === 'suspended');
                return;
            }
            if (window.activeSub === 'add') {
                renderAddTenantForm();
                return;
            }
        }
        // Plan Management
        if (window.activeNav === 'plans') {
            if (window.activeSub === 'sub-plans') { fetchPage(`${window.APP_URL}/pages/super_admin/plans.php`); return; }
            if (window.activeSub === 'flags') { fetchPage(`${window.APP_URL}/pages/super_admin/flags.php`); return; }
            if (window.activeSub === 'assign') { fetchPage(`${window.APP_URL}/pages/super_admin/plan-assign.php`); return; }
        }

        // Revenue Analytics
        if (window.activeNav === 'revenue') {
            if (window.activeSub === 'mrr') { fetchPage(`${window.APP_URL}/pages/super_admin/revenue.php`); return; }
            if (window.activeSub === 'payments') { fetchPage(`${window.APP_URL}/pages/super_admin/payments.php`); return; }
            if (window.activeSub === 'invoices') { fetchPage(`${window.APP_URL}/pages/super_admin/invoices.php`); return; }
        }

        // Platform Analytics
        if (window.activeNav === 'analytics') {
            if (window.activeSub === 'users') { fetchPage(`${window.APP_URL}/pages/super_admin/users.php`); return; }
            if (window.activeSub === 'heatmap') { fetchPage(`${window.APP_URL}/pages/super_admin/heatmap.php`); return; }
            if (window.activeSub === 'sms') { fetchPage(`${window.APP_URL}/pages/super_admin/sms.php`); return; }
        }

        // Support
        if (window.activeNav === 'support') {
            if (window.activeSub === 'open' || window.activeSub === 'resolved') {
                fetchPage(`${window.APP_URL}/pages/super_admin/support.php`);
                return;
            }
            if (window.activeSub === 'impersonate') {
                fetchPage(`${window.APP_URL}/pages/super_admin/impersonation-logs.php`);
                return;
            }
        }

        // System Configuration
        if (window.activeNav === 'system') {
            if (window.activeSub === 'toggles') { fetchPage(`${window.APP_URL}/pages/super_admin/flags.php`); return; }
            if (window.activeSub === 'maintenance') { fetchPage(`${window.APP_URL}/pages/super_admin/maintenance.php`); return; }
            if (window.activeSub === 'announce') { fetchPage(`${window.APP_URL}/pages/super_admin/announcements.php`); return; }
            if (window.activeSub === 'email-cfg') { fetchPage(`${window.APP_URL}/pages/super_admin/email-config.php`); return; }
            renderConfigList(); return;
        }

        // System Logs
        if (window.activeNav === 'logs') {
            if (window.activeSub === 'db') { renderDatabaseInsights(); return; }
            if (window.activeSub === 'audit' || window.activeSub === 'errors' || window.activeSub === 'api') {
                fetchPage(`${window.APP_URL}/pages/super_admin/logs.php`);
                return;
            }
            renderLogsList(window.activeSub); return;
        }

        // Profile
        if (window.activeNav === 'profile') {
            if (window.activeSub === 'view') { fetchPage(`${window.APP_URL}/pages/super_admin/profile.php`); return; }
            if (window.activeSub === 'password') { fetchPage(`${window.APP_URL}/pages/super_admin/change-password.php`); return; }
            if (window.activeSub === 'activity') { fetchPage(`${window.APP_URL}/pages/super_admin/activity-log.php`); return; }
        }

        // Settings - Branding
        if (window.activeNav === 'settings') {
            if (window.activeSub === 'branding') { fetchPage(`${window.APP_URL}/pages/super_admin/branding.php`); return; }
            if (window.activeSub === 'sms-tpl') { fetchPage(`${window.APP_URL}/pages/super_admin/sms-templates.php`); return; }
            if (window.activeSub === 'email-cfg') { fetchPage(`${window.APP_URL}/pages/super_admin/email-config.php`); return; }
            renderConfigList(); return;
        }

        renderGenericPage();
    }

    function fetchPage(file) {
        mainContent.innerHTML = '<div class="page fu">Loading...</div>';
        window.authFetch(file)
            .then(res => res.text())
            .then(html => {
                const isFullPage = (file.endsWith('.html') || file.endsWith('.php')) &&
                    html.trim().toLowerCase().startsWith('<!doctype');
                if (isFullPage) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const mainEl = doc.querySelector('main.main') || doc.querySelector('main') || doc.querySelector('.page');
                    let mainHtml = mainEl ? mainEl.innerHTML : '';
                    if (mainEl) {
                        let next = mainEl.nextElementSibling;
                        while (next && next.tagName !== 'SCRIPT') {
                            mainHtml += next.outerHTML;
                            next = next.nextElementSibling;
                        }
                    }
                    if (!mainHtml) mainHtml = html;
                    mainContent.innerHTML = mainHtml;
                    const scripts = doc.body.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        if (oldScript.src) newScript.src = oldScript.src;
                        else newScript.textContent = oldScript.textContent;
                        mainContent.appendChild(newScript);
                    });
                } else {
                    mainContent.innerHTML = html;
                }
                executeScripts(mainContent);
            })
            .catch(err => {
                console.error(err);
                mainContent.innerHTML = '<div class="page">Error loading module.</div>';
            });
    }

    // Map standalone HTML page paths to panel navigation (so injected pages' go() work)
    window.go = function(path) {
        const map = {
            'index.html': ['overview'],
            'profile.php': ['profile', 'view'], 'my-profile.html': ['profile', 'view'],
            'change-password.php': ['profile', 'password'], 'change-password.html': ['profile', 'password'],
            'activity-log.php': ['profile', 'activity'], 'activity-log.html': ['profile', 'activity'],
            'email-config.php': ['settings', 'email-cfg'], 'email-config.html': ['settings', 'email-cfg'],
            'plans.php': ['plans', 'sub-plans'], 'subscription-plans.html': ['plans', 'sub-plans'],
            'flags.php': ['plans', 'flags'], 'feature-flags.html': ['plans', 'flags'],
            'plan-assign.php': ['plans', 'assign'], 'plan-assignment.html': ['plans', 'assign'],
            'payments.php': ['revenue', 'payments'], 'payment-history.html': ['revenue', 'payments'],
            'invoices.php': ['revenue', 'invoices'], 'invoice-generator.html': ['revenue', 'invoices'],
            'users.php': ['analytics', 'users'], 'active-users.html': ['analytics', 'users'],
            'heatmap.php': ['analytics', 'heatmap'], 'feature-heatmap.html': ['analytics', 'heatmap'],
            'revenue.php': ['revenue', 'mrr'], 'revenue-analytics.html': ['revenue', 'mrr'], 'mrr-dashboard.html': ['revenue', 'mrr'],
            'sms.php': ['analytics', 'sms'], 'sms-credits.html': ['analytics', 'sms'],
            'support.php': ['support', 'open'], 'support-tickets.html': ['support', 'open'],
            'impersonation-log.html': ['support', 'impersonate'],
            'resolved-tickets.html': ['support', 'resolved'],
            'logs.php': ['logs', 'audit'], 'system-logs.html': ['logs', 'audit'], 'audit-logs.html': ['logs', 'audit'],
            'error-logs.html': ['logs', 'errors'], 'api-request-logs.html': ['logs', 'api'], 'db-insights.html': ['logs', 'db'],
            'tenant-management.html': ['tenants', 'all'], 'all-institutes.html': ['tenants', 'all'],
            'tenant-add.php': ['tenants', 'add'], 'add-institute.html': ['tenants', 'add'],
            'suspended.html': ['tenants', 'suspended']
        };
        const key = path.replace(/^.*\//, '');
        const nav = map[key] || map[path];
        if (nav) goNav(nav[0], nav[1] || null);
        else window.location.href = path;
    };

    function executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function renderOverview() {
        const s = platformStats || {
            totalTenants: 0,
            planStats: { starter: 0, growth: 0, professional: 0, enterprise: 0 },
            mrr: 0,
            recentSignups: [],
            health: { uptime: '0%', latency: '0ms', queue: 0, redis: '0' },
            tickets: { critical: 0, high: 0, normal: 0 }
        };

        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px"><span class="bc-sep">›</span><span class="bc-cur">Overview</span></span>
                </div>
                
                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(0,184,148,0.1); color:var(--green);"><i class="fa-solid fa-gauge-high"></i></div>
                        <div>
                            <div class="page-title">Platform Dashboard</div>
                            <div class="page-sub">Live Platform Metrics · ${new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })}</div>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="qa-bar mb">
                    <button class="btn bt btn-sm" onclick="goNav('tenants', 'add')"><i class="fa-solid fa-building-circle-plus"></i> Add New Institute</button>
                    <button class="btn bs btn-sm" onclick="goNav('plans', 'assign')"><i class="fa-solid fa-tag"></i> Assign Plan</button>
                    <button class="btn bs btn-sm" onclick="goNav('system', 'announce')"><i class="fa-solid fa-bullhorn"></i> Send Platform Announcement</button>
                    <button class="btn bs btn-sm" onclick="goNav('system', 'toggles')"><i class="fa-solid fa-toggle-on"></i> Toggle Feature</button>
                </div>

                <!-- WIDGETS GRID -->
                <div class="stat-grid">
                    <div class="stat-card card">
                        <div class="stat-top">
                            <div class="stat-icon-box ic-green"><i class="fa-solid fa-building"></i></div>
                            <div class="stat-badge bg-g">Live</div>
                        </div>
                        <div class="stat-val">${s.totalTenants}</div>
                        <div class="stat-lbl">Total Active Tenants</div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-top">
                            <div class="stat-icon-box ic-purple"><i class="fa-solid fa-layer-group"></i></div>
                        </div>
                        <div class="stat-lbl" style="margin-bottom:8px">Active Subscriptions</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
                            <div class="tag bg-t" style="font-size:9px">Starter: ${s.planStats.starter || 0}</div>
                            <div class="tag bg-b" style="font-size:9px">Growth: ${s.planStats.growth || 0}</div>
                            <div class="tag bg-p" style="font-size:9px">Pro: ${s.planStats.professional || 0}</div>
                            <div class="tag bg-y" style="font-size:9px">Ent: ${s.planStats.enterprise || 0}</div>
                        </div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-top">
                            <div class="stat-icon-box ic-teal"><i class="fa-solid fa-coins"></i></div>
                            <div class="stat-badge bg-g">↑ Realtime</div>
                        </div>
                        <div class="stat-val">NPR ${(s.mrr / 100000).toFixed(2)}L</div>
                        <div class="stat-lbl">Monthly Recurring Revenue</div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-top">
                            <div class="stat-icon-box ic-red"><i class="fa-solid fa-shield-halved"></i></div>
                            <div class="stat-badge bg-r">Secure</div>
                        </div>
                        <div class="stat-val" style="font-size:1.5rem">Security Active</div>
                        <div class="sc-delta" style="color:var(--green); font-weight:600;">System Shield Online</div>
                    </div>
                </div>

                <div class="g65 mb">
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-chart-line"></i> MRR Growth Trend (Last 6 Months)</div>
                        <div class="bar-chart" style="height:140px; justify-content: space-around;">
                            ${(s.mrrTrend || []).map((d,i)=>`
                                <div class="bar-col">
                                    <div class="bar-fill" style="height:${Math.min(120, d.v/2)}px; background:${i===(s.mrrTrend.length-1)?"var(--green)":"#cbd5e1"};"></div>
                                    <div class="bar-lbl">${d.m}</div>
                                </div>
                            `).join('')}
                        </div>
                        <div style="margin-top:10px; font-size:10px; display:flex; gap:15px; color:var(--text-light)">
                            <span><i class="fa-solid fa-square" style="color:var(--green)"></i> Current Month</span>
                            <span><i class="fa-solid fa-square" style="color:#cbd5e1"></i> Growth Trend (NPR K)</span>
                        </div>
                    </div>
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-heart-pulse"></i> System Health Metrics</div>
                        <div class="health-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:10px">
                            <div class="wf-item">
                                <i class="fa-solid fa-circle-check" style="color:var(--green)"></i>
                                <div><div style="font-size:11px; opacity:0.6">Uptime</div><div style="font-weight:700">${s.health.uptime}</div></div>
                            </div>
                            <div class="wf-item">
                                <i class="fa-solid fa-bolt" style="color:var(--amber)"></i>
                                <div><div style="font-size:11px; opacity:0.6">API p95</div><div style="font-weight:700">${s.health.latency}</div></div>
                            </div>
                            <div class="wf-item">
                                <i class="fa-solid fa-list-ul"></i>
                                <div><div style="font-size:11px; opacity:0.6">Queue Depth</div><div style="font-weight:700">${s.health.queue}</div></div>
                            </div>
                            <div class="wf-item">
                                <i class="fa-solid fa-memory"></i>
                                <div><div style="font-size:11px; opacity:0.6">Redis Mem</div><div style="font-weight:700">${s.health.redis}</div></div>
                            </div>
                        </div>
                        <div class="ct" style="margin-top:20px"><i class="fa-solid fa-message"></i> SMS Platform Credits</div>
                        <div class="prog-t" style="margin-top:5px; height:6px;"><div class="prog-f" style="width:${Math.round((s.sms.usedCredits / (s.sms.totalCredits || 1)) * 100)}%; background:var(--green)"></div></div>
                        <div style="display:flex; justify-content:space-between; font-size:11px; margin-top:5px;">
                            <span>${(s.sms.usedCredits / 1000).toFixed(0)}K Credits Consumed</span>
                            <span>Quota: ${(s.sms.totalCredits / 1000).toFixed(0)}K</span>
                        </div>
                    </div>
                </div>

                <div class="g2 mb">
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-user-plus"></i> Recent Signups</div>
                        <div class="tbl-wrap" style="border:none;">
                            <table style="min-width:100%">
                                <thead><tr><th>Institute</th><th>Plan</th><th>Date</th><th>Status</th></tr></thead>
                                <tbody>
                                    ${s.recentSignups.map(sn=>`
                                        <tr>
                                            <td><div style="font-weight:700; font-size:13px;">${sn.name}</div></td>
                                            <td><span class="tag bg-b" style="text-transform:capitalize;">${sn.plan}</span></td>
                                            <td style="font-size:11px; color:var(--text-light)">${new Date(sn.created_at).toLocaleDateString()}</td>
                                            <td><span class="tag ${sn.status==="active"?"bg-t":"bg-y"}">${sn.status}</span></td>
                                        </tr>
                                    `).join('')}
                                    ${s.recentSignups.length === 0 ? '<tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-light);">No recent signups</td></tr>' : ''}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-headset"></i> Support Tickets Overview</div>
                        <div style="display:flex; gap:10px; margin-bottom:15px">
                            <div style="flex:1; text-align:center; padding:15px; border-radius:10px; background:#fef2f2">
                                <div style="font-size:20px; font-weight:800; color:var(--red)">${s.tickets.critical}</div>
                                <div style="font-size:10px; color:var(--red); font-weight:700">CRITICAL</div>
                            </div>
                            <div style="flex:1; text-align:center; padding:15px; border-radius:10px; background:#fff7ed">
                                <div style="font-size:20px; font-weight:800; color:var(--amber)">${s.tickets.high}</div>
                                <div style="font-size:10px; color:var(--amber); font-weight:700">HIGH</div>
                            </div>
                            <div style="flex:1; text-align:center; padding:15px; border-radius:10px; background:#f0fdf4">
                                <div style="font-size:20px; font-weight:800; color:var(--green)">${s.tickets.normal}</div>
                                <div style="font-size:10px; color:var(--green); font-weight:700">NORMAL</div>
                            </div>
                        </div>
                        <div class="ct"><i class="fa-solid fa-list-check"></i> Daily Workflow</div>
                        <div class="workflow-list">
                            ${[
                                "Check System Health for overnight alerts",
                                "Review new signups and assign plans",
                                "Process pending support tickets",
                                "Monitor MRR dashboard for churn signals",
                                "Review security alert panel"
                            ].map(task=>`
                                <div class="wf-item" style="padding:8px 12px; font-size:12px;">
                                    <i class="fa-regular fa-circle-check"></i>
                                    <span>${task}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderDatabaseInsights() {
        const d = dbInsights;
        if (!d) { mainContent.innerHTML = '<div class="page">Error loading insights.</div>'; return; }

        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px">
                        <span class="bc-sep">›</span>
                        <span class="bc-root" onclick="goNav('logs', 'audit')">System Logs</span>
                        <span class="bc-sep">›</span>
                        <span class="bc-cur">Database Insights</span>
                    </span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(52,73,94,0.1); color:var(--navy);"><i class="fa-solid fa-database"></i></div>
                        <div>
                            <div class="page-title">Database Insights</div>
                            <div class="page-sub">Meta-analysis of the ERP core database, storage health, and record distribution.</div>
                        </div>
                    </div>
                </div>

                <div class="stats-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:20px;">
                    <div class="card" style="padding:20px;">
                        <div style="font-size:12px; color:var(--text-light); font-weight:700; text-transform:uppercase;">Storage Used</div>
                        <div style="font-size:28px; font-weight:800; color:var(--navy); margin:10px 0;">${d.total_size_mb} MB</div>
                        <div style="font-size:11px; color:var(--green); font-weight:600;"><i class="fa fa-server"></i> ${d.db_name}</div>
                    </div>
                    <div class="card" style="padding:20px;">
                        <div style="font-size:12px; color:var(--text-light); font-weight:700; text-transform:uppercase;">Core Tables</div>
                        <div style="font-size:28px; font-weight:800; color:var(--navy); margin:10px 0;">${d.table_count}</div>
                        <div style="font-size:11px; color:var(--text-light); font-weight:600;">MySQL ${d.server_info.split('-')[0]}</div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-top:20px;">
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-table-list"></i> Table Management & Storage</div>
                        <div class="tbl-wrap">
                            <table style="min-width:100%">
                                <thead>
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Estimated Rows</th>
                                        <th>Data Size</th>
                                        <th>Index Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${d.tables.map(t => `
                                        <tr>
                                            <td><code style="color:var(--navy); font-weight:700;">${t.name}</code></td>
                                            <td>${parseInt(t.rows).toLocaleString()}</td>
                                            <td>${t.data_kb} KB</td>
                                            <td>${t.index_kb} KB</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-chart-pie"></i> Record Distribution</div>
                        <div class="dist-list">
                            ${Object.entries(d.distribution).map(([key, val]) => `
                                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--card-border);">
                                    <span style="text-transform:capitalize; font-weight:600; color:var(--text-light);">${key.replace('_', ' ')}</span>
                                    <span style="font-weight:800;">${val.toLocaleString()}</span>
                                </div>
                            `).join('')}
                        </div>
                        <div style="margin-top:25px; text-align:center; font-size:11px; color:var(--text-light); line-height:1.4;">
                            <i class="fa fa-info-circle"></i> Statistics are derived from realtime <code>information_schema</code> metadata estimates.
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderGenericPage() {
        const item = NAV.find(n => n.id === window.activeNav);
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="card" style="text-align:center; padding:100px 40px;">
                    <i class="fa-solid fa-toolbox" style="font-size:3rem; color:var(--text-light); margin-bottom:20px;"></i>
                    <h2>${window.activeSub || window.activeNav.toUpperCase()} Module</h2>
                    <p style="color:var(--text-body); margin-top:10px;">Platform Administration V3.0 — Sub-module in preparation.</p>
                </div>
            </div>
        `;
    }

    function renderAddTenantForm() {
        window.currentAddStep = 1;
        mainContent.innerHTML = '<div class="page fu">Loading...</div>';
        fetch(`${window.APP_URL}/pages/super_admin/tenant-add.php`)
            .then(res => res.text())
            .then(html => {
                mainContent.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                mainContent.innerHTML = '<div class="page">Error loading form.</div>';
            });
    }

    function renderTenantsList(onlySuspended = false) {
        const title = onlySuspended ? "Suspended Institutes" : "All Institutes";
        const icon = onlySuspended ? "fa-ban" : "fa-list";
        
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px">
                        <span class="bc-sep">›</span>
                        <span class="bc-root" onclick="goNav('tenants', 'all')">Tenant Management</span>
                        <span class="bc-sep">›</span>
                        <span class="bc-cur">${title}</span>
                    </span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(0,184,148,0.1); color:var(--green);"><i class="fa-solid ${icon}"></i></div>
                        <div>
                            <div class="page-title">${title}</div>
                            <div class="page-sub">Manage platform tenants, their status, and subscription health.</div>
                        </div>
                    </div>
                </div>

                <!-- TABLE FILTERS -->
                <div class="card mb" style="padding:15px;">
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:250px; position:relative;">
                            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-light); font-size:12px;"></i>
                            <input type="text" placeholder="Search by Institute Name, ID, or Contact..." style="width:100%; border:1px solid var(--card-border); border-radius:8px; padding:10px 10px 10px 35px; font-size:13px; font-weight:500;">
                        </div>
                        <select style="border:1px solid var(--card-border); border-radius:8px; padding:0 12px; font-size:13px; font-weight:500; min-width:150px;">
                            <option>Registration: All time</option>
                            <option>Last 7 Days</option>
                            <option>Last 30 Days</option>
                        </select>
                        <select style="border:1px solid var(--card-border); border-radius:8px; padding:0 12px; font-size:13px; font-weight:500; min-width:150px;">
                            <option>All Plans</option>
                            <option>Starter</option>
                            <option>Growth</option>
                            <option>Professional</option>
                        </select>
                        <button class="btn bt" style="font-size:13px;"><i class="fa-solid fa-filter"></i> Apply</button>
                    </div>
                </div>

                <!-- DATA TABLE -->
                <div class="card">
                    <div class="tbl-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Institute Details</th>
                                    <th>Admin Contact</th>
                                    <th>Plan & Usage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tenantData.map(t => `
                                    <tr>
                                        <td>
                                            <div style="font-weight:800; color:var(--text-dark);">${t.name}</div>
                                            <div style="font-size:11px; color:var(--text-light); margin-top:2px;">ID: HL-TEN-${t.id.toString().padStart(3, '0')} · subdomain: ${t.subdomain}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; font-size:13px;">Phone: ${t.phone || 'N/A'}</div>
                                            <div style="font-size:11px; color:var(--text-light);">${t.address || ''}</div>
                                        </td>
                                        <td>
                                            <div class="tag bg-b" style="margin-bottom:4px; text-transform:capitalize;">${t.plan}</div>
                                            <div style="font-size:10px; color:var(--text-body);">SMS: ${t.sms_credits} | Students: ${t.student_limit}</div>
                                        </td>
                                        <td>
                                            <span class="tag ${t.status === 'active' ? 'bg-t' : (t.status === 'trial' ? 'bg-y' : 'bg-r')}" style="text-transform:capitalize;">${t.status}</span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:6px;">
                                                <button class="hbtn" style="width:28px; height:28px; background:var(--bg); border:1px solid var(--card-border); color:var(--green);" title="View Dashboard" onclick="window.open('http://${t.subdomain}.localhost/erp/UI', '_blank')"><i class="fa-solid fa-up-right-from-square" style="font-size:10px;"></i></button>
                                                <button class="hbtn" style="width:28px; height:28px; background:var(--bg); border:1px solid var(--card-border); color:var(--text-body);" title="Settings"><i class="fa-solid fa-gear" style="font-size:10px;"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                                ${tenantData.length === 0 ? `<tr><td colspan="5" style="text-align:center; padding:40px; color:var(--text-light);">No data found.</td></tr>` : ''}
                            </tbody>
                        </table>
                    </div>
                    <div style="padding:15px; border-top:1px solid var(--card-border); display:flex; justify-content:space-between; align-items:center;">
                        <div style="font-size:12px; color:var(--text-light);">Showing ${tenantData.length} entries</div>
                        <div style="display:flex; gap:5px;">
                            <button class="btn btn-sm bs">Previous</button>
                            <button class="btn btn-sm bt">1</button>
                            <button class="btn btn-sm bs">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPlansList() {
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px"><span class="bc-sep">›</span><span class="bc-cur">Subscription Plans</span></span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(129,65,165,0.1); color:var(--purple);"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <div class="page-title">SaaS Subscription Plans</div>
                            <div class="page-sub">Define and manage tiered pricing, feature limits, and billing cycles.</div>
                        </div>
                    </div>
                    <button class="btn bt" onclick="alert('Creating new plan...')"><i class="fa-solid fa-plus"></i> Create New Plan</button>
                </div>

                <div class="stat-grid mb">
                    <div class="stat-card card">
                        <div class="stat-lbl">Active Starter</div>
                        <div class="stat-val">42</div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-lbl">Active Growth</div>
                        <div class="stat-val">68</div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-lbl">Active Professional</div>
                        <div class="stat-val">35</div>
                    </div>
                    <div class="stat-card card">
                        <div class="stat-lbl">Total Revenue</div>
                        <div class="stat-val" style="font-size:1.2rem;">NPR 4.8L/mo</div>
                    </div>
                </div>

                <div class="card">
                    <div class="tbl-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Price (NPR)</th>
                                    <th>Student Limit</th>
                                    <th>Staff Limit</th>
                                    <th>Features</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><div style="font-weight:800;">Starter Pack</div><div style="font-size:10px; color:var(--text-light)">For small schools</div></td>
                                    <td><div style="font-weight:700;">1,500 <small>/mo</small></div></td>
                                    <td>200</td>
                                    <td>20</td>
                                    <td><div style="display:flex; gap:4px;"><span class="tag bg-t">Attendance</span><span class="tag bg-t">Exams</span></div></td>
                                    <td><span class="tag bg-t">Active</span></td>
                                    <td><button class="btn btn-sm bs">Edit</button></td>
                                </tr>
                                <tr>
                                    <td><div style="font-weight:800;">Growth Tier</div><div style="font-size:10px; color:var(--text-light)">Most Popular</div></td>
                                    <td><div style="font-weight:700;">3,500 <small>/mo</small></div></td>
                                    <td>500</td>
                                    <td>50</td>
                                    <td><div style="display:flex; gap:4px;"><span class="tag bg-t">Inventory</span><span class="tag bg-t">SMS</span></div></td>
                                    <td><span class="tag bg-t">Active</span></td>
                                    <td><button class="btn btn-sm bs">Edit</button></td>
                                </tr>
                                <tr>
                                    <td><div style="font-weight:800;">Pro Enterprise</div><div style="font-size:10px; color:var(--text-light)">Custom features</div></td>
                                    <td><div style="font-weight:700;">12,000 <small>/mo</small></div></td>
                                    <td>Unlimited</td>
                                    <td>Unlimited</td>
                                    <td><div style="display:flex; gap:4px;"><span class="tag bg-t">Whitelabel</span><span class="tag bg-t">API</span></div></td>
                                    <td><span class="tag bg-t">Active</span></td>
                                    <td><button class="btn btn-sm bs">Edit</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPaymentsList() {
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px"><span class="bc-sep">›</span><span class="bc-cur">Payment History</span></span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(0,184,148,0.1); color:var(--green);"><i class="fa-solid fa-receipt"></i></div>
                        <div>
                            <div class="page-title">Platform Transactions</div>
                            <div class="page-sub">Audit and track all subscription payments and credit purchases.</div>
                        </div>
                    </div>
                </div>

                <div class="card mb" style="padding:15px; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-weight:700; color:var(--text-dark);">Total Revenue (MTD): <span style="color:var(--green)">NPR 14.22L</span></div>
                    <button class="btn btn-sm bs"><i class="fa-solid fa-download"></i> Export CSV</button>
                </div>

                <div class="card">
                    <div class="tbl-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Institute</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${[
                                    {id: "TXN-8812", inst: "Global Academic Center", amt: "3,500", meth: "Khalti", date: "Shrawan 28", status: "Successful"},
                                    {id: "TXN-8811", inst: "Sagarmatha Public", amt: "12,000", meth: "Bank Transfer", date: "Shrawan 26", status: "Pending"},
                                    {id: "TXN-8810", inst: "Everest Nursing", amt: "1,500", meth: "eSewa", date: "Shrawan 25", status: "Successful"},
                                    {id: "TXN-8809", inst: "Lumbini Hub", amt: "3,500", meth: "Khalti", date: "Shrawan 22", status: "Successful"}
                                ].map(t => `
                                    <tr>
                                        <td><code style="font-size:11px; font-weight:700;">${t.id}</code></td>
                                        <td><div style="font-weight:700;">${t.inst}</div></td>
                                        <td><div style="font-weight:800; color:var(--text-dark);">NPR ${t.amt}</div></td>
                                        <td><div style="font-size:12px;">${t.meth}</div></td>
                                        <td style="font-size:12px; color:var(--text-light);">${t.date}</td>
                                        <td><span class="tag ${t.status==="Successful"?"bg-t":"bg-y"}">${t.status}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function renderTicketsList(onlyResolved = false) {
        const title = onlyResolved ? "Resolved History" : "Open Tickets";
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px"><span class="bc-sep">›</span><span class="bc-cur">${title}</span></span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(225,29,72,0.1); color:var(--red);"><i class="fa-solid fa-headset"></i></div>
                        <div>
                            <div class="page-title">${title}</div>
                            <div class="page-sub">Support requests and technical inquiries from platform users.</div>
                        </div>
                    </div>
                </div>

                <div class="card mb" style="padding:15px;">
                    <div style="display:flex; gap:10px;">
                        <span class="tag bg-r">Critical: 4</span>
                        <span class="tag bg-y">High: 12</span>
                        <span class="tag bg-b">Normal: 25</span>
                    </div>
                </div>

                <div class="card">
                    <div class="tbl-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Ticket Info</th>
                                    <th>Requester</th>
                                    <th>Priority</th>
                                    <th>Last Update</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${[
                                    {id: "TICK-102", sub: "Payment gateway error", user: "GAC Admin", prio: "Critical", time: "2h ago", status: "Open"},
                                    {id: "TICK-101", sub: "SMS credits not syncing", user: "Sagarmatha Principal", prio: "High", time: "4h ago", status: "Open"},
                                    {id: "TICK-98", sub: "Request for white-labeling", user: "Everest Nursing", prio: "Normal", time: "Yesterday", status: "Resolved"}
                                ].filter(t => onlyResolved ? t.status === "Resolved" : t.status !== "Resolved").map(t => `
                                    <tr>
                                        <td>
                                            <div style="font-weight:800; color:var(--text-dark);">${t.sub}</div>
                                            <div style="font-size:10px; color:var(--text-light); text-transform:uppercase; letter-spacing:0.5px;">ID: ${t.id}</div>
                                        </td>
                                        <td><div style="font-weight:600;">${t.user}</div></td>
                                        <td><span class="tag ${t.prio==="Critical"?"bg-r":t.prio==="High"?"bg-y":"bg-b"}">${t.prio}</span></td>
                                        <td style="font-size:11px; color:var(--text-light);">${t.time}</td>
                                        <td><span class="tag ${onlyResolved?"bg-t":"bg-r"}">${t.status}</span></td>
                                        <td><button class="btn btn-sm bt">Handle</button></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function renderConfigList() {
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px"><span class="bc-sep">›</span><span class="bc-cur">System Configuration</span></span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(15,23,42,0.1); color:var(--navy);"><i class="fa-solid fa-sliders"></i></div>
                        <div>
                            <div class="page-title">Platform Configuration</div>
                            <div class="page-sub">Global feature toggles, maintenance settings, and platform announcements.</div>
                        </div>
                    </div>
                </div>

                <div class="stat-grid mb">
                    <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:700;">Maintenance Mode</div>
                            <div style="font-size:11px; color:var(--text-light)">Puts platform in read-only mode for users.</div>
                        </div>
                        <div class="tag bg-r">OFF</div>
                    </div>
                    <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:700;">New Signups</div>
                            <div style="font-size:11px; color:var(--text-light)">Allow new institutes to register.</div>
                        </div>
                        <div class="tag bg-t">ON</div>
                    </div>
                </div>

                <div class="card">
                    <div class="ct"><i class="fa-solid fa-bullhorn"></i> Active Platform Announcements</div>
                    <div class="tbl-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Target Audience</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><div style="font-weight:700;">System Update V3.1 coming this Sunday</div></td>
                                    <td><span class="tag bg-b">All Tenants</span></td>
                                    <td style="font-size:12px;">2081-08-25</td>
                                    <td><span class="tag bg-t">Broadcasting</span></td>
                                    <td><button class="btn btn-sm bs">Stop</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function renderLogsList(type = 'audit') {
        const title = type === 'errors' ? "Error Logs" : (type === 'api' ? "API Request Logs" : "Audit Logs");
        const color = type === 'errors' ? "var(--red)" : "var(--green)";
        
        mainContent.innerHTML = `
            <div class="page fu">
                <div class="bc">
                    <span class="bc-root" onclick="goNav('overview')"><i class="fa-solid fa-house" style="font-size:10px"></i> Platform Admin</span>
                    <span style="display:flex;align-items:center;gap:5px"><span class="bc-sep">›</span><span class="bc-cur">${title}</span></span>
                </div>

                <div class="page-head">
                    <div class="page-title-row">
                        <div class="page-icon" style="background:rgba(15,23,42,0.1); color:var(--navy);"><i class="fa-solid fa-scroll"></i></div>
                        <div>
                            <div class="page-title">${title}</div>
                            <div class="page-sub">Security tracking, user activities, and system execution history.</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="tbl-wrap">
                        <table style="font-family:monospace; font-size:12px;">
                            <thead>
                                <tr style="text-transform:none;">
                                    <th style="width:160px;">Timestamp</th>
                                    <th>Event / Action</th>
                                    <th>Target</th>
                                    <th>Actor</th>
                                    <th>IP / Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${[
                                    {tm: "2081-08-22 14:22:15", ev: "Login Success", tgt: "Super Admin Panel", act: "Anil Shrestha", ip: "103.45.XX.XX"},
                                    {tm: "2081-08-22 14:15:02", ev: "Plan Changed", tgt: "GAC (ID-055)", act: "Auto-System", ip: "Internal"},
                                    {tm: "2081-08-22 13:58:30", ev: "Suspension lifted", tgt: "Everest Nursing", act: "Deepak S.", ip: "27.34.XX.XX"}
                                ].map(l => `
                                    <tr>
                                        <td style="color:var(--text-light)">${l.tm}</td>
                                        <td><span style="font-weight:700; color:${color}">${l.ev}</span></td>
                                        <td>${l.tgt}</td>
                                        <td><div style="font-weight:600;">${l.act}</div></td>
                                        <td style="color:var(--text-light)">${l.ip}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    // ── HELPER FUNCTIONS ──
    window.previewSubdomain = () => {
        const val = document.getElementById('subdomainInp')?.value || '—';
        const preview = document.getElementById('subdomainPreview');
        if (preview) preview.textContent = `preview: ${val}.hamrolabs.com.np`;
    };

    window.saveInstitute = () => {
        if (!validateStep(3)) return;

        showToast('Registering Institute on Hamro Labs Cloud...', 'info');
        
        // Mock server delay
        setTimeout(() => {
            showToast('Institute "'+document.getElementById('instName').value+'" created successfully!', 'success');
            setTimeout(() => {
                goNav('tenants', 'all');
            }, 1000);
        }, 1500);
    };

    window.validateStep = (step) => {
        let isValid = true;
        // Reset errors
        document.querySelectorAll('.error-msg').forEach(e => e.remove());
        document.querySelectorAll('.form-inp, .form-sel').forEach(i => i.classList.remove('error'));

        const showError = (id, msg) => {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('error');
                const err = document.createElement('div');
                err.className = 'error-msg';
                err.textContent = msg;
                el.parentElement.appendChild(err);
                isValid = false;
            }
        };

        if (step === 1) {
            const nameEn = document.getElementById('instName')?.value.trim();
            const nameNp = document.getElementById('instNameNp')?.value.trim();
            const address = document.getElementById('instAddress')?.value.trim();
            const phone = document.getElementById('instPhone')?.value.trim();
            const email = document.getElementById('instEmail')?.value.trim();

            if (!nameEn) showError('instName', 'Institute name (English) is required');
            if (!nameNp) showError('instNameNp', 'Institute name (Nepali) is required');
            if (!address) showError('instAddress', 'Full office address is required');
            if (!phone) showError('instPhone', 'Official contact number is required');
            if (!email || !email.includes('@')) showError('instEmail', 'Valid official email is required');
        }

        if (step === 2) {
            const sub = document.getElementById('subdomainInp')?.value.trim();
            const admin = document.getElementById('adminName')?.value.trim();
            const email = document.getElementById('adminEmail')?.value.trim();
            const phone = document.getElementById('adminPhone')?.value.trim();
            const pass = document.getElementById('adminPass')?.value.trim();

            if (!sub) showError('subdomainInp', 'Preferred subdomain is required');
            if (!admin) showError('adminName', 'Primary administrator name is required');
            if (!email || !email.includes('@')) showError('adminEmail', 'Valid admin email is required');
            if (!phone || phone.length < 10) showError('adminPhone', 'Valid mobile number is required');
            if (!pass || pass.length < 8) showError('adminPass', 'Password must be at least 8 characters');
        }

        if (step === 3) {
            const invoice = document.getElementById('invoiceName')?.value.trim();
            if (!invoice) showError('invoiceName', 'Official invoice name is required');
        }

        if (!isValid) {
            showToast('Please fix the errors before continuing', 'error');
        }
        return isValid;
    };

    window.currentAddStep = 1;
    window.changeStep = (dir) => {
        if (dir > 0 && !validateStep(window.currentAddStep)) return;
        
        const nextStep = window.currentAddStep + dir;
        if (nextStep < 1 || nextStep > 3) return;

        // Hide current
        const currForm = document.getElementById('formStep' + window.currentAddStep);
        const currInd = document.getElementById('stepInd' + window.currentAddStep);
        
        if (currForm) currForm.style.display = 'none';
        if (currInd) {
            currInd.classList.remove('active');
            if (dir > 0) currInd.classList.add('completed');
        }

        // Show next
        window.currentAddStep = nextStep;
        const nextForm = document.getElementById('formStep' + window.currentAddStep);
        const nextInd = document.getElementById('stepInd' + window.currentAddStep);
        
        if (nextForm) nextForm.style.display = 'block';
        if (nextInd) {
            nextInd.classList.add('active');
            nextInd.classList.remove('completed');
        }

        // Buttons
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnSubmit = document.getElementById('btnSubmit');

        if (btnPrev) btnPrev.style.display = window.currentAddStep === 1 ? 'none' : 'inline-flex';
        if (btnNext) btnNext.style.display = window.currentAddStep === 3 ? 'none' : 'inline-flex';
        if (btnSubmit) btnSubmit.style.display = window.currentAddStep === 3 ? 'inline-flex' : 'none';
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    let toastTimer;
    window.showToast = (msg, type = 'success') => {
        let t = document.getElementById('toast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'toast';
            t.className = 'toast';
            document.body.appendChild(t);
        }
        
        let icon = 'fa-circle-check';
        if (type === 'error') {
            icon = 'fa-circle-exclamation';
            t.style.background = 'var(--red)';
        } else if (type === 'info') {
            icon = 'fa-info-circle';
            t.style.background = 'var(--navy)';
        } else {
            t.style.background = 'var(--navy)';
        }

        t.innerHTML = `<i class="fa-solid ${icon}"></i> <span id="toastMsg">${msg}</span>`;
        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 4000);
    };

    // Initial Load
    if (window.activeNav === 'overview') {
        loadStats().then(() => {
            renderSidebar();
            renderPage();
            startRealtimeMetrics();
        });
    } else if (window.activeNav === 'tenants' && (window.activeSub === 'all' || window.activeSub === 'suspended')) {
        loadTenants(window.activeSub === 'suspended').then(() => {
            renderSidebar();
            renderPage();
        });
    } else if (window.activeNav === 'logs' && window.activeSub === 'db') {
        loadDatabaseInsights().then(() => {
            renderSidebar();
            renderPage();
            startRealtimeMetrics();
        });
    } else {
        renderSidebar();
        renderPage();
    }
});
