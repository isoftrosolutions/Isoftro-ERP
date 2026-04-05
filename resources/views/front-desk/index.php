<?php
/**
 * iSoftro ERP — Front Desk Dashboard
 * Seamless Single Page Application (SPA) entry point
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Front Desk Dashboard';
$roleCSS = "ia-dashboard-new.css";
$wrapperClass = "app-layout"; 
include VIEWS_PATH . '/layouts/header.php';

// Load Sidebar Config & Badges
require_once APP_ROOT . '/app/Helpers/fd-sidebar-config.php';
$sidebarConfig = getFDSidebarConfig();
$badges = []; // We can add a function to fetch badges later

// Internal Front Desk Components
require_once VIEWS_PATH . '/components/payment-processing-modal.php';
?>

<!-- ── SIDEBAR ── -->
<?php
$_fdUser   = getCurrentUser();
$_fdName   = $_fdUser['name'] ?? 'Operator';
$_fdParts  = explode(' ', $_fdName);
$_fdInit   = strtoupper(substr($_fdParts[0], 0, 1) . (isset($_fdParts[1]) ? substr($_fdParts[1], 0, 1) : ''));
$_fdTenant = $_SESSION['tenant_name'] ?? 'iSoftro ERP';
?>
<nav class="sb" id="sidebar" aria-label="Front Desk navigation">
    <!-- Mobile-only header -->
    <div class="sb-header">
        <div class="hdr-logo-box" style="display:flex; align-items:center;">
            <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Logo" style="height:28px; width:auto; margin-right:10px; filter: brightness(0) invert(1);">
            <span style="color:#fff; font-size:14px; font-weight:800; letter-spacing:0.5px;">FRONT DESK</span>
        </div>
        <button class="sb-toggle" id="sbClose" aria-label="Close sidebar">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sb-body" id="sbBody">
        <!-- Navigation rendered by frontdesk.js -->
    </div>

    <!-- Footer: operator context + desktop collapse -->
    <div class="sb-footer">
        <div class="sb-footer-inner">
            <div class="sb-tenant-av" aria-hidden="true"><?php echo htmlspecialchars($_fdInit); ?></div>
            <div class="sb-footer-text">
                <div class="sb-tenant-name"><?php echo htmlspecialchars($_fdName); ?></div>
                <div class="sb-tenant-plan">Front Desk · <?php echo htmlspecialchars($_fdTenant); ?></div>
            </div>
        </div>
        <button class="js-sidebar-toggle sb-collapse-btn" aria-label="Toggle sidebar">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
    </div>
</nav>

<div class="main">
    <?php 
    // Top Navigation (Premium Admin Header)
    include __DIR__ . '/../admin/layouts/header.php'; 
    ?>

    <!-- ── MAIN CONTENT (AJAX TARGET) ── -->
    <main class="content" id="mainContent">
        <div class="pg-loading">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            <span>Initializing Operations...</span>
        </div>
    </main>
</div>

<!-- Inject Sidebar Config -->
<script>
    window._IA_NAV_CONFIG = <?php echo json_encode($sidebarConfig, JSON_UNESCAPED_UNICODE); ?>;
    // Overriding the default NAV in frontdesk.js if it uses it differently
</script>


<script src="<?php echo APP_URL; ?>/assets/js/nepal-data.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/nexus-data-loader.js?v=1.1"></script>
<!-- Front Desk Domain Modules -->
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-students.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-attendance.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-fees.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-inquiries.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-academic.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-academics.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-exams.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-qbank.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-homework.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-timetable.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-audit-logs.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-support.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-settings.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-staff.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-salary.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk/fd-study-materials.js?v=1.1"></script>

<!-- Main SPA Shell -->
<script src="<?php echo APP_URL; ?>/assets/js/frontdesk.js?v=1.1"></script>
<style>
/* Dashboard Shell specific optimizations */
.pg-loading {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 60vh; color: #94a3b8; gap: 16px;
}
.pg-loading i { font-size: 32px; color: var(--green); }
</style>
?>
</body>
</html>
<?php exit; // End of shell ?>
