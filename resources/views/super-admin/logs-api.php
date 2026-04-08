<?php
/**
 * ISOFTRO - API Request Logs
 * Variable: $logs
 */
$logs = $logs ?? [];
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Logs</span> <span style="color:#94a3b8;"> / API Logs</span></div>
        <h1>API Request Logs</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('logs')"><i class="fas fa-shield-check"></i> Audit Logs</button>
        <button class="btn bs" onclick="goNav('logs-errors')"><i class="fas fa-bug"></i> Error Logs</button>
        <button class="btn bt" onclick="goNav('logs-api')"><i class="fas fa-sync"></i> Refresh</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #6366f1;">
        <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;">API Events</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count($logs) ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #0284c7;">
        <div style="font-size:11px;font-weight:700;color:#0284c7;text-transform:uppercase;">Unique Admins</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;">
            <?= count(array_unique(array_column($logs, 'user_id'))) ?>
        </div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-network-wired"></i> API Activity Log</span>
        <div class="search-box"><i class="fas fa-search"></i>
            <input type="text" class="search-inp" placeholder="Search action or email..." onkeyup="filterApiLogs(this.value)">
        </div>
    </div>
    <?php if (empty($logs)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-network-wired" style="font-size:40px;opacity:.3;display:block;margin-bottom:15px;"></i>
        <p>No API log entries found. API-tagged audit events will appear here.</p>
    </div>
    <?php else: ?>
    <div class="tbl-wrap mt-15">
        <table id="apiLogTable">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Tenant</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap;"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($l['user_email'] ?: 'System') ?></strong></td>
                    <td><span class="pill pp"><?= htmlspecialchars($l['action']) ?></span></td>
                    <td><?= htmlspecialchars($l['tenant_name'] ?: '—') ?></td>
                    <td><code style="color:var(--text-light);font-size:11px;"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></code></td>
                    <td><small style="color:var(--text-light);"><?= htmlspecialchars(substr($l['metadata'] ?? '{}', 0, 60)) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filterApiLogs(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#apiLogTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
