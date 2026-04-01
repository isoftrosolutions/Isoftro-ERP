<?php
/**
 * Hamro ERP — Super Admin Header Module
 * Refactored to match Institute Admin header structure & class names.
 */

// Include sidebar component
// Sidebars are now included by the view files directly or handled via include_path


// Module Configuration
define('SUPERADMIN_ASSETS_VERSION', '1.0.0');
define('SUPERADMIN_THEME_COLOR', '#009E7E');
define('SUPERADMIN_CSS_PATH', APP_URL . '/assets/css');
define('SUPERADMIN_JS_PATH', APP_URL . '/assets/js');


// Fallback values
$pageTitle    = $pageTitle    ?? APP_NAME;
$themeColor   = $themeColor   ?? SUPERADMIN_THEME_COLOR;
$bodyClass    = $bodyClass    ?? '';           // no role-scoping class needed
$wrapperClass = $wrapperClass ?? 'root';      // matches institute-admin .root

/**
 * CSS assets — super_admin.css is now the sole role stylesheet
 */
function getSuperAdminCSS() {
    return [
        'core'       => SUPERADMIN_CSS_PATH . '/core.css',
        'layout'     => SUPERADMIN_CSS_PATH . '/layout.css',
        'components' => SUPERADMIN_CSS_PATH . '/components.css',
        'utilities'  => SUPERADMIN_CSS_PATH . '/utilities.css',
        'superadmin' => SUPERADMIN_CSS_PATH . '/super_admin.css',
    ];
}

/**
 * JS assets
 * Note: super_admin.js handles all navigation - do NOT load script.js as it conflicts
 */
function getSuperAdminJS() {
    return [
        'sa-core'      => SUPERADMIN_JS_PATH . '/sa-core.js',
        'sa-dash'      => SUPERADMIN_JS_PATH . '/sa-dashboard.js',
        'sa-tenants'   => SUPERADMIN_JS_PATH . '/sa-tenants.js',
        'sa-plans'     => SUPERADMIN_JS_PATH . '/sa-plans.js',
        'sa-revenue'   => SUPERADMIN_JS_PATH . '/sa-revenue.js',
        'sa-analytics' => SUPERADMIN_JS_PATH . '/sa-analytics.js',
        'sa-support'   => SUPERADMIN_JS_PATH . '/sa-support.js',
        'sa-system'    => SUPERADMIN_JS_PATH . '/sa-system.js',
    ];
}

/**
 * External CDN resources
 */
function getExternalResources() {
    return [
        'fonts' => [
            'url'  => 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap',
            'type' => 'stylesheet'
        ],
        'icons' => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
            'type' => 'stylesheet'
        ],
        'sweetalert2' => [
            'url'  => 'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            'type' => 'script'
        ],
        'bootstrap' => [
            'url'  => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'type' => 'stylesheet'
        ],
        'chartjs' => [
            'url'  => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js',
            'type' => 'script'
        ],
    ];
}

if (!function_exists('renderExternalStyles')) {
    function renderExternalStyles() {
        foreach (getExternalResources() as $r) {
            if ($r['type'] === 'stylesheet') {
                echo '<link rel="stylesheet" href="' . $r['url'] . '">' . "\n";
            }
        }
    }
}

if (!function_exists('renderExternalScripts')) {
    function renderExternalScripts() {
        foreach (getExternalResources() as $r) {
            if ($r['type'] === 'script') {
                echo '<script src="' . $r['url'] . '"></script>' . "\n";
            }
        }
    }
}

if (!function_exists('renderSuperAdminCSS')) {
    function renderSuperAdminCSS() {
        foreach (getSuperAdminCSS() as $name => $path) {
            echo '<link rel="stylesheet" href="' . $path . '?v=' . SUPERADMIN_ASSETS_VERSION . '">' . "\n";
        }
    }
}

if (!function_exists('renderSuperAdminJS')) {
    function renderSuperAdminJS() {
        foreach (getSuperAdminJS() as $name => $path) {
            echo '<script src="' . $path . '?v=' . SUPERADMIN_ASSETS_VERSION . '"></script>' . "\n";
        }
    }
}

function renderPWAMeta() {
    global $themeColor;
    echo '    <link rel="manifest" href="' . APP_URL . '/manifest.json">' . "\n";
    echo '    <meta name="theme-color" content="' . $themeColor . '">' . "\n";
    echo '    <link rel="icon" type="image/svg+xml" href="' . APP_URL . '/assets/images/favicon.svg">' . "\n";
    echo '    <link rel="apple-touch-icon" href="' . APP_URL . '/assets/images/logo.png">' . "\n";
    echo '    <meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '    <meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
    echo '    <meta name="apple-mobile-web-app-title" content="iSoftro ERP">' . "\n";
}

