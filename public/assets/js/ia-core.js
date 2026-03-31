/**
 * Hamro ERP — Institute Admin · ia-core.js
 * Core: shared state, sidebar, page routing
 * Load this file LAST, after all ia-*.js domain modules.
 *
 * Sidebar config is injected by PHP as window._IA_NAV_CONFIG
 * Badge counters injected as window._IA_BADGES
 */

/* ── STATE ──────────────────────────────────────────────────────── */
window._IA = {
    activeNav: 'overview',
    activeSub: null,
    expanded: JSON.parse(localStorage.getItem('_ia_expanded') || '{}'),
};

/**
 * Global helper to get the currency symbol. 
 * Prioritizes window._INSTITUTE_CONFIG, then window.INSTITUTE_CONFIG, fallback to ₹.
 */
window.getCurrencySymbol = function() {
    return window._INSTITUTE_CONFIG?.currency_symbol || window.INSTITUTE_CONFIG?.currency_symbol || '₹';
};

/* Build flat nav from PHP-injected config (for routing compatibility) */
function _iaBuildFlatNav() {
    const cfg = window._IA_NAV_CONFIG || [];
    const flat = [];
    cfg.forEach(section => {
        (section.items || []).forEach(item => {
            flat.push({
                id: item.id,
                icon: item.icon,
                label: item.label,
                sub: item.sub || null,
                sec: section.section,
                badge_key: item.badge_key || null,
            });
        });
    });
    return flat;
}

const _IA_NAV = _iaBuildFlatNav();

/* ── Save expanded state to localStorage ── */
function _iaSaveExpanded() {
    localStorage.setItem('_ia_expanded', JSON.stringify(_IA.expanded));
}

/* ── NAVIGATION ─────────────────────────────────────────────────── */
window.goNav = function(id, subId = null, params = null) {
    _IA.activeNav = id; _IA.activeSub = subId;
    const url = new URL(window.location);
    const pageVal = subId ? `${id}-${subId}` : id;
    url.search = ''; url.searchParams.set('page', pageVal);
    if (params) Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
    window.history.pushState({ pageVal }, '', url);
    if (window.innerWidth < 1024) document.body.classList.remove('sb-active');
    _iaRenderSidebar(); _iaRenderPage();
};

window.toggleExp = function(id) {
    _IA.expanded[id] = !_IA.expanded[id];
    _iaSaveExpanded();
    
    // Smooth toggle if element exists
    const el = document.getElementById(`sub-${id}`);
    const chev = document.querySelector(`[onclick="toggleExp('${id}')"] .nbc`);
    if (el) {
        if (_IA.expanded[id]) {
            el.style.display = 'block';
            setTimeout(() => el.classList.add('open'), 10);
            if (chev) chev.classList.add('open');
        } else {
            el.classList.remove('open');
            setTimeout(() => el.style.display = 'none', 200);
            if (chev) chev.classList.remove('open');
        }
    } else {
        _iaRenderSidebar();
    }
};

/* ── SIDEBAR (Mirrors Super Admin structure) ────────────────── */
/* Escape a string for safe use inside an HTML attribute value */
function _iaEsc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function _iaRenderSidebar(filter = '') {
    const sbBody = document.getElementById('sbBody'); if (!sbBody) return;
    const badges = window._IA_BADGES || {};
    const sections = [...new Set(_IA_NAV.map(n => n.sec))];
    let html = '';

    sections.forEach(sec => {
        const items = _IA_NAV.filter(n => {
            if (n.sec !== sec) return false; if (!filter) return true;
            return n.label.toLowerCase().includes(filter) || (n.sub && n.sub.some(s => s.l.toLowerCase().includes(filter)));
        });
        if (!items.length) return;

        html += `<div class="sb-sec-lbl">${_iaEsc(sec)}</div>`;

        items.forEach(nav => {
            const hasSub = !!(nav.sub && nav.sub.length);
            const isActive = _IA.activeNav === nav.id;
            const isExp = filter ? true : _IA.expanded[nav.id];
            const navId = _iaEsc(nav.id);

            // Badge
            const badgeVal = nav.badge_key && badges[nav.badge_key] ? badges[nav.badge_key] : null;
            const badgeHtml = badgeVal ? `<span class="sb-badge" aria-label="${badgeVal} notifications" style="margin-left:auto; background:var(--red); color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:10px;">${badgeVal}</span>` : '';

            if (hasSub) {
                html += `
                    <button class="nb-btn ${isActive ? 'active' : ''}"
                            onclick="toggleExp('${navId}')"
                            aria-expanded="${isExp ? 'true' : 'false'}"
                            aria-controls="sub-${navId}"
                            ${isActive ? 'aria-current="page"' : ''}>
                        <i class="fa-solid ${_iaEsc(nav.icon)} nbi" aria-hidden="true"></i>
                        <span class="nbl">${_iaEsc(nav.label)}</span>
                        ${badgeHtml}
                        <i class="fa fa-chevron-right nbc ${isExp ? 'open' : ''}" aria-hidden="true" style="font-size:10px; margin-left:8px;"></i>
                    </button>
                    <div class="sub-menu ${isExp ? 'open' : ''}" id="sub-${navId}" role="group" aria-label="${_iaEsc(nav.label)} submenu" style="${isExp ? '' : 'display:none;'}">
                `;

                nav.sub.forEach(s => {
                    if (filter && !s.l.toLowerCase().includes(filter) && !nav.label.toLowerCase().includes(filter)) return;
                    const isSubActive = _IA.activeNav === nav.id && _IA.activeSub === s.id;
                    const subBadge = s.badge_key && badges[s.badge_key] ? `<span class="sb-badge sm" aria-label="${badges[s.badge_key]} notifications" style="margin-left:auto; opacity:0.7;">${badges[s.badge_key]}</span>` : '';
                    const sId = _iaEsc(s.id);
                    const action = s.onclick ? s.onclick : `goNav('${navId}', '${sId}')`;
                    html += `
                        <button class="sub-btn ${isSubActive ? 'active' : ''}"
                                onclick="${action}"
                                ${isSubActive ? 'aria-current="page"' : ''}>
                            <i class="fa-solid ${_iaEsc(s.icon)} smi" aria-hidden="true" style="font-size:11px; margin-right:8px; opacity:0.6;"></i>
                            ${_iaEsc(s.l)}
                            ${subBadge}
                        </button>
                    `;

                    if (s.child && isExp) {
                        s.child.forEach(c => {
                            const isChildActive = _IA.activeNav === nav.id && _IA.activeSub === c.id;
                            const cId = _iaEsc(c.id);
                            const childAction = c.onclick ? c.onclick : `goNav('${navId}', '${cId}')`;
                            html += `
                                <button class="sub-btn child ${isChildActive ? 'active' : ''}"
                                        onclick="${childAction}"
                                        ${isChildActive ? 'aria-current="page"' : ''}
                                        style="padding-left:60px; font-size:12px; opacity:0.8;">
                                    <i class="fa-solid ${_iaEsc(c.icon)} smi" aria-hidden="true" style="font-size:10px; margin-right:6px; opacity:0.5;"></i>
                                    ${_iaEsc(c.l)}
                                </button>
                            `;
                        });
                    }
                });

                html += `</div>`;
            } else {
                html += `
                    <button class="nb-btn ${isActive ? 'active' : ''}"
                            onclick="goNav('${navId}')"
                            ${isActive ? 'aria-current="page"' : ''}
                            title="${_iaEsc(nav.label)}">
                        <i class="fa-solid ${_iaEsc(nav.icon)} nbi" aria-hidden="true"></i>
                        <span class="nbl">${_iaEsc(nav.label)}</span>
                        ${badgeHtml}
                    </button>
                `;
            }
        });
    });

    // PWA Install Box
    html += `<div style="padding:15px 18px;"><button class="install-btn-trigger" onclick="openPwaModal()" style="width:100%; padding:10px; background:var(--teal-lt); color:var(--teal); border:none; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer;"><i class="fa-solid fa-bolt" aria-hidden="true"></i> App Install</button></div>`;

    sbBody.innerHTML = html;
    _iaRenderBottomNav();
}

