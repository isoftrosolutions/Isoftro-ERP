/**
 * Hamro ERP — Teacher Portal
 * Production Blueprint V3.0
 * 
 * This script handles all front-end logic for the Teacher Portal.
 * It is modeled after the Front Desk Operator script for consistency and premium feel.
 */

console.log('Teacher Portal Loaded');

// ── STATE ──
let activeNav = 'dashboard';
let expanded = { academics: true, lms: false, exams: false, profile: false };

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page');
    activeNav = initialPage || 'dashboard';

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbClose = document.getElementById('sbClose');
    const sbOverlay = document.getElementById('sbOverlay');

    // ── NAVIGATION TREE ──
    const NAV = [
        { 
            sec: "OVERVIEW", 
            items: [
                { id: "dashboard", icon: "fa-th-large", label: "Dashboard" }
            ]
        },
        { 
            sec: "ACADEMICS", 
            items: [
                { id: "classes", icon: "fa-chalkboard-user", label: "My Classes" },
                { id: "attendance", icon: "fa-calendar-check", label: "Student Attendance", sub: [
                    { id: "mark", l: "Mark Attendance" },
                    { id: "report", l: "Attendance Report" }
                ]},
                { id: "timetable", icon: "fa-table", label: "Full Timetable" }
            ]
        },
        { 
            sec: "LMS & ASSIGNMENTS", 
            items: [
                { id: "lms", icon: "fa-book", label: "Learning Materials", sub: [
                    { id: "materials", l: "Subject Materials" },
                    { id: "upload", l: "Upload New" }
                ]},
                { id: "assignments", icon: "fa-file-pen", label: "Assignments", sub: [
                    { id: "active", l: "Ongoing Tasks" },
                    { id: "grading", l: "Pending Grading" },
                    { id: "create", l: "New Assignment" }
                ]}
            ]
        },
        { 
            sec: "EXAMS & GRADING", 
            items: [
                { id: "exams", icon: "fa-award", label: "Exams & Tests", sub: [
                    { id: "schedule", l: "Exam Schedule" },
                    { id: "qb", l: "Question Bank" },
                    { id: "results", l: "Publish Results" }
                ]}
            ]
        },
        { 
            sec: "PERSONAL", 
            items: [
                { id: "profile", icon: "fa-id-card", label: "My Profile", sub: [
                    { id: "personal", l: "Personal Info" },
                    { id: "salary-slips", l: "Salary Slips" }
                ]},
                { id: "leave", icon: "fa-calendar-plus", label: "Leave Requests", sub: [
                    { id: "apply", l: "Apply for Leave" },
                    { id: "history", l: "Leave History" }
                ]}
            ]
        }
    ];

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subActive = null) => {
        activeNav = subActive ? `${id}-${subActive}` : id;

        // Update URL
        const url = new URL(window.location.href);
        url.searchParams.set('page', activeNav);
        window.history.pushState({ activeNav }, '', url);

        if (window.innerWidth < 1024) document.body.classList.remove('sb-active');
        renderSidebar();
        renderPage();
    };

    window.toggleExp = (id) => {
        expanded[id] = !expanded[id];
        renderSidebar();
    };

    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.activeNav) {
            activeNav = e.state.activeNav;
        } else {
            const up = new URLSearchParams(window.location.search);
            activeNav = up.get('page') || 'dashboard';
        }
        renderSidebar();
        renderPage();
    });

    // ── SIDEBAR RENDERING ──
    function renderSidebar() {
        let html = '';

        NAV.forEach(section => {
            // Check if this section should have a divider
            if (section.sec === 'PERSONAL') {
                html += `<div class="sb-divider"></div>`;
            }
            
            html += `<div class="sb-sec"><div class="sb-sec-lbl">${section.sec}</div>`;

            section.items.forEach(item => {
                const isActive = activeNav === item.id || activeNav.startsWith(item.id + '-');
                const isExp = expanded[item.id];

                html += `<div class="sb-item">
                    <button class="nb-btn ${isActive ? 'active' : ''}" onclick="${item.sub ? `toggleExp('${item.id}')` : `goNav('${item.id}')`}">
                        <i class="fa-solid ${item.icon}"></i>
                        <span class="sb-lbl">${item.label}</span>
                        ${item.sub ? `<i class="fa-solid fa-chevron-right sb-chev" style="font-size:10px; transition:0.2s; ${isExp ? 'transform:rotate(90deg)' : ''}"></i>` : ''}
                    </button>`;

                if (item.sub && isExp) {
                    html += `<div class="sub-menu">`;
                    item.sub.forEach(s => {
                        const isSubActive = activeNav === `${item.id}-${s.id}`;
                        html += `<button class="sub-btn ${isSubActive ? 'active' : ''}" onclick="goNav('${item.id}', '${s.id}')">
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

        // Footer Section
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

    // ── PAGE DISPATCHER ──
    async function renderPage() {
        window.scrollTo(0, 0);
        mainContent.innerHTML = '<div class="pg fu"><div style="text-align:center; padding:100px;"><i class="fa-solid fa-spinner fa-spin fa-3x" style="color:var(--green)"></i><p style="margin-top:15px; color:var(--text-light)">Loading module...</p></div></div>';

        if (activeNav === 'dashboard') {
            renderDashboard();
        } else if (activeNav === 'classes') {
            renderMyClasses();
        } else if (activeNav === 'timetable') {
            renderFullTimetable();
        } else if (activeNav === 'attendance-mark') {
            window.renderPartialModule('attendance-mark');
        } else if (activeNav === 'attendance-report') {
            window.renderPartialModule('attendance-report');
        } else if (activeNav === 'lms-materials') {
            if (window.renderStudyMaterials) window.renderStudyMaterials();
            else renderGenericPage();
        } else if (activeNav === 'profile-personal' || activeNav === 'profile') {
            renderTeacherProfile();
        } else if (activeNav === 'exams-qb') {
            if (window.renderQuestionBank) window.renderQuestionBank();
            else renderGenericPage();
        } else if (activeNav === 'assignments-active' || activeNav === 'assignments-list') {
            if (window.renderHomeworkList) window.renderHomeworkList();
            else renderGenericPage();
        } else if (activeNav === 'assignments-create') {
            if (window.renderCreateHomeworkForm) window.renderCreateHomeworkForm();
            else renderGenericPage();
        } else if (activeNav === 'assignments-grading' || activeNav === 'assignments') {
            if (window.renderHomeworkList) window.renderHomeworkList(); // Currently no grading module, fallback to list
            else renderGenericPage();
        } else {
            renderGenericPage();
        }
    }

    // ── MODULE: DASHBOARD ──
    async function renderDashboard() {
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
                            <div class="sc-lbl">Attendance Rate</div>
                        </div>
                        <div class="sc">
                            <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-file-pen"></i></div><div class="bdg bg-r">${stats.pending_assignments} pending</div></div>
                            <div class="sc-val">${stats.pending_assignments}</div>
                            <div class="sc-lbl">Pending Grading</div>
                        </div>
                        <div class="sc">
                            <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-award"></i></div><div class="bdg bg-t">Exams</div></div>
                            <div class="sc-val">${stats.submitted_questions}</div>
                            <div class="sc-lbl">Exams Created</div>
                        </div>
                    </div>

                    <div class="g65 mt-20">
                        <!-- LEFT COL: CLASSES -->
                        <div>
                            <div class="card mb-20">
                                <div class="ct"><i class="fa-solid fa-clock"></i> Today's Schedule</div>
                                ${classesHtml}
                                <button class="btn bs" style="width:100%; justify-content:center; margin-top:10px; font-size:11px;" onclick="goNav('classes')">View Full Schedule</button>
                            </div>
                        </div>

                        <!-- RIGHT COL: SYLLABUS & LEAVE -->
                        <div>
                            <div class="card mb-20">
                                <div class="ct"><i class="fa-solid fa-chart-line"></i> Syllabus Progress</div>
                                ${(data.syllabus_coverage || []).length === 0 ? '<div style="font-size:12px; color:var(--text-light); text-align:center; padding:10px;">No coverage data available</div>' : data.syllabus_coverage.map(s => `
                                    <div class="pr-row">
                                        <div class="pr-lbl" title="${s.subject}">${s.subject}</div>
                                        <div class="pr-tr"><div class="pr-fi" style="width:${s.percentage}%; background:${s.color};"></div></div>
                                        <div style="font-size:10px; color:var(--text-light);">${s.percentage}%</div>
                                    </div>
                                `).join('')}
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
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">Failed to load dashboard.</div></div>`;
        }
    }

    // ── MODULE: CLASSES ──
    async function renderMyClasses() {
        renderTodaySchedule(); // Alias for specific view
    }

    async function renderTodaySchedule() {
        try {
            const res = await fetch(`${APP_URL}/api/teacher/classes?action=today`);
            const json = await res.json();
            
            if (!json.success) {
                mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${json.message}</div></div>`;
                return;
            }

            const data = json.data || [];
            let html = `
                <div class="pg fu">
                    <div class="pg-head mb-20">
                        <div class="pg-title">My Classes</div>
                        <div style="font-size:12px; color:var(--text-body); margin-top:4px;">Daily schedule and timetable</div>
                    </div>
                    
                    <div class="tabs">
                        <button class="tab-btn active" onclick="goNav('classes')">Today's Schedule</button>
                        <button class="tab-btn" onclick="goNav('timetable')">Weekly Timetable</button>
                    </div>

                    <div style="margin-top:20px;" class="grid">
            `;

            if (data.length === 0) {
                html += '<div class="alert alert-info">No classes scheduled for today.</div>';
            } else {
                data.forEach(cls => {
                    html += `
                        <div class="card p-20" style="position:relative;">
                            <h4 style="margin:0 0 10px 0; color:var(--text-dark);">${cls.subject_name || 'Subject'}</h4>
                            <div style="color:var(--text-light); font-size:13px; margin-bottom:5px;">
                                <i class="fa-solid fa-users" style="width:16px;"></i> ${cls.batch_name || 'Batch'}
                            </div>
                            <div style="color:var(--text-light); font-size:13px; margin-bottom:5px;">
                                <i class="fa-solid fa-clock" style="width:16px;"></i> ${cls.start_time} - ${cls.end_time}
                            </div>
                            <div style="color:var(--text-light); font-size:13px; margin-bottom:15px;">
                                <i class="fa-solid fa-door-open" style="width:16px;"></i> Room: ${cls.room || 'TBA'}
                            </div>
                            <div style="border-top:1px solid var(--card-border); padding-top:15px; text-align:right;">
                                <button class="btn btn-sm btn-green" onclick="alert('Marking attendance for ${cls.subject_name}')"><i class="fa-solid fa-user-check"></i> Mark Attendance</button>
                            </div>
                        </div>
                    `;
                });
            }

            html += `</div></div>`;
            mainContent.innerHTML = html;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">Failed to load classes.</div></div>`;
        }
    }

    // ── MODULE: FULL TIMETABLE ──
    async function renderFullTimetable() {
        try {
            const res = await fetch(`${APP_URL}/api/teacher/classes?action=weekly`);
            const json = await res.json();
            
            if (!json.success) {
                mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${json.message}</div></div>`;
                return;
            }

            const data = json.data || [];
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            
            // Group by day_of_week
            const grouped = {};
            for(let i=1; i<=7; i++) grouped[i] = [];
            data.forEach(slot => {
                if(grouped[slot.day_of_week]) grouped[slot.day_of_week].push(slot);
            });

            let html = `
                <div class="pg fu">
                    <div class="pg-head mb-20">
                        <div class="pg-title">Weekly Timetable</div>
                        <div style="font-size:12px; color:var(--text-body); margin-top:4px;">Full overview of your teaching commitments</div>
                    </div>
                    
                    <div class="tabs">
                        <button class="tab-btn" onclick="goNav('classes')">Today's Schedule</button>
                        <button class="tab-btn active" onclick="goNav('timetable')">Weekly Timetable</button>
                    </div>

                    <div style="margin-top:20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            `;

            for(let i=1; i<=7; i++) {
                const daySlots = grouped[i];
                const isToday = (new Date().getDay() + 1) === i;

                html += `
                    <div class="card p-0 overflow-hidden" style="${isToday ? 'border-top: 3px solid var(--green);' : ''}">
                        <div style="padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; color: ${isToday ? 'var(--green)' : 'var(--text-dark)'}; font-size: 13px;">${days[i-1]}</span>
                            ${isToday ? '<span class="bdg bg-green" style="font-size: 9px; padding: 2px 6px;">TODAY</span>' : ''}
                        </div>
                        <div style="padding: 10px;">
                `;

                if (daySlots.length === 0) {
                    html += `<div style="padding: 20px 0; text-align: center; color: var(--text-light); font-size: 11px;">No classes</div>`;
                } else {
                    daySlots.forEach(slot => {
                        html += `
                            <div style="padding: 10px; border-radius: 8px; background: #fff; border: 1px solid var(--card-border); margin-bottom: 8px; position: relative; transition: 0.2s; cursor: pointer;" 
                                 onmouseover="this.style.borderColor='var(--green)'; this.style.background='#f0fdf4'" 
                                 onmouseout="this.style.borderColor='var(--card-border)'; this.style.background='#fff'">
                                <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom:2px;">${slot.subject_name}</div>
                                <div style="font-size: 10px; color: var(--text-body); margin-bottom:4px;">${slot.batch_name}</div>
                                <div style="font-size: 10px; color: var(--text-light); display: flex; align-items: center; gap: 4px;">
                                    <i class="fa-regular fa-clock" style="font-size: 9px;"></i>
                                    ${new Date('1970-01-01T' + slot.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true})}
                                </div>
                            </div>
                        `;
                    });
                }

                html += `</div></div>`;
            }

            html += `</div></div>`;
            mainContent.innerHTML = html;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">Failed to load weekly timetable.</div></div>`;
        }
    }

    // ── MODULE: PROFILE ──
    async function renderTeacherProfile() {
        try {
            const res = await fetch(`${APP_URL}/api/teacher/profile`);
            const json = await res.json();
            
            if (!json.success) {
                mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${json.message}</div></div>`;
                return;
            }

            const data = json.data;
            const initials = data.full_name ? data.full_name.substring(0, 2).toUpperCase() : 'T';

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-head mb-20">
                        <div class="pg-title">My Profile</div>
                        <div style="font-size:12px; color:var(--text-body); margin-top:4px;">Personal details and portal settings</div>
                    </div>
                    
                    <div class="g65">
                        <div class="card" style="text-align:center;">
                            <div style="margin-bottom:20px; display:flex; justify-content:center;">
                                <div style="width:100px; height:100px; border-radius:50%; background:var(--green); color:white; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:700; box-shadow:0 10px 20px rgba(0,184,148,0.2);">${initials}</div>
                            </div>
                            <h3 style="margin:0 0 5px 0;">${data.full_name || 'N/A'}</h3>
                            <div style="color:var(--text-light); font-size:14px; margin-bottom:15px;">Employee ID: ${data.employee_id || '---'}</div>
                            <div class="bdg bg-green" style="margin-bottom:20px;">${data.status || 'Active'}</div>
                            
                            <div style="border-top:1px solid var(--card-border); padding-top:20px; text-align:left;">
                                <div style="margin-bottom:12px; font-size:13px;"><i class="fa-solid fa-envelope" style="width:20px; color:var(--green);"></i> ${data.email || 'N/A'}</div>
                                <div style="margin-bottom:12px; font-size:13px;"><i class="fa-solid fa-phone" style="width:20px; color:var(--green);"></i> ${data.phone || 'N/A'}</div>
                                <div style="margin-bottom:12px; font-size:13px;"><i class="fa-solid fa-calendar-alt" style="width:20px; color:var(--green);"></i> Joined on ${data.joined_date || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <h3 style="margin-bottom:15px; border-bottom:1px solid var(--card-border); padding-bottom:10px; font-size:16px;">Job Information</h3>
                            
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                                <div class="mb-20">
                                    <label style="font-size:11px; font-weight:700; color:var(--text-light); text-transform:uppercase;">Qualification</label>
                                    <div style="font-size:14px; margin-top:4px;">${data.qualification || 'Not Specified'}</div>
                                </div>
                                <div class="mb-20">
                                    <label style="font-size:11px; font-weight:700; color:var(--text-light); text-transform:uppercase;">Specialization</label>
                                    <div style="font-size:14px; margin-top:4px;">${data.specialization || 'Not Specified'}</div>
                                </div>
                            </div>

                            <h3 style="margin-top:20px; margin-bottom:15px; border-bottom:1px solid var(--card-border); padding-bottom:10px; font-size:16px;">Account Security</h3>
                            <div style="padding:15px; background:#f8fafc; border-radius:10px; border:1px solid var(--card-border);">
                                <p style="font-size:12px; color:var(--text-body); margin-bottom:15px;">Update your password regularly to keep your account secure.</p>
                                <button class="btn btn-green" style="width:100%; justify-content:center;"><i class="fa-solid fa-key"></i> Change Password</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">Failed to load profile.</div></div>`;
        }
    }

    // ── GENERIC FALLBACK ──
    function renderGenericPage() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div style="text-align:center; padding:100px;">
                    <i class="fa-solid fa-screwdriver-wrench" style="font-size:48px; color:#cbd5e1; margin-bottom:20px;"></i>
                    <h2 style="color:#1e293b;">${activeNav.toUpperCase().replace('-', ' ')}</h2>
                    <p style="color:#64748b;">This module is being connected to the database...</p>
                    <button class="btn btn-green mt-20" onclick="goNav('dashboard')"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</button>
                </div>
            </div>`;
    }

    // ── INITIALIZE ──
    if (sbToggle) {
        sbToggle.addEventListener('click', () => document.body.classList.toggle('sb-active'));
    }
    if (sbClose) {
        sbClose.addEventListener('click', () => document.body.classList.remove('sb-active'));
    }
    if (sbOverlay) {
        sbOverlay.addEventListener('click', () => document.body.classList.remove('sb-active'));
    }

    renderSidebar();
    renderPage();
    
    // Internal Helper to render sidebar dynamically
    window.__teacherRenderSidebar = renderSidebar;
    window.__teacherRenderPage = renderPage;
});

// ── GLOBAL UTILITIES ──
window.renderPartialModule = async function(endpoint, extraParams = '') {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div class="pg fu"><div style="text-align:center; padding:100px;"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color:var(--green)"></i><br><span style="color:var(--text-light); font-size:12px;">Loading Module...</span></div></div>';

    try {
        const url = `${window.APP_URL}/dash/teacher/${endpoint}?partial=true${extraParams}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const html = await res.text();
        mc.innerHTML = html;

        // Execute scripts in partial safely
        const scripts = mc.querySelectorAll('script');
        scripts.forEach(s => {
            if (s.src) {
                // If script with same SRC already exists, skip
                if (document.querySelector(`script[src*="${s.src.split('?')[0]}"]`)) {
                    console.log(`Skipping already loaded script: ${s.src}`);
                    return;
                }
            }
            const newScript = document.createElement('script');
            if (s.src) {
                newScript.src = s.src;
            } else {
                newScript.textContent = s.textContent;
            }
            document.head.appendChild(newScript);
        });
    } catch (err) {
        console.error('Partial Load Error:', err);
        mc.innerHTML = `<div class="pg fu"><div class="alert alert-danger">Failed to load module: ${err.message}</div></div>`;
    }
};

// ── EXPOSED FUNCTIONS ──
window.goNav = (id, subActive = null) => {
    activeNav = subActive ? `${id}-${subActive}` : id;

    // Update URL
    const url = new URL(window.location.href);
    url.searchParams.set('page', activeNav);
    window.history.pushState({ activeNav }, '', url);

    if (window.innerWidth < 1024) document.body.classList.remove('sb-active');
    
    if (window.__teacherRenderSidebar) window.__teacherRenderSidebar();
    if (window.__teacherRenderPage) window.__teacherRenderPage();
};

window.toggleExp = (id) => {
    expanded[id] = !expanded[id];
    if (window.__teacherRenderSidebar) window.__teacherRenderSidebar();
};
