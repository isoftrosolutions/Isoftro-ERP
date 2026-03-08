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
                    <span class="logo-txt">Hamro ERP</span>
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
                        <div class="u-av">SK</div>
                        <div style="display:flex; flex-direction:column; margin-left:8px; line-height:1.2;">
                            <span style="font-size:12px; font-weight:700;">Suman Karki</span>
                            <span style="font-size:10px; opacity:0.8;">HL-KH-047 (Student)</span>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="font-size:9px; margin-left:6px; opacity:0.7;"></i>
                    </div>
                    
                    <div id="userDropdown" style="position:absolute; top:calc(100% + 10px); right:0; background:#fff; border:1px solid var(--card-border); border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1); min-width:200px; padding:8px; z-index:1100; visibility:hidden; opacity:0; transition:0.2s;">
                        <a href="student-profile-view.php" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-regular fa-circle-user" style="color:var(--green)"></i> My Profile</a>
                        <a href="student-change-password.php" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-key" style="color:var(--amber)"></i> Change Password</a>
                        <a href="student-id-card-view.php" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-id-card" style="color:var(--green)"></i> Digital ID Card</a>
                        <div style="height:1px; background:var(--card-border); margin:6px 0;"></div>
                        <a href="../../index.php?logout=1" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
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
                <!-- Rendered via js/student.js -->
            </div>
        </nav>

        <!-- ── MAIN CONTENT ── -->
        <main class="main" id="mainContent">
            <!-- Rendered via js/student.js -->
        </main>

    </div>



        <!-- Custom Scripts -->
    <script src="<?php echo APP_URL; ?>/public/assets/js/pwa-handler.js"></script>
    <script src="<?= APP_URL ?>/public/assets/js/student-lms.js"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/student.js"></script>
    <script src="<?php echo APP_URL; ?>/public/assets/js/breadcrumb.js"></script>
    <script>
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
