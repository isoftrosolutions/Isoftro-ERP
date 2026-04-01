/**
 * iSoftro ERP — Teacher Dashboard
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
            if (sec === 'PERSONAL') {
                html += `<div class="sb-divider"></div>`;
            }
            html += `<div class="sb-sec"><div class="sb-sec-lbl">${sec}</div>`;

            NAV.filter(n => n.sec === sec).forEach(nav => {
                const isActive = activeNav === nav.id || activeNav.startsWith(nav.id + '-');
                const isExp = expanded[nav.id];

                html += `<div class="sb-item">
                    <button class="nb-btn ${isActive ? 'active' : ''}" onclick="${nav.sub ? `toggleExp('${nav.id}')` : `goNav('${nav.id}')`}">
                        <i class="fa-solid ${nav.icon}"></i>
                        <span class="sb-lbl">${nav.label}</span>
                        ${nav.sub ? `<i class="fa-solid fa-chevron-right sb-chev" style="font-size:10px; transition:0.2s; ${isExp ? 'transform:rotate(90deg)' : ''}"></i>` : ''}
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

        // Add Install Button
        html += `
            <div style="padding: 10px 24px;">
                <button onclick="if(window.openPwaModal) openPwaModal()" style="width:100%; display:flex; align-items:center; gap:10px; padding:10px 16px; background:#f0fdf4; border:1px dashed var(--green); border-radius:10px; color:var(--green); font-size:12px; font-weight:700; cursor:pointer; transition:0.2s;">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Instant Install</span>
                </button>
            </div>
        `;

        // Add Sidebar Footer (same as front-desk)
        const uName = document.querySelector('#userChip span:first-child')?.innerText || 'Teacher';
        const uInitials = document.querySelector('#userChip .u-av')?.innerText || 'T';
        
        html += `
            <div style="flex:1"></div>
            <div class="sb-footer">
                <div class="sb-user-av">${uInitials}</div>
                <div style="overflow:hidden;">
                    <div class="sb-user-name" style="white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">${uName}</div>
                    <div class="sb-user-role">Teacher Portal</div>
                </div>
                <div style="margin-left:auto"><span class="online-dot"></span></div>
            </div>
        `;

        sbBody.innerHTML = html;
    }

    // ── PAGE RENDERING ──
    function renderPage() {
        mainContent.innerHTML = '<div class="pg fu">Loading...</div>';

        if (activeNav === 'dashboard') {
            renderDashboard();
        } else if (activeNav === 'exams-qb') {
            if (window.renderQuestionBank) window.renderQuestionBank();
            else renderGenericPage();
        } else {
            renderGenericPage();
        }
    }

    async function renderDashboard() {
        mainContent.innerHTML = '<div class="pg fu"><div style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p>Loading Dashboard...</p></div></div>';

        try {
            const res = await fetch(`${APP_URL}/api/teacher/dashboard`);
            const json = await res.json();
            
            if (!json.success) {
                mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${json.message}</div></div>`;
                return;
            }

            const data = json.data;
            const tInfo = data.teacher_info || {};
            const stats = data.stats || {};
            const todayClasses = data.today_classes || [];
            
            let classesHtml = '';
            if (todayClasses.length === 0) {
                classesHtml = '<div style="padding:15px; color:var(--text-light); text-align:center;">No classes scheduled for today.</div>';
            } else {
                todayClasses.forEach(cls => {
                    let badgeClass = 'bg-t';
                    if (cls.status === 'ONGOING') badgeClass = 'bg-green';
                    else if (cls.status === 'UPCOMING') badgeClass = 'bg-amber';
                    
                    classesHtml += `
                        <div class="cls-card ${cls.status === 'ONGOING' || cls.status === 'UPCOMING' ? (cls.status === 'ONGOING' ? 'ongoing' : 'upcoming') : ''}">
                            <div class="cls-time">${new Date('1970-01-01T' + cls.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            <div class="cls-info">
                                <span class="cls-subj">${cls.subject_name || 'Subject'}</span>
                                <span class="cls-batch">${cls.batch_name || 'Batch'} · Room ${cls.room || 'TBA'}</span>
                            </div>
                            <div class="bdg ${badgeClass}">${cls.status}</div>
                        </div>
                    `;
                });
            }

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-head">
                        <div class="pg-title">Namaste, ${tInfo.full_name || 'Teacher'} 👋</div>
                        <div style="font-size:12px; color:var(--text-body); margin-top:4px;">
                            ${tInfo.institute_name || 'Institute'} · Today is ${new Date().toLocaleDateString('en-US', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}
                        </div>
                    </div>

                    <!-- STAT GRID -->
                    <div class="sg">
                        <div class="sc">
                            <div class="sc-top"><div class="sc-ico ic-green"><i class="fa-solid fa-chalkboard-user"></i></div><div class="bdg bg-t">${stats.today_class_count} Today</div></div>
                            <div class="sc-val">${stats.today_class_count}</div>
                            <div class="sc-lbl">Today's Lectures</div>
                        </div>
                        <div class="sc">
                            <div class="sc-top"><div class="sc-ico ic-green"><i class="fa-solid fa-user-check"></i></div><div class="bdg bg-t"><i class="fa-solid fa-caret-up"></i> 2%</div></div>
                            <div class="sc-val">${stats.attendance_rate}%</div>
                            <div class="sc-lbl">This Week's Attendance</div>
                        </div>
                        <div class="sc">
                            <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-file-pen"></i></div><div class="bdg bg-r">${stats.pending_assignments} pending</div></div>
                            <div class="sc-val">${stats.pending_assignments}</div>
                            <div class="sc-lbl">Pending Grading</div>
                        </div>
                        <div class="sc">
                            <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-award"></i></div><div class="bdg bg-t">Questions</div></div>
                            <div class="sc-val">${stats.submitted_questions}</div>
                            <div class="sc-lbl">Submitted Questions</div>
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
                                ${classesHtml}
                            </div>
                        </div>

                        <!-- SYLLABUS & LEAVE -->
                        <div>
                            <div class="card mb-20">
                                <div class="ct"><i class="fa-solid fa-chart-line"></i> Syllabus Coverage</div>
                                ${(data.syllabus_coverage || []).length === 0 ? '<div style="font-size:12px; color:var(--text-light); text-align:center; padding:10px;">No coverage data available</div>' : data.syllabus_coverage.map(s => `
                                    <div class="pr-row">
                                        <div class="pr-lbl" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${s.subject}</div>
                                        <div class="pr-tr"><div class="pr-fi" style="width:${s.percentage}%; background:${s.color};"></div></div>
                                        <div style="font-size:10px; color:var(--text-light);">${s.percentage}%</div>
                                    </div>
                                `).join('')}
                                <button class="btn bs" style="width:100%; justify-content:center; margin-top:10px; font-size:11px;">Update Progress</button>
                            </div>

                            <div class="card">
                                <div class="ct"><i class="fa-solid fa-user-clock"></i> Leave Balance</div>
                                ${(data.leave_balance || []).length === 0 ? '<div style="font-size:12px; color:var(--text-light); text-align:center; padding:10px;">No leave data available</div>' : data.leave_balance.map(l => `
                                    <div style="margin-bottom:12px;">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                            <span style="font-size:12px; color:var(--text-body);">${l.type}</span>
                                            <span style="font-size:11px; font-weight:700;">${l.used}/${l.total} used</span>
                                        </div>
                                        <div class="prog-t"><div class="prog-f" style="width:${l.percentage}%; background:${l.color};"></div></div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Render Dashboard Error:', error);
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">Failed to load dashboard data.</div></div>`;
        }
    }

    function renderGenericPage() {
        let label = 'Module';
        const dashIdx = activeNav.indexOf('-');
        const id    = dashIdx === -1 ? activeNav : activeNav.slice(0, dashIdx);
        const subId = dashIdx === -1 ? null       : activeNav.slice(dashIdx + 1);
        
        // Dispatch to actual modules if implemented
        if (id === 'profile' && subId === 'personal') {
            if (typeof renderTeacherProfile === 'function') return renderTeacherProfile();
        }
        if (id === 'profile' && subId === 'salary-slips') {
            if (typeof renderSalarySlips === 'function') return renderSalarySlips();
        }
        if (id === 'classes') {
            if (typeof renderMyClasses === 'function') return renderMyClasses();
            if (typeof taSwitchClassTab === 'function') {
                 // For sub-tabs if called directly via nav, e.g. classes-today or classes-timetable
                 if (subId === 'today' || subId === 'timetable') {
                     renderMyClasses().then(() => {
                         taSwitchClassTab(subId);
                     });
                     return;
                 }
            }
        }

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
