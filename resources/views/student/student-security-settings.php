<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Security Settings - Student";
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
                    <div style="width:28px; height:28px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#fff;">
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
                        <a href="<?= APP_URL ?>/logout" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ── SIDEBAR ── -->
        <nav class="sb" id="sidebar">
            <!-- Sidebar header shown only on mobile -->
            <div class="sb-header">
                <div class="hdr-logo-box">
                    <span class="logo-txt">Student Portal</span>
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
            <!-- Main Content - Security Settings -->
            <div class="pg">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="student.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="student-profile-view.php">My Profile</a>
                    <i class="fas fa-chevron-right"></i>
                    <span class="bc-cur">Security Settings</span>
                </div>

                <!-- Page Header -->
                <div class="pg-hdr">
                    <div>
                        <h1>Security Settings</h1>
                        <p>Manage your account security preferences</p>
                    </div>
                </div>

                <!-- Security Content -->
                <div class="g65">
                    <!-- Left Column - Security Options -->
                    <div>
                        <!-- Two-Factor Authentication Card -->
                        <div class="card">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                                <div class="sc-ico ic-purple"><i class="fas fa-shield-alt"></i></div>
                                <div>
                                    <h3 style="font-weight: 700;">Two-Factor Authentication</h3>
                                    <p style="font-size: 12px; color: var(--text-body);">Add an extra layer of security to your account</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 16px; border-radius: 10px; margin-bottom: 16px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 4px;">SMS Authentication</div>
                                        <div style="font-size: 11px; color: var(--text-light);">Receive OTP via SMS on login</div>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="sms2fa">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--card-border);">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-mobile-alt" style="color: var(--green);"></i>
                                        <span style="font-size: 12px;">Mobile: +977 984****567</span>
                                        <button class="btn bs btn-sm" style="margin-left: auto;" onclick="alert('Change phone number')">Change</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 16px; border-radius: 10px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 4px;">Email Authentication</div>
                                        <div style="font-size: 11px; color: var(--text-light);">Receive OTP via email on login</div>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" id="email2fa">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--card-border);">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-envelope" style="color: var(--green);"></i>
                                        <span style="font-size: 12px;">Email: rahul****@institute.edu</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Login Sessions Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-history"></i> Active Login Sessions
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-green"><i class="fas fa-laptop"></i></div>
                                <div class="ai-content">
                                    <div class="nm-row">
                                        <span class="nm">Current Session</span>
                                        <span class="pill pg">Active Now</span>
                                    </div>
                                    <div class="sub-txt">Chrome on Windows · Kathmandu, Nepal</div>
                                    <div class="sub-txt">Started: Today, 10:30 AM</div>
                                </div>
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-blue"><i class="fas fa-mobile-alt"></i></div>
                                <div class="ai-content">
                                    <div class="nm-row">
                                        <span class="nm">Mobile App</span>
                                        <button class="btn bs btn-sm" onclick="alert('Session terminated')">Terminate</button>
                                    </div>
                                    <div class="sub-txt">Android 13 · Samsung Galaxy</div>
                                    <div class="sub-txt">Last active: 2 hours ago</div>
                                </div>
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-amber"><i class="fas fa-tablet"></i></div>
                                <div class="ai-content">
                                    <div class="nm-row">
                                        <span class="nm">iPad Safari</span>
                                        <button class="btn bs btn-sm" onclick="alert('Session terminated')">Terminate</button>
                                    </div>
                                    <div class="sub-txt">iPadOS · Safari</div>
                                    <div class="sub-txt">Last active: Yesterday, 8:15 PM</div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 16px;">
                                <button class="btn bs" style="width: 100%;" onclick="alert('All other sessions terminated')">
                                    <i class="fas fa-sign-out-alt"></i> Log Out All Other Sessions
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <!-- Login History Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-clock"></i> Recent Login History
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-green"><i class="fas fa-check-circle"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Successful login · Chrome, Windows</div>
                                    <div class="ai-tm">Today, 10:30 AM · Kathmandu</div>
                                </div>
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-green"><i class="fas fa-check-circle"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Successful login · Mobile App</div>
                                    <div class="ai-tm">Yesterday, 8:15 PM · Lalitpur</div>
                                </div>
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-amber"><i class="fas fa-question-circle"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Failed login attempt · Wrong password</div>
                                    <div class="ai-tm">2 days ago · Unknown location</div>
                                </div>
                            </div>
                            
                            <div class="ai">
                                <div class="ad ic-green"><i class="fas fa-check-circle"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Successful login · Firefox, Mac</div>
                                    <div class="ai-tm">3 days ago · Kathmandu</div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 12px; text-align: center;">
                                <a href="#" style="color: var(--green); font-size: 12px;" onclick="alert('View full login history')">View Full History →</a>
                            </div>
                        </div>

                        <!-- Security Questions Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-question-circle"></i> Security Questions
                            </div>
                            
                            <div style="background: #f8fafc; padding: 16px; border-radius: 10px; margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 600; font-size: 13px;">Question 1</span>
                                    <span class="pill pg">Set</span>
                                </div>
                                <div style="font-size: 12px; color: var(--text-body);">What is your mother's maiden name?</div>
                                <div style="font-size: 12px; margin-top: 6px;">********</div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 16px; border-radius: 10px; margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 600; font-size: 13px;">Question 2</span>
                                    <span class="pill pg">Set</span>
                                </div>
                                <div style="font-size: 12px; color: var(--text-body);">What was your first pet's name?</div>
                                <div style="font-size: 12px; margin-top: 6px;">********</div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 16px; border-radius: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span style="font-weight: 600; font-size: 13px;">Question 3</span>
                                    <span class="pill py">Not Set</span>
                                </div>
                                <div style="font-size: 12px; color: var(--text-body);">What city were you born in?</div>
                                <button class="btn bs btn-sm" style="margin-top: 10px;" onclick="alert('Set security question')">Add Question</button>
                            </div>
                        </div>

                        <!-- Trusted Devices -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-laptop"></i> Trusted Devices
                            </div>
                            
                            <div class="col-item">
                                <i class="fas fa-laptop col-ico ic-blue"></i>
                                <span class="col-lbl">Rahul's Laptop (Windows)</span>
                                <span class="col-val"><i class="fas fa-check-circle" style="color: var(--green);"></i></span>
                            </div>
                            
                            <div class="col-item">
                                <i class="fas fa-mobile-alt col-ico ic-teal"></i>
                                <span class="col-lbl">Samsung Galaxy S23</span>
                                <span class="col-val"><i class="fas fa-check-circle" style="color: var(--green);"></i></span>
                            </div>
                            
                            <div style="margin-top: 12px;">
                                <button class="btn bs" style="width: 100%;" onclick="alert('Remove all trusted devices')">
                                    <i class="fas fa-times-circle"></i> Remove All Trusted Devices
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Toggle Switch */
                .switch {
                    position: relative;
                    display: inline-block;
                    width: 44px;
                    height: 22px;
                }
                
                .switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                
                .slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .3s;
                    border-radius: 22px;
                }
                
                .slider:before {
                    position: absolute;
                    content: "";
                    height: 18px;
                    width: 18px;
                    left: 2px;
                    bottom: 2px;
                    background-color: white;
                    transition: .3s;
                    border-radius: 50%;
                }
                
                input:checked + .slider {
                    background-color: var(--green);
                }
                
                input:checked + .slider:before {
                    transform: translateX(22px);
                }
            </style>

            <script>
                document.getElementById('sms2fa')?.addEventListener('change', function(e) {
                    alert(e.target.checked ? 'SMS 2FA Enabled' : 'SMS 2FA Disabled');
                });
                
                document.getElementById('email2fa')?.addEventListener('change', function(e) {
                    alert(e.target.checked ? 'Email 2FA Enabled' : 'Email 2FA Disabled');
                });
            </script>
        </main>

    </div>

    <!-- Custom Scripts -->
    <script src="<?php echo APP_URL; ?>/assets/js/pwa-handler.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/student.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/breadcrumb.js"></script>
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
