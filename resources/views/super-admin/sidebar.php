<?php

/**
 * ISOFTRO - Super Admin Sidebar Navigation Component
 * 
 * Renders ONLY for superadmin role.
 * Menu structure is loaded from sa-sidebar-config.php (single source of truth).
 * Badge counters loaded from sa-sidebar-badges.php.
 * Filtered config is injected as JSON for JS rendering.
 */

function renderSuperAdminSidebar() {
    // ── Guard: Only render for super admin ──
    $user = getCurrentUser();
    if (!$user || (($user['role'] ?? '') !== 'superadmin' && ($user['role'] ?? '') !== 'super-admin')) {
        return; // Silent exit — unauthorized users see nothing
    }

    // ── Load config, badges & shared utils ──
    require_once APP_ROOT . '/app/Helpers/sidebar-utils.php';
    require_once APP_ROOT . '/app/Helpers/sa-sidebar-config.php';
    require_once APP_ROOT . '/app/Helpers/sa-sidebar-badges.php';

    $sidebarConfig = getSASidebarConfig();
    $sidebarConfig = filterSASidebarByPermission($sidebarConfig);

    $tenantId = $_SESSION['tenant_id'] ?? null; // May be null for super admin
    $badges = getSASidebarBadges($tenantId);

    // Super admin branding
    $tenantName = $_SESSION['tenant_name'] ?? 'ISOFTRO Platform';
    $planName = 'Platform Admin'; // Super admins don't have plans

    // Dynamic brand color from session/tenant (use super admin color or default)
    $brandColor = $_SESSION['brand_color'] ?? '#00B894'; // Super admin green

    // User info
    $initials = generateInitials($user['name'] ?? '', 'SA');
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
            <div class="logo-txt">ISOFTRO Platform</div>
            <button class="sb-toggle" id="sbClose" aria-label="Close sidebar">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="sb-body" id="sbBody">
            <!-- Navigation rendered by sa-core.js from window._SA_NAV_CONFIG -->
        </div>

        <!-- Footer: context + desktop collapse toggle -->
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
        window._SA_NAV_CONFIG = <?php echo json_encode($sidebarConfig, JSON_UNESCAPED_UNICODE); ?>;
        window._SA_BADGES = <?php echo json_encode($badges, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <?php
}

