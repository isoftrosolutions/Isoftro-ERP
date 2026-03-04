<?php
/**
 * Front Desk — Header (Refactored to match Institute Admin)
 * White background, centered search, glassmorphism dropdown
 */
$user = getCurrentUser();
$tenantName = $_SESSION['tenant_name'] ?? 'Institute';
$tenantId = $_SESSION['tenant_id'] ?? null;

// User initials
$initials = 'FD';
if ($user && isset($user['name'])) {
    $parts = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

// Institute logo and name - always fetch from tenants table
$logoUrl = null;
$tenantLogo = $_SESSION['institute_logo'] ?? $_SESSION['tenant_logo'] ?? null;

if ($tenantId) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT name, logo_path FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $tenantId]);
        $tenant = $stmt->fetch();
        if ($tenant) {
            // Always use tenant name from database
            if (!empty($tenant['name'])) {
                $tenantName = $tenant['name'];
                $_SESSION['tenant_name'] = $tenantName;
            }
            // Get logo if available
            if (empty($tenantLogo) && !empty($tenant['logo_path'])) {
                $tenantLogo = $tenant['logo_path'];
                $_SESSION['tenant_logo'] = $tenantLogo;
                $_SESSION['institute_logo'] = $tenantLogo;
            }
        }
    } catch (Exception $e) {}
}

if (!empty($tenantLogo)) {
    $logoUrl = (strpos($tenantLogo, 'http') === 0) ? $tenantLogo : APP_URL . $tenantLogo;
}

// Notification count
$notificationCount = 0;
try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE tenant_id = :tid AND user_id = :uid AND is_read = 0");
    $stmt->execute(['tid' => $_SESSION['userData']['tenant_id'] ?? 0, 'uid' => $_SESSION['userData']['id'] ?? null]);
    $notificationCount = (int) $stmt->fetchColumn();
} catch (Exception $e) {}
?>

<link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/frontdesk.css?v=1.1">

