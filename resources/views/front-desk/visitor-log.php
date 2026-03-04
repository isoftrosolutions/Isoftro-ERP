<?php
/**
 * Front Desk — Visitor Log
 * Real-time tracking of visitors using the inquiries table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Visitor Log';
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
        <div>
            <h1 class="pg-title">Visitor Registry</h1>
            <p class="pg-sub">Monitor and manage institutional visitors in real-time</p>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="loadVisitors()">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>
            <button class="btn bs" onclick="openCheckInModal()">
                <i class="fa-solid fa-person-walking-arrow-right"></i> Register Visitor
            </button>
        </div>
    </div>

    <!-- Visitor Table -->
    <div class="card">
        <div class="tw">
            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Visitor Details</th>
                        <th>Contact</th>
                        <th>Purpose of Visit</th>
                        <th style="text-align:center">In</th>
                        <th style="text-align:center">Out</th>
                        <th style="text-align:center">Status</th>
                        <th style="text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody id="visitorTableBody">
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:flex; align-items:center; justify-content:center;"></i>
                            Initializing visitor log...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination Bar -->
        <div id="paginationBar" style="padding:16px 20px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
            <div style="font-size:13px; color:#64748b;" id="paginationInfo">Page 1 of 1</div>
            <div style="display:flex; gap:8px;" id="paginationBtns"></div>
        </div>
    </div>
</div>
</div>

<!-- Check-In Modal -->
<div id="checkInModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:90%; max-width:450px; padding:0; border-radius:16px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:18px;">Visitor Check-In</h3>
            <button onclick="closeModal('checkInModal')" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:20px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="checkInForm" style="padding:24px;" onsubmit="handleCheckInSubmit(event)">
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Visitor Name</label>
                <input type="text" name="full_name" id="vis_name" required class="fi" placeholder="Full name of visitor">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Phone Number</label>
                <input type="text" name="phone" id="vis_phone" required class="fi" placeholder="Contact number">
            </div>
            <div style="margin-bottom:24px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Purpose of Visit</label>
                <textarea name="notes" id="vis_notes" class="fi" style="height:80px; resize:none;" placeholder="Reason for visiting..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn bt" onclick="closeModal('checkInModal')">Cancel</button>
                <button type="submit" class="btn" style="background:#F59E0B; color:#fff;">Check In</button>
            </div>
        </form>
    </div>
</div>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#F59E0B; box-shadow:0 0 0 3px rgba(245, 158, 11, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.v-tag { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.v-inside { background:#FEF3C7; color:#92400E; }
.v-left { background:#F1F5F9; color:#475569; }
</style>

<script>
let currentVisitorPage = 1;
let totalVisitorPages = 1;

async function loadVisitors(page = 1) {
    currentVisitorPage = page;
    const tbody = document.getElementById('visitorTableBody');
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const url = new URL('<?= APP_URL ?>/api/frontdesk/visitor-log');
        url.searchParams.set('page', page);
        url.searchParams.set('limit', 20);

        const res = await fetch(url.toString(), getHeaders());
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            totalVisitorPages = result.total_pages || 1;

            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;">No visitor logs found.</td></tr>`;
                renderVisitorPagination();
                return;
            }
            
            tbody.innerHTML = data.map(v => {
                const isInside = !v.check_out_at;
                const statusLabel = isInside ? 'Inside' : 'Checked Out';
                const statusClass = isInside ? 'v-inside' : 'v-left';
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-weight:700; color:#1e293b;">${esc(v.full_name)}</td>
                        <td style="padding:14px 16px; font-size:13px; color:#475569;">${esc(v.phone)}</td>
                        <td style="padding:14px 16px; font-size:13px; color:#64748b; max-width:250px;">${esc(v.notes || '-')}</td>
                        <td style="padding:14px 16px; text-align:center; font-size:12px; color:#475569;">
                            ${formatTime(v.check_in_at)}
                        </td>
                        <td style="padding:14px 16px; text-align:center; font-size:12px; color:#475569;">
                            ${v.check_out_at ? formatTime(v.check_out_at) : '-'}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="v-tag ${statusClass}">${statusLabel}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            ${isInside ? `
                                <button class="btn" style="background:#1e293b; color:#fff; padding:6px 12px; font-size:11px;" onclick="checkOutVisitor(${v.id})">
                                    Check Out
                                </button>
                            ` : `<i class="fa-solid fa-check" style="color:#10B981;"></i>`}
                        </td>
                    </tr>
                `;
            }).join('');
            renderVisitorPagination();
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:50px; color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

function renderVisitorPagination() {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');
    
    info.textContent = `Page ${currentVisitorPage} of ${totalVisitorPages}`;
    
    let html = '';
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadVisitors(${currentVisitorPage - 1})" ${currentVisitorPage <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
    
    let start = Math.max(1, currentVisitorPage - 2);
    let end = Math.min(totalVisitorPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        const active = i === currentVisitorPage;
        html += `<button class="btn ${active ? '' : 'bt'}" style="padding:4px 10px; font-size:12px; ${active ? 'background:#F59E0B; color:#fff; border:none;' : ''}" onclick="loadVisitors(${i})">${i}</button>`;
    }
    
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadVisitors(${currentVisitorPage + 1})" ${currentVisitorPage >= totalVisitorPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
    btns.innerHTML = html;
}

async function checkOutVisitor(id) {
    if (!confirm('Mark this visitor as checked out?')) return;
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/visitor-log', getHeaders({
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action: 'checkout' })
        }));
        const result = await res.json();
        if (result.success) {
            loadVisitors();
        }
    } catch (e) { alert(e.message); }
}

function openCheckInModal() {
    document.getElementById('checkInModal').style.display = 'flex';
    document.getElementById('checkInForm').reset();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function handleCheckInSubmit(e) {
    e.preventDefault();
    const data = {
        full_name: document.getElementById('vis_name').value,
        phone: document.getElementById('vis_phone').value,
        notes: document.getElementById('vis_notes').value
    };

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/visitor-log', getHeaders({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }));
        const result = await res.json();
        if (result.success) {
            closeModal('checkInModal');
            loadVisitors();
        }
    } catch (e) { alert(e.message); }
}

function formatTime(ts) {
    if (!ts) return '-';
    return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

document.addEventListener('DOMContentLoaded', loadVisitors);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
