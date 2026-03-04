<?php
/**
 * Front Desk — All Students Listing
 * Shows all students with registration status badges
 * Allows completing profiles for quick-registered students
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'All Students';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('students');
}
?>
<div class="pg">

    <!-- Page Header -->
    <div class="pg-head">
        <div>
            <h1 class="pg-title">Students Registry</h1>
            <p class="pg-sub">Manage admissions, track status, and complete profiles</p>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="loadStudents()">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>
            <a href="<?= APP_URL ?>/dash/front-desk/index?page=admissions-adm-form" class="btn bs">
                <i class="fa-solid fa-user-plus"></i> New Admission
            </a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="stat-grid mb">
        <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Total Students</span></div>
            <div class="stat-value" id="statTotal">...</div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Quick Registered</span></div>
            <div class="stat-value" id="statQuick">...</div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Fully Registered</span></div>
            <div class="stat-value" id="statFull">...</div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card mb" style="padding:16px 20px;">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:250px;position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                <input type="text" id="searchInput" class="fi" placeholder="Search by name, phone, roll no..."
                    style="padding-left:40px;" oninput="applyFilters()">
            </div>
            <select id="statusFilter" class="fi" style="width:200px;" onchange="applyFilters()">
                <option value="">All Statuses</option>
                <option value="quick_registered">Quick Registered</option>
                <option value="fully_registered">Fully Registered</option>
            </select>
            <div style="font-size:13px;color:#64748b;font-weight:600;">
                <span id="showingCount">0</span> Matches
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card">
        <div class="tw">
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Student Details</th>
                        <th>Program/Batch</th>
                        <th>Contact</th>
                        <th>Admission</th>
                        <th>Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsBody">
                    <?php for($i=0; $i<6; $i++): ?>
                    <tr>
                        <td colspan="7"><div class="skeleton" style="height:40px;"></div></td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination Bar -->
        <div id="paginationBar" style="padding:16px 20px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:13px;color:#64748b;" id="paginationInfo">Page 1 of 1</div>
            <div style="display:flex;gap:8px;" id="paginationBtns"></div>
        </div>
    </div>

</div>
<!-- Student Profile Drawer -->
<div id="profileDrawer" style="position:fixed;top:0;right:-480px;width:460px;height:100vh;background:#fff;z-index:8000;box-shadow:-4px 0 20px rgba(0,0,0,0.12);transition:right 0.3s ease;overflow-y:auto;"></div>
<div id="drawerOverlay" onclick="closeDrawer()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.3);z-index:7999;"></div>

<style>
.fi { width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;outline:none;transition:border-color 0.2s;background:#fff;box-sizing:border-box;font-family:inherit;}
.fi:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
.btn { padding:10px 20px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:all 0.2s; }
.bt  { background:#fff;color:#374151;border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; }
tr.student-row:hover { background:#f8fafc; }
tr.student-row td { padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.badge { font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:4px; }
.badge-quick { background:#FEF3C7;color:#92400E; }
.badge-full  { background:#DCFCE7;color:#166534; }
.badge-active { background:#DBEAFE;color:#1D4ED8; }
.act-btn { padding:5px 12px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid;transition:all 0.15s; }
</style>

<script>
<?php
$user = $_SESSION['userData'] ?? [];
echo "window.currentUser = " . json_encode($user) . ";\n";
echo "window.APP_URL = '" . APP_URL . "';\n";
?>

let currentPage = 1;
let totalPages = 1;
let pageSize = 20;

// ── Load Students (Paginated) ─────────────────────────────────
async function loadStudents(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    
    document.getElementById('studentsBody').innerHTML = `
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:24px;margin-bottom:10px;display:block;"></i>Loading...
        </td></tr>`;

    try {
        const url = new URL(APP_URL + '/api/frontdesk/students');
        url.searchParams.set('page', page);
        url.searchParams.set('limit', pageSize);
        if (search) url.searchParams.set('search', search);
        if (status) url.searchParams.set('registration_status', status);

        const res = await fetch(url.toString(), getHeaders());
        const data = await res.json();
        
        if (!data.success) throw new Error(data.message);
        
        allStudents = data.data || [];
        totalPages = data.total_pages || 1;
        
        document.getElementById('showingCount').textContent = data.total || 0;
        document.getElementById('statTotal').textContent = data.total || 0;
        
        renderTable(allStudents);
        renderPagination();
    } catch(e) {
        document.getElementById('studentsBody').innerHTML = `
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#EF4444;">
                <i class="fa-solid fa-exclamation-triangle" style="font-size:24px;margin-bottom:10px;display:block;"></i>
                ${e.message || 'Failed to load students.'}
                <br><button onclick="loadStudents()" class="btn bt" style="margin-top:10px;">Retry</button>
            </td></tr>`;
    }
}

function renderPagination() {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');
    
    info.textContent = `Page ${currentPage} of ${totalPages}`;
    
    let html = '';
    // Previous
    html += `<button class="btn bt" style="padding:6px 12px;font-size:12px;" onclick="loadStudents(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-left"></i>
    </button>`;
    
    // Page numbers (simple version: show current and nearby)
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        const active = i === currentPage;
        html += `<button class="btn ${active ? '' : 'bt'}" 
            style="padding:6px 12px;font-size:12px;${active ? 'background:#3B82F6;color:#fff;border:none;' : ''}" 
            onclick="loadStudents(${i})">${i}</button>`;
    }
    
    // Next
    html += `<button class="btn bt" style="padding:6px 12px;font-size:12px;" onclick="loadStudents(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-right"></i>
    </button>`;
    
    btns.innerHTML = html;
}

// ── Apply Filters ─────────────────────────────────────────────
function applyFilters() {
    // Reset to page 1 on filter change
    loadStudents(1);
}

// ── Render Table ──────────────────────────────────────────────
function renderTable(students) {
    const tbody = document.getElementById('studentsBody');
    if (!students.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fa-solid fa-users-slash" style="font-size:32px;margin-bottom:10px;display:block;"></i>
            No students found.</td></tr>`;
        return;
    }

    tbody.innerHTML = students.map((s, i) => {
        const isQuick   = s.registration_status === 'quick_registered';
        const statusBadge = isQuick
            ? `<span class="badge badge-quick"><i class="fa-solid fa-bolt"></i> Quick</span>`
            : `<span class="badge badge-full"><i class="fa-solid fa-check-circle"></i> Full</span>`;
        const initials  = (s.full_name || '?').charAt(0).toUpperCase();
        const regDate   = s.admission_date || s.created_at?.substr(0,10) || 'N/A';
        
        // Build photo URL - check for existing photo_url field
        const photoSrc = s.photo_url ? (s.photo_url.startsWith('http') ? s.photo_url : APP_URL + s.photo_url) : null;

        const completeBtn = isQuick
            ? `<a href="${APP_URL}/dash/front-desk/admission-form?complete=${s.id}" class="act-btn" style="color:#6C5CE7;border-color:#6C5CE7;text-decoration:none;">
                   <i class="fa-solid fa-user-check"></i> Complete
               </a>`
            : '';

        return `
        <tr class="student-row">
            <td style="font-size:13px;color:#94a3b8;">${i + 1}</td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;border-radius:9px;background:${photoSrc ? 'transparent' : 'linear-gradient(135deg,' + (isQuick?'#F59E0B,#D97706':'#10B981,#059669') + ')'};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;overflow:hidden;">
                        ${photoSrc 
                            ? `<img src="${photoSrc}" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.style.background='linear-gradient(135deg,' + '${isQuick ? ' #F59E0B,#D97706' : '#10B981,#059669'}' + ')';this.parentElement.innerHTML='${initials}'">`
                            : initials}
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1a1a2e;">${escHtml(s.full_name)}</div>
                        <div style="font-size:11px;color:#94a3b8;">${escHtml(s.roll_no)}</div>
                    </div>
                </div>
            </td>
            <td>
                <div style="font-size:13px;font-weight:500;color:#1a1a2e;">${escHtml(s.batch_name || 'N/A')}</div>
                <div style="font-size:11px;color:#94a3b8;">${escHtml(s.course_name || 'N/A')}</div>
            </td>
            <td style="font-size:13px;color:#374151;">${escHtml(s.phone || '—')}</td>
            <td style="font-size:12px;color:#64748b;">${regDate}</td>
            <td>${statusBadge}</td>
            <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button onclick="viewProfile(${s.id})" class="act-btn" style="color:#3B82F6;border-color:#3B82F6;background:transparent;">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                    ${completeBtn}
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── View Student Profile (drawer) ─────────────────────────────
async function viewProfile(id) {
    const drawer  = document.getElementById('profileDrawer');
    const overlay = document.getElementById('drawerOverlay');

    drawer.innerHTML = `<div style="padding:30px;text-align:center;padding-top:80px;color:#94a3b8;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:32px;"></i><div style="margin-top:10px;">Loading profile...</div></div>`;
    drawer.style.right = '0';
    overlay.style.display = 'block';

    try {
        const res = await fetch(APP_URL + '/api/frontdesk/students?id=' + id);
        const data = await res.json();
        const s = data.data;
        if (!s) throw new Error('Student not found');

        const isQuick   = s.registration_status === 'quick_registered';
        let addrStr = '—';
        try { const a = JSON.parse(s.permanent_address || '{}'); addrStr = a.address || a.province && (a.province + ', ' + a.district) || '—'; } catch(e) {}

        drawer.innerHTML = `
        <div style="background:linear-gradient(135deg,${isQuick?'#F59E0B,#D97706':'#10B981,#059669'});padding:28px;color:#fff;position:relative;">
            <button onclick="closeDrawer()" style="position:absolute;top:16px;right:16px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:14px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;margin-bottom:12px;">
                ${(s.full_name||'?').charAt(0).toUpperCase()}
            </div>
            <div style="font-size:20px;font-weight:700;">${escHtml(s.full_name)}</div>
            <div style="font-size:13px;opacity:0.85;margin-top:3px;">${escHtml(s.roll_no)} • ${escHtml(s.batch_name||'N/A')}</div>
            <div style="margin-top:10px;">
                <span style="background:rgba(255,255,255,0.2);font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;">
                    ${isQuick ? '⚡ Quick Registered' : '✅ Fully Registered'}
                </span>
            </div>
        </div>

        <div style="padding:20px;">
            ${isQuick ? `<div style="background:#FEF9C3;border:1px solid #FCD34D;border-radius:10px;padding:12px 15px;margin-bottom:16px;font-size:13px;color:#92400E;">
                <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
                This student has a quick registration profile. Full details are incomplete.
                <a href="${APP_URL}/dash/front-desk/admission-form?complete=${s.id}" style="margin-left:8px;font-weight:600;color:#7C3AED;">Complete Profile →</a>
            </div>` : ''}

            <div style="display:grid;gap:12px;">
                ${profileRow('fa-envelope', 'Email', s.email || '—')}
                ${profileRow('fa-phone', 'Phone', s.phone || '—')}
                ${profileRow('fa-venus-mars', 'Gender', s.gender ? s.gender.charAt(0).toUpperCase() + s.gender.slice(1) : '—')}
                ${profileRow('fa-calendar', 'DOB (AD)', s.dob_ad || '—')}
                ${profileRow('fa-calendar-days', 'DOB (BS)', s.dob_bs || '—')}
                ${profileRow('fa-droplet', 'Blood Group', s.blood_group || '—')}
                ${profileRow('fa-location-dot', 'Permanent Address', addrStr)}
                ${profileRow('fa-person', 'Parent / Husband', s.father_name || s.husband_name || '—')}
                ${profileRow('fa-id-card', 'Citizenship No.', s.citizenship_no || '—')}
                ${profileRow('fa-calendar-check', 'Admission Date', s.admission_date || '—')}
            </div>

            <div style="margin-top:20px;display:flex;gap:10px;">
                ${isQuick ? `<a href="${APP_URL}/dash/front-desk/admission-form?complete=${s.id}" style="flex:1;text-align:center;padding:11px;background:linear-gradient(135deg,#6C5CE7,#8B5CF6);color:#fff;border-radius:10px;font-weight:600;font-size:13px;text-decoration:none;">
                    <i class="fa-solid fa-user-check"></i> Complete Profile
                </a>` : ''}
                <button onclick="closeDrawer()" style="flex:1;padding:11px;background:#f1f5f9;color:#374151;border:none;border-radius:10px;font-weight:600;font-size:13px;cursor:pointer;">
                    Close
                </button>
            </div>
        </div>`;
    } catch(e) {
        drawer.innerHTML = `<div style="padding:30px;text-align:center;color:#EF4444;padding-top:80px;">
            Error: ${e.message}<br><button onclick="closeDrawer()" class="btn bt" style="margin-top:10px;">Close</button></div>`;
    }
}

function profileRow(icon, label, value) {
    return `<div style="display:flex;align-items:flex-start;gap:12px;padding:10px;background:#f8fafc;border-radius:8px;">
        <i class="fa-solid ${icon}" style="width:16px;text-align:center;color:#64748b;margin-top:2px;"></i>
        <div>
            <div style="font-size:11px;color:#94a3b8;font-weight:500;">${label}</div>
            <div style="font-size:13px;color:#1a1a2e;font-weight:500;">${escHtml(String(value))}</div>
        </div>
    </div>`;
}

function closeDrawer() {
    document.getElementById('profileDrawer').style.right = '-480px';
    document.getElementById('drawerOverlay').style.display = 'none';
}

function escHtml(str) {
    if (!str) return '—';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Handle ?complete= param (e.g. from Quick Reg list btn) ────
window.addEventListener('DOMContentLoaded', async () => {
    await loadStudents();
    const params = new URLSearchParams(window.location.search);
    const completeId = params.get('complete');
    if (completeId) {
        // Small delay to let table render, then open profile drawer
        setTimeout(() => viewProfile(parseInt(completeId, 10)), 300);
    }
});
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
