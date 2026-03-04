<?php
/**
 * Front Desk — Daily Collection Summary
 * Real table structure and data fetching for daily fee collection
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Daily Summary';
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
            <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div>
                <h1 class="pg-title">Daily Collection Summary</h1>
                <p class="pg-sub">Review transactions and collections for a specific date</p>
            </div>
        </div>
        <div class="pg-acts">
            <div style="display:flex; gap:10px; align-items:center;">
                <label style="font-size:13px; font-weight:600; color:#64748b;">Date:</label>
                <input type="date" id="collectionDate" class="fi" value="<?= date('Y-m-d') ?>" style="width:160px;" onchange="loadDailyCollection()">
                <button class="btn bt" onclick="loadDailyCollection()">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Collection Table -->
    <div class="card" style="border-radius:16px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Receipt No</th>
                        <th style="padding:14px 16px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Student</th>
                        <th style="padding:14px 16px; text-align:right; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Amount</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Method</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Time</th>
                        <th style="padding:14px 16px; text-align:center; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody id="collectionTableBody">
                    <tr>
                        <td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;">
                            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>
                            Loading today's collection...
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:700; border-top:2px solid #e2e8f0;">
                        <td colspan="2" style="padding:16px; text-align:right;">Grand Total:</td>
                        <td id="totalAmount" style="padding:16px; text-align:right; color:#059669; font-size:16px;">NPR 0.00</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#10B981; box-shadow:0 0 0 3px rgba(16, 185, 129, 0.1); }
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
async function loadDailyCollection() {
    const date = document.getElementById('collectionDate').value;
    const tbody = document.getElementById('collectionTableBody');
    const totalEl = document.getElementById('totalAmount');
    
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const url = `<?= APP_URL ?>/api/frontdesk/fee-reports?action=detailed_collection&start=${date}&end=${date}`;
        const res = await fetch(url);
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;">No collections found for this date.</td></tr>`;
                totalEl.textContent = 'NPR 0.00';
                return;
            }
            
            let total = 0;
            tbody.innerHTML = data.map(item => {
                total += parseFloat(item.amount);
                const methodClass = `m-${item.payment_method}`;
                const time = item.payment_date ? new Date(item.payment_date + (item.created_at ? ' ' + item.created_at.split(' ')[1] : '')).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'N/A';
                
                return `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:14px 16px; font-weight:700; color:#1e293b;">${item.receipt_number}</td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:600; color:#1a1a2e;">${item.student_name}</div>
                            <div style="font-size:11px; color:#64748b;">${item.roll_no} • ${item.batch_name}</div>
                        </td>
                        <td style="padding:14px 16px; text-align:right; font-weight:700; color:#1a1a2e;">
                            ${parseFloat(item.amount).toLocaleString('en-NP', {minimumFractionDigits: 2})}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span class="m-tag ${methodClass}">${item.payment_method.replace('_', ' ')}</span>
                        </td>
                        <td style="padding:14px 16px; text-align:center; font-size:12px; color:#64748b;">
                            ${time}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="window.open('<?= APP_URL ?>/public/receipts/${item.receipt_number}.pdf')">
                                <i class="fa-solid fa-file-pdf"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            totalEl.textContent = 'NPR ' + total.toLocaleString('en-NP', {minimumFractionDigits: 2});
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:10px; display:block;"></i> Error: ${error.message}</td></tr>`;
        totalEl.textContent = 'NPR 0.00';
    }
}

document.addEventListener('DOMContentLoaded', loadDailyCollection);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
