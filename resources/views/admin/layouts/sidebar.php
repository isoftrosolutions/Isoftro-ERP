<?php
/**
 * Institute Admin — Sidebar Navigation Component
 * 
 * Renders ONLY for institute_admin role.
 * Menu structure is loaded from ia-sidebar-config.php (single source of truth).
 * Badge counters loaded from ia-sidebar-badges.php.
 * Filtered config is injected as JSON for JS rendering.
 */

// ── Guard: Only render for institute admin ──
$user = getCurrentUser();
if (!$user || ($user['role'] ?? '') !== 'instituteadmin') {
    return; // Silent exit — unauthorized users see nothing
}

// ── Load config & badges ──
require_once APP_ROOT . '/app/Helpers/ia-sidebar-config.php';
require_once APP_ROOT . '/app/Helpers/ia-sidebar-badges.php';

$sidebarConfig = getIASidebarConfig();
$sidebarConfig = filterIASidebarByPermission($sidebarConfig);

$tenantId = $_SESSION['tenant_id'] ?? null;
$badges = getIASidebarBadges($tenantId);

$tenantName = $_SESSION['tenant_name'] ?? 'Hamro ERP';
$planName   = $_SESSION['userData']['plan_name'] ?? 'Growth Plan';

// Dynamic brand color from session/tenant
$brandColor = $_SESSION['brand_color'] ?? '#00B894';

// User info
$initials = 'AD';
if ($user && isset($user['name'])) {
    $parts = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

// Institute logo - use tenant logo if available, otherwise fallback to default
$tenantLogo = $_SESSION['institute_logo'] ?? $_SESSION['tenant_logo'] ?? null;
if (!empty($tenantLogo)) {
    $logoRelativePath = $tenantLogo;
    // Fix old paths that don't have /public prefix
    if (strpos($logoRelativePath, '/uploads/') === 0 && strpos($logoRelativePath, '/public/') !== 0) {
        $logoRelativePath = '/public' . $logoRelativePath;
    }
    // Ensure logo path has proper prefix
    $logoPath = (strpos($logoRelativePath, 'http') === 0)
        ? $logoRelativePath
        : APP_URL . $logoRelativePath;
} else {
    $logoPath = APP_URL . '/public/assets/images/logo.png';
}
?>

<!-- Dynamic Brand Color -->
<style>
    :root {
        --brand: <?php echo htmlspecialchars($brandColor); ?>;
    }
</style>

    <!-- ── SIDEBAR (mirrors super-admin structure) ── -->
    <nav class="sb" id="sidebar">
        <!-- Mobile-only header inside sidebar -->
        <div class="sb-header">
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="height:28px; width:auto; margin-right:10px; filter: brightness(0) invert(1);">
            <div class="logo-txt">Academic Platform</div>
            <button class="sb-toggle" id="sbClose">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="sb-body" id="sbBody">
            <!-- Navigation (rendered by JS from config) -->
        </div>

        <!-- Sidebar Footer & Collapse Toggle -->
        <div class="sb-footer" style="padding: 15px 20px; border-top: 1px solid var(--card-border); margin-top: auto; position: relative;">
             <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; background: #f3f4f6; color: var(--green); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; flex-shrink: 0;">
                    <?php echo htmlspecialchars(substr($tenantName, 0, 2)); ?>
                </div>
                <div class="sb-footer-text" style="flex: 1; min-width: 0;">
                    <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($tenantName); ?></div>
                    <div style="font-size: 10px; color: var(--text-light);"><?php echo htmlspecialchars($planName); ?></div>
                </div>
             </div>
             
             <!-- Desktop Collapse Toggle (Absolute positioned) -->
             <button class="js-sidebar-toggle desktop-collapse-btn" 
                     style="position: absolute; right: -12px; top: -14px; width: 24px; height: 24px; background: white; border: 1px solid var(--card-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-light); z-index: 10;"
                     onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--text-light)'">
                 <i class="fa-solid fa-chevron-left"></i>
             </button>
        </div>
    </nav>

<!-- Inject sidebar config as JSON for JS consumption -->
<script>
    window._IA_NAV_CONFIG = <?php echo json_encode($sidebarConfig, JSON_UNESCAPED_UNICODE); ?>;
    window._IA_BADGES = <?php echo json_encode($badges, JSON_UNESCAPED_UNICODE); ?>;
</script>