function renderAppConfig() {
    echo '    <script>' . "\n";
    echo '        window.APP_URL = "' . APP_URL . '";' . "\n";
    echo '        window.SA_THEME = "' . SUPERADMIN_THEME_COLOR . '";' . "\n";
    echo '        window.JWT_STATE = true;' . "\n";
    echo '    </script>' . "\n";
}

/**
 * Render the top header bar — mirrors institute-admin .hdr structure exactly.
 */
function renderSuperAdminHeader() {
    if (isset($_GET['partial']) && $_GET['partial'] == 'true') {
        return;
    }
    global $pageTitle;

    $user         = $_SESSION['userData'] ?? null;
    $userName     = $user['name']  ?? 'System Admin';
    $userEmail    = $user['email'] ?? 'admin@hamrolabs.com';
    $nameParts    = explode(' ', trim($userName));
    $userInitials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
    $userRole     = 'Super Admin';

    $notifications = [];
    $unreadCount = 0;
    try {
        if (function_exists('getDBConnection')) {
            $db = getDBConnection();
            $stmt = $db->query("SELECT * FROM notify_sup_admin ORDER BY created_at DESC LIMIT 5");
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $unreadStmt = $db->query("SELECT COUNT(*) as count FROM notify_sup_admin WHERE is_read = 0");
            $unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
    } catch (Exception $e) {
        // Silently fail if DB issues
    }
    ?>
    <!-- ── HEADER (same structure as institute-admin .hdr) ── -->
    <header class="hdr">
        <div class="hdr-left">
            <!-- Sidebar toggle — same class as institute-admin -->
            <button class="sb-toggle" id="sbToggle" title="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="hdr-logo-box">
                <div class="logo-mark">
                    <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Logo" style="height:24px; width:auto;">
                </div>
                <div class="logo-stack">
                    <div class="logo-txt"><?php echo APP_NAME; ?></div>
                    <div class="logo-sub">Platform</div>
                </div>
            </div>
        </div>

        <!-- Page indicator — desktop only (institute-admin equivalent of .hdr-inst) -->
        <div class="hdr-center d-none-mob">
            <div class="page-indicator">
                <span class="indicator-label">Active Module</span>
                <span class="indicator-val"><?php echo htmlspecialchars($pageTitle); ?></span>
            </div>
        </div>

        <div class="hdr-right">
            <!-- Global Platform Search — desktop only -->
            <div class="hdr-search d-none-mob">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Global Platform Search...">
            </div>

            <!-- System health indicator -->
            <div class="hbtn" title="System Health">
                <i class="fa-solid fa-heart-pulse"></i>
                <div class="badge-dot success"></div>
            </div>

            <!-- System Alerts Badge -->
            <div class="dd-wrap">
                <div class="hbtn nb" title="System Alerts" id="notifChip">
                    <i class="fa-solid fa-bell"></i>
                    <?php if($unreadCount > 0): ?>
                    <div class="nbadge"><?php echo $unreadCount; ?></div>
                    <?php endif; ?>
                </div>
                <div class="u-dd" id="notifDropdown" style="width: 320px; right: 0; left: auto; padding-top:0;">
                    <div class="dd-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div class="u-name">Notifications</div>
                        <a href="<?php echo APP_URL; ?>/dash/super-admin/notifications" style="font-size:11px; color:var(--sa-primary); text-decoration:none;">View All</a>
                    </div>
                    <div style="max-height: 300px; overflow-y: auto;">
                    <?php if(empty($notifications)): ?>
                        <div style="padding: 16px; text-align: center; color: var(--text-light); font-size: 13px;">No notifications.</div>
                    <?php else: ?>
                        <?php foreach($notifications as $n): 
                            $icon = 'fa-bell';
                            $color = 'var(--text-light)';
                            if($n['type'] == 'security') { $icon = 'fa-shield-virus'; $color = 'var(--red)'; }
                            elseif($n['type'] == 'signup') { $icon = 'fa-user-plus'; $color = 'var(--green)'; }
                            elseif($n['type'] == 'alert') { $icon = 'fa-triangle-exclamation'; $color = 'var(--amber)'; }
                            elseif($n['type'] == 'payment') { $icon = 'fa-file-invoice-dollar'; $color = 'var(--blue)'; }
                        ?>
                        <div class="dd-item" style="align-items: flex-start; gap: 12px; padding: 12px; border-bottom:1px solid var(--border-color); <?php echo $n['is_read'] ? 'opacity:0.7;' : 'background: #f8fafc;'; ?>" onclick="window.location.href='<?php echo $n['link'] ? htmlspecialchars($n['link']) : '#'; ?>'">
                            <div style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; background: color-mix(in srgb, <?php echo $color; ?> 15%, transparent); color: <?php echo $color; ?>; flex-shrink:0;">
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size: 13px; font-weight: <?php echo $n['is_read'] ? '600' : '700'; ?>; color: var(--text-dark); margin-bottom: 2px;"><?php echo htmlspecialchars($n['title']); ?></div>
                                <div style="font-size: 11px; color: var(--text-light); line-height: 1.4;"><?php echo htmlspecialchars($n['message']); ?></div>
                                <div style="font-size: 10px; color: var(--text-light); margin-top: 4px;"><?php echo date('M d, H:i', strtotime($n['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                    <?php if($unreadCount > 0): ?>
                    <div class="dd-divider" style="margin:0;"></div>
                    <a href="#" class="dd-item" style="justify-content:center; color:var(--text-light); font-size:12px; padding: 8px;">Mark all as read</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User chip + dropdown — same structure as institute-admin -->
            <div class="dd-wrap">
                <div class="u-chip" id="userChip">
                    <div class="u-av"><?php echo $userInitials; ?></div>
                    <div class="u-info d-none-mob">
                        <div class="u-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="u-role"><?php echo $userRole; ?></div>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size:9px; margin-left:2px; opacity:0.7;"></i>
                </div>
                <div class="u-dd" id="userDropdown">
                    <div class="dd-header">
                        <div class="u-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="u-email"><?php echo htmlspecialchars($userEmail); ?></div>
                    </div>
                    <a href="<?php echo APP_URL; ?>/dash/super-admin/profile"      class="dd-item"><i class="fa-regular fa-circle-user"></i> My Profile</a>
                    <a href="<?php echo APP_URL; ?>/dash/super-admin/profile?tab=password" class="dd-item"><i class="fa-solid fa-key"></i> Change Password</a>
                    <a href="<?php echo APP_URL; ?>/dash/super-admin/activity-log" class="dd-item"><i class="fa-solid fa-clock-rotate-left"></i> Activity Log</a>
                    <div class="dd-divider"></div>
                    <a href="<?= APP_URL ?>/logout.php" class="dd-item danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    <?php
}
?>
<?php
if (isset($_GET['partial']) && $_GET['partial'] == 'true') {
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | <?php echo htmlspecialchars($pageTitle); ?> — Super Admin</title>

    <!-- External Resources -->
    <?php renderExternalStyles(); ?>

    <?php
        // Load Vite Assets
        require_once __DIR__ . '/../../../app/Support/ViteAsset.php';
        echo \App\Support\ViteAsset::tags(['resources/css/app.scss', 'resources/js/app.js']);
    ?>

    <!-- Super Admin CSS -->
    <?php renderSuperAdminCSS(); ?>


    <!-- PWA Settings -->
    <?php renderPWAMeta(); ?>

    <!-- App Configuration -->
    <?php renderAppConfig(); ?>
    
    <!-- CSRF disabled - Using JWT for security -->
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <?php if (isset($_SESSION['impersonating']) && $_SESSION['impersonating']): ?>
    <!-- Impersonation Alert Bar -->
    <div style="background: var(--red); color: #fff; padding: 10px 20px; text-align: center; font-size: 13px; font-weight: 700; position: sticky; top: 0; z-index: 9999; display: flex; justify-content: center; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <span><i class="fa-solid fa-user-secret" style="margin-right: 8px;"></i> You are currently impersonating <strong><?php echo htmlspecialchars($_SESSION['userData']['name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['userData']['email']); ?>)</span>
        <a href="<?php echo APP_URL; ?>/dash/super-admin/stop-impersonating" style="background: rgba(255,255,255,0.2); color: #fff; text-decoration: none; padding: 4px 12px; border-radius: 4px; font-size: 11px; text-transform: uppercase; transition: 0.2s; border: 1px solid rgba(255,255,255,0.4);" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">End Session</a>
    </div>
    <?php endif; ?>

    <!-- Overlay (mobile sidebar backdrop) -->
    <div class="sb-overlay" id="sbOverlay"></div>
    <div class="<?php echo htmlspecialchars($wrapperClass); ?>">
