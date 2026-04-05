<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Guardian Portal";
$themeColor = "#E11D48";
$roleCSS = "guardian.css";
include VIEWS_PATH . '/layouts/header.php';
?>

    <!-- Sidebar Overlay -->
    <div class="sb-overlay" id="sbOverlay"></div>

    <div class="root">

        <!-- ── HEADER ── -->
        <header class="hdr">
            <div class="hdr-left">
                <button class="sb-toggle" id="sbToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="hdr-logo-box">
                    <div style="width:36px; height:36px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#fff; margin-right:10px;">
                        <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Logo" style="width:100%; height:auto;">
                    </div>
                    <span class="logo-txt">Hamro ERP</span>
                </div>
            </div>

            <div class="hdr-right">
                <div class="hbtn nb" title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <div class="ndot"></div>
                </div>

                <!-- Guardian Profile Dropdown -->
                <div style="position:relative;">
                    <div class="u-chip" id="userChip">
                        <?php 
                        $uName = $_SESSION['userData']['name'] ?? 'Guardian';
                        $initials = strtoupper(substr($uName, 0, 2));
                        ?>
                        <div class="u-av"><?= $initials ?></div>
                        <div style="display:flex; flex-direction:column; margin-left:8px; line-height:1.2;">
                            <span style="font-size:12px; font-weight:700;"><?= htmlspecialchars($uName) ?></span>
                            <span style="font-size:10px; opacity:0.8;">Guardian</span>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="font-size:9px; margin-left:6px; opacity:0.7;"></i>
                    </div>
                    
                    <div id="userDropdown" style="position:absolute; top:calc(100% + 10px); right:0; background:#fff; border:1px solid var(--card-border); border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1); min-width:200px; padding:8px; z-index:1100; visibility:hidden; opacity:0; transition:0.2s;">
                        <a href="#" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-regular fa-circle-user" style="color:var(--green)"></i> My Profile</a>
                        <a href="#" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-child" style="color:var(--teal)"></i> Student Profile</a>
                        <div style="height:1px; background:var(--card-border); margin:6px 0;"></div>
                        <a href="<?= APP_URL ?>/logout.php" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── SIDEBAR ── -->
        <nav class="sb" id="sidebar" aria-label="Guardian navigation">
            <!-- Mobile-only header -->
            <div class="sb-header">
                <div class="hdr-logo-box" style="display:flex; align-items:center;">
                    <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Logo" style="height:28px; width:auto; margin-right:10px; filter: brightness(0) invert(1);">
                    <span class="logo-txt" style="color:#fff; font-size:14px; font-weight:800; letter-spacing:0.5px;">GUARDIAN</span>
                </div>
                <button class="sb-toggle" id="sbClose" aria-label="Close sidebar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div class="sb-body" id="sbBody">
                <!-- Rendered via guardian.js -->
            </div>

            <!-- Footer: user context + desktop collapse -->
            <?php
            $_gName   = $_SESSION['userData']['name'] ?? 'Guardian';
            $_gParts  = explode(' ', $_gName);
            $_gInit   = strtoupper(substr($_gParts[0], 0, 1) . (isset($_gParts[1]) ? substr($_gParts[1], 0, 1) : ''));
            $_gTenant = $_SESSION['tenant_name'] ?? 'iSoftro ERP';
            ?>
            <div class="sb-footer">
                <div class="sb-footer-inner">
                    <div class="sb-tenant-av" aria-hidden="true"><?php echo htmlspecialchars($_gInit); ?></div>
                    <div class="sb-footer-text">
                        <div class="sb-tenant-name"><?php echo htmlspecialchars($_gName); ?></div>
                        <div class="sb-tenant-plan">Guardian · <?php echo htmlspecialchars($_gTenant); ?></div>
                    </div>
                </div>
                <button class="js-sidebar-toggle sb-collapse-btn" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>
            </div>
        </nav>

        <!-- ── MAIN CONTENT ── -->
        <main class="main" id="mainContent">
            <!-- Rendered via js/guardian.js -->
        </main>

    </div>



        <!-- Custom Scripts -->

    <script src="<?php echo APP_URL; ?>/assets/js/guardian.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/breadcrumb.js"></script>
    <script>
        const sbClose = document.getElementById('sbClose');
        if(sbClose) sbClose.onclick = () => document.body.classList.remove('sb-active');
        
        // Dropdown Logic
        const chip = document.getElementById('userChip');
        const drop = document.getElementById('userDropdown');
        chip.onclick = (e) => {
            e.stopPropagation();
            const isVisible = drop.style.visibility === 'visible';
            drop.style.visibility = isVisible ? 'hidden' : 'visible';
            drop.style.opacity = isVisible ? '0' : '1';
        };
        document.onclick = () => {
            drop.style.visibility = 'hidden';
            drop.style.opacity = '0';
        };
    </script>

</body>
</html>
