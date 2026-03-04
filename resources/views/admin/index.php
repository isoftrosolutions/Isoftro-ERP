<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Institute Admin Dashboard";
$roleCSS = "ia-dashboard-new.css";
$wrapperClass = "app-layout"; // Custom wrapper for ia-dashboard-new.css layout
include VIEWS_PATH . '/layouts/header.php'; // HTML Shell (<head>, <body> open)

// Internal Admin Components
include __DIR__ . '/layouts/sidebar.php';   // Side Navigation
?>

<div class="main">
    <?php include __DIR__ . '/layouts/header.php'; // Top Navigation Bar ?>

    <!-- ── MAIN CONTENT ── -->
    <div class="content" id="mainContent">
        <!-- Rendered via js/ia-core.js -->
    </div>
</div>

<!-- Custom Scripts — Modular IA v3.1 -->
<?php $v = time(); ?>
<script src="<?php echo APP_URL; ?>/public/assets/js/pwa-handler.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/nepal-data.js?v=<?php echo $v; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Domain modules (loaded before core so render functions are available) -->
<script src="<?php echo APP_URL; ?>/public/assets/js/nexus-data-loader.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-students.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-students-v2.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/test-nexus-admission.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-academic.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-academics.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-inquiries.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-staff.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-salary.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-exams.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-settings.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-timetable.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-fees.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-attendance.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-academic-calendar.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-lms.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-study-materials.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-email-templates.js?v=<?php echo $v; ?>"></script>
<!-- Core: routing, sidebar, dashboard — must be LAST -->
<script src="<?php echo APP_URL; ?>/public/assets/js/breadcrumb.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/ia-core.js?v=<?php echo $v; ?>"></script>

<script>
    // Global config passed to JS modules
    const APP_URL = "<?php echo APP_URL; ?>";
    window.APP_URL = APP_URL;
    const CURRENT_INSTITUTE = "<?php echo isset($_SESSION['tenant_name']) ? addslashes($_SESSION['tenant_name']) : 'Institute'; ?>";
</script>

</body>
</html>
