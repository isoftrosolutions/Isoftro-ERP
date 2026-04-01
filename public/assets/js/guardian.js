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

    // ── SIDEBAR TOGGLE (Matches Super Admin pattern) ──
    const toggleSidebar = () => {
        if (window.innerWidth >= 1024) {
            document.body.classList.toggle('sb-collapsed');
        } else {
            document.body.classList.toggle('sb-active');
        }
    };
    const closeSidebar = () => document.body.classList.remove('sb-active');

    if (sbToggle) sbToggle.addEventListener('click', toggleSidebar);
    if (sbOverlay) sbOverlay.addEventListener('click', closeSidebar);

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

        { id: "homework", icon: "fa-book-open", label: "Homework", sub: [
            { id: "list",  l: "Assignments", nav: "homework", sub: "list"  }
        ], sec: "ACADEMICS" },

        { id: "notices", icon: "fa-bullhorn", label: "Notices", sub: [
            { id: "all",  l: "All Announcements", nav: "notices", sub: "all"  }
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
        } else if (activeNav.startsWith('attendance-')) {
            renderAttendanceModule();
        } else if (activeNav.startsWith('exams-')) {
            renderExamsModule();
        } else if (activeNav.startsWith('fee-')) {
            renderFeeModule();
        } else if (activeNav.startsWith('messages-')) {
            renderContactModule();
        } else if (activeNav.startsWith('homework-')) {
            renderHomeworkModule();
        } else if (activeNav.startsWith('notices-')) {
            renderNoticesModule();
        } else {
            renderGenericPage();
        }
    }

    async function renderDashboard() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';

        try {
            const res = await fetch(`${APP_URL}/api/guardian/dashboard`);
            const json = await res.json();
            
            if (!json.success) {
                mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${json.message}</div></div>`;
                return;
            }

            const data = json.data;
            const gInfo = data.guardian_info || {};
            const sInfo = data.student_info || {};
            const stats = data.stats || {};
            
            const gName = gInfo.full_name || 'Guardian';
            const sName = sInfo.full_name || 'Student';
            const sBatch = sInfo.batch_name || 'Batch';
            const sRoll = sInfo.roll_no || 'Roll TBA';

            let examsHtml = '';
            if (data.recent_exams && data.recent_exams.length > 0) {
                data.recent_exams.forEach(ex => {
                    const score = ex.score || 0;
                    const total = ex.total_marks || 100;
                    examsHtml += `
                        <div class="ex-row">
                            <div class="ex-ico" style="background:#EEF2FF; color:#6366F1;"><i class="fa-solid fa-file-pen"></i></div>
                            <div class="ex-info">
                                <div class="ex-subj">${ex.exam_title || 'Exam'}</div>
                                <div class="ex-meta">${ex.exam_type || 'Test'} · ${ex.exam_date || ''}</div>
                            </div>
                            <div class="ex-score">
                                <div class="ex-val">${score}/${total}</div>
                            </div>
                        </div>
                    `;
                });
            } else {
                examsHtml = '<div style="padding:15px; color:var(--text-light); text-align:center;">No recent exams.</div>';
            }

            // Update sidebar info
            const sbChildInfo = document.getElementById('sbChildInfo');
            if (sbChildInfo) {
                const init = sName.substring(0, 2).toUpperCase();
                sbChildInfo.innerHTML = `
                    <div class="child-av">${init}</div>
                    <div class="child-meta">
                        <span class="child-name">${sName}</span>
                        <span class="child-roll">Roll: ${sRoll}</span>
                    </div>
                `;
            }

            let feeHtml = '';
            if (data.fee_status && data.fee_status.length > 0) {
                data.fee_status.forEach(fee => {
                    const isPaid = fee.status === 'paid';
                    feeHtml += `
                        <div class="fee-item">
                            <div class="fee-info">
                                <div class="fee-name">${fee.fee_name || 'Fee'}</div>
                                <div class="fee-due">${isPaid ? 'Paid on' : 'Due'}: ${fee.due_date || 'N/A'}</div>
                            </div>
                            <div class="fee-amt ${isPaid ? 'pg' : 'pr'}">
                                ${isPaid ? 'PAID' : 'Rs. ' + (fee.amount || 0)}
                            </div>
                        </div>
                    `;
                });
            } else {
                feeHtml = '<div style="padding:15px; color:var(--text-light); text-align:center;">No fee records found.</div>';
            }

            let noticeHtml = '';
            if (data.recent_notices && data.recent_notices.length > 0) {
                data.recent_notices.forEach(not => {
                    noticeHtml += `
                        <div class="ex-row" style="margin-bottom:10px;">
                            <div class="ex-info">
                                <div class="ex-subj" style="font-size:13px; font-weight:600;">${not.title || 'Notice'}</div>
                                <div class="ex-meta" style="font-size:11px; margin-top:2px;">${not.created_at || 'Recently'}</div>
                            </div>
                        </div>
                    `;
                });
            } else {
                noticeHtml = '<div style="padding:15px; color:var(--text-light); text-align:center;">No new notices.</div>';
            }

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Namaste, ${gName} 👋</div>
                        <div class="pg-sub">Monitoring ${sName} · ${sBatch} · Roll: ${sRoll}</div>
                    </div>

                    <!-- QUICK ACTIONS -->
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
                                <div class="att-pct">${stats.attendance_rate || 0}%</div>
                                <div style="font-size:11px; color:var(--text-body); line-height:1.2;">Month's Presence: ${stats.attendance_present} / ${stats.attendance_total} days</div>
                            </div>
                        </div>
                        <div class="sc blue">
                            <div class="sc-ico blue"><i class="fa-solid fa-trophy"></i></div>
                            <div class="sc-lbl">Latest Exam Score</div>
                            <div class="sc-val">${stats.latest_exam_score !== null ? stats.latest_exam_score + '%' : 'N/A'}</div>
                            <div class="sc-sub">Recent Test Performance</div>
                        </div>
                        <div class="sc amber">
                            <div class="sc-ico amber"><i class="fa-solid fa-wallet"></i></div>
                            <div class="sc-lbl">Fee Dues</div>
                            <div class="sc-val">Rs. ${stats.fee_dues.toLocaleString()}</div>
                            <div class="sc-sub">Outstanding Amount</div>
                        </div>
                        <div class="sc purple">
                            <div class="sc-ico purple"><i class="fa-solid fa-bullhorn"></i></div>
                            <div class="sc-lbl">Recent Notices</div>
                            <div class="sc-val">${stats.notices_count || 0} New</div>
                            <div class="sc-sub">Relevant to Child's Batch</div>
                        </div>
                    </div>

                    <div class="g64">
                        <div>
                            <!-- EXAM RESULTS -->
                            <div class="card">
                                <div class="card-h">
                                    <div class="card-t"><i class="fa-solid fa-square-poll-vertical" style="color:var(--green)"></i> Recent Exam Results</div>
                                    <a href="javascript:void(0)" onclick="goNav('exams','hist')" class="btn-l" style="font-size:11px; color:var(--green); font-weight:700; text-decoration:none;">View All</a>
                                </div>
                                <div class="card-b">
                                    ${examsHtml}
                                </div>
                            </div>

                            <!-- RECENT MESSAGES -->
                            <div class="contact-zone" style="margin-top:20px;">
                                <div class="contact-t"><i class="fa-solid fa-headset"></i> Contact Institute Admin</div>
                                <div class="contact-d">Direct line to the academic counselor. Type your message below to start a conversation.</div>
                                <textarea class="contact-box" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:8px;" rows="3" placeholder="Type your inquiry here regarding ${sName}'s progress..."></textarea>
                                <button class="btn btn-primary mt-10" onclick="alert('Message sent to admin!')">Send Message</button>
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
                                    ${feeHtml}
                                    <button class="btn bs" style="width:100%; margin-top:16px; font-size:12px; background:var(--green); color:#fff; border:none; padding:10px; border-radius:6px; font-weight:800; cursor:pointer;" onclick="goNav('fee','dues')">Pay Outstanding Online</button>
                                </div>
                            </div>

                            <!-- NOTICE BOARD -->
                            <div class="card" style="margin-top:20px;">
                                <div class="card-h">
                                    <div class="card-t"><i class="fa-solid fa-bullhorn" style="color:var(--purple)"></i> Recent Notices</div>
                                </div>
                                <div class="card-b" style="padding:0 18px 18px;">
                                    ${noticeHtml}
                                </div>
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

    async function renderAttendanceModule() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const res = await fetch(`${APP_URL}/api/guardian/attendance`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            
            const data = json.data;
            const subPage = activeNav.split('-')[1] || 'sum';

            let content = '';
            if (subPage === 'sum') {
                content = renderAttendanceSummary(data.summary);
            } else if (subPage === 'hist') {
                content = renderAttendanceHistory(data.history);
            } else {
                content = `<div class="card" style="padding:40px; text-align:center;"><h3>Leave Applications</h3><p>Online leave application system is being integrated. Please contact the front desk for urgent leave requests.</p></div>`;
            }

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Attendance Monitoring</div>
                        <div class="pg-sub">Detailed statistics and history for your child.</div>
                    </div>
                    ${content}
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${err.message}</div></div>`;
        }
    }

    function renderAttendanceSummary(summary) {
        const pct = summary.total > 0 ? Math.round((summary.present / summary.total) * 100) : 0;
        return `
            <div class="sg" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="sc green">
                    <div class="sc-lbl">Present Days</div>
                    <div class="sc-val">${summary.present}</div>
                </div>
                <div class="sc amber">
                    <div class="sc-lbl">Late Days</div>
                    <div class="sc-val">${summary.late}</div>
                </div>
                <div class="sc red">
                    <div class="sc-lbl">Absent Days</div>
                    <div class="sc-val">${summary.absent}</div>
                </div>
                <div class="sc blue">
                    <div class="sc-lbl">Attendance Rate</div>
                    <div class="sc-val">${pct}%</div>
                </div>
            </div>
            <div class="card" style="margin-top:20px;">
                <div class="card-h"><div class="card-t">Presence Overview</div></div>
                <div class="card-b">
                    <div style="height:200px; display:flex; align-items:center; justify-content:center; background:#f9fafb; border-radius:12px;">
                        <div style="text-align:center;">
                            <div style="font-size:48px; font-weight:800; color:var(--green);">${pct}%</div>
                            <div style="color:var(--text-light);">Overall Academic Year Presence</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderAttendanceHistory(history) {
        let rows = history.map(h => `
            <tr>
                <td style="padding:12px; border-bottom:1px solid #eee;">${h.attendance_date}</td>
                <td style="padding:12px; border-bottom:1px solid #eee;">
                    <span class="badge ${h.status === 'present' ? 'bg-success' : (h.status === 'absent' ? 'bg-danger' : 'bg-warning')}">
                        ${h.status.toUpperCase()}
                    </span>
                </td>
                <td style="padding:12px; border-bottom:1px solid #eee; color:var(--text-light); font-size:12px;">${h.remarks || '-'}</td>
            </tr>
        `).join('');

        return `
            <div class="card">
                <div class="card-h"><div class="card-t">Daily Attendance Log</div></div>
                <div class="card-b" style="padding:0;">
                    <table style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead>
                            <tr style="background:#f8f9fa;">
                                <th style="padding:12px; font-size:12px; color:var(--text-light);">DATE</th>
                                <th style="padding:12px; font-size:12px; color:var(--text-light);">STATUS</th>
                                <th style="padding:12px; font-size:12px; color:var(--text-light);">REMARKS</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="3" style="padding:20px; text-align:center;">No records found.</td></tr>'}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    async function renderExamsModule() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const res = await fetch(`${APP_URL}/api/guardian/exams`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            
            const data = json.data;
            const subPage = activeNav.split('-')[1] || 'hist';

            let content = '';
            if (subPage === 'hist') {
                content = renderExamResults(data.results);
            } else if (subPage === 'analysis') {
                content = renderSubjectAnalysis(data.subject_analysis);
            } else {
                content = `<div class="card" style="padding:40px; text-align:center;"><h3>Performance Trends</h3><p>Visual performance charts are being synchronized with latest exam data.</p></div>`;
            }

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Academic Performance</div>
                        <div class="pg-sub">Results and subject-wise analysis.</div>
                    </div>
                    ${content}
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${err.message}</div></div>`;
        }
    }

    function renderExamResults(results) {
        let rows = results.map(r => `
            <div class="ex-row" style="padding:15px; border-bottom:1px solid #eee;">
                <div class="ex-info">
                    <div class="ex-subj" style="font-weight:700;">${r.exam_title}</div>
                    <div class="ex-meta">${r.subject_name || 'General'} · ${r.exam_date}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:16px; font-weight:800; color:var(--primary);">${r.score} / ${r.total_marks}</div>
                    <div style="font-size:11px; color:var(--text-light);">${Math.round((r.score/r.total_marks)*100)}%</div>
                </div>
            </div>
        `).join('');

        return `
            <div class="card">
                <div class="card-h"><div class="card-t">Recent Exam Scores</div></div>
                <div class="card-b" style="padding:0;">
                    ${rows || '<div style="padding:20px; text-align:center;">No exam records found.</div>'}
                </div>
            </div>
        `;
    }

    function renderSubjectAnalysis(analysis) {
        let cards = analysis.map(a => `
            <div class="sc" style="background:#fff; border:1px solid #eee;">
                <div class="sc-lbl" style="font-size:14px; color:var(--text-dark);">${a.subject}</div>
                <div class="sc-val" style="font-size:24px; color:var(--primary);">${Math.round(a.avg_percentage)}%</div>
                <div style="width:100%; height:6px; background:#eee; border-radius:3px; margin-top:10px; overflow:hidden;">
                    <div style="width:${a.avg_percentage}%; height:100%; background:var(--primary);"></div>
                </div>
            </div>
        `).join('');

        return `
            <div class="sg" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                ${cards || '<div class="card" style="grid-column: 1/-1; padding:20px; text-align:center;">No analysis data available.</div>'}
            </div>
        `;
    }

    function renderGenericPage() {
        // ... (This will be replaced by the specific modules below)
    }

    async function renderFeeModule() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const res = await fetch(`${APP_URL}/api/guardian/fees`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            
            const data = json.data;
            const subPage = activeNav.split('-')[1] || 'dues';

            let content = '';
            if (subPage === 'dues') {
                content = renderFeeDues(data.outstanding);
            } else if (subPage === 'pay') {
                content = renderFeeHistory(data.history);
            } else {
                content = `<div class="card" style="padding:40px; text-align:center;"><h3>Receipt Downloads</h3><p>Official receipts are being generated. You can download existing receipts from the history section below each paid record.</p></div>`;
            }

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Fee Management</div>
                        <div class="pg-sub">Track payments and outstanding dues.</div>
                    </div>
                    ${content}
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${err.message}</div></div>`;
        }
    }

    function renderFeeDues(dues) {
        let total = dues.reduce((sum, d) => sum + parseFloat(d.amount), 0);
        let rows = dues.map(d => `
            <div class="fee-item" style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:700;">${d.title || 'Fee Item'}</div>
                    <div style="font-size:12px; color:var(--red);">Due: ${d.due_date}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:800; font-size:16px;">Rs. ${parseFloat(d.amount).toLocaleString()}</div>
                    <button class="btn btn-sm" style="margin-top:5px; background:var(--green); color:#fff; border:none; padding:4px 10px; border-radius:4px; font-size:11px;" onclick="alert('Proceeding to payment gateway...')">Pay Now</button>
                </div>
            </div>
        `).join('');

        return `
            <div class="sc amber" style="margin-bottom:20px;">
                <div class="sc-lbl">Total Outstanding</div>
                <div class="sc-val">Rs. ${total.toLocaleString()}</div>
            </div>
            <div class="card">
                <div class="card-h"><div class="card-t">Outstanding Invoices</div></div>
                <div class="card-b" style="padding:0;">
                    ${rows || '<div style="padding:20px; text-align:center;">All clear! No outstanding dues.</div>'}
                </div>
            </div>
        `;
    }

    function renderFeeHistory(history) {
        let rows = history.map(h => `
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:12px;">${h.title}</td>
                <td style="padding:12px;">Rs. ${parseFloat(h.amount).toLocaleString()}</td>
                <td style="padding:12px;">${h.updated_at.split(' ')[0]}</td>
                <td style="padding:12px;"><span class="badge bg-success">PAID</span></td>
            </tr>
        `).join('');

        return `
            <div class="card">
                <div class="card-h"><div class="card-t">Payment History</div></div>
                <div class="card-b" style="padding:0;">
                    <table style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead style="background:#f8f9fa;">
                            <tr>
                                <th style="padding:12px; font-size:12px;">ITEM</th>
                                <th style="padding:12px; font-size:12px;">AMOUNT</th>
                                <th style="padding:12px; font-size:12px;">DATE</th>
                                <th style="padding:12px; font-size:12px;">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="4" style="padding:20px; text-align:center;">No payment history found.</td></tr>'}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    async function renderContactModule() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const res = await fetch(`${APP_URL}/api/guardian/contact`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            
            const tickets = json.data;
            const subPage = activeNav.split('-')[1] || 'contact';

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Message Institute</div>
                        <div class="pg-sub">Direct communication channel with school administration.</div>
                    </div>
                    ${renderContactForm()}
                    <div style="margin-top:30px;">
                        <div class="card-t" style="margin-bottom:15px; font-size:16px; font-weight:700;"><i class="fa-solid fa-clock-rotate-left"></i> Previous Inquiries</div>
                        ${renderMessageHistory(tickets)}
                    </div>
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${err.message}</div></div>`;
        }
    }

    function renderContactForm() {
        return `
            <div class="card" style="background:#fff; border:1px solid #eee;">
                <div class="card-b">
                    <div style="margin-bottom:15px;">
                        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px; color:var(--text-light);">SUBJECT</label>
                        <input id="msgSubject" type="text" placeholder="e.g., Leave inquiry, Result doubt..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label style="display:block; font-size:12px; font-weight:700; margin-bottom:5px; color:var(--text-light);">MESSAGE</label>
                        <textarea id="msgBody" rows="4" placeholder="Type your detailed message here..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;"></textarea>
                    </div>
                    <button class="btn btn-primary" onclick="window.submitGuardianMessage()">
                        <i class="fa-solid fa-paper-plane"></i> Send Message
                    </button>
                </div>
            </div>
        `;
    }

    function renderMessageHistory(tickets) {
        if (!tickets || tickets.length === 0) return '<div style="color:var(--text-light); text-align:center;">No previous messages.</div>';
        
        return tickets.map(t => `
            <div class="card" style="margin-bottom:10px; border:1px solid #eee; background:#fff;">
                <div class="card-h" style="padding:10px 15px; background:#fcfcfc;">
                    <div style="display:flex; justify-content:space-between; width:100%;">
                        <div style="font-weight:700; font-size:14px;">${t.subject}</div>
                        <div class="badge ${t.status === 'pending' ? 'bg-warning' : 'bg-success'}" style="font-size:10px;">${t.status.toUpperCase()}</div>
                    </div>
                </div>
                <div class="card-b" style="padding:10px 15px; font-size:13px; color:var(--text-body);">
                    ${t.description}
                    <div style="margin-top:10px; font-size:10px; color:var(--text-light);">${t.created_at}</div>
                </div>
            </div>
        `).join('');
    }

    window.submitGuardianMessage = async () => {
        const sub = document.getElementById('msgSubject').value;
        const msg = document.getElementById('msgBody').value;

        if (!msg) return alert('Please enter a message.');

        try {
            const res = await fetch(`${APP_URL}/api/guardian/contact`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ subject: sub, message: msg })
            });
            const json = await res.json();
            if (json.success) {
                alert(json.message);
                renderContactModule(); // Refresh
            } else {
                alert('Error: ' + json.message);
            }
        } catch (err) {
            alert('Failed to send message.');
        }
    };

    async function renderHomeworkModule() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const res = await fetch(`${APP_URL}/api/guardian/homework`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            
            const homeworks = json.data;

            let rows = homeworks.map(h => `
                <div class="card" style="margin-bottom:15px; border-left:4px solid ${h.submission_status === 'submitted' || h.submission_status === 'graded' ? 'var(--green)' : (new Date(h.due_date) < new Date() ? 'var(--red)' : 'var(--amber)')}">
                    <div class="card-b">
                        <div style="display:flex; justify-content:space-between; align-items:start;">
                            <div>
                                <div style="font-weight:700; font-size:16px;">${h.title}</div>
                                <div style="font-size:12px; color:var(--text-light); margin-top:2px;">${h.subject_name || 'General'} · Due: ${h.due_date}</div>
                            </div>
                            <div class="badge ${h.submission_status === 'submitted' || h.submission_status === 'graded' ? 'bg-success' : 'bg-warning'}">
                                ${h.submission_status ? h.submission_status.toUpperCase() : 'PENDING'}
                            </div>
                        </div>
                        <div style="margin-top:10px; font-size:13px; color:var(--text-body);">${h.description || 'No description provided.'}</div>
                        ${h.marks_obtained !== null ? `<div style="margin-top:10px; font-weight:700; color:var(--green);">Score: ${h.marks_obtained} / ${h.total_marks}</div>` : ''}
                    </div>
                </div>
            `).join('');

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Homework Assignments</div>
                        <div class="pg-sub">Monitor active tasks and evaluation status.</div>
                    </div>
                    ${rows || '<div class="card" style="padding:40px; text-align:center;">No homework assigned.</div>'}
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${err.message}</div></div>`;
        }
    }

    async function renderNoticesModule() {
        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
        
        try {
            const res = await fetch(`${APP_URL}/api/guardian/notices`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);
            
            const notices = json.data;

            let blocks = notices.map(n => `
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-h" style="padding:15px; background:#fcfcfc;">
                        <div style="font-weight:800; color:var(--primary); font-size:16px;">${n.title}</div>
                        <div style="font-size:11px; color:var(--text-light);">${new Date(n.created_at).toLocaleDateString()}</div>
                    </div>
                    <div class="card-b" style="padding:15px; font-size:14px; line-height:1.6; color:var(--text-dark);">
                        ${n.content}
                        ${n.attachment_path ? `<div style="margin-top:15px;"><a href="${APP_URL}/${n.attachment_path}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-paperclip"></i> View Attachment</a></div>` : ''}
                    </div>
                </div>
            `).join('');

            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-header">
                        <div class="pg-title">Notice Board</div>
                        <div class="pg-sub">Important announcements and updates.</div>
                    </div>
                    <div style="max-width:800px;">
                        ${blocks || '<div class="card" style="padding:40px; text-align:center;">No active notices.</div>'}
                    </div>
                </div>
            `;
        } catch (err) {
            mainContent.innerHTML = `<div class="pg fu"><div class="alert alert-danger">${err.message}</div></div>`;
        }
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
