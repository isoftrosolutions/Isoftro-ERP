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
  <?php $BASE = defined('APP_URL') ? APP_URL : ''; ?>
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/login.css">

  <!-- PWA -->
  <link rel="manifest" href="<?= $BASE ?>/manifest.json">
  <meta name="theme-color" content="#006D44">
  <link rel="icon" type="image/svg+xml" href="<?= $BASE ?>/assets/images/favicon.svg">
  <link rel="apple-touch-icon" href="<?= $BASE ?>/assets/images/logo.png">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="iSoftro ERP">
</head>
<body class="lp-body">

  <div class="lp-wrap">

    <!-- ── LEFT PANEL ──────────────────────────────────── -->
    <div class="lp-panel">
      <div class="lp-grid-lines"></div>
      <div class="lp-panel-inner">

        <!-- Brand -->
        <div class="lp-brand">
          <div class="lp-brand-logo">
            <img src="<?= $BASE ?>/assets/images/logo.png" alt="iSoftro">
          </div>
          <div class="lp-brand-text">
            <strong>iSoftro</strong>
            <span>Academic ERP</span>
          </div>
        </div>

        <!-- Headline -->
        <h1 class="lp-headline">Manage Your Institute<br><em>Smarter &amp; Faster</em></h1>
        <p class="lp-desc">Cloud-based academic management for schools, colleges, and coaching centers across Nepal.</p>

        <!-- Features -->
        <ul class="lp-features">
          <li>
            <span class="feat-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            Student &amp; attendance management
          </li>
          <li>
            <span class="feat-icon"><i class="fa-solid fa-file-invoice"></i></span>
            Fee collection &amp; financial reports
          </li>
          <li>
            <span class="feat-icon"><i class="fa-solid fa-calendar-days"></i></span>
            Timetable, exams &amp; results
          </li>
          <li>
            <span class="feat-icon"><i class="fa-solid fa-shield-halved"></i></span>
            Role-based secure access
          </li>
        </ul>

        <!-- Stats -->
        <div class="lp-stats">
          <div>
            <div class="lp-stat-num">500+</div>
            <div class="lp-stat-lbl">Institutes</div>
          </div>
          <div>
            <div class="lp-stat-num">50K+</div>
            <div class="lp-stat-lbl">Students</div>
          </div>
          <div>
            <div class="lp-stat-num">99.9%</div>
            <div class="lp-stat-lbl">Uptime</div>
          </div>
        </div>

      </div><!-- /lp-panel-inner -->
    </div><!-- /lp-panel -->

    <!-- ── RIGHT PANEL ─────────────────────────────────── -->
    <div class="lp-form-side">
      <div class="lp-form-card">

        <!-- Mobile brand (shown < 768px) -->
        <div class="lp-mobile-brand">
          <div class="lp-mobile-brand-dot">
            <img src="<?= $BASE ?>/assets/images/logo.png" alt="iSoftro">
          </div>
          <div class="lp-mobile-brand-text">
            <strong>iSoftro</strong>
            <span>Academic ERP</span>
          </div>
        </div>

        <!-- Header -->
        <div class="lp-form-header">
          <h2 class="lp-greeting">Welcome back</h2>
          <p class="lp-sub">Sign in to access your dashboard.</p>
        </div>

        <!-- Alert -->
        <div id="loginAlert" class="lp-alert"></div>

        <!-- Form -->
        <form id="loginForm" novalidate>

          <div class="lp-field">
            <label for="username" class="lp-label">Email Address</label>
            <div class="lp-input-wrap">
              <span class="lp-input-icon"><i class="fa-regular fa-envelope"></i></span>
              <input
                type="email"
                id="username"
                name="username"
                class="lp-input"
                placeholder="admin@hamrolabs.com"
                required
                autocomplete="email"
              >
            </div>
          </div>

          <div class="lp-field">
            <label for="password" class="lp-label">Password</label>
            <div class="lp-input-wrap">
              <span class="lp-input-icon"><i class="fa-solid fa-lock"></i></span>
              <input
                type="password"
                id="password"
                name="password"
                class="lp-input"
                placeholder="••••••••"
                required
                autocomplete="current-password"
                style="padding-right: 48px;"
              >
              <button type="button" class="lp-pw-toggle" id="togglePassword" aria-label="Toggle password visibility">
                <i class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="lp-options">
            <label class="lp-check-label">
              <input type="checkbox" id="remember" name="remember">
              <span class="lp-checkmark"></span>
              Remember Me
            </label>
            <a href="<?= $BASE ?>/auth/forgot-password" class="lp-forgot">Forgot Password?</a>
          </div>

          <button type="submit" class="lp-btn" id="loginBtn">
            <span id="btnText">Sign In</span>
            <i class="fa-solid fa-arrow-right-to-bracket" id="btnIcon"></i>
            <i class="fa-solid fa-spinner fa-spin" id="btnSpinner" style="display:none;"></i>
          </button>

        </form>

        <!-- PWA Install Banner -->
        <div id="pwaInstallBanner" class="pwa-install-banner" style="display:none;">
          <div class="pwa-icon-box">
            <img src="<?= $BASE ?>/assets/images/logo.png" alt="App Icon">
          </div>
          <div class="pwa-text">
            <h3>Install iSoftro ERP</h3>
            <p>Add to your home screen for a faster experience.</p>
          </div>
          <button type="button" class="pwa-install-btn" onclick="triggerPwaInstall()">
            Install
          </button>
        </div>

        <!-- Back link -->
        <div class="lp-back">
          <a href="<?= $BASE ?>/">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Home
          </a>
        </div>

      </div><!-- /lp-form-card -->
    </div><!-- /lp-form-side -->

  </div><!-- /lp-wrap -->

  <!-- Mobile FAB install button -->
  <button class="mobile-install-btn" id="mobileInstallBtn" onclick="triggerPwaInstall()">
    <i class="fa-solid fa-download"></i> Install App
  </button>

  <script>
    window.APP_URL = '<?= $BASE ?>';
    const APP_URL = window.APP_URL;
  </script>
  <?= \App\Helpers\CsrfHelper::csrfJsHeader() ?>
  <script src="<?= $BASE ?>/assets/js/pwa-handler.js"></script>
  <script src="<?= $BASE ?>/assets/js/login.js"></script>
</body>
</html>
