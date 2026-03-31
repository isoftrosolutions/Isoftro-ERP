<?php
/**
 * Front Desk — Revenue Report
 * Analytics and trends for institute income
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Revenue Report';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('reports');
}
?>
    <div class="pg">
        <!-- Breadcrumbs -->
        <div class="bc">
            <a href="<?= APP_URL ?>/dash/front-desk/index">Dashboard</a>
            <span class="bc-sep">/</span>
            <span class="bc-cur">Revenue Analytics</span>
        </div>

        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <h1 class="pg-title">Revenue Analytics</h1>
                    <p class="pg-sub">Detailed breakdown of income streams and growth trends</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="exportExcel()"><i class="fa-solid fa-file-excel"></i> Export CSV</button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb" style="padding:16px 20px; border-radius:14px; margin-bottom:24px; display:flex; gap:12px; align-items:center;">
            <select class="fi" style="width:160px;">
                <option>Monthly View</option>
                <option>Quarterly View</option>
                <option>Yearly View</option>
            </select>
            <input type="month" class="fi" style="width:160px;" value="<?= date('Y-m') ?>">
            <div style="margin-left:auto; font-size:12px; font-weight:700; color:#64748b;">
                Showing data for: <span style="color:#1e293b;" id="periodLabel"><?= date('F Y') ?></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
            <!-- Monthly Trend -->
            <div class="card" style="padding:24px; border-radius:16px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:24px;">Revenue Growth Trend</h3>
                <div id="revenueTrendChart" style="height:250px; display:flex; align-items:flex-end; justify-content:space-between; padding-bottom:30px; position:relative;">
                    <!-- Axis grid -->
                    <div style="position:absolute; bottom:30px; left:0; right:0; border-bottom:1px solid #f1f5f9;"></div>
                    <div style="position:absolute; top:0; left:0; right:0; border-bottom:1px dashed #f1f5f9; opacity:0.5;"></div>
                    
                    <!-- Trend Bars -->
                    <?php for($i=1; $i<=12; $i++): 
                        $h = rand(30, 90); ?>
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:10px; z-index:1;">
                            <div style="width:14px; height:<?= $h ?>%; background:linear-gradient(to top, #10B981, #34D399); border-radius:4px; opacity:<?= 0.3 + ($h/100) ?>;" title="Month <?= $i ?>: NPR <?= $h * 1000 ?>"></div>
                            <div style="font-size:9px; color:#94a3b8; font-weight:700;"><?= date('M', mktime(0,0,0,$i,1)) ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Revenue Source -->
            <div class="card" style="padding:24px; border-radius:16px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:24px;">Income Source</h3>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">Admission Fees</span>
                            <span style="font-weight:800; color:#1e293b;">65%</span>
                        </div>
                        <div style="height:6px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:65%; height:100%; background:#10B981;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">Monthly Tuition</span>
                            <span style="font-weight:800; color:#1e293b;">22%</span>
                        </div>
                        <div style="height:6px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:22%; height:100%; background:#3B82F6;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">Library & Exam</span>
                            <span style="font-weight:800; color:#1e293b;">8%</span>
                        </div>
                        <div style="height:6px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:8%; height:100%; background:#F59E0B;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">Other Charges</span>
                            <span style="font-weight:800; color:#1e293b;">5%</span>
                        </div>
                        <div style="height:6px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:5%; height:100%; background:#64748b;"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:40px; padding:20px; background:#F0FDF4; border-radius:12px; text-align:center;">
                    <div style="font-size:11px; font-weight:700; color:#166534; text-transform:uppercase;">Net Revenue This Period</div>
                    <div style="font-size:24px; font-weight:900; color:#14532D; margin-top:5px;">NPR 845,200.00</div>
                </div>
            </div>
        </div>
    </div>
<style>
.fi { padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
</style>

<script>
function exportExcel() { alert('Generating Excel report...'); }
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
