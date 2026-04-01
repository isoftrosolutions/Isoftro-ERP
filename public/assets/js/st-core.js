/**
 * iSoftro ERP — Student Portal · st-core.js
 * Core: shared state, sidebar, page routing
 * Load this file LAST, after all st-*.js domain modules.
 */

/* ── STATE ──────────────────────────────────────────────────────────── */
window._ST = {
    activeNav: 'dashboard',
    activeSub: null,
    expanded: JSON.parse(localStorage.getItem('_st_expanded') || '{}'),
};

/**
 * Get currency symbol
 */
window.getCurrencySymbol = function() {
    return window._INSTITUTE_CONFIG?.currency_symbol || window.INSTITUTE_CONFIG?.currency_symbol || '₹';
};

/* ── NAV CONFIG ──────────────────────────────────────────────────────── */
const _ST_NAV = [
    { id: 'dashboard', icon: 'fa-house', label: 'Dashboard' },
    
    // Academic Section
    { section: 'Academic', items: [
        { 
            id: 'academics', 
            icon: 'fa-graduation-cap', 
            label: 'Academic Info',
            sub: [
                { id: 'timetable', l: 'My Timetable' },
                { id: 'attendance', l: 'Attendance' },
                { id: 'leave', l: 'Apply Leave' }
            ]
        },
    ]},
    
    // Learning Section
    { section: 'Learning', items: [
        { 
            id: 'learning', 
            icon: 'fa-book-open', 
            label: 'Knowledge Hub',
            sub: [
                { id: 'materials', l: 'Study Materials' },
                { id: 'assignments', l: 'Assignments' },
                { id: 'classes', l: 'Online Classes' }
            ]
        },
    ]},
    
    // Exams & Results Section
    { section: 'Exams & Results', items: [
        { 
            id: 'exams_results', 
            icon: 'fa-award', 
            label: 'Performance',
            sub: [
                { id: 'exams', l: 'Mock Exams' },
                { id: 'results', l: 'My Results' },
                { id: 'leaderboard', l: 'Leaderboard' }
            ]
        },
    ]},
    
    // Finance Section
    { section: 'Finance', items: [
        { 
            id: 'finance', 
            icon: 'fa-wallet', 
            label: 'Payments',
            sub: [
                { id: 'fees', l: 'Fee Status' },
                { id: 'receipts', l: 'Receipts' }
            ]
        },
    ]},
    
    // Library Section
    { section: 'Library', items: [
        { id: 'library', icon: 'fa-book-reader', label: 'My Books' },
    ]},
    
    // Support Section
    { section: 'Support', items: [
        { id: 'notices', icon: 'fa-bullhorn', label: 'Notices' },
        { id: 'contact', icon: 'fa-headset', label: 'Contact Admin' },
    ]},
    
    // Profile Section
    { section: 'Profile', items: [
        { 
            id: 'my_profile', 
            icon: 'fa-user-circle', 
            label: 'Account',
            sub: [
                { id: 'profile', l: 'My Profile' },
                { id: 'password', l: 'Change Password' },
                { id: 'idcard', l: 'Digital ID Card' }
            ]
        },
    ]},
];

/* ── NAVIGATION ──────────────────────────────────────────────────────── */
window.goST = function(id, subId = null, params = null) {
    _ST.activeNav = id;
    _ST.activeSub = subId;
    
    const url = new URL(window.location);
    const pageVal = subId ? `${id}-${subId}` : id;
    url.search = '';
    url.searchParams.set('page', pageVal);
    if (params) {
        Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
    }
    window.history.pushState({ pageVal }, '', url);
    
    if (window.innerWidth < 1024) {
        document.body.classList.remove('sb-active');
    }
    
    _stRenderSidebar();
    _stRenderPage();
};

/* ── Save expanded state to localStorage ── */
function _stSaveExpanded() {
    localStorage.setItem('_st_expanded', JSON.stringify(_ST.expanded));
}

