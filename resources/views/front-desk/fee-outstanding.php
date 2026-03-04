<?php
/**
 * Front Desk — Outstanding Fee Dues
 * Real table structure and data fetching for outstanding dues
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Outstanding Dues';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('fees');
}
?>

<div class="pg">
    <!-- Page Header -->
    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background:linear-gradient(135deg, #EF4444, #DC2626);">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h1 class="pg-title">Outstanding Dues</h1>
                <p class="pg-sub">List of students with unpaid fees and overdue balances</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="loadOutstandingDues()">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh List
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="card mb" style="padding:16px 20px; border-radius:14px; margin-bottom:20px; display:flex; gap:24px; align-items:center; background:#FFF1F2; border:1px solid #FECACA;">
        <div>
            <div style="font-size:12px; font-weight:700; color:#991B1B; text-transform:uppercase;">Total Outstanding</div>
            <div id="totalOutstandingVal" style="font-size:24px; font-weight:800; color:#991B1B;">NPR 0.00</div>
        </div>
        <div style="width:1px; height:40px; background:#FECACA;"></div>
        <div>
            <div style="font-size:12px; font-weight:700; color:#991B1B; text-transform:uppercase;">Defaulter Count</div>
            <div id="defaulterCountVal" style="font-size:24px; font-weight:800; color:#991B1B;">0 Students</div>
        </div>
    </div>

    <!-- Outstanding Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Student</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Batch / Course</th>
                        <th style="padding:14px 16px; text-align:right; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Balance Due</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Oldest Due Date</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Status</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody id="outstandingTableBody">
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading outstanding data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.ov-tag { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.ov-on_time { background:#DCFCE7; color:#166534; }
.ov-overdue { background:#FEE2E2; color:#B91C1C; }
</style>

<script>
async function loadOutstandingDues() {
    const tbody = document.getElementById('outstandingTableBody');
    const totalVal = document.getElementById('totalOutstandingVal');
    const countVal = document.getElementById('defaulterCountVal');
    const today = new Date().toISOString().split('T')[0];
    
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        // Fetch stats first
        const statsRes = await fetch('<?= APP_URL ?>/api/frontdesk/fee-reports?action=summary');
        const statsResult = await statsRes.json();
        if (statsResult.success) {
            totalVal.textContent = 'NPR ' + parseFloat(statsResult.data.total_outstanding).toLocaleString('en-NP', {minimumFractionDigits: 2});
            countVal.textContent = statsResult.data.defaulter_count + ' Students';
        }

        // Fetch list
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/fee-reports?action=defaulters');
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;">No outstanding dues found. All student accounts are clear.</td></tr>`;
                return;
            }
            
            tbody.innerHTML = data.map(item => {
                const isOverdue = item.oldest_due_date < today;
                const statusLabel = isOverdue ? 'Overdue' : 'Due Soon';
                const statusClass = isOverdue ? 'ov-overdue' : 'ov-on_time';
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px;">
                            <div style="font-weight:700; color:#1e293b;">${item.full_name}</div>
                            <div style="font-size:11px; color:#64748b;">${item.roll_no}</div>
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-size:13px; color:#475569;">${item.batch_name}</div>
                        </td>
                        <td style="padding:14px 16px; text-align:right; font-weight:800; color:#991B1B;">
                            ${parseFloat(item.total_due).toLocaleString('en-NP', {minimumFractionDigits: 2})}
                        </td>
                        <td style="padding:14px 16px; text-align:center; font-size:13px; color:#64748b;">
                            ${new Date(item.oldest_due_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="ov-tag ${statusClass}">${statusLabel}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <button class="btn" style="background:#1e293b; color:#fff; padding:6px 12px; font-size:12px;" onclick="window.location.href='<?= APP_URL ?>/dash/front-desk/admission-form?student_id=${item.student_id}'">
                                <i class="fa-solid fa-credit-card"></i> Pay Now
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:10px; display:block;"></i> Error: ${error.message}</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', loadOutstandingDues);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
