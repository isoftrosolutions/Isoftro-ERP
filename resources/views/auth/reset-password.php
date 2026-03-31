<?php
require_once __DIR__ . '/../../../config/config.php';

$email = $_GET['email'] ?? ($_POST['email'] ?? '');
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$error = '';
$success = '';

if (empty($email)) {
    header("Location: " . APP_URL . "/auth/login");
    exit;
}

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reset_token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Validate Reset Token exists in DB
    $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$email, $reset_token]);
    $resetReq = $stmt->fetch();

    if (!$resetReq) {
        $error = "The verification session has expired or is invalid. Please start over.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $userId = $resetReq['user_id'];
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $updStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if ($updStmt->execute([$hash, $userId])) {
            $delStmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $delStmt->execute([$email]);

            $success = "Your password has been successfully reset. You can now log in with your new password.";
        } else {
            $error = "Failed to update your password. Please try again later.";
        }
    }
}

$BASE = defined('APP_URL') ? APP_URL : '/erp';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — iSoftro ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        body { 
            font-family: var(--font); 
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .rp-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .rp-logo { display: block; height: 32px; margin: 0 auto 24px; }
        .rp-title { font-size: 24px; font-weight: 700; color: var(--navy); margin-bottom: 8px; text-align: center; }
        .rp-subtitle { font-size: 13.5px; color: var(--text-body); margin-bottom: 30px; text-align: center; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; }
        .form-input { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid #e1e8ed; 
            border-radius: 10px; 
            font-size: 14px; 
            outline: none; 
            transition: 0.2s;
            background: rgba(255, 255, 255, 0.9);
        }
        .form-input:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(0, 109, 68, 0.08); }
        .otp-input { 
            letter-spacing: 8px; 
            font-size: 20px; 
            text-align: center; 
            font-weight: 700; 
            color: var(--green);
        }
        .btn-submit { 
            width: 100%; 
            padding: 14px; 
            background: var(--green); 
            color: #fff; 
            border: none; 
            border-radius: 10px; 
            font-size: 15px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover { background: var(--green-d); transform: translateY(-1px); }
        .back-link { display: block; margin-top: 24px; text-align: center; font-size: 14px; font-weight: 500; color: var(--text-light); text-decoration: none; }
    </style>
</head>
<body>
    <div class="rp-card">
        <img src="<?= $BASE ?>/assets/images/logo.png" alt="isoftro" class="rp-logo">
        <h1 class="rp-title">Create New Password</h1>
        <p class="rp-subtitle">Verify your identity and choose a secure password to protect your account.</p>

        <form method="POST" id="resetForm">
            <?= \App\Helpers\CsrfHelper::csrfField() ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group" style="position: relative;">
                <label class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-input" placeholder="Min. 8 characters" required minlength="8" style="padding-right: 45px;">
                <button type="button" class="toggle-password" data-target="password" style="position: absolute; right: 12px; top: 35px; background: none; border: none; color: var(--text-light); cursor: pointer; font-size: 16px; outline: none;">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <div class="form-group" style="position: relative;">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm" id="confirm" class="form-input" placeholder="Repeat your password" required style="padding-right: 45px;">
                <button type="button" class="toggle-password" data-target="confirm" style="position: absolute; right: 12px; top: 35px; background: none; border: none; color: var(--text-light); cursor: pointer; font-size: 16px; outline: none;">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <button type="submit" class="btn-submit">Update Password</button>
        </form>

        <a href="<?= $BASE ?>/auth/login" class="back-link">
            <i class="fa-solid fa-arrow-left" style="margin-right:6px;"></i> Back to Login
        </a>
    </div>

    <script>
        // Password visibility toggles
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?= addslashes($error) ?>',
            confirmButtonColor: '#006D44'
        });
        <?php endif; ?>

        <?php if ($success): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?= addslashes($success) ?>',
            confirmButtonColor: '#006D44'
        }).then(() => {
            window.location.href = '<?= $BASE ?>/auth/login';
        });
        document.getElementById('resetForm').style.display = 'none';
        <?php endif; ?>
    </script>
</body>
</html>
