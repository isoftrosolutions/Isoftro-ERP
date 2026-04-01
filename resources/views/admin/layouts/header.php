<?php
/**
 * Institute Admin — Header Navigation (Refactored - Mobile First)
 * Fully responsive, all navigation fixed, logo issues resolved
 */
$user = getCurrentUser();
$tenantName = $_SESSION['tenant_name'] ?? 'Institute';
$tenantId = $_SESSION['tenant_id'] ?? null;

// User initials
$initials = 'AD';
if($user && isset($user['name'])) {
    $parts = explode(' ', $user['name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

// Institute logo / name - fetch from session or database
$logoUrl = null;
$tenantLogo = $_SESSION['institute_logo'] ?? $_SESSION['tenant_logo'] ?? null;

// If we have a tenant ID but are missing logo or name (or have default names), hydrate from DB
if ($tenantId && (empty($tenantLogo) || in_array($tenantName, ['Dashboard', 'Institute', 'Hamro ERP']))) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT name, logo_path FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $tenantId]);
        $tenant = $stmt->fetch();
        if ($tenant) {
            // Logo
            if (empty($tenantLogo) && !empty($tenant['logo_path'])) {
                $tenantLogo = $tenant['logo_path'];
                $_SESSION['tenant_logo'] = $tenantLogo;
                $_SESSION['institute_logo'] = $tenantLogo;
            }
            // Institute name (fallback when session not set or default)
            if (in_array($tenantName, ['Dashboard', 'Institute', 'Hamro ERP']) && !empty($tenant['name'])) {
                $tenantName = $tenant['name'];
                $_SESSION['tenant_name'] = $tenantName;
            }
        }
    } catch (Exception $e) {
        // Silent fail
    }
}

if (!empty($tenantLogo)) {
    // Strip any legacy /public prefix — production web root IS public/
    $logoRelativePath = $tenantLogo;
    if (strpos($logoRelativePath, '/public/') === 0) {
        $logoRelativePath = substr($logoRelativePath, 7);
    }
    $logoUrl = (strpos($logoRelativePath, 'http') === 0) ? $logoRelativePath : APP_URL . $logoRelativePath;
}

