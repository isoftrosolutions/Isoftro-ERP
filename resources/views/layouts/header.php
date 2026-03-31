<?php
/**
 * isoftro — Centralized Header
 * Platform Blueprint V3.0
 * 
 * This file is included in all dashboard pages to ensure consistent
 * asset loading and meta information management.
 */

// Fallback values if not set by including page
$pageTitle = $pageTitle ?? APP_NAME;
$themeColor = $themeColor ?? '#00B894';
$roleCSS = $roleCSS ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | <?php echo $pageTitle; ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php
        // Load Vite Assets
        require_once __DIR__ . '/../../../app/Support/ViteAsset.php';
        echo \App\Support\ViteAsset::tags(['resources/css/app.scss', 'resources/js/app.js']);
    ?>


    <!-- Custom Styles (Using APP_URL for absolute paths) -->
    <?php $cssFile = realpath(__DIR__ . '/../../../public/assets/css/core.css'); $cssVer = (defined('APP_DEBUG') && APP_DEBUG) ? time() : ($cssFile ? filemtime($cssFile) : time()); ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/core.css?v=<?php echo $cssVer; ?>">
    <?php if (empty($roleCSS) || $roleCSS !== 'ia-dashboard-new.css'): ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/layout.css?v=<?php echo $cssVer; ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/components.css?v=<?php echo $cssVer; ?>">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/utilities.css?v=<?php echo $cssVer; ?>">

    <!-- Nexus Design System -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/ia-form-components.css?v=<?php echo $cssVer; ?>">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/ia-add-student-v2.css?v=<?php echo $cssVer; ?>">

    <?php if ($roleCSS): ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/<?php echo $roleCSS; ?>?v=<?php echo $cssVer; ?>">
    <?php endif; ?>

    <!-- PWA Settings -->
    <link rel="manifest" href="<?php echo APP_URL; ?>/manifest.json">
    <meta name="theme-color" content="<?php echo $themeColor; ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo APP_URL; ?>/assets/images/favicon.svg">
    <link rel="apple-touch-icon" href="<?php echo APP_URL; ?>/assets/images/logo.png">
    <script src="<?php echo APP_URL; ?>/assets/js/auth-helper.js"></script>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="iSoftro ERP">

    <script>
        window.APP_URL = "<?php echo APP_URL; ?>";
        window.baseUrl = "<?php echo APP_URL; ?>";
        window.currentTenantId = "<?php echo $_SESSION['userData']['tenant_id'] ?? $_SESSION['tenant_id'] ?? ''; ?>";
    </script>
    
    <!-- CSRF Protection (Dynamic Synchronization) -->
    <?php 
        if (function_exists('csrfMetaTag')) {
            echo csrfMetaTag();
            echo csrfJsHeader();
        }
    ?>

</head>
<body class="<?php echo $bodyClass ?? ''; ?>">
    <div class="sb-overlay" id="sbOverlay"></div>
    <div class="<?php echo $wrapperClass ?? 'root'; ?>">
