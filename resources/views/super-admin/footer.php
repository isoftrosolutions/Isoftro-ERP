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
    <?php renderSuperAdminJS(); ?>

    <script src="<?php echo SUPERADMIN_JS_PATH; ?>/breadcrumb.js?v=<?php echo SUPERADMIN_ASSETS_VERSION; ?>"></script>

    <?php renderInlineConfig(); ?>

    <!-- Module Management Modal (Global) -->
    <div id="moduleModal" class="modal-root">
        <div class="modal-card" style="max-width:500px;">
            <div class="modal-head">
                <h2 id="moduleModalTitle">Module Configuration</h2>
                <button class="modal-close" onclick="SuperAdmin.closeModal('moduleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="module_tenant_id">
                <p style="font-size:13px; color:var(--tl); margin-bottom:20px;">Select which modules should be enabled for <strong id="module_tenant_name"></strong>. Core modules cannot be disabled.</p>
                
                <div id="modulesList" style="display:flex; flex-direction:column; gap:12px;">
                    <!-- Loaded via JS -->
                </div>
            </div>
            <div class="modal-foot" style="padding: 16px 24px; border-top: 1px solid var(--cb); display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc;">
                <button type="button" class="btn bs" onclick="SuperAdmin.closeModal('moduleModal')">Cancel</button>
                <button type="button" class="btn bt" id="saveModulesBtn" onclick="SuperAdmin.saveModules()">Save Configuration</button>
            </div>
        </div>
    </div>

    <style>
    /* Global Modal Styles (If not already in core) */
    .modal-root { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 2000; visibility: hidden; opacity: 0; transition: 0.3s; }
    .modal-root.active { visibility: visible; opacity: 1; }
    .modal-card { background: #fff; width: 100%; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; transform: translateY(20px); transition: 0.3s; }
    .modal-root.active .modal-card { transform: translateY(0); }
    .modal-head { padding: 20px 24px; border-bottom: 1px solid var(--cb); display: flex; justify-content: space-between; align-items: center; }
    .modal-head h2 { font-size: 18px; font-weight: 800; margin: 0; color: var(--td); }
    .modal-close { background: none; border: none; font-size: 24px; color: var(--tl); cursor: pointer; }
    .modal-body { padding: 24px; max-height: 80vh; overflow-y: auto; }
    
    /* Switch Styles */
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; }
    input:checked + .slider { background-color: var(--sa-primary, #009E7E); }
    input:checked + .slider:before { transform: translateX(20px); }
    .slider.round { border-radius: 24px; }
    .slider.round:before { border-radius: 50%; }
    input:disabled + .slider { opacity: 0.5; cursor: not-allowed; }
    </style>
</body>
</html>
