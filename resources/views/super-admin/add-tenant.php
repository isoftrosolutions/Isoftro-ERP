<?php
/**
 * Hamro ERP â€” Add New Tenant (Single-Page Â· Bootstrap 5 Â· Live Preview)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pageTitle = 'Add New Institute';
$activePage = 'tenant-management.php';
?>

<?php renderSuperAdminHeader();
renderSidebar($activePage); ?>

<main class="main" id="mainContent">
<!-- sa-addtenant.js inside <main> so processPartialHtml re-executes it on SPA navigation -->
<script src="<?= APP_URL ?>/public/assets/js/sa-addtenant.js?v=1.0.1"></script>


<div class="pg fu" style="max-width:1500px;">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-1">
        <ol class="breadcrumb mb-0" style="font-size:12px;">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--tl);">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="tenant-management.php" class="text-decoration-none" style="color:var(--tl);">Tenant Management</a></li>
            <li class="breadcrumb-item active" style="color:var(--sa-primary); font-weight:600;">Add New Institute</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 my-3">
        <div class="d-flex align-items-center gap-3">
            <div class="pg-ico ic-t"><i class="fa-solid fa-building-circle-plus"></i></div>
            <div>
                <div class="pg-title">Register New Institute</div>
                <div class="pg-sub">All fields update the live preview in real-time â†’</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="tenant-management.php" class="btn bs"><i class="fa-solid fa-xmark me-1"></i>Discard</a>
            <button class="btn bt px-4" id="btnFinalSubmit" onclick="saveInstitute()">
                <i class="fa-solid fa-check-double me-1"></i>Register Institute
            </button>
        </div>
    </div>

    <!-- â•â•â• MAIN LAYOUT: Form (left) + Preview (right) â•â•â• -->
    <div class="row g-4 align-items-start">

        <!-- â”€â”€â”€ LEFT COL: Form â”€â”€â”€ -->
        <div class="col-12 col-xl-7">
            <div class="d-flex flex-column gap-4">

                <!-- â•â• SECTION 1: Institute Identity â•â• -->
                <div class="at-card">
                    <div class="at-card-head">
                        <div class="at-num">01</div>
                        <div>
                            <div class="at-title">Institute Identity</div>
                            <div class="at-sub">Name, logo, brand color and contact details</div>
                        </div>
                    </div>
                    <div class="at-card-body">

                        <!-- Name Row -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-lbl">English Name <span class="req">*</span></label>
                                <input class="form-inp" type="text" id="instName"
                                    placeholder="e.g. Everest Loksewa Classes"
                                    oninput="syncPreview()">
                                <span class="err-msg" id="err-instName"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-lbl">Nepali Name</label>
                                <input class="form-inp" type="text" id="instNameNp"
                                    placeholder="à¤‰à¤¦à¤¾: à¤à¤­à¤°à¥‡à¤·à¥à¤Ÿ à¤²à¥‹à¤•à¤¸à¥‡à¤µà¤¾ à¤•à¥à¤²à¤¾à¤¸"
                                    oninput="syncPreview()">
                            </div>
                        </div>

                        <!-- Tagline -->
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-lbl">Tagline / Motto</label>
                                <input class="form-inp" type="text" id="instTagline"
                                    placeholder="e.g. Shaping Future Leaders"
                                    oninput="syncPreview()">
                            </div>
                        </div>

                        <!-- Logo + Color Row -->
                        <div class="row g-3 mt-1">
                            <!-- Logo Upload -->
                            <div class="col-md-7">
                                <label class="form-lbl">Institute Logo</label>
                                <div class="logo-drop-zone" id="logoDropZone"
                                     onclick="document.getElementById('instLogo').click()">
                                    <div id="logoEmptyState">
                                        <i class="fa-solid fa-cloud-arrow-up logo-drop-icon"></i>
                                        <div class="logo-drop-label">Click or drag & drop to upload</div>
                                        <div class="logo-drop-hint">PNG Â· JPG Â· SVG &nbsp;Â·&nbsp; Max 2MB &nbsp;Â·&nbsp; Recommended 256Ã—256px</div>
                                    </div>
                                    <img id="logoFilePreview" src="" alt="" style="display:none; max-height:90px; max-width:160px; border-radius:10px; object-fit:contain;">
                                </div>
                                <input type="file" id="instLogo" accept="image/*" style="display:none"
                                       onchange="handleLogoUpload(this)">
                                <button type="button" class="at-btn-link danger mt-1"
                                        id="clearLogoBtn" onclick="clearLogo()" style="display:none;">
                                    <i class="fa-solid fa-trash-can"></i> Remove logo
                                </button>
                            </div>

                            <!-- Brand Color -->
                            <div class="col-md-5">
                                <label class="form-lbl">Brand Color</label>
                                <div class="color-block">
                                    <div class="color-preview-big" id="colorPreviewBig" style="background:#009E7E;">
                                        <input type="color" id="themeColor" value="#009E7E"
                                               oninput="syncPreview()" title="Pick custom color">
                                    </div>
                                    <div class="color-info">
                                        <div class="color-hex" id="colorHexLabel">#009E7E</div>
                                        <div class="color-swatches mt-2">
                                            <span class="sw" style="background:#009E7E;" onclick="setColor('#009E7E')" title="Teal"></span>
                                            <span class="sw" style="background:#3B82F6;" onclick="setColor('#3B82F6')" title="Blue"></span>
                                            <span class="sw" style="background:#8141A5;" onclick="setColor('#8141A5')" title="Purple"></span>
                                            <span class="sw" style="background:#E11D48;" onclick="setColor('#E11D48')" title="Red"></span>
                                            <span class="sw" style="background:#F59E0B;" onclick="setColor('#F59E0B')" title="Amber"></span>
                                            <span class="sw" style="background:#0F172A;" onclick="setColor('#0F172A')" title="Navy"></span>
                                            <span class="sw" style="background:#06B6D4;" onclick="setColor('#06B6D4')" title="Cyan"></span>
                                            <span class="sw" style="background:#10B981;" onclick="setColor('#10B981')" title="Emerald"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-lbl">Full Office Address <span class="req">*</span></label>
                                <input class="form-inp" type="text" id="instAddress"
                                    placeholder="e.g. New Baneshwor, Kathmandu, Nepal"
                                    oninput="syncPreview()">
                                <span class="err-msg" id="err-instAddress"></span>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-lbl">Contact Number <span class="req">*</span></label>
                                <input class="form-inp" type="tel" id="instPhone"
                                    placeholder="+977-01-XXXXXXX"
                                    oninput="syncPreview()">
                                <span class="err-msg" id="err-instPhone"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-lbl">Official Email <span class="req">*</span></label>
                                <input class="form-inp" type="email" id="instEmail"
                                    placeholder="info@institute.com"
                                    oninput="syncPreview()">
                                <span class="err-msg" id="err-instEmail"></span>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-lbl">Website <span class="tag-opt">optional</span></label>
                                <input class="form-inp" type="url" id="instWebsite"
                                    placeholder="https://institute.com.np"
                                    oninput="syncPreview()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-lbl">PAN Number <span class="tag-opt">optional</span></label>
                                <input class="form-inp" type="text" id="panNumber"
                                    placeholder="9-digit PAN">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- â•â• SECTION 2: Platform Setup â•â• -->
                <div class="at-card">
                    <div class="at-card-head">
                        <div class="at-num">02</div>
                        <div>
                            <div class="at-title">Platform Setup</div>
                            <div class="at-sub">Subdomain, subscription plan and tenant configuration</div>
                        </div>
                    </div>
                    <div class="at-card-body">

                        <!-- Subdomain -->
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-lbl">Subdomain <span class="req">*</span></label>
                                <div class="subdomain-wrap">
                                    <input class="form-inp subdomain-inp" type="text" id="subdomainInp"
                                        placeholder="e.g. everest" oninput="handleSubdomainInput()">
                                    <span class="subdomain-sfx">.hamrolabs.com.np</span>
                                </div>
                                <div class="subdomain-pill" id="subPill">
                                    <i class="fa-solid fa-link"></i>
                                    <span id="subPillText">everest.hamrolabs.com.np</span>
                                </div>
                                <span class="err-msg" id="err-subdomain"></span>
                            </div>
                        </div>

                        <!-- Plan & Status -->
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-lbl">Subscription Plan <span class="req">*</span></label>
                                <select class="form-inp" id="billingPlan" onchange="syncPreview()" style="appearance:auto;">
                                    <option value="starter">ðŸš€ Starter â€” up to 150 students</option>
                                    <option value="growth">ðŸ“ˆ Growth â€” up to 500 students</option>
                                    <option value="professional">ðŸ’¼ Professional â€” up to 1,500 students</option>
                                    <option value="enterprise">ðŸ¢ Enterprise â€” Unlimited</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-lbl">Initial Status <span class="req">*</span></label>
                                <select class="form-inp" id="tenantStatus" onchange="syncPreview()" style="appearance:auto;">
                                    <option value="trial">ðŸ• Trial Period</option>
                                    <option value="active">âœ… Active (Paid)</option>
                                    <option value="suspended">â¸ Suspended</option>
                                </select>
                            </div>
                        </div>

                        <!-- Limits -->
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-lbl">Student Limit</label>
                                <input class="form-inp" type="number" id="studentLimit"
                                    placeholder="e.g. 150" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-lbl">SMS Credits</label>
                                <input class="form-inp" type="number" id="smsCredits"
                                    placeholder="e.g. 100" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-lbl">Trial Ends At</label>
                                <input class="form-inp" type="date" id="trialEndsAt">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- â•â• SECTION 3: Admin Account â•â• -->
                <div class="at-card">
                    <div class="at-card-head">
                        <div class="at-num">03</div>
                        <div>
                            <div class="at-title">Primary Admin Account</div>
                            <div class="at-sub">Login credentials for the institute administrator</div>
                        </div>
                    </div>
                    <div class="at-card-body">

                        <!-- Admin Name & Phone -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-lbl">Admin Full Name <span class="req">*</span></label>
                                <input class="form-inp" type="text" id="adminName"
                                    placeholder="e.g. Anil Shrestha"
                                    oninput="syncPreview()">
                                <span class="err-msg" id="err-adminName"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-lbl">Admin Phone <span class="req">*</span></label>
                                <input class="form-inp" type="tel" id="adminPhone"
                                    placeholder="98XXXXXXXX">
                                <span class="err-msg" id="err-adminPhone"></span>
                            </div>
                        </div>

                        <!-- Admin Email -->
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-lbl">Admin Login Email <span class="req">*</span></label>
                                <input class="form-inp" type="email" id="adminEmail"
                                    placeholder="admin@institute.com"
                                    oninput="syncPreview()">
                                <span class="err-msg" id="err-adminEmail"></span>
                            </div>
                        </div>

                        <!-- Passwords -->
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-lbl">Password <span class="req">*</span></label>
                                <div class="pass-wrap">
                                    <input class="form-inp" type="password" id="adminPass"
                                        placeholder="Min. 8 characters"
                                        oninput="checkPassStrength()">
                                    <button type="button" class="pass-eye" onclick="togglePass('adminPass','pico1')">
                                        <i class="fa-solid fa-eye" id="pico1"></i>
                                    </button>
                                </div>
                                <div class="pass-bar mt-2"><div id="strengthBar"></div></div>
                                <div id="strengthLabel" class="strength-label"></div>
                                <span class="err-msg" id="err-adminPass"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-lbl">Confirm Password <span class="req">*</span></label>
                                <div class="pass-wrap">
                                    <input class="form-inp" type="password" id="adminPassConfirm"
                                        placeholder="Re-enter password"
                                        oninput="checkConfirmPass()">
                                    <button type="button" class="pass-eye" onclick="togglePass('adminPassConfirm','pico2')">
                                        <i class="fa-solid fa-eye" id="pico2"></i>
                                    </button>
                                </div>
                                <span class="err-msg" id="err-adminPassConfirm"></span>
                            </div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="pass-req-grid mt-2">
                            <div class="preq" id="req-length"><i class="fa-solid fa-circle-dot"></i> At least 8 characters</div>
                            <div class="preq" id="req-upper"><i class="fa-solid fa-circle-dot"></i> One uppercase letter</div>
                            <div class="preq" id="req-number"><i class="fa-solid fa-circle-dot"></i> One number</div>
                            <div class="preq" id="req-special"><i class="fa-solid fa-circle-dot"></i> One special character</div>
                        </div>

                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="d-flex justify-content-end gap-2 pb-4">
                    <a href="tenant-management.php" class="btn bs">Cancel</a>
                    <button class="btn bt px-5" onclick="saveInstitute()">
                        <i class="fa-solid fa-check-double me-1"></i> Finalize & Register
                    </button>
                </div>

            </div><!-- /flex column -->
        </div><!-- /col-xl-7 -->

        <!-- â”€â”€â”€ RIGHT COL: Sticky Live Preview â”€â”€â”€ -->
        <div class="col-12 col-xl-5">
            <div class="preview-sticky">

                <!-- Preview Label -->
                <div class="preview-top-label">
                    <i class="fa-solid fa-eye"></i> Live Preview
                    <span class="preview-pulse"></span>
                </div>

                <!-- â”€â”€ INSTITUTE CARD MOCKUP â”€â”€ -->
                <div class="mock-card" id="mockCard">

                    <!-- Card Hero / Header -->
                    <div class="mock-hero" id="mockHero" style="background:#009E7E;">

                        <!-- Logo bubble -->
                        <div class="mock-logo-bubble" id="mockLogoBubble">
                            <!-- Placeholder icon -->
                            <div class="mock-logo-icon" id="mockLogoIcon">
                                <i class="fa-solid fa-building"></i>
                            </div>
                            <!-- Uploaded image preview -->
                            <img id="mockLogoImg" src="" alt="Logo"
                                 style="display:none; width:100%; height:100%; object-fit:contain; border-radius:14px; padding:4px; background:#fff;">
                        </div>

                        <!-- Name block -->
                        <div class="mock-name-block">
                            <div class="mock-name" id="mockName">Institute Name</div>
                            <div class="mock-name-np" id="mockNameNp">à¤¸à¤‚à¤¸à¥à¤¥à¤¾à¤•à¥‹ à¤¨à¤¾à¤®</div>
                            <div class="mock-tagline" id="mockTagline">Tagline goes here</div>
                        </div>

                        <!-- Color dot decorator -->
                        <div class="mock-hero-deco">
                            <div class="mock-deco-circle c1"></div>
                            <div class="mock-deco-circle c2"></div>
                        </div>
                    </div>

                    <!-- Card Body: Info rows -->
                    <div class="mock-body">
                        <div class="mock-info-row">
                            <span class="mock-info-icon"><i class="fa-solid fa-link"></i></span>
                            <span id="mockSubdomain" class="mock-info-val">subdomain.hamrolabs.com.np</span>
                        </div>
                        <div class="mock-info-row">
                            <span class="mock-info-icon"><i class="fa-solid fa-envelope"></i></span>
                            <span id="mockEmail" class="mock-info-val">info@institute.com</span>
                        </div>
                        <div class="mock-info-row">
                            <span class="mock-info-icon"><i class="fa-solid fa-phone"></i></span>
                            <span id="mockPhone" class="mock-info-val">+977-XXXXXXXXX</span>
                        </div>
                        <div class="mock-info-row">
                            <span class="mock-info-icon"><i class="fa-solid fa-location-dot"></i></span>
                            <span id="mockAddress" class="mock-info-val">Institute address</span>
                        </div>
                        <div class="mock-info-row" id="mockWebsiteRow" style="display:none;">
                            <span class="mock-info-icon"><i class="fa-solid fa-globe"></i></span>
                            <span id="mockWebsite" class="mock-info-val"></span>
                        </div>
                    </div>

                    <!-- Card Footer: badges + admin -->
                    <div class="mock-footer">
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <span class="mock-badge plan" id="mockPlan">Starter</span>
                            <span class="mock-badge status" id="mockStatus">Trial</span>
                        </div>
                        <div class="mock-admin-block">
                            <div class="mock-admin-label">ADMIN ACCOUNT</div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <div class="mock-admin-avatar" id="mockAdminAvatar">A</div>
                                <div>
                                    <div class="mock-admin-name" id="mockAdminName">Admin Name</div>
                                    <div class="mock-admin-email" id="mockAdminEmail">admin@institute.com</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /mock-card -->

                <!-- â”€â”€ CHECKLIST â”€â”€ -->
                <div class="chk-card mt-3">
                    <div class="chk-card-title"><i class="fa-solid fa-list-check me-1"></i> Registration Checklist</div>
                    <div class="chk-list">
                        <div class="chk-row" id="chk-name"><i class="fa-regular fa-circle-dot"></i><span>Institute name filled</span></div>
                        <div class="chk-row" id="chk-subdomain"><i class="fa-regular fa-circle-dot"></i><span>Subdomain configured</span></div>
                        <div class="chk-row" id="chk-contact"><i class="fa-regular fa-circle-dot"></i><span>Phone &amp; email provided</span></div>
                        <div class="chk-row" id="chk-address"><i class="fa-regular fa-circle-dot"></i><span>Address entered</span></div>
                        <div class="chk-row" id="chk-logo"><i class="fa-regular fa-circle-dot"></i><span>Logo uploaded</span></div>
                        <div class="chk-row" id="chk-admin"><i class="fa-regular fa-circle-dot"></i><span>Admin account details</span></div>
                        <div class="chk-row" id="chk-pass"><i class="fa-regular fa-circle-dot"></i><span>Strong password set</span></div>
                    </div>
                </div>

            </div><!-- /preview-sticky -->
        </div><!-- /col-xl-5 -->

    </div><!-- /row -->
</div><!-- /pg -->
</main>

<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   ADD TENANT â€” REDESIGN
   Bootstrap 5 grid Â· Live preview card
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

/* Shared */
.req  { color:var(--red); }
.tag-opt { font-size:10px; font-weight:600; color:var(--tl); background:var(--bg);
           padding:1px 6px; border-radius:20px; border:1px solid var(--cb); margin-left:4px; }
