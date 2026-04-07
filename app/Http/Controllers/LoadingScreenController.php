<?php
/**
 * Loading Screen Controller
 * Handles the post-login loading screen with database-driven institute branding
 */

namespace App\Http\Controllers;

require_once base_path('config/config.php');

class LoadingScreenController {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    /**
     * Show the loading screen after successful login
     * Fetches institute data from database and renders the loading view
     */
    public function show() {
        // Validate that user is coming from successful login
        $token = $_GET['token'] ?? '';
        $redirect = $_GET['redirect'] ?? ($_SESSION['pending_redirect'] ?? '/dash/admin');
        
        // Check session token
        if (empty($token) || !isset($_SESSION['loading_token']) || $token !== $_SESSION['loading_token']) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
        
        // Check token expiry
        if (isset($_SESSION['loading_token_expires']) && $_SESSION['loading_token_expires'] < time()) {
            unset($_SESSION['loading_token']);
            unset($_SESSION['loading_token_expires']);
            header('Location: ' . APP_URL . '/login');
            exit;
        }
        
        // Clear the loading token so it can't be reused
        unset($_SESSION['loading_token']);
        unset($_SESSION['loading_token_expires']);
        
        // Get user data from session
        $user = $_SESSION['userData'] ?? [];
        
        if (empty($user)) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
        
        // Get tenant data
        $tenant = $this->getTenantData($user['tenant_id'] ?? null);
        
        // Prepare tenant data for the view
        $instituteData = [
            'name' => $tenant['name'] ?? 'Institute',
            'tagline' => $tenant['tagline'] ?? 'Excellence in Education',
            'logo' => $_SESSION['institute_logo'] ?? $tenant['logo'] ?? null,
            'role' => $user['role'] ?? 'user',
            'userName' => $user['name'] ?? 'User',
            'brandColor' => $tenant['brand_color'] ?? '#009E7E',
            'redirectUrl' => $redirect
        ];
        
        // Render the loading screen view
        $this->renderView($instituteData);
    }
    
    /**
     * Get tenant/institute data from database
     */
    private function getTenantData($tenantId) {
        if (!$tenantId) {
            return [];
        }
        
        $stmt = $this->db->prepare("
            SELECT t.*, t.logo_path as logo
            FROM tenants t
            WHERE t.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $tenantId]);
        return $stmt->fetch() ?: [];
    }
    
    /**
     * Render the loading screen HTML view
     */
    private function renderView($data) {
        $roleLabel = $this->getRoleLabel($data['role']);
        $rawLogo = $data['logo'] ? (strpos($data['logo'], '/public/') === 0 ? substr($data['logo'], 7) : $data['logo']) : null;
        $logoUrl = $rawLogo ? APP_URL . $rawLogo : null;
        $brandColor = htmlspecialchars($data['brandColor']);
        $instituteName = htmlspecialchars($data['name']);
        $instituteTagline = htmlspecialchars($data['tagline']);
        $redirectUrl = htmlspecialchars($data['redirectUrl']);
        $userName = htmlspecialchars($data['userName']);
        
        // Generate initials from institute name
        $initials = $this->generateInitials($data['name']);
        
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Welcome — {$instituteName}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --sa-primary:    {$brandColor};
  --sa-primary-d:  {$this->darkenColor($brandColor, 20)};
  --sa-primary-lt: {$this->lightenColor($brandColor, 80)};
  --purple:        #8141A5;
  --soft-purple:   #F3E8FF;
  --navy:          #0F172A;
  --td:            #1E293B;
  --tb:            #475569;
  --tl:            #94A3B8;
  --cb:            #E2E8F0;
  --bg:            #F8FAFC;
  --white:         #fff;
  --sh:  0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
  --shm: 0 4px 16px rgba(0,0,0,.08);
  --font: 'Plus Jakarta Sans', sans-serif;
}

html, body {
  height: 100%;
  font-family: var(--font);
  background: var(--bg);
}

body {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}

