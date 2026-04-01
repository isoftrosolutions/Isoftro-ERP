<?php
/**
 * ISOFTRO - Super Admin Dashboard
 * Main entry point for platform management
 */

requireAuth();
if (getCurrentUser()['role'] !== 'superadmin') {
    abort(403);
}

require_once __DIR__ . '/sidebar.php';

$PDO = getDBConnection();

// Get initial stats (Server-side rendering for first load)
$stmt = $PDO->query("SELECT COUNT(*) FROM tenants");
$totalTenants = $stmt->fetchColumn();

$stmt = $PDO->query("SELECT COUNT(*) FROM users WHERE role != 'superadmin' AND status = 'active'");
$totalStudents = $stmt->fetchColumn(); // In this context, probably all non-admin users

$stmt = $PDO->query("SELECT COALESCE(SUM(amount), 0) FROM tenant_payments WHERE status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$monthlyRevenue = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISOFTRO - Super Admin Dashboard</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/super-admin.css">
    
    <!-- Global Config -->
    <script>
        window.APP_URL = "<?= APP_URL ?>";
    </script>

    <!-- CSRF Protection -->
    <?= \App\Helpers\CsrfHelper::csrfMetaTag() ?>
    <?= \App\Helpers\CsrfHelper::csrfJsHeader() ?>

</head>
<body>
    <div class="root">
        <!-- HEADER -->
        <header class="hdr">
            <div class="hdr-left">
                <button class="sb-toggle" id="sbToggle">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <div class="hdr-logo-box">
                    <img src="<?= APP_URL ?>/assets/images/logo.png" alt="ISOFTRO" style="height: 24px; filter: brightness(0) invert(1);">
                    <span style="font-weight: 800; letter-spacing: -0.5px;">PLATFORM OWNER</span>
                </div>
                
                <!-- Global Platform Search -->
                <div class="hdr-search-box" style="margin-left: 20px; position: relative; display: none; /* Tablet+ only */">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); opacity: 0.5;"></i>
                    <input type="text" placeholder="Global Platform Search..." style="padding: 8px 12px 8px 36px; border-radius: 20px; border: none; background: rgba(255,255,255,0.15); color: #fff; font-size: 13px; width: 280px; outline: none;">
                </div>
            </div>
            
            <div class="hdr-right">
                <!-- System Alerts Badge -->
                <button class="hbtn nb" id="notifChip">
                    <i class="fas fa-bell"></i>
                    <span class="nbadge">12</span>
                </button>
                
                <!-- Admin Profile Dropdown -->
                <div style="position: relative;">
                    <div class="hdr-user" id="userChip" style="cursor: pointer; display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 25px;">
                        <div class="hdr-av" style="width: 28px; height: 28px; background: var(--green-d, #007a62); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid rgba(255,255,255,0.2);">
                            <?= strtoupper(substr(getCurrentUser()['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <span class="hdr-uname" style="font-size: 11px; font-weight: 700; line-height: 1;"><?= htmlspecialchars(getCurrentUser()['name']) ?></span>
                            <span style="font-size: 8px; opacity: 0.8; font-weight: 600;">PLATFORM ADMIN</span>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size: 10px; opacity: 0.5;"></i>
                    </div>
                    
                    <!-- Dropdown Menu -->
                    <div class="u-dd" id="userDropdown" style="position: absolute; top: calc(100% + 10px); right: 0; background: #fff; border-radius: 12px; padding: 10px; min-width: 200px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; display: none; z-index: 1000;">
                        <a href="#" class="dd-item" style="display: flex; align-items: center; gap: 10px; padding: 10px; font-size: 13px; color: #475569; text-decoration: none; border-radius: 8px; transition: 0.2s;">
                            <i class="fas fa-user-circle"></i> My Profile
                        </a>
                        <a href="#" class="dd-item" style="display: flex; align-items: center; gap: 10px; padding: 10px; font-size: 13px; color: #475569; text-decoration: none; border-radius: 8px; transition: 0.2s;">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <a href="#" class="dd-item" style="display: flex; align-items: center; gap: 10px; padding: 10px; font-size: 13px; color: #475569; text-decoration: none; border-radius: 8px; transition: 0.2s;">
                            <i class="fas fa-fingerprint"></i> Activity Log
                        </a>
                        <div style="height: 1px; background: #f1f5f9; margin: 8px 0;"></div>
                        <a href="<?= APP_URL ?>/logout.php" class="dd-item" style="display: flex; align-items: center; gap: 10px; padding: 10px; font-size: 13px; color: #ef4444; text-decoration: none; border-radius: 8px; transition: 0.2s;">
                            <i class="fas fa-sign-out-alt"></i> Logout Platform
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <style>
            @media (min-width: 1024px) {
                .hdr-search-box { display: block !important; }
            }
            .dd-item:hover { background: #f1f5f9; color: var(--green) !important; }
        </style>

        <!-- SIDEBAR OVERLAY -->
        <div class="sb-overlay" id="sbOverlay"></div>

        <!-- SIDEBAR -->
        <?php renderSuperAdminSidebar(); ?>

        <!-- MAIN CONTENT -->
        <main class="main" id="mainContent">
            <!-- Content will be loaded via AJAX (sa-core.js) -->
            <!-- Placeholder for initial load -->
            <div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--green);"></i>
            </div>
        </main>
    </div>

     <!-- Scripts -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <script src="<?= APP_URL ?>/assets/js/sa-core.js"></script>
     <script src="<?= APP_URL ?>/assets/js/sa-sidebar.js"></script>
     <script src="<?= APP_URL ?>/assets/js/sa-pages.js"></script>
</body>
</html>
