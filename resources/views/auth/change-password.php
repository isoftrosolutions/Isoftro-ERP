<?php
/**
 * Hamro ERP — Change Password Page
 * Platform Blueprint V3.0
 */

require_once __DIR__ . '/../../../config/config.php';
requireAuth(); // Ensure user is logged in

$user = getCurrentUser();
$role = str_replace(['_', ' '], '-', strtolower($user['role']));
$pageTitle = 'Change Password';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — Hamro ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?= \App\Helpers\CsrfHelper::csrfMetaTag() ?>
    <style>
        :root {
            --green: #006D44;
            --green-d: #004D30;
            --navy: #003D2E;
            --text-dark: #1A3C34;
            --text-body: #4A6355;
            --text-light: #7A9488;
            --bg: #f8fafc;
            --white: #ffffff;
            --font: 'Poppins', sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font); background: var(--bg); padding: 40px 20px; }
        .cp-card {
            background: var(--white);
            max-width: 500px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .cp-header { text-align: center; margin-bottom: 30px; }
        .cp-icon { 
            width: 60px; height: 60px; background: rgba(0,109,68,0.1); 
            color: var(--green); border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 24px; margin: 0 auto 15px;
        }
        .cp-title { font-size: 20px; font-weight: 700; color: var(--navy); margin-bottom: 8px; }
        .cp-subtitle { font-size: 13px; color: var(--text-body); }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        .form-input { 
            width: 100%; padding: 12px 16px; border: 1px solid #e1e8ed; 
            border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s;
        }
        .form-input:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(0,109,68,0.08); }
        .btn-submit { 
            width: 100%; padding: 14px; background: var(--green); color: #fff; 
            border: none; border-radius: 10px; font-size: 15px; font-weight: 600; 
            cursor: pointer; transition: 0.2s;
        }
        .btn-submit:hover { background: var(--green-d); transform: translateY(-1px); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .back-link { display: block; text-align: center; margin-top: 25px; font-size: 13px; color: var(--text-light); text-decoration: none; }
    </style>
</head>
<body>
    <div class="cp-card">
        <div class="cp-header">
            <div class="cp-icon"><i class="fa-solid fa-lock"></i></div>
            <h1 class="cp-title">Update Password</h1>
            <p class="cp-subtitle">Protect your account by creating a strong password.</p>
        </div>

        <form id="changePasswordForm">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" id="old_password" class="form-input" placeholder="Enter your current password" required>
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" id="new_password" class="form-input" placeholder="Min. 8 characters" required minlength="8">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" id="confirm_password" class="form-input" placeholder="Repeat your new password" required>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">Save Changes</button>
        </form>

        <a href="<?= APP_URL ?>/dash/<?= array_search($user['role'], ['super-admin'=>'superadmin', 'admin'=>'instituteadmin', 'front-desk'=>'frontdesk', 'teacher'=>'teacher', 'student'=>'student', 'guardian'=>'guardian']) ?: str_replace('_', '-', strtolower($user['role'])) ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?= \App\Helpers\CsrfHelper::csrfJsHeader() ?>
    <script>
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const oldPwd = document.getElementById('old_password').value;
            const newPwd = document.getElementById('new_password').value;
            const confirmPwd = document.getElementById('confirm_password').value;

            if (newPwd !== confirmPwd) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'New passwords do not match.', confirmButtonColor: '#006D44' });
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';

            try {
                const formData = new FormData();
                formData.append('old_password', oldPwd);
                formData.append('new_password', newPwd);
                formData.append('confirm_password', confirmPwd);

                const response = await fetch('<?= APP_URL ?>/api/auth/change-password', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#006D44'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.error || 'Update failed.', confirmButtonColor: '#006D44' });
                    btn.disabled = false;
                    btn.innerText = 'Save Changes';
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Connection failed.', confirmButtonColor: '#006D44' });
                btn.disabled = false;
                btn.innerText = 'Save Changes';
            }
        });
    </script>
</body>
</html>