.err-msg { display:block; font-size:11px; font-weight:600; color:var(--red); min-height:14px; margin-top:3px; }
.form-inp.is-err  { border-color:var(--red)!important; background:#fff5f5!important; }
.form-inp.is-ok   { border-color:var(--sa-primary)!important; }
.at-btn-link { background:none; border:none; font-family:var(--font); font-size:12px; font-weight:600;
               cursor:pointer; padding:0; display:flex; align-items:center; gap:5px; }
.at-btn-link.danger { color:var(--red); }
.at-btn-link.danger:hover { text-decoration:underline; }

/* â”€â”€ Section Cards â”€â”€ */
.at-card { background:#fff; border:1px solid var(--cb); border-radius:16px; overflow:hidden;
           transition:box-shadow .2s; }
.at-card:hover { box-shadow:0 4px 24px rgba(0,0,0,.07); }
.at-card-head { display:flex; align-items:center; gap:14px; padding:18px 24px;
                background:linear-gradient(135deg,#f8fafc,#f1f5f9);
                border-bottom:1px solid var(--cb); }
.at-num { width:38px; height:38px; border-radius:12px; background:var(--sa-primary);
          color:#fff; display:flex; align-items:center; justify-content:center;
          font-size:13px; font-weight:800; flex-shrink:0; }
.at-title { font-size:15px; font-weight:700; color:var(--td); }
.at-sub   { font-size:12px; color:var(--tl); margin-top:2px; }
.at-card-body { padding:22px 24px; }

/* â”€â”€ Logo Drop Zone â”€â”€ */
.logo-drop-zone { border:2px dashed var(--cb); border-radius:14px; padding:24px;
                  display:flex; flex-direction:column; align-items:center; justify-content:center;
                  cursor:pointer; text-align:center; min-height:130px;
                  background:var(--bg); transition:border-color .2s, background .2s; }
.logo-drop-zone:hover { border-color:var(--sa-primary); background:var(--sa-primary-lt); }
.logo-drop-zone.drag-active { border-color:var(--sa-primary); background:var(--sa-primary-lt); }
.logo-drop-icon { font-size:32px; color:var(--sa-primary); opacity:.75; }
.logo-drop-label { font-size:13px; font-weight:600; color:var(--tb); margin-top:8px; }
.logo-drop-hint  { font-size:11px; color:var(--tl); margin-top:4px; }

/* â”€â”€ Color Block â”€â”€ */
.color-block { display:flex; gap:14px; align-items:flex-start; }
.color-preview-big {
    width:72px; height:72px; border-radius:16px;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; cursor:pointer;
    box-shadow:0 4px 14px rgba(0,0,0,.25);
    transition:background .3s, transform .2s;
    overflow:hidden; position:relative;
}
.color-preview-big:hover { transform:scale(1.05); }
.color-preview-big input[type="color"] {
    opacity:0; position:absolute; inset:0; width:100%; height:100%; cursor:pointer;
}
.color-info { flex:1; }
.color-hex { font-size:15px; font-weight:800; color:var(--td); font-family:monospace; letter-spacing:1px; }
.color-swatches { display:flex; flex-wrap:wrap; gap:7px; }
.sw { width:24px; height:24px; border-radius:8px; cursor:pointer; display:inline-block;
      box-shadow:0 2px 6px rgba(0,0,0,.2); transition:transform .15s, box-shadow .15s; }
.sw:hover { transform:scale(1.25); box-shadow:0 4px 10px rgba(0,0,0,.3); }

/* â”€â”€ Subdomain â”€â”€ */
.subdomain-wrap { position:relative; }
.subdomain-inp  { padding-right:180px!important; }
.subdomain-sfx  { position:absolute; right:14px; top:50%; transform:translateY(-50%);
                  font-size:12px; font-weight:700; color:var(--tl); pointer-events:none; }
.subdomain-pill { display:inline-flex; align-items:center; gap:7px; margin-top:8px;
                  background:var(--sa-primary-lt); color:var(--sa-primary);
                  border-radius:20px; padding:5px 14px; font-size:12px; font-weight:700; }

/* â”€â”€ Password â”€â”€ */
.pass-wrap { position:relative; }
.pass-eye  { position:absolute; right:12px; top:50%; transform:translateY(-50%);
             background:none; border:none; color:var(--tl); cursor:pointer; font-size:14px;
             padding:4px; transition:color .2s; }
.pass-eye:hover { color:var(--sa-primary); }
.pass-bar { height:4px; background:var(--cb); border-radius:4px; overflow:hidden; }
.pass-bar div { height:100%; width:0; border-radius:4px; transition:width .35s,background .35s; }
.strength-label { font-size:11px; font-weight:700; margin-top:3px; }
.pass-req-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px;
                 background:var(--bg); border:1px solid var(--cb); border-radius:12px;
                 padding:14px 16px; }
.preq { font-size:11px; font-weight:600; color:var(--tl); display:flex; align-items:center;
        gap:6px; transition:color .2s; }
.preq.met { color:var(--sa-primary); }
.preq.met i { color:var(--sa-primary); }
.preq i { font-size:10px; }

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   LIVE PREVIEW (right column)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.preview-sticky { position:sticky; top:calc(var(--hh) + 20px); }
.preview-top-label { font-size:11px; font-weight:700; text-transform:uppercase;
                     letter-spacing:.6px; color:var(--tl); margin-bottom:10px;
                     display:flex; align-items:center; gap:8px; }
.preview-pulse { width:8px; height:8px; border-radius:50%; background:var(--sa-primary);
                 animation:pulse 1.5s infinite ease-in-out; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.7)} }

