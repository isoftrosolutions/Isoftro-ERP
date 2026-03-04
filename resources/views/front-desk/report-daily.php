<?php
/**
 * Front Desk — Daily Operations Report
 * Aggregated summary of daily activities
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Daily Operations Report';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';

}
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
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
            <span class="bc-cur">Daily Operations Report</span>
        </div>

        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #3B82F6);">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h1 class="pg-title">Daily Operations Report</h1>
                    <p class="pg-sub">Consolidated summary of activities for <?= date('d M, Y', strtotime($date)) ?></p>
                </div>
            </div>
            <div class="pg-acts">
                <input type="date" value="<?= $date ?>" class="fi" style="width:160px; margin-right:10px;" onchange="window.location.href='?date='+this.value">
                <button class="btn bt" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Report</button>
            </div>
        </div>

        <!-- Quick Summary Cards -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-sack-dollar"></i></div></div>
                <div class="sc-val" id="totalCollection">NPR 45,200</div>
                <div class="sc-lbl">Total Collection</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-user-plus"></i></div></div>
                <div class="sc-val" id="newAdmissions">8</div>
                <div class="sc-lbl">New Admissions</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-headset"></i></div></div>
                <div class="sc-val" id="newInquiries">15</div>
                <div class="sc-lbl">New Inquiries</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-red"><i class="fa-solid fa-file-circle-exclamation"></i></div></div>
                <div class="sc-val" id="followupsDone">12</div>
                <div class="sc-lbl">Follow-ups Done</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <!-- Collection Breakdown -->
            <div class="card" style="padding:24px; border-radius:16px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-money-bill-transfer" style="color:#10B981;"></i> Collection Breakdown
                </h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; justify-content:space-between; padding:12px; background:#F8FAFC; border-radius:10px;">
                        <span style="font-weight:600; color:#475569;">Cash Payments</span>
                        <span style="font-weight:800; color:#1e293b;">NPR 12,000</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; background:#F8FAFC; border-radius:10px;">
                        <span style="font-weight:600; color:#475569;">Digital (eSewa/Khalti)</span>
                        <span style="font-weight:800; color:#1e293b;">NPR 25,500</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:12px; background:#F8FAFC; border-radius:10px;">
                        <span style="font-weight:600; color:#475569;">Bank Transfer / Cheque</span>
                        <span style="font-weight:800; color:#1e293b;">NPR 7,700</span>
                    </div>
                </div>
            </div>

            <!-- Admission Details -->
            <div class="card" style="padding:24px; border-radius:16px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-user-graduate" style="color:#3B82F6;"></i> Recent Admissions
                </h3>
                <div style="overflow-y:auto; max-height:200px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="font-size:11px; color:#94a3b8; text-transform:uppercase; border-bottom:1px solid #f1f5f9;">
                                <th style="padding:8px; text-align:left;">Student</th>
                                <th style="padding:8px; text-align:left;">Course</th>
                                <th style="padding:8px; text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom:1px solid #f1f5f9; font-size:13px;">
                                <td style="padding:10px;">Rajesh Hamal</td>
                                <td style="padding:10px;">BBA I</td>
                                <td style="padding:10px; text-align:right; font-weight:700;">5,500</td>
                            </tr>
                            <tr style="border-bottom:1px solid #f1f5f9; font-size:13px;">
                                <td style="padding:10px;">Sujata Karki</td>
                                <td style="padding:10px;">BCA III</td>
                                <td style="padding:10px; text-align:right; font-weight:700;">8,000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<style>
.fi { padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }

@media print {
    body { background: #fff !important; }
    .pg-acts, .sidebar, .header { display: none !important; }
    .main { margin-left: 0 !important; width: 100% !important; }
    .card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
}
</style>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
