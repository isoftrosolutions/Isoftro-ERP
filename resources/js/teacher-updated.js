/**
 * Hamro ERP — Teacher Dashboard
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page');
    let activeNav = initialPage || 'dashboard';
    let expanded = { classes: true, attendance: true, lms: true, assignments: true, exams: true, profile: true };

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbClose = document.getElementById('sbClose');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── SIDEBAR TOGGLE (matches Super Admin pattern) ──
    const toggleSidebar = () => {
        if (window.innerWidth >= 1024) {
            document.body.classList.toggle('sb-collapsed');
        } else {
            document.body.classList.toggle('sb-active');
        }
    };
    const closeSidebar = () => document.body.classList.remove('sb-active');

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbClose) sbClose.addEventListener('click', closeSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // ── NAVIGATION TREE ──
    const NAV = [
        { id: "dashboard", icon: "fa-house", label: "Dashboard", sub: null, sec: "MAIN" },
        { id: "classes", icon: "fa-calendar-days", label: "My Classes", sub: [
            { id: "today", l: "Today's Schedule", icon: "fa-calendar-day" },
            { id: "timetable", l: "Full Timetable", icon: "fa-calendar-week" },
            { id: "substitute", l: "Substitute Requests", icon: "fa-hand-holding-hand" }
        ], sec: "ACADEMIC" },
        { id: "attendance", icon: "fa-check-circle", label: "Attendance", sub: [
            { id: "mark", l: "Mark Attendance", icon: "fa-calendar-check" },
            { id: "history", l: "Attendance History", icon: "fa-clock-rotate-left" },
            { id: "leave-approvals", l: "Leave Approvals", icon: "fa-file-signature" }
        ], sec: "ACADEMIC" },
        { id: "lms", icon: "fa-book-open", label: "LMS", sub: [
            { id: "materials", l: "Study Materials", icon: "fa-file-pdf" },
            { id: "upload-video", l: "Upload Video", icon: "fa-video" },
            { id: "lesson-plans", l: "Lesson Plans", icon: "fa-list-check" },
            { id: "online-links", l: "Online Class Links", icon: "fa-link" }
        ], sec: "ACADEMIC" },
        { id: "assignments", icon: "fa-file-lines", label: "Assignments", sub: [
            { id: "create", l: "Create Assignment", icon: "fa-plus" },
            { id: "pending", l: "Pending Evaluations", icon: "fa-pen-to-square" },
            { id: "graded", l: "Graded Submissions", icon: "fa-check-double" }
        ], sec: "ACADEMIC" },
        { id: "exams", icon: "fa-file-contract", label: "Exams & Results", sub: [
            { id: "qb", l: "Question Bank (My Questions)", icon: "fa-database" },
            { id: "create-exam", l: "Create Exam", icon: "fa-plus-circle" },
            { id: "results", l: "Exam Results", icon: "fa-square-poll-vertical" },
            { id: "analytics", l: "Performance Analytics", icon: "fa-chart-line" }
        ], sec: "ACADEMIC" },
        { id: "profile", icon: "fa-user", label: "My Profile", sub: [
            { id: "personal", l: "Personal Details", icon: "fa-id-card" },
            { id: "qualifications", l: "Qualifications", icon: "fa-graduation-cap" },
            { id: "salary-slips", l: "Salary Slips", icon: "fa-wallet" },
            { id: "leave-history", l: "Leave History", icon: "fa-calendar-days" }
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

        // PWA Install button removed
        /*
        html += `
            <div class="sb-install-box">
                <button class="install-btn-trigger" onclick="openPwaModal()">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Instant Install</span>
                </button>
            </div>
        `;
        */

        sbBody.innerHTML = html;
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
                    <div class="pg-title">Namaste, Ramesh Sir 👋</div>
                    <div style="font-size:12px; color:var(--text-body); margin-top:4px;">
                        Pioneer Loksewa Institute · Today is Shrawan 22, 2081 (BS)
                    </div>
                </div>

                <!-- STAT GRID -->
                <div class="sg">
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-green"><i class="fa-solid fa-chalkboard-user"></i></div><div class="bdg bg-t">3 Today</div></div>
                        <div class="sc-val">3</div>
                        <div class="sc-lbl">Today's Lectures</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-green"><i class="fa-solid fa-user-check"></i></div><div class="bdg bg-t"><i class="fa-solid fa-caret-up"></i> 2%</div></div>
                        <div class="sc-val">82%</div>
                        <div class="sc-lbl">This Week's Attendance</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-file-pen"></i></div><div class="bdg bg-r">12 pending</div></div>
                        <div class="sc-val">12</div>
                        <div class="sc-lbl">Pending Grading</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-award"></i></div><div class="bdg bg-t">Last Exam</div></div>
                        <div class="sc-val">74.5</div>
                        <div class="sc-lbl">Avg. Exam Score</div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="qa-grid">
                    <button class="qa-btn" onclick="goNav('attendance', 'mark')">
                        <div class="qa-ico" style="color:var(--green)"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="qa-lbl">Mark Attendance</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('lms', 'materials')">
                        <div class="qa-ico" style="color:var(--teal)"><i class="fa-solid fa-file-arrow-up"></i></div>
                        <div class="qa-lbl">Upload Material</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('assignments', 'create')">
                        <div class="qa-ico" style="color:var(--amber)"><i class="fa-solid fa-plus-circle"></i></div>
                        <div class="qa-lbl">Create Assignment</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('exams', 'qb')">
                        <div class="qa-ico" style="color:var(--purple)"><i class="fa-solid fa-database"></i></div>
                        <div class="qa-lbl">Add Question</div>
                    </button>
                    <button class="qa-btn" onclick="goNav('profile', 'salary-slips')">
                        <div class="qa-ico" style="color:var(--red)"><i class="fa-solid fa-wallet"></i></div>
                        <div class="qa-lbl">Download Slip</div>
                    </button>
                </div>

                <div class="g65">
                    <!-- CLASS SCHEDULE -->
                    <div>
                        <div class="card mb-20">
                            <div class="ct"><i class="fa-solid fa-clock"></i> Today's Classes</div>
                            <div class="cls-card ongoing">
                                <div class="cls-time">07:00 AM</div>
                                <div class="cls-info">
                                    <span class="cls-subj">General Knowledge</span>
                                    <span class="cls-batch">Kharidar Batch A · Room 201</span>
                                </div>
                                <div class="bdg bg-green">ATTENDANCE MARKED</div>
                            </div>
                            <div class="cls-card upcoming">
                                <div class="cls-time">10:00 AM</div>
                                <div class="cls-info">
                                    <span class="cls-subj">Lok Sewa Ain</span>
                                    <span class="cls-batch">Section Officer B · Online</span>
                                </div>
                                <div class="bdg bg-amber">PENDING</div>
                            </div>
                            <div class="cls-card">
                                <div class="cls-time">05:00 PM</div>
                                <div class="cls-info">
                                    <span class="cls-subj">Current Affairs</span>
                                    <span class="cls-batch">Nayab Subba Eve · Room 104</span>
                                </div>
                                <div class="bdg bg-t">NOT STARTED</div>
                            </div>
                        </div>

                        <!-- ANNOUNCEMENTS -->
                        <div class="card">
                            <div class="ct"><i class="fa-solid fa-bullhorn"></i> Announcements</div>
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <div style="display:flex; gap:12px; align-items:start; padding-bottom:12px; border-bottom:1px solid var(--card-border);">
                                    <div style="background:var(--green-lt); color:var(--green); padding:8px; border-radius:8px; font-size:14px;"><i class="fa-solid fa-megaphone"></i></div>
                                    <div style="flex:1;">
                                        <div style="font-size:13px; font-weight:600; color:var(--text-dark);">Staff Meeting at 4 PM Today</div>
                                        <div style="font-size:11px; color:var(--text-light); margin-top:2px;">Institute-wide · Just now</div>
                                    </div>
                                </div>
                                <div style="display:flex; gap:12px; align-items:start; padding-bottom:12px; border-bottom:1px solid var(--card-border);">
                                    <div style="background:var(--amber-lt); color:var(--amber); padding:8px; border-radius:8px; font-size:14px;"><i class="fa-solid fa-calendar-exclamation"></i></div>
                                    <div style="flex:1;">
                                        <div style="font-size:13px; font-weight:600; color:var(--text-dark);">Friday Exam Schedule Updated</div>
                                        <div style="font-size:11px; color:var(--text-light); margin-top:2px;">Kharidar Batch A · 2 hours ago</div>
                                    </div>
                                </div>
                                <div style="display:flex; gap:12px; align-items:start;">
                                    <div style="background:var(--green-lt); color:var(--green); padding:8px; border-radius:8px; font-size:14px;"><i class="fa-solid fa-circle-check"></i></div>
                                    <div style="flex:1;">
                                        <div style="font-size:13px; font-weight:600; color:var(--text-dark);">New Study Material Guidelines</div>
                                        <div style="font-size:11px; color:var(--text-light); margin-top:2px;">Admin · Yesterday</div>
                                    </div>
                                </div>
                            </div>
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
                                <div class="pr-tr"><div class="pr-fi" style="width:75%; background:var(--teal);"></div></div>
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
                            <div class="prog-t"><div class="prog-f" style="width:33%; background:var(--green);"></div></div>
                            <div style="display:flex; justify-content:space-between; margin-top:12px; margin-bottom:10px;">
                                <span style="font-size:12px; color:var(--text-body);">Sick Leaves</span>
                                <span style="font-size:12px; font-weight:700;">1/8 used</span>
                            </div>
                            <div class="prog-t"><div class="prog-f" style="width:12%; background:var(--red);"></div></div>
                            
                            <div style="margin-top:20px; padding-top:15px; border-top:1px solid var(--card-border);">
                                <div style="font-size:11px; font-weight:600; color:var(--text-light); text-transform:uppercase; margin-bottom:8px;">Recent Status</div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:12px; color:var(--text-dark);">Medical Leave (2 days)</span>
                                    <span class="bdg bg-amber" style="font-size:9px;">PENDING</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderGenericPage() {
        let label = 'Module';
        const dashIdx = activeNav.indexOf('-');
        const id    = dashIdx === -1 ? activeNav : activeNav.slice(0, dashIdx);
        const subId = dashIdx === -1 ? null       : activeNav.slice(dashIdx + 1);
        const navItem = NAV.find(n => n.id === id);
        if (navItem) {
            if (subId && navItem.sub) {
                const subItem = navItem.sub.find(s => s.id === subId);
                if (subItem) label = subItem.l;
            } else {
                label = navItem.label;
            }
        }

        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="card" style="text-align:center; padding:80px 40px;">
                    <i class="fa-solid fa-person-digging" style="font-size:3rem; color:var(--text-light); margin-bottom:20px;"></i>
                    <h2>${label} Module</h2>
                    <p style="color:var(--text-body); margin-top:10px;">Teacher specialized modules are being integrated for the V3.0 portal.</p>
                </div>
            </div>
        `;
    }

    // Init
    renderSidebar();
    renderPage();
});