function _iaRenderBottomNav() {
    let bNav = document.getElementById('bottomNav');
    if (!bNav) { bNav = document.createElement('nav'); bNav.id='bottomNav'; bNav.className='mobile-bottom-nav'; bNav.setAttribute('aria-label','Quick navigation'); document.body.appendChild(bNav); }

    // Pull up to 5 top-level items from the permission-filtered config (same source as sidebar)
    const pinned = ['overview','students','fee','accounting','exams','comms'];
    const items = _IA_NAV
        .filter(n => pinned.includes(n.id))
        .sort((a, b) => pinned.indexOf(a.id) - pinned.indexOf(b.id))
        .slice(0, 5);

    // Fall back: if fewer than 3 pinned items exist, fill with first available items
    if (items.length < 3) {
        const extras = _IA_NAV.filter(n => !pinned.includes(n.id)).slice(0, 5 - items.length);
        items.push(...extras);
    }

    bNav.innerHTML = items.map(i => {
        const id = _iaEsc(i.id);
        const icon = _iaEsc(i.icon);
        const label = _iaEsc(i.label);
        const hasSub = i.sub && i.sub.length;
        const action = hasSub ? `goNav('${id}', '${_iaEsc(i.sub[0].id)}')` : `goNav('${id}')`;
        const isActive = _IA.activeNav === i.id;
        return `<button class="mb-nav-btn ${isActive ? 'active' : ''}" onclick="${action}" aria-label="${label}" ${isActive ? 'aria-current="page"' : ''}><i class="fa-solid ${icon}" aria-hidden="true"></i><span>${label}</span></button>`;
    }).join('');
}