<style>
/* ── HEADER (Aligning with Mockup) ── */
.header {
  position: fixed; top: 0; left: 0; right: 0; height: var(--hdr-h); z-index: 1000;
  background: var(--green); display: flex; align-items: center; padding: 0 16px;
  box-shadow: 0 2px 8px rgba(0,184,148,.30); color: #fff; gap: 12px;
}
.hdr-brand { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.hdr-logo {
  width: 34px; height: 34px; background: rgba(255,255,255,.2); border-radius: 9px;
  display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800;
  letter-spacing: -1px;
}
.hdr-name { font-size: 14px; font-weight: 800; letter-spacing: -.3px; }
.hdr-sub { font-size: 10px; opacity: .75; font-weight: 500; }
.hdr-divider { width: 1px; height: 28px; background: rgba(255,255,255,.25); margin: 0 4px; }
.hdr-portal-tag {
  background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.3);
  padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
  letter-spacing: .3px; text-transform: uppercase;
}
.hdr-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.hdr-clock { font-size: 12px; font-weight: 600; opacity: .9; text-align: right; }
.hdr-date { font-size: 11px; opacity: .7; text-align: right; }
.hbtn {
  width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.15); border-radius: var(--radius-sm);
  cursor: pointer; font-size: 14px; border: none; color: #fff; position: relative;
  transition: .2s;
}
.hbtn:hover { background: rgba(255, 255, 255, 0.25); }
.nbadge {
  position: absolute; top: -3px; right: -3px; width: 15px; height: 15px;
  border-radius: 50%; background: var(--red); color: #fff; font-size: 8px;
  font-weight: 800; display: flex; align-items: center; justify-content: center;
  border: 2px solid var(--green);
}
.hdr-avatar {
  width: 32px; height: 32px; border-radius: 50%; background: var(--green-d);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; cursor: pointer;
}
.hdr-uinfo { display: flex; flex-direction: column; align-items: flex-end; }
.hdr-uname { font-size: 12px; font-weight: 700; color: #fff; }
.hdr-urole { font-size: 10px; opacity: .7; color: #fff; }
.menu-toggle {
  width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.15); border-radius: var(--radius-sm);
  border: none; color: #fff; cursor: pointer; font-size: 16px;
}

/* Dropdown (Glassmorphism Adjusted for Green) */
.fd-dropdown {
    position: absolute; top: calc(100% + 8px); right: 0;
    background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px);
    border-radius: 12px; box-shadow: var(--shadow-lg);
    min-width: 240px; opacity: 0; visibility: hidden;
    transform: translateY(-10px); transition: all 0.2s; z-index: 1100;
}
.fd-dropdown.active { opacity: 1; visibility: visible; transform: translateY(0); }
.fd-dd-header { padding: 16px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
.fd-dd-menu { list-style: none; padding: 8px; }
.fd-dd-menu li a {
    display: flex; align-items: center; gap: 10px; padding: 10px;
    color: var(--text-dark); text-decoration: none; font-size: 13px;
    border-radius: 8px; transition: 0.2s;
}
.fd-dd-menu li a:hover { background: #f0fdf4; color: var(--green); }
.fd-dd-divider { height: 1px; background: #eee; margin: 4px 0; }
</style>

<header class="header">
    <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
    
    <div class="hdr-brand" onclick="goNav('dashboard')">
        <div class="hdr-logo">HL</div>
        <div>
            <div class="hdr-name"><?= htmlspecialchars($tenantName) ?></div>
            <div class="hdr-sub">ERP Portal</div>
        </div>
    </div>
    
    <div class="hdr-divider"></div>
    <div class="hdr-portal-tag"><i class="fa fa-door-open" style="margin-right:5px"></i>Front Desk</div>

    <!-- Right Section -->
    <div class="hdr-right">
        <div class="hdr-time-box">
            <div class="hdr-clock" id="hdr-clock"><?= date('h:i A') ?></div>
            <div class="hdr-date" id="hdr-date"><?= date('D, M d, Y') ?></div>
        </div>

        <button class="hbtn" id="fdNotifToggle" title="Notifications">
            <i class="fa fa-bell"></i>
            <?php if ($notificationCount > 0): ?>
                <span class="nbadge"><?= $notificationCount ?></span>
            <?php endif; ?>
        </button>

        <button class="hbtn" title="Quick Search" onclick="focusSearch()">
            <i class="fa fa-search"></i>
        </button>

        <!-- User Profile -->
        <div style="position:relative; display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.1); padding:4px 10px; border-radius:20px; cursor:pointer;" id="fdProfileToggle">
            <div class="hdr-avatar"><?= $initials ?></div>
            <div class="hdr-uinfo">
                <div class="hdr-uname"><?= htmlspecialchars($user['name'] ?? 'Operator') ?></div>
                <div class="hdr-urole">Front Desk</div>
            </div>

            <!-- Profile Dropdown -->
            <div class="fd-dropdown" id="fdProfileDropdown">
                <div class="fd-dd-header">
                    <div class="hdr-avatar" style="width:40px; height:40px;"><?= $initials ?></div>
                    <div>
                        <div style="font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($user['name'] ?? 'Operator') ?></div>
                        <div style="font-size:11px; color:var(--text-light);">Front Desk Operator</div>
                    </div>
                </div>
                <ul class="fd-dd-menu">
                    <li><a href="#" onclick="goNav('settings', 'profile'); return false;"><i class="fa-regular fa-user"></i> My Profile</a></li>
                    <li><a href="#" onclick="goNav('settings', 'password'); return false;"><i class="fa-solid fa-key"></i> Security</a></li>
                    <div class="fd-dd-divider"></div>
                    <li><a href="<?= APP_URL ?>/logout" style="color:var(--red);"><i class="fa-solid fa-power-off"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Notifications Panel (Slide-in) -->
<style>
.fd-notif-panel {
    position: fixed; top: var(--hdr-h); right: -100%; width: 340px;
    height: calc(100vh - var(--hdr-h)); background: #fff;
    box-shadow: var(--shadow-lg); transition: 0.3s; z-index: 1001;
    display: flex; flex-direction: column;
}
.fd-notif-panel.active { right: 0; }
.fd-notif-header { padding: 16px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.fd-notif-body { flex: 1; overflow-y: auto; padding: 10px; }
</style>

<div class="fd-notif-panel" id="fdNotifPanel">
    <div class="fd-notif-header">
        <h4 style="margin:0;"><i class="fa-solid fa-bell"></i> Notifications</h4>
        <button class="hbtn" style="color:var(--text-body); background:none;" id="fdCloseNotif"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="fd-notif-body" id="fdNotifBody">
        <div style="padding:20px; text-align:center; color:var(--text-light);">No new notifications</div>
    </div>
</div>

<script>
(function() {
    const clock = document.getElementById('hdr-clock');
    const date = document.getElementById('hdr-date');
    
    function updateTime() {
        const now = new Date();
        if (clock) clock.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', hour12: true});
        if (date) date.textContent = now.toLocaleDateString([], {weekday: 'short', month: 'short', day: '2-digit', year: 'numeric'});
    }
    setInterval(updateTime, 10000);
    updateTime();

    // Toggle logic
    const toggle = document.getElementById('menuToggle');
    if (toggle) {
        toggle.addEventListener('click', () => document.body.classList.toggle('sb-active'));
    }

    const profileToggle = document.getElementById('fdProfileToggle');
    const profileDropdown = document.getElementById('fdProfileDropdown');
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });
        document.addEventListener('click', () => profileDropdown.classList.remove('active'));
    }

    const notifToggle = document.getElementById('fdNotifToggle');
    const notifPanel = document.getElementById('fdNotifPanel');
    const closeNotif = document.getElementById('fdCloseNotif');
    if (notifToggle && notifPanel) {
        notifToggle.addEventListener('click', (e) => { e.stopPropagation(); notifPanel.classList.toggle('active'); });
        if (closeNotif) closeNotif.addEventListener('click', () => notifPanel.classList.remove('active'));
        document.addEventListener('click', (e) => {
            if (!notifPanel.contains(e.target) && !notifToggle.contains(e.target)) notifPanel.classList.remove('active');
        });
    }
})();
</script>
