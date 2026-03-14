<?php
/**
 * Shared Outstanding Dues Manager Component
 * Nexus Design System
 */

$apiEndpoint = $apiEndpoint ?? APP_URL . '/api/frontdesk/fee-reports';
$componentId = $componentId ?? 'shared_dues';
?>

<div class="pg-nexus">
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Outstanding Dues</span>
    </div>

    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(239, 68, 68, 0.08); color: #EF4444;">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h1 class="pg-title">Outstanding Dues</h1>
                <p class="pg-sub">List of students with unpaid fees and overdue balances</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="refreshDues()">
                <i class="fa-solid fa-rotate"></i>
            </button>
            <button class="btn" style="background: #1e293b; color: #fff;" onclick="window.location.href='<?= APP_URL ?>/dash/front-desk/index?page=report-fees'">
                <i class="fa-solid fa-chart-line"></i> Fee Reports
            </button>
        </div>
    </div>

    <div class="stat-group mb">
        <div class="stat-item" style="border-left: 4px solid #EF4444;">
            <span class="lbl" style="color: #EF4444;">Total Outstanding</span>
            <span class="val" id="dues_total_amount" style="color: #991B1B;">NPR 0.00</span>
            <span class="sub">Total pending till date</span>
        </div>
        <div class="stat-item" style="border-left: 4px solid #F59E0B;">
            <span class="lbl" style="color: #F59E0B;">Defaulters</span>
            <span class="val" id="dues_defaulter_count">0</span>
            <span class="sub">Unique students with dues</span>
        </div>
        <div class="stat-item" style="border-left: 4px solid #3B82F6;">
            <span class="lbl" style="color: #3B82F6;">Overdue (>30 Days)</span>
            <span class="val" id="dues_overdue_count">0</span>
            <span class="sub">Long term pending</span>
        </div>
    </div>

    <div class="card" style="border-radius: 14px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table" id="dues_table">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding-left: 24px;">Student Detail</th>
                        <th>Program/Batch</th>
                        <th style="text-align: right;">Total Due</th>
                        <th style="text-align: center;">Last Payment</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right; padding-right: 24px;">Action</th>
                    </tr>
                </thead>
                <tbody id="dues_table_body">
                    <tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading dues data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const API_URL = "<?= $apiEndpoint ?>";
    let allDues = [];
    
    window.refreshDues = async () => {
        const tbody = document.getElementById('dues_table_body');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Refreshing...</td></tr>';
        await loadDues();
    };

    async function loadDues() {
        try {
            // Summary Stats
            const sumRes = await fetch(`${API_URL}?action=summary`, getHeaders());
            const sum = await sumRes.json();
            if (sum.success) {
                document.getElementById('dues_total_amount').textContent = 'NPR ' + parseFloat(sum.data.total_outstanding).toLocaleString('en-NP', {minimumFractionDigits: 2});
                document.getElementById('dues_defaulter_count').textContent = sum.data.defaulter_count;
                document.getElementById('dues_overdue_count').textContent = sum.data.overdue_count || 0;
            }

            // List
            const res = await fetch(`${API_URL}?action=defaulters`, getHeaders());
            const r = await res.json();
            if (r.success) {
                allDues = r.data || [];
                renderDuesTable();
            } else {
                throw new Error(r.message);
            }
        } catch (e) {
            document.getElementById('dues_table_body').innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--red);">${e.message}</td></tr>`;
        }
    }

    function renderDuesTable() {
        const tbody = document.getElementById('dues_table_body');
        if (!allDues.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px; color: var(--text-light);">No outstanding dues found. All clear!</td></tr>';
            return;
        }
        
        const today = new Date().toISOString().split('T')[0];

        tbody.innerHTML = allDues.map(d => {
            const isOverdue = d.oldest_due_date < today;
            const statusTag = isOverdue 
                ? `<span class="tag" style="background:#FEE2E2; color:#B91C1C; font-weight:800;">CRITICAL OVERDUE</span>`
                : `<span class="tag" style="background:#FFF7ED; color:#C2410C; font-weight:800;">PENDING</span>`;
            
            return `
                <tr>
                    <td style="padding-left: 24px;">
                        <div style="font-weight: 700; color: var(--text-dark);">${d.full_name}</div>
                        <div style="font-size: 11px; color: var(--text-light); margin-top: 2px;">#${d.roll_no} • ${d.phone || 'No Phone'}</div>
                    </td>
                    <td>
                        <div style="font-size: 13px; font-weight: 600; color:#334155;">${d.batch_name || '—'}</div>
                    </td>
                    <td style="text-align: right; font-weight: 800; color: #991B1B;">
                        ${parseFloat(d.total_due).toLocaleString('en-NP', {minimumFractionDigits: 2})}
                    </td>
                    <td style="text-align: center; font-size: 12px; color: var(--text-light);">
                        ${d.last_payment_date ? new Date(d.last_payment_date).toLocaleDateString() : 'Never'}
                    </td>
                    <td style="text-align: center;">${statusTag}</td>
                    <td style="text-align: right; padding-right: 24px;">
                        <button class="btn sm" style="background: #1e293b; color: #fff;" onclick="goNav('fee','fee-coll', {student_id: ${d.student_id}})">
                            <i class="fa-solid fa-wallet"></i> Collect
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    loadDues();
})();
</script>
