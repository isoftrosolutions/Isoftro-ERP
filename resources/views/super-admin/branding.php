<?php
/**
 * Hamro ERP — Platform Branding
 * Configure platform-wide branding settings
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}
require_once VIEWS_PATH . '/layouts/header_1.php';

$pdo = getDBConnection();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security violation: Invalid CSRF token.';
        $messageType = 'error';
    } else {
        try {
            $settings = [
                ['platform_name', $_POST['platform_name'] ?? 'Hamro Labs ERP', 'string'],
                ['platform_logo', $_POST['platform_logo'] ?? '/logo.png', 'string'],
                ['platform_primary_color', $_POST['primary_color'] ?? '#009E7E', 'string'],
                ['platform_secondary_color', $_POST['secondary_color'] ?? '#6c757d', 'string'],
                ['platform_footer_text', $_POST['footer_text'] ?? '© 2026 Hamro Labs. All rights reserved.', 'string'],
                ['support_email', $_POST['support_email'] ?? 'support@hamrolabs.com', 'string'],
            ];
            
            foreach ($settings as $setting) {
                $stmt = $pdo->prepare("
                    INSERT INTO platform_settings (setting_key, setting_value, setting_type, description, is_public)
                    VALUES (?, ?, ?, 'Platform branding', 1)
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$setting[0], $setting[1], $setting[2], $setting[1]]);
            }
            
            $message = 'Branding settings saved successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get current settings
$settings = [];
try {
    $result = $pdo->query("SELECT setting_key, setting_value FROM platform_settings WHERE is_public = 1");
    while ($row = $result->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use defaults
}

$defaults = [
    'platform_name' => 'Hamro Labs ERP',
    'platform_logo' => '/logo.png',
    'platform_primary_color' => '#009E7E',
    'platform_secondary_color' => '#6c757d',
    'platform_footer_text' => '© 2026 Hamro Labs. All rights reserved.',
    'support_email' => 'support@hamrolabs.com',
];

$settings = array_merge($defaults, $settings);

$pageTitle = 'Platform Branding';
renderSuperAdminHeader();
renderSidebar('branding.php');
?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-p"><i class="fa-solid fa-palette"></i></div>
                <div>
                    <div class="pg-title">Platform Branding</div>
                    <div class="pg-sub">Configure platform-wide branding and visual identity</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Preview -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="ct"><i class="fa-solid fa-eye"></i> Live Preview</div>
            <div class="preview-container" style="background: #f8f9fa; padding: 30px; border-radius: 8px; text-align: center;">
                <div class="preview-header" style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 20px;">
                    <img src="<?php echo htmlspecialchars($settings['platform_logo']); ?>" alt="Logo" style="height: 32px;">
                    <span class="preview-title" style="font-size: 24px; font-weight: 700; color: <?php echo htmlspecialchars($settings['platform_primary_color']); ?>;">
                        <?php echo htmlspecialchars($settings['platform_name']); ?>
                    </span>
                </div>
                <div class="preview-buttons" style="display: flex; gap: 12px; justify-content: center;">
                    <button style="background: <?php echo htmlspecialchars($settings['platform_primary_color']); ?>; color: white; padding: 10px 20px; border: none; border-radius: 6px;">Primary</button>
                    <button style="background: <?php echo htmlspecialchars($settings['platform_secondary_color']); ?>; color: white; padding: 10px 20px; border: none; border-radius: 6px;">Secondary</button>
                </div>
                <p style="margin-top: 20px; color: var(--tl);"><?php echo htmlspecialchars($settings['platform_footer_text']); ?></p>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
            <div class="g7-3">
                <!-- Basic Settings -->
                <div class="card">
                    <div class="ct"><i class="fa-solid fa-cog"></i> Basic Settings</div>
                    
                    <div class="frm">
                        <label>Platform Name</label>
                        <input type="text" name="platform_name" value="<?php echo htmlspecialchars($settings['platform_name']); ?>" required>
                    </div>
                    
                    <div class="frm">
                        <label>Logo URL</label>
                        <input type="text" name="platform_logo" value="<?php echo htmlspecialchars($settings['platform_logo']); ?>" placeholder="/logo.png">
                        <small>Relative path from ERP root</small>
                    </div>
                    
                    <div class="frm">
                        <label>Support Email</label>
                        <input type="email" name="support_email" value="<?php echo htmlspecialchars($settings['support_email']); ?>">
                    </div>
                </div>

                <!-- Color Scheme -->
                <div class="card">
                    <div class="ct"><i class="fa-solid fa-paint-brush"></i> Color Scheme</div>
                    
                    <div class="frm">
                        <label>Primary Color</label>
                        <div class="color-input">
                            <input type="color" name="primary_color" value="<?php echo htmlspecialchars($settings['platform_primary_color']); ?>">
                            <input type="text" value="<?php echo htmlspecialchars($settings['platform_primary_color']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="frm">
                        <label>Secondary Color</label>
                        <div class="color-input">
                            <input type="color" name="secondary_color" value="<?php echo htmlspecialchars($settings['platform_secondary_color']); ?>">
                            <input type="text" value="<?php echo htmlspecialchars($settings['platform_secondary_color']); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card" style="margin-top: 20px;">
                <div class="ct"><i class="fa-solid fa-copyright"></i> Footer Settings</div>
                <div class="frm">
                    <label>Footer Text</label>
                    <input type="text" name="footer_text" value="<?php echo htmlspecialchars($settings['platform_footer_text']); ?>">
                </div>
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn bt">
                    <i class="fa-solid fa-save"></i> Save Branding Settings
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.querySelectorAll('input[type="color"]').forEach(input => {
    input.addEventListener('input', (e) => {
        e.target.nextElementSibling.value = e.target.value.toUpperCase();
    });
});
</script>

<style>
.color-input {
    display: flex;
    gap: 12px;
    align-items: center;
}
.color-input input[type="color"] {
    width: 50px;
    height: 40px;
    padding: 2px;
    border: 1px solid var(--bd);
    border-radius: 6px;
    cursor: pointer;
}
.color-input input[type="text"] {
    width: 120px;
    font-family: monospace;
}
.frm small {
    display: block;
    font-size: 11px;
    color: var(--tl);
    margin-top: 4px;
}
</style>

<?php include 'footer.php'; ?>
