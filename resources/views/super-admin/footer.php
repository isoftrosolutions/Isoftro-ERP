<?php
/**
 * Hamro ERP — Super Admin Footer
 * Closes the .root wrapper, loads JS, ends document.
 */

if (isset($_GET['partial']) && $_GET['partial'] == 'true') {
    return;
}

function renderInlineConfig() {
    global $pageTitle;
    echo '    <script>' . "\n";
    echo '        const CURRENT_PAGE = "' . htmlspecialchars($pageTitle ?? 'Dashboard') . '";' . "\n";
    echo '        const USER_ROLE = "superadmin";' . "\n";
    echo '    </script>' . "\n";
}
?>
    </div><!-- /.root -->

    <!-- ── MOBILE BOTTOM NAV (Super Admin specific) ── -->
    <nav class="mob-nav">
        <a href="<?php echo APP_URL; ?>/dash/super-admin/index" class="mn-item active">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo APP_URL; ?>/dash/super-admin/tenant-management" class="mn-item">
            <i class="fa-solid fa-building"></i>
            <span>Tenants</span>
        </a>
        <a href="<?php echo APP_URL; ?>/dash/super-admin/revenue-analytics" class="mn-item">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>Revenue</span>
        </a>
        <a href="<?php echo APP_URL; ?>/dash/super-admin/support-tickets" class="mn-item">
            <i class="fa-solid fa-ticket"></i>
            <span>Support</span>
        </a>
        <button class="mn-item" onclick="document.getElementById('sbToggle').click()">
            <i class="fa-solid fa-bars"></i>
            <span>More</span>
        </button>
    </nav>

    <!-- CDN Scripts (SweetAlert2, Chart.js) -->
    <?php renderExternalScripts(); ?>

    <!-- App JS -->
    <script src="<?php echo SUPERADMIN_JS_PATH; ?>/pwa-handler.js?v=<?php echo SUPERADMIN_ASSETS_VERSION; ?>"></script>
    <script src="<?php echo SUPERADMIN_JS_PATH; ?>/breadcrumb.js?v=<?php echo SUPERADMIN_ASSETS_VERSION; ?>"></script>

    <?php renderInlineConfig(); ?>
</body>
</html>