/* â”€â”€ Mock Card â”€â”€ */
.mock-card { border-radius:20px; overflow:hidden; border:1px solid var(--cb);
             box-shadow:0 8px 40px rgba(0,0,0,.12); background:#fff; }

/* Hero banner */
.mock-hero { position:relative; padding:28px 22px 22px;
             display:flex; align-items:center; gap:16px;
             overflow:hidden; transition:background .35s; }

/* Decorative circles */
.mock-hero-deco { position:absolute; inset:0; pointer-events:none; }
.mock-deco-circle { position:absolute; border-radius:50%;
                    background:rgba(255,255,255,.08); }
.c1 { width:200px; height:200px; right:-60px; top:-60px; }
.c2 { width:100px; height:100px; right:40px; bottom:-40px; }

/* Logo bubble */
.mock-logo-bubble { width:72px; height:72px; border-radius:16px;
                    background:rgba(255,255,255,.22); flex-shrink:0;
                    display:flex; align-items:center; justify-content:center;
                    border:2px solid rgba(255,255,255,.3);
                    overflow:hidden; transition:all .3s;
                    box-shadow:0 4px 16px rgba(0,0,0,.2); }
.mock-logo-icon { font-size:28px; color:#fff; opacity:.9; }

/* Name block */
.mock-name-block { flex:1; min-width:0; }
.mock-name { font-size:18px; font-weight:800; color:#fff; line-height:1.2;
             word-break:break-word; text-shadow:0 1px 4px rgba(0,0,0,.2); }
.mock-name-np { font-size:12px; color:rgba(255,255,255,.82); font-weight:600; margin-top:3px; }
.mock-tagline { font-size:11px; color:rgba(255,255,255,.65); font-style:italic; margin-top:6px; }

/* Info rows */
.mock-body { padding:18px 22px; display:flex; flex-direction:column; gap:11px; }
.mock-info-row { display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:var(--tb); }
.mock-info-icon { width:18px; flex-shrink:0; text-align:center; }
.mock-info-icon i { color:var(--sa-primary); font-size:12px; }
.mock-info-val { flex:1; word-break:break-all; }

/* Footer */
.mock-footer { padding:16px 22px; background:var(--bg); border-top:1px solid var(--cb); }
.mock-badge { font-size:10px; font-weight:700; padding:4px 11px; border-radius:20px;
              text-transform:uppercase; letter-spacing:.5px; }
.mock-badge.plan   { background:var(--sa-primary-lt); color:var(--sa-primary); }
.mock-badge.status { background:#fef3c7; color:#b45309; }
.mock-badge.status.is-active { background:#d1fae5; color:#065f46; }
.mock-badge.status.is-suspended { background:#fee2e2; color:#991b1b; }

.mock-admin-block { border-top:1px solid var(--cb); padding-top:12px; }
.mock-admin-label { font-size:9px; font-weight:700; color:var(--tl);
                    text-transform:uppercase; letter-spacing:.7px; }
.mock-admin-avatar { width:34px; height:34px; border-radius:10px;
                     background:var(--sa-primary); color:#fff;
                     display:flex; align-items:center; justify-content:center;
                     font-size:13px; font-weight:800; flex-shrink:0;
                     transition:background .3s; }
.mock-admin-name  { font-size:13px; font-weight:700; color:var(--td); }
.mock-admin-email { font-size:11px; color:var(--tl); margin-top:1px; }

/* Checklist */
.chk-card { background:#fff; border:1px solid var(--cb); border-radius:14px; padding:16px 18px; }
.chk-card-title { font-size:11px; font-weight:700; color:var(--tl);
                  text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
.chk-list { display:flex; flex-direction:column; gap:6px; }
.chk-row { display:flex; align-items:center; gap:9px; font-size:12px;
           font-weight:600; color:var(--tl); padding:5px 0;
           border-bottom:1px solid var(--bg); transition:color .2s; }
.chk-row:last-child { border-bottom:none; }
.chk-row.done { color:var(--sa-primary); }
.chk-row.done i { color:var(--sa-primary); }
.chk-row i { font-size:11px; transition:color .2s; }
</style>

<script>
/* HELPERS */
const _gid = id => document.getElementById(id);
const _val = id => _gid(id)?.value?.trim() ?? '';

function syncPreview() {
    const name    = _val('instName')    || 'Institute Name';
    const nameNp  = _val('instNameNp') || 'à¤¸à¤‚à¤¸à¥à¤¥à¤¾à¤•à¥‹ à¤¨à¤¾à¤®';
    const tagline = _val('instTagline') || 'Tagline goes here';
    const email   = _val('instEmail')  || 'info@institute.com';
    const phone   = _val('instPhone')  || '+977-XXXXXXXXX';
    const address = _val('instAddress')|| 'Institute address';
    const website = _val('instWebsite');
    const subdomain = _val('subdomainInp') || 'subdomain';
    const plan    = _gid('billingPlan')?.value  || 'starter';
    const status  = _gid('tenantStatus')?.value || 'trial';
    const color   = _gid('themeColor')?.value   || '#009E7E';
    const adminN  = _val('adminName')  || 'Admin Name';
    const adminE  = _val('adminEmail') || 'admin@institute.com';

    _setText('mockName', name);    _setText('mockNameNp', nameNp);
    _setText('mockTagline', tagline); _setText('mockEmail', email);
    _setText('mockPhone', phone);  _setText('mockAddress', address);
    _setText('mockSubdomain', subdomain + '.hamrolabs.com.np');

    const wRow = _gid('mockWebsiteRow');
    if (wRow) {
        if (website) { wRow.style.display = 'flex'; _setText('mockWebsite', website); }
        else         { wRow.style.display = 'none'; }
    }

    const hero = _gid('mockHero'); const cpb = _gid('colorPreviewBig');
    const hexLbl = _gid('colorHexLabel'); const ava = _gid('mockAdminAvatar');
    if (hero) hero.style.background = color;
    if (cpb)  cpb.style.background  = color;
    if (hexLbl) hexLbl.textContent  = color.toUpperCase();
    if (ava)  ava.style.background  = color;

    const pLabels = { starter:'Starter', growth:'Growth', professional:'Professional', enterprise:'Enterprise' };
    _setText('mockPlan', (pLabels[plan] || plan));

    const sBadge = _gid('mockStatus');
    if (sBadge) {
        const sMap = { trial:'Trial', active:'Active', suspended:'Suspended' };
        sBadge.textContent = sMap[status] || status;
        sBadge.className   = 'mock-badge status' +
            (status === 'active' ? ' is-active' : status === 'suspended' ? ' is-suspended' : '');
    }

    _setText('mockAdminName', adminN); _setText('mockAdminEmail', adminE);
    const initials = adminN.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    _setText('mockAdminAvatar', initials || 'A');
    updateChecklist();
}
function _setText(id, text) { const el = _gid(id); if (el) el.textContent = text; }

function handleSubdomainInput() {
    const inp = _gid('subdomainInp');
    const clean = inp.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
    inp.value = clean;
    const pill = _gid('subPillText');
    if (pill) pill.textContent = (clean || '...') + '.hamrolabs.com.np';
    syncPreview();
}

function setColor(hex) {
    const tc = _gid('themeColor');
    if (tc) tc.value = hex;
    syncPreview();
}

function handleLogoUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 2 * 1024 * 1024) { alert('Max file size is 2MB.'); return; }
    const reader = new FileReader();
    reader.onload = function(e) {
        const src = e.target.result;
        const es = _gid('logoEmptyState'); const fi = _gid('logoFilePreview');
        const cb = _gid('clearLogoBtn');  const mi = _gid('mockLogoIcon'); const ml = _gid('mockLogoImg');
        if (es) es.style.display = 'none';
        if (fi) { fi.src = src; fi.style.display = 'block'; }
        if (cb) cb.style.display = 'flex';
        if (mi) mi.style.display = 'none';
        if (ml) { ml.src = src; ml.style.display = 'block'; }
        updateChecklist();
    };
    reader.readAsDataURL(file);
}

function clearLogo() {
    const il = _gid('instLogo'); if (il) il.value = '';
    const fi = _gid('logoFilePreview'); if (fi) fi.style.display = 'none';
    const es = _gid('logoEmptyState'); if (es) es.style.display = 'flex';
    const cb = _gid('clearLogoBtn'); if (cb) cb.style.display = 'none';
    const ml = _gid('mockLogoImg'); if (ml) ml.style.display = 'none';
    const mi = _gid('mockLogoIcon'); if (mi) mi.style.display = 'flex';
    updateChecklist();
}

function checkPassStrength() {
    const p = _val('adminPass');
    const reqs = { length: p.length >= 8, upper: /[A-Z]/.test(p), number: /[0-9]/.test(p), special: /[^A-Za-z0-9]/.test(p) };
    ['length','upper','number','special'].forEach(function(k) {
        const el = _gid('req-' + k); if (!el) return;
        el.className = 'preq' + (reqs[k] ? ' met' : '');
        const ico = el.querySelector('i');
        if (ico) ico.className = reqs[k] ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-dot';
    });
    const score = Object.values(reqs).filter(Boolean).length;
    const cfgs = [null,
        {w:'25%', bg:'#E11D48', txt:'Weak',     col:'#E11D48'},
        {w:'50%', bg:'#F59E0B', txt:'Fair',     col:'#F59E0B'},
        {w:'75%', bg:'#3B82F6', txt:'Good',     col:'#3B82F6'},
        {w:'100%',bg:'#009E7E', txt:'Strong',   col:'#009E7E'}
    ];
    const cfg = cfgs[score];
    if (cfg) {
        const bar = _gid('strengthBar'); const lbl = _gid('strengthLabel');
        if (bar) { bar.style.width = cfg.w; bar.style.background = cfg.bg; }
        if (lbl) { lbl.textContent = cfg.txt; lbl.style.color = cfg.col; }
    }
    checkConfirmPass();
    updateChecklist();
}

function checkConfirmPass() {
    const p1 = _val('adminPass'), p2 = _val('adminPassConfirm');
    const errEl = _gid('err-adminPassConfirm');
    if (errEl) errEl.textContent = (p2 && p1 !== p2) ? 'Passwords do not match' : '';
}

function togglePass(inputId, iconId) {
    const inp = _gid(inputId); const ico = _gid(iconId);
    if (!inp || !ico) return;
    if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fa-solid fa-eye-slash'; }
    else { inp.type = 'password'; ico.className = 'fa-solid fa-eye'; }
}

function updateChecklist() {
    _chkSet('chk-name',      !!_val('instName'));
    _chkSet('chk-subdomain', _val('subdomainInp').length >= 3);
    _chkSet('chk-contact',   !!_val('instEmail') && !!_val('instPhone'));
    _chkSet('chk-address',   !!_val('instAddress'));
    _chkSet('chk-logo',      _gid('mockLogoImg') ? _gid('mockLogoImg').style.display !== 'none' : false);
    _chkSet('chk-admin',     !!_val('adminName') && !!_val('adminEmail'));
    const p = _val('adminPass');
    const strong = p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p);
    _chkSet('chk-pass', strong && p === _val('adminPassConfirm') && !!_val('adminPassConfirm'));
}
function _chkSet(id, done) {
    const el = _gid(id); if (!el) return;
    el.className = 'chk-row' + (done ? ' done' : '');
    const ico = el.querySelector('i');
    if (ico) ico.className = done ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle-dot';
}

function validateAll() {
    let ok = true;
    const rules = [
        {id:'instName',     err:'err-instName',    msg:'Institute name is required'},
        {id:'instAddress',  err:'err-instAddress', msg:'Address is required'},
        {id:'instPhone',    err:'err-instPhone',   msg:'Phone is required',      pat:/^[\d\+\-\s]{7,15}$/, patMsg:'Enter a valid phone'},
        {id:'instEmail',    err:'err-instEmail',   msg:'Email is required',      type:'email'},
        {id:'subdomainInp', err:'err-subdomain',   msg:'Subdomain is required',  min:3, minMsg:'Min 3 characters'},
        {id:'adminName',    err:'err-adminName',   msg:'Admin name is required'},
        {id:'adminPhone',   err:'err-adminPhone',  msg:'Admin phone is required'},
        {id:'adminEmail',   err:'err-adminEmail',  msg:'Admin email is required', type:'email'},
        {id:'adminPass',    err:'err-adminPass',   msg:'Password is required'},
    ];
    rules.forEach(function(r) {
        const e = _gid(r.err); if (e) e.textContent = '';
        const i = _gid(r.id);  if (i) i.classList.remove('is-err','is-ok');
    });
    rules.forEach(function(r) {
        const el = _gid(r.id); if (!el) return;
        const v = el.value.trim(); let msg = '';
        if (!v) msg = r.msg;
        else if (r.min && v.length < r.min) msg = r.minMsg;
        else if (r.type === 'email' && !/^[\w\-\.]+@([\w\-]+\.)+[\w\-]{2,}$/.test(v)) msg = 'Enter a valid email';
        else if (r.pat && !r.pat.test(v)) msg = r.patMsg;
        if (msg) {
            const e = _gid(r.err); if (e) e.textContent = msg;
            el.classList.add('is-err'); ok = false;
        } else { el.classList.add('is-ok'); }
    });
    const p = _val('adminPass');
    if (p && !(p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p))) {
        const e = _gid('err-adminPass'); if (e) e.textContent = 'Password not strong enough';
        const inp = _gid('adminPass'); if (inp) inp.classList.add('is-err'); ok = false;
    }
    const p2 = _val('adminPassConfirm');
    if (!p2) {
        const e = _gid('err-adminPassConfirm'); if (e) e.textContent = 'Please confirm your password';
        const inp = _gid('adminPassConfirm'); if (inp) inp.classList.add('is-err'); ok = false;
    } else if (p !== p2) {
        const e = _gid('err-adminPassConfirm'); if (e) e.textContent = 'Passwords do not match';
        const inp = _gid('adminPassConfirm'); if (inp) inp.classList.add('is-err'); ok = false;
    }
    return ok;
}

function saveInstitute() {
    if (!validateAll()) {
        if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification('Please fix all highlighted errors.', 'error');
        var firstErr = document.querySelector('.is-err');
        if (firstErr) firstErr.scrollIntoView({behavior:'smooth', block:'center'});
        return;
    }
    var fd = new FormData();
    fd.append('name',       _val('instName'));
    fd.append('nepaliName', _val('instNameNp'));
    fd.append('tagline',    _val('instTagline') || 'Education evolved.');
    fd.append('subdomain',  _val('subdomainInp'));
    fd.append('address',    _val('instAddress'));
    fd.append('phone',      _val('instPhone'));
    fd.append('email',      _val('instEmail'));
    fd.append('adminName',  _val('adminName'));
    fd.append('adminEmail', _val('adminEmail'));
    fd.append('adminPhone', _val('adminPhone'));
    fd.append('adminPass',  _gid('adminPass').value);
    fd.append('plan',       _gid('billingPlan').value);
    fd.append('status',     _gid('tenantStatus').value);
    fd.append('brandColor', _gid('themeColor').value);
    var logo = _gid('instLogo');
    if (logo && logo.files && logo.files[0]) fd.append('logo', logo.files[0]);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || window.CSRF_TOKEN;
    fd.append('csrf_token', csrf);

    var doConfirm = (typeof SuperAdmin !== 'undefined')
        ? SuperAdmin.confirmAction('Finalize Registration?', 'This will create the institute account and admin login credentials.', 'Yes, Register Now')
        : Promise.resolve({isConfirmed: window.confirm('Finalize Registration?')});

    doConfirm.then(function(res) {
        if (!res.isConfirmed) return;
        var btn = _gid('btnFinalSubmit');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Registering...'; }
        fetch(window.APP_URL + '/api/super-admin/tenants/save', {
            method: 'POST', headers: {'X-CSRF-Token': csrf}, body: fd
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification(data.message, 'success');
                setTimeout(function() {
                    if (typeof SuperAdmin !== 'undefined') SuperAdmin.goNav('tenants');
                    else window.location.href = 'tenant-management.php';
                }, 1500);
            } else {
                if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification(data.message, 'error');
                else alert(data.message);
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i>Register Institute'; }
            }
        })
        .catch(function(err) {
            if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification('Network error. Please try again.', 'error');
            console.error(err);
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i>Register Institute'; }
        });
    });
}

/* â”€â”€â”€ Expose ALL on window â€” required for SPA innerHTML injection â”€â”€â”€ */
window.saveInstitute        = saveInstitute;
window.syncPreview          = syncPreview;
window.handleSubdomainInput = handleSubdomainInput;
window.setColor             = setColor;
window.handleLogoUpload     = handleLogoUpload;
window.clearLogo            = clearLogo;
window.checkPassStrength    = checkPassStrength;
window.checkConfirmPass     = checkConfirmPass;
window.togglePass           = togglePass;

/* â”€â”€â”€ Init â€” runs immediately, works in both direct load and SPA injection â”€â”€â”€ */
(function initAddTenantPage() {
    try { syncPreview(); } catch(e) {}
    ['instName','instNameNp','instTagline','instEmail','instPhone','instAddress','instWebsite','adminName','adminEmail']
        .forEach(function(id) { var el = _gid(id); if (el) el.addEventListener('input', syncPreview); });
    ['billingPlan','tenantStatus']
        .forEach(function(id) { var el = _gid(id); if (el) el.addEventListener('change', syncPreview); });
    var dz = _gid('logoDropZone');
    if (dz) {
        dz.addEventListener('dragover', function(e) { e.preventDefault(); dz.classList.add('drag-active'); });
        dz.addEventListener('dragleave', function() { dz.classList.remove('drag-active'); });
        dz.addEventListener('drop', function(e) {
            e.preventDefault(); dz.classList.remove('drag-active');
            if (e.dataTransfer.files[0]) handleLogoUpload({files: e.dataTransfer.files});
        });
    }
})();
</script>

<?php include 'footer.php'; ?>
