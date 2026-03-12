<?php
/**
 * Hamro ERP — Centralized Header
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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php
        // Load Vite Assets
        require_once __DIR__ . '/../../../app/Support/ViteAsset.php';
        echo \App\Support\ViteAsset::tags(['resources/css/app.scss', 'resources/js/app.js']);
    ?>


    <!-- Custom Styles (Using APP_URL for absolute paths) -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/core.css">
    <?php if (empty($roleCSS) || $roleCSS !== 'ia-dashboard-new.css'): ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/layout.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/components.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/utilities.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/pwa-install.css">
    
    <!-- Nexus Design System -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/ia-form-components.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/ia-add-student-v2.css">

    
    <?php if ($roleCSS): ?>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/<?php echo $roleCSS; ?>">
    <?php endif; ?>

    <!-- PWA Settings -->
    <link rel="manifest" href="<?php echo APP_URL; ?>/public/manifest.json">
    <meta name="theme-color" content="<?php echo $themeColor; ?>">
    <link rel="apple-touch-icon" href="<?php echo APP_URL; ?>/public/assets/images/logo.png">
    <link rel="icon" type="image/svg+xml" href="<?php echo APP_URL; ?>/public/assets/images/favicon.svg">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <script>
        window.APP_URL = "<?php echo APP_URL; ?>";
    </script>
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">
    <div class="sb-overlay" id="sbOverlay"></div>
    <div class="<?php echo $wrapperClass ?? 'root'; ?>">
