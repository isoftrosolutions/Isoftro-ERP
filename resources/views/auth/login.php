<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — iSoftro Academic ERP</title>
  <meta name="description" content="Login to your iSoftro Academic ERP dashboard.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <?php require_once base_path('config/config.php'); ?>
  <?= \App\Helpers\CsrfHelper::csrfMetaTag() ?>
  <?php $BASE = defined('APP_URL') ? APP_URL : '/erp'; ?>
  <link rel="stylesheet" href="<?= $BASE ?>/public/assets/css/login.css">
  
  <!-- PWA Settings -->
  <link rel="manifest" href="<?= $BASE ?>/manifest.json">
  <meta name="theme-color" content="#006D44">
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/public/assets/images/favicon.svg">
  <link rel="apple-touch-icon" href="<?= $BASE ?>/public/assets/images/logo.png">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="iSoftro ERP">
  <style>
    :root {
      --green: #006D44;
      --green-d: #004D30;
      --green-light: #8CC63F;
      --navy: #003D2E;
      --text-dark: #1A3C34;
      --text-body: #4A6355;
      --text-light: #7A9488;
      --red: #D32F2F;
      --white: #ffffff;
      --font: 'Poppins', sans-serif;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: var(--font); }

    /* Alert banner */
    .lp-alert {
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 20px;
      display: none;
    }
    .lp-alert--error { background: #FDE8E8; color: #D32F2F; border: 1px solid #FECACA; display: block; }
    .lp-alert--success { background: #E8F5E0; color: #006D44; border: 1px solid #A8D86B; display: block; }
    .lp-label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
      text-align: left;
    }
  </style>
</head>
<body class="lp-body">
  <div class="lp-container">
    <!-- LEFT SIDE: ILLUSTRATION -->
    <div class="lp-left">
      <div class="lp-illus-wrap">
        <div style="text-align:center;">
          <div style="width:min(320px, 80vw); height:min(320px, 80vw); margin:0 auto; background:linear-gradient(135deg,#006D44,#8CC63F); border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 30px 60px rgba(0,109,68,0.25);">
            <div style="text-align:center; color:#fff; padding: 20px;">
              <img src="<?= $BASE ?>/public/assets/images/logo.png" alt="isoftro" style="width:100%; max-width:120px; height:auto; margin-bottom:16px; filter: brightness(0) invert(1);">
              <div style="font-size:clamp(18px, 4vw, 28px); font-weight:800; letter-spacing:-0.5px;">isoftro</div>
              <div style="font-size:11px; font-weight:500; opacity:0.8; letter-spacing:2px;">ACADEMIC ERP</div>
            </div>
          </div>
          <p style="margin-top:40px; font-size:clamp(16px, 2vw, 18px); font-weight:700; color:#004D30; padding:0 20px;">Manage Your Institute Smartly</p>
          <p style="margin-top:8px; font-size:14px; color:#4A6355; max-width:360px; margin-left:auto; margin-right:auto; padding:0 20px;">Cloud-based academic management for schools, colleges, and coaching centers across Nepal.</p>
        </div>
      </div>
    </div>

    <!-- RIGHT SIDE: LOGIN FORM -->
    <div class="lp-right">
      <div class="lp-form-box">
        <div class="mobile-header">
            <img src="<?= $BASE ?>/public/assets/images/logo.png" alt="Logo">
            <div class="mobile-header-text">
              <span class="mh-top">ISOFTRO</span>
              <span class="mh-bot">ACADEMIC ERP</span>
            </div>
          </div>

          <div class="lp-header">
          <div class="lp-logo">
            <img src="<?= $BASE ?>/public/assets/images/logo.png" alt="Logo" style="height:32px; width:auto; margin-right:12px;">
            <div class="logo-text">
              <span class="lt-top">ISOFTRO</span>
              <span class="lt-bot">ACADEMIC ERP</span>
            </div>
          </div>

          <h1 class="lp-title">Welcome Back</h1>
          <p class="lp-subtitle">Sign in to access your dashboard.</p>
        </div>

        <div id="loginAlert" class="lp-alert"></div>

        <form id="loginForm" class="lp-form" novalidate>
          <div class="form-group">
            <label for="username" class="lp-label">Email Address</label>
            <input type="email" id="username" name="username" class="lp-input" placeholder="e.g. admin@hamrolabs.com" required autocomplete="email">
          </div>

          <div class="form-group" style="position: relative;">
            <label for="password" class="lp-label">Password</label>
            <input type="password" id="password" name="password" class="lp-input" placeholder="••••••••" required style="padding-right: 50px;" autocomplete="current-password">
            <button type="button" id="togglePassword" style="position:absolute;right:15px;top:42px;background:none;border:none;color:var(--text-light);cursor:pointer;font-size:18px;padding:5px;outline:none;">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="lp-options">
            <label class="lp-check">
              <input type="checkbox" id="remember" name="remember">
              <span class="checkmark"></span>
              Remember Me
            </label>
            <a href="<?= $BASE ?>/auth/forgot-password" class="lp-forgot">Forgot Password?</a>
          </div>

          <button type="submit" class="lp-btn" id="loginBtn">
            <span id="btnText">Login</span>
            <i class="fa-solid fa-spinner fa-spin" id="btnSpinner" style="display:none;"></i>
          </button>
        </form>

        <div style="margin-top:32px;text-align:center;">
          <a href="<?= $BASE ?>/" style="color:var(--green);font-weight:600;text-decoration:none;font-size:14px;">
            <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back to Home
          </a>
        </div>
      </div>

      <!-- PWA Install Banner -->
      <div id="pwaInstallBanner" class="pwa-install-banner" style="display: none;">
        <div class="pwa-content">
          <div class="pwa-icon-box">
            <img src="<?= $BASE ?>/public/assets/images/logo.png" alt="App Icon">
          </div>
          <div class="pwa-text">
            <h3>Install isoftro ERP</h3>
            <p>Install our app on your home screen for a faster, better experience.</p>
          </div>
          <button type="button" class="pwa-install-btn" onclick="triggerPwaInstall()">
            Install Now
          </button>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* PWA Install Banner Styling */
    .pwa-install-banner {
      margin-top: 24px;
      background: #ffffff;
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 10px 30px rgba(0, 109, 68, 0.1);
      border: 1px solid rgba(0, 109, 68, 0.1);
      animation: slideUp 0.5s ease-out;
    }
    .pwa-content {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .pwa-icon-box {
      width: 48px;
      height: 48px;
      background: #F0FDF4;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .pwa-icon-box img {
      width: 28px;
      height: auto;
    }
    .pwa-text {
      flex: 1;
    }
    .pwa-text h3 {
      font-size: 15px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 2px;
    }
    .pwa-text p {
      font-size: 12px;
      color: var(--text-body);
      line-height: 1.4;
    }
    .pwa-install-btn {
      background: var(--green);
      color: #fff;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      white-space: nowrap;
    }
    .pwa-install-btn:hover {
      background: var(--green-d);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 109, 68, 0.2);
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 991px) {
      .mobile-header {
        display: flex !important;
      }
    }
    .mobile-header {
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px 16px 30px;
      gap: 12px;
    }
    .mobile-header img {
      height: 36px;
    }
    .mobile-header-text {
      display: flex;
      flex-direction: column;
    }
    .mobile-header-text .mh-top {
      font-size: 20px;
      font-weight: 800;
      color: var(--green);
      line-height: 1;
    }
    .mobile-header-text .mh-bot {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-dark);
      letter-spacing: 1px;
      opacity: 0.8;
    }
    @media (max-width: 480px) {
      .pwa-content { flex-direction: column; text-align: center; }
      .pwa-install-btn { width: 100%; }
    }
    .mobile-install-btn {
      display: none;
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--green);
      color: #fff;
      border: none;
      padding: 14px 20px;
      border-radius: 50px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(0,109,68,0.3);
      z-index: 1000;
      align-items: center;
      gap: 8px;
    }
    .mobile-install-btn:hover {
      background: var(--green-d);
      transform: translateY(-2px);
    }
    @media (max-width: 991px) {
      .mobile-install-btn { display: flex; }
    }
  </style>

  <script>
  window.APP_URL = '<?= $BASE ?>';
  const APP_URL = window.APP_URL;
  </script>
  <?= \App\Helpers\CsrfHelper::csrfJsHeader() ?>
  <script src="<?= $BASE ?>/public/assets/js/pwa-handler.js"></script>
  <script src="<?= $BASE ?>/public/assets/js/login.js"></script>
  <button class="mobile-install-btn" id="mobileInstallBtn" onclick="triggerPwaInstall()">
    <i class="fa-solid fa-download"></i> Install App
  </button>
</body>
</html>
