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
            { id: "transfer", l: "Batch Transfer", icon: "fa-right-left" },
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
            { id: "profiles", l: "Teacher Profiles", icon: "fa-id-badge" },
            { id: "allocation", l: "Subject Allocation", icon: "fa-book-open-reader" },
            { id: "salary", l: "Salary Management", icon: "fa-wallet" },
            { id: "performance", l: "Performance Analytics", icon: "fa-chart-simple" }
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
        { id: "accounting", icon: "fa-calculator", label: "Accounting", sub: [
            { id: "dashboard", l: "Dashboard", icon: "fa-gauge" },
            { id: "coa", l: "Chart of Accounts", icon: "fa-tree" },
            { id: "voucher", l: "Voucher Entry", icon: "fa-file-invoice" },
            { id: "ledger", l: "General Ledger", icon: "fa-book" },
            { id: "trial-balance", l: "Trial Balance", icon: "fa-scale-balanced" },
            { id: "reports", l: "Financial Reports", icon: "fa-chart-line" }
        ], sec: "FINANCE" },
        { id: "settings", icon: "fa-gear", label: "Settings", sub: [
            { id: "prof", l: "Institute Profile", icon: "fa-building" },
            { id: "brand", l: "Branding", icon: "fa-palette" },
            { id: "rbac", l: "RBAC Config", icon: "fa-user-shield" },
            { id: "notif", l: "Notification Rules", icon: "fa-bell-concierge" },
            { id: "year", l: "Academic Year", icon: "fa-calendar-check" }
        ], sec: "SYSTEM" },
    ];

    // ── NAVIGATION LOGIC ──
    window.goNav = (id, subId = null) => {
        activeNav = id;
        activeSub = subId;

        // Update URL via pushState
        const url = new URL(window.location);
        const pageVal = subId ? `${id}-${subId}` : id;
        url.searchParams.set('page', pageVal);
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

    function renderSidebar() {
        const sections = [...new Set(NAV.map(n => n.sec))];
        let html = '';

        sections.forEach(sec => {
            html += `<div class="sb-sec"><div class="sb-lbl">${sec}</div>`;

            NAV.filter(n => n.sec === sec).forEach(nav => {
                const isActive = activeNav === nav.id && !activeSub;
                const isExp = expanded[nav.id];

                html += `<div class="sb-item">
                    <button class="nb-btn ${isActive ? 'active' : ''}" onclick="${nav.sub ? `toggleExp('${nav.id}')` : `goNav('${nav.id}')`}">
                        <i class="fa-solid ${nav.icon} nbi"></i>
                        <span class="nbl">${nav.label}</span>
                        ${nav.sub ? `<i class="fa-solid fa-chevron-right nbc ${isExp ? 'open' : ''}"></i>` : ''}
                    </button>`;

                if (nav.sub && isExp) {
                    html += `<div class="sub-menu">`;
                    nav.sub.forEach(s => {
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

        // PWA Install button removed
        /*
        html += `
            <div class="sb-install-box">
                <button class="install-btn-trigger" onclick="openPwaModal()">
                    <i class="fa-solid fa-bolt"></i>
                    <span> Install App</span>
                </button>
            </div>
        `;
        */

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
            { id: 'accounting', icon: 'fa-calculator', label: 'Accounts', action: "goNav('accounting', 'dashboard')" },
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
        mainContent.innerHTML = '<div class="pg fu">Loading...</div>';

        if (activeNav === 'overview') {
            renderDashboard();
        } else if (activeNav === 'academic') {
            if (!activeSub) {
                // Default to courses when clicking Academic
                renderCourses();
            } else if (activeSub === 'batches') {
                renderBatches();
            } else if (activeSub === 'courses') {
                renderCourses();
            } else {
                renderGenericPage();
            }
        } else if (activeNav === 'students') {
            renderStudents();
        } else if (activeNav === 'accounting') {
            if (typeof AccountingModule !== 'undefined') {
                AccountingModule.renderAction(activeSub);
            } else {
                renderGenericPage();
            }
        } else {
            renderGenericPage();
        }
    }

    // ── STUDENTS FUNCTIONS ──
    async function renderStudents() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Students</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-graduate"></i></div>
                        <div>
                            <div class="pg-title">Student Management</div>
                            <div class="pg-sub">Manage all registered students</div>
                        </div>
                    </div>
                </div>
                <div class="card" id="studentListContainer">
                    <div class="pg-loading">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Loading students...</span>
                    </div>
                </div>
            </div>
        `;
        loadStudents();
    }

    async function loadStudents() {
        const container = document.getElementById('studentListContainer');
        try {
            const res = await fetch(APP_URL + '/api/admin/students');
            const result = await res.json();
            
            if (!result.success) {
                container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">${result.message}</div>`;
                return;
            }

            const students = result.data;
            if (students.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-user-graduate" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No students registered yet.</p>
                </div>`;
                return;
            }

            container.innerHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Batch</th>
                            <th>Phone</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${students.map(s => `
                            <tr>
                                <td>${s.roll_no}</td>
                                <td>${s.name}</td>
                                <td>${s.batch_name || 'N/A'}</td>
                                <td>${u.phone || '-'}</td>
                                <td><span class="tag ${s.status === 'active' ? 'bg-t' : 'bg-b'}">${s.status}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (e) {
            console.error('Failed to load students', e);
            container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">Failed to load students</div>`;
        }
    }

    // ── COURSES FUNCTIONS ──
    async function renderCourses() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <span class="bc-cur">Courses</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-book-bookmark"></i></div>
                        <div>
                            <div class="pg-title">Course Management</div>
                            <div class="pg-sub">Manage courses and categories</div>
                        </div>
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
        loadCourses();
    }

    async function loadCourses() {
        const container = document.getElementById('courseListContainer');
        try {
            const res = await fetch(APP_URL + '/api/admin/courses');
            const result = await res.json();
            
            if (!result.success) {
                container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">${result.message}</div>`;
                return;
            }

            const courses = result.data;
            if (courses.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-book-bookmark" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No courses created yet.</p>
                </div>`;
                return;
            }

            container.innerHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Category</th>
                            <th>Fees</th>
                            <th>Duration</th>
                            <th>Batches</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${courses.map(c => `
                            <tr>
                                <td><div style="font-weight:600;">${c.name}</div></td>
                                <td><span class="tag bg-b">${c.category || 'N/A'}</span></td>
                                <td>${c.fee ? 'NPR ' + c.fee : '-'}</td>
                                <td>${c.duration || '-'}</td>
                                <td>${c.total_batches || 0}</td>
                                <td><span class="tag ${c.status === 'active' ? 'bg-t' : 'bg-b'}">${c.status}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (e) {
            console.error('Failed to load courses', e);
            container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">Failed to load courses</div>`;
        }
    }

    // ── BATCH CRUD FUNCTIONS ──
    async function renderBatches() {
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');
        const courseId = urlParams.get('course_id');
        const action = urlParams.get('action');

        if (action === 'add') {
            renderBatchAdd();
            return;
        }

        if (id) {
            renderBatchEdit(id);
            return;
        }

        // Render batch list
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <a href="#" onclick="goNav('academic', 'batches')">Batches</a>
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

        loadBatches(courseId);
    }

    async function loadBatches(courseId = null) {
        const container = document.getElementById('batchListContainer');
        try {
            let url = APP_URL + '/api/admin/batches';
            if (courseId) url += `?course_id=${courseId}`;
            
            const res = await fetch(url);
            const result = await res.json();

            if (!result.success) {
                container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">${result.message}</div>`;
                return;
            }

            const batches = result.data;
            if (batches.length === 0) {
                container.innerHTML = `<div style="padding:60px; text-align:center; color:#94a3b8;">
                    <i class="fa-solid fa-layer-group" style="font-size:3rem; margin-bottom:15px;"></i>
                    <p>No batches created yet.</p>
                    <button class="btn bt mt-2" onclick="goNav('academic', 'batches', {action: 'add'})">Create First Batch</button>
                </div>`;
                return;
            }

            container.innerHTML = `
                <table class="data-table">
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
                        ${batches.map(b => `
                            <tr>
                                <td><div style="font-weight:600;">${b.name}</div></td>
                                <td>${b.course_name}</td>
                                <td><span class="tag bg-b">${b.shift.toUpperCase()}</span></td>
                                <td>${b.total_students || 0}</td>
                                <td>${b.start_date_bs || b.start_date}</td>
                                <td><span class="tag ${b.status === 'active' ? 'bg-t' : 'bg-b'}">${b.status}</span></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <button class="btn-icon" title="Edit" onclick="goNav('academic', 'batches', {id: ${b.id}})"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon" title="Time Table"><i class="fa-solid fa-calendar-days"></i></button>
                                    <button class="btn-icon text-danger" title="Delete" onclick="deleteBatch(${b.id}, '${b.name}')"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } catch (e) {
            console.error('Failed to load batches', e);
            container.innerHTML = `<div style="padding:40px; text-align:center; color:#ef4444;">Failed to load batches</div>`;
        }
    }

    async function renderBatchAdd() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <a href="#" onclick="goNav('academic', 'batches')">Batches</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">New Batch</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-plus-circle"></i></div>
                        <div>
                            <div class="pg-title">Create New Batch</div>
                            <div class="pg-sub">Add a new batch for a course</div>
                        </div>
                    </div>
                </div>
                <div class="card fu" style="max-width:800px; margin:0 auto; padding:30px;">
                    <form id="batchAddForm">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div class="form-group">
                                <label class="form-label">Course *</label>
                                <select name="course_id" id="batchCourseSelect" class="form-control" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Batch Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Kharidar Morning 2081" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Shift</label>
                                <select name="shift" class="form-control">
                                    <option value="morning">Morning</option>
                                    <option value="day">Day</option>
                                    <option value="evening">Evening</option>
                                    <option value="weekend">Weekend</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Strength</label>
                                <input type="number" name="max_strength" class="form-control" value="40" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date (BS)</label>
                                <input type="text" name="start_date_bs" class="form-control" placeholder="2081-01-15">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date (BS)</label>
                                <input type="text" name="end_date_bs" class="form-control" placeholder="2081-12-30">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <input type="text" name="room" class="form-control" placeholder="e.g., Room 201">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
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

    async function renderBatchEdit(id) {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">›</span>
                    <a href="#" onclick="goNav('academic', 'batches')">Batches</a>
                    <span class="bc-sep">›</span> <span class="bc-cur">Edit Batch</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-pen"></i></div>
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
                            <div class="form-group">
                                <label class="form-label">Course *</label>
                                <select name="course_id" id="batchCourseSelectEdit" class="form-control" required>
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Batch Name *</label>
                                <input type="text" name="name" id="batchName" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Shift</label>
                                <select name="shift" id="batchShift" class="form-control">
                                    <option value="morning">Morning</option>
                                    <option value="day">Day</option>
                                    <option value="evening">Evening</option>
                                    <option value="weekend">Weekend</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Strength</label>
                                <input type="number" name="max_strength" id="batchMaxStrength" class="form-control" min="1">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date (BS)</label>
                                <input type="text" name="start_date_bs" id="batchStartDateBs" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date (BS)</label>
                                <input type="text" name="end_date_bs" id="batchEndDateBs" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <input type="text" name="room" id="batchRoom" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" id="batchStatus" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
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

        await populateCourses('batchCourseSelectEdit');
        await loadBatchData(id);
        document.getElementById('batchEditForm').onsubmit = (e) => submitBatchForm(e, 'PUT');
    }

    async function loadBatchData(id) {
        try {
            const res = await fetch(APP_URL + `/api/admin/batches?id=${id}`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                const b = data.data[0];
                document.getElementById('batchCourseSelectEdit').value = b.course_id;
                document.getElementById('batchName').value = b.name;
                document.getElementById('batchShift').value = b.shift;
                document.getElementById('batchMaxStrength').value = b.max_strength || 40;
                document.getElementById('batchStartDateBs').value = b.start_date_bs || '';
                document.getElementById('batchEndDateBs').value = b.end_date_bs || '';
                document.getElementById('batchRoom').value = b.room || '';
                document.getElementById('batchStatus').value = b.status;
            }
        } catch (e) { console.error('Failed to load batch data', e); }
    }

    async function submitBatchForm(e, method) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = {};
        
        formData.forEach((value, key) => {
            if (value !== '') data[key] = value;
        });

        // Handle numeric fields
        if (data.course_id) data.course_id = parseInt(data.course_id);
        if (data.max_strength) data.max_strength = parseInt(data.max_strength);

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
                Swal.fire('Error', result.message, 'error');
            }
        } catch (e) {
            console.error('Failed to submit batch form', e);
            Swal.fire('Error', 'Failed to submit form', 'error');
        }
    }

    async function deleteBatch(id, name) {
        const result = await Swal.fire({
            title: 'Delete Batch?',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ef4444'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await fetch(APP_URL + '/api/admin/batches', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();

            if (data.success) {
                Swal.fire('Deleted', data.message, 'success').then(() => {
                    loadBatches();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            console.error('Failed to delete batch', e);
            Swal.fire('Error', 'Failed to delete batch', 'error');
        }
    }

    // Helper function to populate courses dropdown
    async function populateCourses(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;

        try {
            const res = await fetch(APP_URL + '/api/admin/courses');
            const data = await res.json();

            if (data.success && data.data) {
                data.data.forEach(c => {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = c.name;
                    select.appendChild(option);
                });
            }
        } catch (e) { console.error('Failed to load courses', e); }
    }

    function renderDashboard() {
        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="bc">
                    <a href="#">Pioneer Loksewa Institute</a> <span class="bc-sep">›</span> <span class="bc-cur">Dashboard</span>
                </div>
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-gauge-high"></i></div>
                        <div><div class="pg-title">Institute Dashboard</div><div class="pg-sub">Production Blueprint V3.0 · Monday, Shrawan 22</div></div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="qa-bar mb">
                    <button class="btn bt btn-sm" onclick="goNav('students', 'add')"><i class="fa-solid fa-user-plus"></i> Add Student</button>
                    <button class="btn bs btn-sm" onclick="goNav('fee', 'record')"><i class="fa-solid fa-money-bill-wave"></i> Record Fee Payment</button>
                    <button class="btn bs btn-sm" onclick="goNav('comms', 'sms')"><i class="fa-solid fa-message"></i> Send SMS Broadcast</button>
                    <button class="btn bs btn-sm" onclick="goNav('exams', 'create-ex')"><i class="fa-solid fa-file-circle-plus"></i> Create Exam</button>
                    <button class="btn bs btn-sm"><i class="fa-solid fa-calendar-xmark"></i> Mark Holiday</button>
                </div>

                <!-- DASHBOARD WIDGETS -->
                <div class="sg">
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-t"><i class="fa-solid fa-users"></i></div><div class="bdg bg-t">+12 New</div></div>
                        <div class="sc-val">476</div>
                        <div class="sc-lbl">Total Active Students</div>
                        <div class="spark">
                            ${[40,55,48,62,58,70,76,80].map((v,i)=>(`<div class="spark-b ${i===7?"hi":""}" style="height:${v/2}%"></div>`)).join('')}
                        </div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-clipboard-user"></i></div><div class="bdg bg-b">84% Live</div></div>
                        <div class="sc-val">84.2%</div>
                        <div class="sc-lbl">Today's Attendance</div>
                        <div style="margin-top:10px; display:flex; gap:4px; flex-wrap:wrap;">
                            <span class="tag bg-t">Morning: 88%</span>
                            <span class="tag bg-y">Day: 76%</span>
                        </div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-t"><i class="fa-solid fa-vault"></i></div><div class="bdg bg-t">Trend ↑</div></div>
                        <div class="sc-val">NPR 42K</div>
                        <div class="sc-lbl">Today's Fee Collection</div>
                        <div class="sc-delta">Cash: 28K | Bank: 14K</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-r"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="bdg bg-r">Overdue</div></div>
                        <div class="sc-val" style="font-size:1.5rem">NPR 1.8L</div>
                        <div class="sc-lbl">Outstanding Dues</div>
                        <div class="aging-bar">
                            <div class="aging-f" style="width:30%; background:#fef9e7"></div>
                            <div class="aging-f" style="width:40%; background:#fde8ed"></div>
                            <div class="aging-f" style="width:30%; background:#9f1239"></div>
                        </div>
                    </div>
                </div>

                <div class="sg mb">
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-a"><i class="fa-solid fa-magnifying-glass"></i></div></div>
                        <div class="sc-val">12</div>
                        <div class="sc-lbl">New Inquiries Today</div>
                        <div class="sc-delta">5 Pending follow-ups</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-p"><i class="fa-solid fa-file-signature"></i></div></div>
                        <div class="sc-val">3</div>
                        <div class="sc-lbl">Upcoming Exams</div>
                        <div class="sc-delta">Mock #12, Subba Prelim...</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-n"><i class="fa-solid fa-person-chalkboard"></i></div></div>
                        <div class="sc-val">9/11</div>
                        <div class="sc-lbl">Teacher Attendance</div>
                        <div class="sc-delta">2 On specialized leave</div>
                    </div>
                    <div class="sc card">
                        <div class="sc-top"><div class="sc-ico ic-b"><i class="fa-solid fa-chart-line"></i></div></div>
                        <div class="sc-val">68%</div>
                        <div class="sc-lbl">Fee vs Target</div>
                        <div class="prog-t" style="margin-top:10px;"><div class="prog-f" style="width:68%; background:var(--teal)"></div></div>
                    </div>
                </div>

                <div class="g65 mb">
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-chart-bar"></i> Monthly Enrollment Trend (Last 6 Months)</div>
                        <div class="bar-ch" style="height:120px;">
                            ${["Bai","Jes","Asa","Shr","Bha","Ash"].map((m,i)=>(`
                                <div class="bar-col">
                                    <div class="bar-f" style="height:${[40,65,70,85,82,90][i]}px; background:${i===5?"var(--teal)":"#cbd5e1"}"></div>
                                    <div class="bar-l">${m}</div>
                                </div>
                            `)).join('')}
                        </div>
                    </div>
                    <div class="card">
                        <div class="ct"><i class="fa-solid fa-list-check"></i> Daily Workflow Checklist</div>
                        <div class="workflow-list">
                            ${[
                                "Review dashboard metrics",
                                "Process new student inquiries",
                                "Approve lesson plans",
                                "Trigger SMS reminders for dues",
                                "Confirm enrollment counts for exams",
                                "Export daily collection report"
                            ].map((task,i)=>(`
                                <div class="wf-item">
                                    <i class="fa-regular fa-circle-check"></i>
                                    <span>${task}</span>
                                </div>
                            `)).join('')}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="ct"><i class="fa-solid fa-history"></i> Recent Activity Log</div>
                    <div class="ai">
                        <div class="ai-dot ic-t"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="ai-content">
                            <div class="ai-txt">Ramesh Thapa admitted to Kharidar Morning</div>
                            <div class="ai-tm"><strong>Anil Shrestha</strong> · 10 min ago</div>
                        </div>
                    </div>
                    <div class="ai">
                        <div class="ai-dot ic-b"><i class="fa-solid fa-money-bill"></i></div>
                        <div class="ai-content">
                            <div class="ai-txt">Fee NPR 4,500 collected from Sita Adhikari</div>
                            <div class="ai-tm"><strong>Puja Sharma</strong> · 1 hour ago</div>
                        </div>
                    </div>
                    <div class="ai">
                        <div class="ai-dot ic-r"><i class="fa-solid fa-comment-sms"></i></div>
                        <div class="ai-content">
                            <div class="ai-txt">SMS Broadcast: Mock Test Reschedule (145 recipients)</div>
                            <div class="ai-tm"><strong>System</strong> · 3 hours ago</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderGenericPage() {
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

    // Init
    renderSidebar();
    renderPage();
});
