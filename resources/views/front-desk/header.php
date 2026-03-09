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
  background: var(--green-gradient); display: flex; align-items: center; padding: 0 20px;
  box-shadow: 0 2px 10px rgba(22, 160, 133, 0.3); color: #fff; gap: 15px;
}
.hdr-brand { display: flex; align-items: center; gap: 12px; cursor: pointer; min-width: 220px; }
.hdr-logo {
  width: 38px; height: 38px; background: #fff; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800;
  color: var(--green-d); box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.hdr-name { font-size: 15px; font-weight: 800; letter-spacing: -.3px; line-height: 1.2; }
.hdr-sub { font-size: 10px; opacity: .85; font-weight: 500; }

/* ── CENTRAL SEARCH ── */
.hdr-center { flex: 1; display: flex; justify-content: center; padding: 0 20px; }
.hdr-search-box {
  width: 100%; max-width: 450px; position: relative; display: flex; align-items: center;
}
.hdr-search-input {
  width: 100%; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
  border-radius: 8px; padding: 10px 15px 10px 40px; color: #fff; font-size: 13px;
  outline: none; transition: 0.2s;
}
.hdr-search-input::placeholder { color: rgba(255,255,255,0.7); }
.hdr-search-input:focus { background: rgba(255,255,255,0.25); border-color: rgba(255,255,255,0.4); }
.hdr-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); opacity: 0.7; font-size: 14px; }
.hdr-pay-btn {
  margin-left: 10px; background: var(--white); color: var(--green-d); border: none;
  padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap;
  transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.hdr-pay-btn:hover { background: #f8fafc; transform: translateY(-1px); }

.hdr-right { display: flex; align-items: center; gap: 10px; }
.hdr-time-box { text-align: right; margin-right: 10px; }
.hdr-clock { font-size: 13px; font-weight: 700; }
.hdr-date { font-size: 10px; opacity: 0.8; }

.hbtn {
  width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.12); border-radius: 10px;
  cursor: pointer; font-size: 15px; border: none; color: #fff; position: relative;
  transition: .2s;
}
.hbtn:hover { background: rgba(255, 255, 255, 0.22); }
.nbadge {
  position: absolute; top: -4px; right: -4px; width: 16px; height: 16px;
  border-radius: 50%; background: var(--red); color: #fff; font-size: 9px;
  font-weight: 800; display: flex; align-items: center; justify-content: center;
  border: 2px solid #1abc9c;
}
.hdr-avatar {
  width: 34px; height: 34px; border-radius: 50%; background: #fff; color: var(--green-d);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; cursor: pointer;
}
.hdr-uinfo-box {
  display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.1);
  padding: 4px 12px; border-radius: 25px; cursor: pointer; transition: 0.2s;
}
.hdr-uinfo-box:hover { background: rgba(255,255,255,0.2); }
.hdr-uinfo { display: flex; flex-direction: column; }
.hdr-uname { font-size: 12px; font-weight: 700; color: #fff; }
.hdr-urole { font-size: 10px; opacity: .8; color: #fff; }

.menu-toggle {
  width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.15); border-radius: 10px;
  border: none; color: #fff; cursor: pointer; font-size: 18px;
}

/* Dropdown */
.fd-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0;
    background: #fff; border-radius: 12px; box-shadow: var(--shadow-md);
    min-width: 240px; opacity: 0; visibility: hidden;
    transform: translateY(-10px); transition: 0.2s; z-index: 1100;
}
.fd-dropdown.active { opacity: 1; visibility: visible; transform: translateY(0); }
.fd-dd-header { padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.fd-dd-menu { list-style: none; padding: 8px; }
.fd-dd-menu li a {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px;
    color: var(--text-dark); text-decoration: none; font-size: 13px;
    border-radius: 8px; transition: 0.2s;
}
.fd-dd-menu li a:hover { background: #f1f5f9; color: var(--green-d); }
.fd-dd-divider { height: 1px; background: #f1f5f9; margin: 5px 0; }
</style>

<header class="header">
    <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
    
    <div class="hdr-brand" onclick="goNav('dashboard')">
        <div class="hdr-logo">HL</div>
        <div>
            <div class="hdr-name"><?= htmlspecialchars($tenantName) ?></div>
            <div class="hdr-sub">Nepal CyberFirm · BrightFuture</div>
        </div>
    </div>
    
    <!-- Center Section: Search & Actions -->
    <div class="hdr-center">
        <div class="hdr-search-box">
            <i class="fa fa-search hdr-search-icon"></i>
            <input type="text" class="hdr-search-input" id="hdrSearch" placeholder="Search student, roll no, receipt...">
        </div>
        <button class="hdr-pay-btn" onclick="goNav('fee', 'fee-coll')">
            <i class="fa fa-plus-circle"></i> Record Payment
        </button>
    </div>

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

        <!-- User Profile -->
        <div class="hdr-uinfo-box" id="fdProfileToggle">
            <div class="hdr-avatar"><?= $initials ?></div>
            <div class="hdr-uinfo">
                <div class="hdr-uname"><?= htmlspecialchars($user['name'] ?? 'Operator') ?></div>
                <div class="hdr-urole">Front Desk</div>
            </div>

            <!-- Profile Dropdown -->
            <div class="fd-dropdown" id="fdProfileDropdown">
                <div class="fd-dd-header">
                    <div class="hdr-avatar" style="width:40px; height:40px; background:var(--green); color:#fff;"><?= $initials ?></div>
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

<!-- Search Results Dropdown -->
<style>
.search-results {
    position: absolute; top: 100%; left: 0; right: 0;
    background: #fff; border-radius: 0 0 12px 12px;
    box-shadow: var(--shadow-md); z-index: 1000;
    max-height: 400px; overflow-y: auto; display: none;
    margin-top: 5px; border: 1px solid #e2e8f0;
}
.search-res-item {
    display: flex; align-items: center; gap: 12px; padding: 12px 15px;
    cursor: pointer; transition: 0.2s; border-bottom: 1px solid #f1f5f9;
}
.search-res-item:hover { background: #f8fafc; }
.res-avatar {
    width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0;
    display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;
}
.res-main { font-size: 13px; font-weight: 600; color: var(--text-dark); }
.res-sub { font-size: 11px; color: var(--text-light); }
</style>
<div id="searchResults" class="search-results"></div>

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
