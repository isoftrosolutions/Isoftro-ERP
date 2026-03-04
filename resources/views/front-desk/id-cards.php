<?php
/**
 * Front Desk — ID Card Requests
 * Track and manage student ID cards
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'ID Card Generator';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('admissions');
}
?>

<div class="pg">
    <!-- Page Header -->
    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background:linear-gradient(135deg, #6366F1, #4F46E5);">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <h1 class="pg-title">ID Card Requests</h1>
                <p class="pg-sub">Manage and track student identification cards</p>
            </div>
        </div>
        <div class="pg-acts" style="display:flex; gap:10px;">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                <input type="text" id="idSearch" class="fi" placeholder="Search students..." style="width:220px; padding-left:35px;" onkeyup="loadIDCards()">
            </div>
            <select id="statusFilter" class="fi" style="width:160px;" onchange="loadIDCards()">
                <option value="">All Statuses</option>
                <option value="none">Not Requested</option>
                <option value="requested">Requested</option>
                <option value="processing">Processing</option>
                <option value="issued">Issued</option>
            </select>
        </div>
    </div>

    <!-- ID Card Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Roll No</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Student Name</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Current Status</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Issued At</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody id="idCardTableBody">
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading ID card status...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#6366F1; box-shadow:0 0 0 3px rgba(99, 102, 241, 0.1); }
.btn { padding:8px 16px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:6px; }
.id-tag { font-size:10px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.id-none { background:#F1F5F9; color:#475569; }
.id-requested { background:#DBEAFE; color:#1E40AF; }
.id-processing { background:#FEF3C7; color:#92400E; }
.id-issued { background:#DCFCE7; color:#166534; }
</style>

<script>
async function loadIDCards() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('idSearch').value;
    const tbody = document.getElementById('idCardTableBody');
    
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/id-card-requests?status=${status}&search=${encodeURIComponent(search)}`, getHeaders());
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No matching student records found.</td></tr>`;
                return;
            }
            
            tbody.innerHTML = data.map(s => {
                const cur = s.id_card_status || 'none';
                let tagClass = 'id-' + cur;
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-weight:700; color:#475569;">${esc(s.roll_no)}</td>
                        <td style="padding:14px 16px; font-weight:700; color:#1e293b;">${esc(s.full_name)}</td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="id-tag ${tagClass}">${cur.replace('_', ' ')}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center; font-size:12px; color:#64748b;">
                            ${s.id_card_issued_at ? new Date(s.id_card_issued_at).toLocaleDateString() : '-'}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                ${cur === 'none' ? `
                                    <button class="btn" style="background:#6366F1; color:#fff;" onclick="updateStatus(${s.id}, 'request')">Request</button>
                                ` : ''}
                                ${cur === 'requested' ? `
                                    <button class="btn" style="background:#F59E0B; color:#fff;" onclick="updateStatus(${s.id}, 'processing')">Process</button>
                                ` : ''}
                                ${(cur === 'requested' || cur === 'processing') ? `
                                    <button class="btn" style="background:#10B981; color:#fff;" onclick="updateStatus(${s.id}, 'issue')">Issue</button>
                                ` : ''}
                                ${cur === 'issued' ? `<i class="fa-solid fa-circle-check" style="color:#10B981; font-size:18px;"></i>` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

async function updateStatus(id, action) {
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/id-card-requests', getHeaders({
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action })
        }));
        const result = await res.json();
        if (result.success) loadIDCards();
    } catch (e) { alert(e.message); }
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

document.addEventListener('DOMContentLoaded', loadIDCards);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