/* Subtle mesh background */
body::before {
  content: '';
  position: fixed; inset: 0;
  background:
    radial-gradient(ellipse 65% 55% at 15% 10%, {$this->hexToRgba($brandColor, 0.08)} 0%, transparent 55%),
    radial-gradient(ellipse 55% 45% at 85% 88%, rgba(129,65,165,0.07) 0%, transparent 55%);
  pointer-events: none;
}

/* ── CARD ── */
.card {
  background: var(--white);
  border-radius: 20px;
  border: 1px solid var(--cb);
  width: min(400px, 92vw);
  padding: 36px 32px 28px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.10);
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: cardIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
  position: relative;
  overflow: hidden;
}

/* top accent strip */
.card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--sa-primary), var(--purple));
}

@keyframes cardIn {
  from { opacity: 0; transform: translateY(24px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ── SHIMMER ── */
@keyframes shimmer {
  0%   { background-position: -500px 0; }
  100% { background-position: 500px 0; }
}
.skel {
  background: linear-gradient(90deg, #e8edf3 25%, #f4f7fa 50%, #e8edf3 75%);
  background-size: 500px 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 8px;
}

/* ── SKELETON ELEMENTS ── */
.skel-logo {
  width: 80px; height: 80px;
  border-radius: 16px;
  margin-bottom: 20px;
  flex-shrink: 0;
}

.skel-name {
  height: 18px;
  width: 60%;
  margin-bottom: 10px;
  border-radius: 6px;
}

.skel-tag {
  height: 12px;
  width: 78%;
  margin-bottom: 7px;
  border-radius: 6px;
}

.skel-tag.s2 {
  width: 52%;
  margin-bottom: 22px;
}

.skel-badge {
  height: 28px;
  width: 40%;
  border-radius: 999px;
  margin-bottom: 28px;
}

.skeleton-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

/* Animated dots */
.dots {
  display: flex;
  gap: 5px;
  margin-bottom: 28px;
}
.dots span {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--cb);
  animation: dotPulse 1.2s ease-in-out infinite;
}
.dots span:nth-child(2) { animation-delay: .14s; }
.dots span:nth-child(3) { animation-delay: .28s; }

@keyframes dotPulse {
  0%,80%,100% { transform: scale(0.7); background: var(--cb); }
  40%          { transform: scale(1.15); background: var(--sa-primary); }
}

/* ── REAL CONTENT ── */
.real-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  opacity: 0;
  transform: translateY(6px);
  transition: opacity 0.45s ease, transform 0.45s ease;
}
.real-content.show {
  opacity: 1;
  transform: translateY(0);
}

/* Institute logo */
.inst-logo-box {
  width: 80px; height: 80px;
  border-radius: 16px;
  border: 1px solid var(--cb);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  box-shadow: var(--sh);
  background: #fff;
  flex-shrink: 0;
}
.inst-logo-box img { width: 100%; height: 100%; object-fit: contain; }

/* Initials fallback canvas */
.inst-logo-box canvas { width: 80px; height: 80px; border-radius: 16px; }

.inst-name {
  font-size: 16px;
  font-weight: 800;
  color: var(--td);
  text-align: center;
  margin-bottom: 6px;
  letter-spacing: -0.2px;
}

.inst-tagline {
  font-size: 12px;
  color: var(--tl);
  text-align: center;
  font-weight: 400;
  line-height: 1.55;
  max-width: 280px;
  margin-bottom: 16px;
}

/* Role badge */
.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--sa-primary-lt);
  color: var(--sa-primary-d);
  font-size: 11.5px;
  font-weight: 700;
  padding: 6px 16px;
  border-radius: 999px;
  margin-bottom: 24px;
  letter-spacing: 0.2px;
  border: 1px solid {$this->hexToRgba($brandColor, 0.2)};
}
.role-badge .dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--sa-primary);
  animation: blink 1.3s ease-in-out infinite;
}
@keyframes blink {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:0.35; transform:scale(0.8); }
}

