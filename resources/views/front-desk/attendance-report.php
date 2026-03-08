<?php
/**
 * Front Desk — Attendance Reports
 * Mobile-first analytics and reporting for attendance data
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Attendance Reports';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}

// Fetch batches for filter
$db = getDBConnection();
$tenantId = $_SESSION['userData']['tenant_id'];
$stmtBatches = $db->prepare("SELECT id, name FROM batches WHERE tenant_id = :tid AND status IN ('active', 'completed') AND deleted_at IS NULL ORDER BY name");
$stmtBatches->execute(['tid' => $tenantId]);
$batches = $stmtBatches->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('academic');
}
?>

<div class="pg rpt-page">
    <!-- Page Header -->
    <div class="rpt-header">
        <div class="rpt-header-left">
            <div class="rpt-header-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <h1 class="rpt-header-title">Attendance Reports</h1>
                <p class="rpt-header-sub">Analyze presence trends & track frequent absentees</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rpt-filters">
        <div class="rpt-filter-row">
            <div class="rpt-filter-group">
                <label class="rpt-label"><i class="fa-solid fa-users-rectangle"></i> Batch</label>
                <div class="rpt-select-wrap">
                    <select id="batchFilter" class="rpt-select">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down rpt-select-icon"></i>
                </div>
            </div>
            <div class="rpt-filter-group">
                <label class="rpt-label"><i class="fa-regular fa-calendar"></i> From</label>
                <input type="date" id="startDate" class="rpt-input" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="rpt-filter-group">
                <label class="rpt-label"><i class="fa-regular fa-calendar-check"></i> To</label>
                <input type="date" id="endDate" class="rpt-input" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="rpt-filter-actions">
            <button class="rpt-btn rpt-btn-primary" onclick="loadReport()">
                <i class="fa-solid fa-chart-bar"></i> Analyze
            </button>
            <button class="rpt-btn rpt-btn-outline" onclick="exportCSV()">
                <i class="fa-solid fa-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Summary Stat Cards -->
    <div class="rpt-stats">
        <div class="rpt-stat rpt-stat-green">
            <div class="rpt-stat-ico"><i class="fa-solid fa-percentage"></i></div>
            <div class="rpt-stat-body">
                <div class="rpt-stat-val" id="statPct">—</div>
                <div class="rpt-stat-lbl">Presence Rate</div>
            </div>
        </div>
        <div class="rpt-stat rpt-stat-blue">
            <div class="rpt-stat-ico rpt-ico-blue"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="rpt-stat-body">
                <div class="rpt-stat-val" id="statTotal">—</div>
                <div class="rpt-stat-lbl">Total Records</div>
            </div>
        </div>
        <div class="rpt-stat rpt-stat-amber">
            <div class="rpt-stat-ico rpt-ico-amber"><i class="fa-solid fa-clock"></i></div>
            <div class="rpt-stat-body">
                <div class="rpt-stat-val" id="statLate">—</div>
                <div class="rpt-stat-lbl">Late Instances</div>
            </div>
        </div>
        <div class="rpt-stat rpt-stat-red">
            <div class="rpt-stat-ico rpt-ico-red"><i class="fa-solid fa-user-xmark"></i></div>
            <div class="rpt-stat-body">
                <div class="rpt-stat-val" id="statAbsToday">—</div>
                <div class="rpt-stat-lbl">Absent Today</div>
            </div>
        </div>
    </div>

    <!-- Trend + Absentees Grid -->
    <div class="rpt-grid">
        <!-- Daily Trend Chart -->
        <div class="rpt-card">
            <div class="rpt-card-head">
                <div class="rpt-card-title"><i class="fa-solid fa-chart-column" style="color:#3b82f6;"></i> Daily Trend</div>
                <div class="rpt-legend">
                    <span class="rpt-legend-item"><span class="rpt-dot" style="background:#10b981;"></span>Present</span>
                    <span class="rpt-legend-item"><span class="rpt-dot" style="background:#f59e0b;"></span>Late</span>
                    <span class="rpt-legend-item"><span class="rpt-dot" style="background:#ef4444;"></span>Absent</span>
                </div>
            </div>
            <div class="rpt-card-body">
                <div id="trendContainer" class="rpt-chart">
                    <div class="rpt-chart-empty"><i class="fa-solid fa-spinner fa-spin"></i></div>
                </div>
            </div>
        </div>

        <!-- Top Absentees -->
        <div class="rpt-card">
            <div class="rpt-card-head">
                <div class="rpt-card-title"><i class="fa-solid fa-user-slash" style="color:#ef4444;"></i> Top Absentees</div>
            </div>
            <div class="rpt-card-body rpt-scroll" id="absenteeList">
                <div class="rpt-chart-empty"><i class="fa-solid fa-spinner fa-spin"></i></div>
            </div>
        </div>
    </div>

    <!-- Batch Comparison -->
    <div class="rpt-card rpt-mb">
        <div class="rpt-card-head">
            <div class="rpt-card-title"><i class="fa-solid fa-layer-group" style="color:var(--green);"></i> Batch Comparison</div>
        </div>
        <div class="rpt-card-body" id="batchCompare">
            <div class="rpt-chart-empty"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="rptToast" class="rpt-toast"></div>

<style>
/* ═══════════════════════════════════════
   ATTENDANCE REPORTS — MOBILE-FIRST UI
   ═══════════════════════════════════════ */

