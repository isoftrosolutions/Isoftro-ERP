/**
 * iSoftro ERP — Front Desk Operator
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page');
    let activeNav = initialPage || 'dashboard';
    let expanded = { inquiries: true, admissions: true, fee: true, library: false, notifs: false };

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbClose = document.getElementById('sbClose');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── SIDEBAR TOGGLE ──
    const toggleSidebar = () => {
        if (window.innerWidth >= 1024) {
            document.body.classList.toggle('sb-collapsed');
            localStorage.setItem('_fd_sb_collapsed', document.body.classList.contains('sb-collapsed') ? '1' : '0');
        } else {
            document.body.classList.toggle('sb-active');
        }
    };
    const closeSidebar = () => document.body.classList.remove('sb-active');

    // Restore collapsed state on desktop
    if (window.innerWidth >= 1024 && localStorage.getItem('_fd_sb_collapsed') === '1') {
        document.body.classList.add('sb-collapsed');
    }

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbClose) sbClose.addEventListener('click', closeSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // Delegate collapse button in sidebar footer
    document.addEventListener('click', e => {
        if (e.target.closest('.js-sidebar-toggle') && !e.target.closest('#sbToggle')) {
            e.preventDefault();
            toggleSidebar();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024 && document.body.classList.contains('sb-active')) {
            document.body.classList.remove('sb-active');
        }
    });

    // ── NAVIGATION TREE ──
    const NAV = [
        { id: "dashboard", icon: "fa-house", label: "Dashboard", sub: null, sec: "MAIN" },
        { id: "inquiries", icon: "fa-magnifying-glass", label: "Inquiries", sub: [
            { id: "inq-list", l: "Inquiry List", icon: "fa-list" },
            { id: "inq-add", l: "Add New Inquiry", icon: "fa-plus" },
            { id: "inq-rem", l: "Follow-Up Reminders", icon: "fa-bell" },
            { id: "inq-rep", l: "Conversion Report", icon: "fa-chart-pie" }
        ], sec: "OPERATIONS" },
        { id: "admissions", icon: "fa-user-graduate", label: "Admissions", sub: [
            { id: "adm-form", l: "New Admission Form", icon: "fa-file-signature" },
            { id: "adm-all", l: "All Students", icon: "fa-users" },
            { id: "adm-id", l: "ID Card Generator", icon: "fa-id-card" },
            { id: "adm-doc", l: "Document Verification", icon: "fa-clipboard-check" }
        ], sec: "OPERATIONS" },
        { id: "fee", icon: "fa-money-bill-wave", label: "Fee Collection", sub: [
            { id: "fee-coll", l: "Collect Payment", icon: "fa-hand-holding-dollar" },
            { id: "fee-out", l: "Outstanding Dues", icon: "fa-clock" },
            { id: "fee-rcp", l: "Receipt History", icon: "fa-receipt" },
            { id: "fee-sum", l: "Daily Collection Summary", icon: "fa-table-list" }
        ], sec: "OPERATIONS" },
        { id: "library", icon: "fa-book", label: "Library", sub: [
            { id: "lib-issue", l: "Issue Book", icon: "fa-book-open" },
            { id: "lib-return", l: "Return Book", icon: "fa-arrow-rotate-left" },
            { id: "lib-overdue", l: "Overdue List", icon: "fa-triangle-exclamation" }
        ], sec: "OPERATIONS" },
        { id: "notifs", icon: "fa-paper-plane", label: "Notifications", sub: [
            { id: "sms-send", l: "Send SMS", icon: "fa-message" },
            { id: "email-send", l: "Send Email", icon: "fa-envelope" },
            { id: "notif-log", l: "Notification Log", icon: "fa-list-ul" }
        ], sec: "COMMUNICATION" },
    ];

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subActive = null) => {
        activeNav = subActive ? `${id}-${subActive}` : id;

        // Update URL via pushState
        const url = new URL(window.location);
        url.searchParams.set('page', activeNav);
        window.history.pushState({ activeNav }, '', url);

        if (window.innerWidth < 1024) closeSidebar();
        renderSidebar();
        renderPage();
    };

    // Handle Browser Back/Forward
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.activeNav) {
            activeNav = e.state.activeNav;
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            activeNav = urlParams.get('page') || 'dashboard';
        }
        renderSidebar();
        renderPage();
    });

    window.toggleExp = (id) => {
        expanded[id] = !expanded[id];
        renderSidebar();
    };

    function renderSidebar() {
        const sections = [...new Set(NAV.map(n => n.sec))];
        let html = '';

        sections.forEach(sec => {
            html += `<div class="sidebar-section" data-sec="${sec}">
                <div class="sidebar-section-label">${sec}</div>`;

            NAV.filter(n => n.sec === sec).forEach(nav => {
                const isActive = activeNav === nav.id || activeNav.startsWith(nav.id + '-');
                const isExp = expanded[nav.id];

                if (nav.sub) {
                    html += `<div class="sidebar-item-group">
                        <button class="sidebar-item${isActive ? ' active' : ''}"
                                onclick="toggleExp('${nav.id}')"
                                aria-expanded="${isExp ? 'true' : 'false'}">
                            <i class="fa-solid ${nav.icon} sidebar-item-icon"></i>
                            <span class="sidebar-item-label">${nav.label}</span>
                            <i class="fa-solid fa-chevron-right sidebar-item-chevron${isExp ? ' open' : ''}"></i>
                        </button>
                        <div class="sidebar-submenu${isExp ? ' open' : ''}" id="submenu-${nav.id}">
                            <div>`;
                    nav.sub.forEach(s => {
                        const isSubActive = activeNav === `${nav.id}-${s.id}`;
                        html += `<button class="sidebar-subitem${isSubActive ? ' active' : ''}"
                                    onclick="goNav('${nav.id}', '${s.id}')">
                            ${s.icon ? `<i class="fa-solid ${s.icon} sidebar-subitem-icon"></i>` : ''}
                            <span class="sidebar-subitem-label">${s.l}</span>
                        </button>`;
                    });
                    html += `</div></div></div>`;
                } else {
                    html += `<button class="sidebar-item${isActive ? ' active' : ''}"
                                onclick="goNav('${nav.id}')">
                        <i class="fa-solid ${nav.icon} sidebar-item-icon"></i>
                        <span class="sidebar-item-label">${nav.label}</span>
                    </button>`;
                }
            });
            html += `</div>`;
        });

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
            { id: 'dashboard', icon: 'fa-house', label: 'Home', action: "goNav('dashboard')" },
            { id: 'inquiries', icon: 'fa-magnifying-glass', label: 'Inquiries', action: "goNav('inquiries', 'inq-list')" },
            { id: 'admissions', icon: 'fa-user-graduate', label: 'Admissions', action: "goNav('admissions', 'adm-all')" },
            { id: 'fee', icon: 'fa-money-bill-wave', label: 'Fee', action: "goNav('fee', 'fee-coll')" },
            { id: 'notifs', icon: 'fa-paper-plane', label: 'Notices', action: "goNav('notifs', 'sms-send')" }
        ];

        let html = '';
        items.forEach(item => {
            const isActive = activeNav === item.id || (typeof activeNav === 'string' && activeNav.startsWith(item.id + '-'));
            html += `<button class="mb-nav-btn ${isActive ? 'active' : ''}" onclick="${item.action}">
                <i class="fa-solid ${item.icon}"></i>
                <span>${item.label}</span>
            </button>`;
        });
        bNav.innerHTML = html;
    }

    // ── PAGE RENDERING ──
    function renderPage() {
        mainContent.innerHTML = '<div class="pg fu">Loading...</div>';

        if (activeNav === 'dashboard') {
            renderDashboard();
        } else {
            renderGenericPage();
        }
    }

    function renderDashboard() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="pg-head">
                    <div class="pg-title">Front Desk Dashboard</div>
                    <div class="pg-sub">Pioneer Loksewa Institute · Today's operations overview</div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="qa-grid">
                    <button class="qa-btn" onclick="goNav('admissions', 'adm-form')">
                        <div class="qa-ico ic-blue"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="qa-lbl">New Admission</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('fee', 'fee-coll')">
                        <div class="qa-ico ic-teal"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        <div class="qa-lbl">Collect Fee</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('library', 'lib-issue')">
                        <div class="qa-ico ic-purple"><i class="fa-solid fa-book"></i></div>
                        <div class="qa-lbl">Issue Book</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('notifs', 'sms-send')">
                        <div class="qa-ico ic-amber"><i class="fa-solid fa-comment-sms"></i></div>
                        <div class="qa-lbl">Send SMS</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('admissions', 'adm-id')">
                        <div class="qa-ico ic-navy"><i class="fa-solid fa-id-card"></i></div>
                        <div class="qa-lbl">Generate ID Card</div>
                    </button>
                </div>

                <!-- STAT GRID -->
                <div class="sg">
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-graduation-cap"></i></div><div class="bdg bg-t">+3 today</div></div>
                        <div class="sc-val">3</div>
                        <div class="sc-lbl">Today's Admissions</div>
                        <div class="sc-delta">2 docs pending verification</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-money-bill-trend-up"></i></div><div class="bdg bg-t">↑ vs yesterday</div></div>
                        <div class="sc-val">NPR 9,500</div>
                        <div class="sc-lbl">Today's Fee Collection</div>
                        <div class="sc-delta">Cash 6.5K · Bank 3.0K</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-phone-volume"></i></div><div class="bdg bg-y">Action needed</div></div>
                        <div class="sc-val">4</div>
                        <div class="sc-lbl">Pending Inquiries</div>
                        <div class="sc-delta">2 follow-ups due today</div>
                    </div>
                </div>

                <div class="sg">
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-red"><i class="fa-solid fa-circle-exclamation"></i></div><div class="bdg bg-r">Overdue</div></div>
                        <div class="sc-val">NPR 12.2K</div>
                        <div class="sc-lbl">Outstanding Dues</div>
                        <div class="sc-delta">4 students overdue</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-clipboard-check"></i></div><div class="bdg bg-y">Pending</div></div>
                        <div class="sc-val">3</div>
                        <div class="sc-lbl">Docs Pending Verification</div>
                        <div class="sc-delta">Citizenship, Photos...</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="bdg bg-r">Overdue</div></div>
                        <div class="sc-val">2</div>
                        <div class="sc-lbl">Library Books Overdue</div>
                        <div class="sc-delta">Total Fine: NPR 85</div>
                    </div>
                </div>

                <div class="g65">
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-history"></i> Today's Admissions Activity</div>
                        <div class="ai">
                            <div class="ad" style="background:#f1f5f9; color:#475569">S</div>
                            <div class="nm-row">
                                <div><div class="nm">Suman Karki</div><div class="sub-txt">Kharidar Prep · 9:14 AM</div></div>
                                <span class="pill py">pending</span>
                            </div>
                        </div>
                        <div class="ai">
                            <div class="ad" style="background:#f0fdf4; color:#16a34a">A</div>
                            <div class="nm-row">
                                <div><div class="nm">Anita Thapa</div><div class="sub-txt">Nayab Subba Prep · 10:38 AM</div></div>
                                <span class="pill pg">verified</span>
                            </div>
                        </div>
                        <div class="ai">
                            <div class="ad" style="background:#f1f5f9; color:#475569">B</div>
                            <div class="nm-row">
                                <div><div class="nm">Bikash Shrestha</div><div class="sub-txt">Section Officer · 11:55 AM</div></div>
                                <span class="pill py">pending</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-coins"></i> Collection Summary</div>
                        <div class="col-item">
                            <div class="col-ico">💵</div>
                            <div class="col-lbl">Cash Collected</div>
                            <div class="col-val" style="color:#16a34a">NPR 6,500</div>
                        </div>
                        <div class="col-item">
                            <div class="col-ico">🏦</div>
                            <div class="col-lbl">Bank Transfer</div>
                            <div class="col-val" style="color:#3b82f6">NPR 3,000</div>
                        </div>
                        <div style="height:1px; background:var(--card-border); margin:15px 0;"></div>
                        <div class="col-item" style="background:#f0fdf4; border-color:#dcfce7">
                            <div class="col-ico">✅</div>
                            <div class="col-lbl" style="color:#16a34a; font-weight:700;">Total Today</div>
                            <div class="col-val" style="color:#16a34a">NPR 9,500</div>
                        </div>
                        <button class="btn bs" style="width:100%; justify-content:center; margin-top:10px;"><i class="fa-solid fa-print"></i> Print Daily Summary</button>
                    </div>
                </div>

                <!-- DAILY WORKFLOW -->
                <div class="card" style="margin-top:20px;">
                    <div class="ct"><i class="fa-solid fa-list-check"></i> Daily Administrator Workflow</div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:15px;">
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; border:1px solid var(--card-border); background:#f8fafc;">
                            <div style="width:28px; height:28px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">1</div>
                            <div style="font-size:13px; color:var(--text-body);"><span style="font-weight:700; color:var(--text-dark);">Check pending inquiries</span> and make follow-up calls / send SMS</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; border:1px solid var(--card-border); background:#f8fafc;">
                            <div style="width:28px; height:28px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">2</div>
                            <div style="font-size:13px; color:var(--text-body);"><span style="font-weight:700; color:var(--text-dark);">Process walk-in admissions</span>: fill form, collect docs, generate roll no.</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; border:1px solid var(--card-border); background:#f8fafc;">
                            <div style="width:28px; height:28px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">3</div>
                            <div style="font-size:13px; color:var(--text-body);"><span style="font-weight:700; color:var(--text-dark);">Collect fee payments</span>, generate and print receipts</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; border:1px solid var(--card-border); background:#f8fafc;">
                            <div style="width:28px; height:28px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">4</div>
                            <div style="font-size:13px; color:var(--text-body);"><span style="font-weight:700; color:var(--text-dark);">Verify and update document status</span> for pending students</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; border:1px solid var(--card-border); background:#f8fafc;">
                            <div style="width:28px; height:28px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">5</div>
                            <div style="font-size:13px; color:var(--text-body);"><span style="font-weight:700; color:var(--text-dark);">Handle library book issues</span> and returns, calculate fines</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:10px; border:1px solid var(--card-border); background:#f8fafc;">
                            <div style="width:28px; height:28px; border-radius:50%; background:var(--green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">6</div>
                            <div style="font-size:13px; color:var(--text-body);"><span style="font-weight:700; color:var(--text-dark);">Print daily collection summary</span> report at end of day</div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    }

    function renderGenericPage() {
        const title = activeNav.split('-').map(s=>s.charAt(0).toUpperCase()+s.slice(1)).join(' ');
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="card" style="text-align:center; padding:80px 40px;">
                    <i class="fa-solid fa-person-digging" style="font-size:3rem; color:var(--text-light); margin-bottom:20px;"></i>
                    <h2>${title} Module</h2>
                    <p style="color:var(--text-body); margin-top:10px;">Module implementation in progress to match V3.0 specs.</p>
                </div>
            </div>
        `;
    }

    // Init
    renderSidebar();
    renderPage();
});
