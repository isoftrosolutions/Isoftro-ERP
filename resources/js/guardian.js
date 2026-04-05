/**
 * iSoftro ERP — Guardian Dashboard
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page');
    let activeNav = initialPage || 'dashboard';
    let expanded = { attendance: true, exams: false, fee: false, notices: false, messages: false };

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── SIDEBAR TOGGLE ──
    const toggleSidebar = () => {
        if (window.innerWidth >= 1024) {
            document.body.classList.toggle('sb-collapsed');
            localStorage.setItem('_gd_sb_collapsed', document.body.classList.contains('sb-collapsed') ? '1' : '0');
        } else {
            document.body.classList.toggle('sb-active');
        }
    };
    const closeSidebar = () => document.body.classList.remove('sb-active');

    // Restore collapsed state on desktop
    if (window.innerWidth >= 1024 && localStorage.getItem('_gd_sb_collapsed') === '1') {
        document.body.classList.add('sb-collapsed');
    }

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // Delegate collapse button in footer
    document.addEventListener('click', e => {
        if (e.target.closest('.js-sidebar-toggle') && !e.target.closest('#sbToggle')) {
            e.preventDefault();
            toggleSidebar();
        }
    });

    // Handle sbClose (mobile X button now has sb-toggle class, listen by ID)
    const sbClose = document.getElementById('sbClose');
    if (sbClose) sbClose.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024 && document.body.classList.contains('sb-active')) {
            document.body.classList.remove('sb-active');
        }
    });

    // ── NAVIGATION TREE — PRD v3.0 Section 4.6 ──
    const NAV = [
        { id: "dashboard", icon: "fa-columns", label: "Dashboard", sub: null, sec: "MAIN" },
        
        { id: "attendance", icon: "fa-calendar-check", label: "Attendance", sub: [
            { id: "sum",   l: "Attendance Summary",   nav: "attendance", sub: "sum"  },
            { id: "hist",  l: "Attendance History",   nav: "attendance", sub: "hist" },
            { id: "leave", l: "Leave Applications",    nav: "attendance", sub: "leave"}
        ], sec: "MONITORING" },

        { id: "exams", icon: "fa-trophy", label: "Exam Results", sub: [
            { id: "hist",     l: "Result History",    nav: "exams", sub: "hist"     },
            { id: "trend",    l: "Performance Trend", nav: "exams", sub: "trend"    },
            { id: "analysis", l: "Subject Analysis",  nav: "exams", sub: "analysis" }
        ], sec: "MONITORING" },

        { id: "fee", icon: "fa-wallet", label: "Fee", sub: [
            { id: "dues",     l: "Outstanding Dues", nav: "fee", sub: "dues"    },
            { id: "pay",      l: "Payment History",  nav: "fee", sub: "pay"     },
            { id: "receipts", l: "Download Receipts",nav: "fee", sub: "receipts"}
        ], sec: "MONITORING" },

        { id: "notices", icon: "fa-bullhorn", label: "Notices", sub: [
            { id: "inst",  l: "Institute Announcements", nav: "notices", sub: "inst"  },
            { id: "batch", l: "My Child's Notices",      nav: "notices", sub: "batch" }
        ], sec: "MAIN" },

        { id: "messages", icon: "fa-envelope", label: "Messages", sub: [
            { id: "inbox",   l: "Inbox",          nav: "messages", sub: "inbox"   },
            { id: "contact", l: "Contact Admin",  nav: "messages", sub: "contact" }
        ], sec: "MAIN" },
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
            { id: 'dashboard', icon: 'fa-columns', label: 'Home', action: "goNav('dashboard')" },
            { id: 'attendance', icon: 'fa-calendar-check', label: 'Attendance', action: "goNav('attendance', 'sum')" },
            { id: 'exams', icon: 'fa-trophy', label: 'Results', action: "goNav('exams', 'hist')" },
            { id: 'fee', icon: 'fa-wallet', label: 'Fee', action: "goNav('fee', 'dues')" },
            { id: 'messages', icon: 'fa-envelope', label: 'Messages', action: "goNav('messages', 'inbox')" }
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
                <div class="pg-header">
                    <div class="pg-title">Welcome back, Prakash ji 👋</div>
                    <div class="pg-sub">Monitoring Rohan Sharma · Kharidar Prep Batch · Roll: HL-2081-KH-042</div>
                </div>

                <!-- QUICK ACTIONS (PRD Spec) -->
                <div class="qa-grid">
                    <button class="qa-btn" onclick="goNav('attendance', 'sum')">
                        <i class="fa-solid fa-chart-bar" style="color:var(--green)"></i> View Attendance Report
                    </button>
                    <button class="qa-btn" onclick="goNav('fee', 'receipts')">
                        <i class="fa-solid fa-file-invoice-dollar" style="color:var(--amber)"></i> Download Fee Receipt
                    </button>
                    <button class="qa-btn primary" onclick="goNav('messages', 'contact')">
                        <i class="fa-solid fa-envelope"></i> Message Institute Admin
                    </button>
                </div>

                <!-- STAT GRID -->
                <div class="sg">
                    <div class="sc green">
                        <div class="sc-ico green"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="sc-lbl">Child's Attendance</div>
                        <div class="att-ring">
                            <div class="att-pct">80%</div>
                            <div style="font-size:11px; color:var(--text-body); line-height:1.2;">Month's Presence: 20 / 25 days<br><span class="bdg bg-t">STABLE</span></div>
                        </div>
                    </div>
                    <div class="sc blue">
                        <div class="sc-ico blue"><i class="fa-solid fa-trophy"></i></div>
                        <div class="sc-lbl">Latest Exam Score</div>
                        <div class="sc-val">78%</div>
                        <div class="sc-sub"><span class="bdg bg-b">Rank #8 / 42</span> · Mock Set 4</div>
                    </div>
                    <div class="sc amber">
                        <div class="sc-ico amber"><i class="fa-solid fa-wallet"></i></div>
                        <div class="sc-lbl">Fee Dues</div>
                        <div class="sc-val">NPR 2,500</div>
                        <div class="sc-sub"><span class="bdg bg-r">Next Due: Falgun 12</span> · Installment #3</div>
                    </div>
                    <div class="sc purple">
                        <div class="sc-ico purple"><i class="fa-solid fa-bullhorn"></i></div>
                        <div class="sc-lbl">Recent Notices</div>
                        <div class="sc-val">3 New</div>
                        <div class="sc-sub">Relevant to Child's Batch</div>
                    </div>
                </div>

                <div class="g64">
                    <div>
                        <!-- EXAM RESULTS -->
                        <div class="card">
                            <div class="card-h">
                                <div class="card-t"><i class="fa-solid fa-square-poll-vertical" style="color:var(--green)"></i> Recent Exam Results</div>
                                <a href="#" class="btn-l" style="font-size:11px; color:var(--green); font-weight:700; text-decoration:none;">View All</a>
                            </div>
                            <div class="card-b">
                                <div class="ex-row">
                                    <div class="ex-ico" style="background:#EEF2FF; color:#6366F1;">📐</div>
                                    <div class="ex-info">
                                        <div class="ex-subj">General Knowledge</div>
                                        <div class="ex-meta">Mock Test #4 · July 18, 2081</div>
                                    </div>
                                    <div class="ex-score">
                                        <div class="ex-val">82/100</div>
                                        <div class="ex-rnk">Rank #4</div>
                                    </div>
                                </div>
                                <div class="ex-row">
                                    <div class="ex-ico" style="background:#ECFDF5; color:#10B981;">📜</div>
                                    <div class="ex-info">
                                        <div class="ex-subj">Lok Sewa Ain</div>
                                        <div class="ex-meta">Sectional Test · July 12, 2081</div>
                                    </div>
                                    <div class="ex-score">
                                        <div class="ex-val">74/100</div>
                                        <div class="ex-rnk">Rank #11</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RECENT MESSAGES -->
                        <div class="contact-zone">
                            <div class="contact-t"><i class="fa-solid fa-headset"></i> Contact Institute Admin</div>
                            <div class="contact-d">Direct line to the academic counselor. Type your message below to start a conversation.</div>
                            <div class="contact-box">Type your inquiry here regarding Rohan's progress...</div>
                            <button class="contact-btn">Send Message</button>
                            <div style="clear:both;"></div>
                        </div>
                    </div>

                    <div>
                        <!-- FEE STATUS -->
                        <div class="card">
                            <div class="card-h">
                                <div class="card-t"><i class="fa-solid fa-credit-card" style="color:var(--amber)"></i> Fee Status</div>
                            </div>
                            <div class="card-b">
                                <div class="fee-item">
                                    <div class="fee-info">
                                        <div class="fee-name">Monthly Installment #3</div>
                                        <div class="fee-due">Due: Falgun 12, 2081</div>
                                    </div>
                                    <div class="fee-amt pr">NPR 2,500</div>
                                </div>
                                <div class="fee-item">
                                    <div class="fee-info">
                                        <div class="fee-name">Mock Test Series Fee</div>
                                        <div class="fee-due">Paid on: Falgun 01, 2081</div>
                                    </div>
                                    <div class="fee-amt pg">PAID</div>
                                </div>
                                <button class="btn bs" style="width:100%; margin-top:16px; font-size:12px; background:var(--green); color:#fff; border:none; padding:10px; border-radius:6px; font-weight:800; cursor:pointer;">Pay Outstanding Online</button>
                            </div>
                        </div>

                        <!-- NOTICE BOARD -->
                        <div class="card">
                            <div class="card-h">
                                <div class="card-t"><i class="fa-solid fa-bullhorn" style="color:var(--purple)"></i> Recent Notices</div>
                            </div>
                            <div class="card-b" style="padding:0 18px 18px;">
                                <div class="ex-row">
                                    <div class="ex-info">
                                        <div class="ex-subj" style="font-size:12px;">Mid-term exam schedule released</div>
                                        <div class="ex-meta">Yesterday at 4:30 PM</div>
                                    </div>
                                </div>
                                <div class="ex-row">
                                    <div class="ex-info">
                                        <div class="ex-subj" style="font-size:12px;">No classes on Saturday (Falgun 09)</div>
                                        <div class="ex-meta">2 days ago</div>
                                    </div>
                                </div>
                            </div>
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
                    <i class="fa-solid fa-shield-halved" style="font-size:3rem; color:var(--text-light); margin-bottom:20px;"></i>
                    <h2>${title} Module</h2>
                    <p style="color:var(--text-body); margin-top:10px;">Guardian monitoring tools are being synced with the V3.0 production server.</p>
                </div>
            </div>
        `;
    }

    // Init
    renderSidebar();
    renderPage();
});
