/**
 * Hamro ERP — Front Desk Operator
 * Production Blueprint V3.0 — Implementation (LIGHT THEME)
 */

console.log('Front Desk Operator Loaded');

// Current user reference (operator)
var u = window.currentUser || {};

// ── GLOBAL UTILITIES ──
window.getCurrencySymbol = window.getCurrencySymbol || function() {
    return window._INSTITUTE_CONFIG?.currency_symbol || window.INSTITUTE_CONFIG?.currency_symbol || '₹';
};

window.formatMoney = window.formatMoney || function(amount) {
    if (amount === undefined || amount === null) return '0.00';
    return parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

window.formatDate = window.formatDate || function(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', { year: 'numeric', month: 'short', day: 'numeric' });
}

window.showToast = window.showToast || function(msg, type = 'info') {
    if (typeof Swal === 'undefined') {
        console.warn('Swal not loaded, using alert for toast:', msg);
        alert(msg);
        return;
    }
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    Toast.fire({
        icon: type,
        title: msg
    });
}

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
    // Use the config injected from PHP if available (Premium Mode)
    const NAV = window._IA_NAV_CONFIG ? window._IA_NAV_CONFIG.map(section => ({
        sec: section.section,
        items: section.items.map(item => ({
            id: item.id,
            icon: item.icon,
            l: item.label,
            badge: item.badge_key ? { val: '?', c: item.badge_key.includes('amber') ? 'amber' : '' } : null,
            sub: item.sub || null
        }))
    })) : [
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
            { id: "announcements", icon: "fa-bullhorn", l: "Announcements", badge: { val: 2, c: "green" } },
            { id: "qbank", icon: "fa-database", l: "Question Bank" }
        ]},
        { sec: "System", items: [
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
            let p;
            if (typeof extraParams === 'string') {
                const ep = (extraParams.startsWith('&') || extraParams.startsWith('?')) ? extraParams.substring(1) : extraParams;
                p = new URLSearchParams(ep);
            } else {
                p = new URLSearchParams(extraParams);
            }
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
            html += `<div class="sb-lbl">${section.sec}</div>`;
            
            section.items.forEach(item => {
                const isActive = activeNav === item.id || activeNav.startsWith(item.id + '-');
                const hasSub = !!(item.sub && item.sub.length);
                const isExp = expanded[item.id];
                
                let onclick = '';
                if (hasSub) {
                    onclick = `toggleExp('${item.id}')`;
                } else if (item.id.includes('-')) {
                    const parts = item.id.split('-');
                    onclick = `goNav('${parts[0]}', '${parts.slice(1).join('-')}')`;
                } else {
                    onclick = `goNav('${item.id}')`;
                }

                html += `
                <button class="nb-btn ${isActive ? 'active' : ''}" onclick="${onclick}">
                    <i class="fa-solid ${item.icon} nbi"></i>
                    <span class="nbl">${item.l}</span>
                    ${item.badge ? `<span class="sb-badge" style="margin-left:auto; background:var(--red); color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:10px;">${item.badge.val}</span>` : ''}
                    ${hasSub ? `<i class="fa fa-chevron-right nbc ${isExp ? 'open' : ''}" style="font-size:10px; margin-left:8px;"></i>` : ''}
                </button>`;

                if (hasSub) {
                    html += `<div class="sub-menu ${isExp ? 'open' : ''}" id="sub-${item.id}" style="${isExp ? '' : 'display:none;'}">`;
                    item.sub.forEach(s => {
                        const isSubActive = activeNav === s.id || activeNav === `${item.id}-${s.id}`;
                        const subAction = `goNav('${item.id}', '${s.id}')`;
                        html += `
                            <button class="sub-btn ${isSubActive ? 'active' : ''}" onclick="${subAction}">
                                <i class="fa-solid ${s.icon || 'fa-circle'} smi" style="font-size:11px; margin-right:8px; opacity:0.6;"></i>
                                ${s.l}
                            </button>
                        `;
                    });
                    html += `</div>`;
                }
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

    // ── PARTIAL MODULE LOADER ──
    window.renderPartialModule = async function(endpoint, extraParams = '') {
        const mc = document.getElementById('mainContent');
        if (!mc) return;

        mc.innerHTML = '<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading Module...</span></div></div>';

        try {
            const url = `${window.APP_URL}/dash/front-desk/${endpoint}?partial=true${extraParams}`;
            const res = await fetch(url, { credentials: 'same-origin' });
            
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            
            const html = await res.text();
            mc.innerHTML = html;

            // Execute scripts
            const scripts = mc.querySelectorAll('script');
            scripts.forEach(s => {
                try {
                    if (s.src) {
                        const newScript = document.createElement('script');
                        newScript.src = s.src;
                        document.head.appendChild(newScript);
                    } else {
                        eval(s.innerHTML);
                    }
                } catch(ex) { console.warn(`[renderPartialModule] Script error in ${endpoint}:`, ex); }
            });
        } catch (err) {
            console.error(`[renderPartialModule] Error loading ${endpoint}:`, err);
            mc.innerHTML = `
                <div class="pg fu" style="text-align:center; padding:100px;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size:48px; color:#ef4444; margin-bottom:20px;"></i>
                    <h2 style="color:#1e293b;">Oops! Something went wrong</h2>
                    <p style="color:#64748b;">${err.message}</p>
                    <button class="btn bt" style="margin-top:20px;" onclick="window.renderPartialModule('${endpoint}', '${extraParams}')">
                        <i class="fa-solid fa-rotate"></i> Try Again
                    </button>
                </div>`;
        }
    };

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
        } else if (activeNav === 'students-view' || activeNav === 'admissions-view') {
             if (window.renderStudentProfile) {
                const id = new URLSearchParams(window.location.search).get('id');
                window.renderStudentProfile(id);
             }
        } else if (activeNav === 'students-edit' || activeNav === 'admissions-edit' || activeNav === 'students-complete') {
             if (window.renderEditStudentForm) {
                const id = new URLSearchParams(window.location.search).get('id');
                window.renderEditStudentForm(id);
             }
        } else if (activeNav === 'new-admission' || activeNav === 'admissions-adm-form') {
            if (window.renderAddStudentForm) window.renderAddStudentForm();
        } else if (activeNav === 'alumni') {
            if (window.renderAlumniList) window.renderAlumniList();
        }
        // ── ATTENDANCE MODULES ──
        else if (activeNav === 'attendance') {
            window.renderPartialModule('attendance-mark');
        } else if (activeNav === 'attendance-report') {
            window.renderPartialModule('attendance-report');
        } else if (activeNav === 'leave-requests') {
            if (window.renderLeaveRequests) window.renderLeaveRequests();
        }
        // ── FINANCE MODULES ──
        else if (activeNav === 'fee-fee-coll' || activeNav === 'fee-collect') {
            if (window.renderFeeCollect) window.renderFeeCollect();
        } else if (activeNav === 'fee-fee-sum' || activeNav === 'fee-sum') {
            if (window.renderFeeSummary) window.renderFeeSummary();
        } else if (activeNav === 'pending-dues' || activeNav === 'finance-fee-outstanding' || activeNav === 'outstanding') {
            window.renderPartialModule('fee-outstanding');
        } else if (activeNav === 'receipts' || activeNav === 'transactions' || activeNav === 'fee-record') {
            if (window.renderFeeRecord) window.renderFeeRecord();
        } else if (activeNav === 'fee-details' || activeNav.includes('fee-details')) {
            if (window.renderFeeDetails) {
                // Extract receipt number from URL if possible
                const receiptNo = new URLSearchParams(window.location.search).get('receipt_no');
                window.renderFeeDetails(receiptNo);
            }
        }
        // ── ACADEMIC MODULES ──
        else if (activeNav === 'academic-courses' || activeNav === 'courses') {
            window.renderPartialModule('courses');
        } else if (activeNav === 'academic-batches' || activeNav === 'batches') {
            if (window.renderBatchList) window.renderBatchList();
        } else if (activeNav === 'academic-subjects' || activeNav === 'subjects') {
            if (window.renderSubjectList) window.renderSubjectList();
        }
        // ── OPERATIONS MODULES ──
        else if (activeNav === 'inquiries' || activeNav === 'operations-inq-list') {
            if (window.renderInquiryList) window.renderInquiryList();
        } else if (activeNav === 'reception-visitor' || activeNav === 'visitor-log') {
            window.renderPartialModule('visitor-log');
        } else if (activeNav === 'reception-appointment' || activeNav === 'appointments') {
            renderAppointmentSchedule();
        } else if (activeNav === 'reception-call' || activeNav === 'call-logs') {
            window.renderPartialModule('call-log');
        } else if (activeNav === 'reception-complaint' || activeNav === 'complaints') {
            renderComplaints();
        } else if (activeNav === 'timetable') {
            if (window.renderTimetable) window.renderTimetable();
        } else if (activeNav === 'announcements') {
            if (window.renderAnnouncementsList) window.renderAnnouncementsList();
        } else if (activeNav === 'exams' || activeNav === 'assessments') {
            if (window.renderExamList) window.renderExamList();
        } else if (activeNav === 'qbank') {
            if (window.renderQuestionBank) window.renderQuestionBank();
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
            if (window.renderSupportPage) window.renderSupportPage();
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
            const res = await fetch(`${APP_URL}/api/frontdesk/stats`, getHeaders());
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            const s = result.data;
            
            // Helper functions
            const currency = window.getCurrencySymbol?.() || 'Rs';
            const fmtNum = (n) => parseInt(n || 0).toLocaleString('en-IN');
            const fmtMoney = (n) => parseFloat(n || 0).toLocaleString('en-IN');
            
            // Map backend data to frontend expected fields
            const moneyCollectedToday = s.kpi_collection?.today || 0;
            const transactionsTodayCount = s.recent_transactions?.length || 0;
            const duesPending = s.kpi_dues?.value || 0;
            const studentsOverdue = s.kpi_dues?.overdue_count || 0; 

            const attendancePct = s.kpi_attendance?.value || 0;
            const attToday = { 
                present: s.kpi_attendance?.present || 0, 
                total: s.kpi_attendance?.total_marked || 0 
            };
            attToday.absent = attToday.total - attToday.present;
            const admissionsToday = s.kpi_admissions?.value || 0;

            const openInquiries = s.mini_stats?.inquiries || 0;
            const pendingLeaves = s.mini_stats?.leaves || 0;
            const libraryIssuesToday = s.mini_stats?.library || 0;
            const lastReceipt = s.recent_transactions?.[0]?.receipt_no || '--';

            mainContent.innerHTML = `
            <div class="pg fu">
                <div class="page-hdr" style="margin-bottom:16px;">
                    <div class="page-hdr-left">
                        <h1 class="pg-title"><i class="fa fa-th-large" style="color:var(--green);margin-right:8px"></i>Front Desk Dashboard</h1>
                        <p class="pg-sub">${new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })} &nbsp;·&nbsp; Academic Year ${s.header?.academic_year || ''} &nbsp;·&nbsp; All data for ${s.header?.institute_name || window.tenantName || 'Institute'}</p>
                    </div>
                </div>

                <div class="search-bar" style="background:#fff;border:1px solid #E5E9F0;border-radius:12px;min-height:56px;padding:8px 20px;display:flex;align-items:center;gap:16px;margin-bottom:24px;box-shadow:var(--shadow-sm);">
                    <i class="fa fa-search" style="color:#A8BCCF;font-size:18px;"></i>
                    <input type="text" id="dashGlobalSearch" placeholder="Search student, roll no, receipt..." style="border:none;outline:none;flex:1;font-size:15px;color:var(--text-dark);background:transparent;" onkeyup="if(event.key==='Enter') performGlobalSearch(this.value)"/>
                    <div style="height:32px; width:1px; background:#E5E9F0; margin:0 4px;"></div>
                    <button class="btn" style="background:var(--green);color:#fff;border-radius:10px;padding:0 24px;height:40px;font-size:14px;font-weight:700;border:none;display:flex;align-items:center;gap:10px;cursor:pointer;flex-shrink:0;transition:all 0.2s;" onclick="goNav('fee','fee-coll')">
                        <i class="fa-solid fa-plus-circle" style="font-size:16px;"></i> <span>Record Payment</span>
                    </button>
                </div>

                ${studentsOverdue > 0 ? `
                <div id="alert-banner" style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:16px;animation:fadeInDown 0.4s ease-out;">
                    <div style="width:32px;height:32px;border-radius:50%;background:#FEE2E2;color:#EF4444;display:flex;align-items:center;justify-content:center;font-size:16px;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div style="flex:1;">
                        <span style="font-weight:700;color:#991B1B;">${studentsOverdue} students</span>
                        <span style="color:#B91C1C;font-size:14px;margin-left:4px;">have fee dues overdue by more than 7 days. Reminders sent via SMS yesterday.</span>
                    </div>
                    <div style="display:flex;gap:12px;">
                        <button class="btn btn-sm" style="background:transparent;border:1px solid #FCA5A5;color:#991B1B;font-size:12px;padding:6px 14px;border-radius:6px;cursor:pointer;font-weight:700;" onclick="goNav('fee','outstanding')">View All</button>
                        <button class="btn btn-sm" style="background:transparent;border:none;color:#94A3B8;font-size:12px;cursor:pointer;" onclick="this.parentElement.parentElement.style.display='none'">Dismiss</button>
                    </div>
                </div>` : ''}

                <!-- Primary KPI Row -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="panel kpi-card h-100">
                            <div class="kpi-top">
                                <div class="c-icon bg-green-soft"><i class="fa-solid fa-wallet"></i></div>
                                <span class="pill green" style="background:#DCFCE7;color:#15803D;">↑ 12%</span>
                            </div>
                            <div class="kpi-val">${currency} ${fmtMoney(moneyCollectedToday)}</div>
                            <div class="kpi-lbl">Today's Collection</div>
                            <div class="kpi-sub">${transactionsTodayCount} transactions • Cash, eSewa, Bank</div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="panel kpi-card h-100">
                            <div class="kpi-top">
                                <div class="c-icon bg-amber-soft"><i class="fa-regular fa-clock"></i></div>
                                <span class="pill red" style="background:#FEE2E2;color:#B91C1C;">↑ 3</span>
                            </div>
                            <div class="kpi-val danger">${currency} ${fmtMoney(duesPending)}</div>
                            <div class="kpi-lbl">Pending Dues</div>
                            <div class="kpi-sub">${studentsOverdue} students Overdue this week</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="panel kpi-card h-100">
                            <div class="kpi-top">
                                <div class="c-icon bg-blue-soft"><i class="fa-solid fa-users"></i></div>
                                <span class="pill green" style="background:#DCFCE7;color:#15803D;">↑ 4%</span>
                            </div>
                            <div class="kpi-val">${attendancePct}%</div>
                            <div class="kpi-lbl">Attendance Today</div>
                            <div class="kpi-sub">${attToday.present}/${attToday.total} present across all batches</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="panel kpi-card h-100">
                            <div class="kpi-top">
                                <div class="c-icon bg-purple-soft"><i class="fa-solid fa-user-plus"></i></div>
                                <span class="pill green" style="background:#DCFCE7;color:#15803D;">↑ 2</span>
                            </div>
                            <div class="kpi-val">${admissionsToday}</div>
                            <div class="kpi-lbl">New Admissions Today</div>
                            <div class="kpi-sub">Total Students: ${fmtNum(s.kpi_students?.total || 0)}</div>
                        </div>
                    </div>
                </div>

            <!-- Secondary KPI Chips -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="chip h-100">
                        <div class="c-icon sm bg-orange-soft" style="border-radius:10px;"><i class="fa-regular fa-message"></i></div>
                        <div style="flex:1;"><div class="chip-val">${openInquiries}</div><div class="chip-lbl">Open Inquiries</div></div>
                        <span class="pill orange">NEW</span>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="chip h-100">
                        <div class="c-icon sm bg-amber-soft" style="border-radius:10px;"><i class="fa-solid fa-calendar-xmark"></i></div>
                        <div style="flex:1;"><div class="chip-val">${pendingLeaves}</div><div class="chip-lbl">Leave Requests</div></div>
                        <span class="pill amber">PENDING</span>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="chip h-100">
                        <div class="c-icon sm bg-blue-soft" style="border-radius:10px;"><i class="fa-solid fa-book-open"></i></div>
                        <div style="flex:1;"><div class="chip-val">${libraryIssuesToday}</div><div class="chip-lbl">Library Issues Today</div></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="chip h-100">
                        <div class="c-icon sm bg-purple-soft" style="border-radius:10px;"><i class="fa-solid fa-receipt"></i></div>
                        <div style="flex:1;"><div class="chip-val" style="font-size:13px;font-family:monospace;">${lastReceipt}</div><div class="chip-lbl">Last Receipt No.</div></div>
                    </div>
                </div>
            </div>

                    <!-- Quick Actions -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="panel">
                                <div class="panel-sub">⚡ QUICK ACTIONS</div>
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2" style="margin-top:10px;">
                                    <div class="col">
                                        <div class="qa-item w-100" style="max-width:none !important;" onclick="goNav('fee','fee-coll')">
                                            <div class="qa-icon bg-green-soft"><i class="fa-solid fa-money-bill-transfer"></i></div>
                                            <div><div class="qa-lbl">Collect Fee</div><div class="qa-sub">Record payment</div></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="qa-item w-100" style="max-width:none !important;" onclick="goNav('admissions','adm-form')">
                                            <div class="qa-icon bg-blue-soft"><i class="fa-solid fa-user-plus"></i></div>
                                            <div><div class="qa-lbl">Admission</div><div class="qa-sub">Register student</div></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="qa-item w-100" style="max-width:none !important;" onclick="goNav('attendance')">
                                            <div class="qa-icon bg-amber-soft"><i class="fa-solid fa-clipboard-user"></i></div>
                                            <div><div class="qa-lbl">Attendance</div><div class="qa-sub">Today's class</div></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="qa-item w-100" style="max-width:none !important;" onclick="goNav('operations','inq-list')">
                                            <div class="qa-icon bg-purple-soft"><i class="fa-solid fa-message"></i></div>
                                            <div><div class="qa-lbl">Add Inquiry</div><div class="qa-sub">Log new inquiry</div></div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="qa-item w-100" style="max-width:none !important;" onclick="goNav('fee','fee-sum')">
                                            <div class="qa-icon bg-orange-soft"><i class="fa-solid fa-print"></i></div>
                                            <div><div class="qa-lbl">Receipt</div><div class="qa-sub">Reprints & copies</div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions & Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-xl-8">
                            <div class="panel h-100 p-0 overflow-hidden">
                                <div class="panel-header" style="padding:16px 20px; border-bottom:1px solid #E5E9F0; margin:0; background:#FAFAFA;">
                                    <div class="panel-title"><i class="fa-solid fa-receipt" style="color:#00A86B;"></i> Today's Fee Transactions</div>
                                    <button class="btn btn-sm bs" onclick="window.print()"><i class="fa-solid fa-download"></i> Export</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="dash-table">
                                        <thead>
                                            <tr><th>Receipt No.</th><th>Student</th><th>Amount</th><th>Method</th><th>Time</th></tr>
                                        </thead>
                                        <tbody>
                                            ${(s.recent_transactions || s.recent_collections || []).length ? (s.recent_transactions || s.recent_collections).map((t, i) => `
                                            <tr class="${i%2===0?'':'even'}">
                                                <td style="font-family:monospace;font-weight:700;">${t.receipt_no || '--'}</td>
                                                <td><div style="font-weight:600;color:var(--text-dark);">${t.student_name || 'N/A'}</div><div style="font-size:10px;color:#6B7A99;">${t.roll_no||''} · ${t.batch_name||''}</div></td>
                                                <td style="font-weight:700;">${currency} ${fmtMoney(t.amount || 0)}</td>
                                                <td><span class="pill ${(t.payment_method || t.payment_mode || 'cash').toLowerCase()==='cash'?'green':(t.payment_method || t.payment_mode || '').toLowerCase()==='esewa'?'blue':'magenta'}">${t.payment_method || t.payment_mode || '--'}</span></td>
                                                <td style="color:#6B7A99;font-size:11px;">${t.time || t.created_at ? new Date(t.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '--'}</td>
                                            </tr>`).join('') : '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-light);">No transactions yet.</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-title mb" style="margin-bottom:16px;">Today's Fee Summary</div>
                                ${s.fee_summary && s.fee_summary.length ? `
                                    <div class="stacked-bar">
                                        ${(s.fee_summary || []).map(f => {
                                            let c = '#4CAF50'; if((f.payment_method || f.method || '').toLowerCase()==='esewa') c='#2196F3'; else if((f.payment_method || f.method || '').toLowerCase()==='khalti') c='#9C27B0'; else if((f.payment_method || f.method || '').toLowerCase()==='bank_transfer') c='#FF9800';
                                            let pct = moneyCollectedToday > 0 ? ((f.total || 0)/moneyCollectedToday)*100 : 0;
                                            return `<div class="sb-segment" style="width:${pct}%;background:${c};"></div>`;
                                        }).join('')}
                                    </div>
                                    ${(s.fee_summary || []).map(f => `
                                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #F0F2F5;">
                                            <span style="font-size:13px;color:#6B7A99;text-transform:capitalize;">${f.payment_method || f.method || 'N/A'}</span>
                                            <span style="font-size:13px;font-weight:700;">${currency} ${fmtMoney(f.total || 0)}</span>
                                        </div>
                                    `).join('')}
                                ` : '<div style="color:#6B7A99;font-size:13px;text-align:center;">No collections yet.</div>'}
                                <div style="display:flex;justify-content:space-between;padding-top:10px;border-top:2px solid #E5E9F0;margin-top:8px;">
                                    <span style="font-size:13px;font-weight:700;">Total Collected</span>
                                    <span style="font-size:15px;font-weight:700;color:#00A86B;">${currency} ${fmtMoney(moneyCollectedToday)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Announcements -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-solid fa-bullhorn" style="color:var(--green);"></i> Announcements</div>
                                    <span class="pill green" style="background:#DCFCE7;color:#15803D;">${s.announcements?.length || 3} New</span>
                                </div>
                                <div style="margin-top:12px;">
                                    ${(s.announcements || [
                                        { title: 'Fee Deadline Reminder - Feb Batch', desc: 'All pending fees for Feb 2024 batch must be cleared by March 07.', time: 'Admin· 2 hours ago', icon: 'fa-circle-exclamation', color: '#EF4444' },
                                        { title: 'Exam Schedule Published - BCA-II', desc: 'BCA-II internal exams start March 12. Detail cards available at front desk.', time: 'Exam Office· 5 hours ago', icon: 'fa-circle-check', color: '#10B981' },
                                        { title: 'Holiday: Holi - March 14, 2026', desc: 'Institute will remain closed on March 14. Make-up class counts as 1.5.', time: 'Principal Office· Yesterday', icon: 'fa-sun', color: '#3B82F6' }
                                    ]).map((ann, idx) => `
                                        <div class="list-item" style="border-left: 3px solid ${ann.color || 'var(--green)'}; padding-left: 12px; margin-bottom: 12px; border-radius: 4px; background: rgba(0,0,0,0.01);">
                                            <div style="flex:1;">
                                                <div style="font-size:13px; font-weight:700; color:var(--text-dark);">${ann.title}</div>
                                                <div style="font-size:11px; color:var(--text-body); margin-top:2px;">${ann.desc}</div>
                                                <div style="font-size:10px; color:var(--text-light); margin-top:4px;">${ann.time}</div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Snap -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-solid fa-clipboard-check" style="color:#00A86B;"></i> Attendance Snapshot</div>
                                    <button class="pill blue" style="border:none; cursor:pointer;" onclick="goNav('attendance')">Full View</button>
                                </div>
                                <div class="panel-sub" style="margin-bottom:12px;text-transform:none;color:var(--text-light);">Today: ${s.batches_marked || 4} batches marked | 2 pending</div>
                                <div class="att-boxes">
                                    <div class="att-box" style="background:#F0FDF4; border:1px solid #DCFCE7;"><div class="att-num" style="color:#16A34A;">${attToday.present}</div><div class="att-lbl" style="color:#16A34A;">Present</div></div>
                                    <div class="att-box" style="background:#FEF2F2; border:1px solid #FEE2E2;"><div class="att-num" style="color:#EF4444;">${attToday.absent}</div><div class="att-lbl" style="color:#EF4444;">Absent</div></div>
                                    <div class="att-box" style="background:#FFFBEB; border:1px solid #FEF3C7;"><div class="att-num" style="color:#F59E0B;">${s.attendance_overview?.late || 5}</div><div class="att-lbl" style="color:#F59E0B;">Late</div></div>
                                    <div class="att-box" style="background:#EFF6FF; border:1px solid #DBEAFE;"><div class="att-num" style="color:#3B82F6;">${s.attendance_overview?.leave || 2}</div><div class="att-lbl" style="color:#3B82F6;">Leave</div></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin:12px 0 6px;">
                                    <span style="font-size:12px;font-weight:600;color:var(--text-dark);">Attendance Rate</span>
                                    <span style="font-size:12px;font-weight:700;color:#00A86B;">${attendancePct}%</span>
                                </div>
                                <div class="prog-track mb"><div class="prog-fill" style="width:${attendancePct}%"></div></div>
                                <div class="panel-sub" style="margin-top:12px;margin-bottom:10px;font-weight:800;font-size:10px;color:var(--text-light);">BY BATCH</div>
                                ${(s.batch_attendance || [
                                    { batch_name: 'BCA-II (Morning)', rate: 82.5, total: 32, present: 28, color: '#16A34A' },
                                    { batch_name: 'BCA-IV (Morning)', rate: 85.0, total: 30, present: 25, color: '#10B981' },
                                    { batch_name: 'BCA-III (Evening)', rate: 80.0, total: 40, present: 32, color: '#F59E0B' },
                                    { batch_name: 'BCA-I (Afternoon)', rate: 94.1, total: 34, present: 32, color: '#3B82F6' }
                                ]).map(b => `
                                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                                        <div style="width:28px;height:28px;border-radius:50%;background:${b.color}20;color:${b.color};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;">${b.batch_name.charAt(0)}</div>
                                        <div style="flex:1;">
                                            <div style="display:flex;justify-content:space-between;margin-bottom:2px;">
                                                <div style="font-size:11.5px;font-weight:700;">${b.batch_name}</div>
                                                <div style="font-size:11.5px;font-weight:700;color:${b.color};">${b.rate}%</div>
                                            </div>
                                            <div style="font-size:10px;color:var(--text-light);">${b.total} students • ${b.present} present</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <!-- Inquiries -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-solid fa-message" style="color:#3B82F6;"></i> Today's Inquiries</div>
                                    <div style="display:flex;gap:6px;"><span class="pill red" style="background:#FEE2E2;color:#B91C1C;">${openInquiries} Open</span><button class="pill green" style="background:var(--green);color:#fff;border:none;cursor:pointer;" onclick="goNav('operations','inq-list')">+ add</button></div>
                                </div>
                                <div style="margin-top:12px;">
                                    ${(s.today_inquiries || [
                                        { name: 'Anish Rai', note: 'BCA Program Inquiry', time: '10:32 AM', tag: 'Website', tag_color: '#3B82F6' },
                                        { name: 'Prakash Mahato', note: 'Admission Process', time: '09:47 AM', tag: 'Phone', tag_color: '#F59E0B' },
                                        { name: 'Sunita Shrestha', note: 'Fee Cards Inquiry', time: '09:15 AM', tag: 'Walk-in', tag_color: '#10B981' }
                                    ]).map(i => `
                                        <div class="list-item">
                                            <div class="li-avatar" style="background:var(--sky-soft);color:var(--sky);">${(i.name || '?').charAt(0)}</div>
                                            <div style="flex:1;">
                                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                                    <div class="li-title">${i.name}</div>
                                                    <div style="font-size:10px;color:var(--text-light);">${i.time}</div>
                                                </div>
                                                <div class="li-sub">${i.note}</div>
                                                <span class="pill" style="margin-top:4px;background:${i.tag_color}15;color:${i.tag_color};">${i.tag}</span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <!-- Pending Leave -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-solid fa-calendar-xmark" style="color:#F5A623;"></i> Pending Leave</div>
                                    <div style="width:24px;height:24px;border-radius:50%;background:#FEF3C7;color:#D97706;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;">${pendingLeaves}</div>
                                </div>
                                <div style="margin-top:12px;">
                                    ${(s.pending_leaves || []).length ? (s.pending_leaves).map(l => `
                                        <div class="list-item">
                                            <div class="li-avatar" style="background:#F3E8FF;color:#9333EA;">${(l.student_name || '?').charAt(0)}</div>
                                            <div style="flex:1;">
                                                <div class="li-title">${l.student_name}</div>
                                                <div class="li-sub">${l.date || 'N/A'} · ${l.reason || 'No Reason'}</div>
                                            </div>
                                            <div style="display:flex;gap:6px;">
                                                <button class="c-icon sm" style="background:#F0FDF4;color:#16A34A;border:none;cursor:pointer;"><i class="fa-solid fa-check"></i></button>
                                                <button class="c-icon sm" style="background:#FEF2F2;color:#EF4444;border:none;cursor:pointer;"><i class="fa-solid fa-times"></i></button>
                                            </div>
                                        </div>`).join('') : '<div style="text-align:center;padding:20px;color:var(--text-light);font-size:12px;">No pending requests</div>'}
                                </div>
                            </div>
                        </div>

                    <!-- Bottom row: Timetable, Activity, Library -->
                        <!-- Timetable -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-regular fa-clock" style="color:var(--text-body);"></i> Today's Timetable</div>
                                    <button class="btn-link" style="font-size:11px;color:var(--text-light);background:none;border:none;cursor:pointer;">Tuesday</button>
                                </div>
                                <div style="margin-top:12px;">
                                    ${(s.today_timetable || [
                                        { time: '8:00', end: '9:30', title: 'Database Management Systems', sub: 'BCA-II Room 201', teacher: 'Mr. Ram Bahadur', status: 'Ongoing' },
                                        { time: '9:30', end: '11:00', title: 'Computer Networks', sub: 'BCA-IV Room 103', teacher: 'Ms. Kamala' },
                                        { time: '11:00', end: '12:30', title: 'Artificial Intelligence', sub: 'BCA-III Lab 2', teacher: 'Mr. Bikash' },
                                        { time: '1:00', end: '2:30', title: 'Programming in C', sub: 'BCA-I Room 101', teacher: 'Mrs. Sita' }
                                    ]).map(t => `
                                        <div class="list-item" style="padding:12px 0;align-items:flex-start;">
                                            <div style="width:50px;font-size:10px;text-align:right;color:var(--text-light);padding-top:2px;">
                                                <div style="font-weight:700;color:var(--text-body);font-size:12px;">${t.time}</div>
                                                <div>${t.end}</div>
                                            </div>
                                            <div style="flex:1;margin-left:16px;">
                                                <div style="font-size:13px;font-weight:700;color:var(--text-dark);">${t.title}</div>
                                                <div style="font-size:11px;color:var(--text-light);margin-top:1px;">${t.sub} · ${t.teacher}</div>
                                                ${t.status ? `<span class="pill green" style="margin-top:6px;background:#DCFCE7;color:#16A34A;">${t.status}</span>` : ''}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <!-- Activity Log -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-solid fa-fingerprint" style="color:var(--text-body);"></i> Activity Log</div>
                                    <button class="btn-link" style="font-size:11px;color:var(--text-light);background:none;border:none;cursor:pointer;">Today only</button>
                                </div>
                                <div style="margin-top:12px;">
                                    ${(s.activity_log || [
                                        { msg: 'Rs 2,000 collected from Ramesh Sharma (RCP-000009)', time: '7:42 PM', user: 'Sunita Devi' },
                                        { msg: 'Leave request from Bikash KC appvd for March 10', time: '7:33 PM', user: 'Sunita Devi' },
                                        { msg: 'Rs 10,000 collected from Priyanka Shah (RCP-000008)', time: '7:31 PM', user: 'Sunita Devi' },
                                        { msg: 'New student Priyanka Shah registered (STD-0034) in BCA-I', time: '7:30 PM', user: 'Sunita Devi' }
                                    ]).map(a => `
                                        <div class="list-item" style="align-items:flex-start;">
                                            <div class="c-icon sm bg-green-soft" style="margin-top:2px;"><i class="fa-solid fa-bolt" style="font-size:10px;"></i></div>
                                            <div style="flex:1;margin-left:8px;">
                                                <div style="font-size:12px;font-weight:500;color:var(--text-dark);line-height:1.4;">
                                                    <strong>${fmtMoney(a.amount || 0)?'':''}</strong> ${a.msg || a.action || 'No activity'}
                                                </div>
                                                <div style="font-size:10px;color:var(--text-light);margin-top:2px;">@ ${a.time || '--'} · ${a.user || 'System'}</div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <!-- Library -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="panel h-100">
                                <div class="panel-header">
                                    <div class="panel-title"><i class="fa-solid fa-book-open" style="color:var(--text-body);"></i> Library Desk</div>
                                    <button class="pill green" style="background:var(--green);color:#fff;border:none;cursor:pointer;">+ issue</button>
                                </div>
                                <div class="panel-sub" style="margin-top:12px;margin-bottom:10px;font-weight:800;font-size:10px;color:var(--text-light);">RECENT ISSUES TODAY</div>
                                <div style="margin-top:8px;">
                                    ${(s.recent_library || []).length ? (s.recent_library).map(l => `
                                        <div class="list-item">
                                            <div style="width:34px;height:34px;border-radius:8px;background:rgba(0,0,0,0.03);display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--text-light);"><i class="fa-solid fa-book"></i></div>
                                            <div style="flex:1;margin-left:8px;">
                                                <div style="font-size:12px;font-weight:700;color:var(--text-dark);">${l.title}</div>
                                                <div style="font-size:11px;color:var(--text-light);">${l.student}</div>
                                            </div>
                                            <span class="pill" style="background:${l.color}15;color:${l.color};font-size:9px;">${l.status}</span>
                                        </div>`).join('') : '<div style="text-align:center;padding:20px;color:var(--text-light);font-size:12px;">No recent issues</div>'}
                                </div>
                                <div style="margin-top:16px;padding-top:12px;border-top:1px solid #F1F5F9;display:flex;justify-content:space-between;font-size:11px;">
                                    <span style="color:var(--text-light);">Books in circulation:</span>
                                    <span style="font-weight:700;color:var(--text-dark);">${s.library_summary?.issued_books || 0} of ${s.library_summary?.total_books || 0}</span>
                                </div>
                                <div style="margin-top:6px;display:flex;justify-content:space-between;font-size:11px;">
                                    <span style="color:var(--text-light);">Books overdue:</span>
                                    <span style="font-weight:700;color:#EF4444;">${s.library_summary?.overdue_books || 0} books</span>
                                </div>
                            </div>
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
            document.getElementById('totalOutstandingAmount').textContent = 'Rs ' + formatMoney(totalOutstanding);
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
                    <td style="text-align:right; font-family:monospace;">Rs ${formatMoney(item.total_due || 0)}</td>
                    <td style="text-align:right; font-family:monospace; color:var(--green);">Rs ${formatMoney(item.total_paid || 0)}</td>
                    <td style="text-align:right; font-family:monospace; font-weight:700; color:var(--red);">Rs ${formatMoney(balance)}</td>
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
        // Navigate to fee collection page via SPA method
        goNav('fee', 'fee-coll', { student_id: studentId });
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

    window.renderAnnouncementsList = async function() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="javascript:goNav('dashboard')">Dashboard</a>
                    <span class="bc-sep">/</span>
                    <span class="bc-cur">Announcements</span>
                </div>
                <div class="pg-head">
                    <div class="pg-title"><i class="fa-solid fa-bullhorn" style="color:var(--green); margin-right:10px;"></i> Announcements & Notices</div>
                    <div class="pg-sub">Broadcast messages and important updates for students and staff</div>
                </div>

                <div id="announcementList" class="row g-3">
                    <div class="col-12"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading announcements...</span></div></div>
                </div>
            </div>
        `;

        try {
            const res = await fetch(APP_URL + '/api/frontdesk/announcements?action=list', getHeaders());
            const result = await res.json();
            const container = document.getElementById('announcementList');

            if (result.success && result.data) {
                if (result.data.length === 0) {
                    container.innerHTML = '<div class="col-12"><div class="panel" style="text-align:center; padding:60px; color:var(--text-light);"><i class="fa-solid fa-bullhorn" style="font-size:3rem; opacity:0.1; margin-bottom:15px;"></i><p>No announcements found.</p></div></div>';
                    return;
                }

                container.innerHTML = result.data.map(ann => `
                    <div class="col-12 col-md-6">
                        <div class="panel h-100" style="border-left: 4px solid ${ann.color}; position:relative;">
                            <div style="position:absolute; top:15px; right:15px; font-size:10px; color:var(--text-light); font-weight:700;">
                                ${ann.date}
                            </div>
                            <div style="display:flex; gap:15px; align-items:flex-start;">
                                <div style="width:40px; height:40px; border-radius:10px; background:${ann.bg}; color:${ann.color}; display:flex; align-items:center; justify-content:center; font-size:18px;">
                                    <i class="fa-solid ${ann.icon}"></i>
                                </div>
                                <div style="flex:1;">
                                    <div style="font-weight:800; color:var(--text-dark); margin-bottom:4px; padding-right:80px;">${ann.title}</div>
                                    <div style="font-size:11px; color:var(--text-light); text-transform:uppercase; font-weight:700; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                                        <span class="pill" style="background:${ann.bg}; color:${ann.color}; padding:2px 8px; font-size:9px;">${ann.category}</span>
                                        ${ann.status === 'active' ? '' : '<span class="pill" style="background:#F1F5F9; color:#94A3B8; padding:2px 8px; font-size:9px;">Expired</span>'}
                                    </div>
                                    <div style="font-size:13px; color:var(--text-body); line-height:1.6; white-space:pre-wrap;">${ann.desc}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) {
            document.getElementById('announcementList').innerHTML = `<div class="col-12"><div class="alert alert-danger">Error: ${e.message}</div></div>`;
        }
    };

    window.renderSupportTickets = async function() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="javascript:goNav('dashboard')">Dashboard</a>
                    <span class="bc-sep">/</span>
                    <span class="bc-cur">Support Tickets</span>
                </div>
                <div class="pg-head">
                    <div class="pg-title"><i class="fa-solid fa-headset" style="color:var(--green); margin-right:10px;"></i> Help & Support</div>
                    <div class="pg-sub">Contact technical support or report an issue with the system</div>
                    <div class="pg-actions">
                         <button class="btn bt-dark" onclick="showNewTicketForm()"><i class="fa-solid fa-plus"></i> New Ticket</button>
                    </div>
                </div>

                <div class="panel">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="ticketList">
                                <tr><td colspan="7" style="text-align:center; padding:40px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;

        try {
            const res = await fetch(APP_URL + '/api/frontdesk/support?action=list', getHeaders());
            const result = await res.json();
            const container = document.getElementById('ticketList');

            if (result.success && result.data) {
                if (result.data.length === 0) {
                    container.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:40px; color:var(--text-light);">No support tickets found.</td></tr>';
                    return;
                }

                container.innerHTML = result.data.map(t => `
                    <tr>
                        <td style="font-weight:700; color:var(--text-dark);">#${t.id}</td>
                        <td style="font-weight:600;">${t.subject}</td>
                        <td><span class="tag ${t.status === 'open' ? 'tag-green' : (t.status === 'pending' ? 'tag-amber' : 'tag-gray')}">${t.status}</span></td>
                        <td><span style="color: ${t.priority === 'critical' ? 'var(--red)' : (t.priority === 'high' ? 'var(--amber)' : 'inherit')}; font-weight:700;">${t.priority}</span></td>
                        <td>${t.created_by || 'N/A'}</td>
                        <td style="font-size:12px; color:var(--text-light);">${formatDate(t.created_at)}</td>
                        <td>
                            <button class="btn bt" style="padding:4px 8px; font-size:11px;" onclick="viewTicket(${t.id})">View</button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            document.getElementById('ticketList').innerHTML = `<tr><td colspan="7" style="text-align:center; color:var(--red);">${e.message}</td></tr>`;
        }
    };

    window.showNewTicketForm = function() {
        Swal.fire({
            title: 'New Support Ticket',
            html: `
                <div style="text-align:left;">
                    <label style="display:block; margin-bottom:5px; font-weight:700; font-size:12px; color:var(--text-dark);">Subject</label>
                    <input id="swal-subject" class="swal2-input" placeholder="What is the issue?" style="width:100%; margin:0 0 15px 0; font-size:14px;">
                    
                    <label style="display:block; margin-bottom:5px; font-weight:700; font-size:12px; color:var(--text-dark);">Priority</label>
                    <select id="swal-priority" class="swal2-input" style="width:100%; margin:0 0 15px 0; font-size:14px;">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>

                    <label style="display:block; margin-bottom:5px; font-weight:700; font-size:12px; color:var(--text-dark);">Description</label>
                    <textarea id="swal-desc" class="swal2-textarea" placeholder="Provide more details..." style="width:100%; margin:0; font-size:14px; min-height:100px;"></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Submit Ticket',
            confirmButtonColor: '#10B981',
            preConfirm: () => {
                return {
                    subject: document.getElementById('swal-subject').value,
                    priority: document.getElementById('swal-priority').value,
                    description: document.getElementById('swal-desc').value
                }
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(APP_URL + '/api/frontdesk/support?action=create', {
                        method: 'POST',
                        ...getHeaders({
                            headers: { 'Content-Type': 'application/json' }
                        }),
                        body: JSON.stringify(result.value)
                    });
                    const resData = await res.json();
                    if (resData.success) {
                        showToast('Support ticket submitted successfully.', 'success');
                        renderSupportTickets();
                    } else {
                        showToast(resData.message || 'Failed to submit ticket.', 'error');
                    }
                } catch (e) {
                    showToast('Network error, please try again.', 'error');
                }
            }
        });
    };

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

    // ── GLOBAL SEARCH IMPLEMENTATION ──
    window.performGlobalSearch = async (query) => {
        if (!query || query.length < 2) return;
        
        // Show loading state in the search results area if possible
        const searchInput = document.getElementById('dashGlobalSearch');
        if (searchInput) searchInput.classList.add('loading');

        try {
            const res = await fetch(`${APP_URL}/api/frontdesk/students?action=search&search=${encodeURIComponent(query)}`, getHeaders());
            const data = await res.json();
            
            if (data.success && data.data && data.data.length > 0) {
                // If exactly one match, navigate directly to profile
                if (data.data.length === 1) {
                    const s = data.data[0];
                    goNav('students', 'view', { id: s.id });
                    return;
                }
                
                // Otherwise show results (using the header search results logic)
                handleHeaderSearch(query);
                if (hdrSearch) {
                    hdrSearch.value = query;
                    hdrSearch.focus();
                }
            } else {
                showToast('No students found matching your search.', 'info');
            }
        } catch (e) {
            console.error('Global search error:', e);
            showToast('Search failed. Please try again.', 'error');
        } finally {
            if (searchInput) searchInput.classList.remove('loading');
        }
    };

    async function handleHeaderSearch(query) {
        if (!query || query.length < 2) {
            if (searchResults) searchResults.style.display = 'none';
            return;
        }

        try {
            const res = await fetch(`${APP_URL}/api/frontdesk/students?action=search&search=${encodeURIComponent(query)}`, getHeaders());
            const data = await res.json();
            
            if (!data.success) return;

            if (!searchResults) return;

            let html = '<div class="search-res-list">';
            if (data.data && data.data.length > 0) {
                html += '<div class="search-cat"><i class="fa fa-users"></i> Students Found</div>';
                data.data.forEach(s => {
                    html += `
                    <div class="search-res-item" onclick="goNav('students', 'view', {id: ${s.id}}); closeSearch();">
                        <div class="res-avatar">${(s.name || 'S').charAt(0)}</div>
                        <div style="flex:1;">
                            <div class="res-main">${s.name}</div>
                            <div class="res-sub">${s.roll_no || 'No ID'} • ${s.batch_name || 'No Batch'}</div>
                        </div>
                        <i class="fa fa-chevron-right" style="font-size:10px; opacity:0.3;"></i>
                    </div>`;
                });
                
                html += `<div class="search-footer" onclick="goNav('students', 'all', {search: '${query}'}); closeSearch();">View all student results for "${query}"</div>`;
            } else {
                html += '<div style="padding:20px; text-align:center; color:#94a3b8;"><i class="fa fa-search-minus" style="font-size:20px; margin-bottom:10px; display:block;"></i>No matching students found</div>';
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
