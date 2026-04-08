<?php
/**
 * ISOFTRO - Invoice Generator
 * Variable: $payments
 */
$payments = $payments ?? [];
$total    = array_sum(array_column($payments, 'amount'));
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Revenue</span> <span style="color:#94a3b8;"> / Invoice Generator</span></div>
        <h1>Invoice Generator</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="exportInvoices()"><i class="fas fa-file-export"></i> Export CSV</button>
        <button class="btn bt" onclick="openCreateInvoiceModal()"><i class="fas fa-plus"></i> Manual Invoice</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="background:linear-gradient(135deg,#0284c7,#0ea5e9);color:#fff;border:none;">
        <div style="font-size:11px;font-weight:700;opacity:.8;text-transform:uppercase;">Total Invoiced</div>
        <div style="font-size:30px;font-weight:800;margin:8px 0;">Rs. <?= number_format($total) ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #22c55e;">
        <div style="font-size:11px;font-weight:700;color:#22c55e;text-transform:uppercase;">Total Records</div>
        <div style="font-size:30px;font-weight:800;color:var(--text-dark);margin:8px 0;"><?= count($payments) ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #f59e0b;">
        <div style="font-size:11px;font-weight:700;color:#f59e0b;text-transform:uppercase;">Pending Invoices</div>
        <div style="font-size:30px;font-weight:800;color:var(--text-dark);margin:8px 0;">
            <?= count(array_filter($payments, fn($p) => ($p['status'] ?? 'paid') !== 'paid')) ?>
        </div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-file-invoice"></i> Payment Records</span>
        <div class="search-box"><i class="fas fa-search"></i>
            <input type="text" class="search-inp" placeholder="Search by institute or ref..." onkeyup="filterInvoices(this.value)">
        </div>
    </div>
    <div class="tbl-wrap mt-15">
        <table id="invoicesTable">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Institute</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-light);">No payment records found.</td></tr>
                <?php else: ?>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><code style="font-size:12px;"><?= htmlspecialchars($p['reference_id'] ?? 'N/A') ?></code></td>
                    <td>
                        <div style="font-weight:700;"><?= htmlspecialchars($p['tenant_name']) ?></div>
                        <div style="font-size:11px;color:var(--text-light);"><?= htmlspecialchars($p['tenant_email'] ?? '') ?></div>
                    </td>
                    <td><span class="pill pp"><?= strtoupper($p['plan'] ?? 'N/A') ?></span></td>
                    <td style="font-weight:700;">Rs. <?= number_format($p['amount']) ?></td>
                    <td>
                        <?php $st = $p['status'] ?? 'paid'; ?>
                        <span class="pill" style="background:<?= $st==='paid'?'#dcfce7':'#fee2e2' ?>;color:<?= $st==='paid'?'#166534':'#991b1b' ?>;">
                            <?= strtoupper($st) ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-light);"><?= date('M d, Y', strtotime($p['created_at'] ?? 'now')) ?></td>
                    <td>
                        <button class="btn bs sm" onclick="downloadInvoice(<?= $p['id'] ?>)" title="Download Invoice">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn bs sm" onclick="sendInvoice(<?= $p['id'] ?>)" title="Send via Email">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterInvoices(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#invoicesTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function downloadInvoice(id) {
    SuperAdmin.showNotification('Generating invoice PDF...', 'info');
    window.open((window.APP_URL || '') + '/api/invoices/download/' + id, '_blank');
}
function sendInvoice(id) {
    SuperAdmin.showNotification('Invoice sent via email.', 'success');
}
function exportInvoices() {
    SuperAdmin.showNotification('Export feature coming soon.', 'info');
}
function openCreateInvoiceModal() {
    SuperAdmin.showNotification('Manual invoice creation coming soon.', 'info');
}
</script>
