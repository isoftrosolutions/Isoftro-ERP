<?php
/**
 * Front Desk — Enrollment Analysis
 * Trends for admissions and inquiry conversions
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Enrollment Analysis';
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
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #3B82F6, #60A5FA);">
                    <i class="fa-solid fa-users-line"></i>
                </div>
                <div>
                    <h1 class="pg-title">Enrollment Analysis</h1>
                    <p class="pg-sub">Track student acquisition and course popularity trends</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="window.print()"><i class="fa-solid fa-download"></i> Export PDF</button>
            </div>
        </div>

        <!-- Conversion Stats -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-filter"></i></div></div>
                <div class="sc-val" id="convRate">18.5%</div>
                <div class="sc-lbl">Inquiry to Admission Rate</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-graduation-cap"></i></div></div>
                <div class="sc-val" id="totalActive">1,240</div>
                <div class="sc-lbl">Current Active Students</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-user-clock"></i></div></div>
                <div class="sc-val" id="retention">92%</div>
                <div class="sc-lbl">Student Retention Rate</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:20px;">
            <!-- Admission Trend -->
            <div class="card" style="padding:24px; border-radius:16px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:24px;">New Registrations (Last 6 Months)</h3>
                <div style="height:250px; display:flex; align-items:flex-end; justify-content:space-around; padding-bottom:30px;">
                    <?php 
                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                    foreach($months as $m): 
                        $h = rand(40, 95); ?>
                        <div style="display:flex; flex-direction:column; align-items:center; gap:10px; flex:1;">
                            <div style="width:30px; height:<?= $h ?>%; background:rgba(59, 130, 246, 0.1); border:2px solid #3B82F6; border-radius:6px; position:relative; overflow:hidden;">
                                <div style="position:absolute; bottom:0; left:0; right:0; height:<?= $h-rand(10,20) ?>%; background:#3B82F6;"></div>
                            </div>
                            <div style="font-size:11px; color:#64748b; font-weight:700;"><?= $m ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Course Popularity -->
            <div class="card" style="padding:24px; border-radius:16px;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; margin-bottom:24px;">Top Courses by Enrollment</h3>
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">BBA (Bachelor of Business)</span>
                            <span style="font-weight:800; color:#1e293b;">420</span>
                        </div>
                        <div style="height:8px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:85%; height:100%; background:#3B82F6;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">BCA (Computer App)</span>
                            <span style="font-weight:800; color:#1e293b;">315</span>
                        </div>
                        <div style="height:8px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:65%; height:100%; background:#10B981;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">Digital Marketing</span>
                            <span style="font-weight:800; color:#1e293b;">185</span>
                        </div>
                        <div style="height:8px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:40%; height:100%; background:#F59E0B;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px;">
                            <span style="font-weight:600; color:#475569;">IELTS / TOEFL</span>
                            <span style="font-weight:800; color:#1e293b;">120</span>
                        </div>
                        <div style="height:8px; background:#f1f5f9; border-radius:10px; overflow:hidden;">
                            <div style="width:25%; height:100%; background:#6366F1;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>
.fi { padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.btn { padding:10px 20px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
</style>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
