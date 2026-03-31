<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Student ID Card - Student";
$themeColor = "#009E7E";
$roleCSS = "student.css";

// --- DYNAMIC DATA FETCHING ---
$user = getCurrentUser();
$db = getDBConnection();
$tenantId = $user['tenant_id'];

// 1. Fetch Tenant/Institute Details
$stmt = $db->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenantId]);
$tenant = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Fetch Student Details
$stmt = $db->prepare("
    SELECT s.*, u.name as full_name, u.phone, u.profile_image, 
           c.name as course_name, b.name as batch_name,
           u.email
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
    LEFT JOIN batches b ON e.batch_id = b.id 
    LEFT JOIN courses c ON b.course_id = c.id
    WHERE u.id = ? AND s.tenant_id = ?
    LIMIT 1
");
$stmt->execute([$user['id'], $tenantId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Prepare Logo & Photo
$instituteLogo = $_SESSION['institute_logo'] ?? $tenant['logo_path'] ?? '';
if ($instituteLogo && strpos($instituteLogo, 'http') !== 0) {
    if (strpos($instituteLogo, '/uploads/') === 0 && strpos($instituteLogo, '/public/') !== 0) {
        $instituteLogo = '/public' . $instituteLogo;
    }
    $instituteLogo = APP_URL . $instituteLogo;
}

$studentPhoto = $student['profile_image'] ?? '';
if ($studentPhoto && strpos($studentPhoto, 'http') !== 0) {
    if (strpos($studentPhoto, '/uploads/') === 0 && strpos($studentPhoto, '/public/') !== 0) {
        $studentPhoto = '/public' . $studentPhoto;
    }
    $studentPhoto = APP_URL . $studentPhoto;
} else if (empty($studentPhoto)) {
    $studentPhoto = 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80';
}
// -----------------------------

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
                        <img src="<?php echo $instituteLogo ?: (APP_URL . '/assets/images/logo.png'); ?>" alt="Logo" style="width:100%; height:auto;">
                    </div>
                    <span class="logo-txt"><?php echo htmlspecialchars($tenant['name'] ?? 'Nepal ERP'); ?></span>
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
                        <div style="display:flex; flex-direction:column; margin-left:8px; line-height:1.2;">
                            <span style="font-size:12px; font-weight:700;"><?php echo htmlspecialchars($student['full_name'] ?? 'Student'); ?></span>
                            <span style="font-size:10px; opacity:0.8;"><?php echo htmlspecialchars($student['roll_no'] ?? ''); ?> (Student)</span>
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
            <!-- Main Content - ID Card View -->
            <div class="pg">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="student.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="student-profile-view.php">My Profile</a>
                    <i class="fas fa-chevron-right"></i>
                    <span class="bc-cur">ID Card</span>
                </div>

                <!-- Page Header -->
                <div class="pg-hdr">
                    <div>
                        <h1>Student ID Card</h1>
                        <p>Your official institute identification card</p>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bs" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn bt" onclick="alert('Downloading ID Card...')">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                    </div>
                </div>

                <!-- ID Card Display -->
                <div class="id-card-wrapper" id="printableCard">
                    <div class="custom-id-card">
                        <!-- Top Banner -->
                        <div class="card-top-bg"></div>
                        <svg class="card-top-shadow" width="48%" height="12px" viewBox="0 0 288 12" preserveAspectRatio="none" style="position: absolute; top: 80px; right: 0; z-index: 2; display: block;">
                            <polygon points="20,0 288,0 288,12 0,12" fill="#020942" />
                        </svg>
                        
                        <div class="card-header-content">
                            <div class="card-logo">
                                <?php if ($instituteLogo): ?>
                                    <img src="<?php echo $instituteLogo; ?>" alt="Institute Logo" style="height: 60px; width: auto; max-width: 120px;">
                                <?php else: ?>
                                    <i class="fas fa-graduation-cap" style="font-size: 38px; color: #fff;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-institute">
                                <h2><?php echo htmlspecialchars($tenant['name'] ?? 'Institute Name'); ?></h2>
                                <p><?php echo htmlspecialchars($tenant['address'] ?? 'City, Nepal'); ?></p>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="card-body">
                            <h1 class="card-title">STUDENT CARD</h1>
                            <table class="card-details">
                                <tr><td>Name</td><td>:</td><td><?php echo htmlspecialchars($student['full_name'] ?? 'N/A'); ?></td></tr>
                                <tr><td>Roll No</td><td>:</td><td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td></tr>
                                <tr><td>Course</td><td>:</td><td><?php echo htmlspecialchars($student['course_name'] ?? 'N/A'); ?></td></tr>
                                <tr><td>Batch</td><td>:</td><td><?php echo htmlspecialchars($student['batch_name'] ?? 'N/A'); ?></td></tr>
                                <tr><td>Address</td><td>:</td><td><?php echo htmlspecialchars($student['address'] ?? 'Kathmandu, Nepal'); ?></td></tr>
                                <tr><td>Contact No</td><td>:</td><td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td></tr>
                            </table>
                        </div>

                        <!-- Photo -->
                        <div class="card-photo">
                            <div class="photo-inner" style="background: url('<?php echo $studentPhoto; ?>') center/cover;"></div>
                        </div>

                        <!-- Bottom Decor -->
                        <div class="decor-bg"></div>
                        <svg width="220" height="180" viewBox="0 0 220 180" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 2; display: block;">
                            <polygon points="0,54 220,180 0,180" fill="#020942" />
                        </svg>
                        <svg width="200" height="120" viewBox="0 0 200 120" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 3; display: block;">
                            <polygon points="0,24 160,120 0,120" fill="#8cc63f" />
                        </svg>
                        <svg width="120" height="60" viewBox="0 0 120 60" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 0; z-index: 4; display: block;">
                            <polygon points="0,6 78,60 0,60" fill="#3cb4cd" />
                        </svg>
                        <svg width="20" height="45" viewBox="0 0 20 45" preserveAspectRatio="none" style="position: absolute; bottom: 0; left: 210px; z-index: 2; display: block;">
                            <polygon points="6,0 20,45 0,45" fill="#fff" />
                        </svg>
                    </div>
                </div>

                <!-- ID Card Info & Actions -->
                <div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div class="sc-ico ic-blue"><i class="fas fa-info-circle"></i></div>
                        <div>
                            <h4 style="font-weight: 700; margin-bottom: 4px;">About Your ID Card</h4>
                            <p style="font-size: 12px; color: var(--text-body);">Use this ID card for institute access, library borrowing, and exam hall entry.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <span class="tag bg-t"><i class="fas fa-check-circle"></i> Verified</span>
                        <span class="tag bg-b"><i class="fas fa-clock"></i> Valid till Dec 2025</span>
                        <span class="tag bg-p"><i class="fas fa-shield-alt"></i> Digital Signature</span>
                    </div>
                </div>
            </div>

            <style>
                .id-card-wrapper {
                    display: flex;
                    justify-content: center;
                    margin: 30px auto 40px;
                }
                .custom-id-card {
                    width: 600px;
                    height: 380px;
                    background: linear-gradient(135deg, #cbeeea 0%, #80b5e2 100%);
                    position: relative;
                    overflow: hidden;
                    font-family: 'Inter', sans-serif;
                    border-radius: 12px;
                    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
                    color: #0b114d;
                }

                /* Top Sections */
                .card-top-bg {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 80px;
                    background-color: #8cc63f; /* Bright green */
                    z-index: 3;
                }

                .card-header-content {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 80px;
                    z-index: 4;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 0 30px;
                    gap: 15px;
                    text-align: center;
                }
                .card-logo {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .card-institute {
                    color: #fff;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }
                .card-institute h2 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 800;
                    letter-spacing: 0.5px;
                }
                .card-institute p {
                    margin: 0;
                    font-size: 16px;
                    opacity: 0.95;
                    font-weight: 500;
                }

                /* Body Content */
                .card-body {
                    position: absolute;
                    top: 110px;
                    left: 35px;
                    z-index: 5;
                }
                .card-title {
                    font-size: 32px;
                    font-weight: 800;
                    color: #020942;
                    margin: 0 0 15px 0;
                    letter-spacing: 1px;
                }
                .card-details {
                    border-collapse: collapse;
                }
                .card-details td {
                    padding: 5px 0;
                    font-size: 17px;
                    font-weight: 600;
                    color: #0f5173; /* Teal blue */
                }
                .card-details td:nth-child(1) {
                    width: 110px;
                }
                .card-details td:nth-child(2) {
                    width: 20px;
                    text-align: center;
                }
                .card-details td:nth-child(3) {
                    color: #2c3e50;
                }

                /* Photo Area */
                .card-photo {
                    position: absolute;
                    top: 100px;
                    right: 40px;
                    width: 160px;
                    height: 180px;
                    background-color: #020942;
                    border-radius: 25px;
                    padding: 5px;
                    z-index: 6;
                }
                .photo-inner {
                    width: 100%;
                    height: 100%;
                    border-radius: 20px;
                    border: 4px solid #3cb4cd;
                }

                /* Bottom Decorations */
                .decor-bg {
                    position: absolute;
                    bottom: 0;
                    left: 0;+
                    width: 100%;
                    height: 45px;
                    background-color: #9ECCE6;
                    z-index: 1;
                }
                
                @media print {
                    body * { visibility: hidden; }
                    .id-card-wrapper, .id-card-wrapper * { visibility: visible; }
                    .id-card-wrapper { position: absolute; top: 0; left: 0; margin: 0; transform: scale(1.1); transform-origin: top left; }
                    .pg-acts, .card, .breadcrumb { display: none; }
                }
            </style>
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
