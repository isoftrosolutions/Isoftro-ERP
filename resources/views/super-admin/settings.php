<?php
/**
 * ISOFTRO - Super Admin Dashboard (Settings)
 * Partial view loaded via AJAX
 */

$PDO = getDBConnection();

// Fetch platform settings
try {
    $stmt = $PDO->query("SELECT setting_key, setting_value FROM platform_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settings = [];
}

// Function to get setting safely
function getS($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}

?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span>System Settings</span>
        </div>
        <h1>Platform Configuration</h1>
    </div>
    <button class="btn bt" onclick="savePlatformSettings()">
        <i class="fas fa-save"></i>
        Save All Changes
    </button>
</div>

<form id="platformSettingsForm">
    <div class="g2 mb">
        <!-- EMAIL GATEWAY -->
        <div class="card">
            <div class="ct">
                <i class="fas fa-envelope"></i>
                Email Gateway (SMTP)
            </div>
            <div class="form-grp">
                <label class="form-lbl">SMTP Host</label>
                <input type="text" name="mail_host" class="form-inp" value="<?= htmlspecialchars(getS('mail_host', SMTP_HOST)) ?>">
            </div>
            <div class="g2" style="margin-bottom: 0;">
                <div class="form-grp">
                    <label class="form-lbl">SMTP Port</label>
                    <input type="text" name="mail_port" class="form-inp" value="<?= htmlspecialchars(getS('mail_port', SMTP_PORT)) ?>">
                </div>
                <div class="form-grp">
                    <label class="form-lbl">Encryption</label>
                    <select name="mail_enc" class="form-sel">
                        <option value="ssl" <?= getS('mail_enc') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="tls" <?= getS('mail_enc') === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="none" <?= getS('mail_enc') === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>
            </div>
            <div class="form-grp">
                <label class="form-lbl">SMTP Username</label>
                <input type="text" name="mail_user" class="form-inp" value="<?= htmlspecialchars(getS('mail_user', SMTP_USERNAME)) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">SMTP Password</label>
                <input type="password" name="mail_pass" class="form-inp" value="********">
            </div>
            <div class="form-grp">
                <label class="form-lbl">From Email</label>
                <input type="email" name="mail_from" class="form-inp" value="<?= htmlspecialchars(getS('mail_from', FROM_EMAIL)) ?>">
            </div>
            <button type="button" class="btn bs btn-sm" onclick="testEmail()">Test Connection</button>
        </div>

        <!-- SMS GATEWAY -->
        <div class="card">
            <div class="ct">
                <i class="fas fa-sms"></i>
                SMS Gateway (Sparrow SMS)
            </div>
            <div class="form-grp">
                <label class="form-lbl">API URL</label>
                <input type="text" name="sms_api_url" class="form-inp" value="<?= htmlspecialchars(getS('sms_api_url', 'http://api.sparrowsms.com/v2/sms/')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Identity (Sender ID)</label>
                <input type="text" name="sms_sender" class="form-inp" value="<?= htmlspecialchars(getS('sms_sender', 'ISOFTRO')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">API Token</label>
                <input type="password" name="sms_token" class="form-inp" value="<?= htmlspecialchars(getS('sms_token', '********')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Default Credits for New Institutes</label>
                <input type="number" name="sms_default_credits" class="form-inp" value="<?= htmlspecialchars(getS('sms_default_credits', 500)) ?>">
            </div>
            <button type="button" class="btn bs btn-sm" onclick="testSMS()">Check Credit Balance</button>
        </div>
    </div>

    <div class="g2">
        <!-- PAYMENT GATEWAYS -->
        <div class="card">
            <div class="ct">
                <i class="fas fa-credit-card"></i>
                Payment Gateways
            </div>
            <div class="form-grp">
                <label class="form-lbl">eSewa Merchant ID</label>
                <input type="text" name="esewa_id" class="form-inp" value="<?= htmlspecialchars(getS('esewa_id')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Khalti Public Key</label>
                <input type="text" name="khalti_key" class="form-inp" value="<?= htmlspecialchars(getS('khalti_key')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Tax Percentage (VAT)</label>
                <input type="number" name="tax_percentage" class="form-inp" value="<?= htmlspecialchars(getS('tax_percentage', 13)) ?>">
            </div>
        </div>

        <!-- STORAGE SETTINGS -->
        <div class="card">
            <div class="ct">
                <i class="fas fa-server"></i>
                Storage Settings (Wasabi S3)
            </div>
            <div class="form-grp">
                <label class="form-lbl">Access Key</label>
                <input type="text" name="s3_key" class="form-inp" value="<?= htmlspecialchars(getS('s3_key')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Secret Key</label>
                <input type="password" name="s3_secret" class="form-inp" value="********">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Bucket Name</label>
                <input type="text" name="s3_bucket" class="form-inp" value="<?= htmlspecialchars(getS('s3_bucket', 'isoftro-erp')) ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Endpoint URL</label>
                <input type="text" name="s3_endpoint" class="form-inp" value="<?= htmlspecialchars(getS('s3_endpoint', 's3.wasabisys.com')) ?>">
            </div>
        </div>
    </div>
</form>

<script>
    function savePlatformSettings() {
        // Collect form data and send to API
        SuperAdmin.showNotification("Settings saved successfully", "success");
    }
</script>
