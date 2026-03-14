

<?php
/**
 * Hamro ERP — Role Selection View
 * Moved from public/index.php
 */

$pageTitle    = "Hamro ERP — Select Role";
$wrapperClass = "role-portal";

// Absolute paths for organized assets
$ASSETS = APP_URL . '/public/assets';

// Include header from organized layouts
include VIEWS_PATH . '/layouts/header.php';
?>
    <style>
        :root {
            --role-green:  #00B894;
            --role-blue:   #3B82F6;
            --role-purple: #8141A5;
            --role-amber:  #F59E0B;
            --role-red:    #E11D48;
            --role-teal:   #009E7E;
        }
        .role-portal {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, var(--bg) 0%, #e6f7f3 100%);
        }
        .portal-header {
            background: var(--green);
            color: #fff;
            padding: 16px 24px;
            box-shadow: 0 2px 8px rgba(0,184,148,0.3);
        }
        .portal-header .hdr-logo-box {
            display: flex; align-items: center; gap: 8px; font-weight: 800;
        }
        .portal-header .logo-fallback {
            width: 32px; height: 32px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .portal-header .logo-txt { font-size: 1.25rem; }
        .portal-main {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center; padding: 40px 20px;
        }
        .portal-title { text-align: center; margin-bottom: 12px; }
        .portal-title h1 { font-size: 2rem; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
        .portal-title p  { font-size: 1rem; color: var(--text-body); max-width: 600px; margin: 0 auto; }
        .roles-grid {
            display: grid; grid-template-columns: 1fr;
            gap: 20px; width: 100%; max-width: 1200px; margin-top: 40px;
        }
        @media (min-width: 640px)  { .roles-grid { grid-template-columns: repeat(2,1fr); } }
        @media (min-width: 1024px) { .roles-grid { grid-template-columns: repeat(3,1fr); } }
        .role-card {
            background: #fff; border-radius: 16px; padding: 32px 24px;
            box-shadow: var(--shadow); border: 1px solid var(--card-border);
            text-align: center; transition: all 0.3s ease; cursor: pointer; position: relative; overflow: hidden;
        }
        .role-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: var(--role-color); transform: scaleX(0); transition: transform 0.3s ease;
        }
        .role-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .role-card:hover::before { transform: scaleX(1); }
        .role-icon {
            width: 72px; height: 72px; margin: 0 auto 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; color: #fff; background: var(--role-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .role-title { font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
        .role-desc  { font-size: 0.875rem; color: var(--text-body); line-height: 1.5; margin-bottom: 20px; }
        .role-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 24px; background: var(--role-color); color: #fff;
            border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease; text-decoration: none;
        }
        .role-btn:hover { background: var(--role-color-d); transform: scale(1.02); }
        .portal-footer { text-align: center; padding: 24px; color: var(--text-light); font-size: 0.875rem; }
        .portal-footer a { color: var(--green); text-decoration: none; font-weight: 600; }
        .portal-footer a:hover { text-decoration: underline; }
    </style>

    <!-- Header -->
    <header class="portal-header">
        <div class="hdr-logo-box">
            <div class="logo-fallback">
                <img src="<?= $ASSETS ?>/images/logo.png" alt="Hamro ERP" style="height:32px;width:auto;">
            </div>
            <span class="logo-txt">Hamro ERP</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="portal-main">
        <div class="portal-title">
            <h1>Select Your Role</h1>
            <p>Choose the appropriate dashboard to access the Hamro ERP system based on your assigned role.</p>
        </div>

        <div class="roles-grid">

            <!-- Super Admin -->
            <div class="role-card" onclick="window.location.href='dash/super-admin'">
                <div class="role-icon" style="--role-color:var(--role-green);--role-color-d:#007a62;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h3 class="role-title">Super Admin</h3>
                <p class="role-desc">Full system control, user management, platform configuration, and administrative oversight.</p>
                <a href="dash/super-admin" class="role-btn" onclick="event.stopPropagation()">
                    Access Dashboard <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Institute Admin -->
            <div class="role-card" onclick="window.location.href='dash/admin'">
                <div class="role-icon" style="--role-color:var(--role-blue);--role-color-d:#2563EB;">
                    <i class="fa-solid fa-building"></i>
                </div>
                <h3 class="role-title">Institute Admin</h3>
                <p class="role-desc">Manage institute operations, staff, programs, and institutional settings.</p>
                <a href="dash/admin" class="role-btn" onclick="event.stopPropagation()">
                    Access Dashboard <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Front Desk -->
            <div class="role-card" onclick="window.location.href='dash/front-desk'">
                <div class="role-icon" style="--role-color:var(--role-purple);--role-color-d:#6B21A8;">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3 class="role-title">Front Desk Operator</h3>
                <p class="role-desc">Handle visitor management, inquiries, reception duties, and front-line operations.</p>
                <a href="dash/front-desk" class="role-btn" onclick="event.stopPropagation()">
                    Access Dashboard <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Teacher -->
            <div class="role-card" onclick="window.location.href='dash/teacher'">
                <div class="role-icon" style="--role-color:var(--role-amber);--role-color-d:#D97706;">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <h3 class="role-title">Teacher</h3>
                <p class="role-desc">Access teaching materials, student records, class schedules, and grading tools.</p>
                <a href="dash/teacher" class="role-btn" onclick="event.stopPropagation()">
                    Access Dashboard <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Student -->
            <div class="role-card" onclick="window.location.href='dash/student'">
                <div class="role-icon" style="--role-color:var(--role-teal);--role-color-d:#007A6C;">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <h3 class="role-title">Student</h3>
                <p class="role-desc">View courses, assignments, grades, attendance, and academic resources.</p>
                <a href="dash/student" class="role-btn" onclick="event.stopPropagation()">
                    Access Dashboard <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Guardian -->
            <div class="role-card" onclick="window.location.href='dash/guardian'">
                <div class="role-icon" style="--role-color:var(--role-red);--role-color-d:#BE123C;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <h3 class="role-title">Guardian / Parent</h3>
                <p class="role-desc">Monitor student progress, attendance, fees, and communicate with teachers.</p>
                <a href="dash/guardian" class="role-btn" onclick="event.stopPropagation()">
                    Access Dashboard <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        <p>Hamro ERP &copy; <?= date('Y') ?> | Platform Version 3.0</p>
        <p style="margin-top:8px;">
            <a href="#"><i class="fa-solid fa-circle-question"></i> Need Help?</a>
        </p>
    </footer>
</div>
</body>
</html>
