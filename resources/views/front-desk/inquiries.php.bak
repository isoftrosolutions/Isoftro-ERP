<?php
/**
 * Front Desk — Inquiry Management
 * Modern, elegant listing of all student inquiries
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Inquiry Management';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('inquiries'); ?>

<main class="main" id="mainContent">
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #8B5CF6, #7C3AED);">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div>
                    <h1 class="pg-title">Inquiry Management</h1>
                    <p class="pg-sub">Track potential students and lead conversions</p>
                </div>
            </div>
            <div class="pg-acts">
                <a href="<?= APP_URL ?>/dash/front-desk/inquiry-add" class="btn" style="background:linear-gradient(135deg, #8B5CF6, #7C3AED); color:#fff; border:none; text-decoration:none; display:flex; align-items:center; gap:8px; padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px;">
                    <i class="fa-solid fa-plus"></i> New Inquiry
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-list-ul"></i></div></div>
                <div class="sc-val"><div class="skeleton" style="width:40px;height:24px;" id="totalInquiries"></div></div>
                <div class="sc-lbl">Total Inquiries</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-clock"></i></div></div>
                <div class="sc-val"><div class="skeleton" style="width:40px;height:24px;" id="pendingFollowups"></div></div>
                <div class="sc-lbl">Pending Follow-ups</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-user-check"></i></div></div>
                <div class="sc-val"><div class="skeleton" style="width:40px;height:24px;" id="convertedToday"></div></div>
                <div class="sc-lbl">Converted Today</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-chart-line"></i></div></div>
                <div class="sc-val"><div class="skeleton" style="width:40px;height:24px;" id="conversionRate"></div></div>
                <div class="sc-lbl">Conv. Rate (MTD)</div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card mb" style="padding:16px 20px; border-radius:14px; margin-bottom:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <div style="flex:1; min-width:250px; position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                <input type="text" id="inquirySearch" class="fi" placeholder="Search name, phone, or course..." style="padding-left:36px;" oninput="filterInquiries()">
            </div>
            <select id="statusFilter" class="fi" style="width:160px;" onchange="filterInquiries()">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="follow_up">Follow Up</option>
                <option value="converted">Converted</option>
                <option value="closed">Closed / Lost</option>
            </select>
            <select id="sourceFilter" class="fi" style="width:160px;" onchange="filterInquiries()">
                <option value="">All Sources</option>
                <option value="walk_in">Walk-in</option>
                <option value="phone">Phone Call</option>
                <option value="facebook">Facebook</option>
                <option value="website">Website</option>
            </select>
            <button class="btn bt" onclick="loadInquiries()">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>
        </div>

        <!-- Inquiries Table -->
        <div class="card" style="border-radius:16px; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                            <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Inquirer</th>
                            <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Interested Course</th>
                            <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Source</th>
                            <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Status</th>
                            <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Date</th>
                            <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inquiryTableBody">
                        <?php for($i=0; $i<6; $i++): ?>
                        <tr>
                            <td style="padding:15px;">
                                <div class="skeleton-text skeleton" style="width:140px;height:14px;margin-bottom:6px;"></div>
                                <div class="skeleton-text skeleton" style="width:100px;height:10px;"></div>
                            </td>
                            <td style="padding:15px;">
                                <div class="skeleton-text skeleton" style="width:120px;height:12px;"></div>
                            </td>
                            <td style="padding:15px;">
                                <div class="skeleton" style="width:80px;height:20px;border-radius:12px;"></div>
                            </td>
                            <td style="padding:15px;">
                                <div class="skeleton" style="width:70px;height:20px;border-radius:12px;"></div>
                            </td>
                            <td style="padding:15px;">
                                <div class="skeleton-text skeleton" style="width:80px;height:12px;"></div>
                            </td>
                            <td style="padding:15px;">
                                <div style="display:flex;gap:6px;justify-content:center;">
                                    <div class="skeleton" style="width:32px;height:32px;border-radius:8px;"></div>
                                    <div class="skeleton" style="width:32px;height:32px;border-radius:8px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#8B5CF6; box-shadow:0 0 0 3px rgba(139, 92, 246, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.st-tag { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:0.3px; }
.st-pending { background:#F3E8FF; color:#7E22CE; }
.st-follow_up { background:#FEF3C7; color:#92400E; }
.st-converted { background:#DCFCE7; color:#166534; }
.st-closed { background:#F1F5F9; color:#475569; }
.st-lost { background:#FEE2E2; color:#B91C1C; }
</style>

<script>
let allInquiries = [];

async function loadInquiries() {
    const tbody = document.getElementById('inquiryTableBody');
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/inquiries');
        const result = await res.json();
        
        if (result.success) {
            allInquiries = result.data || [];
            updateStats();
            filterInquiries();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:10px; display:block;"></i> Error: ${error.message}</td></tr>`;
    }
}

function updateStats() {
    const total = allInquiries.length;
    const pending = allInquiries.filter(i => i.status === 'pending' || i.status === 'follow_up').length;
    const convertedToday = allInquiries.filter(i => i.status === 'converted' && i.updated_at?.startsWith(new Date().toISOString().split('T')[0])).length;
    
    document.getElementById('totalInquiries').textContent = total;
    document.getElementById('pendingFollowups').textContent = pending;
    document.getElementById('convertedToday').textContent = convertedToday;
    
    const convertedTotal = allInquiries.filter(i => i.status === 'converted').length;
    const rate = total > 0 ? Math.round((convertedTotal / total) * 100) : 0;
    document.getElementById('conversionRate').textContent = rate + '%';
}

function filterInquiries() {
    const search = document.getElementById('inquirySearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const source = document.getElementById('sourceFilter').value;
    
    const filtered = allInquiries.filter(i => {
        const matchSearch = !search || 
            (i.full_name || '').toLowerCase().includes(search) || 
            (i.phone || '').toLowerCase().includes(search) || 
            (i.course_name || '').toLowerCase().includes(search);
        const matchStatus = !status || i.status === status;
        const matchSource = !source || i.source === source;
        return matchSearch && matchStatus && matchSource;
    });
    
    renderTable(filtered);
}

function renderTable(data) {
    const tbody = document.getElementById('inquiryTableBody');
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;">No inquiries found matching criteria.</td></tr>`;
        return;
    }
    
    tbody.innerHTML = data.map(i => {
        const statusClass = `st-${i.status || 'pending'}`;
        const sourceLabel = (i.source || 'Walk-in').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        return `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:14px 16px;">
                    <div style="font-weight:600; color:#1a1a2e;">${esc(i.full_name)}</div>
                    <div style="font-size:12px; color:#64748b;">${esc(i.phone)}</div>
                </td>
                <td style="padding:14px 16px;">
                    <div style="font-size:13px; font-weight:500; color:#1a1a2e;">${esc(i.course_name || 'N/A')}</div>
                </td>
                <td style="padding:14px 16px;">
                    <div style="font-size:12px; color:#64748b;"><i class="fa-solid ${getSourceIcon(i.source)}" style="margin-right:6px; opacity:0.7;"></i> ${sourceLabel}</div>
                </td>
                <td style="padding:14px 16px;">
                    <span class="st-tag ${statusClass}">${(i.status || 'pending').replace('_', ' ')}</span>
                </td>
                <td style="padding:14px 16px; font-size:12px; color:#64748b;">
                    ${formatDate(i.created_at)}
                </td>
                <td style="padding:14px 16px; text-align:center;">
                    <div style="display:flex; justify-content:center; gap:8px;">
                        <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="viewInquiry(${i.id})" title="View Details">
                            <i class="fa-solid fa-eye" style="color:#3B82F6;"></i>
                        </button>
                        <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="addFollowup(${i.id})" title="Add Follow-up">
                            <i class="fa-solid fa-phone" style="color:#F59E0B;"></i>
                        </button>
                        <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="convertToAdmission(${i.id})" title="Convert to Admission">
                            <i class="fa-solid fa-user-plus" style="color:#10B981;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function getSourceIcon(source) {
    switch(source) {
        case 'walk_in': return 'fa-person-walking';
        case 'phone': return 'fa-phone';
        case 'facebook': return 'fa-facebook';
        case 'website': return 'fa-globe';
        default: return 'fa-circle-question';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// Function stubs for actions
function viewInquiry(id) { alert('View Inquiry: ' + id); }
function addFollowup(id) { alert('Add Follow-up: ' + id); }
function convertToAdmission(id) { window.location.href = '<?= APP_URL ?>/dash/front-desk/admission-form?inquiry_id=' + id; }

document.addEventListener('DOMContentLoaded', loadInquiries);
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