/* ── PAGE ROUTER ────────────────────────────────────────────────── */
function _iaRenderPage() {
    const mc = document.getElementById('mainContent'); if (!mc) return;
    mc.innerHTML = '<div class="pg fu"><div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i><span>Loading...</span></div></div>';
    const urlParams = new URLSearchParams(window.location.search);
    const nav = _IA.activeNav, sub = _IA.activeSub;
    if (nav==='overview') { _iaRenderDashboard(); return; }
    if (nav==='students') { 
        if(sub==='add') window.renderAddStudentFormV2?.(); 
        else if(sub==='enroll-existing') window.renderEnrollExistingFormV2?.(); 
        else if(sub==='edit' || sub==='complete') window.renderEditStudentForm?.(urlParams.get('id')); 
        else if(sub==='view') window.renderStudentProfile?.(urlParams.get('id')); 
        else if(sub==='vault') window.renderDocumentVault?.(); 
        else if(sub==='alumni') window.renderAlumniList?.(); 
        else window.renderStudentList?.(); 
        return; 
    }
    if (nav==='academic') {
        if (sub==='courses') { if(urlParams.get('id')) window.renderEditCourseForm?.(urlParams.get('id')); else if(urlParams.get('action')==='add') window.renderAddCourseForm?.(); else window.renderCourseList?.(); return; }
        if (sub==='course-categories') { window.renderCourseCategoryList?.(); return; }
        if (sub==='batches') { if(urlParams.get('id')) window.renderEditBatchForm?.(urlParams.get('id')); else if(urlParams.get('action')==='add') window.renderAddBatchForm?.(); else window.renderBatchList?.(); return; }
        if (sub==='subjects') { if(urlParams.get('id')) window.renderEditSubjectForm?.(urlParams.get('id')); else if(urlParams.get('action')==='add') window.renderAddSubjectForm?.(); else window.renderSubjectList?.(); return; }
        if (sub==='allocation') { window.renderSubjectAllocation?.(); return; }
        if (sub==='timetable') { window.renderTimetablePage?.(); return; }
        if (sub==='rooms') { window.renderRoomsPage?.(); return; }
        if (sub==='calendar') { window.renderAcademicCalendar?.(); return; }
    }
    if (nav==='inq') {
        if(sub==='list') window.renderInquiryList?.(); else if(sub==='add-inq') window.renderAddInquiryForm?.();
        else if(sub==='inq-analytics') window.renderInquiryAnalytics?.(); else if(sub==='adm-form') window.renderAdmissionForm?.();
        return;
    }
    if (nav==='exams') {
        if (sub==='create-ex')    { window.renderCreateExamForm?.(); return; }
        if (sub==='schedule' || sub==='results' || !sub) { window.renderExamList?.(); return; }
        if (sub==='qbank')        { if(window.renderQuestionBank) window.renderQuestionBank(); else mc.innerHTML=`<div class="pg fu"><div class="pg-loading">Loading QBank...</div></div>`; return; }
        window.renderExamList?.(); return;
    }
    if (nav==='homework') {
        if (sub==='list' || !sub) { window.renderHomeworkList?.(); return; }
        if (sub==='create') { window.renderCreateHomeworkForm?.(); return; }
    }

    if (nav==='fee' && sub==='setup') { window.renderFeeSetup?.(); return; }
    if (nav==='fee' && sub==='record') { window.renderFeeRecord?.(); return; }
    if (nav==='fee' && (sub==='quick' || sub==='fee-coll')) { window.renderQuickPayment?.(urlParams.get('id') || urlParams.get('student_id')); return; }
    if (nav==='fee' && sub==='details') { window.renderFeeDetails?.(urlParams.get('receipt_no')); return; }
    if (nav==='fee' && sub==='outstanding') { window.renderFeeOutstanding?.(); return; }
    if (nav==='fee' && sub==='fin-reports') { window.renderFeeReports?.(); return; }
    if (nav==='fee' && sub==='ledger') { window.renderStudentLedger?.(urlParams.get('id')); return; }
    if (nav==='attendance') {
        if (sub==='take') { window.renderAttendanceTake?.(); return; }
        if (sub==='leave') { window.renderLeaveRequests?.(); return; }
        if (sub==='report') { window.renderAttendanceReport?.(); return; }
        window.renderAttendanceTake?.(); return;
    }
    if (nav==='teachers')  { 
        if(sub==='add') window.renderAddStaffForm?.('teacher'); 
        else if(sub==='allocation') window.renderSubjectAllocation?.();
        else window.renderStaffList?.('teacher'); 
        return; 
    }
    if (nav==='frontdesk') { sub==='add'?window.renderAddStaffForm?.('frontdesk'):window.renderStaffList?.('frontdesk'); return; }
    if (nav==='settings') {
        if (sub==='prof') { window.renderInstituteProfile?.(); return; }
        if (sub==='user-prof') { window.renderUserProfile?.(); return; }
        if (sub==='billing') { window.renderBillingSettings?.(); return; }
        if (sub==='em-tpls') { window.renderEmailTemplates?.(); return; }
        if (sub==='email') { window.renderEmailSettings?.(); return; }
        if (sub==='brand') { window.renderBrandingSettings?.(); return; }
        if (sub==='rbac') { window.renderRBACSettings?.(); return; }
        if (sub==='notif') { window.renderNotificationSettings?.(); return; }
        if (sub==='year') { window.renderAcademicYearSettings?.(); return; }
    }
    if (nav==='lms') {
        if (sub==='overview' || !sub) { window.renderLMSDashboard?.(); return; }
        if (sub==='materials' || sub==='videos' || sub==='assignments') { window.renderStudyMaterials?.(sub); return; }
        if (sub==='upload') { window.renderStudyMaterialUploadPage?.(); return; }
        if (sub==='categories') { window.renderLMSCategories?.(); return; }
        if (sub==='analytics') { window.renderLMSAnalytics?.(); return; }
    }
    if (nav==='comms') {
        if (sub==='email') { window.renderEmailModule?.(); return; }
        if (sub==='msg-log') { window.renderMessageLog?.(); return; }
    }
    if (nav==='staff-salary') {
        if (sub==='add' || sub==='edit') window.renderStaffSalaryForm?.(urlParams.get('id'));
        else window.renderStaffSalary?.();
        return;
    }
    if (nav==='reports') {
        if (sub==='fee-rep') { window.renderFeeReports?.(); return; }
        if (sub==='att-rep') { window.renderAttendanceReport?.(); return; }
        if (sub==='ex-rep')  { window.renderExamAnalytics?.(); return; }
        if (sub==='inq-rep') { window.renderInquiryAnalytics?.(); return; }
        if (sub==='lms-rep') { window.renderLMSAnalytics?.(); return; }
        if (sub==='custom')  { window.renderCustomReports?.(); return; }
        window.renderFeeReports?.(); return;
    }
    if (nav==='accounting') {
        if (window.AccountingModule) {
            window.AccountingModule.renderAction(sub);
        } else {
            mc.innerHTML = `<div class="pg fu"><div class="pg-loading">Loading Accounting Module...</div></div>`;
        }
        return;
    }
    if (nav==='expenses') {
        if (window.renderExpenses) {
            window.renderExpenses();
        } else {
            mc.innerHTML = '<div class="pg fu"><div class="pg-loading">Loading Expense Module...</div></div>';
        }
        return;
    }
    if (nav==='auditlogs') {
        window.renderAuditLogs?.();
        return;
    }
    if (nav==='support') {
        window.renderSupportPage?.();
        return;
    }
    if (nav==='feedback') {
        window.renderFeedbackPage?.();
        return;
    }
    mc.innerHTML = `<div class="pg fu"><div class="card" style="text-align:center;padding:100px 40px;"><i class="fa-solid fa-cubes-stacked" style="font-size:3rem;color:var(--tl);margin-bottom:20px;"></i><h2>${(sub||nav).toUpperCase()} Module</h2><p style="color:var(--tb);margin-top:10px;">Coming soon in V3.1.</p></div></div>`;
}

