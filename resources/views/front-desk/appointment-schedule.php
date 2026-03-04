<?php
/**
 * Front Desk — Appointment Schedule
 * Manage visits and faculty meetings using the inquiries table
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Appointment Schedule';
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
            <div class="pg-ico" style="background:linear-gradient(135deg, #EC4899, #DB2777);">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <h1 class="pg-title">Appointment Schedule</h1>
                <p class="pg-sub">Manage upcoming visits and faculty meetings</p>
            </div>
        </div>
        <div class="pg-acts" style="display:flex; gap:10px; align-items:center;">
            <input type="date" id="appointmentFilterDate" class="fi" value="<?= date('Y-m-d') ?>" style="width:160px;" onchange="loadAppointments()">
            <button class="btn" style="background:linear-gradient(135deg, #EC4899, #DB2777); color:#fff;" onclick="openAppointmentModal()">
                <i class="fa-solid fa-plus"></i> Book Appointment
            </button>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Time</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Visitor / Student</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Purpose</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Status</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody id="appointmentTableBody">
                    <tr>
                        <td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading schedule...
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

<!-- Appointment Modal -->
<div id="appointmentModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div class="card" style="width:90%; max-width:450px; padding:0; border-radius:16px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:18px;">Schedule Appointment</h3>
            <button onclick="closeModal('appointmentModal')" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:20px;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="appointmentForm" style="padding:24px;" onsubmit="handleAppointmentSubmit(event)">
            <div style="margin-bottom:16px;">
                <label class="modal-label">Name</label>
                <input type="text" id="app_name" required class="fi" placeholder="Visitor or Student Name">
            </div>
            <div style="margin-bottom:16px;">
                <label class="modal-label">Phone</label>
                <input type="text" id="app_phone" required class="fi" placeholder="Contact Number">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label class="modal-label">Date</label>
                    <input type="date" id="app_date" required class="fi" value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="modal-label">Time</label>
                    <input type="time" id="app_time" required class="fi">
                </div>
            </div>
            <div style="margin-bottom:24px;">
                <label class="modal-label">Purpose</label>
                <textarea id="app_notes" class="fi" style="height:80px; resize:none;" placeholder="Notes for the meeting..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn bt" onclick="closeModal('appointmentModal')">Cancel</button>
                <button type="submit" class="btn" style="background:#EC4899; color:#fff;">Save Appointment</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-label { display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#EC4899; box-shadow:0 0 0 3px rgba(236, 72, 153, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.st-tag { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.st-pending { background:#FEF3C7; color:#92400E; }
.st-closed { background:#DCFCE7; color:#166534; }
</style>

<script>
let currentAppPage = 1;
let totalAppPages = 1;

async function loadAppointments(page = 1) {
    currentAppPage = page;
    const date = document.getElementById('appointmentFilterDate').value;
    const tbody = document.getElementById('appointmentTableBody');
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const url = new URL('<?= APP_URL ?>/api/frontdesk/appointments');
        url.searchParams.set('date', date);
        url.searchParams.set('page', page);
        url.searchParams.set('limit', 20);

        const res = await fetch(url.toString(), getHeaders());
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            totalAppPages = result.total_pages || 1;

            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">No appointments scheduled for this date.</td></tr>`;
                renderAppPagination();
                return;
            }
            
            tbody.innerHTML = data.map(app => {
                const isPending = app.status === 'pending';
                const statusLabel = isPending ? 'Pending' : 'Completed';
                const statusClass = isPending ? 'st-pending' : 'st-closed';
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-weight:700; color:#1e293b;">
                            ${formatTime(app.appointment_time)}
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:700; color:#1e293b;">${esc(app.full_name)}</div>
                            <div style="font-size:11px; color:#64748b;">${esc(app.phone)}</div>
                        </td>
                        <td style="padding:14px 16px; font-size:13px; color:#64748b; max-width:250px;">${esc(app.notes || '-')}</td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="st-tag ${statusClass}">${statusLabel}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            ${isPending ? `
                                <button class="btn" style="background:#1e293b; color:#fff; padding:6px 12px; font-size:11px;" onclick="completeAppointment(${app.id})">
                                    Complete
                                </button>
                            ` : `<i class="fa-solid fa-check-double" style="color:#10B981;"></i>`}
                        </td>
                    </tr>
                `;
            }).join('');
            renderAppPagination();
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:50px; color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

function renderAppPagination() {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');
    
    info.textContent = `Page ${currentAppPage} of ${totalAppPages}`;
    
    let html = '';
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadAppointments(${currentAppPage - 1})" ${currentAppPage <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
    
    let start = Math.max(1, currentAppPage - 2);
    let end = Math.min(totalAppPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        const active = i === currentAppPage;
        html += `<button class="btn ${active ? '' : 'bt'}" style="padding:4px 10px; font-size:12px; ${active ? 'background:#EC4899; color:#fff; border:none;' : ''}" onclick="loadAppointments(${i})">${i}</button>`;
    }
    
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadAppointments(${currentAppPage + 1})" ${currentAppPage >= totalAppPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
    btns.innerHTML = html;
}

async function completeAppointment(id) {
    if (!confirm('Mark as completed?')) return;
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/appointments', getHeaders({
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action: 'complete' })
        }));
        const result = await res.json();
        if (result.success) {
            loadAppointments();
        }
    } catch (e) { alert(e.message); }
}

function openAppointmentModal() {
    document.getElementById('appointmentModal').style.display = 'flex';
    document.getElementById('appointmentForm').reset();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

async function handleAppointmentSubmit(e) {
    e.preventDefault();
    const data = {
        full_name: document.getElementById('app_name').value,
        phone: document.getElementById('app_phone').value,
        appointment_date: document.getElementById('app_date').value,
        appointment_time: document.getElementById('app_time').value,
        notes: document.getElementById('app_notes').value
    };

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/appointments', getHeaders({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }));
        const result = await res.json();
        if (result.success) {
            closeModal('appointmentModal');
            loadAppointments();
        }
    } catch (e) { alert(e.message); }
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    // Handle both HH:MM:SS and HH:MM
    const [h, m] = timeStr.split(':');
    const date = new Date();
    date.setHours(h, m);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

document.addEventListener('DOMContentLoaded', loadAppointments);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