// Current page detection for active states
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#009E7E">
    <?php echo \App\Helpers\CsrfHelper::csrfMetaTag(); ?>
    <?php echo \App\Helpers\CsrfHelper::csrfJsHeader(); ?>
    
    <!-- Dedicated Styles -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/profile-dropdown.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/ia-support.css">

    <!-- Header Styles -->
    <style>
        /* CSS Variables */
        :root {
            --header-height: 60px;
            --header-bg: #ffffff;
            --header-border: #e5e7eb;
            --primary: #009E7E;
            --primary-dark: #008F6E;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
        }

        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Header Container */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--header-bg);
            border-bottom: 1px solid var(--header-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        /* Prevent breadcrumb from pushing items in the flex container */
        .header .breadcrumb {
            position: absolute;
            bottom: -22px;
            left: 16px;
            margin: 0;
            z-index: -1;
            pointer-events: auto;
        }

        /* Left Section */
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 0 0 auto;
        }

        /* Hamburger Menu */
        .menu-toggle {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .menu-toggle:hover {
            background: var(--bg-light);
        }

        .menu-toggle span {
            display: block;
            width: 22px;
            height: 2px;
            background: var(--text-dark);
            transition: all 0.3s;
            border-radius: 2px;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Institute Name */
        .institute-name {
            font-size: clamp(12px, 3.5vw, 16px);
            font-weight: 700;
            color: #ffffff;
            background: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
            padding: 6px 12px;
            border-radius: 9999px;
            flex-shrink: 0; /* Important: don't let it be squashed */
        }

        @media (min-width: 768px) {
            .institute-name {
                max-width: 250px;
            }
        }

        @media (min-width: 1024px) {
            .institute-name {
                max-width: 350px;
            }
        }

        /* Center Section - Search */
        .header-center {
            flex: 1 1 auto;
            display: flex;
            justify-content: center;
            padding: 0 16px;
            max-width: 400px;
        }

        .search-box {
            position: relative;
            width: 100%;
            max-width: 300px;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            max-height: 380px;
            overflow-y: auto;
            z-index: 1100;
            font-size: 13px;
        }

        .search-results-section-title {
            padding: 6px 10px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
            background: #f9fafb;
        }

        .search-results-item {
            padding: 8px 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .search-results-item:hover {
            background: #f3f4f6;
        }

        .search-results-item-main {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #111827;
            gap: 10px;
        }

        .search-results-item-meta {
            font-size: 11px;
            color: #6b7280;
            padding-left: 38px;
        }

        .search-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            background: #f3f4f6;
        }

        .search-avatar-ph {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .search-box input {
            width: 100%;
            height: 38px;
            padding: 0 12px 0 38px;
            border: 1px solid var(--header-border);
            border-radius: 20px;
            font-size: 14px;
            background: var(--bg-light);
            transition: all 0.2s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 158, 126, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 14px;
        }

        /* Right Section */
        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        /* Quick Action Buttons */
        .quick-actions {
            display: none;
            align-items: center;
            gap: 4px;
        }

        @media (min-width: 1024px) {
            .quick-actions {
                display: flex;
            }
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 16px;
            position: relative;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: var(--bg-light);
            color: #ffffff;
        }

        .btn-icon .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 16px;
            text-align: center;
        }

        /* Profile Section */
        .profile-section {
            position: relative;
        }

        .profile-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            border-radius: 50%;
            cursor: pointer;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: box-shadow 0.2s;
        }

        .profile-btn:hover {
            box-shadow: 0 0 0 3px rgba(0, 158, 126, 0.2);
        }

        .profile-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-initials {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        /* Dropdown Menus */
        .dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 18px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
            min-width: 260px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-15px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 1001;
            border: 1px solid rgba(255, 255, 255, 0.4);
            overflow: hidden;
        }

        .dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .dropdown-header {
            padding: 20px;
            background: linear-gradient(135deg, rgba(0, 158, 126, 0.08) 0%, rgba(255, 255, 255, 0) 100%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .dropdown-header .u-av-lg {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 158, 126, 0.2);
        }

        .dropdown-header .user-meta {
            flex: 1;
            overflow: hidden;
        }

        .dropdown-header .name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropdown-header .role {
            font-size: 11px;
            color: var(--text-light);
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .dropdown-menu {
            list-style: none;
            padding: 10px;
        }

        .dropdown-menu li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-radius: 10px;
        }

        .dropdown-menu li a:hover {
            background: rgba(0, 158, 126, 0.08);
            color: var(--primary);
            transform: translateX(4px);
        }

        .dropdown-menu li a i {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border-radius: 8px;
            color: var(--text-light);
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .dropdown-menu li a:hover i {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(0, 0, 0, 0.05);
            margin: 8px 10px;
        }

        .dropdown-menu .logout {
            color: #ef4444;
        }

        .dropdown-menu .logout i {
            background: #fef2f2;
            color: #ef4444;
        }
        
        .dropdown-menu li a.logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .dropdown-menu li a.logout:hover i {
            background: #dc2626;
            color: white;
        }

        /* Mobile Menu Overlay */
        .mobile-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Mobile Search Overlay */
        .mobile-search {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            background: white;
            padding: 16px;
            border-bottom: 1px solid var(--header-border);
            transform: translateY(-100%);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 998;
        }

        .mobile-search.active {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }

        .mobile-search input {
            width: 100%;
            height: 44px;
            padding: 0 16px;
            border: 2px solid var(--primary);
            border-radius: 8px;
            font-size: 16px;
        }

        /* Notifications Panel */
        .notifications-panel {
            position: fixed;
            top: var(--header-height);
            right: -100%;
            width: 100%;
            max-width: 380px;
            height: calc(100vh - var(--header-height));
            background: white;
            box-shadow: var(--shadow-lg);
            transition: right 0.3s;
            z-index: 1001;
            display: flex;
            flex-direction: column;
        }

        .notifications-panel.active {
            right: 0;
        }

        .panel-header {
            padding: 16px;
            border-bottom: 1px solid var(--header-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%) scale(0.8);
            background: var(--text-dark);
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
            pointer-events: none;
        }

        [data-tooltip]:hover::after {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) scale(1);
        }

        /* Hide on mobile */
        @media (max-width: 767px) {
            .header-center {
                display: none;
            }
        }

        /* AY Display */
        .ay-display {
            display: none;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-light);
            border-radius: 20px;
            font-size: 13px;
            color: var(--text-light);
        }

        @media (min-width: 1200px) {
            .ay-display {
                display: flex;
            }
        }

        .ay-display i {
            color: var(--primary);
        }

        /* Mobile action buttons */
        .mobile-actions {
            display: flex;
            gap: 4px;
        }

        @media (min-width: 768px) {
            .mobile-actions {
                display: none;
            }
        }
        @media (max-width: 767px) {
            .pd-menu-new {
                position: fixed;
                top: auto;
                bottom: 20px;
                left: 20px;
                right: 20px;
                width: auto;
                transform: translateY(100%);
            }
            .pd-menu-new.active {
                transform: translateY(0);
            }
        }
    </style>
    
    <!-- Profile Dropdown JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/profile-dropdown.js" defer></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <!-- Left Section -->
        <div class="header-left">
            <button class="menu-toggle js-sidebar-toggle" id="menuToggle" aria-label="Toggle Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <div class="institute-name" title="<?php echo htmlspecialchars($tenantName); ?>">
                <?php echo htmlspecialchars($tenantName); ?>
            </div>
        </div>

        <!-- Center - Search (Desktop) -->
        <div class="header-center">
            <div class="search-box">
                <i class="fa-solid fa-search"></i>
                <input
                    type="text"
                    id="globalSearch"
                    placeholder="Search students / staff by ID, roll, name..."
                    autocomplete="off"
                >
                <div id="globalSearchResults" class="search-results" style="display:none;"></div>
            </div>
        </div>

        <!-- Right Section -->
        <div class="header-right">
            <!-- Academic Year (Desktop) -->
            <div class="ay-display">
                <i class="fa-solid fa-calendar-alt"></i>
                <span>AY <?php echo date('Y')-1; ?>-<?php echo date('Y'); ?></span>
            </div>

            <!-- Mobile Search Toggle -->
            <button class="btn-icon mobile-actions" id="mobileSearchToggle" data-tooltip="Search">
                <i class="fa-solid fa-search"></i>
            </button>

            <!-- Quick Actions (Desktop) -->
            <div class="quick-actions">
                <button class="btn-icon" onclick="goNav('inq', 'list')" data-tooltip="KYC / Admission">
                    <i class="fa-solid fa-id-card"></i>
                </button>
                
                <button class="btn-icon" onclick="window.location.reload()" data-tooltip="Refresh">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                
                <button class="btn-icon" onclick="goNav('comms', 'sms')" data-tooltip="Messages">
                    <i class="fa-solid fa-comment-dots"></i>
                    <span class="badge" id="msgBadge" style="display:none">0</span>
                </button>
                
                <button class="btn-icon" onclick="goNav('settings', 'notif')" data-tooltip="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="badge" id="notifBadge">3</span>
                </button>
                
                <button class="btn-icon" onclick="goNav('support')" data-tooltip="Support">
                    <i class="fa-solid fa-headset"></i>
                </button>
            </div>

            <!-- ── PREMIUM PROFILE DROPDOWN ── -->
            <div class="pd-container">
                <button class="pd-trigger" id="pdTrigger" aria-label="User Menu">
                    <?php if (!empty($logoUrl)): ?>
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Avatar" 
                             onerror="this.style.display='none'; this.parentElement.querySelector('.pd-initials').style.display='flex';">
                        <div class="pd-initials" style="display:none;"><?php echo $initials; ?></div>
                    <?php else: ?>
                        <div class="pd-initials"><?php echo $initials; ?></div>
                    <?php endif; ?>
                </button>

                <div class="pd-menu-new" id="pdMenuNew">
                    <div class="pd-header-new">
                        <span class="pd-user-name"><?php echo htmlspecialchars($user['name'] ?? 'Priyanka Kumari Sah'); ?></span>
                        <span class="pd-user-role"><?php echo htmlspecialchars($user['role'] ?? 'Institute Admin'); ?></span>
                    </div>

                    <div class="pd-divider-new"></div>

                    <ul class="pd-list-new">
                        <li class="pd-item-new">
                            <a href="javascript:void(0)" onclick="goNav('settings', 'user-prof')">
                                <i class="fa-regular fa-circle-user"></i> My Profile
                            </a>
                        </li>
                        <li class="pd-item-new">
                            <a href="javascript:void(0)" onclick="goNav('settings', 'prof')">
                                <i class="fa-solid fa-sliders"></i> Settings
                            </a>
                        </li>
                        <li class="pd-item-new">
                            <a href="javascript:void(0)" onclick="goNav('settings', 'notif')">
                                <i class="fa-regular fa-bell"></i> Notifications 
                                <span class="pd-badge-new">3</span>
                            </a>
                        </li>
                    </ul>

                    <div class="pd-divider-new"></div>

                    <ul class="pd-list-new">
                        <li class="pd-item-new">
                            <a href="javascript:void(0)" onclick="goNav('support')">
                                <i class="fa-solid fa-circle-question"></i> Help & Support
                            </a>
                        </li>
                        <li class="pd-item-new">
                            <a href="javascript:void(0)" onclick="goNav('feedback')">
                                <i class="fa-regular fa-paper-plane"></i> Feedback
                            </a>
                        </li>
                    </ul>

                    <div class="pd-divider-new"></div>

                    <ul class="pd-list-new">
                        <li class="pd-item-new danger">
                            <a href="<?= APP_URL ?>/logout.php">
                                <i class="fa-solid fa-power-off"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- (REMOVED: profile dropdown logic moved to profile-dropdown.js) -->
        </div>
    </header>

    <!-- Mobile Search Overlay -->
    <div class="mobile-search" id="mobileSearch">
        <input
            type="text"
            id="mobileSearchInput"
            placeholder="Search students / staff by ID, roll, name..."
            autocomplete="off"
        >
    </div>

    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Notifications Panel -->
    <div class="notifications-panel" id="notificationsPanel">
        <div class="panel-header">
            <h3><i class="fa-solid fa-bell"></i> Notifications</h3>
            <button class="btn-icon" id="closeNotifPanel">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="panel-content">
            <div style="padding: 20px; text-align: center; color: var(--text-light);">
                <i class="fa-solid fa-bell" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <p>No new notifications</p>
            </div>
        </div>
    </div>

    <!-- Spacer for fixed header -->
    <div style="height: var(--header-height);"></div>

    <!-- Header JavaScript -->
    <script>
        (function() {
            // Elements
            const menuToggle = document.getElementById('menuToggle');
            const profileToggle = document.getElementById('profileToggle');
            const profileDropdown = document.getElementById('profileDropdown');
            const mobileSearchToggle = document.getElementById('mobileSearchToggle');
            const mobileSearch = document.getElementById('mobileSearch');
            const mobileOverlay = document.getElementById('mobileOverlay');
            const sbOverlay = document.getElementById('sbOverlay');
            const sbClose = document.getElementById('sbClose');
            const notifToggle = document.getElementById('notifToggle');
            const notificationsPanel = document.getElementById('notificationsPanel');
            const closeNotifPanel = document.getElementById('closeNotifPanel');

            // Toggle Sidebar (Menu) - use global sb-active pattern
            if (menuToggle) {
                menuToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    
                    // On mobile, this toggles the overlay view
                    document.body.classList.toggle('sb-active');
                    
                    // On desktop, this toggles the collapsed state (rail view)
                    document.body.classList.toggle('sb-collapsed');

                    // Toggle sidebar overlay if present
                    if (sbOverlay) {
                        sbOverlay.classList.toggle('active');
                    }
                });
            }

            // Close sidebar when clicking overlay or close button inside sidebar (if available)
            function closeSidebar() {
                document.body.classList.remove('sb-active');
                if (menuToggle) menuToggle.classList.remove('active');
                if (sbOverlay) sbOverlay.classList.remove('active');
            }

            if (sbOverlay) {
                sbOverlay.addEventListener('click', closeSidebar);
            }

            if (sbClose) {
                sbClose.addEventListener('click', closeSidebar);
            }

            // Toggle Profile Dropdown
            if (profileToggle) {
                profileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('active');
                });

                // Close on outside click
                document.addEventListener('click', function(e) {
                    if (!profileDropdown.contains(e.target) && !profileToggle.contains(e.target)) {
                        profileDropdown.classList.remove('active');
                    }
                });
            }

            // Toggle Mobile Search
            if (mobileSearchToggle && mobileSearch) {
                mobileSearchToggle.addEventListener('click', function() {
                    mobileSearch.classList.toggle('active');
                    if (mobileSearch.classList.contains('active')) {
                        document.getElementById('mobileSearchInput').focus();
                    }
                });
            }

            // Toggle Notifications Panel
            if (notifToggle && notificationsPanel) {
                notifToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationsPanel.classList.toggle('active');
                });
            }

            if (closeNotifPanel) {
                closeNotifPanel.addEventListener('click', function() {
                    notificationsPanel.classList.remove('active');
                });
            }

            // Mobile Overlay - Close everything
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    this.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                    if (menuToggle) menuToggle.classList.remove('active');
                    if (mobileSearch) mobileSearch.classList.remove('active');
                });
            }

            // Global Search Functionality (live results)
            const globalSearch = document.getElementById('globalSearch');
            const mobileSearchInput = document.getElementById('mobileSearchInput');
            const searchResults = document.getElementById('globalSearchResults');
            let searchTimeout = null;

            function hideSearchResults() {
                if (searchResults) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                }
            }

            function renderSearchResults(data, query) {
                if (!searchResults) return;

                const hasAny =
                    (data.students && data.students.length) ||
                    (data.teachers && data.teachers.length) ||
                    (data.batches && data.batches.length) ||
                    (data.courses && data.courses.length);

                if (!hasAny) {
                    searchResults.innerHTML = '<div class="search-results-item"><div class="search-results-item-main">No matches for "<strong>' +
                        (query || '') + '</strong>"</div></div>';
                    searchResults.style.display = 'block';
                    return;
                }

                let html = '';

                if (data.students && data.students.length) {
                    html += '<div class="search-results-section-title">Students</div>';
                    data.students.forEach(s => {
                        const name = s.name || '';
                        const roll = s.roll_no ? 'Roll: ' + s.roll_no : '';
                        const meta = [roll, s.phone, s.email].filter(Boolean).join(' • ');
                        
                        // Avatar logic
                        const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                        const photoUrl = s.photo_url || null;
                        const avatarHtml = photoUrl 
                            ? `<img src="${photoUrl}" class="search-avatar" onerror="this.outerHTML='<div class=\\'search-avatar-ph\\'>${initials}</div>'">`
                            : `<div class="search-avatar-ph">${initials}</div>`;

                        html += '<div class="search-results-item" data-type="student" data-id="' + s.id + '">';
                        html +=   '<div class="search-results-item-main">';
                        html +=     avatarHtml;
                        html +=     '<span>' + name + '</span>';
                        html +=   '</div>';
                        if (meta) {
                            html += '<div class="search-results-item-meta">' + meta + '</div>';
                        }
                        html += '</div>';
                    });
                }

                if (data.teachers && data.teachers.length) {
                    html += '<div class="search-results-section-title">Staff</div>';
                    data.teachers.forEach(t => {
                        const name = t.name || '';
                        const role = t.role || 'Teacher';
                        const meta = [role, t.phone, t.email].filter(Boolean).join(' • ');
                        html += '<div class="search-results-item" data-type="teacher" data-id="' + t.id + '">';
                        html +=   '<div class="search-results-item-main"><span>' + name + '</span></div>';
                        if (meta) {
                            html += '<div class="search-results-item-meta">' + meta + '</div>';
                        }
                        html += '</div>';
                    });
                }

                if (data.batches && data.batches.length) {
                    html += '<div class="search-results-section-title">Batches</div>';
                    data.batches.forEach(b => {
                        const name = b.name || '';
                        const meta = b.course_name ? 'Course: ' + b.course_name : '';
                        html += '<div class="search-results-item" data-type="batch" data-id="' + b.id + '">';
                        html +=   '<div class="search-results-item-main"><span>' + name + '</span></div>';
                        if (meta) {
                            html += '<div class="search-results-item-meta">' + meta + '</div>';
                        }
                        html += '</div>';
                    });
                }

                if (data.courses && data.courses.length) {
                    html += '<div class="search-results-section-title">Courses</div>';
                    data.courses.forEach(c => {
                        const name = c.name || '';
                        html += '<div class="search-results-item" data-type="course" data-id="' + c.id + '">';
                        html +=   '<div class="search-results-item-main"><span>' + name + '</span></div>';
                        html += '</div>';
                    });
                }

                searchResults.innerHTML = html;
                searchResults.style.display = 'block';
            }

            function navigateToResult(type, id) {
                if (!type || !id) return;

                // Try to use goNav for soft navigation if available
                if (typeof window.goNav === 'function') {
                    switch (type) {
                        case 'student':
                            window.goNav('students', 'view', { id: id });
                            break;
                        case 'teacher':
                            window.goNav('teachers', 'list', { id: id }); // Fallback to list for now
                            break;
                        case 'batch':
                            window.goNav('academic', 'batches', { id: id });
                            break;
                        case 'course':
                            window.goNav('academic', 'courses', { id: id });
                            break;
                    }
                    hideSearchResults();
                } else {
                    // Fallback to full reload if goNav not yet loaded
                    let url = null;
                    switch (type) {
                        case 'student':
                            url = '<?php echo APP_URL; ?>/dash/admin?page=students-view&id=' + encodeURIComponent(id);
                            break;
                        case 'teacher':
                            url = '<?php echo APP_URL; ?>/dash/admin?page=teachers&id=' + encodeURIComponent(id);
                            break;
                        case 'batch':
                            url = '<?php echo APP_URL; ?>/dash/admin?page=academic-batches&id=' + encodeURIComponent(id);
                            break;
                        case 'course':
                            url = '<?php echo APP_URL; ?>/dash/admin?page=academic-courses&id=' + encodeURIComponent(id);
                            break;
                    }
                    if (url) {
                        window.location.href = url;
                    }
                }
            }

            function requestLiveSearch(query) {
                query = (query || '').trim();
                if (!searchResults) return;

                if (query.length < 2) {
                    hideSearchResults();
                    return;
                }

                // Call API endpoint (Laravel route)
                fetch('<?php echo APP_URL; ?>/api/admin/global-search?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        if (!data || data.success === false) {
                            hideSearchResults();
                            return;
                        }
                        renderSearchResults(data, query);
                    })
                    .catch(() => {
                        hideSearchResults();
                    });
            }

            if (searchResults) {
                searchResults.addEventListener('click', function(e) {
                    const item = e.target.closest('.search-results-item');
                    if (!item) return;
                    const type = item.getAttribute('data-type');
                    const id = item.getAttribute('data-id');
                    navigateToResult(type, id);
                });
            }

            if (globalSearch) {
                globalSearch.addEventListener('input', function() {
                    const value = this.value;
                    if (searchTimeout) clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => requestLiveSearch(value), 250);
                });

                globalSearch.addEventListener('focus', function() {
                    if (this.value && this.value.trim().length >= 2) {
                        requestLiveSearch(this.value);
                    }
                });

                globalSearch.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        hideSearchResults();
                    }
                });
            }

            if (mobileSearchInput) {
                mobileSearchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const q = (this.value || '').trim();
                        if (q.length >= 2) {
                            // Redirect to students list filtered by the query for now
                            // since there is no dedicated search results page
                            window.location.href = '<?php echo APP_URL; ?>/dash/admin?page=students&search=' + encodeURIComponent(q);
                        }
                        mobileSearch.classList.remove('active');
                    }
                });
            }

            // Update badges dynamically
            function updateBadges() {
                fetch('<?php echo APP_URL; ?>/api/notifications/count')
                    .then(r => r.json())
                    .then(data => {
                        const notifBadge = document.getElementById('notifBadge');
                        const msgBadge = document.getElementById('msgBadge');
                        
                        if (notifBadge && data.notifications > 0) {
                            notifBadge.textContent = data.notifications;
                            notifBadge.style.display = 'block';
                        }
                        if (msgBadge && data.messages > 0) {
                            msgBadge.textContent = data.messages;
                            msgBadge.style.display = 'block';
                        }
                    })
                    .catch(() => {}); // Silent fail
            }

            // Update badges every 5 minutes
            setInterval(updateBadges, 300000);
            updateBadges();
        })();
    </script>
</body>
</html>
