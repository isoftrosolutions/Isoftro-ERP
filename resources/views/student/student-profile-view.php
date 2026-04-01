<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "My Profile - Student";
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
                        <a href="<?= APP_URL ?>/logout.php" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
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
            <!-- Main Content - Profile View -->
            <div class="pg">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="student.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <span class="bc-cur">My Profile</span>
                </div>

                <!-- Page Header -->
                <div class="pg-hdr">
                    <div>
                        <h1>My Profile</h1>
                        <p>View and manage your personal information</p>
                    </div>
                    <div class="pg-acts">
                        <a href="student-change-password.php" class="btn bs">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <a href="student-id-card-view.php" class="btn bt">
                            <i class="fas fa-id-card"></i> View ID Card
                        </a>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="g65">
                    <!-- Left Column - Profile Card -->
                    <div>
                        <!-- Profile Header Card -->
                        <div class="card" style="padding: 0; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, var(--green) 0%, var(--green-d) 100%); height: 80px; position: relative;"></div>
                            <div style="padding: 0 24px 24px; position: relative;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: -40px;">
                                    <div style="display: flex; gap: 20px; align-items: flex-end;">
                                        <div style="width: 100px; height: 100px; background: #fff; border-radius: 16px; box-shadow: var(--shadow-md); display: flex; align-items: center; justify-content: center; border: 3px solid #fff;">
                                            <span style="font-size: 36px; font-weight: 800; color: var(--green);">RS</span>
                                        </div>
                                        <div style="margin-bottom: 10px;">
                                            <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 4px;">Rahul Sharma</h2>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <span class="pill pg"><i class="fas fa-circle" style="font-size: 6px;"></i> Active Student</span>
                                                <span class="pill pb">Roll: STU-2025-001</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <!-- READ-ONLY: Students cannot edit their profile -->
                                        <div style="display:flex;align-items:center;gap:6px;padding:7px 12px;background:#FEF3C7;border-radius:8px;border:1px solid #FCD34D;">
                                            <i class="fas fa-lock" style="color:#92400E;font-size:11px;"></i>
                                            <span style="font-size:12px;font-weight:600;color:#92400E;">View Only — Contact Front Desk to update</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information Card -->
                        <div class="card" style="margin-top: 20px;">
                            <div class="ct">
                                <i class="fas fa-user-circle"></i> Personal Information
                            </div>
                            <div class="detail-section">
                                <div class="detail-kv">
                                    <div class="detail-k">Full Name</div>
                                    <div class="detail-v">Rahul Sharma</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Father's Name</div>
                                    <div class="detail-v">Mr. Rajesh Sharma</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Mother's Name</div>
                                    <div class="detail-v">Mrs. Sunita Sharma</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Date of Birth</div>
                                    <div class="detail-v">15 Jan 2003 (BS: 2059-10-02)</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Gender</div>
                                    <div class="detail-v">Male</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Blood Group</div>
                                    <div class="detail-v">B+</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Nationality</div>
                                    <div class="detail-v">Nepali</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Citizenship No.</div>
                                    <div class="detail-v">45-02-78-12345 <span style="color: var(--text-light); font-size: 11px;">(Confidential)</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-phone-alt"></i> Contact Information
                            </div>
                            <div class="detail-section">
                                <div class="detail-kv">
                                    <div class="detail-k">Email Address</div>
                                    <div class="detail-v">rahul.sharma@institute.edu</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Mobile Number</div>
                                    <div class="detail-v">+977 9841234567</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Alternate Phone</div>
                                    <div class="detail-v">01-4412345</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Permanent Address</div>
                                    <div class="detail-v">Kathmandu-15, Bagmati Province, Nepal</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Temporary Address</div>
                                    <div class="detail-v">Lalitpur-3, Bagmati Province, Nepal</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <!-- Academic Information Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-graduation-cap"></i> Academic Information
                            </div>
                            <div class="detail-section">
                                <div class="detail-kv">
                                    <div class="detail-k">Student ID</div>
                                    <div class="detail-v">STU-2025-001</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Course</div>
                                    <div class="detail-v">Loksewa Nayab Subba</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Batch</div>
                                    <div class="detail-v">Morning Batch (2025)</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Admission Date</div>
                                    <div class="detail-v">01 Jan 2025 (BS: 2081-09-17)</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Enrollment Status</div>
                                    <div class="detail-v"><span class="pill pg">Active</span></div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Qualification</div>
                                    <div class="detail-v">Bachelor's Degree (3rd Year)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Guardian Information Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-users"></i> Guardian Information
                            </div>
                            <div class="detail-section">
                                <div class="detail-kv">
                                    <div class="detail-k">Guardian Name</div>
                                    <div class="detail-v">Mr. Rajesh Sharma</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Relation</div>
                                    <div class="detail-v">Father</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Contact</div>
                                    <div class="detail-v">+977 9851234567</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Email</div>
                                    <div class="detail-v">rajesh.sharma@email.com</div>
                                </div>
                                <div class="detail-kv">
                                    <div class="detail-k">Address</div>
                                    <div class="detail-v">Kathmandu-15, Nepal</div>
                                </div>
                            </div>
                            <div style="margin-top: 16px; background: #f8fafc; padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
                                <i class="fas fa-info-circle" style="color: var(--green);"></i>
                                <span style="font-size: 12px; color: var(--text-body);">Guardian has read-only access to monitor your progress</span>
                            </div>
                        </div>

                        <!-- Documents Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-file-alt"></i> Documents
                            </div>
                            <div class="col-item">
                                <i class="fas fa-id-card col-ico ic-blue"></i>
                                <span class="col-lbl">Citizenship (Front)</span>
                                <span class="col-val"><i class="fas fa-check-circle" style="color: var(--green);"></i></span>
                            </div>
                            <div class="col-item">
                                <i class="fas fa-id-card col-ico ic-blue"></i>
                                <span class="col-lbl">Citizenship (Back)</span>
                                <span class="col-val"><i class="fas fa-check-circle" style="color: var(--green);"></i></span>
                            </div>
                            <div class="col-item">
                                <i class="fas fa-image col-ico ic-purple"></i>
                                <span class="col-lbl">Passport Photo</span>
                                <span class="col-val"><i class="fas fa-check-circle" style="color: var(--green);"></i></span>
                            </div>
                            <div class="col-item">
                                <i class="fas fa-graduation-cap col-ico ic-teal"></i>
                                <span class="col-lbl">Academic Certificate</span>
                                <span class="col-val"><i class="fas fa-check-circle" style="color: var(--green);"></i></span>
                            </div>
                            <div style="margin-top: 16px; background: #f0fdf4; padding: 12px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
                                <i class="fas fa-info-circle" style="color: var(--green);"></i>
                                <span style="font-size: 12px; color: var(--text-body);">To upload documents, please contact the front desk.</span>
                            </div>
                        </div>

                        <!-- Account Activity Card -->
                        <div class="card">
                            <div class="ct">
                                <i class="fas fa-history"></i> Recent Activity
                            </div>
                            <div class="ai">
                                <div class="ad ic-green"><i class="fas fa-sign-in-alt"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Logged in from new device</div>
                                    <div class="ai-tm">2 hours ago</div>
                                </div>
                            </div>
                            <div class="ai">
                                <div class="ad ic-blue"><i class="fas fa-file-pdf"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Downloaded ID Card</div>
                                    <div class="ai-tm">Yesterday, 3:45 PM</div>
                                </div>
                            </div>
                            <div class="ai">
                                <div class="ad ic-amber"><i class="fas fa-key"></i></div>
                                <div class="ai-content">
                                    <div class="ai-txt">Password changed</div>
                                    <div class="ai-tm">3 days ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Edit button functionality
                document.querySelector('.btn.bs.btn-sm')?.addEventListener('click', function() {
                    alert('Edit profile feature coming soon!');
                });
                
                document.querySelector('.btn.bs[style*="width: 100%"]')?.addEventListener('click', function() {
                    alert('Document upload feature coming soon!');
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
