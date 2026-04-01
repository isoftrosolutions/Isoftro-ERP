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

// ── Load config, badges & shared utils ──
require_once APP_ROOT . '/app/Helpers/sidebar-utils.php';
require_once APP_ROOT . '/app/Helpers/ia-sidebar-config.php';
require_once APP_ROOT . '/app/Helpers/ia-sidebar-badges.php';

$sidebarConfig = getIASidebarConfig();
$sidebarConfig = filterIASidebarByPermission($sidebarConfig);

$tenantId = $_SESSION['tenant_id'] ?? null;
$badges = getIASidebarBadges($tenantId);

$tenantName = $_SESSION['tenant_name'] ?? 'iSoftro ERP';
$planName   = $_SESSION['userData']['plan_name'] ?? 'Growth Plan';

// Dynamic brand color from session/tenant
$brandColor = $_SESSION['brand_color'] ?? '#00B894';

// User info
$initials = generateInitials($user['name'] ?? '', 'AD');
$logoPath  = resolveLogoPath($_SESSION['institute_logo'] ?? $_SESSION['tenant_logo'] ?? null);
?>

<!-- Dynamic Brand Color -->
<style>
    :root { --brand: <?php echo htmlspecialchars($brandColor); ?>; }
</style>

<!-- ── SIDEBAR ── -->
<nav class="sb" id="sidebar" aria-label="Main navigation">
    <!-- Mobile-only header -->
    <div class="sb-header">
        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($tenantName); ?> logo" class="sb-header-logo">
        <div class="logo-txt"><?php echo htmlspecialchars($tenantName); ?></div>
        <button class="sb-toggle" id="sbClose" aria-label="Close sidebar">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Nav search -->
    <div class="sb-search-wrap">
        <i class="fa-solid fa-magnifying-glass sb-search-icon" aria-hidden="true"></i>
        <input
            type="search"
            id="sbSearch"
            class="sb-search"
            placeholder="Search menu…"
            aria-label="Search navigation"
            autocomplete="off"
            oninput="(function(v){clearTimeout(window._sbST);window._sbST=setTimeout(()=>_iaRenderSidebar(v.trim().toLowerCase()),120);})(this.value)"
        >
    </div>

    <div class="sb-body" id="sbBody">
        <!-- Navigation rendered by ia-core.js from window._IA_NAV_CONFIG -->
    </div>

    <!-- Footer: tenant context + desktop collapse toggle -->
    <div class="sb-footer">
        <div class="sb-footer-inner">
            <div class="sb-tenant-av" aria-hidden="true"><?php echo htmlspecialchars(substr($tenantName, 0, 2)); ?></div>
            <div class="sb-footer-text">
                <div class="sb-tenant-name"><?php echo htmlspecialchars($tenantName); ?></div>
                <div class="sb-tenant-plan"><?php echo htmlspecialchars($planName); ?></div>
            </div>
        </div>
        <!-- Desktop collapse toggle -->
        <button class="js-sidebar-toggle sb-collapse-btn" aria-label="Toggle sidebar">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
    </div>
</nav>

<!-- Inject sidebar config as JSON for JS consumption -->
<script>
    window._IA_NAV_CONFIG = <?php echo json_encode($sidebarConfig, JSON_UNESCAPED_UNICODE); ?>;
    window._IA_BADGES = <?php echo json_encode($badges, JSON_UNESCAPED_UNICODE); ?>;
</script>
