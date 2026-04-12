<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Not Found — iSoftro Academic ERP</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: #f8fafc;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px;
      color: #1a2535;
    }
    .error-card {
      background: #fff;
      border-radius: 20px;
      padding: 60px 48px;
      text-align: center;
      max-width: 540px;
      width: 100%;
      box-shadow: 0 4px 40px rgba(0,0,0,0.08);
    }
    .error-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #006D44, #8CC63F);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 2rem;
      color: #fff;
    }
    .error-code {
      font-size: 5rem;
      font-weight: 800;
      color: #006D44;
      line-height: 1;
      margin-bottom: 8px;
    }
    .error-title {
      font-size: 1.4rem;
      font-weight: 600;
      color: #1a2535;
      margin-bottom: 12px;
    }
    .error-desc {
      font-size: 0.95rem;
      color: #64748b;
      line-height: 1.7;
      margin-bottom: 36px;
    }
    .error-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      border: none;
    }
    .btn--primary { background: #006D44; color: #fff; }
    .btn--primary:hover { background: #004D30; }
    .btn--outline { background: transparent; color: #006D44; border: 2px solid #006D44; }
    .btn--outline:hover { background: #006D44; color: #fff; }
    .brand {
      margin-top: 40px;
      display: flex;
      align-items: center;
      gap: 8px;
      justify-content: center;
      font-size: 0.9rem;
      color: #94a3b8;
    }
    .brand strong { color: #006D44; }
  </style>
</head>
<body>
  <div class="error-card">
    <div class="error-icon">
      <i class="fa-solid fa-map-location-dot"></i>
    </div>
    <div class="error-code">404</div>
    <h1 class="error-title">Page Not Found</h1>
    <p class="error-desc">
      The page you're looking for doesn't exist or has been moved.
      Let's get you back on track.
    </p>
    <div class="error-actions">
      <a href="/" class="btn btn--primary">
        <i class="fa-solid fa-house"></i> Go Home
      </a>
      <a href="/#contact" class="btn btn--outline">
        <i class="fa-solid fa-headset"></i> Contact Support
      </a>
    </div>
    <div class="brand">
      <span>Powered by</span>
      <strong>iSoftro Academic ERP</strong>
    </div>
  </div>
</body>
</html>
