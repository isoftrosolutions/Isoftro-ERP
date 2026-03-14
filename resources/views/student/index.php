<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Student Portal";
$themeColor = "#009E7E";
$roleCSS = "student.css";
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
                        <img src="<?php echo APP_URL; ?>/public/assets/images/logo.png" alt="Logo" style="width:100%; height:auto;">
                    </div>
                    <span class="logo-txt"><?php echo APP_NAME; ?></span>
                </div>
            </div>

            <div class="hdr-right">
                <div class="hbtn nb" title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <div class="ndot"></div>
                </div>

                <!-- Student Dropdown -->
                <div style="position:relative;">
                    <div class="u-chip" id="userChip">
                        <div class="u-av">ST</div>
                        <div style="display:flex; flex-direction:column; margin-left:8px; line-height:1.2;">
                            <span style="font-size:12px; font-weight:700;">Student</span>
                            <span style="font-size:10px; opacity:0.8;">Student Portal</span>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="font-size:9px; margin-left:6px; opacity:0.7;"></i>
                    </div>
                    
                    <div id="userDropdown" style="position:absolute; top:calc(100% + 10px); right:0; background:#fff; border:1px solid var(--card-border); border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1); min-width:200px; padding:8px; z-index:1100; visibility:hidden; opacity:0; transition:0.2s;">
                        <a href="#" onclick="goST('profile'); return false;" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-regular fa-circle-user" style="color:var(--green)"></i> My Profile</a>
                        <a href="#" onclick="goST('password'); return false;" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-key" style="color:var(--amber)"></i> Change Password</a>
                        <a href="#" onclick="goST('idcard'); return false;" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-id-card" style="color:var(--green)"></i> Digital ID Card</a>
                        <div style="height:1px; background:var(--card-border); margin:6px 0;"></div>
                        <a href="<?= APP_URL ?>/logout" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── SIDEBAR ── -->
        <nav class="sb" id="sidebar">
            <!-- Sidebar header shown only on mobile -->
            <div class="sb-header">
                <div class="hdr-logo-box" style="display:flex; align-items:center;">
                    <img src="<?php echo APP_URL; ?>/public/assets/images/logo.png" alt="Logo" style="height:28px; width:auto; margin-right:10px; filter: brightness(0) invert(1);">
                    <span class="logo-txt" style="color:#fff; font-size:14px; font-weight:800; letter-spacing:0.5px;">STUDENT</span>
                </div>
                <button class="sb-close-btn" id="sbClose" title="Close Sidebar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="sb-body" id="sbBody">
                <!-- Rendered via st-core.js -->
            </div>
        </nav>

        <!-- ── MAIN CONTENT ── -->
        <main class="main" id="mainContent">
            <!-- Rendered via st-core.js -->
        </main>

    </div>

    <!-- Custom Scripts -->
    <?php $v = time(); ?>

    <script src="<?php echo APP_URL; ?>/public/assets/js/nepal-data.js?v=<?php echo $v; ?>"></script>
    
    <!-- Student Portal Modules -->
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-dashboard.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-classes.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-attendance.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-leave.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-assignments.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-fees.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-library.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-exams.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-qbank.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-materials.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-contact.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-notices.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-leaderboard.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-profile.js?v=<?php echo $v; ?>"></script>

    <!-- Core: routing, sidebar, dashboard — must be LAST -->
    <script src="<?php echo APP_URL; ?>/public/assets/js/breadcrumb.js?v=<?php echo $v; ?>"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/st-core.js?v=<?php echo $v; ?>"></script>

    <script>
        // Dropdown Logic
        const chip = document.getElementById('userChip');
        const drop = document.getElementById('userDropdown');
        if (chip && drop) {
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
        }

        // Sidebar Toggle
        const sbToggle = document.getElementById('sbToggle');
        const sbClose = document.getElementById('sbClose');
        const sidebar = document.getElementById('sidebar');
        const sbOverlay = document.getElementById('sbOverlay');
        
        if (sbToggle && sidebar) {
            sbToggle.onclick = () => {
                document.body.classList.toggle('sb-active');
            };
        }
        if (sbClose && sidebar) {
            sbClose.onclick = () => {
                document.body.classList.remove('sb-active');
            };
        }
        if (sbOverlay) {
            sbOverlay.onclick = () => {
                document.body.classList.remove('sb-active');
            };
        }

        // Global config passed to JS modules
        const APP_URL = "<?php echo APP_URL; ?>";
        window.APP_URL = APP_URL;
        const CURRENT_INSTITUTE = "<?php echo isset($_SESSION['tenant_name']) ? addslashes($_SESSION['tenant_name']) : 'Institute'; ?>";
    </script>

</body>
</html>
