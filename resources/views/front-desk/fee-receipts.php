<?php
/**
 * Front Desk — Receipt History
 * Real table structure and data fetching for fee payment history
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Receipt History';
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
            <div class="pg-ico" style="background:linear-gradient(135deg, #3B82F6, #2563EB);">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <h1 class="pg-title">Receipt History</h1>
                <p class="pg-sub">Search and track all generated fee receipts</p>
            </div>
        </div>
        <div class="pg-acts" style="display:flex; gap:10px; align-items:center;">
            <div style="position:relative;">
                <i class="fa-solid fa-calendar-alt" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none;"></i>
                <input type="date" id="receiptFrom" class="fi" style="padding-left:36px; width:150px;" value="<?= date('Y-m-01') ?>">
            </div>
            <span style="color:#94a3b8; font-weight:600;">to</span>
            <div style="position:relative;">
                <i class="fa-solid fa-calendar-alt" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none;"></i>
                <input type="date" id="receiptTo" class="fi" style="padding-left:36px; width:150px;" value="<?= date('Y-m-d') ?>">
            </div>
            <button class="btn bt" onclick="loadReceipts()" style="padding:10px 14px;">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </div>

    <!-- Receipts Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Receipt No</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Date</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Student</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Fee Item</th>
                        <th style="padding:14px 16px; text-align:right; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Amount</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Mode</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody id="receiptsTableBody">
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading receipt history...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#3B82F6; box-shadow:0 0 0 3px rgba(59, 130, 246, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.m-tag { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; }
.m-cash { background:#DCFCE7; color:#166534; }
.m-bank_transfer { background:#DBEAFE; color:#1E40AF; }
.m-esewa { background:#F1F8E9; color:#33691E; }
.m-khalti { background:#F3E8FF; color:#6B21A8; }
</style>

<script>
async function loadReceipts() {
    const from = document.getElementById('receiptFrom').value;
    const to = document.getElementById('receiptTo').value;
    const tbody = document.getElementById('receiptsTableBody');
    
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const url = `<?= APP_URL ?>/api/frontdesk/fee-reports?action=detailed_collection&start=${from}&end=${to}`;
        const res = await fetch(url);
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:50px; color:#94a3b8;">No receipts found for this date range.</td></tr>`;
                return;
            }
            
            tbody.innerHTML = data.map(item => {
                const methodClass = `m-${item.payment_method}`;
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-weight:700; color:#1e293b;">${item.receipt_number}</td>
                        <td style="padding:14px 16px; font-size:13px; color:#475569;">
                            ${new Date(item.payment_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:600; color:#1a1a2e;">${item.student_name}</div>
                            <div style="font-size:11px; color:#64748b;">${item.roll_no}</div>
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-size:13px; color:#475569;">${item.fee_name || 'Generic Payment'}</div>
                        </td>
                        <td style="padding:14px 16px; text-align:right; font-weight:700; color:#1a1a2e;">
                            ${parseFloat(item.amount).toLocaleString('en-NP', {minimumFractionDigits: 2})}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="m-tag ${methodClass}">${item.payment_method.replace('_', ' ')}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <div style="display:flex; justify-content:center; gap:8px;">
                                <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="goNav('fee', 'fee-details', '&receipt_no=${item.receipt_number}')" title="View Details">
                                    <i class="fa-solid fa-eye" style="color:#6366F1;"></i>
                                </button>
                                <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="window.open('<?= APP_URL ?>/api/frontdesk/fees?action=generate_receipt_html&is_pdf=1&receipt_no=${item.receipt_number}')" title="View PDF">
                                    <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i>
                                </button>
                                <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="emailReceipt('${item.receipt_number}', this)" title="Email Receipt">
                                    <i class="fa-solid fa-envelope" style="color:#3B82F6;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:50px; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:10px; display:block;"></i> Error: ${error.message}</td></tr>`;
    }
}

async function emailReceipt(receiptNo, btn) {
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
    
    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/fees`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
            body: JSON.stringify({ action: 'trigger_email', receipt_no: receiptNo })
        });
        const result = await res.json();
        if (result.success) {
            btn.innerHTML = '<i class="fa-solid fa-check" style="color:#10B981;"></i>';
            setTimeout(() => { btn.innerHTML = originalHtml; btn.disabled = false; }, 2000);
        } else {
            alert('Error: ' + result.message);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    } catch (e) {
        alert('Failed to send email');
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', loadReceipts);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
