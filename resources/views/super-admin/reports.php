<?php
/**
 * Hamro ERP — Super Admin Reports Engine UI
 */

// Load global config if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Report Engine';
// Use the Super Admin specific header from layouts
require_once VIEWS_PATH . '/layouts/header_1.php';
?>

<?php renderSuperAdminHeader(); ?>
<?php renderSidebar('reports'); ?>


<!-- ── MAIN CONTENT (mirrors institute-admin .main) ── -->
<main class="main" id="mainContent">
    <div class="page fu">
        <div class="pg-hdr">
            <div class="pg-hdr-left">
                <div class="breadcrumb">
                    <span class="bc-root" onclick="goNav('overview')">Dashboard</span>
                    <span class="bc-sep">›</span>
                    <span class="bc-cur">Report Engine</span>
                </div>
                <h1 style="display:flex; align-items:center; gap:10px;">
                    <i class="fa fa-file-contract" style="color:var(--purple); font-size:1.1rem;"></i>
                    Platform Report Engine
                </h1>
                <p>Generate detailed platform-wide reports in Excel and PDF formats using Python engine.</p>
            </div>
        </div>

        <div class="g65">
            <div class="card report-config">
                <div class="ct"><i class="fa fa-cog"></i> Report Configuration</div>
                <form id="reportForm" onsubmit="generateReport(event)">
                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label class="form-label">Report Type</label>
                            <select class="form-control" name="type" id="reportType" required>
                                <option value="revenue">Revenue & Payments Report</option>
                                <option value="tenants">Tenant Infrastructure Report</option>
                                <option value="users">User Accounts Report</option>
                                <option value="sms">SMS Consumption Audit</option>
                                <option value="login">Login History Log</option>
                                <option value="audit">System Audit Trail</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Output Format</label>
                            <select class="form-control" name="format" id="reportFormat" required>
                                <option value="excel">Microsoft Excel (.xlsx)</option>
                                <option value="pdf">Adobe PDF (.pdf)</option>
                            </select>
                        </div>
                    </div>

                    <div id="dateFilters" class="form-grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start" id="startDate" value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end" id="endDate" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn bs">Reset</button>
                        <button type="submit" class="btn bt" id="genBtn">
                            <i class="fa fa-bolt"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <div class="card history-card">
                <div class="ct"><i class="fa fa-history"></i> Recent Generations</div>
                <div id="generationHistory">
                    <div class="history-empty">
                        <i class="fa fa-folder-open"></i>
                        <p>No reports generated in this session yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card about-card">
            <div class="ct"><i class="fa fa-info-circle"></i> About Report Engine</div>
            <div class="info-grid">
                <div class="info-card purple">
                    <h4><i class="fa fa-python"></i> Powered by Python</h4>
                    <p>Uses heavy-duty data processing and charting libraries for high-fidelity exports.</p>
                </div>
                <div class="info-card green">
                    <h4><i class="fa fa-file-excel"></i> Smart Excel</h4>
                    <p>Reports include automated charts and conditional formatting for instant insights.</p>
                </div>
                <div class="info-card blue">
                    <h4><i class="fa fa-shield-alt"></i> Secure Exports</h4>
                    <p>Audit trails are maintained for every report generated by any administrator.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
async function generateReport(e) {
    e.preventDefault();
    const btn = document.getElementById('genBtn');
    const form = document.querySelector('#reportForm');
    const formData = new FormData(form);
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Processing...';
    
    try {
        const response = await fetch('<?php echo APP_URL; ?>/api/super-admin/reports/generate', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': window.CSRF_TOKEN
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Report generated successfully!', 'success');
            addToHistory(result);
            // Auto download
            window.location.href = result.url;
        } else {
            showToast(result.message || 'Generation failed', 'error');
        }
    } catch (err) {
        showToast('System error during generation', 'error');
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-bolt"></i> Generate Report';
    }
}

function addToHistory(report) {
    const history = document.getElementById('generationHistory');
    
    // Remove empty state if present
    const emptyState = history.querySelector('.history-empty');
    if (emptyState) {
        emptyState.remove();
    }
    
    const div = document.createElement('div');
    div.className = 'tl-item';
    
    const iconClass = report.filename.endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file-excel';
    const iconColor = report.filename.endsWith('.pdf') ? 'ic-red' : 'ic-green';
    const dotClass = report.filename.endsWith('.pdf') ? 'ic-purple' : 'ic-blue';
    
    div.innerHTML = `
        <div class="tl-dot ${dotClass}">
            <i class="fa ${iconClass}"></i>
        </div>
        <div class="tl-content">
            <div class="tl-title">${report.filename}</div>
            <div class="tl-time">Generated just now</div>
        </div>
        <div class="tl-download">
            <a href="${report.url}" class="btn bs btn-sm" download><i class="fa fa-download"></i></a>
        </div>
    `;
    
    history.prepend(div);
}

// Toast notification function
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = type === 'success' ? 'toast-success' : 'toast-error';
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.animation = 'slideIn 0.3s ease';
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<style>
/* Inline styles for backward compatibility - main styles are in super_admin.css */
.form-group { margin-bottom: 0; }
.form-label { display: block; font-size: 13px; font-weight: 600; color: var(--td); margin-bottom: 8px; }
.form-control { width: 100%; padding: 10px 14px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 14px; transition: border-color .2s; }
.form-control:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 0 3px rgba(129, 65, 165, 0.1); }
.text-red-500 { color: #ef4444; }
.text-green-500 { color: #22c55e; }

/* Toast animations */
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

/* Icon colors for timeline */
.ic-red { color: #ef4444; }
.ic-green { color: #22c55e; }
</style>

<?php include 'footer.php'; ?>
