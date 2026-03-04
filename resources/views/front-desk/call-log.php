<?php
/**
 * Front Desk — Call Log
 * Track incoming and outgoing calls using the inquiries table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Call Log';
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
            <div class="pg-ico" style="background:linear-gradient(135deg, #0EA5E9, #0284C7);">
                <i class="fa-solid fa-phone-volume"></i>
            </div>
            <div>
                <h1 class="pg-title">Call Log</h1>
                <p class="pg-sub">Track and manage telephonic interactions</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn" style="background:linear-gradient(135deg, #0EA5E9, #0284C7); color:#fff;" onclick="openCallModal()">
                <i class="fa-solid fa-plus"></i> Log New Call
            </button>
        </div>
    </div>

    <!-- Call Log Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Date & Time</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Caller / Contact</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Type</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Call Summary</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody id="callTableBody">
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading logs...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination Bar -->
        <div id="paginationBar" style="padding:16px 20px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#fcfdfe;">
            <div style="font-size:13px; color:#64748b;" id="paginationInfo">
                Showing page 1 of 1
            </div>
            <div style="display:flex; gap:8px;" id="paginationBtns">
                <!-- Dynamic Buttons -->
            </div>
        </div>
    </div>
</div>

<!-- Call Modal -->
<div id="callModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:90%; max-width:450px; padding:0; border-radius:16px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:18px;">Log Call Record</h3>
            <button onclick="closeModal('callModal')" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:20px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="callForm" style="padding:24px;" onsubmit="handleCallSubmit(event)">
            <div style="margin-bottom:16px;">
                <label class="modal-label">Contact Name</label>
                <input type="text" id="call_name" required class="fi" placeholder="Name of person">
            </div>
            <div style="margin-bottom:16px;">
                <label class="modal-label">Phone Number</label>
                <input type="text" id="call_phone" required class="fi" placeholder="Contact number">
            </div>
            <div style="margin-bottom:16px;">
                <label class="modal-label">Call Type</label>
                <select id="call_type" class="fi">
                    <option value="incoming">Incoming Call</option>
                    <option value="outgoing">Outgoing Call</option>
                </select>
            </div>
            <div style="margin-bottom:24px;">
                <label class="modal-label">Conversation Summary</label>
                <textarea id="call_notes" class="fi" style="height:80px; resize:none;" placeholder="What was discussed?"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn bt" onclick="closeModal('callModal')">Cancel</button>
                <button type="submit" class="btn" style="background:#0EA5E9; color:#fff;">Save Log</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-label { display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#0EA5E9; box-shadow:0 0 0 3px rgba(14, 165, 233, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.type-tag { font-size:10px; font-weight:700; padding:2px 8px; border-radius:4px; text-transform:uppercase; }
.type-incoming { background:#DBEAFE; color:#1E40AF; }
.type-outgoing { background:#F3E8FF; color:#6B21A8; }
</style>

<script>
let currentCallPage = 1;
let totalCallPages = 1;

async function loadCalls(page = 1) {
    currentCallPage = page;
    const tbody = document.getElementById('callTableBody');
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const url = new URL('<?= APP_URL ?>/api/frontdesk/call-logs');
        url.searchParams.set('page', page);
        url.searchParams.set('limit', 20);

        const res = await fetch(url.toString(), getHeaders());
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            totalCallPages = result.total_pages || 1;

            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No call logs found.</td></tr>`;
                renderCallPagination();
                return;
            }
            
            tbody.innerHTML = data.map(c => {
                const isIncoming = c.source === 'incoming';
                const typeClass = isIncoming ? 'type-incoming' : 'type-outgoing';
                const typeLabel = isIncoming ? 'Inbound' : 'Outbound';
                const typeIcon = isIncoming ? 'fa-arrow-down-left' : 'fa-arrow-up-right';
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-size:13px; color:#475569;">
                            ${new Date(c.created_at).toLocaleString()}
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:700; color:#1e293b;">${esc(c.full_name)}</div>
                            <div style="font-size:11px; color:#64748b;">${esc(c.phone)}</div>
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="type-tag ${typeClass}"><i class="fa-solid ${typeIcon}" style="margin-right:4px;"></i>${typeLabel}</span>
                        </td>
                        <td style="padding:14px 16px; font-size:13px; color:#64748b; max-width:300px;">${esc(c.notes || '-')}</td>
                        <td style="padding:14px 16px; text-align:center;">
                             <button style="background:none; border:none; color:#ef4444; cursor:pointer;" onclick="deleteCall(${c.id})"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');
            renderCallPagination();
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

function renderCallPagination() {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');
    
    info.textContent = `Page ${currentCallPage} of ${totalCallPages}`;
    
    let html = '';
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadCalls(${currentCallPage - 1})" ${currentCallPage <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
    
    let start = Math.max(1, currentCallPage - 2);
    let end = Math.min(totalCallPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        const active = i === currentCallPage;
        html += `<button class="btn ${active ? '' : 'bt'}" style="padding:4px 10px; font-size:12px; ${active ? 'background:#0EA5E9; color:#fff; border:none;' : ''}" onclick="loadCalls(${i})">${i}</button>`;
    }
    
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadCalls(${currentCallPage + 1})" ${currentCallPage >= totalCallPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
    btns.innerHTML = html;
}

async function deleteCall(id) {
    if (!confirm('Delete this call log?')) return;
    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/call-logs?id=${id}`, getHeaders({ method: 'DELETE' }));
        const result = await res.json();
        if (result.success) loadCalls();
    } catch (e) { alert(e.message); }
}

function openCallModal() {
    document.getElementById('callModal').style.display = 'flex';
    document.getElementById('callForm').reset();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function handleCallSubmit(e) {
    e.preventDefault();
    const data = {
        full_name: document.getElementById('call_name').value,
        phone: document.getElementById('call_phone').value,
        call_type: document.getElementById('call_type').value,
        notes: document.getElementById('call_notes').value
    };

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/call-logs', getHeaders({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }));
        const result = await res.json();
        if (result.success) {
            closeModal('callModal');
            loadCalls();
        }
    } catch (e) { alert(e.message); }
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

document.addEventListener('DOMContentLoaded', loadCalls);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
