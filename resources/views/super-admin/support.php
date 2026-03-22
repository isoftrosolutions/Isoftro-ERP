<?php
/**
 * ISOFTRO - Support Tickets
 * Variable: $tickets
 */
$tickets = $tickets ?? [];
?>

<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Support</span></div>
        <h1>Support Tickets</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="SuperAdmin.showNotification('Export: coming soon','info')"><i class="fas fa-file-export"></i> Export</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #ef4444;">
        <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;">Open Tickets</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count(array_filter($tickets, fn($t) => ($t['status'] ?? 'open') === 'open')) ?></div>
        <div style="font-size:12px;color:var(--text-light);">Awaiting response</div>
    </div>
    <div class="card p-20" style="border-left:4px solid #f59e0b;">
        <div style="font-size:11px;font-weight:700;color:#f59e0b;text-transform:uppercase;">High Priority</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count(array_filter($tickets, fn($t) => ($t['priority'] ?? 'normal') === 'high')) ?></div>
        <div style="font-size:12px;color:var(--text-light);">Require immediate attention</div>
    </div>
    <div class="card p-20" style="border-left:4px solid #22c55e;">
        <div style="font-size:11px;font-weight:700;color:#22c55e;text-transform:uppercase;">Avg. Response Time</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;">2.4h</div>
        <div style="font-size:12px;color:var(--text-light);">Last 30 days</div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-envelope-open-text"></i> Active Tickets</span>
        <div style="display:flex;gap:8px;">
            <select class="filter-sel" id="priorityFilter" onchange="filterTickets()">
                <option value="">All Priorities</option>
                <option value="high">High</option>
                <option value="normal">Normal</option>
                <option value="low">Low</option>
            </select>
        </div>
    </div>

    <?php if (empty($tickets)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-inbox" style="font-size:40px;opacity:0.3;display:block;margin-bottom:15px;"></i>
        <p>No support tickets at this time.</p>
    </div>
    <?php else: ?>
    <div class="tbl-wrap mt-15">
        <table class="table" id="ticketsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Institute</th>
                    <th>Subject</th>
                    <th>Priority</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr data-priority="<?= $t['priority'] ?? 'normal' ?>">
                    <td style="font-weight:700;font-family:monospace;">#<?= $t['id'] ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($t['tenant'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($t['subject'] ?? '') ?></td>
                    <td>
                        <?php $p = $t['priority'] ?? 'normal'; ?>
                        <span class="bdg" style="background:<?= $p==='high'?'#fee2e2':($p==='low'?'#f0fdf4':'#fef9c3') ?>;color:<?= $p==='high'?'#b91c1c':($p==='low'?'#15803d':'#854d0e') ?>;">
                            <?= strtoupper($p) ?>
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-light);"><?= date('M d, H:i', strtotime($t['created_at'] ?? 'now')) ?></td>
                    <td>
                        <button class="btn bs sm" onclick="SuperAdmin.showNotification('Opening ticket #<?= $t['id'] ?>...','info')">
                            <i class="fas fa-external-link-alt"></i> View
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
function filterTickets() {
    const priority = document.getElementById('priorityFilter').value;
    document.querySelectorAll('#ticketsTable tbody tr').forEach(row => {
        row.style.display = (!priority || row.dataset.priority === priority) ? '' : 'none';
    });
}
</script>
