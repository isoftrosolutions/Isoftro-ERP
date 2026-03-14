<?php
/**
 * Hamro ERP — Front Desk Dashboard
 * Seamless Single Page Application (SPA) entry point
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Front Desk Dashboard';
include VIEWS_PATH . '/layouts/header.php';
require_once __DIR__ . '/sidebar.php';

// Render base layout
renderFrontDeskHeader();
renderFrontDeskSidebar('index');
?>

<link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/frontdesk.css?v=1.1">

<!-- ── MAIN CONTENT Shell ── -->
<main class="main" id="mainContent">
    <div class="pg fu">
        <div class="pg-loading">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            <span>Initializing Operations...</span>
        </div>
    </div>
</main>

<script src="<?php echo APP_URL; ?>/public/assets/js/nepal-data.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/nexus-data-loader.js?v=1.1"></script>
<!-- Front Desk Domain Modules -->
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-students.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-attendance.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-fees.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-inquiries.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-academic.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-academics.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-exams.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-qbank.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-homework.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-timetable.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-audit-logs.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-support.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-settings.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-staff.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-salary.js?v=1.1"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk/fd-study-materials.js?v=1.1"></script>

<!-- Main SPA Shell -->
<script src="<?php echo APP_URL; ?>/public/assets/js/frontdesk.js?v=1.1"></script>
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
