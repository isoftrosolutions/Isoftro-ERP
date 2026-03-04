<?php
/**
 * Front Desk — Attendance Reports
 * Visualization of attendance data and absent tracking
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Attendance Reports';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';

// Fetch batches for filter
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];
$stmtBatches = $db->prepare("SELECT id, name FROM batches WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('academic'); ?>

<main class="main" id="mainContent">
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #3B82F6);">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div>
                    <h1 class="pg-title">Attendance Reports</h1>
                    <p class="pg-sub">Analyze presence trends and track frequent absentees</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="loadReport()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="card mb" style="padding:16px 20px; border-radius:14px; margin-bottom:24px; display:flex; gap:12px; align-items:center;">
            <select id="batchFilter" class="fi" style="width:200px;" onchange="loadReport()">
                <option value="">All Batches</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="month" id="monthFilter" class="fi" style="width:160px;" value="<?= date('Y-m') ?>" onchange="loadReport()">
            <div style="margin-left:auto; display:flex; gap:8px;">
                <button class="btn bt" style="padding:8px 15px; font-size:13px;" onclick="exportExcel()">
                    <i class="fa-solid fa-file-excel"></i> Export
                </button>
            </div>
        </div>

        <!-- Top Stats -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-user-check"></i></div></div>
                <div class="sc-val" id="avgPresence">-%</div>
                <div class="sc-lbl">Avg. Presence (Month)</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-user-clock"></i></div></div>
                <div class="sc-val" id="totalLate">-</div>
                <div class="sc-lbl">Total Late Instances</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-red"><div style="color:#ef4444;"><i class="fa-solid fa-user-xmark"></i></div></div></div>
                <div class="sc-val" id="absentToday">-</div>
                <div class="sc-lbl">Absent Today</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
            <!-- Attendance Heatmap / Table -->
            <div class="card" style="padding:20px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-calendar-days" style="color:#3B82F6;"></i> Daily Presence Trend
                </h3>
                <div id="trendContainer" style="height:200px; display:flex; align-items:flex-end; justify-content:space-between; padding-top:20px;">
                    <!-- Trend bars -->
                    <div style="text-align:center; width:100%; color:#94a3b8;">Loading trend data...</div>
                </div>
            </div>

            <!-- Top Absentees -->
            <div class="card" style="padding:20px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-user-slash" style="color:#EF4444;"></i> Top Absentees
                </h3>
                <div id="absenteeList">
                    <div style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i></div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.fi { padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.abs-item { display:flex; justify-content:space-between; padding:12px 10px; border-bottom:1px solid #f1f5f9; }
.abs-item:last-child { border-bottom:none; }
</style>

<script>
async function loadReport() {
    const batchId = document.getElementById('batchFilter').value;
    const month = document.getElementById('monthFilter').value;
    
    // In a real app, this would fetch from API
    // Mocking summary for demo
    document.getElementById('avgPresence').textContent = '84.2%';
    document.getElementById('totalLate').textContent = '12';
    document.getElementById('absentToday').textContent = batchId ? '3' : '18';
    
    renderMockTrend();
    renderMockAbsentees();
}

function renderMockTrend() {
    const container = document.getElementById('trendContainer');
    const days = 15;
    container.innerHTML = Array.from({length: days}).map((_, i) => {
        const h = Math.floor(Math.random() * 60) + 40;
        return `
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:8px;">
                <div style="width:80%; max-width:12px; height:${h}%; background:linear-gradient(to top, #3B82F6, #60A5FA); border-radius:4px; opacity:${0.5 + (h/200)};" title="Day ${i+1}: ${h}%"></div>
                <div style="font-size:9px; color:#94a3b8; font-weight:700;">${i+1}</div>
            </div>
        `;
    }).join('');
}

function renderMockAbsentees() {
    const container = document.getElementById('absenteeList');
    const data = [
        { name: 'Sujata Karki', count: 5 },
        { name: 'Rohan Sharma', count: 4 },
        { name: 'Anjali Gurung', count: 3 },
        { name: 'Binesh Magar', count: 3 }
    ];
    
    container.innerHTML = data.map(i => `
        <div class="abs-item">
            <div>
                <div style="font-weight:600; font-size:13px; color:#1a1a2e;">${i.name}</div>
                <div style="font-size:11px; color:#64748b;">Batch: BBA II</div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:800; color:#EF4444;">${i.count}</div>
                <div style="font-size:10px; color:#94a3b8;">Days</div>
            </div>
        </div>
    `).join('');
}

function exportExcel() { alert('Exporting attendance report to Excel...'); }

document.addEventListener('DOMContentLoaded', loadReport);
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
