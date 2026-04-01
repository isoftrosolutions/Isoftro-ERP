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

<!-- ── PREMIUM SIDEBAR ── -->
<nav class="sb" id="sidebar">
    <div class="sb-body" id="sbBody">
        <!-- Navigation (rendered by JS from config) -->
    </div>
    
    <div class="sb-footer">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 32px; height: 32px; background: rgba(0, 184, 148, 0.08); color: var(--green); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px;">
                FD
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Front Desk</div>
                <div style="font-size: 10px; color: var(--text-light);">Operations</div>
            </div>
        </div>
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
