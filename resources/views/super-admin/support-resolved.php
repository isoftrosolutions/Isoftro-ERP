<?php
/**
 * ISOFTRO - Resolved Support Tickets
 * Variable: $tickets
 */
$tickets = $tickets ?? [];
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Support</span> <span style="color:#94a3b8;"> / Resolved</span></div>
        <h1>Resolved Tickets</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('support')"><i class="fas fa-envelope-open-text"></i> Open Tickets</button>
        <button class="btn bs" onclick="goNav('support-resolved')"><i class="fas fa-sync"></i> Refresh</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #22c55e;">
        <div style="font-size:11px;font-weight:700;color:#22c55e;text-transform:uppercase;">Resolved</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count($tickets) ?></div>
        <div style="font-size:12px;color:var(--text-light);">Successfully closed</div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-check-double"></i> Resolved History</span>
        <div class="search-box"><i class="fas fa-search"></i>
            <input type="text" class="search-inp" placeholder="Search..." onkeyup="filterResolved(this.value)">
        </div>
    </div>
    <?php if (empty($tickets)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-inbox" style="font-size:40px;opacity:.3;display:block;margin-bottom:15px;"></i>
        <p>No resolved tickets found.</p>
    </div>
    <?php else: ?>
    <div class="tbl-wrap mt-15">
        <table id="resolvedTable">
            <thead>
                <tr><th>#</th><th>Institute</th><th>Subject</th><th>Priority</th><th>Resolved On</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td style="font-weight:700;font-family:monospace;">#<?= $t['id'] ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($t['tenant_name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($t['subject'] ?? '') ?></td>
                    <td>
                        <?php $p = $t['priority'] ?? 'normal'; ?>
                        <span class="bdg" style="background:<?= $p==='high'?'#fee2e2':($p==='low'?'#f0fdf4':'#fef9c3') ?>;color:<?= $p==='high'?'#b91c1c':($p==='low'?'#15803d':'#854d0e') ?>;">
                            <?= strtoupper($p) ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-light);"><?= date('M d, Y', strtotime($t['updated_at'] ?? $t['created_at'])) ?></td>
                    <td>
                        <button class="btn bs sm" onclick="SuperAdmin.showNotification('Ticket #<?= $t['id'] ?> details loading...','info')">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn bs sm" onclick="reopenTicket(<?= $t['id'] ?>)" title="Reopen">
                            <i class="fas fa-undo"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filterResolved(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#resolvedTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
async function reopenTicket(id) {
    const r = await SuperAdmin.confirmAction('Reopen ticket #' + id + '?', 'It will move back to Open Tickets.', 'Yes, Reopen');
    if (r.isConfirmed) {
        SuperAdmin.showNotification('Ticket #' + id + ' reopened', 'success');
        setTimeout(() => goNav('support'), 1000);
    }
}
</script>