/* Page header */
.rpt-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
}
.rpt-header-left { display: flex; align-items: center; gap: 12px; }
.rpt-header-icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 20px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    flex-shrink: 0;
}
.rpt-header-title { font-size: 1.25rem; font-weight: 800; color: var(--text-dark); margin: 0; }
.rpt-header-sub { font-size: 12px; color: var(--text-light); margin: 2px 0 0; }

/* Filters */
.rpt-filters {
    background: #fff; border-radius: 16px; border: 1px solid var(--card-border);
    padding: 16px; margin-bottom: 20px; box-shadow: var(--shadow);
}
.rpt-filter-row {
    display: grid; grid-template-columns: 1fr; gap: 12px;
}
.rpt-filter-group { display: flex; flex-direction: column; }
.rpt-label {
    font-size: 11px; font-weight: 700; color: var(--text-light);
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
    display: flex; align-items: center; gap: 5px;
}
.rpt-label i { font-size: 10px; }
.rpt-select-wrap { position: relative; }
.rpt-select {
    width: 100%; padding: 11px 36px 11px 14px;
    border: 1.5px solid var(--card-border); border-radius: 10px;
    font-size: 14px; font-family: var(--font);
    background: #fff; color: var(--text-dark);
    outline: none; appearance: none; cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.rpt-select:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1); }
.rpt-select-icon {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    font-size: 11px; color: var(--text-light); pointer-events: none;
}
.rpt-input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid var(--card-border); border-radius: 10px;
    font-size: 14px; font-family: var(--font);
    background: #fff; color: var(--text-dark);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.rpt-input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1); }
.rpt-filter-actions {
    display: flex; gap: 8px; margin-top: 12px;
}
.rpt-btn {
    flex: 1; padding: 11px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 700; font-family: var(--font);
    cursor: pointer; border: none;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: all 0.2s;
}
.rpt-btn-primary {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    color: #fff;
    box-shadow: 0 4px 12px rgba(26, 26, 46, 0.2);
}
.rpt-btn-primary:hover { transform: translateY(-1px); }
.rpt-btn-outline {
    background: #fff; color: var(--text-body);
    border: 1.5px solid var(--card-border);
}
.rpt-btn-outline:hover { border-color: var(--green); color: var(--green); }