/* ── PROGRESS ── */
.progress-track {
  width: 100%;
  height: 3px;
  background: var(--cb);
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 22px;
}
.progress-fill {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--sa-primary), var(--purple));
  border-radius: 999px;
  transition: width 0.25s ease;
}

/* ── DIVIDER ── */
.divider {
  width: 100%;
  height: 1px;
  background: var(--cb);
  margin-bottom: 20px;
}

/* ── POWERED BY ── */
.powered-by {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 9px;
  width: 100%;
}
.powered-label {
  font-size: 9.5px;
  font-weight: 700;
  color: var(--tl);
  letter-spacing: 1.4px;
  text-transform: uppercase;
}
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
}
.brand-logo {
  width: 34px; height: 34px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid var(--cb);
  flex-shrink: 0;
  box-shadow: var(--sh);
}
.brand-logo img { width: 100%; height: 100%; object-fit: cover; }
.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}
.brand-name {
  font-size: 13px;
  font-weight: 800;
  color: var(--td);
  letter-spacing: -0.2px;
}
.brand-sub {
  font-size: 9.5px;
  color: var(--tl);
  font-weight: 500;
  letter-spacing: 0.3px;
}

/* Welcome message */
.welcome-msg {
  font-size: 13px;
  color: var(--tb);
  text-align: center;
  margin-bottom: 12px;
}
.welcome-msg strong {
  color: var(--sa-primary);
  font-weight: 600;
}
</style>
</head>
<body>
<div class="card" id="card">

  <!-- ── SKELETON ── -->
  <div class="skeleton-group" id="skelGroup">
    <div class="skel skel-logo"></div>
    <div class="skel skel-name"></div>
    <div class="skel skel-tag"></div>
    <div class="skel skel-tag s2"></div>
    <div class="skel skel-badge"></div>
    <div class="dots"><span></span><span></span><span></span></div>
  </div>

  <!-- ── REAL CONTENT ── -->
  <div class="real-content" id="realContent">
    <div class="inst-logo-box" id="instLogoBox">
      <!-- logo or initials injected by JS -->
    </div>
    <div class="inst-name" id="realName">{$instituteName}</div>
    <div class="inst-tagline" id="realTagline">{$instituteTagline}</div>
    <div class="welcome-msg">Welcome back, <strong>{$userName}</strong></div>
    <div class="role-badge">
      <span class="dot"></span>
      <span id="realRole">{$roleLabel}</span>
    </div>
  </div>

  <!-- always visible -->
  <div class="progress-track">
    <div class="progress-fill" id="prog"></div>
  </div>
  <div class="divider"></div>
  <div class="powered-by">
    <div class="powered-label">Powered by</div>
    <div class="brand">
      <div class="brand-logo">
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%23009E7E'/%3E%3Ctext x='50' y='58' font-size='40' text-anchor='middle' fill='white' font-family='Arial'%3EH%3C/text%3E%3C/svg%3E" alt="Hamro Labs"/>
      </div>
      <div class="brand-text">
        <span class="brand-name">Hamro Labs</span>
        <span class="brand-sub">ERP Platform</span>
      </div>
    </div>
  </div>

</div>

<script>
/* ── CONFIG ─────────────────────────────────────────────────── */
const tenant = {
  name:    "{$instituteName}",
  tagline: "{$instituteTagline}",
  logo:    "{$logoUrl}",
  role:    "{$data['role']}",
  userName: "{$userName}",
  brandColor: "{$brandColor}"
};

const REDIRECT_URL = "{$redirectUrl}";
const SKEL_MS     = 1800;
const REDIRECT_MS = 3500;

// Role label map
const roleMap = {
  student:    "Student Portal",
  teacher:    "Teacher Dashboard",
  admin:      "Admin Panel",
  instituteadmin: "Institute Admin",
  "front desk": "Front Desk",
  frontdesk:  "Front Desk",
  principal:  "Principal Office",
  institute:  "Institute Admin",
  superadmin: "Super Admin",
  guardian:   "Guardian Portal"
};

