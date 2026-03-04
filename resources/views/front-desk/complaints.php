<?php
/**
 * Front Desk — Complaints Registration
 * Manage student and visitor complaints using the inquiries table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Complaint Registration';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('reception_desk');
}
?>

<div class="pg">
    <!-- Page Header -->
    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background:linear-gradient(135deg, #EF4444, #B91C1C);">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <h1 class="pg-title">Complaints</h1>
                <p class="pg-sub">Register and track resolution of issues</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn" style="background:linear-gradient(135deg, #EF4444, #B91C1C); color:#fff;" onclick="openComplaintModal()">
                <i class="fa-solid fa-plus"></i> Lodge Complaint
            </button>
        </div>
    </div>

    <!-- Complaints Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Date</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Complainant</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Description</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Status</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody id="complaintTableBody">
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Fetching complaints...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Complaint Modal -->
<div id="complaintModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:90%; max-width:480px; padding:0; border-radius:16px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:18px;">Lodge New Complaint</h3>
            <button onclick="closeModal('complaintModal')" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:20px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="complaintForm" style="padding:24px;" onsubmit="handleComplaintSubmit(event)">
            <div style="margin-bottom:16px;">
                <label class="modal-label">Complainant Name</label>
                <input type="text" id="comp_name" required class="fi" placeholder="Student or visitor name">
            </div>
            <div style="margin-bottom:16px;">
                <label class="modal-label">Phone (Optional)</label>
                <input type="text" id="comp_phone" class="fi" placeholder="Contact number">
            </div>
            <div style="margin-bottom:24px;">
                <label class="modal-label">Complaint Details</label>
                <textarea id="comp_notes" required class="fi" style="height:120px; resize:none;" placeholder="Describe the issue in detail..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn bt" onclick="closeModal('complaintModal')">Cancel</button>
                <button type="submit" class="btn" style="background:#EF4444; color:#fff;">Register Complaint</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-label { display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#EF4444; box-shadow:0 0 0 3px rgba(239, 68, 68, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.c-badge { font-size:10px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.c-open { background:#FEE2E2; color:#991B1B; }
.c-progress { background:#FEF3C7; color:#92400E; }
.c-resolved { background:#DCFCE7; color:#166534; }
</style>

<script>
async function loadComplaints() {
    const tbody = document.getElementById('complaintTableBody');
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/complaints', getHeaders());
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No complaints registered.</td></tr>`;
                return;
            }
            
            tbody.innerHTML = data.map(c => {
                const status = c.status || 'open';
                let statusClass = 'c-open';
                if (status === 'in_progress') statusClass = 'c-progress';
                if (status === 'resolved') statusClass = 'c-resolved';
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-size:13px; color:#475569;">
                            ${new Date(c.created_at).toLocaleDateString()}
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:700; color:#1e293b;">${esc(c.full_name)}</div>
                            <div style="font-size:11px; color:#64748b;">${esc(c.phone || '-')}</div>
                        </td>
                        <td style="padding:14px 16px; font-size:13px; color:#475569; max-width:350px;">
                            ${esc(c.notes)}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="c-badge ${statusClass}">${status.replace('_', ' ')}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <select onchange="updateComplaintStatus(${c.id}, this.value)" style="padding:4px 8px; font-size:11px; border-radius:6px; border:1px solid #e2e8f0; outline:none;">
                                <option value="open" ${status==='open'?'selected':''}>Open</option>
                                <option value="in_progress" ${status==='in_progress'?'selected':''}>In Progress</option>
                                <option value="resolved" ${status==='resolved'?'selected':''}>Resolved</option>
                            </select>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

async function updateComplaintStatus(id, status) {
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/complaints', getHeaders({
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status })
        }));
        const result = await res.json();
        if (result.success) loadComplaints();
    } catch (e) { alert(e.message); }
}

function openComplaintModal() {
    document.getElementById('complaintModal').style.display = 'flex';
    document.getElementById('complaintForm').reset();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function handleComplaintSubmit(e) {
    e.preventDefault();
    const data = {
        full_name: document.getElementById('comp_name').value,
        phone: document.getElementById('comp_phone').value,
        notes: document.getElementById('comp_notes').value
    };

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/complaints', getHeaders({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }));
        const result = await res.json();
        if (result.success) {
            closeModal('complaintModal');
            loadComplaints();
        }
    } catch (e) { alert(e.message); }
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

document.addEventListener('DOMContentLoaded', loadComplaints);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
