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
    <nav class="sb" id="sidebar" aria-label="Super admin navigation" aria-hidden="true">
        <div class="sb-head">
            <div class="sb-brand">
                <div class="sb-brand-name"><?php echo htmlspecialchars($tenantName); ?></div>
                <div class="sb-brand-sub"><?php echo htmlspecialchars($planName); ?></div>
            </div>
            <button class="sb-icon-btn sb-close" id="sbClose" type="button" aria-label="Close sidebar">
                <span aria-hidden="true">×</span>
            </button>
        </div>

        <div class="sb-body" id="sbBody">
            <!-- Navigation rendered by sa-core.js from window._SA_NAV_CONFIG -->
        </div>

        <!-- Footer: context + desktop collapse toggle -->
        <div class="sb-foot">
            <div class="sb-foot-meta">
                <div class="sb-foot-av" aria-hidden="true"><?php echo htmlspecialchars(substr($tenantName, 0, 2)); ?></div>
                <div class="sb-foot-txt">
                    <div class="sb-foot-name"><?php echo htmlspecialchars($tenantName); ?></div>
                    <div class="sb-foot-sub"><?php echo htmlspecialchars($planName); ?></div>
                </div>
            </div>
            <button class="sb-icon-btn sb-collapse" id="sbCollapse" type="button" aria-label="Collapse sidebar">
                <span class="sb-collapse-ic" aria-hidden="true">‹</span>
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