/* ── SIDEBAR ──────────────────────────────────────────────────────────── */
function _stRenderSidebar(filter = '') {
    const sbBody = document.getElementById('sbBody');
    if (!sbBody) return;
    
    const badges = window._ST_BADGES || {};
    let html = '';
    
    // Build flat nav from _ST_NAV
    const flatNav = [];
    _ST_NAV.forEach(section => {
        const secName = section.section || 'General';
        const items = section.items || [section];
        items.forEach(item => {
            flatNav.push({
                id: item.id,
                icon: item.icon,
                label: item.label,
                section: secName,
                sub: item.sub || null,
                badge_key: item.badge_key || null,
            });
        });
    });
    
    // Group by section
    const sections = [...new Set(flatNav.map(n => n.section))];
    
    sections.forEach(sec => {
        const items = flatNav.filter(n => n.section === sec);
        if (!items.length) return;
        
        // Section Label
        html += `<div class="sb-sec">${sec}</div>`;
        
        // Items
        items.forEach(item => {
            if (filter && !item.label.toLowerCase().includes(filter)) return;
            
            const hasSub = !!(item.sub && item.sub.length);
            const isActive = _ST.activeNav === item.id ? 'active' : '';
            const isExp = _ST.expanded[item.id];
            
            const badge = item.badge_key && badges[item.badge_key] 
                ? `<span class="sb-badge primary">${badges[item.badge_key]}</span>` 
                : '';
            
            if (hasSub) {
                html += `
                    <button class="sb-btn ${isActive}" onclick="toggleSTExp('${item.id}');">
                        <i class="fa-solid ${item.icon}"></i>
                        <span class="sb-lbl">${item.label}</span>
                        <i class="fa-solid fa-chevron-right nbc ${isExp ? 'open' : ''}" style="margin-left:auto; font-size:10px;"></i>
                    </button>
                    <div class="sub-menu ${isExp ? 'open' : ''}" id="sub-${item.id}" style="${isExp ? 'display:block' : 'display:none'}">
                `;
                item.sub.forEach(s => {
                    const isSubActive = _ST.activeNav === item.id && _ST.activeSub === s.id ? 'active' : '';
                    html += `
                        <button class="sub-btn ${isSubActive}" onclick="goST('${item.id}', '${s.id}');">
                            ${s.l}
                        </button>
                    `;
                });
                html += `</div>`;
            } else {
                html += `
                    <button class="sb-btn ${isActive}" onclick="goST('${item.id}');">
                        <i class="fa-solid ${item.icon}"></i>
                        <span class="sb-lbl">${item.label}</span>
                        ${badge}
                    </button>
                `;
            }
        });
    });
    
    sbBody.innerHTML = html;
}

window.toggleSTExp = function(id) {
    _ST.expanded[id] = !_ST.expanded[id];
    _stSaveExpanded();
    _stRenderSidebar();
};

/* ── PAGE RENDERER ───────────────────────────────────────────────────── */
function _stRenderPage() {
    const nav = _ST.activeNav;
    const sub = _ST.activeSub;
    const urlParams = new URLSearchParams(window.location.search);
    
    // Update breadcrumb
    if (window.renderBreadcrumb) {
        window.renderBreadcrumb('student', nav, sub);
    }
    
    // Route to appropriate renderer
    switch (nav) {
        case 'dashboard':
            if (window.renderSTDashboard) window.renderSTDashboard();
            break;
        case 'profile':
            if (window.renderStudentProfile) window.renderStudentProfile();
            break;
        case 'password':
            if (window.renderChangePassword) window.renderChangePassword();
            break;
        case 'attendance':
            if (window.renderSTAttendance) window.renderSTAttendance();
            break;
        case 'fees':
            if (window.renderSTFees) window.renderSTFees();
            break;
        case 'materials':
            if (window.renderSTMaterials) window.renderSTMaterials();
            break;
        case 'assignments':
            if (window.renderSTAssignments) window.renderSTAssignments();
            break;
        case 'exams':
            if (window.renderSTExams) window.renderSTExams();
            break;
        case 'results':
            if (window.renderSTResults) window.renderSTResults();
            break;
        case 'library':
            if (window.renderSTLibrary) window.renderSTLibrary();
            break;
        case 'notices':
            if (window.renderSTNotices) window.renderSTNotices();
            break;
        case 'timetable':
            if (window.renderSTTimetable) window.renderSTTimetable();
            break;
        case 'classes':
            if (window.renderSTClasses) window.renderSTClasses();
            break;
        case 'idcard':
            if (window.renderSTIdCard) window.renderSTIdCard();
            break;
        case 'receipts':
            if (window.renderSTReceipts) window.renderSTReceipts();
            break;
        case 'leaderboard':
            if (window.renderSTLeaderboard) window.renderSTLeaderboard();
            break;
        case 'leave':
            if (window.renderSTLeave) window.renderSTLeave();
            break;
        case 'contact':
            if (window.renderSTContact) window.renderSTContact();
            break;
        default:
            if (window.renderSTDashboard) window.renderSTDashboard();
    }
}

/* ── INITIALIZE ─────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    // Parse URL params
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    
    if (pageParam) {
        const parts = pageParam.split('-');
        _ST.activeNav = parts[0];
        _ST.activeSub = parts[1] || null;
    }
    
    // Render initial UI
    _stRenderSidebar();
    _stRenderPage();
    
    // Handle browser back/forward
    window.addEventListener('popstate', function(e) {
        const urlParams = new URLSearchParams(window.location.search);
        const pageParam = urlParams.get('page');
        
        if (pageParam) {
            const parts = pageParam.split('-');
            _ST.activeNav = parts[0];
            _ST.activeSub = parts[1] || null;
            _stRenderSidebar();
            _stRenderPage();
        }
    });
});

// Global helper
window.goST = window.goST;
