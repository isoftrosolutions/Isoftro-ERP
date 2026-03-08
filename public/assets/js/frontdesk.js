/**
 * Hamro ERP — Front Desk Operator
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

console.log('Front Desk Operator Loaded');

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

    // ── NAVIGATION LOGIC ──
    window._iaRenderBreadcrumb = (items) => {
        const breadcrumb = document.getElementById('iaBreadcrumb');
        if (!breadcrumb) return;
        
        let html = '';
        items.forEach((item, idx) => {
            if (idx === items.length - 1) {
                html += `<span class="bc-item-active">${item.label}</span>`;
            } else {
                html += `<a href="${item.link || '#'}" class="bc-item">${item.label}</a> <i class="fa fa-chevron-right bc-sep"></i> `;
            }
        });
        breadcrumb.innerHTML = html;
        breadcrumb.style.display = 'flex';
    };

    window.getHeaders = (options = {}) => {
        const headers = options.headers || {};
        if (window.CSRF_TOKEN) {
            headers['X-CSRF-Token'] = window.CSRF_TOKEN;
        }
        return { ...options, headers };
    };

    // ── SIDEBAR TOGGLE ──
    const toggleSidebar = () => document.body.classList.toggle('sb-active');
    const closeSidebar = () => document.body.classList.remove('sb-active');

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbClose) sbClose.addEventListener('click', closeSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

    // ── NAVIGATION TREE ──
    const NAV = [
        { sec: "Overview", items: [
            { id: "dashboard", icon: "fa-th-large", l: "Dashboard" },
            { id: "attendance", icon: "fa-calendar-check", l: "Today's Attendance", badge: { val: 12, c: "amber" } }
        ]},
        { sec: "Admissions", items: [
            { id: "admissions-adm-all", icon: "fa-user-graduate", l: "Student Lookup" },
            { id: "admissions-adm-form", icon: "fa-user-plus", l: "New Admission" },
            { id: "operations-inq-list", icon: "fa-comments", l: "Inquiries", badge: { val: 7 } }
        ]},
        { sec: "Fee & Finance", items: [
            { id: "fee-fee-coll", icon: "fa-money-bill-wave", l: "Fee Collection" },
            { id: "transactions", icon: "fa-exchange-alt", l: "Transactions" },
            { id: "pending-dues", icon: "fa-clock", l: "Pending Dues", badge: { val: 18 } },
            { id: "receipts", icon: "fa-receipt", l: "Receipts" }
        ]},
        { sec: "Operations", items: [
            { id: "leave-requests", icon: "fa-user-clock", l: "Leave Requests", badge: { val: 5, c: "amber" } },
            { id: "library", icon: "fa-book", l: "Library Desk" },
            { id: "timetable", icon: "fa-table", l: "Today's Timetable" },
            { id: "announcements", icon: "fa-bullhorn", l: "Announcements", badge: { val: 2, c: "green" } }
        ]},
        { sec: "System", divider: true, items: [
            { id: "support", icon: "fa-headset", l: "Support Tickets" },
            { id: "audit-log", icon: "fa-shield-alt", l: "Activity Log" }
        ]}
    ];

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subActive = null, extraParams = '') => {
        activeNav = subActive ? `${id}-${subActive}` : id;

        // Update URL via pushState
        const url = new URL(window.location.origin + window.location.pathname);
        url.searchParams.set('page', activeNav);
        
        if (extraParams) {
            const ep = extraParams.startsWith('&') || extraParams.startsWith('?') ? extraParams.substring(1) : extraParams;
            const p = new URLSearchParams(ep);
            p.forEach((v, k) => url.searchParams.set(k, v));
        }

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
        const sbBody = document.getElementById('sbBody');
        if (!sbBody) return;

        let html = '';
        NAV.forEach(section => {
            if (section.divider) html += '<div class="sb-divider"></div>';
            if (section.sec) html += `<div class="sb-sec-lbl">${section.sec}</div>`;
            
            section.items.forEach(item => {
                const isActive = activeNav === item.id;
                // Parse IDs like 'admissions-adm-all' back into goNav calls
                let onclick = '';
                if (item.id.includes('-')) {
                    const parts = item.id.split('-');
                    onclick = `goNav('${parts[0]}', '${parts.slice(1).join('-')}')`;
                } else {
                    onclick = `goNav('${item.id}')`;
                }

                html += `
                <button class="sb-btn ${isActive ? 'active' : ''}" onclick="${onclick}">
                    <i class="fa ${item.icon}"></i>
                    <span class="sb-lbl">${item.l}</span>
                    ${item.badge ? `<span class="sb-badge ${item.badge.c || ''}">${item.badge.val}</span>` : ''}
                </button>`;
            });
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
            { id: 'admissions-adm-all', icon: 'fa-users', label: 'Students', action: "goNav('admissions', 'adm-all')" },
            { id: 'fee-fee-coll', icon: 'fa-money-bill-wave', label: 'Fee', action: "goNav('fee', 'fee-coll')" },
            { id: 'operations-inq-list', icon: 'fa-magnifying-glass', label: 'Inquiries', action: "goNav('operations', 'inq-list')" }
        ];

        let html = '';
        items.forEach(item => {
            const isActive = activeNav === item.id;
            html += `<button class="mb-nav-btn ${isActive ? 'active' : ''}" onclick="${item.action}">
                <i class="fa-solid ${item.icon}"></i>
                <span>${item.label}</span>
            </button>`;
        });
        bNav.innerHTML = html;
    }

    // ── PAGE RENDERING ──
    function renderPage() {
        window.scrollTo(0, 0);
        mainContent.innerHTML = '<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading...</span></div></div>';

        if (activeNav === 'dashboard') {
            renderDashboard();
        } 
        // ── STUDENT MODULES ──
        else if (activeNav === 'students' || activeNav === 'admissions-adm-all') {
            if (window.renderStudentList) window.renderStudentList();
        } else if (activeNav === 'new-admission' || activeNav === 'admissions-adm-form') {
            if (window.renderAddStudentForm) window.renderAddStudentForm();
        } else if (activeNav === 'alumni') {
            if (window.renderAlumniList) window.renderAlumniList();
        }
        // ── ATTENDANCE MODULES ──
        else if (activeNav === 'attendance') {
            if (window.renderAttendanceTake) window.renderAttendanceTake();
        } else if (activeNav === 'attendance-report') {
            if (window.renderAttendanceReport) window.renderAttendanceReport();
        } else if (activeNav === 'leave-requests') {
            if (window.renderLeaveRequests) window.renderLeaveRequests();
        }
        // ── FINANCE MODULES ──
        else if (activeNav === 'fee-fee-coll') {
            if (window.renderFeeCollect) window.renderFeeCollect();
        } else if (activeNav === 'pending-dues' || activeNav === 'finance-fee-outstanding' || activeNav === 'outstanding') {
            if (window.renderFeeOutstanding) window.renderFeeOutstanding();
        } else if (activeNav === 'receipts' || activeNav === 'transactions') {
            if (window.renderFeeReceipts) window.renderFeeReceipts();
        } else if (activeNav === 'fee-details' || activeNav.includes('fee-details')) {
            if (window.renderFeeDetails) window.renderFeeDetails();
        }
        // ── ACADEMIC MODULES ──
        else if (activeNav === 'academic-courses' || activeNav === 'courses') {
            if (window.renderCourseList) window.renderCourseList();
        } else if (activeNav === 'academic-batches' || activeNav === 'batches') {
            if (window.renderBatchList) window.renderBatchList();
        } else if (activeNav === 'academic-subjects' || activeNav === 'subjects') {
            if (window.renderSubjectList) window.renderSubjectList();
        }
        // ── OPERATIONS MODULES ──
        else if (activeNav === 'inquiries' || activeNav === 'operations-inq-list') {
            if (window.renderInquiryList) window.renderInquiryList();
        } else if (activeNav === 'reception-visitor' || activeNav === 'visitor-log') {
            renderVisitorLog();
        } else if (activeNav === 'reception-appointment' || activeNav === 'appointments') {
            renderAppointmentSchedule();
        } else if (activeNav === 'reception-call' || activeNav === 'call-logs') {
            renderCallLog();
        } else if (activeNav === 'reception-complaint' || activeNav === 'complaints') {
            renderComplaints();
        } else if (activeNav === 'timetable') {
            if (window.renderTimetable) window.renderTimetable();
        } else if (activeNav === 'exams' || activeNav === 'assessments') {
            if (window.renderExamList) window.renderExamList();
        } else if (activeNav === 'homework') {
            if (window.renderHomeworkList) window.renderHomeworkList();
        } else if (activeNav === 'staff') {
            if (window.renderStaffList) window.renderStaffList();
        } else if (activeNav === 'salary' || activeNav === 'payroll') {
            if (window.renderSalaryList) window.renderSalaryList();
        } else if (activeNav === 'study-materials') {
            if (window.renderStudyMaterials) window.renderStudyMaterials();
        } else if (activeNav === 'inq-add' || activeNav === 'add-inq') {
            if (window.renderAddInquiryForm) window.renderAddInquiryForm();
        } else if (activeNav === 'inq-analytics' || activeNav === 'analytics') {
            if (window.renderInquiryAnalytics) window.renderInquiryAnalytics();
        } else if (activeNav === 'adm-form' || activeNav === 'admission-form') {
            if (window.renderAdmissionForm) window.renderAdmissionForm();
        } else if (activeNav === 'audit-log') {
            if (window.renderAuditLogs) window.renderAuditLogs();
        } else if (activeNav === 'support') {
            if (window.renderSupportTickets) window.renderSupportTickets();
        } else if (activeNav === 'settings') {
            if (window.renderInstituteSettings) window.renderInstituteSettings();
        }
        else {
            // Fallback for subpages or missing ones
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div style="text-align:center; padding:100px;">
                        <i class="fa-solid fa-screwdriver-wrench" style="font-size:48px; color:#cbd5e1; margin-bottom:20px;"></i>
                        <h2 style="color:#1e293b;">${activeNav.split('-').join(' ').toUpperCase()}</h2>
                        <p style="color:#64748b;">This module is being connected to the administrative logic...</p>
                    </div>
                </div>`;
        }
    }

    async function renderDashboard() {
        window.scrollTo(0, 0);
        mainContent.innerHTML = '<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Dashboard...</span></div></div>';
    

        try {
            const res = await fetch(`${APP_URL}/api/admin/stats`, getHeaders());
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            const s = result.data;
            
            // Helper functions
            const fmtNum = (n) => parseInt(n || 0).toLocaleString('en-IN');
            const fmtMoney = (n) => parseFloat(n || 0).toLocaleString('en-IN');
            
            // 1. KPI Fees
            const moneyCollectedToday = s.fee_summary?.reduce((sum, f) => sum + parseFloat(f.total), 0) || 0;
            const duesPending = s.kpi_dues?.amount || 0;
            const studentsOverdue = s.kpi_dues?.count || 0;

            // 2. Attendance & Admissions
            const attendancePct = s.attendance_rate || 0;
            const attToday = s.attendance_overview?.today || { present: 0, total: 0, absent: 0 };
            const admissionsToday = s.kpi_students?.new || 0;

            // 3. Chips
            const openInquiries = s.secondary_kpi?.inquiries || 0;
            const pendingLeaves = s.pending_leaves?.length || 0;
            const libraryIssuesToday = s.today_library_issues?.length || 0;
            const lastReceipt = s.recent_transactions?.[0]?.receipt_no || '--';

            mainContent.innerHTML = `
            <div class="pg fu">
                <div class="page-hdr" style="margin-bottom:16px;">
                    <div class="page-hdr-left">
                        <h1 class="pg-title"><i class="fa fa-th-large" style="color:var(--green);margin-right:8px"></i>Front Desk Dashboard</h1>
                        <p class="pg-sub">${new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })} &nbsp;·&nbsp; Academic Year ${s.header?.academic_year || ''} &nbsp;·&nbsp; All data for ${result.header?.institute_name || window.tenantName || 'Institute'}</p>
                    </div>
                </div>

                <div class="search-bar" style="background:#fff;border:1px solid #E5E9F0;border-radius:6px;height:40px;padding:0 16px;display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                    <i class="fa fa-search" style="color:#A8BCCF;font-size:16px;"></i>
                    <input type="text" placeholder="Search student, roll no, receipt..." style="border:none;outline:none;flex:1;font-size:13px;color:#A8BCCF;"/>
                    <button class="btn btn-primary" style="background:#00A86B;color:#fff;border-radius:6px;padding:8px 20px;font-size:13px;font-weight:600;border:none;" onclick="goNav('fee','fee-coll')">
                        <i class="fa-solid fa-circle-plus"></i> Record Payment
                    </button>
                </div>

                ${studentsOverdue > 0 ? `
                <div id="alert-banner" style="background:#FFF8E1;border:1px solid #F5A623;border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#F5A623;font-size:16px;"></i>
                    <div style="flex:1;">
                        <strong style="color:#E65100;">${studentsOverdue} students</strong>
                        <span style="color:#92400E;font-size:13px;">have fee dues overdue. Reminders sent via SMS.</span>
                    </div>
                    <button class="btn" style="background:transparent;border:none;color:#1565C0;font-size:12px;cursor:pointer;text-decoration:underline;">View All</button>
                    <button class="btn" style="background:transparent;border:none;color:#6B7A99;font-size:12px;cursor:pointer;" onclick="this.parentElement.style.display='none'">Dismiss</button>
                </div>` : ''}

                <div class="dashboard-grid">
                    <!-- KPI Row 1 -->
                    <div class="col-6 panel kpi-card">
                        <div class="kpi-top">
                            <div class="c-icon bg-green-soft"><i class="fa-solid fa-wallet"></i></div>
                        </div>
                        <div class="kpi-val">Rs ${fmtMoney(moneyCollectedToday)}</div>
                        <div class="kpi-lbl">Today's Collection</div>
                        <div class="kpi-sub">${s.recent_transactions?.length || 0} transactions</div>
                    </div>
                    
                    <div class="col-6 panel kpi-card">
                        <div class="kpi-top">
                            <div class="c-icon bg-amber-soft"><i class="fa-regular fa-clock"></i></div>
                        </div>
                        <div class="kpi-val danger">Rs ${fmtMoney(duesPending)}</div>
                        <div class="kpi-lbl">Pending Dues</div>
                        <div class="kpi-sub">${studentsOverdue} students with dues</div>
                    </div>

                    <!-- KPI Row 2 -->
                    <div class="col-6 panel kpi-card">
                        <div class="kpi-top">
                            <div class="c-icon bg-blue-soft"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="kpi-val">${attendancePct}%</div>
                        <div class="kpi-lbl">Attendance Today</div>
                        <div class="kpi-sub">${attToday.present}/${attToday.total} present across all batches</div>
                    </div>

                    <div class="col-6 panel kpi-card">
                        <div class="kpi-top">
                            <div class="c-icon bg-purple-soft"><i class="fa-solid fa-user-plus"></i></div>
                        </div>
                        <div class="kpi-val">${admissionsToday}</div>
                        <div class="kpi-lbl">New Admissions This Month</div>
                        <div class="kpi-sub">Total Active: ${fmtNum(s.kpi_students?.total || 0)}</div>
                    </div>

                    <!-- Status Chips -->
                    <div class="col-12 chip-row">
                        <div class="chip">
                            <div class="c-icon sm bg-amber-soft"><i class="fa-regular fa-message"></i></div>
                            <div style="flex:1;"><div class="chip-val">${openInquiries}</div><div class="chip-lbl">Open Inquiries</div></div>
                            <div class="pill green">New</div>
                        </div>
                        <div class="chip">
                            <div class="c-icon sm bg-amber-soft"><i class="fa-solid fa-calendar-xmark"></i></div>
                            <div style="flex:1;"><div class="chip-val">${pendingLeaves}</div><div class="chip-lbl">Leave Requests</div></div>
                            ${pendingLeaves > 0 ? '<div class="pill orange">Pending</div>' : ''}
                        </div>
                        <div class="chip">
                            <div class="c-icon sm bg-blue-soft"><i class="fa-solid fa-book-open"></i></div>
                            <div style="flex:1;"><div class="chip-val">${libraryIssuesToday}</div><div class="chip-lbl">Library Issues Today</div></div>
                        </div>
                        <div class="chip">
                            <div class="c-icon sm bg-purple-soft"><i class="fa-solid fa-receipt"></i></div>
                            <div style="flex:1;"><div class="chip-val" style="font-size:13px;font-family:monospace;">${lastReceipt}</div><div class="chip-lbl">Last Receipt No.</div></div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-12 panel">
                        <div class="panel-sub">⚡ QUICK ACTIONS</div>
                        <div class="qa-row">
                            <div class="qa-item" onclick="goNav('fee','fee-coll')">
                                <div class="qa-icon bg-green-soft"><i class="fa-solid fa-money-bill-transfer"></i></div>
                                <div><div class="qa-lbl">Collect Fee</div><div class="qa-sub">Record payment</div></div>
                            </div>
                            <div class="qa-item" onclick="goNav('admissions','adm-form')">
                                <div class="qa-icon bg-blue-soft"><i class="fa-solid fa-user-plus"></i></div>
                                <div><div class="qa-lbl">New Admission</div><div class="qa-sub">Register student</div></div>
                            </div>
                            <div class="qa-item" onclick="goNav('attendance')">
                                <div class="qa-icon bg-amber-soft"><i class="fa-solid fa-clipboard-user"></i></div>
                                <div><div class="qa-lbl">Mark Attendance</div><div class="qa-sub">Today's class</div></div>
                            </div>
                            <div class="qa-item" onclick="goNav('operations','inq-list')">
                                <div class="qa-icon bg-purple-soft"><i class="fa-solid fa-message"></i></div>
                                <div><div class="qa-lbl">Add Inquiry</div><div class="qa-sub">Log new inquiry</div></div>
                            </div>
                            <div class="qa-item" onclick="goNav('fee','fee-sum')">
                                <div class="qa-icon bg-orange-soft"><i class="fa-solid fa-print"></i></div>
                                <div><div class="qa-lbl">Print Receipt</div><div class="qa-sub">Reprints & copies</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions & Summary -->
                    <div class="col-8 panel" style="padding:0; overflow:hidden;">
                        <div class="panel-header" style="padding:16px 20px; border-bottom:1px solid #E5E9F0; margin:0; background:#FAFAFA;">
                            <div class="panel-title"><i class="fa-solid fa-receipt" style="color:#00A86B;"></i> Today's Fee Transactions</div>
                            <button class="btn" style="background:transparent;border:1px solid #E5E9F0;color:#6B7A99;font-size:12px;padding:4px 10px;border-radius:4px;"><i class="fa-solid fa-cloud-arrow-down"></i> Export</button>
                        </div>
                        <table class="dash-table">
                            <thead>
                                <tr><th>Receipt No.</th><th>Student</th><th>Amount</th><th>Method</th><th>Time</th></tr>
                            </thead>
                            <tbody>
                                ${s.recent_transactions && s.recent_transactions.length ? s.recent_transactions.map((t, i) => `
                                <tr class="${i%2===0?'':'even'}">
                                    <td style="font-family:monospace;">${t.receipt_no}</td>
                                    <td><div style="font-weight:600;">${t.student_name}</div><div style="font-size:10px;color:#6B7A99;">${t.roll_no||''} · ${t.batch_name||''}</div></td>
                                    <td style="font-weight:700;">Rs ${fmtMoney(t.amount)}</td>
                                    <td><span class="pill ${t.payment_method==='cash'?'green':t.payment_method==='esewa'?'blue':'magenta'}">${t.payment_method}</span></td>
                                    <td style="color:#6B7A99;font-size:11px;">--</td>
                                </tr>`).join('') : '<tr><td colspan="5" style="text-align:center;">No transactions today.</td></tr>'}
                            </tbody>
                        </table>
                    </div>

                    <div class="col-4 panel">
                        <div class="panel-title mb" style="margin-bottom:16px;">Today's Fee Summary</div>
                        ${s.fee_summary && s.fee_summary.length ? `
                            <div class="stacked-bar">
                                ${s.fee_summary.map(f => {
                                    let c = '#4CAF50'; if(f.payment_method==='esewa') c='#2196F3'; else if(f.payment_method==='khalti') c='#9C27B0'; else if(f.payment_method==='bank') c='#FF9800';
                                    let pct = moneyCollectedToday > 0 ? (f.total/moneyCollectedToday)*100 : 0;
                                    return `<div class="sb-segment" style="width:${pct}%;background:${c};"></div>`;
                                }).join('')}
                            </div>
                            ${s.fee_summary.map(f => `
                                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F0F2F5;">
                                    <span style="font-size:13px;color:#6B7A99;text-transform:capitalize;">${f.payment_method}</span>
                                    <span style="font-size:13px;font-weight:700;">Rs ${fmtMoney(f.total)}</span>
                                </div>
                            `).join('')}
                        ` : '<div style="color:#6B7A99;font-size:13px;text-align:center;">No collections yet.</div>'}
                        <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:2px solid #E5E9F0;margin-top:8px;">
                            <span style="font-size:13px;font-weight:700;">Total Collected</span>
                            <span style="font-size:15px;font-weight:700;color:#00A86B;">Rs ${fmtMoney(moneyCollectedToday)}</span>
                        </div>
                    </div>

                    <!-- Announcements & Snapshots Row -->
                    <div class="col-4 panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="fa-solid fa-clipboard-check" style="color:#00A86B;"></i> Attendance Snapshot</div>
                        </div>
                        <div class="panel-sub" style="margin-bottom:12px;text-transform:none;">All Batches</div>
                        <div class="att-boxes">
                            <div class="att-box bg-green-soft"><div class="att-num">${attToday.present}</div><div class="att-lbl">Present</div></div>
                            <div class="att-box bg-red-soft"><div class="att-num">${attToday.absent}</div><div class="att-lbl">Absent</div></div>
                            <div class="att-box bg-blue-soft"><div class="att-num">${attToday.total - (attToday.present+attToday.absent)}</div><div class="att-lbl">Other</div></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <span style="font-size:11px;color:#6B7A99;">Attendance Rate</span>
                            <span style="font-size:12px;font-weight:700;color:#00A86B;">${attendancePct}%</span>
                        </div>
                        <div class="prog-track mb"><div class="prog-fill" style="width:${attendancePct}%"></div></div>
                        <div class="panel-sub" style="margin-top:12px;margin-bottom:8px;">BY BATCH</div>
                        ${s.batch_attendance && s.batch_attendance.length ? s.batch_attendance.slice(0,4).map(b => `
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <div class="c-icon sm bg-green-soft">${b.batch_name.charAt(0)}</div>
                                <div style="flex:1;font-size:12px;font-weight:500;">${b.batch_name}</div>
                                <div style="font-size:12px;font-weight:700;color:#00A86B;">${b.rate}%</div>
                            </div>
                        `).join('') : '<div style="font-size:12px;color:#6B7A99;text-align:center;">No batch data today.</div>'}
                    </div>

                    <div class="col-4 panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="fa-solid fa-message" style="color:#3B82F6;"></i> Today's Inquiries</div>
                            <div style="display:flex;gap:6px;"><span class="pill red">${openInquiries} Open</span><button class="pill green" style="border:none;cursor:pointer;" onclick="goNav('operations','inq-list')">+ Add</button></div>
                        </div>
                        <div>
                            ${s.today_inquiries && s.today_inquiries.length ? s.today_inquiries.map(i => `
                                <div class="list-item">
                                    <div class="li-avatar bg-blue-soft">${(i.name || i.contact_name || '?').charAt(0)}</div>
                                    <div style="flex:1;">
                                        <div class="li-title">${i.name || i.contact_name || 'Visitor'}</div>
                                        <div class="li-sub">${i.program_of_interest || 'General Inquiry'}</div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-size:10px;color:#6B7A99;margin-bottom:4px;">${new Date(i.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</div>
                                        <span class="pill ${i.status==='new'?'green':'orange'}">${i.status}</span>
                                    </div>
                                </div>
                            `).join('') : '<div style="font-size:12px;color:#6B7A99;text-align:center;">No inquiries today.</div>'}
                        </div>
                    </div>

                    <div class="col-4 panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="fa-solid fa-calendar-xmark" style="color:#F5A623;"></i> Pending Leave</div>
                            <div class="c-icon sm bg-amber-soft" style="font-size:10px;font-weight:bold;">${pendingLeaves}</div>
                        </div>
                        <div>
                            ${s.pending_leaves && s.pending_leaves.length ? s.pending_leaves.map(l => `
                                <div class="list-item">
                                    <div class="li-avatar bg-orange-soft">${(l.student_name || '?').charAt(0)}</div>
                                    <div style="flex:1;">
                                        <div class="li-title">${l.student_name || 'Student'}</div>
                                        <div class="li-sub">${l.start_date || l.from_date || ''} · ${l.reason || 'Leave'}</div>
                                    </div>
                                    <div style="display:flex;gap:4px;">
                                        <button class="c-icon sm bg-green-soft" style="border:none;cursor:pointer;"><i class="fa-solid fa-check"></i></button>
                                        <button class="c-icon sm bg-red-soft" style="border:none;cursor:pointer;"><i class="fa-solid fa-times"></i></button>
                                    </div>
                                </div>
                            `).join('') : '<div style="font-size:12px;color:#6B7A99;text-align:center;">No pending requests.</div>'}
                        </div>
                    </div>

                    <!-- Bottom row: Timetable, Activity, Library -->
                    <div class="col-4 panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="fa-regular fa-clock"></i> Today's Timetable</div>
                            <span class="pill blue">${new Date().toLocaleDateString('en-US',{weekday:'long'})}</span>
                        </div>
                        <div>
                            ${s.today_timetable && s.today_timetable.length ? s.today_timetable.map(ts => {
                                let cTime = new Date().toTimeString().substring(0,8);
                                let isOngoing = ts.start_time <= cTime && ts.end_time >= cTime;
                                return `
                                <div style="margin-bottom:12px;">
                                    <div style="font-size:11px;font-weight:500;color:#6B7A99;margin-bottom:4px;">${ts.start_time.substring(0,5)} - ${ts.end_time.substring(0,5)}</div>
                                    <div style="background:${isOngoing?'#F0FFF8':'#FAFAFA'};border-left:3px solid ${isOngoing?'#00A86B':'#E5E9F0'};border-radius:0 6px 6px 0;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <div style="font-size:13px;font-weight:600;color:#1A1F36;">${ts.subject_name || 'Class'}</div>
                                            <div style="font-size:11px;color:#6B7A99;">${ts.batch_name||''} · ${ts.room_name||''}</div>
                                        </div>
                                        ${isOngoing ? '<span class="pill green">Ongoing</span>' : ''}
                                    </div>
                                </div>`;
                            }).join('') : '<div style="font-size:12px;color:#6B7A99;text-align:center;">No classes scheduled today.</div>'}
                        </div>
                    </div>

                    <div class="col-4 panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="fa-solid fa-bolt" style="color:#8141A5;"></i> Activity Log</div>
                        </div>
                        <div class="timeline">
                            ${s.activity_log && s.activity_log.length ? s.activity_log.slice(0,5).map((a,i) => `
                                <div class="tl-item">
                                    <div class="tl-dot ${i%2===0?'green':i%3===0?'blue':'orange'}"></div>
                                    <div class="tl-text">${a.description}</div>
                                    <div class="tl-sub">${a.user_name || 'System'}</div>
                                </div>
                            `).join('') : '<div style="font-size:12px;color:#6B7A99;text-align:center;">No recent activity.</div>'}
                        </div>
                    </div>

                    <div class="col-4 panel">
                        <div class="panel-header">
                            <div class="panel-title"><i class="fa-solid fa-book" style="color:#3B82F6;"></i> Library Desk</div>
                            <button class="pill green" style="border:none;cursor:pointer;">+ Issue</button>
                        </div>
                        <div class="panel-sub" style="margin-bottom:12px;">RECENT ISSUES TODAY</div>
                        <div>
                            ${s.today_library_issues && s.today_library_issues.length ? s.today_library_issues.map((li, i) => {
                                let c = ['bg-green-soft','bg-blue-soft','bg-red-soft','bg-amber-soft'][i%4];
                                return `
                                <div class="list-item">
                                    <div style="width:32px;height:40px;border-radius:4px;" class="${c} flex items-center justify-center">
                                        <i class="fa-solid fa-book" style="font-size:12px;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="li-title">${li.book_title.length>15 ? li.book_title.substring(0,15)+'...' : li.book_title}</div>
                                        <div class="li-sub">${li.student_name} · Due ${li.due_date}</div>
                                    </div>
                                    <span class="pill green">Issued</span>
                                </div>`;
                            }).join('') : '<div style="font-size:12px;color:#6B7A99;text-align:center;">No issues today.</div>'}
                        </div>
                    </div>
                </div>
            </div>`;
        } catch (e) {
            mainContent.innerHTML = `<div class="alert alert-danger">Failed to load dashboard: ${e.message}</div>`;
        }
    }


    // ═══════════════════════════════════════════════════════════════
    // FEE OUTSTANDING MODULE - Fetch real data from database
    // ═══════════════════════════════════════════════════════════════
    window.renderFeeOutstanding = async function() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="javascript:goNav('dashboard')">Dashboard</a>
                    <span class="bc-sep">/</span>
                    <a href="javascript:goNav('fee','fee-coll')">Fee Collection</a>
                    <span class="bc-sep">/</span>
                    <span class="bc-cur">Outstanding Dues</span>
                </div>
                <div class="pg-head" style="display:flex; align-items:center; gap:14px;">
                    <div class="sc-ico ic-red" style="width:44px; height:44px; font-size:20px;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <div class="pg-title">Outstanding Dues</div>
                        <div class="pg-sub">Students with pending fee payments</div>
                    </div>
                </div>
                
                <!-- Filter Bar -->
                <div class="card mb" style="padding:15px;">
                    <div style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
                        <input type="text" id="outstandingSearch" class="form-control" placeholder="Search student name..." 
                               style="max-width:250px;" onkeyup="filterOutstanding()">
                        <select id="outstandingCourseFilter" class="form-control" style="max-width:200px;" onchange="filterOutstanding()">
                            <option value="">All Courses</option>
                        </select>
                        <select id="outstandingStatusFilter" class="form-control" style="max-width:150px;" onchange="filterOutstanding()">
                            <option value="">All Status</option>
                            <option value="overdue">Overdue</option>
                            <option value="pending">Pending</option>
                        </select>
                        <button class="btn bs" onclick="loadOutstandingData()" style="margin-left:auto;">
                            <i class="fa-solid fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Outstanding Summary Cards -->
                <div class="sg mb" id="outstandingSummary">
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-red"><i class="fa-solid fa-users"></i></div></div>
                        <div class="sc-val"><div class="skeleton" style="width:40px;height:30px;" id="totalStudentsCount"></div></div>
                        <div class="sc-lbl">Students with Dues</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-money-bill"></i></div></div>
                        <div class="sc-val"><div class="skeleton" style="width:60px;height:30px;" id="totalOutstandingAmount"></div></div>
                        <div class="sc-lbl">Total Outstanding</div>
                    </div>
                    <div class="sc">
                        <div class="sc-top"><div class="sc-ico ic-red"><i class="fa-solid fa-calendar-xmark"></i></div></div>
                        <div class="sc-val"><div class="skeleton" style="width:40px;height:30px;" id="overdueCount"></div></div>
                        <div class="sc-lbl">Overdue Payments</div>
                    </div>
                </div>

                <!-- Outstanding List -->
                <div class="tw" id="outstandingContainer">
                    <div style="padding:20px;">
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                    </div>
                </div>
            </div>
        `;

        await loadOutstandingData();
    };

    let outstandingDataCache = [];
    let outstandingCoursesCache = [];

    async function loadOutstandingData() {
        const container = document.getElementById('outstandingContainer');
        // Keep skeletons visible during load

        try {
            // Fetch outstanding fees from the API
            const res = await fetch(APP_URL + '/api/frontdesk/fees?action=get_outstanding', getHeaders());
            const result = await res.json();

            if (!result.success) {
                container.innerHTML = `<div class="alert alert-danger">Error: ${result.message || 'Failed to load data'}</div>`;
                return;
            }

            outstandingDataCache = result.data || [];
            
            // Calculate summary statistics
            const totalStudents = outstandingDataCache.length;
            const totalOutstanding = outstandingDataCache.reduce((sum, item) => {
                return sum + (parseFloat(item.total_due || 0) - parseFloat(item.total_paid || 0));
            }, 0);
            
            // Count overdue (due date passed)
            const today = new Date().toISOString().split('T')[0];
            const overdueCount = outstandingDataCache.filter(item => item.due_date && item.due_date < today).length;

            // Update summary cards
            document.getElementById('totalStudentsCount').textContent = totalStudents;
            document.getElementById('totalOutstandingAmount').textContent = 'NPR ' + formatMoney(totalOutstanding);
            document.getElementById('overdueCount').textContent = overdueCount;

            // Populate course filter
            const courseFilter = document.getElementById('outstandingCourseFilter');
            const uniqueCourses = [...new Set(outstandingDataCache.map(d => d.course_id).filter(Boolean))];
            
            // Keep the "All Courses" option and add new ones
            courseFilter.innerHTML = '<option value="">All Courses</option>';
            uniqueCourses.forEach(courseId => {
                const courseData = outstandingDataCache.find(d => d.course_id === courseId);
                if (courseData && courseData.course_name) {
                    courseFilter.innerHTML += `<option value="${courseId}">${courseData.course_name}</option>`;
                    outstandingCoursesCache.push({ id: courseId, name: courseData.course_name });
                }
            });

            renderOutstandingTable(outstandingDataCache);

        } catch (e) {
            console.error('Error loading outstanding data:', e);
            container.innerHTML = `<div class="alert alert-danger">Error loading data: ${e.message}</div>`;
        }
    }

    function renderOutstandingTable(data) {
        const container = document.getElementById('outstandingContainer');

        if (!data || data.length === 0) {
            container.innerHTML = `
                <div style="padding:60px; text-align:center; color:var(--text-light);">
                    <i class="fa-solid fa-check-circle" style="font-size:3rem; margin-bottom:15px; color:var(--green);"></i>
                    <h3>No Outstanding Dues</h3>
                    <p>All students are up to date with their fee payments!</p>
                </div>
            `;
            return;
        }

        const today = new Date().toISOString().split('T')[0];

        let html = `
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th style="text-align:right;">Total Due</th>
                        <th style="text-align:right;">Paid</th>
                        <th style="text-align:right;">Balance</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.forEach(item => {
            const balance = parseFloat(item.total_due || 0) - parseFloat(item.total_paid || 0);
            const isOverdue = item.due_date && item.due_date < today;
            const statusClass = isOverdue ? 'bg-r' : 'bg-y';
            const statusText = isOverdue ? 'Overdue' : 'Pending';

            html += `
                <tr>
                    <td>
                        <div style="font-weight:600;">${item.student_name || 'N/A'}</div>
                        <div class="sub-txt">ID: ${item.student_id || 'N/A'}</div>
                    </td>
                    <td>${item.course_name || 'N/A'}</td>
                    <td style="text-align:right; font-family:monospace;">NPR ${formatMoney(item.total_due || 0)}</td>
                    <td style="text-align:right; font-family:monospace; color:var(--green);">NPR ${formatMoney(item.total_paid || 0)}</td>
                    <td style="text-align:right; font-family:monospace; font-weight:700; color:var(--red);">NPR ${formatMoney(balance)}</td>
                    <td style="text-align:center;">
                        <span class="tag ${statusClass}">${statusText}</span>
                    </td>
                    <td style="text-align:center;">
                        <button class="btn bt" style="padding:6px 12px; font-size:12px;" onclick="collectPayment(${item.student_id}, '${item.student_name?.replace(/'/g, "\\'")}')"
                            ><i class="fa-solid fa-hand-holding-dollar"></i> Collect
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    window.filterOutstanding = function() {
        const searchTerm = document.getElementById('outstandingSearch')?.value?.toLowerCase() || '';
        const courseFilter = document.getElementById('outstandingCourseFilter')?.value || '';
        const statusFilter = document.getElementById('outstandingStatusFilter')?.value || '';
        const today = new Date().toISOString().split('T')[0];

        const filtered = outstandingDataCache.filter(item => {
            const matchSearch = !searchTerm || (item.student_name && item.student_name.toLowerCase().includes(searchTerm));
            const matchCourse = !courseFilter || item.course_id == courseFilter;
            
            let matchStatus = true;
            if (statusFilter === 'overdue') {
                matchStatus = item.due_date && item.due_date < today;
            } else if (statusFilter === 'pending') {
                matchStatus = !item.due_date || item.due_date >= today;
            }

            return matchSearch && matchCourse && matchStatus;
        });

        renderOutstandingTable(filtered);
    };

    window.collectPayment = function(studentId, studentName) {
        // Navigate to fee collection page with student pre-selected
        window.location.href = APP_URL + '/dash/front-desk/fee-collect?student_id=' + studentId;
    };

    function formatMoney(amount) {
        return parseFloat(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ═══════════════════════════════════════════════════════════════
    // RECEIPT HISTORY MODULE
    // ═══════════════════════════════════════════════════════════════
    window.renderFeeReceipts = async function() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="javascript:goNav('dashboard')">Dashboard</a>
                    <span class="bc-sep">/</span>
                    <a href="javascript:goNav('fee','fee-coll')">Fee Collection</a>
                    <span class="bc-sep">/</span>
                    <span class="bc-cur">Receipt History</span>
                </div>
                <div class="pg-head" style="display:flex; align-items:center; gap:14px;">
                    <div class="sc-ico ic-blue" style="width:44px; height:44px; font-size:20px;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div style="flex:1;">
                        <div class="pg-title">Receipt History</div>
                        <div class="pg-sub">Recent fee payments and transactions</div>
                    </div>
                    <button class="btn bs" onclick="renderRecentPayments()"><i class="fa-solid fa-refresh"></i> Refresh</button>
                </div>

                <div class="tw" id="historyContainer">
                    <div style="padding:20px;">
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                        <div class="skeleton" style="height:40px; width:100%; margin-bottom:12px;"></div>
                    </div>
                </div>
            </div>
        `;

        try {
            const res = await fetch(APP_URL + '/api/frontdesk/fees?action=get_recent_payments', getHeaders());
            const result = await res.json();
            const container = document.getElementById('historyContainer');

            if (result.success && Array.isArray(result.data)) {
                if (result.data.length === 0) {
                    container.innerHTML = '<div style="padding:40px; text-align:center; color:#94a3b8;">No recent payments found.</div>';
                    return;
                }

                container.innerHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Receipt No</th>
                                <th style="text-align:right;">Amount</th>
                                <th style="text-align:center;">Method</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${result.data.map(p => `
                                <tr>
                                    <td>${formatDate(p.payment_date)}</td>
                                    <td>
                                        <div style="font-weight:600;">${p.student_name}</div>
                                        <div class="sub-txt">${p.roll_no}</div>
                                    </td>
                                    <td style="font-family:monospace;">${p.receipt_no}</td>
                                    <td style="text-align:right; font-weight:700; color:#10B981;">Rs. ${formatMoney(p.amount_paid)}</td>
                                    <td style="text-align:center;"><span class="tag bg-t">${p.payment_mode}</span></td>
                                    <td style="text-align:center;">
                                        <div style="display:flex; justify-content:center; gap:6px;">
                                            <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="goNav('fee', 'fee-details', '&receipt_no=${p.receipt_no}')" title="View Details">
                                                <i class="fa-solid fa-eye" style="color:#6366F1;"></i>
                                            </button>
                                            <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="window.open('${APP_URL}/api/frontdesk/fees?action=generate_receipt_html&is_pdf=1&receipt_no=${p.receipt_no}', '_blank')" title="Print">
                                                <i class="fa-solid fa-print" style="color:#64748b;"></i>
                                            </button>
                                            <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="emailReceiptFromHistory('${p.receipt_no}', this)" title="Email Receipt">
                                                <i class="fa-solid fa-envelope" style="color:#3B82F6;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        } catch (e) {
            document.getElementById('historyContainer').innerHTML = '<div class="alert alert-danger">Failed to load history</div>';
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PARTIAL FETCH RENDERERS (RECEPTION & MISC)
    // ═══════════════════════════════════════════════════════════════

    async function renderVisitorLog() {
        mainContent.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Visitor Log...</span></div></div>`;
        try {
            const res = await fetch(`${APP_URL}/dash/front-desk/visitor-log?partial=true`);
            const html = await res.text();
            mainContent.innerHTML = `<div class="pg fu">${html}</div>`;
            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(s => eval(s.innerHTML));
        } catch (e) { mainContent.innerHTML = `<div class="alert alert-danger">Error loading visitor log</div>`; }
    }

    async function renderAppointmentSchedule() {
        mainContent.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Appointments...</span></div></div>`;
        try {
            const res = await fetch(`${APP_URL}/dash/front-desk/appointment-schedule?partial=true`);
            const html = await res.text();
            mainContent.innerHTML = `<div class="pg fu">${html}</div>`;
            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(s => eval(s.innerHTML));
        } catch (e) { mainContent.innerHTML = `<div class="alert alert-danger">Error loading appointments</div>`; }
    }

    async function renderCallLog() {
        mainContent.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Call Log...</span></div></div>`;
        try {
            const res = await fetch(`${APP_URL}/dash/front-desk/call-log?partial=true`);
            const html = await res.text();
            mainContent.innerHTML = `<div class="pg fu">${html}</div>`;
            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(s => eval(s.innerHTML));
        } catch (e) { mainContent.innerHTML = `<div class="alert alert-danger">Error loading call logs</div>`; }
    }

    async function renderComplaints() {
        mainContent.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Complaints...</span></div></div>`;
        try {
            const res = await fetch(`${APP_URL}/dash/front-desk/complaints?partial=true`);
            const html = await res.text();
            mainContent.innerHTML = `<div class="pg fu">${html}</div>`;
            const innerScripts = mainContent.querySelectorAll('script');
            innerScripts.forEach(s => eval(s.innerHTML));
        } catch (e) { mainContent.innerHTML = `<div class="alert alert-danger">Error loading complaints</div>`; }
    }

    // ═══════════════════════════════════════════════════════════════
    // MISC UTILITIES
    // ═══════════════════════════════════════════════════════════════

    async function handleHeaderSearch(query) {
        if (!query || query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        try {
            const res = await fetch(`${APP_URL}/api/frontdesk/students?action=search&query=${encodeURIComponent(query)}`, getHeaders());
            const data = await res.json();
            
            if (!data.success) return;

            let html = '<div class="search-res-list">';
            if (data.data && data.data.length > 0) {
                html += '<div class="search-cat">Students</div>';
                data.data.forEach(s => {
                    html += `<div class="search-res-item" onclick="goNav('students'); closeSearch();">
                        <div class="res-main">${s.full_name}</div>
                        <div class="res-sub">${s.roll_no || ''} • ${s.phone || ''}</div>
                    </div>`;
                });
            } else {
                html += '<div style="padding:15px; text-align:center; color:#94a3b8;">No matches found</div>';
            }
            html += '</div>';
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        } catch (e) { console.error('Search error:', e); }
    }

    window.focusSearch = () => {
        if (hdrSearch) hdrSearch.focus();
    };

    window.closeSearch = () => {
        if (hdrSearch) hdrSearch.value = '';
        if (searchResults) searchResults.style.display = 'none';
    };

    window.renderFeeDetails = async function() {
        mainContent.innerHTML = `<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Details...</span></div></div>`;
        try {
            const res = await fetch(`${APP_URL}/dash/front-desk/fee-details?partial=true`);
            const html = await res.text();
            mainContent.innerHTML = `<div class="pg fu">${html}</div>`;
            const innerScripts = mainContent.querySelectorAll('script');
            innerScripts.forEach(s => eval(s.innerHTML));
        } catch (e) {
            mainContent.innerHTML = `<div class="alert alert-danger">Error loading details</div>`;
        }
    }

    // ── SEARCH & PROFILE DROPDOWN logic ──
    const userChip = document.getElementById('userChip');
    const userDropdown = document.getElementById('userDropdown');
    const hdrSearch = document.getElementById('hdrSearch');
    const searchResults = document.getElementById('searchResults');

    // Profile Dropdown Toggle
    if (userChip && userDropdown) {
        userChip.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = userDropdown.style.display === 'none';
            userDropdown.style.display = isHidden ? 'block' : 'none';
        });

        // Click outside to close
        document.addEventListener('click', () => {
            userDropdown.style.display = 'none';
        });

        userDropdown.addEventListener('click', (e) => e.stopPropagation());
    }

    // Global Search Logic
    let searchTimeout = null;
    if (hdrSearch && searchResults) {
        hdrSearch.addEventListener('input', (e) => {
            const q = e.target.value.trim();
            clearTimeout(searchTimeout);

            if (q.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(async () => {
                handleHeaderSearch(q);
            }, 300);
        });

        // Hide search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!hdrSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    // Init
    renderSidebar();
    renderPage();
});
