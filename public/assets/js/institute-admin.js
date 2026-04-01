/**
 * iSoftro ERP — Institute Admin Panel
 * Production Blueprint V3.0 — Implementation
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── STATE ──
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = urlParams.get('page') || 'overview';
    
    let activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
    let activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;
    let expanded = { students: true, inq: false, academic: true, fee: false, teachers: false, exams: false, lms: false, comms: false, library: false, reports: false, settings: false };

    // ── ELEMENTS ──
    const mainContent = document.getElementById('mainContent');
    const sbBody = document.getElementById('sbBody');
    const sbToggle = document.getElementById('sbToggle');
    const sbClose = document.getElementById('sbClose');
    const sbOverlay = document.getElementById('sbOverlay');
    const sbSearchInput = document.getElementById('sbSearch');

    if (sbSearchInput) {
        sbSearchInput.addEventListener('input', (e) => {
            renderSidebar(e.target.value.toLowerCase());
        });
    }

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

    // ── NAVIGATION TREE (V3.0 Blueprint) ──
    const NAV = [
        { id: "overview", icon: "fa-house", label: "Dashboard", sub: null, sec: "MAIN" },
        { id: "students", icon: "fa-user-graduate", label: "Students", sub: [
            { id: "all", l: "All Students", icon: "fa-list" },
            { id: "add", l: "Add Student", icon: "fa-plus" },
            { id: "alumni", l: "Alumni Records", icon: "fa-user-tag" },
            { id: "vault", l: "Document Vault", icon: "fa-vault" }
        ], sec: "MANAGEMENT" },
        { id: "inq", icon: "fa-magnifying-glass", label: "Inquiries & Admissions", sub: [
            { id: "list", l: "Inquiry List", icon: "fa-clipboard-list" },
            { id: "add-inq", l: "Add Inquiry", icon: "fa-user-plus" },
            { id: "inq-analytics", l: "Conversion Analytics", icon: "fa-chart-pie" },
            { id: "adm-form", l: "Admission Form", icon: "fa-id-card" }
        ], sec: "MANAGEMENT" },
        { id: "academic", icon: "fa-book", label: "Academic", sub: [
            { id: "courses", l: "Courses", icon: "fa-book-bookmark" },
            { id: "batches", l: "Batches", icon: "fa-layer-group" },
            { id: "timetable", l: "Timetable Builder", icon: "fa-calendar-plus" },
            { id: "calendar", l: "Academic Calendar", icon: "fa-calendar-days" },
            { id: "lessons", l: "Lesson Plans", icon: "fa-chalkboard-user" }
        ], sec: "MANAGEMENT" },
        { id: "fee", icon: "fa-hand-holding-dollar", label: "Fee Management", sub: [
            { id: "setup", l: "Fee Items Setup", icon: "fa-sliders" },
            { id: "plans", l: "Installment Plans", icon: "fa-calendar-check" },
            { id: "record", l: "Record Payment", icon: "fa-money-bill-wave" },
            { id: "outstanding", l: "Outstanding Dues", icon: "fa-clock" },
            { id: "fin-reports", l: "Financial Reports", icon: "fa-chart-line" }
        ], sec: "MANAGEMENT" },
        { id: "teachers", icon: "fa-user-tie", label: "Teachers", sub: [
            { id: "profiles", l: "Teacher List", icon: "fa-id-badge" },
            { id: "add", l: "Add Teacher", icon: "fa-user-plus" },
            { id: "allocation", l: "Subject Allocation", icon: "fa-book-open-reader" },
            { id: "salary", l: "Salary Management", icon: "fa-wallet" },
            { id: "performance", l: "Performance Analytics", icon: "fa-chart-simple" }
        ], sec: "STAFF" },
        { id: "frontdesk", icon: "fa-person-rays", label: "Front Desk Staff", sub: [
            { id: "list", l: "Front Desk List", icon: "fa-list" },
            { id: "add", l: "Add Front Desk", icon: "fa-user-plus" }
        ], sec: "STAFF" },
        { id: "exams", icon: "fa-file-signature", label: "Exams & Mock Tests", sub: [
            { id: "qbank", l: "Question Bank", icon: "fa-database" },
            { id: "create-ex", l: "Create Exam", icon: "fa-circle-plus" },
            { id: "schedule", l: "Exam Schedule", icon: "fa-calendar-week" },
            { id: "results", l: "Results & Rankings", icon: "fa-trophy" }
        ], sec: "ACADEMIC" },
        { id: "lms", icon: "fa-book-open", label: "LMS", sub: [
            { id: "materials", l: "Study Materials", icon: "fa-file-pdf" },
            { id: "videos", l: "Video Management", icon: "fa-video" },
            { id: "assignments", l: "Assignments", icon: "fa-list-check" },
            { id: "classes", l: "Online Classes", icon: "fa-laptop-code" }
        ], sec: "ACADEMIC" },
        { id: "comms", icon: "fa-paper-plane", label: "Communication", sub: [
            { id: "sms", l: "SMS Broadcast", icon: "fa-message" },
            { id: "email", l: "Email Campaigns", icon: "fa-envelope-open-text" },
            { id: "templates", l: "SMS Templates", icon: "fa-comment-dots" },
            { id: "msg-log", l: "Message Log", icon: "fa-clock-rotate-left" }
        ], sec: "SYSTEM" },
        { id: "library", icon: "fa-book-atlas", label: "Library", sub: [
            { id: "catalog", l: "Book Catalog", icon: "fa-rectangle-list" },
            { id: "issue", l: "Issue / Return", icon: "fa-right-left" },
            { id: "overdue", l: "Overdue Tracking", icon: "fa-triangle-exclamation" },
            { id: "stock", l: "Stock Report", icon: "fa-boxes-stacked" }
        ], sec: "SYSTEM" },
        { id: "reports", icon: "fa-chart-column", label: "Reports", sub: [
            { id: "fee-rep", l: "Fee Reports", icon: "fa-file-invoice-dollar" },
            { id: "att-rep", l: "Attendance Reports", icon: "fa-clipboard-user" },
            { id: "ex-rep", l: "Exam Reports", icon: "fa-square-poll-vertical" },
        ], sec: "ANALYTICS" },
        { id: "settings", icon: "fa-gear", label: "Settings", sub: [
            { id: "prof", l: "Institute Profile", icon: "fa-building" },
            { id: "brand", l: "Branding", icon: "fa-palette" },
            { id: "rbac", l: "RBAC Config", icon: "fa-user-shield" },
            { id: "notif", l: "Notification Rules", icon: "fa-bell-concierge" },
            { id: "year", l: "Academic Year", icon: "fa-calendar-check" }
        ], sec: "SYSTEM" },
    ];

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subId = null, params = null) => {
        activeNav = id;
        activeSub = subId;

        // Update URL via pushState
        const url = new URL(window.location);
        const pageVal = subId ? `${id}-${subId}` : id;
        url.searchParams.set('page', pageVal);

        // Clear existing params besides page
        const existingPage = url.searchParams.get('page');
        url.search = '';
        url.searchParams.set('page', existingPage);

        if (params) {
            Object.keys(params).forEach(key => {
                url.searchParams.set(key, params[key]);
            });
        }

        window.history.pushState({ pageVal }, '', url);

        if (window.innerWidth < 1024) closeSidebar();
        renderSidebar();
        renderPage();
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
        activeNav = pageVal.includes('-') ? pageVal.split('-')[0] : pageVal;
        activeSub = pageVal.includes('-') ? pageVal.split('-')[1] : null;

        renderSidebar();
        renderPage();
    });

    window.toggleExp = (id) => {
        expanded[id] = !expanded[id];
        renderSidebar();
    };

    function renderSidebar(filter = '') {
        const sections = [...new Set(NAV.map(n => n.sec))];
        let html = '';

        sections.forEach(sec => {
            const filteredNav = NAV.filter(n => {
                if (n.sec !== sec) return false;
                if (!filter) return true;
                const labelMatch = n.label.toLowerCase().includes(filter);
                const subMatch = n.sub && n.sub.some(s => s.l.toLowerCase().includes(filter));
                return labelMatch || subMatch;
            });

            if (filteredNav.length === 0) return;

            html += `<div class="sb-sec"><div class="sb-lbl">${sec}</div>`;

            filteredNav.forEach(nav => {
                const isActive = activeNav === nav.id && !activeSub;
                // Auto-expand if searching
                const isExp = filter ? true : expanded[nav.id];

                html += `<div class="sb-item">
                    <button class="nb-btn ${isActive ? 'active' : ''}" onclick="${nav.sub ? `toggleExp('${nav.id}')` : `goNav('${nav.id}')`}">
                        <i class="fa-solid ${nav.icon} nbi"></i>
                        <span class="nbl">${nav.label}</span>
                        ${nav.sub ? `<i class="fa-solid fa-chevron-right nbc ${isExp ? 'open' : ''}"></i>` : ''}
                    </button>`;

                if (nav.sub && isExp) {
                    html += `<div class="sub-menu">`;
                    nav.sub.forEach(s => {
                        if (filter && !s.l.toLowerCase().includes(filter) && !nav.label.toLowerCase().includes(filter)) return;
                        const isSubActive = activeNav === nav.id && activeSub === s.id;
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
                    <i class="fa-solid fa-bolt"></i>
                    <span> Install App</span>
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
            { id: 'students', icon: 'fa-user-graduate', label: 'Students', action: "goNav('students', 'all')" },
            { id: 'fee', icon: 'fa-hand-holding-dollar', label: 'Fee', action: "goNav('fee', 'record')" },
            { id: 'exams', icon: 'fa-file-signature', label: 'Exams', action: "goNav('exams', 'schedule')" },
            { id: 'comms', icon: 'fa-paper-plane', label: 'Comms', action: "goNav('comms', 'sms')" }
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
        const urlParams = new URLSearchParams(window.location.search);
        mainContent.innerHTML = '<div class="pg fu">Loading...</div>';

        if (activeNav === 'overview') {
            renderDashboard();
        } else {
            renderGenericPage(urlParams);
        }
    }

    /**
     * Format number as Indian Rupees with proper thousand separators
     * @param {number} amount - The amount to format
     * @returns {string} Formatted amount string
     */
    function formatRs(amount) {
        if (amount === null || amount === undefined) return '₹ 0';
        const num = Number(amount);
        if (isNaN(num)) return '₹ 0';
        
        // Format with Indian numbering system (lakhs, crores)
        const formatted = num.toLocaleString('en-IN', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        });
        return '₹ ' + formatted;
    }

    /**
     * Format percentage change with arrow indicator
     * @param {number} percent - The percentage value
     * @returns {string} HTML string with arrow indicator
     */
    function formatPercentChange(percent) {
        const isPositive = percent >= 0;
        const arrow = isPositive ? '↑' : '↓';
        const colorClass = isPositive ? 'up' : 'down';
        return `<span class="${colorClass}">${arrow} ${Math.abs(percent).toFixed(1)}%</span>`;
    }

    /**
     * Get loading skeleton HTML for dashboard
     */
    function getDashboardSkeleton() {
        return `
            <div class="pg fu">
                <!-- Header Skeleton -->
                <div class="bc skeleton-text" style="width: 200px; height: 16px; background: #e2e8f0; border-radius: 4px; margin-bottom: 16px;"></div>
                
                <!-- Welcome Banner Skeleton -->
                <div class="welcome-banner" style="background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); margin-bottom: 20px;">
                    <div style="width: 60%;">
                        <div class="skeleton-text" style="width: 100px; height: 12px; background: rgba(0,0,0,0.1); border-radius: 4px; margin-bottom: 8px;"></div>
                        <div class="skeleton-text" style="width: 250px; height: 24px; background: rgba(0,0,0,0.1); border-radius: 4px; margin-bottom: 8px;"></div>
                        <div class="skeleton-text" style="width: 300px; height: 14px; background: rgba(0,0,0,0.1); border-radius: 4px;"></div>
                    </div>
                    <div class="wb-stats">
                        <div class="wb-stat" style="background: rgba(0,0,0,0.05);">
                            <div class="skeleton-text" style="width: 40px; height: 24px; background: rgba(0,0,0,0.1); border-radius: 4px; margin: 0 auto 4px;"></div>
                            <div class="skeleton-text" style="width: 60px; height: 10px; background: rgba(0,0,0,0.1); border-radius: 4px; margin: 0 auto;"></div>
                        </div>
                        <div class="wb-stat" style="background: rgba(0,0,0,0.05);">
                            <div class="skeleton-text" style="width: 40px; height: 24px; background: rgba(0,0,0,0.1); border-radius: 4px; margin: 0 auto 4px;"></div>
                            <div class="skeleton-text" style="width: 60px; height: 10px; background: rgba(0,0,0,0.1); border-radius: 4px; margin: 0 auto;"></div>
                        </div>
                        <div class="wb-stat" style="background: rgba(0,0,0,0.05);">
                            <div class="skeleton-text" style="width: 40px; height: 24px; background: rgba(0,0,0,0.1); border-radius: 4px; margin: 0 auto 4px;"></div>
                            <div class="skeleton-text" style="width: 60px; height: 10px; background: rgba(0,0,0,0.1); border-radius: 4px; margin: 0 auto;"></div>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards Skeleton -->
                <div class="kpi-grid">
                    ${[1,2,3,4,5,6].map(() => `
                        <div class="kpi-card" style="background: #f1f5f9;">
                            <div class="kpi-top">
                                <div class="skeleton-text" style="width: 100px; height: 12px; background: #e2e8f0; border-radius: 4px;"></div>
                                <div style="width: 34px; height: 34px; background: #e2e8f0; border-radius: 8px;"></div>
                            </div>
                            <div class="skeleton-text" style="width: 80px; height: 26px; background: #e2e8f0; border-radius: 4px; margin-bottom: 8px;"></div>
                            <div class="skeleton-text" style="width: 120px; height: 10px; background: #e2e8f0; border-radius: 4px;"></div>
                        </div>
                    `).join('')}
                </div>

                <!-- Main Grid Skeleton -->
                <div class="main-grid">
                    <div>
                        <div class="card" style="background: #f1f5f9; height: 300px; margin-bottom: 16px;">
                            <div class="card-header">
                                <div class="skeleton-text" style="width: 150px; height: 14px; background: #e2e8f0; border-radius: 4px;"></div>
                            </div>
                        </div>
                        <div class="card" style="background: #f1f5f9; height: 200px;">
                            <div class="card-header">
                                <div class="skeleton-text" style="width: 150px; height: 14px; background: #e2e8f0; border-radius: 4px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="right-panel">
                        <div class="card" style="background: #f1f5f9; height: 250px;">
                            <div class="card-header">
                                <div class="skeleton-text" style="width: 120px; height: 14px; background: #e2e8f0; border-radius: 4px;"></div>
                            </div>
                        </div>
                        <div class="card" style="background: #f1f5f9; height: 200px;">
                            <div class="card-header">
                                <div class="skeleton-text" style="width: 120px; height: 14px; background: #e2e8f0; border-radius: 4px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Update workflow checklist item
     */
    window.updateWorkflowItem = async function(taskKey, isCompleted, taskName, taskDescription) {
        try {
            const res = await fetch(APP_URL + '/api/admin/stats?action=workflow', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    task_key: taskKey,
                    is_completed: isCompleted,
                    task_name: taskName,
                    task_description: taskDescription
                })
            });
            const result = await res.json();
            if (!result.success) {
                console.error('Failed to update workflow:', result.message);
            }
        } catch (error) {
            console.error('Workflow update error:', error);
        }
    };

    async function renderDashboard() {
        // Inject Premium CSS
        if (!document.getElementById('ia-dashboard-premium-css')) {
            const link = document.createElement('link');
            link.id = 'ia-dashboard-premium-css';
            link.rel = 'stylesheet';
            link.href = APP_URL + '/assets/css/ia-dashboard-premium.css';
            document.head.appendChild(link);
        }

        // Show loading skeleton
        mainContent.innerHTML = getDashboardSkeleton();

        try {
            const res = await fetch(APP_URL + '/api/admin/stats');
            const result = await res.json();
            
            if (!result.success) throw new Error(result.message || 'Failed to fetch dashboard data');
            const stats = result.data;

            let html = `
                <div class="pg fu slide-up-anim">
                    <div class="bc">
                        <a href="#" onclick="goNav('overview'); return false;">${stats.institute_name || 'Dashboard'}</a> <span class="bc-sep">›</span> <span class="bc-cur">Overview</span>
                    </div>
                    
                    <!-- WELCOME BANNER -->
                    <div class="welcome-banner">
                        <div>
                            <div class="wb-greeting">Good ${getGreeting()}, Admin</div>
                            <div class="wb-title">${stats.institute_name || 'Institute Dashboard'}</div>
                            <div class="wb-quote">"Success in education comes from consistent daily efforts and attention to every student."</div>
                        </div>
                        <div class="wb-stats">
                            <div class="wb-stat">
                                <div class="wb-stat-val">${stats.total_students || 0}</div>
                                <div class="wb-stat-lbl">Total Students</div>
                            </div>
                            <div class="wb-stat">
                                <div class="wb-stat-val">${stats.active_batches || 0}</div>
                                <div class="wb-stat-lbl">Active Batches</div>
                            </div>
                            <div class="wb-stat">
                                <div class="wb-stat-val">${stats.total_teachers || 0}</div>
                                <div class="wb-stat-lbl">Teachers</div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI CARDS GRID -->
                    <div class="kpi-grid-premium">
                        <!-- Active Students Card -->
                        <div class="kpi-card-premium c-green">
                            <div class="kpi-top-premium">
                                <div class="kpi-label-premium">Active Students</div>
                                <div class="kpi-icon-premium"><i class="fa-solid fa-users"></i></div>
                            </div>
                            <div class="kpi-val-premium">${stats.total_students || 0}</div>
                            <div class="kpi-sub-premium">
                                <span class="kpi-trend ${stats.student_growth_percent >= 0 ? 'up' : 'down'}">
                                ${stats.student_growth_percent >= 0 ? '↑' : '↓'} ${Math.abs(stats.student_growth_percent || 0).toFixed(1)}%
                                </span>
                                <span>from last month</span>
                            </div>
                        </div>

                        <!-- Today's Attendance Card -->
                        <div class="kpi-card-premium c-blue">
                            <div class="kpi-top-premium">
                                <div class="kpi-label-premium">Today's Attendance</div>
                                <div class="kpi-icon-premium"><i class="fa-solid fa-clipboard-user"></i></div>
                            </div>
                            <div class="kpi-val-premium">${stats.attendance_rate || 0}<small>%</small></div>
                            <div class="kpi-sub-premium">
                                <span style="color: #10b981; font-weight: 600;">${stats.attendance?.present || 0} Present</span> • 
                                <span style="color: #ef4444; font-weight: 600;">${stats.attendance?.absent || 0} Absent</span>
                            </div>
                            ${(stats.batch_attendance && stats.batch_attendance.length > 0) ? `
                            <div class="batch-attn-list">
                                ${stats.batch_attendance.slice(0, 3).map(b => `<div class="batch-attn-pill ${b.rate >= 80 ? 'good' : (b.rate >= 60 ? 'warn' : 'bad')}">${b.batch_name}: <span>${b.rate}%</span></div>`).join('')}
                                ${stats.batch_attendance.length > 3 ? `<div class="batch-attn-pill">+${stats.batch_attendance.length - 3} more</div>` : ''}
                            </div>
                            ` : ''}
                        </div>

                        <!-- Today's Collection Card -->
                        <div class="kpi-card-premium c-purple">
                            <div class="kpi-top-premium">
                                <div class="kpi-label-premium">Today's Collection</div>
                                <div class="kpi-icon-premium"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                            </div>
                            <div class="kpi-val-premium" style="font-size: 26px;">${typeof formatRs === 'function' ? formatRs(stats.today_fee || 0) : '₹' + (stats.today_fee || 0)}</div>
                            <div class="kpi-sub-premium">
                                <span class="kpi-trend ${stats.today_fee_change_percent >= 0 ? 'up' : 'down'}">
                                ${stats.today_fee_change_percent >= 0 ? '↑' : '↓'} ${Math.abs(stats.today_fee_change_percent || 0).toFixed(1)}%
                                </span>
                                <span>from yesterday</span>
                            </div>
                            <div class="kpi-breakdown">
                                <div class="kpi-breakdown-item">
                                    <div class="kpi-breakdown-val">₹${stats.today_fee_cash || 0}</div>
                                    <div class="kpi-breakdown-lbl">Cash</div>
                                </div>
                                <div class="kpi-breakdown-item">
                                    <div class="kpi-breakdown-val">₹${stats.today_fee_bank || 0}</div>
                                    <div class="kpi-breakdown-lbl">Bank</div>
                                </div>
                            </div>
                        </div>

                        <!-- Outstanding Dues Card -->
                        <div class="kpi-card-premium c-red">
                            <div class="kpi-top-premium">
                                <div class="kpi-label-premium">Outstanding Dues</div>
                                <div class="kpi-icon-premium"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            </div>
                            <div class="kpi-val-premium" style="font-size: 26px;">${typeof formatRs === 'function' ? formatRs(stats.outstanding_dues || 0) : '₹' + (stats.outstanding_dues || 0)}</div>
                            <div class="kpi-sub-premium">
                                <span style="font-weight: 600; color: #ef4444;">${stats.outstanding_students || 0} students pending</span>
                            </div>
                        </div>

                        <!-- New Inquiries Card -->
                        <div class="kpi-card orange">
                            <div class="kpi-top">
                                <div class="kpi-label">New Inquiries</div>
                                <div class="kpi-icon orange"><i class="fa-solid fa-magnifying-glass"></i></div>
                            </div>
                            <div class="kpi-val">${stats.new_inquiries || 0}</div>
                            <div class="kpi-sub">${stats.pending_inquiries || 0} pending • ${stats.followups_today || 0} follow-ups today</div>
                        </div>

                        <!-- Upcoming Exams Card -->
                        <div class="kpi-card teal">
                            <div class="kpi-top">
                                <div class="kpi-label">Upcoming Exams</div>
                                <div class="kpi-icon teal"><i class="fa-solid fa-file-signature"></i></div>
                            </div>
                            <div class="kpi-val">${stats.upcoming_exams || 0}</div>
                            <div class="kpi-sub">Next 7 days</div>
                        </div>
                    </div>

                    <!-- MAIN GRID -->
                    <div class="main-grid">
                        <div class="left-content">
                            
                            <!-- CRITICAL ALERTS PANEL -->
                            ${(stats.critical_alerts && stats.critical_alerts.length > 0) ? `
                            <div class="card-premium">
                                <div class="card-header-premium">
                                    <h4><i class="fa-solid fa-bell" style="color: var(--red);"></i> Critical Alerts</h4>
                                    <span class="card-badge red">${stats.critical_alerts.length} Pending</span>
                                </div>
                                <div class="card-body-premium">
                                    <div class="alerts-premium-container">
                                        ${stats.critical_alerts.map(alert => `
                                            <div class="alert-premium-card bg-${alert.color}">
                                                <div class="alert-icon-box"><i class="fa-solid ${alert.icon}"></i></div>
                                                <div class="alert-content">
                                                    <h5>${alert.title}</h5>
                                                    <p>${alert.message}</p>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                            ` : ''}

                            <!-- FEE OVERVIEW SECTION -->
                            <div class="card-premium">
                                <div class="card-header-premium">
                                    <h4><i class="fa-solid fa-chart-pie" style="color: var(--green);"></i> Fee Overview</h4>
                                    <span class="card-badge ${(stats.target_achievement_percent || 0) >= 80 ? 'green' : ((stats.target_achievement_percent || 0) >= 50 ? 'orange' : 'red')}">
                                        ${stats.target_achievement_percent || 0}% of Target
                                    </span>
                                </div>
                                <div class="card-body-premium">
                                    <div class="fee-summary">
                                        <div class="fee-sum-item collected">
                                            <div class="fs-val">${typeof formatRs === 'function' ? formatRs(stats.monthly_collected || 0) : '₹' + (stats.monthly_collected || 0)}</div>
                                            <div class="fs-lbl">Collected This Month</div>
                                        </div>
                                        <div class="fee-sum-item outstanding">
                                            <div class="fs-val">${typeof formatRs === 'function' ? formatRs(stats.outstanding_dues || 0) : '₹' + (stats.outstanding_dues || 0)}</div>
                                            <div class="fs-lbl">Outstanding</div>
                                        </div>
                                        <div class="fee-sum-item discount">
                                            <div class="fs-val">${typeof formatRs === 'function' ? formatRs(stats.monthly_discount || 0) : '₹' + (stats.monthly_discount || 0)}</div>
                                            <div class="fs-lbl">Discounts Given</div>
                                        </div>
                                    </div>

                                    <!-- Target Progress -->
                                    <div class="progress-bar-wrap" style="margin-bottom: 16px;">
                                        <div class="progress-bar-label">
                                            <span>Monthly Target Achievement</span>
                                            <span>${formatRs(stats.monthly_collected || 0)} / ${formatRs(stats.monthly_target || 0)}</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-bar-fill ${(stats.target_achievement_percent || 0) >= 80 ? 'green' : ((stats.target_achievement_percent || 0) >= 50 ? 'orange' : 'red')}" 
                                                 style="width: ${Math.min(100, stats.target_achievement_percent || 0)}%"></div>
                                        </div>
                                    </div>

                                    <!-- Fee Aging Report -->
                                    <div style="margin-top: 20px;">
                                        <div style="font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 12px;">
                                            <i class="fa-solid fa-triangle-exclamation" style="color: var(--orange); margin-right: 6px;"></i>
                                            Fee Aging Report
                                        </div>
                                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                                            <div style="background: rgba(0,184,148,0.08); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid rgba(0,184,148,0.2);">
                                                <div style="font-size: 14px; font-weight: 700; color: var(--green);">${formatRs(stats.fee_aging?.['0_30']?.amount || 0)}</div>
                                                <div style="font-size: 10px; color: var(--text-light);">0-30 Days</div>
                                                <div style="font-size: 9px; color: var(--text-body);">${stats.fee_aging?.['0_30']?.count || 0} students</div>
                                            </div>
                                            <div style="background: rgba(245,158,11,0.08); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid rgba(245,158,11,0.2);">
                                                <div style="font-size: 14px; font-weight: 700; color: #d97706;">${formatRs(stats.fee_aging?.['31_60']?.amount || 0)}</div>
                                                <div style="font-size: 10px; color: var(--text-light);">31-60 Days</div>
                                                <div style="font-size: 9px; color: var(--text-body);">${stats.fee_aging?.['31_60']?.count || 0} students</div>
                                            </div>
                                            <div style="background: rgba(225,29,72,0.08); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid rgba(225,29,72,0.2);">
                                                <div style="font-size: 14px; font-weight: 700; color: var(--red);">${formatRs(stats.fee_aging?.['61_90']?.amount || 0)}</div>
                                                <div style="font-size: 10px; color: var(--text-light);">61-90 Days</div>
                                                <div style="font-size: 9px; color: var(--text-body);">${stats.fee_aging?.['61_90']?.count || 0} students</div>
                                            </div>
                                            <div style="background: rgba(129,65,165,0.08); border-radius: 8px; padding: 10px; text-align: center; border: 1px solid rgba(129,65,165,0.2);">
                                                <div style="font-size: 14px; font-weight: 700; color: var(--purple);">${formatRs(stats.fee_aging?.['90plus']?.amount || 0)}</div>
                                                <div style="font-size: 10px; color: var(--text-light);">90+ Days</div>
                                                <div style="font-size: 9px; color: var(--text-body);">${stats.fee_aging?.['90plus']?.count || 0} students</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- REVENUE TRENDS CHART -->
                            <div class="card mb">
                                <div class="card-header">
                                    <h4><i class="fa-solid fa-chart-line" style="color: var(--blue); margin-right: 8px;"></i>Revenue Trends (Last 6 Months)</h4>
                                    <span class="card-badge ${(stats.revenue_change_percent || 0) >= 0 ? 'green' : 'red'}">
                                        ${formatPercentChange(stats.revenue_change_percent || 0)}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div style="height: 200px; position: relative;">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 12px; font-size: 11px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <div style="width: 10px; height: 10px; background: var(--green); border-radius: 2px;"></div>
                                            <span>Revenue Collected</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <div style="width: 10px; height: 10px; background: var(--orange); border-radius: 2px;"></div>
                                            <span>Discounts</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ENROLLMENT TREND -->
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fa-solid fa-user-graduate" style="color: var(--purple); margin-right: 8px;"></i>Enrollment Trend (Last 6 Months)</h4>
                                </div>
                                <div class="card-body">
                                    ${(stats.enrollment_trend && stats.enrollment_trend.length > 0) ? `
                                        <div style="display: flex; align-items: flex-end; gap: 8px; height: 120px; padding: 10px 0;">
                                            ${stats.enrollment_trend.map((m, i) => {
                                                const maxCount = Math.max(...stats.enrollment_trend.map(t => t.count), 1);
                                                const height = Math.max(10, (m.count / maxCount) * 100);
                                                const isLast = i === stats.enrollment_trend.length - 1;
                                                return `
                                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                                        <div style="font-size: 10px; font-weight: 600; color: var(--text-dark);">${m.count}</div>
                                                        <div style="width: 100%; height: ${height}px; background: ${isLast ? 'var(--green)' : '#e2e8f0'}; border-radius: 4px 4px 0 0; transition: all 0.3s;"></div>
                                                        <div style="font-size: 10px; color: var(--text-light);">${m.month}</div>
                                                    </div>
                                                `;
                                            }).join('')}
                                        </div>
                                    ` : `
                                        <div style="padding: 40px; text-align: center; color: var(--text-light);">
                                            <i class="fa-solid fa-chart-bar" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                            <p style="font-size: 12px;">No enrollment data available for this period</p>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT PANEL -->
                        <div class="right-panel">
                            <!-- QUICK ACTIONS -->
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fa-solid fa-bolt" style="color: var(--orange); margin-right: 8px;"></i>Quick Actions</h4>
                                </div>
                                <div class="card-body quick-actions">
                                    <div class="qa-grid">
                                        <button class="qa-btn green" onclick="goNav('students', 'add')">
                                            <i class="fa-solid fa-user-plus"></i>
                                            <span>Add Student</span>
                                        </button>
                                        <button class="qa-btn" style="border-color: rgba(0,184,148,.2);" onclick="goNav('fee', 'record')">
                                            <i class="fa-solid fa-money-bill-wave" style="color: var(--green);"></i>
                                            <span>Record Fee</span>
                                        </button>
                                        <button class="qa-btn" style="border-color: rgba(59,130,246,.2);" onclick="goNav('comms', 'sms')">
                                            <i class="fa-solid fa-message" style="color: #3b82f6;"></i>
                                            <span>Send SMS</span>
                                        </button>
                                        <button class="qa-btn" style="border-color: rgba(129,65,165,.2);" onclick="goNav('exams', 'create-ex')">
                                            <i class="fa-solid fa-file-circle-plus" style="color: var(--purple);"></i>
                                            <span>Create Exam</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- DAILY WORKFLOW CHECKLIST -->
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fa-solid fa-list-check" style="color: var(--green); margin-right: 8px;"></i>Daily Workflow</h4>
                                    <span class="card-badge green" id="workflowProgress">
                                        ${Math.round((stats.workflow?.filter(w => w.done).length || 0) / (stats.workflow?.length || 1) * 100)}%
                                    </span>
                                </div>
                                <div class="card-body" style="padding: 0;">
                                    ${(stats.workflow && stats.workflow.length > 0) ? stats.workflow.map((task, index) => `
                                        <div class="workflow-item" style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: flex-start; gap: 10px; ${task.done ? 'background: rgba(0,184,148,0.03);' : ''}">
                                            <input type="checkbox" 
                                                id="workflow-${index}" 
                                                ${task.done ? 'checked' : ''}
                                                onchange="updateWorkflowItem('${task.key}', this.checked, '${task.task.replace(/'/g, "\\'")}', '${task.desc.replace(/'/g, "\\'")}'); updateWorkflowUI();"
                                                style="margin-top: 2px; cursor: pointer; width: 16px; height: 16px; accent-color: var(--green);">
                                            <div style="flex: 1;">
                                                <label for="workflow-${index}" style="font-size: 12px; font-weight: 600; color: ${task.done ? 'var(--text-light)' : 'var(--text-dark)'}; cursor: pointer; text-decoration: ${task.done ? 'line-through' : 'none'};">
                                                    <i class="fa-solid ${task.icon}" style="color: var(--${task.color}); margin-right: 6px;"></i>
                                                    ${task.task}
                                                </label>
                                                <div style="font-size: 10px; color: var(--text-light); margin-top: 2px; margin-left: 20px;">${task.desc}</div>
                                                ${task.completed_at ? `<div style="font-size: 9px; color: var(--green); margin-top: 4px; margin-left: 20px;"><i class="fa-solid fa-check"></i> Completed ${new Date(task.completed_at).toLocaleTimeString()}</div>` : ''}
                                            </div>
                                        </div>
                                    `).join('') : `
                                        <div style="padding: 20px; text-align: center; color: var(--text-light);">
                                            <p style="font-size: 12px;">No workflow tasks configured</p>
                                        </div>
                                    `}
                                </div>
                            </div>

                            <!-- SYSTEM ACTIVITY -->
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fa-solid fa-clock-rotate-left" style="color: var(--blue); margin-right: 8px;"></i>System Activity</h4>
                                </div>
                                <div class="card-body" style="padding: 0; max-height: 300px; overflow-y: auto;">
                                    ${(stats.recent_activity && stats.recent_activity.length > 0) ? stats.recent_activity.map((act, i) => `
                                        <div style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; ${i % 2 === 0 ? 'background: #fafcff;' : ''}">
                                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                                <div style="width: 28px; height: 28px; background: rgba(0,184,148,0.1); border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    <i class="fa-solid fa-circle-check" style="color: var(--green); font-size: 12px;"></i>
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-size: 12px; color: var(--text-dark); font-weight: 500; word-break: break-word;">${act.action}</div>
                                                    <div style="font-size: 10px; color: var(--text-light); margin-top: 2px;">
                                                        <strong>${act.user_name || 'System'}</strong> • ${formatRelativeTime(act.created_at)}
                                                    </div>
                                                    ${act.description ? `<div style="font-size: 10px; color: var(--text-body); margin-top: 4px; line-height: 1.4;">${act.description}</div>` : ''}
                                                </div>
                                            </div>
                                        </div>
                                    `).join('') : `
                                        <div style="padding: 40px 20px; text-align: center; color: var(--text-light);">
                                            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                                            <p style="font-size: 12px;">No recent activity</p>
                                        </div>
                                    `}
                                </div>
                            </div>

                            <!-- UPCOMING EXAMS -->
                            ${(stats.upcoming_exams_list && stats.upcoming_exams_list.length > 0) ? `
                                <div class="card-premium">
                                    <div class="card-header-premium">
                                        <h4><i class="fa-solid fa-calendar-day" style="color: var(--red);"></i> Upcoming Exams</h4>
                                        <span class="card-badge red">${stats.upcoming_exams || 0}</span>
                                    </div>
                                    <div class="card-body-premium" style="padding: 0;">
                                        ${stats.upcoming_exams_list.slice(0, 3).map(exam => `
                                            <div class="list-item-premium">
                                                <div>
                                                    <div class="list-item-title">${exam.title}</div>
                                                    <div class="list-item-sub">
                                                        <span><i class="fa-solid fa-calendar"></i> ${new Date(exam.exam_date).toLocaleDateString('en-IN', { weekday: 'short', month: 'short', day: 'numeric' })}</span>
                                                        ${exam.batch_name ? `<span><i class="fa-solid fa-layer-group"></i> ${exam.batch_name}</span>` : ''}
                                                    </div>
                                                </div>
                                                <div class="list-item-badge">${exam.enrolled_count || 0} Enrolled</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            mainContent.innerHTML = html;

            // Initialize Revenue Chart
            initRevenueChart(stats.revenue_trend || []);

        } catch (error) {
            console.error('Dashboard Error:', error);
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="card" style="padding: 60px 40px; text-align: center; border: 1px solid var(--red);">
                        <div style="width: 80px; height: 80px; background: rgba(225,29,72,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                            <i class="fa-solid fa-circle-exclamation" style="font-size: 2.5rem; color: var(--red);"></i>
                        </div>
                        <h3 style="color: var(--text-dark); margin-bottom: 8px;">Failed to load dashboard data</h3>
                        <p style="color: var(--text-body); font-size: 14px; margin-bottom: 20px;">${error.message || 'Please check your connection and try again.'}</p>
                        <button class="btn bt" onclick="renderDashboard()" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-rotate-right"></i> Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Initialize Revenue Chart with Chart.js
     */
    function initRevenueChart(revenueData) {
        const ctx = document.getElementById('revenueChart');
        if (!ctx || typeof Chart === 'undefined') return;

        const months = revenueData.map(d => d.month);
        const amounts = revenueData.map(d => d.amount);
        const discounts = revenueData.map(d => d.discounts || 0);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Revenue Collected',
                        data: amounts,
                        backgroundColor: 'rgba(0, 184, 148, 0.8)',
                        borderColor: 'rgba(0, 184, 148, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Discounts',
                        data: discounts,
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return formatRs(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹ ' + (value / 1000).toFixed(0) + 'K';
                            },
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    /**
     * Update workflow checklist UI progress
     */
    window.updateWorkflowUI = function() {
        const checkboxes = document.querySelectorAll('.workflow-item input[type="checkbox"]');
        const checked = document.querySelectorAll('.workflow-item input[type="checkbox"]:checked');
        const progress = checkboxes.length > 0 ? Math.round((checked.length / checkboxes.length) * 100) : 0;
        
        const progressBadge = document.getElementById('workflowProgress');
        if (progressBadge) {
            progressBadge.textContent = progress + '%';
            progressBadge.className = 'card-badge ' + (progress === 100 ? 'green' : (progress >= 50 ? 'orange' : 'red'));
        }
    };

    /**
     * Get appropriate greeting based on time of day
     */
    function getGreeting() {
        const hour = new Date().getHours();
        if (hour < 12) return 'Morning';
        if (hour < 17) return 'Afternoon';
        return 'Evening';
    }

    /**
     * Format relative time (e.g., "2 hours ago")
     */
    function formatRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffSecs < 60) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString('en-IN');
    }

    function renderGenericPage(urlParams) {
        // Handle specific pages
        if (activeNav === 'settings' && activeSub === 'prof') {
            renderInstituteProfile();
            return;
        }

        if (activeNav === 'students') {
            if (activeSub === 'add') {
                renderAddStudentForm();
                return;
            }
            if (activeSub === 'edit') {
                renderEditStudentForm(urlParams.get('id'));
                return;
            }
            if (activeSub === 'all' || !activeSub) {
                renderStudentList();
                return;
            }
        }

        if (activeNav === 'academic') {
            if (activeSub === 'courses') {
                if (urlParams.get('id')) {
                    renderEditCourseForm(urlParams.get('id'));
                } else if (urlParams.get('action') === 'add') {
                    renderAddCourseForm();
                } else {
                    renderCourseList();
                }
                return;
            }
            if (activeSub === 'batches') {
                if (urlParams.get('id')) {
                    renderEditBatchForm(urlParams.get('id'));
                } else if (urlParams.get('action') === 'add') {
                    renderAddBatchForm();
                } else {
                    renderBatchList();
                }
                return;
            }
            if (activeSub === 'timetable') {
                renderTimetablePage();
                return;
            }
        }

        if (activeNav === 'inq' && activeSub === 'list') {
            renderInquiryList();
            return;
        }

        if (activeNav === 'inq' && activeSub === 'add-inq') {
            renderAddInquiryForm();
            return;
        }

        if (activeNav === 'inq' && activeSub === 'inq-analytics') {
            renderInquiryAnalytics();
            return;
        }

        if (activeNav === 'inq' && activeSub === 'adm-form') {
            renderAdmissionForm();
            return;
        }

        if (activeNav === 'exams' && (activeSub === 'schedule' || activeSub === 'results')) {
            renderExamList();
            return;
        }

        if (activeNav === 'attendance') {
        if (activeSub === 'take') { renderAttendanceTake(); return; }
        if (activeSub === 'leave') { renderLeaveRequests(); return; }
        if (activeSub === 'report') { renderAttendanceReport(); return; }
    }

    if (activeNav === 'reports' && activeSub === 'att-rep') {
        renderAttendanceReport();
        return;
    }

    if (activeNav === 'teachers') {
        if (activeSub === 'add') renderAddStaffForm('teacher');
        else renderStaffList('teacher');
        return;
    }
        if (activeNav === 'frontdesk') {
            if (activeSub === 'add') renderAddStaffForm('frontdesk');
            else renderStaffList('frontdesk');
            return;
        }
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="card" style="text-align:center; padding:100px 40px;">
                    <i class="fa-solid fa-cubes-stacked" style="font-size:3rem; color:var(--tl); margin-bottom:20px;"></i>
                    <h2>${activeSub || activeNav.toUpperCase()} Module</h2>
                    <p style="color:var(--tb); margin-top:10px;">This specific view is being populated with the V3.0 blueprint components.</p>
                </div>
            </div>
        `;
    }

    async function renderStudentList() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">All Students</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-graduate"></i></div>
                        <div>
                            <div class="pg-title">Student Directory</div>
                            <div class="pg-sub">Manage and view all enrolled students</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="goNav('students', 'add')">
                            <i class="fa-solid fa-plus"></i> Add New Student
                        </button>
                    </div>
                </div>

                <div class="card mb" style="padding:15px;">
                    <div style="display:flex; gap:15px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:250px;">
                            <div class="form-group">
                                <input type="text" id="studentSearch" class="form-control" placeholder="Search by name, roll no or email..." onkeyup="if(event.key === 'Enter') filterStudents()">
                            </div>
                        </div>
                        <div style="width:150px;">
                            <select id="statusFilter" class="form-control" onchange="filterStudents()">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="alumni">Alumni</option>
                            </select>
                        </div>
                        <button class="btn bs" onclick="filterStudents()">Filter</button>
                    </div>
                </div>

                <div class="card" id="studentTableContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Fetching students...</span>
                    </div>
                </div>
            </div>
        `;

        await loadStudents();
    }

    window.filterStudents = async () => {
        const search = document.getElementById('studentSearch').value;
        const status = document.getElementById('statusFilter').value;
        await loadStudents(search, status);
    };

    async function loadStudents(search = '', status = '') {
        const container = document.getElementById('studentTableContainer');
        try {
            let url = APP_URL + '/api/admin/students';
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (params.toString()) url += '?' + params.toString();

            const res = await fetch(url);
            const result = await res.json();

            if (!result.success) throw new Error(result.message);

            const students = result.data;

            if (students.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-users-slash" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No students found matching your criteria.</p>
                </div>`;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Roll No</th>
                                <th>Batch / Course</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            students.forEach(s => {
                const statusClass = s.status === 'active' ? 'bg-t' : (s.status === 'inactive' ? 'bg-r' : 'bg-b');
                const photoSrc = s.photo_url ? (s.photo_url.startsWith('http') ? s.photo_url : APP_URL + s.photo_url) : null;
                html += `
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                    ${photoSrc ? `<img src="${photoSrc}" style="width:100%; height:100%; object-fit:cover;" onerror="this.parentElement.innerHTML='<i class=\\'fa-solid fa-user\\' style=\\'color:#94a3b8;\\'></i>'">` : `<i class="fa-solid fa-user" style="color:#94a3b8;"></i>`}
                                </div>
                                <div>
                                    <div style="font-weight:600; color:#1e293b;">${s.name}</div>
                                    <div style="font-size:11px; color:#64748b;">
                                        ${u.phone || 'No phone'} 
                                        ${s.dob_bs ? ` • <span style="color:var(--brand); font-weight:600;">BS: ${s.dob_bs}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-family:monospace; font-weight:700; color:#2563eb;">${s.roll_no}</span></td>
                        <td>
                            <div style="font-size:13px; font-weight:500;">${s.batch_name || 'N/A'}</div>
                            <div style="font-size:11px; color:#64748b;">${s.course_name || ''}</div>
                        </td>
                        <td><span class="tag ${statusClass}">${s.status.toUpperCase()}</span></td>
                        <td>${new Date(s.created_at).toLocaleDateString()}</td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="btn-icon" title="View Profile"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn-icon" title="Edit" onclick="goNav('students', 'edit', {id: ${s.id}})"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button class="btn-icon" title="Payment"><i class="fa-solid fa-money-bill-transfer"></i></button>
                            <button class="btn-icon text-danger" title="Delete" onclick="deleteStudent(${s.id}, '${s.name}')"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;

        } catch (error) {
            container.innerHTML = `<div style="padding:40px; text-align:center; color:var(--red);">
                <i class="fa-solid fa-circle-exclamation" style="font-size:2rem; margin-bottom:10px;"></i>
                <p>Failed to load students: ${error.message}</p>
            </div>`;
        }
    }

    async function renderCourseList() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">Courses</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-book-bookmark"></i></div>
                        <div>
                            <div class="pg-title">Course Management</div>
                            <div class="pg-sub">Define and manage institute courses</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="goNav('academic', 'courses', {action: 'add'})">
                            <i class="fa-solid fa-plus"></i> Create Course
                        </button>
                    </div>
                </div>

                <div class="card" id="courseListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading courses...</span>
                    </div>
                </div>
            </div>
        `;
        await loadCourses();
    }

    async function loadCourses() {
        const container = document.getElementById('courseListContainer');
        try {
            const res = await fetch(APP_URL + '/api/admin/courses');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            const courses = result.data;
            if (courses.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-book-open" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No courses created yet.</p>
                </div>`;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Batches</th>
                                <th>Students</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

                courses.forEach(c => {
                    html += `
                        <tr>
                            <td><span style="font-weight:700;">${c.code}</span></td>
                            <td><div style="font-weight:600;">${c.name}</div></td>
                            <td><span class="tag bg-b">${c.category.toUpperCase()}</span></td>
                            <td>${c.total_batches}</td>
                            <td>${c.total_students}</td>
                            <td style="text-align:right; white-space:nowrap;">
                                <button class="btn-icon" title="Edit" onclick="goNav('academic', 'courses', {id: ${c.id}})"><i class="fa-solid fa-pen"></i></button>
                                <button class="btn-icon" title="Manage Batches" onclick="goNav('academic', 'batches', {course_id: ${c.id}})"><i class="fa-solid fa-layer-group"></i></button>
                                <button class="btn-icon text-danger" title="Delete" onclick="deleteCourse(${c.id}, '${c.name}')"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        } catch (error) {
            container.innerHTML = `<div style="padding:20px; color:var(--red); text-align:center;">${error.message}</div>`;
        }
    }

    async function renderBatchList() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">Batches</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <div class="pg-title">Batch Management</div>
                            <div class="pg-sub">Manage course batches and schedules</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="goNav('academic', 'batches', {action: 'add'})">
                            <i class="fa-solid fa-plus"></i> New Batch
                        </button>
                    </div>
                </div>

                <div class="card" id="batchListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading batches...</span>
                    </div>
                </div>
            </div>
        `;
        await loadBatches();
    }

    async function loadBatches() {
        const container = document.getElementById('batchListContainer');
        try {
            const res = await fetch(APP_URL + '/api/admin/batches');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            const batches = result.data;
            if (batches.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-layer-group" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No batches created yet.</p>
                </div>`;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Batch Name</th>
                                <th>Course</th>
                                <th>Shift</th>
                                <th>Students</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            batches.forEach(b => {
                const statusClass = b.status === 'active' ? 'bg-t' : 'bg-b';
                html += `
                    <tr>
                        <td><div style="font-weight:600;">${b.name}</div></td>
                        <td>${b.course_name}</td>
                        <td><span class="tag bg-y">${b.shift.toUpperCase()}</span></td>
                        <td>${b.total_students} / ${b.max_strength}</td>
                        <td>${b.start_date}</td>
                        <td><span class="tag ${statusClass}">${b.status.toUpperCase()}</span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="btn-icon" title="Edit" onclick="goNav('academic', 'batches', {id: ${b.id}})"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-icon" title="Time Table"><i class="fa-solid fa-calendar-days"></i></button>
                            <button class="btn-icon text-danger" title="Delete" onclick="deleteBatch(${b.id}, '${b.name}')"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        } catch (error) {
            container.innerHTML = `<div style="padding:20px; color:var(--red); text-align:center;">${error.message}</div>`;
        }
    }

    async function renderInquiryList() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">Inquiries</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div>
                            <div class="pg-title">Admissions Inquiries</div>
                            <div class="pg-sub">Manage leads and potential students</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="goNav('inq', 'add-inq')">
                            <i class="fa-solid fa-plus"></i> New Inquiry
                        </button>
                    </div>
                </div>

                <div class="card" id="inquiryListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading inquiries...</span>
                    </div>
                </div>
            </div>
        `;
        await loadInquiries();
    }

    async function loadInquiries() {
        const container = document.getElementById('inquiryListContainer');
        try {
            const res = await fetch(APP_URL + '/api/admin/inquiries');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            const inquiries = result.data;
            if (inquiries.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-clipboard-list" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No inquiries found.</p>
                </div>`;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Inquirer</th>
                                <th>Phone</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            inquiries.forEach(i => {
                const statusClass = i.status === 'pending' ? 'bg-y' : (i.status === 'contacted' ? 'bg-b' : 'bg-t');
                html += `
                    <tr>
                        <td><div style="font-weight:600;">${i.full_name}</div></td>
                        <td>${i.phone}</td>
                        <td>${i.course_name || 'N/A'}</td>
                        <td><span class="tag ${statusClass}">${i.status.toUpperCase()}</span></td>
                        <td><span class="tag bg-b">${i.source.toUpperCase()}</span></td>
                        <td>${new Date(i.created_at).toLocaleDateString()}</td>
                        <td style="text-align:right;">
                            <button class="btn-icon" title="Follow up"><i class="fa-solid fa-phone"></i></button>
                            <button class="btn-icon" title="Convert to Admission"><i class="fa-solid fa-user-check"></i></button>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        } catch (error) {
            container.innerHTML = `<div style="padding:20px; color:var(--red); text-align:center;">${error.message}</div>`;
        }
    }

    async function renderAddInquiryForm() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('inq', 'list')">Inquiries</a> <span class="bc-sep">›</span> 
                    <span class="bc-cur">New Inquiry</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-plus"></i></div>
                        <div>
                            <div class="pg-title">Add New Inquiry</div>
                            <div class="pg-sub">Capture a new admission inquiry or lead</div>
                        </div>
                    </div>
                </div>

                <div class="card" style="max-width:800px;">
                    <form id="addInquiryForm" onsubmit="submitInquiry(event)">
                        <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group">
                                <label class="form-label required">Full Name</label>
                                <input type="text" name="full_name" class="form-control" required placeholder="Enter full name">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" required placeholder="98XXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alternative Phone</label>
                                <input type="tel" name="alt_phone" class="form-control" placeholder="Optional">
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Interested Course</label>
                                <select name="course_id" class="form-control" required id="courseSelect">
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label required">Source</label>
                                <select name="source" class="form-control" required>
                                    <option value="">Select Source</option>
                                    <option value="website">Website</option>
                                    <option value="facebook">Facebook</option>
                                    <option value="google">Google Ads</option>
                                    <option value="referral">Referral</option>
                                    <option value="walkin">Walk-in</option>
                                    <option value="phone">Phone Call</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date of Inquiry</label>
                                <input type="date" name="inquiry_date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="pending" selected>Pending</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="interested">Interested</option>
                                    <option value="not_interested">Not Interested</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full address (optional)"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes about the inquiry..."></textarea>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                            <button type="button" class="btn bs" onclick="goNav('inq', 'list')">Cancel</button>
                            <button type="submit" class="btn bt" id="submitBtn">
                                <i class="fa-solid fa-save"></i> Save Inquiry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        // Load courses for dropdown
        await loadCoursesForDropdown();
    }

    async function loadCoursesForDropdown() {
        const select = document.getElementById('courseSelect');
        try {
            const res = await fetch(APP_URL + '/api/admin/courses');
            const result = await res.json();
            if (result.success && result.data) {
                result.data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Failed to load courses:', error);
        }
    }

    window.submitInquiry = async function(e) {
        e.preventDefault();
        const form = document.getElementById('addInquiryForm');
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitBtn');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
        
        try {
            const res = await fetch(APP_URL + '/api/admin/inquiries', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                alert('Inquiry added successfully!');
                goNav('inq', 'list');
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-save"></i> Save Inquiry';
        }
    };

    async function renderInquiryAnalytics() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('inq', 'list')">Inquiries</a> <span class="bc-sep">›</span> 
                    <span class="bc-cur">Conversion Analytics</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-chart-pie"></i></div>
                        <div>
                            <div class="pg-title">Inquiry Analytics</div>
                            <div class="pg-sub">Track conversion rates and inquiry performance</div>
                        </div>
                    </div>
                </div>

                <div class="sg mb">
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-t"><i class="fa-solid fa-users"></i></div></div>
                        <div class="sc-val" id="totalInquiries">-</div>
                        <div class="sc-lbl">Total Inquiries</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-user-check"></i></div></div>
                        <div class="sc-val" id="convertedCount">-</div>
                        <div class="sc-lbl">Converted to Students</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-r"><i class="fa-solid fa-percent"></i></div></div>
                        <div class="sc-val" id="conversionRate">-</div>
                        <div class="sc-lbl">Conversion Rate</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-y"><i class="fa-solid fa-clock"></i></div></div>
                        <div class="sc-val" id="pendingCount">-</div>
                        <div class="sc-lbl">Pending Follow-ups</div>
                    </div>
                </div>

                <div class="g65 mb">
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-chart-bar"></i> Inquiries by Source</div>
                        <div id="sourceChart" style="height:200px; display:flex; align-items:flex-end; gap:15px; padding:20px; justify-content:center;">
                            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-chart-line"></i> Monthly Trend</div>
                        <div id="trendChart" style="height:200px; padding:20px;">
                            <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="ct"><i class="fa-solid fa-table-list"></i> Status Breakdown</div>
                    <div id="statusTable">
                        <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
                    </div>
                </div>
            </div>
        `;
        
        await loadInquiryAnalytics();
    }

    async function loadInquiryAnalytics() {
        try {
            const res = await fetch(APP_URL + '/api/admin/inquiries');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);
            
            const inquiries = result.data;
            const total = inquiries.length;
            const converted = inquiries.filter(i => i.status === 'converted').length;
            const pending = inquiries.filter(i => i.status === 'pending').length;
            const conversionRate = total > 0 ? Math.round((converted / total) * 100) : 0;
            
            document.getElementById('totalInquiries').textContent = total;
            document.getElementById('convertedCount').textContent = converted;
            document.getElementById('conversionRate').textContent = conversionRate + '%';
            document.getElementById('pendingCount').textContent = pending;
            
            // Source breakdown
            const sources = {};
            inquiries.forEach(i => {
                sources[i.source] = (sources[i.source] || 0) + 1;
            });
            
            const sourceHtml = Object.entries(sources).map(([source, count]) => `
                <div style="text-align:center;">
                    <div style="height:${Math.max(40, count * 10)}px; width:50px; background:var(--teal); border-radius:4px 4px 0 0; margin:0 auto;"></div>
                    <div style="margin-top:8px; font-size:12px; color:#64748b;">${source}</div>
                    <div style="font-weight:600;">${count}</div>
                </div>
            `).join('');
            document.getElementById('sourceChart').innerHTML = sourceHtml || '<div style="color:#94a3b8;">No data</div>';
            
            // Status breakdown
            const statuses = {};
            inquiries.forEach(i => {
                statuses[i.status] = (statuses[i.status] || 0) + 1;
            });
            
            const statusHtml = `
                <table class="table">
                    <thead><tr><th>Status</th><th>Count</th><th>Percentage</th></tr></thead>
                    <tbody>
                        ${Object.entries(statuses).map(([status, count]) => `
                            <tr>
                                <td><span class="tag bg-${status === 'pending' ? 'y' : status === 'contacted' ? 'b' : status === 'interested' ? 't' : 'r'}">${status.toUpperCase()}</span></td>
                                <td>${count}</td>
                                <td>${Math.round((count / total) * 100)}%</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('statusTable').innerHTML = statusHtml;
            document.getElementById('trendChart').innerHTML = '<div style="color:#94a3b8; text-align:center; padding:40px;">Monthly trend visualization coming soon</div>';
            
        } catch (error) {
            console.error('Analytics error:', error);
            document.getElementById('totalInquiries').textContent = 'Error';
        }
    }

    async function renderAdmissionForm() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('inq', 'list')">Inquiries</a> <span class="bc-sep">›</span> 
                    <span class="bc-cur">Admission Form</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-id-card"></i></div>
                        <div>
                            <div class="pg-title">Admission Form</div>
                            <div class="pg-sub">Generate and manage admission forms for prospective students</div>
                        </div>
                    </div>
                </div>

                <div class="card mb">
                    <div class="ct"><i class="fa-solid fa-filter"></i> Filter Inquiries for Admission</div>
                    <div style="display:flex; gap:15px; flex-wrap:wrap; margin-top:15px;">
                        <div style="flex:1; min-width:250px;">
                            <input type="text" id="admissionSearch" class="form-control" placeholder="Search by name or phone...">
                        </div>
                        <select id="admissionStatus" class="form-control" style="width:180px;">
                            <option value="">All Statuses</option>
                            <option value="interested">Interested</option>
                            <option value="contacted">Contacted</option>
                            <option value="pending">Pending</option>
                        </select>
                        <button class="btn bs" onclick="filterForAdmission()">Filter</button>
                    </div>
                </div>

                <div class="card" id="admissionListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading inquiries...</span>
                    </div>
                </div>
            </div>
        `;
        await loadInquiriesForAdmission();
    }

    async function loadInquiriesForAdmission(search = '', status = '') {
        const container = document.getElementById('admissionListContainer');
        try {
            let url = APP_URL + '/api/admin/inquiries';
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (params.toString()) url += '?' + params.toString();

            const res = await fetch(url);
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            const inquiries = result.data;
            if (inquiries.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-user-plus" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No inquiries found for admission.</p>
                </div>`;
                return;
            }

            let html = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Inquirer</th>
                            <th>Contact</th>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            inquiries.forEach(i => {
                const statusClass = i.status === 'pending' ? 'bg-y' : (i.status === 'contacted' ? 'bg-b' : i.status === 'interested' ? 'bg-t' : 'bg-r');
                html += `
                    <tr>
                        <td><div style="font-weight:600;">${i.full_name}</div></td>
                        <td>
                            <div style="font-size:12px;">${i.phone}</div>
                            <div style="font-size:11px; color:#64748b;">${i.email || ''}</div>
                        </td>
                        <td>${i.course_name || 'N/A'}</td>
                        <td><span class="tag ${statusClass}">${i.status.toUpperCase()}</span></td>
                        <td>${new Date(i.created_at).toLocaleDateString()}</td>
                        <td style="text-align:right;">
                            <button class="btn bt btn-sm" onclick="generateAdmissionForm(${i.id}, '${i.full_name}')">
                                <i class="fa-solid fa-file-contract"></i> Generate Form
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        } catch (error) {
            container.innerHTML = `<div style="padding:20px; color:var(--red); text-align:center;">${error.message}</div>`;
        }
    }

    window.filterForAdmission = async () => {
        const search = document.getElementById('admissionSearch').value;
        const status = document.getElementById('admissionStatus').value;
        await loadInquiriesForAdmission(search, status);
    };

    window.generateAdmissionForm = function(id, name) {
        alert('Admission form generation for: ' + name + ' (ID: ' + id + ')\nThis feature is coming soon!');
    };

    async function renderExamList() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">Exams</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-file-signature"></i></div>
                        <div>
                            <div class="pg-title">Exams & Tests</div>
                            <div class="pg-sub">Manage schedules and result publishing</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="goNav('exams', 'create-ex')">
                            <i class="fa-solid fa-plus"></i> Schedule Exam
                        </button>
                    </div>
                </div>

                <div class="card" id="examListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading exams...</span>
                    </div>
                </div>
            </div>
        `;
        await loadExams();
    }

    async function loadExams() {
        const container = document.getElementById('examListContainer');
        try {
            const res = await fetch(APP_URL + '/api/admin/exams');
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            const exams = result.data;
            if (exams.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-calendar-xmark" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No exams scheduled.</p>
                </div>`;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Batch</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            exams.forEach(e => {
                const statusClass = e.status === 'scheduled' ? 'bg-b' : (e.status === 'completed' ? 'bg-t' : 'bg-r');
                html += `
                    <tr>
                        <td><div style="font-weight:600;">${e.title}</div></td>
                        <td>${e.batch_name}</td>
                        <td><span class="tag bg-b">${e.exam_type.toUpperCase()}</span></td>
                        <td>${new Date(e.exam_date).toLocaleDateString()}</td>
                        <td><span class="tag ${statusClass}">${e.status.toUpperCase()}</span></td>
                        <td style="text-align:right;">
                            <button class="btn-icon" title="View Result" onclick="goNav('exams', 'results', {exam_id: ${e.id}})"><i class="fa-solid fa-trophy"></i></button>
                            <button class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
        } catch (error) {
            container.innerHTML = `<div style="padding:20px; color:var(--red); text-align:center;">${error.message}</div>`;
        }
    }
    
    // ── INSTITUTE PROFILE PAGE ──
    async function renderInstituteProfile() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">${CURRENT_INSTITUTE || 'Institute'}</a> 
                    <span class="bc-sep">›</span> 
                    <span class="bc-cur">Institute Profile</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <div class="pg-title">Institute Profile</div>
                            <div class="pg-sub">Manage your institute details and branding</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" id="saveProfileBtn" onclick="saveInstituteProfile()">
                            <i class="fa-solid fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
                
                <!-- Profile Form -->
                <div id="profileFormContainer">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:20px;">
                        <!-- Basic Info -->
                        <div class="sc fu">
                            <div class="sb-lbl" style="padding-left:0; margin-bottom:16px;">Basic Information</div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Institute Name (English) *</label>
                                <input type="text" id="inst_name" class="form-control" required placeholder="e.g. Pioneer Loksewa Institute">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Institute Name (Nepali)</label>
                                <input type="text" id="inst_nepali_name" class="form-control" placeholder="उदा. पायनियर लोकसेवा इन्स्टिच्युट">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Subdomain *</label>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <input type="text" id="inst_subdomain" class="form-control" required placeholder="pioneer" style="flex:1;" readonly>
                                    <span style="font-size:13px; color:var(--tl); font-weight:700;">.hamroerp.com</span>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Tagline</label>
                                <input type="text" id="inst_tagline" class="form-control" placeholder="Excellence in Education">
                            </div>
                        </div>
                        
                        <!-- Contact Info -->
                        <div class="sc fu">
                            <div class="sb-lbl" style="padding-left:0; margin-bottom:16px;">Contact Information</div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Primary Phone</label>
                                <input type="text" id="inst_phone" class="form-control" placeholder="98XXXXXXXX">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" id="inst_email" class="form-control" placeholder="info@institute.com">
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Address</label>
                                <textarea id="inst_address" class="form-control" rows="2" placeholder="Baneshwor, Kathmandu"></textarea>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Province</label>
                                <select id="inst_province" class="form-control">
                                    <option value="">Select Province</option>
                                    <option value="Province 1">Province 1</option>
                                    <option value="Province 2">Province 2</option>
                                    <option value="Province 3">Province 3 (Bagmati)</option>
                                    <option value="Gandaki">Gandaki Province</option>
                                    <option value="Lumbini">Lumbini Province</option>
                                    <option value="Karnali">Karnali Province</option>
                                    <option value="Sudurpashchim">Sudurpashchim Province</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Branding -->
                        <div class="sc fu">
                            <div class="sb-lbl" style="padding-left:0; margin-bottom:16px;">Branding</div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Brand Color</label>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <input type="color" id="inst_brand_color" class="form-control" style="width:50px; height:40px; padding:2px;" value="#009E7E">
                                    <input type="text" id="inst_brand_color_hex" class="form-control" style="flex:1;" value="#009E7E" onkeyup="document.getElementById('inst_brand_color').value = this.value">
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Logo</label>
                                <div style="display:flex; align-items:center; gap:16px;">
                                    <div id="logoPreview" style="width:80px; height:80px; border-radius:12px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; border:2px dashed #cbd5e1;">
                                        <i class="fa-solid fa-image" style="font-size:24px; color:#94a3b8;"></i>
                                    </div>
                                    <div>
                                        <input type="file" id="inst_logo" accept="image/*" style="display:none;" onchange="previewLogo(this)">
                                        <button type="button" class="btn bs" onclick="document.getElementById('inst_logo').click()">
                                            <i class="fa-solid fa-upload"></i> Upload Logo
                                        </button>
                                        <p style="font-size:11px; color:var(--tl); margin-top:4px;">PNG, JPG. Max 2MB</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Subscription Info (Read-only) -->
                        <div class="sc fu">
                            <div class="sb-lbl" style="padding-left:0; margin-bottom:16px;">Subscription Details</div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Current Plan</label>
                                <input type="text" id="inst_plan" class="form-control" readonly>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Status</label>
                                <input type="text" id="inst_status" class="form-control" readonly>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">Student Limit</label>
                                <input type="text" id="inst_student_limit" class="form-control" readonly>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label">SMS Credits</label>
                                <input type="text" id="inst_sms_credits" class="form-control" readonly>
                            </div>
                            
                            <a href="#" onclick="goNav('settings', 'brand')" class="btn bs" style="display:inline-flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-palette"></i> Customize Branding
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Load institute data
        await loadInstituteProfile();
    }
    
    async function loadInstituteProfile() {
        try {
            const res = await fetch(APP_URL + '/api/institute/profile');
            const result = await res.json();
            
            if (result.success && result.data) {
                const inst = result.data;
                document.getElementById('inst_name').value = inst.name || '';
                document.getElementById('inst_nepali_name').value = inst.nepali_name || '';
                document.getElementById('inst_subdomain').value = inst.subdomain || '';
                document.getElementById('inst_tagline').value = inst.tagline || '';
                document.getElementById('inst_phone').value = inst.phone || '';
                document.getElementById('inst_email').value = inst.email || '';
                document.getElementById('inst_address').value = inst.address || '';
                document.getElementById('inst_province').value = inst.province || '';
                document.getElementById('inst_brand_color').value = inst.brand_color || '#009E7E';
                document.getElementById('inst_brand_color_hex').value = inst.brand_color || '#009E7E';
                document.getElementById('inst_plan').value = inst.plan ? inst.plan.charAt(0).toUpperCase() + inst.plan.slice(1) : 'Starter';
                document.getElementById('inst_status').value = inst.status ? inst.status.charAt(0).toUpperCase() + inst.status.slice(1) : 'Trial';
                document.getElementById('inst_student_limit').value = inst.student_limit || 100;
                document.getElementById('inst_sms_credits').value = inst.sms_credits || 500;
                
                // Update logo preview if exists
                if (inst.logo_path) {
                    document.getElementById('logoPreview').innerHTML = 
                        `<img src="${inst.logo_path}" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">`;
                }
            }
        } catch (e) {
            console.error('Failed to load institute profile', e);
        }
    }
    
    async function saveInstituteProfile() {
        const btn = document.getElementById('saveProfileBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
        
        const formData = new FormData();
        formData.append('name', document.getElementById('inst_name').value);
        formData.append('nepali_name', document.getElementById('inst_nepali_name').value);
        formData.append('tagline', document.getElementById('inst_tagline').value);
        formData.append('phone', document.getElementById('inst_phone').value);
        formData.append('email', document.getElementById('inst_email').value);
        formData.append('address', document.getElementById('inst_address').value);
        formData.append('province', document.getElementById('inst_province').value);
        formData.append('brand_color', document.getElementById('inst_brand_color').value);
        
        // Logo file
        const logoInput = document.getElementById('inst_logo');
        if (logoInput.files.length > 0) {
            formData.append('logo', logoInput.files[0]);
        }
        
        try {
            const res = await fetch(APP_URL + '/api/institute/profile/update', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.success) {
                Swal.fire('Success', result.message || 'Profile updated successfully!', 'success');
                // Update brand color globally
                document.documentElement.style.setProperty('--inst-primary', document.getElementById('inst_brand_color').value);
            } else {
                Swal.fire('Error', result.message || 'Failed to update profile', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to save profile. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Save Changes';
        }
    }
    
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').innerHTML = 
                    `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    async function renderStaffList(role) {
        const title = role === 'teacher' ? 'Teachers' : 'Front Desk Staff';
        const icon = role === 'teacher' ? 'fa-user-tie' : 'fa-person-rays';
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">${title}</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid ${icon}"></i></div>
                        <div>
                            <div class="pg-title">${title} Directory</div>
                            <div class="pg-sub">Manage academic and administrative staff</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="goNav('${role === 'teacher' ? 'teachers' : 'frontdesk'}', 'add')">
                            <i class="fa-solid fa-plus"></i> Add New ${role === 'teacher' ? 'Teacher' : 'Operator'}
                        </button>
                    </div>
                </div>

                <div class="card" style="padding:20px; margin-bottom:20px;">
                    <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
                        <div style="flex:1; min-width:250px;">
                            <input type="text" id="staffSearch" class="form-control" placeholder="Search by name, email, phone..." onkeyup="filterStaff(this.value, '${role}')">
                        </div>
                        <div>
                            <select id="staffStatusFilter" class="form-control" onchange="filterStaff(document.getElementById('staffSearch').value, '${role}', this.value)">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div style="color:var(--tl); font-size:13px;">
                            <span id="staffCount">0</span> ${role}(s) found
                        </div>
                    </div>
                </div>

                <div class="card" id="staffListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading staff directory...</span>
                    </div>
                </div>
            </div>
        `;
        window.currentStaffData = [];
        await loadStaff(role);
    }

    // Filter staff by search text
    function filterStaff(search, role, status = '') {
        const container = document.getElementById('staffListContainer');
        const staff = window.currentStaffData || [];
        
        const searchLower = search.toLowerCase();
        const filtered = staff.filter(s => {
            const name = (s.name || '').toLowerCase();
            const email = (s.email || '').toLowerCase();
            const phone = (s.phone || '').toLowerCase();
            const matchesSearch = !search || name.includes(searchLower) || email.includes(searchLower) || phone.includes(searchLower);
            const matchesStatus = !status || s.status === status;
            return matchesSearch && matchesStatus;
        });
        
        renderStaffTable(filtered, role);
        document.getElementById('staffCount').textContent = filtered.length;
    }

    function renderStaffTable(staff, role) {
        const container = document.getElementById('staffListContainer');
        
        if (staff.length === 0) {
            container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                <i class="fa-solid fa-users-slash" style="font-size:3rem; margin-bottom:15px;"></i>
                <p>No ${role}s found.</p>
            </div>`;
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            ${role === 'teacher' ? '<th>Employee ID</th><th>Specialization</th>' : '<th>Joined Date</th>'}
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            staff.forEach(s => {
                const statusClass = s.status === 'active' ? 'bg-t' : 'bg-r';
                const displayName = s.name || 'N/A';
                const displayEmail = s.email || 'No email';
                const displayPhone = s.phone || 'No phone';
                const displaySpec = s.specialization || 'General';
                const displayEmpId = s.employee_id || 'N/A';
                
                html += `
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:32px; height:32px; border-radius:50%; background:var(--teal); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700;">
                                    ${displayName.charAt(0)}
                                </div>
                                <div style="font-weight:600;">${displayName}</div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px;">${displayEmail}</div>
                            <div style="font-size:11px; color:var(--tl);">${displayPhone}</div>
                        </td>
                        ${role === 'teacher' ? `
                            <td><span class="tag bg-b">${displayEmpId}</span></td>
                            <td>${displaySpec}</td>
                        ` : `
                            <td>${new Date(s.created_at).toLocaleDateString()}</td>
                        `}
                        <td><span class="tag ${statusClass}">${s.status.toUpperCase()}</span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <button class="btn bt" style="padding:6px 12px; font-size:12px; margin-right:5px;" onclick="editStaff('${role}', ${s.user_id})">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            <button class="btn br" style="padding:6px 12px; font-size:12px;" onclick="deleteStaff('${role}', ${s.user_id}, '${displayName}')">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            container.innerHTML = html;
    }

    // Edit Staff - fetch data and show edit form
    async function editStaff(role, userId) {
        const title = role === 'teacher' ? 'Teacher' : 'Front Desk Operator';
        
        try {
            // Fetch the specific staff member
            const res = await fetch(APP_URL + `/api/admin/staff?role=${role}`, {
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            const result = await res.json();
            
            if (!result.success) throw new Error(result.message);
            
            const staff = result.data.find(s => s.user_id === userId);
            if (!staff) throw new Error('Staff member not found');
            
            renderEditStaffForm(role, staff);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    // Render Edit Staff Form
    function renderEditStaffForm(role, staff) {
        const title = role === 'teacher' ? 'Teacher' : 'Front Desk Operator';
        const displayName = staff.full_name || staff.name || '';
        
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('${role === 'teacher' ? 'teachers' : 'frontdesk'}', 'profiles')">${role === 'teacher' ? 'Teachers' : 'Front Desk'}</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Edit ${title}</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-pen"></i></div>
                        <div>
                            <div class="pg-title">Edit ${title}</div>
                            <div class="pg-sub">Update credentials and profile</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="editStaffForm">
                        <input type="hidden" name="role" value="${role}">
                        <input type="hidden" name="user_id" value="${staff.user_id}">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required value="${displayName}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="${staff.email || ''}" disabled>
                                <small style="color:var(--tl);">Email cannot be changed</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="${staff.phone || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" ${staff.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="inactive" ${staff.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            
                            ${role === 'teacher' ? `
                                <div class="form-group">
                                    <label class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" value="${staff.employee_id || 'N/A'}" disabled>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" name="specialization" class="form-control" value="${staff.specialization || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Qualification</label>
                                    <input type="text" name="qualification" class="form-control" value="${staff.qualification || ''}">
                                </div>
                            ` : ''}
                        </div>
                        <div style="margin-top:30px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('${role === 'teacher' ? 'teachers' : 'frontdesk'}', 'profiles')">Cancel</button>
                            <button type="submit" class="btn bt">Update ${title}</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.getElementById('editStaffForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Updating...';

            try {
                const res = await fetch(APP_URL + '/api/admin/staff', {
                    method: 'PUT',
                    credentials: 'include',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        goNav(role === 'teacher' ? 'teachers' : 'frontdesk', 'profiles');
                    });
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `Update ${title}`;
            }
        };
    }

    // Delete Staff
    async function deleteStaff(role, userId, staffName) {
        const result = await Swal.fire({
            title: 'Delete Staff?',
            text: `Are you sure you want to delete "${staffName}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await fetch(APP_URL + '/api/admin/staff', {
                method: 'DELETE',
                credentials: 'include',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ user_id: userId, role: role })
            });
            const data = await res.json();
            
            if (data.success) {
                Swal.fire('Deleted!', data.message, 'success').then(() => {
                    loadStaff(role);
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    function renderAddStaffForm(role) {
        const title = role === 'teacher' ? 'Teacher' : 'Front Desk Operator';
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('${role === 'teacher' ? 'teachers' : 'frontdesk'}', 'profiles')">${role === 'teacher' ? 'Teachers' : 'Front Desk'}</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Add ${title}</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-plus"></i></div>
                        <div>
                            <div class="pg-title">Add New ${title}</div>
                            <div class="pg-sub">Setup credentials and profile for new staff member</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="addStaffForm">
                        <input type="hidden" name="role" value="${role}">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="Display Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required placeholder="login@institute.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Temporary Password</label>
                                <input type="text" name="password" class="form-control" value="Staff@123">
                                <small style="color:var(--tl);">User will be prompted to change on first login</small>
                            </div>
                            
                            ${role === 'teacher' ? `
                                <div class="form-group">
                                    <label class="form-label">Employee ID</label>
                                    <input type="text" name="employee_id" class="form-control" placeholder="TCH-00X">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" name="specialization" class="form-control" placeholder="e.g. Mathematics, Nepali">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Employment Type</label>
                                    <select name="employment_type" class="form-control">
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="visiting">Visiting Faculty</option>
                                    </select>
                                </div>
                            ` : ''}
                        </div>
                        <div style="margin-top:30px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('${role === 'teacher' ? 'teachers' : 'frontdesk'}', 'list')">Cancel</button>
                            <button type="submit" class="btn bt">Create ${title}</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.getElementById('addStaffForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Creating...';

            try {
                const res = await fetch(APP_URL + '/api/admin/staff', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        goNav(role === 'teacher' ? 'teachers' : 'frontdesk', 'list');
                    });
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = `Create ${title}`;
            }
        };
    }

    // ── DATE CONVERSION HELPER ──
    function setupDateConversion(adInputId, bsInputId) {
        const adInput = document.getElementById(adInputId);
        const bsInput = document.getElementById(bsInputId);
        if (!adInput || !bsInput) return;

        let debounceTimer = null;

        function showSpinner(input) {
            const wrapper = input.closest('.dob-field');
            if (wrapper) {
                let spinner = wrapper.querySelector('.dob-spinner');
                if (!spinner) {
                    spinner = document.createElement('span');
                    spinner.className = 'dob-spinner';
                    spinner.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="color:var(--brand); font-size:12px;"></i>';
                    spinner.style.cssText = 'position:absolute; right:10px; top:50%; transform:translateY(-50%);';
                    wrapper.style.position = 'relative';
                    wrapper.appendChild(spinner);
                }
                spinner.style.display = 'inline';
            }
        }

        function hideSpinner(input) {
            const wrapper = input.closest('.dob-field');
            if (wrapper) {
                const spinner = wrapper.querySelector('.dob-spinner');
                if (spinner) spinner.style.display = 'none';
            }
        }

        async function convertDate(date, type, targetInput) {
            if (!date || date.length < 10) return;
            if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return;

            showSpinner(targetInput);
            try {
                const res = await fetch(APP_URL + `/api/admin/date-convert?date=${encodeURIComponent(date)}&type=${type}`);
                const result = await res.json();
                if (result.success && result.converted) {
                    targetInput.value = result.converted;
                    // Flash green border briefly
                    targetInput.style.borderColor = 'var(--brand)';
                    setTimeout(() => { targetInput.style.borderColor = ''; }, 1500);
                }
            } catch (err) {
                console.warn('Date conversion failed:', err);
            } finally {
                hideSpinner(targetInput);
            }
        }

        // AD changed → convert to BS
        adInput.addEventListener('change', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => convertDate(adInput.value, 'ad', bsInput), 300);
        });

        // BS changed → convert to AD
        bsInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => convertDate(bsInput.value, 'bs', adInput), 500);
        });
    }

    async function renderAddStudentForm() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('students', 'all')">Students</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Add New Student</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-plus"></i></div>
                        <div>
                            <div class="pg-title">New Admission</div>
                            <div class="pg-sub">Follow the 3-step process to enroll a new student</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:900px; margin:0 auto; padding:40px;">
                    <!-- Stepper -->
                    <div class="stepper" style="display:flex; justify-content:space-between; margin-bottom:40px; position:relative;">
                        <div style="position:absolute; top:18px; left:0; right:0; height:2px; background:#e2e8f0; z-index:1;"></div>
                        <div id="step-line" style="position:absolute; top:18px; left:0; width:0%; height:2px; background:var(--brand); z-index:2; transition:0.3s;"></div>
                        
                        <div class="step-item active" id="step-head-1" style="position:relative; z-index:3; text-align:center; flex:1;">
                            <div class="step-dot" style="width:36px; height:36px; border-radius:50%; background:#fff; border:2px solid #cbd5e1; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; transition:0.3s; font-weight:700;">1</div>
                            <div style="font-size:12px; font-weight:600; color:#64748b;">Basic Info</div>
                        </div>
                        <div class="step-item" id="step-head-2" style="position:relative; z-index:3; text-align:center; flex:1;">
                            <div class="step-dot" style="width:36px; height:36px; border-radius:50%; background:#fff; border:2px solid #cbd5e1; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; transition:0.3s; font-weight:700;">2</div>
                            <div style="font-size:12px; font-weight:600; color:#64748b;">Guardian & Academic</div>
                        </div>
                        <div class="step-item" id="step-head-3" style="position:relative; z-index:3; text-align:center; flex:1;">
                            <div class="step-dot" style="width:36px; height:36px; border-radius:50%; background:#fff; border:2px solid #cbd5e1; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; transition:0.3s; font-weight:700;">3</div>
                            <div style="font-size:12px; font-weight:600; color:#64748b;">Account</div>
                        </div>
                    </div>

                    <form id="studentAddForm">
                        <!-- Step 1: Basic Info -->
                        <div id="step-1" class="reg-step">
                            <div class="sc-lbl mb" style="padding-left:0;">General Information</div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" required placeholder="Student's legal name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Batch *</label>
                                    <select name="batch_id" id="formBatchId" class="form-control" required>
                                        <option value="">Select Batch</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Roll Number</label>
                                    <input type="text" name="roll_no" class="form-control" placeholder="Optional">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Admission Date</label>
                                    <input type="date" name="admission_date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Blood Group</label>
                                    <select name="blood_group" class="form-control">
                                        <option value="">Unknown</option>
                                        <option value="A+">A+</option><option value="A-">A-</option>
                                        <option value="B+">B+</option><option value="B-">B-</option>
                                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                        <option value="O+">O+</option><option value="O-">O-</option>
                                    </select>
                                </div>
                            </div>
                            <div style="display:flex; justify-content:flex-end; margin-top:30px;">
                                <button type="button" class="btn bt" onclick="setRegStep(2)">Next <i class="fa-solid fa-arrow-right"></i></button>
                            </div>
                        </div>

                        <!-- Step 2: Guardian & Academic -->
                        <div id="step-2" class="reg-step" style="display:none;">
                            <div class="sc-lbl mb" style="padding-left:0;">Guardian & Personal Info</div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                                <div class="form-group">
                                    <label class="form-label">Father's Name</label>
                                    <input type="text" name="father_name" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Mother's Name</label>
                                    <input type="text" name="mother_name" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Birth (AD) <span style="font-size:10px; color:#94a3b8; font-weight:400;">English Calendar</span></label>
                                    <div class="dob-field">
                                        <input type="date" name="dob_ad" id="add_dob_ad" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Birth (BS) <span style="font-size:10px; color:#94a3b8; font-weight:400;">नेपाली मिति</span></label>
                                    <div class="dob-field">
                                        <input type="text" name="dob_bs" id="add_dob_bs" class="form-control" placeholder="YYYY-MM-DD (e.g. 2055-04-15)">
                                    </div>
                                    <small style="color:#64748b; font-size:11px;"><i class="fa-solid fa-arrows-rotate" style="margin-right:4px;"></i>Fill either date — the other converts automatically</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="permanent_address" class="form-control" placeholder="Full Address">
                                </div>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-top:30px;">
                                <button type="button" class="btn bs" onclick="setRegStep(1)"><i class="fa-solid fa-arrow-left"></i> Previous</button>
                                <button type="button" class="btn bt" onclick="setRegStep(3)">Next <i class="fa-solid fa-arrow-right"></i></button>
                            </div>
                        </div>

                        <!-- Step 3: Account Credentials -->
                        <div id="step-3" class="reg-step" style="display:none;">
                            <div class="sc-lbl mb" style="padding-left:0;">Student Login Account</div>
                            <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px dashed #cbd5e1; margin-bottom:20px;">
                                <div style="display:grid; grid-template-columns:1fr; gap:20px;">
                                    <div class="form-group">
                                        <label class="form-label">Account Email (Login ID) *</label>
                                        <input type="email" name="email" id="regEmail" class="form-control" placeholder="student@example.com">
                                        <small style="color:#64748b;">This will be used for student portal access</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Initial Password</label>
                                        <div style="display:flex; gap:10px;">
                                            <input type="text" name="password" class="form-control" value="Student@123">
                                            <button type="button" class="btn bs" style="padding:0 15px;" onclick="this.previousElementSibling.value = 'Std' + Math.floor(1000 + Math.random() * 9000)">Gen</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-top:30px;">
                                <button type="button" class="btn bs" onclick="setRegStep(2)"><i class="fa-solid fa-arrow-left"></i> Previous</button>
                                <button type="submit" class="btn bt" style="background:var(--brand); color:#fff;">
                                    <i class="fa-solid fa-check-circle"></i> Complete Registration
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <style>
                .step-dot.active { background:var(--brand) !important; color:#fff !important; border-color:var(--brand) !important; box-shadow:0 0 0 4px rgba(0, 109, 68, 0.1); }
                .step-dot.done { background:var(--brand) !important; color:#fff !important; border-color:var(--brand) !important; }
                .step-item.active div:last-child { color:var(--brand) !important; }
            </style>
        `;

        await populateBatches('formBatchId');
        setupDateConversion('add_dob_ad', 'add_dob_bs');

        window.setRegStep = function(step) {
            // Validation for step 1
            if (step > 1) {
                const name = document.querySelector('[name="full_name"]').value;
                const batch = document.querySelector('[name="batch_id"]').value;
                if (!name || !batch) {
                    Swal.fire('Required Fields', 'Please fill in Name and Batch before proceeding.', 'info');
                    return;
                }
            }
            if (step === 3) {
                 // Pre-fill email if it's currently empty but maybe phone was filled? Optional.
            }

            document.querySelectorAll('.reg-step').forEach(s => s.style.display = 'none');
            const activeStep = document.getElementById('step-' + step);
            activeStep.style.display = 'block';
            // Scroll the active step into view so the submit button is always visible
            activeStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Update Stepper
            const line = document.getElementById('step-line');
            line.style.width = (step === 1 ? '0' : step === 2 ? '50%' : '100%') + '';
            
            for (let i = 1; i <= 3; i++) {
                const head = document.getElementById('step-head-' + i);
                const dot = head.querySelector('.step-dot');
                if (i < step) {
                    dot.className = 'step-dot done';
                    dot.innerHTML = '<i class="fa-solid fa-check"></i>';
                } else if (i === step) {
                    dot.className = 'step-dot active';
                    dot.innerHTML = i;
                } else {
                    dot.className = 'step-dot';
                    dot.innerHTML = i;
                }
            }
        };

        document.getElementById('studentAddForm').onsubmit = (e) => submitStudentForm(e, 'POST');
    }

    async function renderEditStudentForm(id) {
        if (!id) {
            Swal.fire('Error', 'Invalid Student ID', 'error');
            goNav('students', 'all');
            return;
        }

        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('students', 'all')">Students</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Edit Student</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-pen"></i></div>
                        <div>
                            <div class="pg-title">Edit Profile</div>
                            <div class="pg-sub">Update student academic and personal info</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:1000px; margin:0 auto; padding:30px;">
                    <form id="studentEditForm">
                        <input type="hidden" name="id" value="${id}">
                        <div class="sc-lbl mb" style="padding-left:0;">Academic Information</div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:30px;">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Roll Number</label>
                                <input type="text" name="roll_no" id="edit_roll_no" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Batch *</label>
                                <select name="batch_id" id="edit_batch_id" class="form-control" required>
                                    <option value="">Select Batch</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="alumni">Alumni</option>
                                    <option value="dropped">Dropped</option>
                                </select>
                            </div>
                        </div>

                        <div class="sc-lbl mb" style="padding-left:0; margin-top:30px;">Date of Birth</div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; margin-bottom:30px;">
                            <div class="form-group">
                                <label class="form-label">DOB (AD) <span style="font-size:10px; color:#94a3b8; font-weight:400;">English Calendar</span></label>
                                <div class="dob-field">
                                    <input type="date" name="dob_ad" id="edit_dob_ad" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">DOB (BS) <span style="font-size:10px; color:#94a3b8; font-weight:400;">नेपाली मिति</span></label>
                                <div class="dob-field">
                                    <input type="text" name="dob_bs" id="edit_dob_bs" class="form-control" placeholder="YYYY-MM-DD">
                                </div>
                                <small style="color:#64748b; font-size:11px;"><i class="fa-solid fa-arrows-rotate" style="margin-right:4px;"></i>Fill either date — the other converts automatically</small>
                            </div>
                        </div>

                        <div style="margin-top:40px; border-top:1px solid #eee; padding-top:20px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('students', 'all')">Cancel</button>
                            <button type="submit" class="btn bt"><i class="fa-solid fa-save"></i> Update Student</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        await populateBatches('edit_batch_id');
        await loadStudentData(id);
        setupDateConversion('edit_dob_ad', 'edit_dob_bs');

        document.getElementById('studentEditForm').onsubmit = (e) => submitStudentForm(e, 'PUT');
    }

    async function populateBatches(selectId) {
        const select = document.getElementById(selectId);
        try {
            const res = await fetch(APP_URL + '/api/admin/batches');
            const data = await res.json();
            if (data.success) {
                data.data.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = `${b.name} (${b.course_name})`;
                    select.appendChild(opt);
                });
            }
        } catch (e) { console.error('Failed to load batches', e); }
    }

    async function loadStudentData(id) {
        try {
            const res = await fetch(APP_URL + `/api/admin/students?id=${id}`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                const s = data.data[0];
                document.getElementById('edit_full_name').value = s.full_name;
                document.getElementById('edit_roll_no').value = s.roll_no;
                document.getElementById('edit_batch_id').value = s.batch_id;
                document.getElementById('edit_status').value = s.status;
                if (s.dob_ad) document.getElementById('edit_dob_ad').value = s.dob_ad;
                if (s.dob_bs) document.getElementById('edit_dob_bs').value = s.dob_bs;
            }
        } catch (e) {
            console.error('Failed to load student data', e);
            Swal.fire('Error', 'Failed to fetch student details', 'error');
        }
    }

    async function submitStudentForm(e, method) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        try {
            const res = await fetch(APP_URL + '/api/admin/students', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            
            if (result.success) {
                Swal.fire('Success', result.message, 'success').then(() => {
                    goNav('students', 'all');
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    window.deleteStudent = async (id, name) => {
        const result = await Swal.fire({
            title: 'Delete Student?',
            text: `Are you sure you want to delete "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch(APP_URL + `/api/admin/students`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => loadStudents());
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    }

    async function renderAddBatchForm() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('academic', 'batches')">Batches</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">New Batch</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-layer-plus"></i></div>
                        <div>
                            <div class="pg-title">Create New Batch</div>
                            <div class="pg-sub">Setup a new class schedule</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="batchAddForm">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Course *</label>
                                <select name="course_id" id="batchCourseSelect" class="form-control" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Batch Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Kharidar 2081 Morning">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Shift</label>
                                <select name="shift" class="form-control">
                                    <option value="morning">Morning</option>
                                    <option value="day">Day</option>
                                    <option value="evening">Evening</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Strength</label>
                                <input type="number" name="max_strength" class="form-control" value="40">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required value="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date (Optional)</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room / Hall</label>
                                <input type="text" name="room" class="form-control" placeholder="Room 101">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:30px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('academic', 'batches')">Cancel</button>
                            <button type="submit" class="btn bt">Create Batch</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        await populateCourses('batchCourseSelect');
        document.getElementById('batchAddForm').onsubmit = (e) => submitBatchForm(e, 'POST');
    }

    async function renderEditBatchForm(id) {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('academic', 'batches')">Batches</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Edit Batch</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <div class="pg-title">Edit Batch</div>
                            <div class="pg-sub">Update batch details and schedule</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="batchEditForm">
                        <input type="hidden" name="id" value="${id}">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Course *</label>
                                <select name="course_id" id="editBatchCourseSelect" class="form-control" required disabled>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Batch Name *</label>
                                <input type="text" name="name" id="editBatchName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Shift</label>
                                <select name="shift" id="editBatchShift" class="form-control">
                                    <option value="morning">Morning</option>
                                    <option value="day">Day</option>
                                    <option value="evening">Evening</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Strength</label>
                                <input type="number" name="max_strength" id="editBatchMax" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date *</label>
                                <input type="date" name="start_date" id="editBatchStart" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date (Optional)</label>
                                <input type="date" name="end_date" id="editBatchEnd" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room / Hall</label>
                                <input type="text" name="room" id="editBatchRoom" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" id="editBatchStatus" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:30px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('academic', 'batches')">Cancel</button>
                            <button type="submit" class="btn bt">Update Batch</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        await populateCourses('editBatchCourseSelect');
        await loadBatchData(id);
        document.getElementById('batchEditForm').onsubmit = (e) => submitBatchForm(e, 'PUT');
    }

    async function populateCourses(selectId) {
        const select = document.getElementById(selectId);
        try {
            const res = await fetch(APP_URL + '/api/admin/courses');
            const data = await res.json();
            if (data.success) {
                data.data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${c.name} (${c.code})`;
                    select.appendChild(opt);
                });
            }
        } catch (e) { console.error('Failed to load courses', e); }
    }

    async function loadBatchData(id) {
        try {
            const res = await fetch(APP_URL + `/api/admin/batches?id=${id}`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                const b = data.data[0];
                document.getElementById('editBatchCourseSelect').value = b.course_id;
                document.getElementById('editBatchName').value = b.name;
                document.getElementById('editBatchShift').value = b.shift;
                document.getElementById('editBatchMax').value = b.max_strength;
                document.getElementById('editBatchStart').value = b.start_date;
                document.getElementById('editBatchEnd').value = b.end_date || '';
                document.getElementById('editBatchRoom').value = b.room || '';
                document.getElementById('editBatchStatus').value = b.status;
            }
        } catch (e) { console.error('Failed to load batch data', e); }
    }

    async function submitBatchForm(e, method) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        try {
            const res = await fetch(APP_URL + '/api/admin/batches', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            
            if (result.success) {
                Swal.fire('Success', result.message, 'success').then(() => {
                    goNav('academic', 'batches');
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    window.deleteBatch = async (id, name) => {
        const result = await Swal.fire({
            title: 'Delete Batch?',
            text: `Delete "${name}"? This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch(APP_URL + `/api/admin/batches`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => renderBatchList());
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    }

    async function renderAddCourseForm() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('academic', 'courses')">Courses</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">New Course</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-folder-plus"></i></div>
                        <div>
                            <div class="pg-title">Define Course</div>
                            <div class="pg-sub">Add a new educational program</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="courseAddForm">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group">
                                <label class="form-label">Course Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Civil Engineering License">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Course Code *</label>
                                <input type="text" name="code" class="form-control" required placeholder="e.g. CEL-2081">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control">
                                    <option value="general">General</option>
                                    <option value="loksewa">Loksewa</option>
                                    <option value="health">Health</option>
                                    <option value="banking">Banking</option>
                                    <option value="tsc">TSC</option>
                                    <option value="engineering">Engineering</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration (Weeks)</label>
                                <input type="number" name="duration_weeks" class="form-control" placeholder="12">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Seats</label>
                                <input type="number" name="seats" class="form-control" placeholder="100">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Brief course overview..."></textarea>
                            </div>
                        </div>
                        <div style="margin-top:30px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('academic', 'courses')">Cancel</button>
                            <button type="submit" class="btn bt">Save Course</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.getElementById('courseAddForm').onsubmit = (e) => submitCourseForm(e, 'POST');
    }

    async function renderEditCourseForm(id) {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> 
                    <a href="#" onclick="goNav('academic', 'courses')">Courses</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Edit Course</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-pen-to-square"></i></div>
                        <div>
                            <div class="pg-title">Edit Course</div>
                            <div class="pg-sub">Update course details and requirements</div>
                        </div>
                    </div>
                </div>

                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="courseEditForm">
                        <input type="hidden" name="id" value="${id}">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group">
                                <label class="form-label">Course Name *</label>
                                <input type="text" name="name" id="editCourseName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Course Code *</label>
                                <input type="text" name="code" id="editCourseCode" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category" id="editCourseCategory" class="form-control">
                                    <option value="general">General</option>
                                    <option value="loksewa">Loksewa</option>
                                    <option value="health">Health</option>
                                    <option value="banking">Banking</option>
                                    <option value="tsc">TSC</option>
                                    <option value="engineering">Engineering</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Duration (Weeks)</label>
                                <input type="number" name="duration_weeks" id="editCourseDur" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Seats</label>
                                <input type="number" name="seats" id="editCourseSeats" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="is_active" id="editCourseStatus" class="form-control">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="editCourseDesc" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                        <div style="margin-top:30px; display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" class="btn bs" onclick="goNav('academic', 'courses')">Cancel</button>
                            <button type="submit" class="btn bt">Update Course</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        await loadCourseData(id);
        document.getElementById('courseEditForm').onsubmit = (e) => submitCourseForm(e, 'PUT');
    }

    async function loadCourseData(id) {
        try {
            const res = await fetch(APP_URL + `/api/admin/courses?id=${id}`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                const c = data.data[0];
                document.getElementById('editCourseName').value = c.name;
                document.getElementById('editCourseCode').value = c.code;
                document.getElementById('editCourseCategory').value = c.category;
                document.getElementById('editCourseDur').value = c.duration_weeks || '';
                document.getElementById('editCourseSeats').value = c.seats || '';
                document.getElementById('editCourseDesc').value = c.description || '';
                document.getElementById('editCourseStatus').value = c.is_active;
            }
        } catch (e) {
            console.error('Failed to load course data', e);
            Swal.fire('Error', 'Failed to fetch course details', 'error');
        }
    }

    async function submitCourseForm(e, method) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

        try {
            const res = await fetch(APP_URL + '/api/admin/courses', {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            
            if (result.success) {
                Swal.fire('Success', result.message, 'success').then(() => {
                    goNav('academic', 'courses');
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    window.deleteCourse = async (id, name) => {
        const result = await Swal.fire({
            title: 'Delete Course?',
            text: `Are you sure you want to delete "${name}"? This will only work if there are no active batches.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch(APP_URL + `/api/admin/courses`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => renderCourseList());
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    }

    // ─── TIMETABLE BUILDER ───────────────────────────────────────────

    let currentTimetableData = [];

    window.renderTimetablePage = async function() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span> <span class="bc-cur">Timetable Builder</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-calendar-plus"></i></div>
                        <div>
                            <div class="pg-title">Timetable Builder</div>
                            <div class="pg-sub">Manage class schedules and teacher assignments</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="openTimetableAddModal()">
                            <i class="fa-solid fa-plus"></i> Add Slot
                        </button>
                    </div>
                </div>

                <div class="tt-filters card">
                    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <select id="ttBatchFilter" class="tt-select" onchange="loadTimetableData()">
                            <option value="">All Batches</option>
                        </select>
                        <button class="btn tt-btn-secondary" onclick="loadTimetableData()">
                            <i class="fa-solid fa-rotate"></i> Refresh
                        </button>
                    </div>
                </div>

                <div id="timetableGrid" class="tt-grid">
                    <div class="tt-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading timetable...</span>
                    </div>
                </div>
            </div>

            <!-- Timetable Modal -->
            <div id="ttModal" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <div class="modal-title" id="ttModalTitle">Add Timetable Slot</div>
                        <button class="modal-close" onclick="closeTimetableModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="ttForm">
                            <input type="hidden" id="ttSlotId">
                            <div class="form-group">
                                <label class="form-label">Batch *</label>
                                <select id="ttSlotBatch" class="form-select" required></select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Teacher *</label>
                                <select id="ttSlotTeacher" class="form-select" required></select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <input type="text" id="ttSlotSubject" class="form-input" placeholder="e.g. Mathematics" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Day of Week *</label>
                                <select id="ttSlotDay" class="form-select" required>
                                    <option value="1">Sunday</option>
                                    <option value="2">Monday</option>
                                    <option value="3">Tuesday</option>
                                    <option value="4">Wednesday</option>
                                    <option value="5">Thursday</option>
                                    <option value="6">Friday</option>
                                    <option value="7">Saturday</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Start Time *</label>
                                    <input type="time" id="ttSlotStart" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">End Time *</label>
                                    <input type="time" id="ttSlotEnd" class="form-input" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room / Hall</label>
                                <input type="text" id="ttSlotRoom" class="form-input" placeholder="e.g. Room 101">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Online Link (Optional)</label>
                                <input type="url" id="ttSlotLink" class="form-input" placeholder="https://zoom.us/j/...">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button id="ttDeleteBtn" class="btn tt-btn-danger" style="display:none; margin-right:auto;" onclick="deleteTimetableSlot()">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                        <button class="btn tt-btn-secondary" onclick="closeTimetableModal()">Cancel</button>
                        <button class="btn tt-btn-primary" onclick="saveTimetableSlot()">Save Slot</button>
                    </div>
                </div>
            </div>
        `;

        // Load dependencies
        await Promise.all([
            loadTimetableBatches(),
            loadTimetableTeachers(),
            loadTimetableData()
        ]);
    };

    async function loadTimetableBatches() {
        const filter = document.getElementById('ttBatchFilter');
        const modalSelect = document.getElementById('ttSlotBatch');
        if (!filter || !modalSelect) return;

        try {
            const res = await fetch(APP_URL + '/api/admin/batches');
            const data = await res.json();
            if (data.success) {
                const batches = data.data;
                let html = '<option value="">All Batches</option>';
                let modalHtml = '<option value="">Select Batch</option>';
                batches.forEach(b => {
                    html += `<option value="${b.id}">${b.name}</option>`;
                    modalHtml += `<option value="${b.id}">${b.name}</option>`;
                });
                filter.innerHTML = html;
                modalSelect.innerHTML = modalHtml;
            }
        } catch (e) { console.error('Error loading timetable batches:', e); }
    }

    async function loadTimetableTeachers() {
        const select = document.getElementById('ttSlotTeacher');
        if (!select) return;

        try {
            const res = await fetch(APP_URL + '/api/admin/staff?role=teacher');
            const data = await res.json();
            if (data.success) {
                const teachers = data.data;
                let html = '<option value="">Select Teacher</option>';
                teachers.forEach(t => {
                    html += `<option value="${t.id}">${t.full_name || t.name}</option>`;
                });
                select.innerHTML = html;
            }
        } catch (e) { console.error('Error loading timetable teachers:', e); }
    }

    window.loadTimetableData = async function() {
        const grid = document.getElementById('timetableGrid');
        const batchId = document.getElementById('ttBatchFilter').value;
        if (!grid) return;

        grid.innerHTML = '<div class="tt-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>';

        try {
            const url = batchId ? `${APP_URL}/api/admin/timetable?batch_id=${batchId}` : `${APP_URL}/api/admin/timetable`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                currentTimetableData = data.data || [];
                renderTimetableGrid(data.grouped || []);
            } else {
                grid.innerHTML = `<div class="tt-empty">${data.message || 'Error loading data'}</div>`;
            }
        } catch (e) {
            grid.innerHTML = '<div class="tt-empty">Failed to load timetable</div>';
        }
    };

    function renderTimetableGrid(grouped) {
        const grid = document.getElementById('timetableGrid');
        const days = ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        let html = '';
        for (let i = 1; i <= 7; i++) {
            const dayGroup = grouped.find(g => g.day_of_week == i);
            const slots = dayGroup ? dayGroup.slots : [];
            const isSunday = i === 1;

            html += `
                <div class="tt-day-column">
                    <div class="tt-day-header ${isSunday ? 'sunday' : ''}">${days[i]}</div>
            `;

            if (slots.length === 0) {
                html += '<div class="tt-empty">No classes</div>';
            } else {
                slots.forEach(slot => {
                    html += `
                        <div class="tt-slot" onclick="openTimetableEditModal(${slot.id})">
                            <div class="tt-slot-time">${formatTTTime(slot.start_time)} - ${formatTTTime(slot.end_time)}</div>
                            <div class="tt-slot-subject">${slot.subject}</div>
                            <div class="tt-slot-teacher">${slot.teacher_name || 'No teacher'}</div>
                            ${slot.room ? `<div class="tt-slot-room"><i class="fa-solid fa-location-dot"></i> ${slot.room}</div>` : ''}
                        </div>
                    `;
                });
            }
            html += '</div>';
        }
        grid.innerHTML = html;
    }

    function formatTTTime(time) {
        if (!time) return '';
        const parts = time.split(':');
        let h = parseInt(parts[0]);
        const m = parts[1];
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${m} ${ampm}`;
    }

    window.openTimetableAddModal = function() {
        document.getElementById('ttModalTitle').textContent = 'Add Timetable Slot';
        document.getElementById('ttSlotId').value = '';
        document.getElementById('ttForm').reset();
        document.getElementById('ttDeleteBtn').style.display = 'none';
        document.getElementById('ttModal').classList.add('active');
    };

    window.openTimetableEditModal = function(id) {
        const slot = currentTimetableData.find(s => s.id == id);
        if (!slot) return;

        document.getElementById('ttModalTitle').textContent = 'Edit Timetable Slot';
        document.getElementById('ttSlotId').value = slot.id;
        document.getElementById('ttSlotBatch').value = slot.batch_id;
        document.getElementById('ttSlotTeacher').value = slot.teacher_id;
        document.getElementById('ttSlotSubject').value = slot.subject;
        document.getElementById('ttSlotDay').value = slot.day_of_week;
        document.getElementById('ttSlotStart').value = slot.start_time;
        document.getElementById('ttSlotEnd').value = slot.end_time;
        document.getElementById('ttSlotRoom').value = slot.room || '';
        document.getElementById('ttSlotLink').value = slot.online_link || '';
        
        document.getElementById('ttDeleteBtn').style.display = 'inline-flex';
        document.getElementById('ttModal').classList.add('active');
    };

    window.closeTimetableModal = function() {
        document.getElementById('ttModal').classList.remove('active');
    };

    window.saveTimetableSlot = async function() {
        const id = document.getElementById('ttSlotId').value;
        const form = document.getElementById('ttForm');
        
        if (!form.reportValidity()) return;

        const payload = {
            action: id ? 'update' : 'create',
            id: id,
            batch_id: document.getElementById('ttSlotBatch').value,
            teacher_id: document.getElementById('ttSlotTeacher').value,
            subject: document.getElementById('ttSlotSubject').value,
            day_of_week: document.getElementById('ttSlotDay').value,
            start_time: document.getElementById('ttSlotStart').value,
            end_time: document.getElementById('ttSlotEnd').value,
            room: document.getElementById('ttSlotRoom').value,
            online_link: document.getElementById('ttSlotLink').value
        };

        try {
            const res = await fetch(APP_URL + '/api/admin/timetable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                closeTimetableModal();
                loadTimetableData();
                Swal.fire('Success', data.message, 'success');
            } else {
                Swal.fire('Conflict / Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to save timetable slot', 'error');
        }
    };

    window.deleteTimetableSlot = async function() {
        const id = document.getElementById('ttSlotId').value;
        if (!id) return;

        const result = await Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently remove this slot.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Yes, delete it!'
        });

        if (result.isConfirmed) {
            try {
                const res = await fetch(APP_URL + '/api/admin/timetable', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                const data = await res.json();
                if (data.success) {
                    closeTimetableModal();
                    loadTimetableData();
                    Swal.fire('Deleted', 'Slot has been removed.', 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Failed to delete slot', 'error');
            }
        }
    };

    // Init
    renderSidebar();
    renderPage();
});