/* ── DASHBOARD (FINALIZED UI) ────────────────────────────────────── */
function _iaRenderDashboardSkeleton() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
        <div class="welcome-banner skeleton" style="height:150px; background:var(--bg); overflow:hidden; position:relative;">
            <div class="skeleton-pulse" style="position:absolute; inset:0; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); animation: skeleton-wave 1.5s infinite;"></div>
        </div>
        <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr); gap:20px; margin-top:20px;">
            ${Array(6).fill(0).map(() => `<div class="card" style="height:120px; background:var(--bg); position:relative; overflow:hidden;"><div class="skeleton-pulse" style="position:absolute; inset:0; background:linear-gradient(90deg, transparent, rgba(0,0,0,0.05), transparent); animation: skeleton-wave 1.5s infinite;"></div></div>`).join('')}
        </div>
        <div class="main-grid" style="margin-top:20px;">
            <div class="card" style="height:400px; background:var(--bg); position:relative; overflow:hidden;"><div class="skeleton-pulse" style="position:absolute; inset:0; background:linear-gradient(90deg, transparent, rgba(0,0,0,0.05), transparent); animation: skeleton-wave 1.5s infinite;"></div></div>
            <div class="card" style="height:400px; background:var(--bg); position:relative; overflow:hidden;"><div class="skeleton-pulse" style="position:absolute; inset:0; background:linear-gradient(90deg, transparent, rgba(0,0,0,0.05), transparent); animation: skeleton-wave 1.5s infinite;"></div></div>
        </div>
        <style>
            @keyframes skeleton-wave { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        </style>
    `;
}

async function _iaRenderDashboard() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    _iaRenderDashboardSkeleton();

    const endpoint = window.APP_URL ? window.APP_URL + '/app/Http/Controllers/Admin/dashboard_stats.php' : '/app/Http/Controllers/Admin/dashboard_stats.php';

    try {
        const res = await fetch(endpoint);
        const result = await res.json(); 
        if (!result.success) throw new Error(result.message);
        const s = result.data;
        
        const formatMoney = num => new Intl.NumberFormat('en-IN').format(Math.round(num || 0));

        mc.innerHTML = `
        <div class="dash-v2-container">
            <!-- SECTION A: HEADER STRIP -->
            <div class="dash-v2-header-strip">
                <div class="header-strip-left">
                    <div class="header-strip-institute">${s.header.institute_name}</div>
                    <div class="header-strip-meta">
                        <i class="fa-solid fa-calendar-days"></i> ${s.header.academic_year} &nbsp;•&nbsp; 
                        <i class="fa-solid fa-clock"></i> ${s.header.current_date}
                    </div>
                </div>
                <div class="header-strip-right">
                    <div class="plan-badge">
                        <div class="plan-status-dot ${s.header.status === 'expiring' ? 'expiring' : (s.header.status === 'expired' ? 'expired' : '')}"></div>
                        ${s.header.plan} Plan
                    </div>
                    <div class="header-actions">
                        <button class="btn-v2 secondary" onclick="alert('Upgrade feature coming soon')">Upgrade Plan</button>
                        <button class="btn-v2 secondary" onclick="window.print()"><i class="fa-solid fa-file-export"></i> Export</button>
                        <button class="btn-v2 primary" onclick="goNav('students','add')"><i class="fa-solid fa-user-plus"></i> New Student</button>
                    </div>
                </div>
            </div>

            <!-- SECTION B: PRIMARY KPI ROW -->
            <div class="kpi-row-v2">
                <!-- 0. Today's Collection -->
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Today's Collection</div>
                        <div class="kpi-v2-icon green"><i class="fa-solid fa-coins"></i></div>
                    </div>
                    <div class="kpi-v2-value">₹${formatMoney(s.kpi_fees.today_collection)}</div>
                    <div class="kpi-v2-meta">
                        <i class="fa-solid fa-calendar-day" style="opacity:0.7"></i> Received today
                    </div>
                    <div class="kpi-v2-progress">
                        <div class="kpi-v2-progress-fill" style="width: 100%; background: #10b981; opacity:0.3;"></div>
                    </div>
                </div>

                <!-- 1. Fee Collection -->
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Total Fee (Month)</div>
                        <div class="kpi-v2-icon green"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                    </div>
                    <div class="kpi-v2-value">₹${formatMoney(s.kpi_fees.collected)}</div>
                    <div class="kpi-v2-meta">
                        <span class="growth-pill ${s.kpi_fees.growth >= 0 ? 'up' : 'down'}">
                            ${s.kpi_fees.growth >= 0 ? '↑' : '↓'} ${Math.abs(s.kpi_fees.growth)}%
                        </span>
                        vs last month
                    </div>
                    <div class="kpi-v2-progress">
                        <div class="kpi-v2-progress-fill" style="width: ${s.kpi_fees.target > 0 ? Math.min(100, (s.kpi_fees.collected / s.kpi_fees.target) * 100) : 0}%; background: #10b981;"></div>
                    </div>
                    <div style="font-size:10px; color:var(--text-light); margin-top:8px; display:flex; justify-content:space-between;">
                        <span>Target: ₹${formatMoney(s.kpi_fees.target)}</span>
                        <span>${s.kpi_fees.target > 0 ? Math.round((s.kpi_fees.collected / s.kpi_fees.target) * 100) : 0}%</span>
                    </div>
                </div>

                <!-- 2. Pending Dues -->
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Pending Dues</div>
                        <div class="kpi-v2-icon orange"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    </div>
                    <div class="kpi-v2-value" style="color: ${s.kpi_dues.threshold_exceeded ? '#ef4444' : 'inherit'}">₹${formatMoney(s.kpi_dues.amount)}</div>
                    <div class="kpi-v2-meta">
                        <i class="fa-solid fa-users"></i> ${s.kpi_dues.count} students strictly overdue
                    </div>
                    <div class="kpi-v2-progress">
                        <div class="kpi-v2-progress-fill" style="width: 100%; background: ${s.kpi_dues.threshold_exceeded ? '#ef4444' : '#f59e0b'}; opacity: 0.3;"></div>
                    </div>
                </div>

                <!-- 3. Active Students -->
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Active Students</div>
                        <div class="kpi-v2-icon blue"><i class="fa-solid fa-user-graduate"></i></div>
                    </div>
                    <div class="kpi-v2-value">${s.kpi_students.total}</div>
                    <div class="kpi-v2-meta">
                        <span class="growth-pill up">+${s.kpi_students.new}</span> new this month
                    </div>
                    <div class="kpi-v2-progress">
                        <div class="kpi-v2-progress-fill" style="width: 100%; background: #3b82f6; opacity:0.3;"></div>
                    </div>
                </div>

                <!-- 4. Staff/Teachers -->
                <div class="kpi-card-v2">
                    <div class="kpi-v2-header">
                        <div class="kpi-v2-label">Total Staff</div>
                        <div class="kpi-v2-icon purple"><i class="fa-solid fa-chalkboard-user"></i></div>
                    </div>
                    <div class="kpi-v2-value">${s.kpi_staff.total}</div>
                    <div class="kpi-v2-meta">
                        <i class="fa-solid fa-circle-check" style="color:#10b981"></i> All systems active
                    </div>
                    <div class="kpi-v2-progress">
                        <div class="kpi-v2-progress-fill" style="width: 100%; background: #8b5cf6; opacity:0.3;"></div>
                    </div>
                </div>
            </div>

            <!-- SECTION C: SECONDARY KPI STRIP -->
            <div class="secondary-strip-v2">
                <div class="compact-card-v2">
                    <div class="compact-card-info">
                        <div class="compact-card-label">Avg. Attendance</div>
                        <div class="compact-card-value">${s.secondary_kpi.attendance.current}%</div>
                    </div>
                    <div class="compact-card-trend" style="color: ${s.secondary_kpi.attendance.current >= s.secondary_kpi.attendance.previous ? '#10b981' : '#ef4444'}">
                        ${s.secondary_kpi.attendance.current >= s.secondary_kpi.attendance.previous ? '↑' : '↓'} ${Math.abs(s.secondary_kpi.attendance.current - s.secondary_kpi.attendance.previous).toFixed(1)}%
                    </div>
                </div>
                <div class="compact-card-v2">
                    <div class="compact-card-info">
                        <div class="compact-card-label">Active Batches</div>
                        <div class="compact-card-value">${s.secondary_kpi.batches}</div>
                    </div>
                    <i class="fa-solid fa-layer-group" style="opacity:0.2"></i>
                </div>
                <div class="compact-card-v2">
                    <div class="compact-card-info">
                        <div class="compact-card-label">Total Courses</div>
                        <div class="compact-card-value">${s.secondary_kpi.courses}</div>
                    </div>
                    <i class="fa-solid fa-book" style="opacity:0.2"></i>
                </div>
                <div class="compact-card-v2">
                    <div class="compact-card-info">
                        <div class="compact-card-label">Open Inquiries</div>
                        <div class="compact-card-value">${s.secondary_kpi.inquiries}</div>
                    </div>
                    <span class="badge-pill" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b">Action Needed</span>
                </div>
            </div>

            <!-- SECTION D & E: ANALYTICS AREA -->
            <div class="main-analytics-v2">
                <!-- Fee Trend Chart -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-chart-line"></i> Fee Collection Trend (${new Date().getFullYear()})</h3>
                        <select class="form-select-sm" style="border:none; font-size:12px; font-weight:700; color:var(--text-light)">
                            <option>Last 12 Months</option>
                        </select>
                    </div>
                    <div class="panel-v2-body chart-container-v2" style="height: clamp(200px, 40dvh, 400px); max-width: 100%;">
                        <canvas id="revenueChartV2"></canvas>
                    </div>
                </div>

                <!-- Target Panel -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-bullseye"></i> Goal Tracking</h3>
                    </div>
                    <div class="panel-v2-body">
                        <!-- Fee Target -->
                        <div class="target-card">
                            <div class="target-circle">
                                <canvas id="feeTargetCircle" width="60" height="60"></canvas>
                                <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800;">
                                    ${s.targets.fee.percent}%
                                </div>
                            </div>
                            <div class="target-info">
                                <div class="target-label">Monthly Collection</div>
                                <div class="target-amt">₹${formatMoney(s.targets.fee.collected)}</div>
                                <div class="target-sub">Goal: ₹${formatMoney(s.targets.fee.target)}</div>
                            </div>
                        </div>

                        <!-- Enrollment Target -->
                        <div class="target-card">
                            <div class="target-circle">
                                <canvas id="enrollTargetCircle" width="60" height="60"></canvas>
                                <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800;">
                                    ${s.targets.enrollment.percent}%
                                </div>
                            </div>
                            <div class="target-info">
                                <div class="target-label">Student Admissions</div>
                                <div class="target-amt">${s.targets.enrollment.current} Students</div>
                                <div class="target-sub">Goal: ${s.targets.enrollment.target} Admissions</div>
                            </div>
                        </div>

                        <div style="background:#fef3c7; padding:12px; border-radius:10px; border:1px solid #fde68a; font-size:11px; color:#92400e; margin-top:10px;">
                            <i class="fa-solid fa-lightbulb"></i> <strong>Tip:</strong> Reach your targets to unlock Enterprise badge rewards.
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTTOM GRID: SECTIONS F-K -->
            <div class="bottom-grid">
                <!-- Notices -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-bullhorn"></i> Active Notices</h3>
                        <a href="#" style="font-size:11px; font-weight:700; color:var(--blue-pure)">View All</a>
                    </div>
                    <div class="panel-v2-body">
                        ${s.notices.length ? s.notices.map(n => `
                            <div class="v2-notice ${n.priority === 'high' ? 'high' : 'normal'}">
                                <div style="font-size:13px; font-weight:700; color:var(--text-dark)">${n.title}</div>
                                <div class="v2-notice-time">${new Date(n.created_at).toLocaleDateString()} • ${n.notice_type}</div>
                            </div>
                        `).join('') : '<div style="text-align:center; padding:20px; color:var(--text-light); font-size:12px;">No active notices</div>'}
                    </div>
                </div>

                <!-- Recent Admissions -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-user-plus"></i> Recent Admissions</h3>
                    </div>
                    <div class="panel-v2-body">
                        <div class="table-responsive">
                            <table class="v2-table">
                                ${s.recent_admissions.map(adm => `
                                    <tr>
                                        <td>
                                            <div class="v2-student-row">
                                                <div class="v2-avatar">${adm.full_name[0]}</div>
                                                <div>
                                                    <div style="font-size:13px; font-weight:700;">${adm.full_name}</div>
                                                    <div style="font-size:11px; color:var(--text-light)">${adm.course_name}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td align="right">
                                            <span class="v2-status-badge ${adm.status === 'active' ? 'active' : 'pending'}">${adm.status}</span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Attendance Overview -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-clipboard-user"></i> Attendance Today</h3>
                    </div>
                    <div class="panel-v2-body">
                        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                            <div style="text-align:center">
                                <div style="font-size:20px; font-weight:800; color:#10b981">${s.attendance_overview.today.present}</div>
                                <div style="font-size:10px; color:var(--text-light); text-transform:uppercase">Present</div>
                            </div>
                            <div style="text-align:center">
                                <div style="font-size:20px; font-weight:800; color:#ef4444">${s.attendance_overview.today.absent}</div>
                                <div style="font-size:10px; color:var(--text-light); text-transform:uppercase">Absent</div>
                            </div>
                            <div style="text-align:center">
                                <div style="font-size:20px; font-weight:800; color:#3b82f6">${s.attendance_overview.today.total}</div>
                                <div style="font-size:10px; color:var(--text-light); text-transform:uppercase">Total</div>
                            </div>
                        </div>
                        <div style="font-size:12px; font-weight:700; color:var(--text-light); margin-bottom:10px;">Batch-wise Breakdown</div>
                        ${s.attendance_overview.batches.map(b => `
                            <div style="margin-bottom:8px;">
                                <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:4px;">
                                    <span style="font-weight:600;">${b.name}</span>
                                    <span style="color:var(--text-light)">${b.present}/${b.total}</span>
                                </div>
                                <div class="progress" style="height:4px; border-radius:10px;">
                                    <div class="progress-bar" style="width: ${(b.present / (b.total || 1)) * 100}%; background:#10b981;"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-fingerprint"></i> Activity Log</h3>
                    </div>
                    <div class="panel-v2-body">
                        ${s.activity_log.length ? s.activity_log.map(act => `
                            <div class="v2-activity-item">
                                <div class="v2-activity-icon"><i class="fa-solid fa-bolt"></i></div>
                                <div class="v2-activity-content">
                                    <div class="v2-activity-title">${act.description}</div>
                                    <div class="v2-activity-meta">${act.user_name} • ${new Date(act.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
                                </div>
                            </div>
                        `).join('') : '<div style="text-align:center; padding:20px; color:var(--text-light); font-size:12px;">No recent activities</div>'}
                    </div>
                </div>

                <!-- Upcoming Exams -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-file-pen"></i> Upcoming Exams</h3>
                    </div>
                    <div class="panel-v2-body">
                        <div class="table-responsive">
                            <table class="v2-table">
                                ${s.upcoming_exams.map(ex => `
                                    <tr>
                                        <td>
                                            <div style="font-size:13px; font-weight:700;">${ex.title}</div>
                                            <div style="font-size:11px; color:var(--text-light);">${ex.course}</div>
                                        </td>
                                        <td align="right">
                                            <div style="font-size:12px; font-weight:700; color:var(--blue-pure)">${new Date(ex.exam_date).toLocaleDateString('en-IN', {day:'numeric', month:'short'})}</div>
                                            <div style="font-size:10px; color:var(--text-light)">${ex.start_time}</div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Library Module -->
                <div class="panel-v2">
                    <div class="panel-v2-header">
                        <h3><i class="fa-solid fa-book-open"></i> Library Hub</h3>
                    </div>
                    <div class="panel-v2-body">
                        ${s.library ? `
                            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; text-align:center;">
                                <div style="background:#f1f5f9; padding:12px; border-radius:12px;">
                                    <div style="font-size:18px; font-weight:800;">${s.library.total}</div>
                                    <div style="font-size:10px; color:var(--text-light); text-transform:uppercase">Books</div>
                                </div>
                                <div style="background:#f1f5f9; padding:12px; border-radius:12px;">
                                    <div style="font-size:18px; font-weight:800; color:#3b82f6">${s.library.issued}</div>
                                    <div style="font-size:10px; color:var(--text-light); text-transform:uppercase">Issued</div>
                                </div>
                                <div style="background:#f1f5f9; padding:12px; border-radius:12px;">
                                    <div style="font-size:18px; font-weight:800; color:#ef4444">${s.library.overdue}</div>
                                    <div style="font-size:10px; color:var(--text-light); text-transform:uppercase">Overdue</div>
                                </div>
                            </div>
                            <div style="margin-top:20px;">
                                <button class="btn-v2 secondary" style="width:100%; justify-content:center; color:var(--text-primary); border:1px solid var(--card-border); background:none">
                                    Issue New Book
                                </button>
                            </div>
                        ` : '<div style="text-align:center; padding:20px; color:var(--text-light); font-size:12px;">Library module not configured</div>'}
                    </div>
                </div>
            </div>
        </div>
        `;

        // Initialize Charts
        setTimeout(() => {
            _iaInitRevenueChartV2(s.revenue_graph);
            _iaDrawTargetCircle('feeTargetCircle', s.targets.fee.percent, '#10b981');
            _iaDrawTargetCircle('enrollTargetCircle', s.targets.enrollment.percent, '#3b82f6');
        }, 200);

    } catch(err) {
        console.error('Dashboard Load Error:', err);
        mc.innerHTML = `<div class="card" style="padding:40px;text-align:center;color:var(--red);"><i class="fa-solid fa-circle-exclamation" style="font-size:3rem;margin-bottom:16px;"></i><h3>Failed to load dashboard</h3><p>${err.message}</p><button class="btn-v2 primary" style="margin:20px auto 0;" onclick="_iaRenderDashboard()">Retry</button></div>`;
    }
}

function _iaInitRevenueChartV2(trendData) {
    if(!trendData || !trendData.length) return;
    const canvas = document.getElementById('revenueChartV2');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    // Destroy existing chart instance if any (for resize re-draw)
    const existing = typeof Chart !== 'undefined' && Chart.getChart ? Chart.getChart(canvas) : null;
    if (existing) existing.destroy();

    const isMobile = window.innerWidth < 768;
    const isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;

    const labels = trendData.map(d => d.month_name);
    const data   = trendData.map(d => d.collected);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Fee Collection (₹)',
                data: data,
                borderColor: '#10b981',
                borderWidth: 3,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4,
                pointRadius: isMobile ? 3 : 5,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#10b981',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: isMobile ? 1.4 : isTablet ? 2.0 : 2.5,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    titleFont: { family: 'Plus Jakarta Sans', size: isMobile ? 11 : 13 },
                    bodyFont:  { family: 'Plus Jakarta Sans', size: isMobile ? 11 : 13 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', borderDash: [5, 5] },
                    ticks: {
                        font: { size: isMobile ? 9 : 10 },
                        callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0) + 'K' : v)
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: isMobile ? 9 : 10, weight: '700' },
                        maxRotation: isMobile ? 45 : 0
                    }
                }
            }
        }
    });
    // Cache trend data for resize re-draw
    window._iaLastRevenueTrendData = trendData;
}

