/**
 * iSoftro ERP — Teacher Dashboard
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page');
    let activeNav = initialPage || 'dashboard';
    let expanded = { academics: true, exams: false, resources: false, hr: false };

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── SIDEBAR TOGGLE ──
    const toggleSidebar = () => document.body.classList.toggle('sb-active');
    const closeSidebar = () => document.body.classList.remove('sb-active');

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // ── NAVIGATION TREE ──
    const NAV = [
        { id: "dashboard", icon: "fa-house", label: "Dashboard", sub: null, sec: "MAIN" },
        { id: "academics", icon: "fa-book-open-reader", label: "Academics", sub: [
            { id: "att", l: "Mark Attendance", icon: "fa-calendar-check" },
            { id: "lp", l: "Lesson Plans", icon: "fa-list-check" },
            { id: "hw", l: "Assignments", icon: "fa-file-pen" },
            { id: "track", l: "Syllabus Tracking", icon: "fa-chart-line" }
        ], sec: "ACADEMY" },
        { id: "exams", icon: "fa-file-contract", label: "Exams & Results", sub: [
            { id: "qb", l: "My Question Bank", icon: "fa-database" },
            { id: "marks", l: "Mark Entry", icon: "fa-pen-to-square" },
            { id: "results", l: "Result View", icon: "fa-square-poll-vertical" }
        ], sec: "ACADEMY" },
        { id: "resources", icon: "fa-folder-open", label: "Library Resources", sub: [
            { id: "notes", l: "Study Materials", icon: "fa-file-pdf" },
            { id: "vids", l: "Video Lectures", icon: "fa-video" }
        ], sec: "ACADEMY" },
        { id: "hr", icon: "fa-user-gear", label: "Human Resources", sub: [
            { id: "leave", l: "Leave Application", icon: "fa-calendar-plus" },
            { id: "salary", l: "Salary Slips", icon: "fa-wallet" },
            { id: "sched", l: "My Schedule", icon: "fa-clock" }
        ], sec: "PERSONAL" },
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
            html += `<div class="sb-sec"><div class="sb-sec-lbl">${sec}</div>`;

            NAV.filter(n => n.sec === sec).forEach(nav => {
                const isActive = activeNav === nav.id || activeNav.startsWith(nav.id + '-');
                const isExp = expanded[nav.id];

                html += `<div class="sb-item">
                    <button class="nb-btn ${isActive ? 'active' : ''}" onclick="${nav.sub ? `toggleExp('${nav.id}')` : `goNav('${nav.id}')`}">
                        <i class="fa-solid ${nav.icon}"></i>
                        <span style="flex:1; text-align:left;">${nav.label}</span>
                        ${nav.sub ? `<i class="fa-solid fa-chevron-right" style="font-size:10px; transition:0.2s; ${isExp ? 'transform:rotate(90deg)' : ''}"></i>` : ''}
                    </button>`;

                if (nav.sub && isExp) {
                    html += `<div class="sub-menu">`;
                    nav.sub.forEach(s => {
                        const isSubActive = activeNav === `${nav.id}-${s.id}`;
                        html += `<button class="sub-btn ${isSubActive ? 'active' : ''}" onclick="goNav('${nav.id}', '${s.id}')">
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
                    <i class="fa-solid fa-bolt"></i>
                    <span>Instant Install</span>
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
            { id: 'dashboard', icon: 'fa-house', label: 'Home', action: "goNav('dashboard')" },
            { id: 'academics', icon: 'fa-book-open-reader', label: 'Academics', action: "goNav('academics', 'att')" },
            { id: 'exams', icon: 'fa-file-contract', label: 'Exams', action: "goNav('exams', 'marks')" },
            { id: 'resources', icon: 'fa-folder-open', label: 'Resources', action: "goNav('resources', 'notes')" },
            { id: 'hr', icon: 'fa-user-gear', label: 'Personal', action: "goNav('hr', 'leave')" }
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
                    <div class="pg-title">Greetings, Ramesh Sir 👋</div>
                    <div style="font-size:12px; color:var(--text-body); margin-top:4px;">
                        Pioneer Loksewa Institute · Today is Shrawan 22, 2081 (BS)
                    </div>
                </div>

                <!-- STAT GRID -->
                <div class="sg">
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-chalkboard-user"></i></div><div class="bdg bg-t">3 Classes</div></div>
                        <div class="sc-val">3</div>
                        <div class="sc-lbl">Today's Lectures</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-green"><i class="fa-solid fa-user-check"></i></div><div class="bdg bg-t">Avg 82%</div></div>
                        <div class="sc-val">82%</div>
                        <div class="sc-lbl">Current Attendance</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-file-pen"></i></div><div class="bdg bg-r">12 pending</div></div>
                        <div class="sc-val">12</div>
                        <div class="sc-lbl">Pending Assignments</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-database"></i></div><div class="bdg bg-t">+4 this week</div></div>
                        <div class="sc-val">48</div>
                        <div class="sc-lbl">Submitted Questions</div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="qa-grid">
                    <button class="qa-btn" onclick="goNav('academics', 'att')">
                        <div class="qa-ico" style="color:var(--green)"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="qa-lbl">Mark Attendance</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('resources', 'notes')">
                        <div class="qa-ico" style="color:var(--blue)"><i class="fa-solid fa-upload"></i></div>
                        <div class="qa-lbl">Upload Notes</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('exams', 'qb')">
                        <div class="qa-ico" style="color:var(--purple)"><i class="fa-solid fa-plus"></i></div>
                        <div class="qa-lbl">Add Question</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('hr', 'leave')">
                        <div class="qa-ico" style="color:var(--red)"><i class="fa-solid fa-calendar-plus"></i></div>
                        <div class="qa-lbl">Apply Leave</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('hr', 'salary')">
                        <div class="qa-ico" style="color:var(--amber)"><i class="fa-solid fa-wallet"></i></div>
                        <div class="qa-lbl">Salary Slips</div>
                    </button>
                </div>

                <div class="g65">
                    <!-- CLASS SCHEDULE -->
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-clock"></i> Today's Schedule</div>
                        <div class="cls-card ongoing">
                            <div class="cls-time">07:00 AM</div>
                            <div class="cls-info">
                                <span class="cls-subj">General Knowledge</span>
                                <span class="cls-batch">Kharidar Batch A · Room 201</span>
                            </div>
                            <div class="bdg bg-t">ONGOING</div>
                        </div>
                        <div class="cls-card upcoming">
                            <div class="cls-time">10:00 AM</div>
                            <div class="cls-info">
                                <span class="cls-subj">Lok Sewa Ain</span>
                                <span class="cls-batch">Section Officer B · Online</span>
                            </div>
                            <div class="bdg bg-t">UPCOMING</div>
                        </div>
                        <div class="cls-card">
                            <div class="cls-time">05:00 PM</div>
                            <div class="cls-info">
                                <span class="cls-subj">Current Affairs</span>
                                <span class="cls-batch">Nayab Subba Eve · Room 104</span>
                            </div>
                            <div class="bdg">LATER</div>
                        </div>
                    </div>

                    <!-- SYLLABUS & LEAVE -->
                    <div>
                        <div class="card mb-20">
                            <div class="ct"><i class="fa-solid fa-chart-line"></i> Syllabus Coverage</div>
                            <div class="pr-row">
                                <div class="pr-lbl">Pol. History</div>
                                <div class="pr-tr"><div class="pr-fi" style="width:100%; background:var(--green);"></div></div>
                                <div style="font-size:10px; color:var(--text-light);">100%</div>
                            </div>
                            <div class="pr-row">
                                <div class="pr-lbl">Constitution</div>
                                <div class="pr-tr"><div class="pr-fi" style="width:75%; background:var(--blue);"></div></div>
                                <div style="font-size:10px; color:var(--text-light);">75%</div>
                            </div>
                            <div class="pr-row">
                                <div class="pr-lbl">Public Admin</div>
                                <div class="pr-tr"><div class="pr-fi" style="width:40%; background:var(--amber);"></div></div>
                                <div style="font-size:10px; color:var(--text-light);">40%</div>
                            </div>
                            <button class="btn bs" style="width:100%; justify-content:center; margin-top:10px; font-size:11px;">Update Progress</button>
                        </div>

                        <div class="card">
                            <div class="ct"><i class="fa-solid fa-user-clock"></i> Leave Balance</div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                <span style="font-size:12px; color:var(--text-body);">Casual Leaves</span>
                                <span style="font-size:12px; font-weight:700;">4/12 used</span>
                            </div>
                            <div class="prog-t"><div class="prog-f" style="width:33%; background:var(--blue);"></div></div>
                            <div style="display:flex; justify-content:space-between; margin-top:12px;">
                                <span style="font-size:12px; color:var(--text-body);">Sick Leaves</span>
                                <span style="font-size:12px; font-weight:700;">1/8 used</span>
                            </div>
                            <div class="prog-t"><div class="prog-f" style="width:12%; background:var(--red);"></div></div>
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
                    <p style="color:var(--text-body); margin-top:10px;">Teacher specialized modules are being integrated for the V3.0 portal.</p>
                </div>
            </div>
        `;
    }

    // Init
    renderSidebar();
    renderPage();
});
