<?php
/**
 * Admin — Fee Reports Dashboard
 * Comprehensive financial analysis for Institute Admins
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = "Fee Reports Dashboard";
$roleCSS = "ia-dashboard-new.css";
$wrapperClass = "app-layout";
$user = getCurrentUser();
$tenantName = $_SESSION['tenant_name'] ?? 'Institute';
$activePage = 'fee-reports';

$isSPA = isset($_GET['spa']) && $_GET['spa'] === 'true';

if (!$isSPA) {
    include VIEWS_PATH . '/layouts/header.php';
    include __DIR__ . '/layouts/sidebar.php';
}

// Pre-fetch courses and batches for filters
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];

$stmtC = $db->prepare("SELECT id, name FROM courses WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtC->execute(['tid' => $tenantId]);
$courses = $stmtC->fetchAll(PDO::FETCH_ASSOC);

$stmtB = $db->prepare("SELECT id, name, course_id FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtB->execute(['tid' => $tenantId]);
$batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

$batchesJson = json_encode($batches);
?>

<?php if (!$isSPA): ?>
<div class="main">
    <?php include __DIR__ . '/layouts/header.php'; ?>

    <div class="content" id="mainContent">
<?php endif; ?>
    <div class="pg">
        <div class="pg-head" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #4f46e5, #4338ca);">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <h1 class="pg-title">Fee Reports Dashboard</h1>
                    <p class="pg-sub">Advanced financial analytics and exportable reports</p>
                </div>
            </div>
            <div class="pg-acts" style="display:flex; gap:10px;">
                <button class="btn" style="background:#e11d48; color:#fff;" onclick="exportFeeReport('pdf')">
                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn" style="background:#16a34a; color:#fff;" onclick="exportFeeReport('excel')">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- KPI Cards -->
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-bottom:24px;">
            <div class="card" style="padding:20px; border-radius:12px; border-left:4px solid #3b82f6;">
                <div style="font-size:13px; color:#64748b; font-weight:600; text-transform:uppercase;">Today's Collection</div>
                <div id="kpi-today" style="font-size:24px; font-weight:700; color:#1e293b; margin-top:8px;">NPR 0</div>
            </div>
            <div class="card" style="padding:20px; border-radius:12px; border-left:4px solid #10b981;">
                <div style="font-size:13px; color:#64748b; font-weight:600; text-transform:uppercase;">Month Collection</div>
                <div id="kpi-month" style="font-size:24px; font-weight:700; color:#1e293b; margin-top:8px;">NPR 0</div>
            </div>
            <div class="card" style="padding:20px; border-radius:12px; border-left:4px solid #ef4444;">
                <div style="font-size:13px; color:#64748b; font-weight:600; text-transform:uppercase;">Total Outstanding</div>
                <div id="kpi-outstanding" style="font-size:24px; font-weight:700; color:#1e293b; margin-top:8px;">NPR 0</div>
            </div>
            <div class="card" style="padding:20px; border-radius:12px; border-left:4px solid #f59e0b;">
                <div style="font-size:13px; color:#64748b; font-weight:600; text-transform:uppercase;">Total Defaulters</div>
                <div id="kpi-defaulters" style="font-size:24px; font-weight:700; color:#1e293b; margin-top:8px;">0</div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="card" style="padding:20px; border-radius:12px; margin-bottom:24px;">
            <div style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:180px;">
                    <label class="frm-lbl">Report Type</label>
                    <select id="reportTypeFilter" class="frm-inp" onchange="toggleReportFilters()">
                        <option value="collection">Detailed Collection</option>
                        <option value="batch_summary">Batch Summary</option>
                        <option value="discount">Discount & Waivers</option>
                    </select>
                </div>
                <div style="flex:1; min-width:150px;" class="filter-dt">
                    <label class="frm-lbl">Start Date</label>
                    <input type="date" id="startDateFilter" class="frm-inp" value="<?= date('Y-m-01') ?>">
                </div>
                <div style="flex:1; min-width:150px;" class="filter-dt">
                    <label class="frm-lbl">End Date</label>
                    <input type="date" id="endDateFilter" class="frm-inp" value="<?= date('Y-m-t') ?>">
                </div>
                <div style="flex:1; min-width:150px;" class="filter-cb">
                    <label class="frm-lbl">Course</label>
                    <select id="courseFilter" class="frm-inp" onchange="filterBatches()">
                        <option value="">All Courses</option>
                        <?php foreach($courses as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:1; min-width:150px;" class="filter-cb">
                    <label class="frm-lbl">Batch</label>
                    <select id="batchFilter" class="frm-inp">
                        <option value="">All Batches</option>
                    </select>
                </div>
                <div style="flex:1; min-width:150px;" class="filter-pm">
                    <label class="frm-lbl">Payment Method</label>
                    <select id="paymentMethodFilter" class="frm-inp">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="esewa">eSewa</option>
                        <option value="khalti">Khalti</option>
                    </select>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="loadFeeReportData()" style="height:42px; padding:0 24px;">
                        <i class="fa-solid fa-filter"></i> Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Render Container -->
        <div class="card" style="border-radius:12px; overflow:hidden;" id="reportRenderArea">
            <div style="padding:60px 20px; text-align:center; color:#64748b;">
                <i class="fa-solid fa-table" style="font-size:48px; margin-bottom:16px; opacity:0.3;"></i>
                <h3 style="font-size:18px; color:#1e293b; margin-bottom:8px;">Select filters and generate report</h3>
                <p>Detailed data will appear here.</p>
            </div>
        </div>
    </div>
<?php if (!$isSPA): ?>
    </div>
</div>
<?php endif; ?>

<style>
.frm-lbl { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; }
.frm-inp { width:100%; padding:10px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; color:#1e293b; }
.report-tbl { width:100%; border-collapse:collapse; }
.report-tbl th { background:#f8fafc; padding:12px 16px; text-align:left; font-size:12px; font-weight:700; color:#475569; border-bottom:1px solid #e2e8f0; }
.report-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; }
.report-tbl tbody tr:hover { background:#f8fafc; }
</style>

<script>
const allBatches = <?= $batchesJson ?>;

function filterBatches() {
    const courseId = document.getElementById('courseFilter').value;
    const batchSel = document.getElementById('batchFilter');
    batchSel.innerHTML = '<option value="">All Batches</option>';
    
    allBatches.forEach(b => {
        if(!courseId || b.course_id == courseId) {
            batchSel.innerHTML += `<option value="${b.id}">${b.name}</option>`;
        }
    });
}

function toggleReportFilters() {
    const type = document.getElementById('reportTypeFilter').value;
    const isColl = type === 'collection';
    
    document.querySelectorAll('.filter-dt, .filter-pm').forEach(el => {
        el.style.display = isColl || type === 'discount' ? 'block' : 'none';
    });
}

async function loadFeeReportData() {
    document.getElementById('reportRenderArea').innerHTML = `
        <div style="padding:60px; text-align:center; color:#64748b;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:32px; margin-bottom:16px;"></i>
            <p>Loading report data...</p>
        </div>
    `;

    const type = document.getElementById('reportTypeFilter').value;
    const start = document.getElementById('startDateFilter').value;
    const end = document.getElementById('endDateFilter').value;
    const batchId = document.getElementById('batchFilter').value;
    const method = document.getElementById('paymentMethodFilter').value;
    
    // First update KPIs
    try {
        const kpiRes = await fetch(`${APP_URL}/api/admin/fee-reports?action=summary`);
        const kpiData = await kpiRes.json();
        if(kpiData.success) {
            document.getElementById('kpi-today').textContent = 'NPR ' + Number(kpiData.data.today_collection).toLocaleString();
            document.getElementById('kpi-month').textContent = 'NPR ' + Number(kpiData.data.month_collection).toLocaleString();
            document.getElementById('kpi-outstanding').textContent = 'NPR ' + Number(kpiData.data.total_outstanding).toLocaleString();
            document.getElementById('kpi-defaulters').textContent = kpiData.data.defaulter_count;
        }
    } catch(e) { console.error('KPI Load Error', e); }

    // Then load the selected report
    try {
        let qs = `action=${type === 'collection' ? 'detailed_collection' : type}`;
        if(type === 'collection' || type === 'discount') {
            qs += `&start=${start}&end=${end}`;
            if(method) qs += `&payment_method=${method}`;
        }
        if(batchId) qs += `&batch_id=${batchId}`;

        const res = await fetch(`${APP_URL}/api/admin/fee-reports?${qs}`);
        const data = await res.json();
        
        if(!data.success) throw new Error(data.message || 'Report fetch failed');
        renderReportTable(type, data.data);
    } catch(e) {
        document.getElementById('reportRenderArea').innerHTML = `
            <div style="padding:40px; text-align:center; color:#ef4444;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:32px; margin-bottom:16px;"></i>
                <p>Failed to load report: ${e.message}</p>
            </div>
        `;
    }
}

function renderReportTable(type, data) {
    if(!data || data.length === 0) {
        document.getElementById('reportRenderArea').innerHTML = `
            <div style="padding:60px; text-align:center; color:#64748b;">
                <p>No records found for the selected filters.</p>
            </div>
        `;
        return;
    }

    let html = `<div style="overflow-x:auto;"><table class="report-tbl"><thead><tr>`;
    
    if(type === 'collection') {
        html += `
            <th>Receipt</th>
            <th>Date</th>
            <th>Student</th>
            <th>Batch</th>
            <th>Method</th>
            <th style="text-align:right;">Amount (NPR)</th>
        </tr></thead><tbody>`;
        let total = 0;
        data.forEach(r => {
            total += parseFloat(r.amount);
            html += `<tr>
                <td style="font-weight:600;">${r.receipt_number}</td>
                <td>${r.payment_date}</td>
                <td>
                    <div style="font-weight:600;">${r.student_name}</div>
                    <div style="font-size:11px; color:#64748b;">${r.roll_no || '-'}</div>
                </td>
                <td>${r.batch_name || '-'}</td>
                <td><span style="background:#e2e8f0; padding:3px 8px; border-radius:12px; font-size:11px; text-transform:uppercase; font-weight:700;">${r.payment_method}</span></td>
                <td style="text-align:right; font-weight:700; color:#10b981;">${Number(r.amount).toLocaleString()}</td>
            </tr>`;
        });
        html += `<tr style="background:#f8fafc;"><td colspan="5" style="text-align:right; font-weight:700;">Total Collection</td><td style="text-align:right; font-weight:800; color:#1e293b; font-size:15px;">${total.toLocaleString()}</td></tr>`;
    } 
    else if(type === 'batch_summary') {
        html += `
            <th>Course</th>
            <th>Batch</th>
            <th style="text-align:right;">Total Due</th>
            <th style="text-align:right;">Total Paid</th>
            <th style="text-align:right;">Outstanding</th>
            <th style="text-align:center;">Collection Rate</th>
        </tr></thead><tbody>`;
        let tDue = 0, tPaid = 0, tOut = 0;
        data.forEach(r => {
            tDue += parseFloat(r.total_due);
            tPaid += parseFloat(r.total_paid);
            tOut += parseFloat(r.outstanding_amount);
            let rate = r.total_due > 0 ? ((r.total_paid / r.total_due) * 100).toFixed(1) : 0;
            html += `<tr>
                <td>${r.course_name}</td>
                <td style="font-weight:600;">${r.batch_name}</td>
                <td style="text-align:right;">${Number(r.total_due).toLocaleString()}</td>
                <td style="text-align:right; color:#10b981; font-weight:600;">${Number(r.total_paid).toLocaleString()}</td>
                <td style="text-align:right; color:#ef4444; font-weight:600;">${Number(r.outstanding_amount).toLocaleString()}</td>
                <td>
                    <div style="display:flex; align-items:center; gap:8px; justify-content:center;">
                        <div style="width:60px; height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                            <div style="height:100%; background:#3b82f6; width:${rate}%;"></div>
                        </div>
                        <span style="font-size:12px; font-weight:600;">${rate}%</span>
                    </div>
                </td>
            </tr>`;
        });
        html += `<tr style="background:#f8fafc;">
            <td colspan="2" style="text-align:right; font-weight:700;">Overall Status</td>
            <td style="text-align:right; font-weight:800;">${tDue.toLocaleString()}</td>
            <td style="text-align:right; font-weight:800; color:#10b981;">${tPaid.toLocaleString()}</td>
            <td style="text-align:right; font-weight:800; color:#ef4444;">${tOut.toLocaleString()}</td>
            <td></td>
        </tr>`;
    }
    else if(type === 'discount') {
        html += `
            <th>Date</th>
            <th>Student</th>
            <th>Fee Item</th>
            <th style="text-align:right;">Waived Amount</th>
            <th>Notes</th>
        </tr></thead><tbody>`;
        data.forEach(r => {
            html += `<tr>
                <td>${r.due_date}</td>
                <td>
                    <div style="font-weight:600;">${r.student_name}</div>
                    <div style="font-size:11px; color:#64748b;">${r.roll_no || '-'}</div>
                </td>
                <td>${r.fee_name}</td>
                <td style="text-align:right; font-weight:600; color:#f59e0b;">${Number(r.fine_waived).toLocaleString()}</td>
                <td><span style="font-size:12px; color:#64748b;">${r.notes || '-'}</span></td>
            </tr>`;
        });
    }

    html += `</tbody></table></div>`;
    document.getElementById('reportRenderArea').innerHTML = html;
}

function exportFeeReport(format) {
    const type = document.getElementById('reportTypeFilter').value;
    const start = document.getElementById('startDateFilter').value;
    const end = document.getElementById('endDateFilter').value;
    
    if (type !== 'collection') {
        alert("Export is currently only supported for 'Detailed Collection' report.");
        return;
    }

    const qs = `action=export_${format}&report_type=${type}&start=${start}&end=${end}`;
    window.open(`${APP_URL}/api/admin/fee-reports?${qs}`, '_blank');
}

document.addEventListener('DOMContentLoaded', () => {
    filterBatches();
    toggleReportFilters();
    loadFeeReportData();
});
</script>

<?php if (!$isSPA): ?>
<?php $v = time(); ?>
<script src="<?php echo APP_URL; ?>/assets/js/pwa-handler.js?v=<?php echo $v; ?>"></script>
</body>
</html>
<?php endif; ?>
