/**
 * Hamro ERP — Student Portal · st-core.js
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
    { id: 'dashboard', icon: 'fa-house', label: 'Dashboard', action: "goST('dashboard')" },
    
    // Academic Section
    { section: 'Academic', items: [
        { id: 'timetable', icon: 'fa-calendar-alt', label: 'My Timetable', action: "goST('timetable')" },
        { id: 'attendance', icon: 'fa-calendar-check', label: 'Attendance', action: "goST('attendance')" },
        { id: 'leave', icon: 'fa-user-clock', label: 'Apply Leave', action: "goST('leave')" },
    ]},
    
    // Learning Section
    { section: 'Learning', items: [
        { id: 'materials', icon: 'fa-book', label: 'Study Materials', action: "goST('materials')", badge_key: 'materials' },
        { id: 'assignments', icon: 'fa-tasks', label: 'Assignments', action: "goST('assignments')", badge_key: 'assignments' },
        { id: 'classes', icon: 'fa-video', label: 'Online Classes', action: "goST('classes')" },
    ]},
    
    // Exams & Results Section
    { section: 'Exams & Results', items: [
        { id: 'exams', icon: 'fa-file-alt', label: 'Mock Exams', action: "goST('exams')", badge_key: 'exams' },
        { id: 'results', icon: 'fa-trophy', label: 'My Results', action: "goST('results')" },
        { id: 'leaderboard', icon: 'fa-medal', label: 'Leaderboard', action: "goST('leaderboard')" },
    ]},
    
    // Finance Section
    { section: 'Finance', items: [
        { id: 'fees', icon: 'fa-money-bill-wave', label: 'Fee Status', action: "goST('fees')" },
        { id: 'receipts', icon: 'fa-receipt', label: 'Receipts', action: "goST('receipts')" },
    ]},
    
    // Library Section
    { section: 'Library', items: [
        { id: 'library', icon: 'fa-book-reader', label: 'My Books', action: "goST('library')", badge_key: 'books' },
    ]},
    
    // Support Section
    { section: 'Support', items: [
        { id: 'notices', icon: 'fa-bullhorn', label: 'Notices', action: "goST('notices')", badge_key: 'notices' },
        { id: 'contact', icon: 'fa-headset', label: 'Contact Admin', action: "goST('contact')" },
    ]},
    
    // Profile Section
    { section: 'Profile', items: [
        { id: 'profile', icon: 'fa-user-graduate', label: 'My Profile', action: "goST('profile')" },
        { id: 'password', icon: 'fa-key', label: 'Change Password', action: "goST('password')" },
        { id: 'idcard', icon: 'fa-id-card', label: 'Digital ID Card', action: "goST('idcard')" },
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
            
            const isActive = _ST.activeNav === item.id ? 'active' : '';
            const badge = item.badge_key && badges[item.badge_key] 
                ? `<span class="sb-badge primary">${badges[item.badge_key]}</span>` 
                : '';
            
            html += `
                <button class="sb-btn ${isActive}" onclick="goST('${item.id}');">
                    <i class="fa-solid ${item.icon}"></i>
                    <span class="sb-lbl">${item.label}</span>
                    ${badge}
                </button>
            `;
        });
    });
    
    sbBody.innerHTML = html;
}

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
