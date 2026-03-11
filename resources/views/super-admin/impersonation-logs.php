<?php
/**
 * Hamro ERP — Tenant Impersonation Logs
 * Track admin access to tenant portals
 */

// Config should already be loaded via bootstrap, but include if needed
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pdo = getDBConnection();

// Fetch impersonation logs
$logs = $pdo->query("
    SELECT i.*, 
           t.name as tenant_name, 
           t.subdomain,
           u.email as admin_email,
           u.name as admin_name
    FROM impersonation_logs i
    LEFT JOIN tenants t ON i.tenant_id = t.id
    LEFT JOIN users u ON i.super_admin_id = u.id
    ORDER BY i.started_at DESC
    LIMIT 50
")->fetchAll();

$pageTitle = 'Impersonation Logs';
include __DIR__ . '/header.php';

renderSuperAdminHeader();
renderSidebar('impersonation-logs.php');
?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-r"><i class="fa-solid fa-user-secret"></i></div>
                <div>
                    <div class="pg-title">Tenant Impersonation Logs</div>
                    <div class="pg-sub">Track admin access to tenant portals for security audit</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="exportLogs()">
                    <i class="fa-solid fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="alert alert-warning" style="margin-bottom: 20px;">
            <i class="fa-solid fa-shield-halved"></i>
            <strong>Security Notice:</strong> All tenant impersonation sessions are logged for audit purposes. 
            Admins should only impersonate for legitimate support purposes.
        </div>

        <!-- Stats Cards -->
        <div class="sg">
            <div class="sc">
                <div class="sc-val"><?php echo count($logs); ?></div>
                <div class="sc-lbl">Total Sessions</div>
            </div>
            <div class="sc">
                <div class="sc-val">
                    <?php 
                    $activeSessions = 0;
                    foreach ($logs as $log) {
                        if ($log['ended_at'] === null) $activeSessions++;
                    }
                    echo $activeSessions;
                    ?>
                </div>
                <div class="sc-lbl">Active Sessions</div>
            </div>
            <div class="sc">
                <div class="sc-val">
                    <?php
                    $today = date('Y-m-d');
                    $todayCount = 0;
                    foreach ($logs as $log) {
                        if (date('Y-m-d', strtotime($log['started_at'])) === $today) {
                            $todayCount++;
                        }
                    }
                    echo $todayCount;
                    ?>
                </div>
                <div class="sc-lbl">Today's Sessions</div>
            </div>
            <div class="sc">
                <div class="sc-val">
                    <?php
                    $uniqueTenants = [];
                    foreach ($logs as $log) {
                        if (!in_array($log['tenant_id'], $uniqueTenants)) {
                            $uniqueTenants[] = $log['tenant_id'];
                        }
                    }
                    echo count($uniqueTenants);
                    ?>
                </div>
                <div class="sc-lbl">Unique Tenants Accessed</div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="ct"><i class="fa-solid fa-list"></i> Session History</div>
            <div class="tw">
                <table>
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Admin</th>
                            <th>Started</th>
                            <th>Ended</th>
                            <th>Duration</th>
                            <th>IP Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px; color: var(--tl);">
                                No impersonation logs found
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): 
                                $start = strtotime($log['started_at']);
                                $end = $log['ended_at'] ? strtotime($log['ended_at']) : time();
                                $duration = $end - $start;
                                $hours = floor($duration / 3600);
                                $minutes = floor(($duration % 3600) / 60);
                            ?>
                            <tr>
                                <td>
                                    <div class="tenant-info">
                                        <strong><?php echo htmlspecialchars($log['tenant_name'] ?? 'Unknown'); ?></strong>
                                        <span><?php echo htmlspecialchars($log['subdomain'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="admin-info">
                                        <strong><?php echo htmlspecialchars($log['admin_name'] ?? 'Unknown'); ?></strong>
                                        <span><?php echo htmlspecialchars($log['admin_email'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td><?php echo date('M d, H:i', $start); ?></td>
                                <td><?php echo $log['ended_at'] ? date('M d, H:i', $end) : '-'; ?></td>
                                <td>
                                    <?php 
                                    if ($log['ended_at']) {
                                        echo $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                                    } else {
                                        echo '<span class="tag bg-g">Active</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($log['ended_at']): ?>
                                    <span class="tag bg-t">Ended</span>
                                    <?php else: ?>
                                    <span class="tag bg-g">Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<style>
.tenant-info, .admin-info {
    display: flex;
    flex-direction: column;
}
.tenant-info span, .admin-info span {
    font-size: 11px;
    color: var(--tl);
}
.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 12px 16px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.alert-warning i {
    font-size: 1.2rem;
}
</style>

<script>
function exportLogs() {
    window.location.href = '?export=1';
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