const getRoleLabel = r =>
  roleMap[(r || '').toLowerCase()] || (r + " Portal");

// Progress bar crawl
const fill = document.getElementById('prog');
let p = 0;
const ticker = setInterval(() => {
  p = Math.min(p + Math.random() * 3.2 + 0.8, 90);
  fill.style.width = p + '%';
}, 90);

// Build initials canvas
function makeInitialsLogo(name) {
  const initials = name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
  const c = document.createElement('canvas');
  c.width = c.height = 80;
  const ctx = c.getContext('2d');
  // gradient bg
  const grad = ctx.createLinearGradient(0,0,80,80);
  grad.addColorStop(0, tenant.brandColor || '#009E7E');
  grad.addColorStop(1, '#8141A5');
  ctx.fillStyle = grad;
  ctx.fillRect(0,0,80,80);
  // text
  ctx.fillStyle = '#fff';
  ctx.font = 'bold 28px "Plus Jakarta Sans", sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(initials, 40, 42);
  return c;
}

// Reveal real content after skeleton
setTimeout(() => {
  const sg = document.getElementById('skelGroup');
  const rc = document.getElementById('realContent');
  const box = document.getElementById('instLogoBox');

  // Populate
  document.getElementById('realName').textContent    = tenant.name;
  document.getElementById('realTagline').textContent = tenant.tagline;
  document.getElementById('realRole').textContent    = getRoleLabel(tenant.role);

  if (tenant.logo) {
    const img = document.createElement('img');
    img.src = tenant.logo;
    img.alt = tenant.name;
    img.onerror = () => {
      box.innerHTML = '';
      box.appendChild(makeInitialsLogo(tenant.name));
    };
    box.appendChild(img);
  } else {
    box.appendChild(makeInitialsLogo(tenant.name));
  }

  // Fade skeleton out
  sg.style.transition = 'opacity 0.35s ease';
  sg.style.opacity = '0';
  setTimeout(() => {
    sg.style.display = 'none';
    rc.classList.add('show');
  }, 350);

}, SKEL_MS);

// Complete progress and redirect
setTimeout(() => {
  clearInterval(ticker);
  fill.style.transition = 'width 0.4s ease';
  fill.style.width = '100%';
  
  // Redirect to dashboard
  setTimeout(() => {
    window.location.href = REDIRECT_URL;
  }, 450);
}, REDIRECT_MS);
</script>
</body>
</html>
HTML;
    }
    
    /**
     * Get display label for role
     */
    private function getRoleLabel($role) {
        $roleMap = [
            'student' => 'Student Portal',
            'teacher' => 'Teacher Dashboard',
            'admin' => 'Admin Panel',
            'instituteadmin' => 'Institute Admin',
            'frontdesk' => 'Front Desk',
            'front desk' => 'Front Desk',
            'principal' => 'Principal Office',
            'superadmin' => 'Super Admin',
            'guardian' => 'Guardian Portal'
        ];
        return $roleMap[strtolower($role)] ?? ucfirst($role) . ' Portal';
    }
    
    /**
     * Generate initials from institute name
     */
    private function generateInitials($name) {
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials ?: 'IN';
    }
    
    /**
     * Darken a hex color
     */
    private function darkenColor($hex, $percent) {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - $percent * 2.55);
        $g = max(0, hexdec(substr($hex, 2, 2)) - $percent * 2.55);
        $b = max(0, hexdec(substr($hex, 4, 2)) - $percent * 2.55);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Lighten a hex color
     */
    private function lightenColor($hex, $percent) {
        $hex = ltrim($hex, '#');
        $r = min(255, hexdec(substr($hex, 0, 2)) + $percent * 2.55);
        $g = min(255, hexdec(substr($hex, 2, 2)) + $percent * 2.55);
        $b = min(255, hexdec(substr($hex, 4, 2)) + $percent * 2.55);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Convert hex to rgba
     */
    private function hexToRgba($hex, $alpha) {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r, $g, $b, $alpha)";
    }
}
