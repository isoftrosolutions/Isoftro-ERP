<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request a Free Demo — iSoftro Academic ERP Nepal</title>
  <meta name="description" content="Request a free demo of iSoftro Academic ERP — Nepal's leading school, college, and coaching center management software. Our team will contact you within 24 hours.">
  <link rel="canonical" href="https://isoftroerp.com/request-demo">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/landing.css') }}">
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">

  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; color: #1a2535; margin: 0; background: #f8fafc; }

    /* Nav */
    .top-nav { background: #fff; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100; }
    .top-nav .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; color: #006D44; text-decoration: none; font-size: 1.1rem; }
    .top-nav .brand img { height: 32px; }
    .breadcrumb { background: #fff; padding: 10px 24px; font-size: 0.82rem; color: #94a3b8; border-bottom: 1px solid #f1f5f9; }
    .breadcrumb a { color: #006D44; text-decoration: none; }
    .breadcrumb span { margin: 0 6px; }

    /* Layout */
    .page-wrap { max-width: 1100px; margin: 0 auto; padding: 48px 24px 80px; display: grid; grid-template-columns: 1fr 420px; gap: 48px; align-items: start; }
    @media (max-width: 860px) { .page-wrap { grid-template-columns: 1fr; } }

    /* Left column */
    .left-col {}
    .left-col .tag { display: inline-block; background: #E8F5E0; color: #006D44; border-radius: 20px; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; margin-bottom: 14px; }
    .left-col h1 { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; color: #1a2535; line-height: 1.25; margin: 0 0 14px; }
    .left-col p { font-size: 0.97rem; color: #64748b; line-height: 1.75; margin: 0 0 32px; }
    .trust-points { list-style: none; padding: 0; margin: 0 0 36px; }
    .trust-points li { display: flex; align-items: flex-start; gap: 12px; font-size: 0.9rem; color: #374151; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
    .trust-points li:last-child { border-bottom: none; }
    .trust-points .icon { width: 32px; height: 32px; background: linear-gradient(135deg, #006D44, #8CC63F); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.85rem; flex-shrink: 0; margin-top: 1px; }
    .trust-points strong { display: block; font-weight: 600; color: #1a2535; font-size: 0.92rem; }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 8px; }
    .stat-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; text-align: center; }
    .stat-box .num { font-size: 1.6rem; font-weight: 800; color: #006D44; }
    .stat-box .lbl { font-size: 0.78rem; color: #64748b; margin-top: 2px; }

    /* Form card */
    .form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 36px 32px; box-shadow: 0 4px 30px rgba(0,0,0,0.06); }
    .form-card h2 { font-size: 1.25rem; font-weight: 700; color: #1a2535; margin: 0 0 6px; }
    .form-card .sub { font-size: 0.85rem; color: #64748b; margin: 0 0 28px; }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .form-group label span { color: #ef4444; margin-left: 2px; }
    .form-group input,
    .form-group select,
    .form-group textarea { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; color: #1a2535; background: #fff; transition: border-color 0.2s; outline: none; }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: #006D44; box-shadow: 0 0 0 3px rgba(0,109,68,0.08); }
    .form-group textarea { resize: vertical; min-height: 90px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
    .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #006D44, #008A56); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; font-family: 'Poppins', sans-serif; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; margin-top: 8px; }
    .btn-submit:hover { background: linear-gradient(135deg, #004D30, #006D44); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,109,68,0.3); }
    .form-note { font-size: 0.78rem; color: #94a3b8; text-align: center; margin-top: 12px; }

    /* Success state */
    .success-banner { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1.5px solid #86efac; border-radius: 14px; padding: 24px 20px; text-align: center; margin-bottom: 28px; }
    .success-banner .check { width: 52px; height: 52px; background: #006D44; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.3rem; margin: 0 auto 14px; }
    .success-banner h3 { font-size: 1.05rem; font-weight: 700; color: #15803d; margin: 0 0 6px; }
    .success-banner p { font-size: 0.87rem; color: #166534; margin: 0; line-height: 1.6; }

    /* Error */
    .error-msg { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; font-size: 0.85rem; color: #dc2626; margin-bottom: 18px; }
    .field-error { font-size: 0.78rem; color: #dc2626; margin-top: 4px; }

    /* Contact strip */
    .contact-strip { background: linear-gradient(135deg, #004D30, #006D44); border-radius: 14px; padding: 24px; margin-top: 24px; color: #fff; }
    .contact-strip h3 { font-size: 0.95rem; font-weight: 700; margin: 0 0 14px; opacity: 0.9; }
    .contact-strip a { display: flex; align-items: center; gap: 10px; color: #fff; text-decoration: none; font-size: 0.88rem; margin-bottom: 10px; opacity: 0.9; }
    .contact-strip a:last-child { margin-bottom: 0; }
    .contact-strip a:hover { opacity: 1; }
    .contact-strip .c-icon { width: 30px; height: 30px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.82rem; flex-shrink: 0; }
  </style>
</head>
<body>

<!-- Nav -->
<nav class="top-nav">
  <a href="/" class="brand">
    <img src="{{ asset('assets/images/logo.png') }}" alt="iSoftro Academic ERP Nepal">
    <span>isoftro</span>
  </a>
  <a href="/login" style="padding:8px 18px;font-size:0.85rem;color:#006D44;text-decoration:none;font-family:'Poppins',sans-serif;font-weight:600;">Log In</a>
</nav>

<!-- Breadcrumb -->
<div class="breadcrumb">
  <a href="/">Home</a><span>/</span>
  <span>Request a Free Demo</span>
</div>

<div class="page-wrap">

  <!-- Left: Trust signals -->
  <div class="left-col">
    <span class="tag"><i class="fa-solid fa-star" style="margin-right:5px;"></i>Free Demo — No Commitment</span>
    <h1>See iSoftro in Action for Your Institution</h1>
    <p>Fill in the form and our team will reach out within <strong>24 hours</strong> to schedule a personalized demo — tailored to your institution type and requirements.</p>

    <ul class="trust-points">
      <li>
        <div class="icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div><strong>Personalized Demo</strong> We walk you through the exact features relevant to your school, college, or coaching center.</div>
      </li>
      <li>
        <div class="icon"><i class="fa-solid fa-rocket"></i></div>
        <div><strong>Go Live in 3–5 Days</strong> Once you decide, our team sets everything up and trains your staff — fast.</div>
      </li>
      <li>
        <div class="icon"><i class="fa-solid fa-calendar"></i></div>
        <div><strong>Nepali Calendar (BS) Built-in</strong> Full Bikram Sambat support — no extra configuration needed.</div>
      </li>
      <li>
        <div class="icon"><i class="fa-solid fa-headset"></i></div>
        <div><strong>Dedicated Nepali Support Team</strong> Onboarding, training, and ongoing support in Nepali and English.</div>
      </li>
      <li>
        <div class="icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div><strong>Trusted by 150+ Institutions</strong> Schools, colleges, coaching centers, and Loksewa prep centers across Nepal.</div>
      </li>
    </ul>

    <div class="stats-row">
      <div class="stat-box"><div class="num">150+</div><div class="lbl">Institutions</div></div>
      <div class="stat-box"><div class="num">25K+</div><div class="lbl">Students</div></div>
      <div class="stat-box"><div class="num">4.8★</div><div class="lbl">Rating</div></div>
    </div>
  </div>

  <!-- Right: Form -->
  <div class="right-col">
    <div class="form-card">

      @if(request('success') == 1)
        <div class="success-banner">
          <div class="check"><i class="fa-solid fa-check"></i></div>
          <h3>Demo Request Received!</h3>
          <p>Thank you! Our team will contact you within <strong>24 hours</strong> to schedule your free demo.</p>
        </div>
        <div style="text-align:center;">
          <a href="/" style="display:inline-flex;align-items:center;gap:8px;color:#006D44;font-size:0.9rem;font-weight:600;text-decoration:none;">
            <i class="fa-solid fa-arrow-left"></i> Back to Homepage
          </a>
        </div>
      @else

        <h2>Request a Free Demo</h2>
        <p class="sub">Get a personalized walkthrough — tailored to your institution.</p>

        @if($errors->any())
          <div class="error-msg">
            <i class="fa-solid fa-circle-exclamation" style="margin-right:6px;"></i>
            Please fix the errors below and try again.
          </div>
        @endif

        <form method="POST" action="/request-demo">
          @csrf

          <div class="form-row">
            <div class="form-group">
              <label>Your Name <span>*</span></label>
              <input type="text" name="name" value="{{ old('name') }}" placeholder="Ramesh Sharma" required>
              @error('name')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
              <label>Phone Number <span>*</span></label>
              <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+977 98XXXXXXXX" required>
              @error('phone')<div class="field-error">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="form-group">
            <label>Email Address <span>*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@institution.edu.np" required>
            @error('email')<div class="field-error">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label>Institution Name <span>*</span></label>
            <input type="text" name="institution_name" value="{{ old('institution_name') }}" placeholder="e.g. ABC School, XYZ College" required>
            @error('institution_name')<div class="field-error">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label>Institution Type <span>*</span></label>
            <select name="institution_type" required>
              <option value="" disabled {{ old('institution_type') ? '' : 'selected' }}>Select your institution type</option>
              <option value="School (Primary/Secondary)" {{ old('institution_type') == 'School (Primary/Secondary)' ? 'selected' : '' }}>School (Primary / Secondary)</option>
              <option value="Higher Secondary (+2)" {{ old('institution_type') == 'Higher Secondary (+2)' ? 'selected' : '' }}>Higher Secondary (+2)</option>
              <option value="College / University" {{ old('institution_type') == 'College / University' ? 'selected' : '' }}>College / University</option>
              <option value="Coaching Center" {{ old('institution_type') == 'Coaching Center' ? 'selected' : '' }}>Coaching Center</option>
              <option value="Loksewa / PSC Coaching" {{ old('institution_type') == 'Loksewa / PSC Coaching' ? 'selected' : '' }}>Loksewa / PSC Coaching</option>
              <option value="Other" {{ old('institution_type') == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('institution_type')<div class="field-error">{{ $message }}</div>@enderror
          </div>

          <div class="form-group">
            <label>Message / Requirements <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
            <textarea name="message" placeholder="Tell us about your institution size, key modules you need, or any questions...">{{ old('message') }}</textarea>
            @error('message')<div class="field-error">{{ $message }}</div>@enderror
          </div>

          <button type="submit" class="btn-submit">
            <i class="fa-solid fa-calendar-check"></i> Request My Free Demo
          </button>
          <p class="form-note"><i class="fa-solid fa-lock" style="margin-right:4px;"></i>Your information is secure. We never share your data.</p>
        </form>

      @endif
    </div>

    <!-- Contact strip -->
    <div class="contact-strip">
      <h3><i class="fa-solid fa-headset" style="margin-right:8px;"></i>Prefer to call us directly?</h3>
      <a href="tel:+9779811144402">
        <div class="c-icon"><i class="fa-solid fa-phone"></i></div>
        +977 9811144402
      </a>
      <a href="mailto:info@isoftro.com">
        <div class="c-icon"><i class="fa-solid fa-envelope"></i></div>
        info@isoftro.com
      </a>
      <a href="#">
        <div class="c-icon"><i class="fa-solid fa-location-dot"></i></div>
        Kathmandu, Nepal
      </a>
    </div>
  </div>

</div>

<!-- Footer -->
<footer style="background:#1a2535;color:rgba(255,255,255,0.6);text-align:center;padding:24px;font-size:0.85rem;font-family:'Poppins',sans-serif;">
  &copy; {{ date('Y') }} iSoftro Pvt. Ltd. &nbsp;|&nbsp;
  <a href="/privacy" style="color:rgba(255,255,255,0.6);">Privacy Policy</a> &nbsp;|&nbsp;
  <a href="/terms" style="color:rgba(255,255,255,0.6);">Terms</a> &nbsp;|&nbsp;
  <a href="/" style="color:#8CC63F;">isoftroerp.com</a>
</footer>

</body>
</html>
