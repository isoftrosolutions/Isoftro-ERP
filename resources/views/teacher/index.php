<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Teacher Portal";
$themeColor = "#00B894";
$roleCSS = "teacher.css";
include VIEWS_PATH . '/layouts/header.php';
?>

        <!-- ── HEADER ── -->
        <header class="hdr">
            <div class="hdr-left">
                <button class="sb-toggle" id="sbToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="hdr-logo-box">
                    <div style="width:38px; height:38px; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#fff; margin-right:10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Logo" style="width:100%; height:auto;">
                    </div>
                    <span class="logo-txt"><?php echo APP_NAME; ?></span>
                </div>
            </div>

            <div class="hdr-right">
                <div class="hbtn" title="Global Search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                
                <div class="hbtn nb" title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <div class="ndot"></div>
                </div>

                <!-- Teacher Dropdown -->
                <div style="position:relative;">
                    <div class="u-chip" id="userChip">
                        <?php 
                        $uName = $_SESSION['userData']['name'] ?? 'Teacher';
                        $initials = strtoupper(substr($uName, 0, 2));
                        ?>
                        <div class="u-av"><?= $initials ?></div>
                        <div style="display:flex; flex-direction:column; margin-left:8px; line-height:1.2;">
                            <span style="font-size:12px; font-weight:700;"><?= htmlspecialchars($uName) ?></span>
                            <span style="font-size:10px; opacity:0.8;">Teacher</span>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="font-size:9px; margin-left:6px; opacity:0.7;"></i>
                    </div>
                    
                    <div id="userDropdown" style="position:absolute; top:calc(100% + 10px); right:0; background:#fff; border:1px solid var(--card-border); border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1); min-width:200px; padding:8px; z-index:1100; visibility:hidden; opacity:0; transition:0.2s;">
                        <a href="javascript:void(0)" onclick="goNav('profile', 'personal')" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-regular fa-circle-user" style="color:var(--green)"></i> My Profile</a>
                        <a href="javascript:void(0)" onclick="goNav('profile', 'leave-history')" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-calendar-plus" style="color:var(--amber)"></i> Leave Application</a>
                        <a href="javascript:void(0)" onclick="goNav('profile', 'salary-slips')" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--text-dark); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-wallet" style="color:var(--green)"></i> Salary Slips</a>
                        <div style="height:1px; background:var(--card-border); margin:6px 0;"></div>
                        <a href="/logout.php" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── SIDEBAR ── -->
        <nav class="sb" id="sidebar">
            <!-- Sidebar header shown only on mobile -->
            <div class="sb-header">
                <div class="hdr-logo-box" style="display:flex; align-items:center;">
                    <img src="<?php echo APP_URL; ?>/assets/images/logo.png" alt="Logo" style="height:28px; width:auto; margin-right:10px; filter: brightness(0) invert(1);">
                    <span class="logo-txt" style="color:#fff; font-size:14px; font-weight:800; letter-spacing:0.5px;">TEACHER</span>
                </div>
                <button class="sb-close-btn" id="sbClose" title="Close Sidebar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="sb-body" id="sbBody">
                <!-- Rendered via js/teacher-updated.js -->
            </div>
        </nav>

        <!-- ── MAIN CONTENT ── -->
        <main class="main" id="mainContent">
            <!-- Rendered via js/teacher-updated.js -->
        </main>

    </div>
    <!-- Custom Scripts -->

    <script src="<?php echo APP_URL; ?>/assets/js/ia-study-materials.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/ia-qbank.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/ia-lms.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/ia-homework.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/teacher-portal.js"></script>
    <script>
        (function() {
            const chip = document.getElementById('userChip');
            const drop = document.getElementById('userDropdown');
            if (chip && drop) {
                chip.onclick = (e) => {
                    e.stopPropagation();
                    const isVisible = drop.style.visibility === 'visible';
                    drop.style.visibility = isVisible ? 'hidden' : 'visible';
                    drop.style.opacity = isVisible ? '0' : '1';
                };
                document.addEventListener('click', () => {
                    drop.style.visibility = 'hidden';
                    drop.style.opacity = '0';
                });
            }
        })();
    </script>

</body>
</html>
