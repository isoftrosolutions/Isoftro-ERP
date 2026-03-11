<?php
/**
 * Hamro ERP — Maintenance Mode
 * Platform-wide maintenance toggle
 */

// Config should already be loaded via bootstrap, but include if needed
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pdo = getDBConnection();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO platform_settings (setting_key, setting_value, setting_type, description)
            VALUES ('maintenance_mode', ?, 'boolean', 'Enable platform maintenance mode')
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $value = isset($_POST['maintenance_enabled']) ? '1' : '0';
        $stmt->execute([$value, $value]);
        
        $message = $_POST['maintenance_enabled'] ? 'Maintenance mode enabled!' : 'Maintenance mode disabled!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current maintenance status
$maintenanceEnabled = false;
try {
    $stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'maintenance_mode'");
    $result = $stmt->fetch();
    $maintenanceEnabled = $result && $result['setting_value'] === '1';
} catch (Exception $e) {
    // Table may not exist yet
}

// Get recent maintenance logs
$maintenanceLogs = [];
try {
    $maintenanceLogs = $pdo->query("
        SELECT * FROM audit_logs 
        WHERE action LIKE '%maintenance%' OR description LIKE '%maintenance%'
        ORDER BY created_at DESC
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    // Table may not exist
}

$pageTitle = 'Maintenance Mode';
include __DIR__ . '/header.php';

renderSuperAdminHeader();
renderSidebar('maintenance.php');
?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-r"><i class="fa-solid fa-tools"></i></div>
                <div>
                    <div class="pg-title">Maintenance Mode</div>
                    <div class="pg-sub">Control platform availability for all tenants</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($maintenanceEnabled): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>WARNING:</strong> Maintenance mode is currently ENABLED. All tenants cannot access the platform.
        </div>
        <?php endif; ?>

        <div class="g7-3">
            <!-- Maintenance Toggle -->
            <div class="card">
                <div class="ct">
                    <i class="fa-solid fa-power-off"></i> 
                    Platform Status
                </div>
                
                <div class="maintenance-status <?php echo $maintenanceEnabled ? 'enabled' : 'disabled'; ?>">
                    <div class="status-icon">
                        <i class="fa-solid fa-<?php echo $maintenanceEnabled ? 'pause' : 'play'; ?>"></i>
                    </div>
                    <div class="status-text">
                        <h3><?php echo $maintenanceEnabled ? 'MAINTENANCE MODE' : 'SYSTEM OPERATIONAL'; ?></h3>
                        <p><?php echo $maintenanceEnabled ? 'Platform is currently in maintenance mode' : 'All systems running normally'; ?></p>
                    </div>
                </div>

                <form method="POST" class="maintenance-form">
                    <div class="frm-check">
                        <label class="toggle-label">
                            <span>Enable Maintenance Mode</span>
                            <input type="checkbox" name="maintenance_enabled" 
                                <?php echo $maintenanceEnabled ? 'checked' : ''; ?>
                                onchange="this.form.submit()">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <p style="font-size: 12px; color: var(--tl); margin-top: 12px;">
                        When enabled, all tenants will see a maintenance page and won't be able to access their dashboards.
                    </p>
                </form>
            </div>

            <!-- Maintenance Info -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-info-circle"></i> About Maintenance Mode</div>
                
                <div class="info-list">
                    <div class="info-item">
                        <i class="fa-solid fa-globe"></i>
                        <div>
                            <strong>Platform-Wide</strong>
                            <span>Applies to all tenants and users</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-bell"></i>
                        <div>
                            <strong>Tenant Notification</strong>
                            <span>Tenants will see a maintenance banner</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <div>
                            <strong>Admin Access</strong>
                            <span>Super admins can still access</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fa-solid fa-database"></i>
                        <div>
                            <strong>Database</strong>
                            <span>Read-only during maintenance</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Maintenance Activity -->
        <div class="card" style="margin-top: 20px;">
            <div class="ct"><i class="fa-solid fa-history"></i> Recent Maintenance Activity</div>
            <?php if (empty($maintenanceLogs)): ?>
            <p style="text-align: center; padding: 30px; color: var(--tl);">
                No maintenance activity recorded yet
            </p>
            <?php else: ?>
            <div class="tw">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Admin</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenanceLogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($log['user_id'] ?? 'System'); ?></td>
                            <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.maintenance-status {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
}
.maintenance-status.enabled {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border: 2px solid #ef4444;
}
.maintenance-status.disabled {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border: 2px solid #10b981;
}
.status-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.maintenance-status.enabled .status-icon {
    background: #ef4444;
    color: white;
}
.maintenance-status.disabled .status-icon {
    background: #10b981;
    color: white;
}
.status-text h3 {
    margin: 0 0 4px 0;
    font-size: 18px;
}
.maintenance-status.enabled .status-text h3 {
    color: #dc2626;
}
.maintenance-status.disabled .status-text h3 {
    color: #059669;
}
.status-text p {
    margin: 0;
    color: var(--tl);
    font-size: 13px;
}
.toggle-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    padding: 16px;
    background: var(--sa-bg);
    border-radius: 8px;
}
.toggle-label input {
    display: none;
}
.toggle-slider {
    width: 50px;
    height: 26px;
    background: var(--tl);
    border-radius: 13px;
    position: relative;
    transition: background 0.3s;
}
.toggle-slider::after {
    content: '';
    position: absolute;
    width: 22px;
    height: 22px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s;
}
.toggle-label input:checked + .toggle-slider {
    background: var(--sa-primary);
}
.toggle-label input:checked + .toggle-slider::after {
    transform: translateX(24px);
}
.info-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: var(--sa-bg);
    border-radius: 8px;
}
.info-item i {
    width: 24px;
    color: var(--sa-primary);
}
.info-item div {
    display: flex;
    flex-direction: column;
}
.info-item strong {
    font-size: 13px;
}
.info-item span {
    font-size: 12px;
    color: var(--tl);
}
.alert-error {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