/* Stat Cards */
.rpt-stats {
    display: grid; grid-template-columns: repeat(2, 1fr);
    gap: 12px; margin-bottom: 20px;
}
.rpt-stat {
    background: #fff; border-radius: 14px; padding: 16px;
    border: 1px solid var(--card-border);
    display: flex; align-items: center; gap: 12px;
    transition: transform 0.2s, box-shadow 0.2s;
    overflow: hidden; position: relative;
}
.rpt-stat:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.rpt-stat-ico {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.rpt-stat-green .rpt-stat-ico { background: #dcfce7; color: #16a34a; }
.rpt-stat-blue .rpt-stat-ico { background: #dbeafe; color: #2563eb; }
.rpt-stat-amber .rpt-stat-ico { background: #fef3c7; color: #d97706; }
.rpt-stat-red .rpt-stat-ico { background: #fee2e2; color: #dc2626; }
.rpt-stat-val { font-size: 1.3rem; font-weight: 800; color: var(--text-dark); line-height: 1; }
.rpt-stat-lbl { font-size: 10px; color: var(--text-light); font-weight: 600; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.3px; }

/* Cards */
.rpt-grid {
    display: grid; grid-template-columns: 1fr;
    gap: 16px; margin-bottom: 16px;
}
.rpt-card {
    background: #fff; border-radius: 14px;
    border: 1px solid var(--card-border); overflow: hidden;
}
.rpt-card-head {
    padding: 14px 16px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
}
.rpt-card-title {
    font-size: 13px; font-weight: 700; color: var(--text-dark);
    display: flex; align-items: center; gap: 8px;
}
.rpt-card-body { padding: 16px; }
.rpt-scroll { max-height: 280px; overflow-y: auto; }
.rpt-mb { margin-bottom: 24px; }

/* Legend */
.rpt-legend { display: flex; gap: 10px; }
.rpt-legend-item { display: flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 600; color: var(--text-body); }
.rpt-dot { width: 8px; height: 8px; border-radius: 3px; }

/* Chart container */
.rpt-chart {
    display: flex; align-items: flex-end; gap: 3px; height: 160px;
}
.rpt-chart-empty {
    width: 100%; height: 160px;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-light); font-size: 13px;
}
.rpt-bar-wrap {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.rpt-bar-tooltip {
    display: none; position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%); background: #1e293b; color: #fff;
    padding: 4px 8px; border-radius: 6px; font-size: 9px;
    white-space: nowrap; font-weight: 600; z-index: 10;
}
.rpt-bar-tooltip::after {
    content: ''; position: absolute; top: 100%; left: 50%;
    transform: translateX(-50%); border: 3px solid transparent; border-top-color: #1e293b;
}

/* Absentee Item */
.rpt-abs-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0; border-bottom: 1px solid #f8fafc;
}
.rpt-abs-item:last-child { border-bottom: none; }
.rpt-abs-rank {
    width: 20px; height: 20px; border-radius: 50%;
    background: #fee2e2; color: #dc2626;
    font-size: 9px; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rpt-abs-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    object-fit: cover; border: 2px solid #f1f5f9; flex-shrink: 0;
}
.rpt-abs-info { flex: 1; min-width: 0; }
.rpt-abs-name { font-size: 12px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rpt-abs-batch { font-size: 9px; color: var(--text-light); }
.rpt-abs-count { text-align: right; flex-shrink: 0; }
.rpt-abs-num { font-size: 16px; font-weight: 800; color: #dc2626; line-height: 1; }
.rpt-abs-days { font-size: 8px; color: var(--text-light); text-transform: uppercase; font-weight: 700; }

/* Batch bars */
.rpt-batch-row {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0; border-bottom: 1px solid #f8fafc;
}
.rpt-batch-row:last-child { border-bottom: none; }
.rpt-batch-name { font-size: 12px; font-weight: 700; color: var(--text-dark); min-width: 80px; flex-shrink: 0; }
.rpt-batch-bar {
    flex: 1; display: flex; gap: 1px; height: 16px;
    border-radius: 4px; overflow: hidden; background: #f8fafc;
}
.rpt-batch-seg {
    height: 100%; transition: width 0.5s ease; position: relative; cursor: pointer;
}
.rpt-batch-seg:hover { opacity: 0.85; }
.rpt-batch-seg .rpt-bar-tooltip { bottom: calc(100% + 4px); }
.rpt-batch-seg:hover .rpt-bar-tooltip { display: block; }
.rpt-batch-pct {
    font-size: 11px; font-weight: 700; min-width: 40px; text-align: right;
}

/* Toast */
.rpt-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(60px);
    background: #1e293b; color: #fff;
    padding: 10px 20px; border-radius: 10px;
    font-size: 12px; font-weight: 600; font-family: var(--font);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    opacity: 0; visibility: hidden; transition: all 0.3s ease;
    z-index: 200; display: flex; align-items: center; gap: 8px;
}
.rpt-toast.show { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }
.rpt-toast.success { background: #059669; }
.rpt-toast.error { background: #dc2626; }

/* ── Responsive ── */
@media (min-width: 640px) {
    .rpt-filter-row { grid-template-columns: 1fr 1fr 1fr; }
    .rpt-filter-actions { margin-top: 12px; }
    .rpt-btn { flex: 0 0 auto; }
    .rpt-stats { grid-template-columns: repeat(4, 1fr); }
}
@media (min-width: 1024px) {
    .rpt-grid { grid-template-columns: 5fr 3fr; }
    .rpt-chart { height: 200px; }
    .rpt-card-body { padding: 20px; }
}
</style>

<script>
const RPT_API = '<?= APP_URL ?>';
const RPT_DEFAULT_AVATAR = '<?= APP_URL ?>/public/assets/images/default-avatar.png';

async function loadReport() {
    const batchId = document.getElementById('batchFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    let url = `${RPT_API}/api/frontdesk/attendance?action=report&start_date=${startDate}&end_date=${endDate}`;
    if (batchId) url += `&batch_id=${batchId}`;

    try {
        const res = await fetch(url);
        const result = await res.json();
        if (!result.success || !result.data) {
            showRptToast('Failed to load report data', 'error');
            return;
        }
        const d = result.data;

        // Stat cards
        const total = parseInt(d.summary?.total_records || 0);
        const present = parseInt(d.summary?.present || 0);
        const late = parseInt(d.summary?.late || 0);
        const absent = parseInt(d.summary?.absent || 0);
        const pct = total > 0 ? (((present + late) / total) * 100).toFixed(1) : '0.0';

        animateVal('statPct', pct, '%');
        animateVal('statTotal', total);
        animateVal('statLate', late);
        animateVal('statAbsToday', d.absent_today || 0);

        // Trend
        renderTrend(d.trend || []);

        // Top absentees
        renderAbsentees(d.top_absentees || []);

        // Batch comparison
        renderBatches(d.batch_stats || []);

    } catch (e) {
        showRptToast('Network error loading report', 'error');
    }
}

function animateVal(id, target, suffix = '') {
    const el = document.getElementById(id);
    if (!el) return;
    const t = parseFloat(target) || 0;
    const dur = 500;
    const start = performance.now();
    function tick(now) {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const cur = Math.round(eased * t);
        el.textContent = suffix === '%' ? cur + '%' : cur.toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = suffix === '%' ? parseFloat(target).toFixed(1) + '%' : parseInt(t).toLocaleString();
    }
    requestAnimationFrame(tick);
}

function renderTrend(trend) {
    const c = document.getElementById('trendContainer');
    if (!c) return;
    if (trend.length === 0) {
        c.innerHTML = '<div class="rpt-chart-empty"><i class="fa-solid fa-chart-column" style="margin-right:6px; opacity:0.4;"></i>No data for this period</div>';
        return;
    }
    const maxT = Math.max(...trend.map(t => parseInt(t.total) || 1));
    c.innerHTML = trend.map((t, i) => {
        const total = parseInt(t.total) || 1;
        const pr = parseInt(t.present) || 0;
        const la = parseInt(t.late) || 0;
        const ab = parseInt(t.absent) || 0;
        const pct = ((pr + la) / total * 100).toFixed(0);
        const h = (total / maxT * 100).toFixed(0);
        const day = t.attendance_date.split('-')[2];
        const abH = (ab / total * h).toFixed(0);
        const laH = (la / total * h).toFixed(0);
        const prH = h - abH - laH;
        const col = pct >= 80 ? '#10b981' : pct >= 60 ? '#f59e0b' : '#ef4444';
        return `<div class="rpt-bar-wrap">
            <div style="width:100%; max-width:16px; height:${h}%; display:flex; flex-direction:column; border-radius:3px 3px 0 0; overflow:hidden; position:relative; cursor:pointer;">
                <div style="flex:${abH}; background:#fee2e2; min-height:${ab > 0 ? '2px' : '0'};"></div>
                <div style="flex:${laH}; background:#fef3c7; min-height:${la > 0 ? '2px' : '0'};"></div>
                <div style="flex:${prH}; background:${col}; min-height:2px;"></div>
                <div class="rpt-bar-tooltip">${t.attendance_date}<br>P:${pr} L:${la} A:${ab}</div>
            </div>
            <div style="font-size:8px; color:var(--text-light); font-weight:700;">${parseInt(day)}</div>
        </div>`;
    }).join('');
}

function renderAbsentees(list) {
    const c = document.getElementById('absenteeList');
    if (!c) return;
    if (list.length === 0) {
        c.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-light);"><i class="fa-solid fa-face-smile" style="font-size:24px; display:block; margin-bottom:8px; color:#10b981;"></i><p style="font-size:12px;">No absentees — excellent!</p></div>';
        return;
    }
    c.innerHTML = list.map((a, i) => {
        const absDays = parseInt(a.absent_days) || 0;
        const totalDays = parseInt(a.total_days) || 1;
        const pct = ((absDays / totalDays) * 100).toFixed(0);
        return `<div class="rpt-abs-item">
            <div class="rpt-abs-rank">${i + 1}</div>
            <img class="rpt-abs-avatar" src="${a.photo_url || RPT_DEFAULT_AVATAR}" onerror="this.src='${RPT_DEFAULT_AVATAR}'">
            <div class="rpt-abs-info">
                <div class="rpt-abs-name">${a.full_name}</div>
                <div class="rpt-abs-batch">${a.batch_name || 'N/A'} · #${a.roll_no || '-'}</div>
            </div>
            <div class="rpt-abs-count">
                <div class="rpt-abs-num">${absDays}</div>
                <div class="rpt-abs-days">${pct}% absent</div>
            </div>
        </div>`;
    }).join('');
}

function renderBatches(stats) {
    const c = document.getElementById('batchCompare');
    if (!c) return;
    if (stats.length === 0) {
        c.innerHTML = '<div style="text-align:center; padding:30px; color:var(--text-light);"><i class="fa-solid fa-layer-group" style="font-size:24px; display:block; margin-bottom:8px; opacity:0.4;"></i>No batch data</div>';
        return;
    }
    c.innerHTML = stats.map(b => {
        const total = parseInt(b.total) || 1;
        const pr = parseInt(b.present) || 0;
        const la = parseInt(b.late) || 0;
        const ab = parseInt(b.absent) || 0;
        const pP = (pr / total * 100).toFixed(1);
        const pL = (la / total * 100).toFixed(1);
        const pA = (ab / total * 100).toFixed(1);
        const overall = ((pr + la) / total * 100).toFixed(1);
        const col = overall >= 80 ? '#16a34a' : overall >= 60 ? '#d97706' : '#dc2626';
        return `<div class="rpt-batch-row">
            <div class="rpt-batch-name">${b.batch_name || 'Batch'}</div>
            <div class="rpt-batch-bar">
                <div class="rpt-batch-seg" style="width:${pP}%; background:#10b981;"><div class="rpt-bar-tooltip">Present: ${pr} (${pP}%)</div></div>
                <div class="rpt-batch-seg" style="width:${pL}%; background:#f59e0b;"><div class="rpt-bar-tooltip">Late: ${la} (${pL}%)</div></div>
                <div class="rpt-batch-seg" style="width:${pA}%; background:#ef4444;"><div class="rpt-bar-tooltip">Absent: ${ab} (${pA}%)</div></div>
            </div>
            <div class="rpt-batch-pct" style="color:${col};">${overall}%</div>
        </div>`;
    }).join('');
}

async function exportCSV() {
    const batchId = document.getElementById('batchFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    let url = `${RPT_API}/api/frontdesk/attendance?action=export&start_date=${startDate}&end_date=${endDate}`;
    if (batchId) url += `&batch_id=${batchId}`;

    try {
        const res = await fetch(url);
        const result = await res.json();
        if (!result.success || !result.data?.length) {
            showRptToast('No data to export', 'error');
            return;
        }
        const headers = ['Date', 'Name', 'Roll No', 'Batch', 'Status'];
        const rows = result.data.map(r => [r.attendance_date, `"${r.full_name}"`, r.roll_no, `"${r.batch_name || ''}"`, r.status]);
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `attendance_${startDate}_to_${endDate}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
        showRptToast('CSV downloaded!', 'success');
    } catch (e) {
        showRptToast('Export failed', 'error');
    }
}

function showRptToast(msg, type = '') {
    const t = document.getElementById('rptToast');
    if (!t) return;
    t.className = 'rpt-toast ' + type;
    t.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> ${msg}`;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

document.addEventListener('DOMContentLoaded', loadReport);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
