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

    // ── Load config & badges ──
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
    $initials = 'SA';
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
    }
    else {
        $logoPath = APP_URL . '/assets/images/logo.png';
    }
    ?>
    <!-- Dynamic Brand Color -->
    <style>
        :root {
            --brand: <?php echo htmlspecialchars($brandColor); ?>;
        }
    </style>

        <!-- ── SIDEBAR (mirrors institute-admin structure) ── -->
        <nav class="sb" id="sidebar">
            <!-- Mobile-only header inside sidebar -->
            <div class="sb-header">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" style="height:28px; width:auto; margin-right:10px; filter: brightness(0) invert(1);">
                <div class="logo-txt">ISOFTRO Platform</div>
                <button class="sb-toggle" id="sbClose">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="sb-body" id="sbBody">
                <!-- Navigation (rendered by JS from config) -->
            </div>

            <!-- System Branding / Context (Desktop only, subtle) -->
            <div class="sb-footer" style="padding: 15px 20px; border-top: 1px solid var(--cb); margin-top: auto;">
                 <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: var(--teal-lt); color: var(--teal); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px;">
                        <?php echo htmlspecialchars(substr($tenantName, 0, 2)); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 12px; font-weight: 700; color: var(--td); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($tenantName); ?></div>
                        <div style="font-size: 10px; color: var(--tl);"><?php echo htmlspecialchars($planName); ?></div>
                    </div>
                 </div>
            </div>
        </nav>

    <!-- Inject sidebar config as JSON for JS consumption -->
    <script>
        window._SA_NAV_CONFIG = <?php echo json_encode($sidebarConfig, JSON_UNESCAPED_UNICODE); ?>;
        window._SA_BADGES = <?php echo json_encode($badges, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <?php
}

