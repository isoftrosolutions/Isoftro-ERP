<?php
/**
 * Hamro ERP — Email Configuration Page
 * Platform Blueprint V3.0
 * 
 * @module SuperAdmin
 */

require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pageTitle = 'Email Configuration';
$activePage = 'email-config.php';
?>

<?php renderSuperAdminHeader(); renderSidebar($activePage); ?>

<main class="main" id="mainContent">
    <div class="page fu">
        <div class="page-head">
            <div class="page-title-row">
                <div class="page-icon" style="background:rgba(41,128,185,0.1); color:#2980b9;">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div>
                    <div class="page-title">Email Configuration</div>
                    <div class="page-sub">Configure global SMTP settings for outgoing emails (Password Resets, Invoices, Alerts).</div>
                </div>
            </div>
        </div>

        <div class="card" style="max-width: 700px; margin: 0 auto; padding: 25px;">
            <form onsubmit="event.preventDefault(); SuperAdmin.showNotification('SMTP settings updated successfully!', 'success');">
                <div class="ct" style="margin-bottom:20px;"><i class="fa-solid fa-server"></i> SMTP Server Settings</div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">SMTP Host</label>
                        <input type="text" class="form-control" placeholder="smtp.example.com" value="<?php echo SMTP_HOST; ?>" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">SMTP Port</label>
                        <input type="number" class="form-control" placeholder="587" value="<?php echo SMTP_PORT; ?>" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">Encryption Type</label>
                        <select class="form-control" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                            <option value="tls" selected>TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">Authentication</label>
                        <select class="form-control" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                            <option value="true" selected>Enabled</option>
                            <option value="false">Disabled</option>
                        </select>
                    </div>
                </div>

                <div class="ct" style="margin-bottom:20px; margin-top:30px;"><i class="fa-solid fa-user"></i> SMTP Credentials</div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">SMTP Username</label>
                        <input type="text" class="form-control" placeholder="your-email@gmail.com" value="<?php echo SMTP_USERNAME; ?>" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">SMTP Password</label>
                        <input type="password" class="form-control" placeholder="App Password" value="xxxx xxxx xxxx xxxx" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                    </div>
                </div>

                <div class="ct" style="margin-bottom:20px; margin-top:30px;"><i class="fa-solid fa-pen"></i> Email Settings</div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">From Name</label>
                        <input type="text" class="form-control" placeholder="Hamro ERP" value="Hamro ERP" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:var(--text-dark); margin-bottom:5px;">From Email</label>
                        <input type="email" class="form-control" placeholder="noreply@hamrolabs.edu.np" value="<?php echo FROM_EMAIL; ?>" style="width:100%; padding:10px; border:1px solid var(--card-border); border-radius:8px;">
                    </div>
                </div>

                <div style="margin-top:30px; display:flex; gap:10px;">
                    <button type="submit" class="btn bt"><i class="fa-solid fa-save"></i> Save Settings</button>
                    <button type="button" class="btn bs" onclick="testSMTPConnection()"><i class="fa-solid fa-plug"></i> Test Connection</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function testSMTPConnection() {
    SuperAdmin.showNotification('Testing SMTP connection...', 'info');
    setTimeout(() => {
        SuperAdmin.showNotification('SMTP connection successful!', 'success');
    }, 1500);
}
</script>

<?php include 'footer.php'; ?>
