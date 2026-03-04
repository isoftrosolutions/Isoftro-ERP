<?php
/**
 * Front Desk — Fee Reports
 * Granular analysis of payments, outstanding dues and batch-wise status
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Fee Reports';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';

}
// Fetch batches for filter
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];
$stmtBatches = $db->prepare("SELECT id, name FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('reports');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <h1 class="pg-title">Fee Reports</h1>
                    <p class="pg-sub">Detailed tracking of payment status and historical dues</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
                <button class="btn" style="background:#1a1a2e; color:#fff;" onclick="exportData()">
                    <i class="fa-solid fa-file-export"></i> Export Report
                </button>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="card mb" style="padding:16px 20px; border-radius:14px; margin-bottom:24px; display:flex; gap:12px; align-items:center;">
            <div style="flex:1;">
                <label class="fl">Batch</label>
                <select id="batchFilter" class="fi" onchange="loadReport()">
                    <option value="">All Batches</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex:1;">
                <label class="fl">Status</label>
                <select id="statusFilter" class="fi" onchange="loadReport()">
                    <option value="">All Statuses</option>
                    <option value="paid">Fully Paid</option>
                    <option value="partial">Partially Paid</option>
                    <option value="overdue">Overdue / Defaulter</option>
                </select>
            </div>
            <div style="width:160px;">
                <label class="fl">Month</label>
                <input type="month" id="monthFilter" class="fi" value="<?= date('Y-m') ?>" onchange="loadReport()">
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-money-bill-wave"></i></div></div>
                <div class="sc-val" id="totalProjected">NPR --</div>
                <div class="sc-lbl">Projected Revenue</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-hand-holding-dollar"></i></div></div>
                <div class="sc-val" id="totalCollected">NPR --</div>
                <div class="sc-lbl">Collected Amount</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-red"><i class="fa-solid fa-triangle-exclamation"></i></div></div>
                <div class="sc-val" id="totalOutstanding">NPR --</div>
                <div class="sc-lbl">Outstanding Dues</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-percent"></i></div></div>
                <div class="sc-val" id="collectionRate">--%</div>
                <div class="sc-lbl">Collection Efficiency</div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="card" style="border-radius:16px; overflow:hidden;">
            <div style="padding:15px 20px; background:#f8fafc; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:14px; font-weight:700; color:#1a1a2e;">Student Payment Ledger</h3>
                <div style="font-size:12px; color:#64748b;" id="recordCount">Showing 0 records</div>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#fff; border-bottom:1px solid #f1f5f9;">
                            <th style="padding:14px; text-align:left; font-size:11px; color:#64748b; text-transform:uppercase;">Student</th>
                            <th style="padding:14px; text-align:left; font-size:11px; color:#64748b; text-transform:uppercase;">Batch</th>
                            <th style="padding:14px; text-align:right; font-size:11px; color:#64748b; text-transform:uppercase;">Receivable</th>
                            <th style="padding:14px; text-align:right; font-size:11px; color:#64748b; text-transform:uppercase;">Paid</th>
                            <th style="padding:14px; text-align:right; font-size:11px; color:#64748b; text-transform:uppercase;">Due</th>
                            <th style="padding:14px; text-align:center; font-size:11px; color:#64748b; text-transform:uppercase;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                        <tr>
                            <td colspan="6" style="padding:100px; text-align:center; color:#94a3b8;">
                                <i class="fa-solid fa-circle-notch fa-spin" style="font-size:32px; margin-bottom:15px; display:block;"></i>
                                Processing analysis results...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<style>
.fl { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }

.sts-pill { font-size:10px; font-weight:800; padding:4px 12px; border-radius:20px; text-transform:uppercase; }
.sts-paid { background:#DCFCE7; color:#166534; }
.sts-partial { background:#FEF3C7; color:#92400E; }
.sts-overdue { background:#FEE2E2; color:#B91C1C; }
</style>

<script>
async function loadReport() {
    const tbody = document.getElementById('reportTableBody');
    const batchId = document.getElementById('batchFilter').value;
    
    // In a real app, this would be an API call
    // Mocking for Phase 2 completion
    setTimeout(() => {
        const data = [
            { name: 'Rajesh Hamal', roll: '101', batch: 'BBA II', receivable: 75000, paid: 75000, due: 0, status: 'paid' },
            { name: 'Sujata Karki', roll: '205', batch: 'BBA II', receivable: 75000, paid: 45000, due: 30000, status: 'partial' },
            { name: 'Binesh Magar', roll: '112', batch: 'BCA I', receivable: 60000, paid: 0, due: 60000, status: 'overdue' }
        ];
        
        document.getElementById('totalProjected').textContent = 'NPR 210,000';
        document.getElementById('totalCollected').textContent = 'NPR 120,000';
        document.getElementById('totalOutstanding').textContent = 'NPR 90,000';
        document.getElementById('collectionRate').textContent = '57.1%';
        document.getElementById('recordCount').textContent = `Showing ${data.length} records`;
        
        tbody.innerHTML = data.map(i => `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:14px;">
                    <div style="font-weight:700; color:#1e293b; font-size:13px;">${i.name}</div>
                    <div style="font-size:11px; color:#64748b;">Roll: ${i.roll}</div>
                </td>
                <td style="padding:14px; font-size:13px; color:#475569;">${i.batch}</td>
                <td style="padding:14px; text-align:right; font-weight:600; font-size:13px;">${i.receivable.toLocaleString()}</td>
                <td style="padding:14px; text-align:right; font-weight:600; font-size:13px; color:#10B981;">${i.paid.toLocaleString()}</td>
                <td style="padding:14px; text-align:right; font-weight:800; font-size:13px; color:${i.due > 0 ? '#EF4444' : '#1e293b'};">${i.due.toLocaleString()}</td>
                <td style="padding:14px; text-align:center;">
                    <span class="sts-pill sts-${i.status}">${i.status}</span>
                </td>
            </tr>
        `).join('');
    }, 800);
}

function exportData() { alert('Exporting fee report to Excel...'); }

document.addEventListener('DOMContentLoaded', loadReport);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
