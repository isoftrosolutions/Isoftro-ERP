<?php
/**
 * Front Desk — Inquiry Conversion Report
 * Visual insights into lead generation and conversion performance
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Conversion Report';
require_once VIEWS_PATH . '/layouts/header_1.php';
require_once __DIR__ . '/sidebar.php';
?>

<?php renderFrontDeskHeader(); ?>
<?php renderFrontDeskSidebar('inquiries'); ?>

<main class="main" id="mainContent">
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg, #10B981, #059669);">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div>
                    <h1 class="pg-title">Inquiry Report</h1>
                    <p class="pg-sub">Insights into your lead conversion performance</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="loadReportData()"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="sg mb" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-blue"><i class="fa-solid fa-users-viewfinder"></i></div></div>
                <div class="sc-val" id="totalLeads">-</div>
                <div class="sc-lbl">Total Leads (All Time)</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-teal"><i class="fa-solid fa-user-check"></i></div></div>
                <div class="sc-val" id="totalConverted">-</div>
                <div class="sc-lbl">Total Conversions</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-purple"><i class="fa-solid fa-percent"></i></div></div>
                <div class="sc-val" id="avgConversionRate">-%</div>
                <div class="sc-lbl">Avg. Conversion Rate</div>
            </div>
            <div class="sc">
                <div class="sc-top"><div class="sc-ico ic-amber"><i class="fa-solid fa-calendar-day"></i></div></div>
                <div class="sc-val" id="leadsThisMonth">-</div>
                <div class="sc-lbl">Leads (M-T-D)</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <!-- Source Breakdown -->
            <div class="card">
                <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9;">
                    <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-filter" style="color:#6C5CE7;"></i> Leads by Source
                    </h3>
                </div>
                <div style="padding:20px;" id="sourceBreakdown">
                    <div style="text-align:center; padding:40px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i></div>
                </div>
            </div>

            <!-- Status Breakdown -->
            <div class="card">
                <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9;">
                    <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-bullseye" style="color:#6C5CE7;"></i> Current Pipeline Status
                    </h3>
                </div>
                <div style="padding:20px;" id="statusBreakdown">
                    <div style="text-align:center; padding:40px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i></div>
                </div>
            </div>
        </div>

        <!-- Monthly Trend -->
        <div class="card" style="margin-top:20px;">
            <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9;">
                <h3 style="font-size:15px; font-weight:700; color:#1a1a2e; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-chart-line" style="color:#6C5CE7;"></i> Monthly Enrollment Trend
                </h3>
            </div>
            <div style="padding:30px; text-align:center; color:#94a3b8;">
                <div style="height:200px; display:flex; align-items:flex-end; justify-content:space-around; gap:10px;" id="trendChart">
                    <!-- Chart bars will be injected here -->
                </div>
            </div>
        </div>
    </div>
</main>

<script>
async function loadReportData() {
    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/inquiries');
        const result = await res.json();
        
        if (result.success) {
            const data = result.data || [];
            processData(data);
        }
    } catch (e) {
        console.error('Report load error:', e);
    }
}

function processData(data) {
    const total = data.length;
    const converted = data.filter(i => i.status === 'converted').length;
    const mtd = data.filter(i => i.created_at && i.created_at.startsWith(new Date().toISOString().substring(0, 7))).length;
    
    document.getElementById('totalLeads').textContent = total;
    document.getElementById('totalConverted').textContent = converted;
    document.getElementById('avgConversionRate').textContent = (total > 0 ? Math.round((converted/total)*100) : 0) + '%';
    document.getElementById('leadsThisMonth').textContent = mtd;
    
    // Source Breakdown
    const sources = {};
    data.forEach(i => { sources[i.source || 'other'] = (sources[i.source || 'other'] || 0) + 1; });
    
    const sourceContainer = document.getElementById('sourceBreakdown');
    sourceContainer.innerHTML = Object.entries(sources).map(([src, count]) => `
        <div style="margin-bottom:15px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px;">
                <span style="font-weight:600; text-transform:capitalize;">${src.replace('_', ' ')}</span>
                <span style="color:#64748b;">${count} leads (${Math.round((count/total)*100)}%)</span>
            </div>
            <div style="height:8px; border-radius:4px; background:#f1f5f9; overflow:hidden;">
                <div style="height:100%; width:${(count/total)*100}%; background:linear-gradient(90deg, #6366F1, #8B5CF6);"></div>
            </div>
        </div>
    `).join('');

    // Status Breakdown
    const statuses = {};
    data.forEach(i => { statuses[i.status || 'pending'] = (statuses[i.status || 'pending'] || 0) + 1; });
    
    const statusContainer = document.getElementById('statusBreakdown');
    statusContainer.innerHTML = Object.entries(statuses).map(([st, count]) => `
        <div style="margin-bottom:15px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px;">
                <span style="font-weight:600; text-transform:capitalize;">${st.replace('_', ' ')}</span>
                <span style="color:#64748b;">${count} in stage</span>
            </div>
            <div style="height:8px; border-radius:4px; background:#f1f5f9; overflow:hidden;">
                <div style="height:100%; width:${(count/total)*100}%; background:linear-gradient(90deg, #10B981, #059669);"></div>
            </div>
        </div>
    `).join('');

    // Trend Chart (Mocking last 6 months)
    const trendContainer = document.getElementById('trendChart');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    trendContainer.innerHTML = months.map(m => `
        <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:8px;">
            <div style="width:100%; max-width:40px; height:${Math.floor(Math.random()*80)+20}%; background:rgba(99, 102, 241, 0.2); border:1px solid rgba(99, 102, 241, 0.4); border-radius:6px; position:relative;" class="chart-bar">
                <div style="position:absolute; top:-20px; left:0; width:100%; text-align:center; font-size:11px; font-weight:700; color:#4F46E5;">${Math.floor(Math.random()*20)+5}</div>
            </div>
            <div style="font-size:11px; font-weight:600; color:#64748b;">${m}</div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', loadReportData);
</script>

<?php
renderSuperAdminCSS();
echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
?>
</body>
</html>
