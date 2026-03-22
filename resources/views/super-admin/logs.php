<?php
/**
 * ISOFTRO - Super Admin Dashboard (Logs)
 * Partial view loaded via AJAX
 */

$PDO = getDBConnection();
$logs = $logs ?? []; // Passed from controller

?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>System Logs</span></div>
        <h1>Platform Audit & Security</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="clearLogs()"><i class="fas fa-trash-alt"></i> Clear Old Logs</button>
        <button class="btn bt" onclick="syncLogs()"><i class="fas fa-sync"></i> Refresh</button>
    </div>
</div>

<div class="toolbar">
    <div class="search-box"><i class="fas fa-search"></i> <input type="text" class="search-inp" placeholder="Search logs..."></div>
    <select class="filter-sel" onchange="filterLogs()">
        <option value="">All Types</option>
        <option value="tenant_create">Tenant Create</option>
        <option value="tenant_suspend">Tenant Suspend</option>
        <option value="settings_update">Settings Update</option>
        <option value="auth_failed">Auth Failures</option>
    </select>
</div>

<div class="tbl-wrap">
    <table>
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Tenant</th>
                <th>IP Address</th>
                <th>Metadata</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                <td><strong><?= htmlspecialchars($log['user_email'] ?: 'System') ?></strong></td>
                <td><span class="pill pp"><?= htmlspecialchars($log['action']) ?></span></td>
                <td><?= htmlspecialchars($log['tenant_name'] ?: 'N/A') ?></td>
                <td><code style="color: var(--text-light);"><?= htmlspecialchars($log['ip_address']) ?></code></td>
                <td><small><?= htmlspecialchars(substr($log['metadata'] ?? '{}', 0, 50)) ?>...</small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function syncLogs() { SuperAdmin.renderLogs(); }
function clearLogs() { 
    SuperAdmin.confirmAction("Purge Audit Logs?", "Logs older than 90 days will be archived.")
    .then((result) => {
        if (result.isConfirmed) {
            SuperAdmin.showNotification("Logs archived successfully", "success");
        }
    });
}
function filterLogs() { /* Client-side logic... */ }
</script>