function _iaDrawTargetCircle(canvasId, percent, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const x = canvas.width / 2;
    const y = canvas.height / 2;
    const radius = 25;
    const endAngle = (percent / 100) * (Math.PI * 2) - (Math.PI / 2);

    // Background circle
    ctx.beginPath();
    ctx.arc(x, y, radius, 0, Math.PI * 2);
    ctx.strokeStyle = '#f1f5f9';
    ctx.lineWidth = 6;
    ctx.stroke();

    // Progress circle
    ctx.beginPath();
    ctx.arc(x, y, radius, -Math.PI / 2, endAngle);
    ctx.strokeStyle = color;
    ctx.lineWidth = 6;
    ctx.lineCap = 'round';
    ctx.stroke();
}

async function _iaToggleWorkflow(taskKey, el) {
    // Legacy mapping - we might update this later or remove if redundant
    console.log('Workflow toggle for:', taskKey);
}

function _iaInitRevenueChart(trendData) {
    if(!trendData || !trendData.length) return;
    const canvas = document.getElementById('revenueChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    const labels = trendData.map(d => d.month);
    const data   = trendData.map(d => d.amount);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(0, 184, 148, 0.4)');
    gradient.addColorStop(1, 'rgba(0, 184, 148, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (NPR)',
                data: data,
                borderColor: '#00B894',
                borderWidth: 3,
                fill: true,
                backgroundColor: gradient,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#00B894',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: '#f1f5f9' },
                    ticks: { callback: v => getCurrencySymbol() + (v/1000) + 'K' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

function _iaBindDashboardInteractions() {
     // Animate progress bars on load
    document.querySelectorAll('.progress-bar-fill').forEach(bar => {
        const target = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => { bar.style.width = target; }, 200);
    });
}

/* ── DOM READY ──────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const pv = (new URLSearchParams(window.location.search).get('page')) || 'overview';
    _IA.activeNav = pv.includes('-') ? pv.split('-')[0] : pv;
    _IA.activeSub = pv.includes('-') ? pv.split('-')[1] : null;

    // ── Global Search & Interactions ──
    const sbSearch = document.getElementById('globalSearch');
    
    // Global Search Functionality
    if (sbSearch) {
        let searchTimeout = null;
        let searchResultsDropdown = null;
        
        // Create/Get search results dropdown
        const getSearchDropdown = () => {
            if (searchResultsDropdown) return searchResultsDropdown;
            searchResultsDropdown = document.createElement('div');
            searchResultsDropdown.id = 'global-search-results';
            searchResultsDropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                max-height: 400px;
                overflow-y: auto;
                z-index: 9999;
                display: none;
                margin-top: 8px;
            `;
            const searchContainer = sbSearch.parentElement;
            searchContainer.style.position = 'relative';
            searchContainer.appendChild(searchResultsDropdown);
            return searchResultsDropdown;
        };
        
        // Perform global search
        const performSearch = async (query) => {
            if (query.length < 2) {
                const dropdown = getSearchDropdown();
                dropdown.style.display = 'none';
                return;
            }
            
            try {
                const baseUrl = typeof APP_URL !== 'undefined' ? APP_URL : (typeof window.APP_URL !== 'undefined' ? window.APP_URL : '');
                const url = `${baseUrl}/api/admin/global-search?q=${encodeURIComponent(query)}`;
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.success) {
                    displaySearchResults(data);
                }
            } catch (err) {
                console.error('Search error:', err);
            }
        };
        
        // Display search results
        const displaySearchResults = (data) => {
            const dropdown = getSearchDropdown();
            let html = '';
            
            // Students section
            if (data.students && data.students.length > 0) {
                html += `<div class="gs-section"><div class="gs-section-title">Students</div>`;
                data.students.forEach(s => {
                    const meta = s.roll_no ? `Roll: ${s.roll_no}` : (s.email || '');
                    html += `<a href="#" class="gs-item" data-type="student" data-id="${s.id}">
                        <span class="gs-icon">🎓</span>
                        <span class="gs-name">${s.name}</span>
                        <span class="gs-meta">${meta}</span>
                    </a>`;
                });
                html += `</div>`;
            }
            
            // Teachers/Staff section
            if (data.teachers && data.teachers.length > 0) {
                html += `<div class="gs-section"><div class="gs-section-title">Teachers/Staff</div>`;
                data.teachers.forEach(t => {
                    html += `<a href="#" class="gs-item" data-type="teacher" data-id="${t.id}">
                        <span class="gs-icon">👨‍🏫</span>
                        <span class="gs-name">${t.name}</span>
                        <span class="gs-meta">${t.role || ''}</span>
                    </a>`;
                });
                html += `</div>`;
            }
            
            // Batches section
            if (data.batches && data.batches.length > 0) {
                html += `<div class="gs-section"><div class="gs-section-title">Batches</div>`;
                data.batches.forEach(b => {
                    html += `<a href="#" class="gs-item" data-type="batch" data-id="${b.id}">
                        <span class="gs-icon">📚</span>
                        <span class="gs-name">${b.name}</span>
                        <span class="gs-meta">${b.course_name || ''}</span>
                    </a>`;
                });
                html += `</div>`;
            }
            
            // Courses section
            if (data.courses && data.courses.length > 0) {
                html += `<div class="gs-section"><div class="gs-section-title">Courses</div>`;
                data.courses.forEach(c => {
                    html += `<a href="#" class="gs-item" data-type="course" data-id="${c.id}">
                        <span class="gs-icon">📖</span>
                        <span class="gs-name">${c.name}</span>
                    </a>`;
                });
                html += `</div>`;
            }
            
            if (!html) {
                html = '<div class="gs-empty">No results found</div>';
            }
            
            dropdown.innerHTML = html;
            dropdown.style.display = 'block';
            
            // Add click handlers for results
            dropdown.querySelectorAll('.gs-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const type = item.dataset.type;
                    const id = item.dataset.id;
                    handleSearchResultClick(type, id);
                });
            });
        };
        
        // Handle search result click - navigate to relevant page
        const handleSearchResultClick = (type, id) => {
            const dropdown = getSearchDropdown();
            dropdown.style.display = 'none';
            sbSearch.value = '';
            
            switch(type) {
                case 'student':
                    goNav('students', 'view', { id: id });
                    break;
                case 'teacher':
                    goNav('staff', null, { id: id, action: 'view' });
                    break;
                case 'batch':
                    goNav('batches', null, { id: id, action: 'view' });
                    break;
                case 'course':
                    goNav('courses', null, { id: id, action: 'view' });
                    break;
            }
        };
        
        // Debounced search input handler
        sbSearch.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) clearTimeout(searchTimeout);
            
            if (query.length === 0) {
                const dropdown = getSearchDropdown();
                dropdown.style.display = 'none';
                return;
            }
            
            // Debounce search by 300ms
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (searchResultsDropdown && !sbSearch.contains(e.target) && !searchResultsDropdown.contains(e.target)) {
                searchResultsDropdown.style.display = 'none';
            }
        });
        
        // Keep dropdown open when clicking inside it
        if (searchResultsDropdown) {
            searchResultsDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }

    window.addEventListener('popstate', e => {
        const p = (e.state && e.state.pageVal) ? e.state.pageVal : (new URLSearchParams(window.location.search).get('page') || 'overview');
        _IA.activeNav = p.includes('-') ? p.split('-')[0] : p;
        _IA.activeSub = p.includes('-') ? p.split('-')[1] : null;
        _iaRenderSidebar(); _iaRenderPage();
    });

    const uc = document.getElementById('userChip'), ud = document.getElementById('userDropdown');
    if (uc && ud) {
        uc.addEventListener('click', e => { e.stopPropagation(); ud.classList.toggle('active'); });
        document.addEventListener('click', () => ud.classList.remove('active'));
    }

    _iaRenderSidebar(); _iaRenderPage();

    // ── Phase 1.9: Debounced resize handler for chart re-draw ──
    let _iaResizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(_iaResizeTimer);
        _iaResizeTimer = setTimeout(() => {
            // Re-draw bottom nav active state
            _iaRenderBottomNav();
            // Re-draw revenue chart if on dashboard and data is cached
            if (window._IA && window._IA.activeNav === 'overview') {
                const canvas = document.getElementById('revenueChartV2');
                if (canvas && window._iaLastRevenueTrendData) {
                    _iaInitRevenueChartV2(window._iaLastRevenueTrendData);
                }
            }
        }, 250);
    });
});
