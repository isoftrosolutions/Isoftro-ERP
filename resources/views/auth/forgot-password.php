<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Hamro ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php require_once __DIR__ . '/../../../config/config.php'; ?>
    <?= \App\Helpers\CsrfHelper::csrfMetaTag() ?>
    <?php $BASE = defined('APP_URL') ? APP_URL : '/erp'; ?>
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
        .fp-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }
        .fp-logo { height: 40px; margin-bottom: 24px; }
        .fp-title { font-size: 24px; font-weight: 700; color: var(--navy); margin-bottom: 12px; }
        .fp-subtitle { font-size: 14px; color: var(--text-body); margin-bottom: 32px; line-height: 1.6; }
        .form-group { text-align: left; margin-bottom: 24px; }
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
        }
        .btn-submit:hover { background: var(--green-d); transform: translateY(-1px); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
        .back-link { display: inline-block; margin-top: 32px; font-size: 14px; font-weight: 500; color: var(--green); text-decoration: none; transition: 0.2s; }
        .back-link:hover { color: var(--green-d); }
    </style>
</head>
<body>
    <div class="fp-card">
        <img src="<?= $BASE ?>/public/assets/images/logo.png" alt="Hamro Labs" class="fp-logo">
        <h1 class="fp-title">Forgot Password?</h1>
        <p class="fp-subtitle" id="fpSubtitle">Enter your email address and we'll send you an OTP code to reset your password.</p>
        
        <!-- Step 1: Email Form -->
        <form id="forgotForm">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" id="email" class="form-input" placeholder="e.g. name@example.com" required>
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">
                <span id="btnText">Send Reset Code</span>
            </button>
        </form>

        <!-- Step 2: OTP Form (Hidden by default) -->
        <form id="otpForm" style="display:none;">
            <div class="form-group">
                <label class="form-label">Enter 6-Digit Code</label>
                <input type="text" id="otp" class="form-input" style="letter-spacing:8px; text-align:center; font-size:20px; font-weight:700;" placeholder="000000" maxlength="6" pattern="\d{6}" required autocomplete="off">
            </div>
            <button type="submit" class="btn-submit" id="verifyBtn">
                <span id="verifyText">Verify Code</span>
            </button>
            <p style="margin-top:20px; font-size:13px; color:var(--text-light);">
                Didn't receive the code? <a href="#" onclick="location.reload();" style="color:var(--green); font-weight:600; text-decoration:none;">Try again</a>
            </p>
        </form>

        <a href="<?= $BASE ?>/auth/login" class="back-link">
            <i class="fa-solid fa-arrow-left" style="margin-right:8px;"></i> Back to Login
        </a>
    </div>
    <?= \App\Helpers\CsrfHelper::csrfJsHeader() ?>
    <script>
        const forgotForm = document.getElementById('forgotForm');
        const otpForm = document.getElementById('otpForm');
        const fpTitle = document.querySelector('.fp-title');
        const fpSubtitle = document.getElementById('fpSubtitle');
        let userEmail = '';

        // Handle Step 1: Request OTP
        forgotForm.addEventListener('submit', function(e) {
            e.preventDefault();
            userEmail = document.getElementById('email').value;
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');

            btn.disabled = true;
            btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

            const formData = new FormData();
            formData.append('email', userEmail);

            fetch('<?= $BASE ?>/auth/send_password_reset', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'OTP Sent!',
                        text: data.message,
                        confirmButtonColor: '#006D44'
                    });
                    
                    // Switch to Step 2
                    forgotForm.style.display = 'none';
                    otpForm.style.display = 'block';
                    fpTitle.innerText = 'Verify Your Identity';
                    fpSubtitle.innerHTML = 'We\'ve sent a verification code to <strong>' + userEmail + '</strong>. Please enter it below.';
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.message, confirmButtonColor: '#006D44' });
                    btn.disabled = false;
                    btnText.innerText = 'Send Reset Code';
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred.', confirmButtonColor: '#006D44' });
                btn.disabled = false;
                btnText.innerText = 'Send Reset Code';
            });
        });

        // Handle Step 2: Verify OTP
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const otpCode = document.getElementById('otp').value;
            const btn = document.getElementById('verifyBtn');
            const btnText = document.getElementById('verifyText');

            btn.disabled = true;
            btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';

            const formData = new FormData();
            formData.append('email', userEmail);
            formData.append('otp', otpCode);

            fetch('<?= $BASE ?>/auth/verify-otp', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Success! Redirect to final Step 3 with the Reset Token
                    window.location.href = '<?= $BASE ?>/auth/reset-password?email=' + encodeURIComponent(userEmail) + '&token=' + data.reset_token;
                } else {
                    Swal.fire({ icon: 'error', title: 'Invalid Code', text: data.message, confirmButtonColor: '#006D44' });
                    btn.disabled = false;
                    btnText.innerText = 'Verify Code';
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Verification failed. Please try again.', confirmButtonColor: '#006D44' });
                btn.disabled = false;
                btnText.innerText = 'Verify Code';
            });
        });
    </script>
</body>
</html>
