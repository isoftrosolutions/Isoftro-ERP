<?php
require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$pageTitle = "Change Password - Student";
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
                        <a href="../../index.php?logout=1" style="display:flex; align-items:center; gap:10px; padding:10px; font-size:13px; color:var(--red); text-decoration:none; border-radius:8px;"><i class="fa-solid fa-power-off"></i> Logout</a>
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
            <!-- Main Content - Change Password -->
            <div class="pg">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="student.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="student-profile-view.php">My Profile</a>
                    <i class="fas fa-chevron-right"></i>
                    <span class="bc-cur">Change Password</span>
                </div>

                <!-- Page Header -->
                <div class="pg-hdr">
                    <div>
                        <h1>Change Password</h1>
                        <p>Update your account password</p>
                    </div>
                </div>

                <!-- Password Change Form -->
                <div style="max-width: 500px; margin: 0 auto;">
                    <div class="card">
                        <div class="ct">
                            <i class="fas fa-lock"></i> Password Settings
                        </div>
                        
                        <!-- Current Password -->
                        <div class="form-grp">
                            <label class="form-lbl">Current Password</label>
                            <div style="position: relative;">
                                <input type="password" class="form-inp" id="currentPass" placeholder="Enter current password">
                                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('currentPass')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <!-- New Password -->
                        <div class="form-grp">
                            <label class="form-lbl">New Password</label>
                            <div style="position: relative;">
                                <input type="password" class="form-inp" id="newPass" placeholder="Enter new password">
                                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('newPass')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Confirm New Password -->
                        <div class="form-grp">
                            <label class="form-lbl">Confirm New Password</label>
                            <div style="position: relative;">
                                <input type="password" class="form-inp" id="confirmPass" placeholder="Re-enter new password">
                                <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer;" onclick="togglePassword('confirmPass')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Password Strength Meter -->
                        <div class="form-grp">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span class="form-hint">Password strength</span>
                                <span class="form-hint" id="strengthText">Weak</span>
                            </div>
                            <div style="display: flex; gap: 4px; height: 4px;">
                                <div style="flex: 1; background: #fee2e2; border-radius: 2px;" id="strength1"></div>
                                <div style="flex: 1; background: #f1f5f9; border-radius: 2px;" id="strength2"></div>
                                <div style="flex: 1; background: #f1f5f9; border-radius: 2px;" id="strength3"></div>
                            </div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="form-grp" style="background: #f8fafc; padding: 12px; border-radius: 8px;">
                            <div style="font-size: 11px; font-weight: 700; margin-bottom: 8px;">Password must contain:</div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-circle" style="font-size: 6px; color: var(--text-light);"></i>
                                    <span class="form-hint">Min 8 characters</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-circle" style="font-size: 6px; color: var(--text-light);"></i>
                                    <span class="form-hint">One uppercase letter</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-circle" style="font-size: 6px; color: var(--text-light);"></i>
                                    <span class="form-hint">One lowercase letter</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-circle" style="font-size: 6px; color: var(--text-light);"></i>
                                    <span class="form-hint">One number</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 12px; margin-top: 20px;">
                            <button class="btn bs" style="flex: 1;" onclick="window.location.href='student-profile-view.php'">Cancel</button>
                            <button class="btn bt" style="flex: 1;" onclick="updatePassword()">Update Password</button>
                        </div>
                    </div>
                    
                    <!-- Password Tips Card -->
                    <div class="card" style="margin-top: 20px;">
                        <div class="ct">
                            <i class="fas fa-shield-alt"></i> Security Tips
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div style="display: flex; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--green);"></i>
                                <span style="font-size: 12px;">Never share your password with anyone</span>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--green);"></i>
                                <span style="font-size: 12px;">Use a unique password for this account</span>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <i class="fas fa-check-circle" style="color: var(--green);"></i>
                                <span style="font-size: 12px;">Change password every 3 months</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function togglePassword(fieldId) {
                    const field = document.getElementById(fieldId);
                    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
                    field.setAttribute('type', type);
                }

                function showMsg(msg, isError) {
                    let el = document.getElementById('pwMsg');
                    if (!el) {
                        el = document.createElement('div');
                        el.id = 'pwMsg';
                        el.style.cssText = 'padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:14px;';
                        document.querySelector('.form-grp').before(el);
                    }
                    el.style.background = isError ? '#fee2e2' : '#dcfce7';
                    el.style.color      = isError ? '#b91c1c' : '#166534';
                    el.style.border     = isError ? '1px solid #fca5a5' : '1px solid #86efac';
                    el.textContent = msg;
                    el.style.display = 'block';
                }

                async function updatePassword() {
                    const current = document.getElementById('currentPass').value.trim();
                    const newPass  = document.getElementById('newPass').value.trim();
                    const confirm  = document.getElementById('confirmPass').value.trim();
                    const btn      = document.querySelector('button.btn.bt');

                    if (!current || !newPass || !confirm) {
                        showMsg('Please fill all fields.', true);
                        return;
                    }
                    if (newPass !== confirm) {
                        showMsg('New passwords do not match.', true);
                        return;
                    }
                    if (newPass.length < 8) {
                        showMsg('Password must be at least 8 characters.', true);
                        return;
                    }

                    btn.disabled = true;
                    btn.textContent = 'Updating…';

                    try {
                        const res = await fetch('<?= APP_URL ?>/api/student/profile?action=change_password', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                current_password: current,
                                new_password:     newPass,
                                confirm_password: confirm
                            })
                        });
                        const data = await res.json();

                        if (data.success) {
                            showMsg('✅ Password changed successfully! Redirecting…', false);
                            setTimeout(() => window.location.href = 'student-profile-view.php', 1800);
                        } else {
                            showMsg(data.message || 'Failed to change password.', true);
                            btn.disabled = false;
                            btn.textContent = 'Update Password';
                        }
                    } catch (err) {
                        showMsg('An unexpected error occurred. Please try again.', true);
                        btn.disabled = false;
                        btn.textContent = 'Update Password';
                    }
                }

                
                // Password strength checker
                document.getElementById('newPass')?.addEventListener('input', function(e) {
                    const pass = e.target.value;
                    let strength = 0;
                    
                    if (pass.length >= 8) strength++;
                    if (pass.match(/[A-Z]/)) strength++;
                    if (pass.match(/[0-9]/)) strength++;
                    
                    const strengthText = document.getElementById('strengthText');
                    const s1 = document.getElementById('strength1');
                    const s2 = document.getElementById('strength2');
                    const s3 = document.getElementById('strength3');
                    
                    s1.style.background = '#f1f5f9';
                    s2.style.background = '#f1f5f9';
                    s3.style.background = '#f1f5f9';
                    
                    if (strength >= 1) s1.style.background = '#fee2e2';
                    if (strength >= 2) s2.style.background = '#fef9c3';
                    if (strength >= 3) s3.style.background = '#dcfce7';
                    
                    if (strength === 0) strengthText.textContent = 'Too weak';
                    else if (strength === 1) strengthText.textContent = 'Weak';
                    else if (strength === 2) strengthText.textContent = 'Medium';
                    else strengthText.textContent = 'Strong';
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
