<?php
/**
 * ISOFTRO - Tenant Impersonation Log
 * Variable: $logs
 */
$logs = $logs ?? [];
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Support</span> <span style="color:#94a3b8;"> / Impersonation Log</span></div>
        <h1>Tenant Impersonation Log</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('support')"><i class="fas fa-arrow-left"></i> Open Tickets</button>
    </div>
</div>

<div class="card" style="background:#fef9c3;border:1px solid #fde68a;padding:14px 20px;border-radius:12px;margin-top:20px;display:flex;align-items:center;gap:12px;">
    <i class="fas fa-triangle-exclamation" style="color:#d97706;font-size:18px;"></i>
    <span style="font-size:13px;color:#92400e;">All impersonation sessions are logged for security and compliance. Only authorized super admins can impersonate tenants.</span>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #7c3aed;">
        <div style="font-size:11px;font-weight:700;color:#7c3aed;text-transform:uppercase;">Total Sessions</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count($logs) ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #0284c7;">
        <div style="font-size:11px;font-weight:700;color:#0284c7;text-transform:uppercase;">Unique Admins</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count(array_unique(array_column($logs, 'user_id'))) ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #059669;">
        <div style="font-size:11px;font-weight:700;color:#059669;text-transform:uppercase;">Tenants Accessed</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count(array_unique(array_column($logs, 'tenant_id'))) ?></div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-user-secret"></i> Impersonation History</span>
        <div class="search-box"><i class="fas fa-search"></i>
            <input type="text" class="search-inp" placeholder="Filter by admin or tenant..." onkeyup="filterImpLog(this.value)">
        </div>
    </div>
    <?php if (empty($logs)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-user-secret" style="font-size:40px;opacity:.3;display:block;margin-bottom:15px;"></i>
        <p>No impersonation sessions recorded.</p>
    </div>
    <?php else: ?>
    <div class="tbl-wrap mt-15">
        <table id="impLogTable">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Tenant Accessed</th>
                    <th>IP Address</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap;"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($l['admin_email'] ?: 'System') ?></td>
                    <td>
                        <?php $a = $l['action'] ?? ''; $started = str_contains($a, 'start') || str_contains($a, 'begin'); ?>
                        <span class="pill" style="background:<?= $started ? '#ede9fe' : '#f1f5f9' ?>;color:<?= $started ? '#6d28d9' : '#475569' ?>;">
                            <?= htmlspecialchars($a) ?>
                        </span>
                    </td>
                    <td style="font-weight:600;"><?= htmlspecialchars($l['tenant_name'] ?: '—') ?></td>
                    <td><code style="font-size:11px;color:var(--text-light);"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></code></td>
                    <td><small style="color:var(--text-light);"><?= htmlspecialchars(substr($l['metadata'] ?? '{}', 0, 60)) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filterImpLog(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#impLogTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
