<?php
/**
 * Shared Student Admission Form Component
 *
 * Required variables (must be set before requiring this file):
 *   $apiEndpoint       (string)  - API URL to POST the form to
 *   $successRedirectUrl (string) - URL to navigate to on success
 *   $componentId       (string)  - Unique suffix (e.g. 'fd' or 'adm') to avoid DOM ID collisions
 *   $courses           (array)   - Fetched courses for the tenant
 *   $batches           (array)   - Fetched batches for the tenant
 *   $viewAllStudentsUrl (string) - URL for the "View All Students" button
 *   $pageTitle         (string)  - Optional: page heading override
 */

$componentId        = $componentId ?? 'shared';
$apiEndpoint        = $apiEndpoint ?? APP_URL . '/api/frontdesk/students';
$successRedirectUrl = $successRedirectUrl ?? APP_URL . '/dash/front-desk/students';
$viewAllStudentsUrl  = $viewAllStudentsUrl ?? APP_URL . '/dash/front-desk/students';
$pageTitle          = $pageTitle ?? 'Student Admission';
$coursesFallbackJson = htmlspecialchars(json_encode($courses ?? []), ENT_QUOTES);

$formId  = 'formAdmission_' . $componentId;
$btnId   = 'btnSubmitAdm_' . $componentId;
$passId  = 'inpPass_' . $componentId;
$confId  = 'inpConfPass_' . $componentId;
$dobBsId = 'inpDobBs_' . $componentId;
$selCourseId = 'selCourse_' . $componentId;
$selBatchId  = 'selBatch_' . $componentId;
$dialogId    = 'successDialog_' . $componentId;
$scCardId    = 'scCard_' . $componentId;
$initialMode = $initialMode ?? null; // 'new' or 'existing'
?>
<style>
/* ── PREMIUM DESIGN SYSTEM (Shared Admission Form) ── */
:root {
    --p: #00b894;
    --p-d: #009e7e;
    --p-lt: rgba(0, 184, 148, 0.08);
    --s: #6c5ce7;
    --a: #ff7675;
    --glass: rgba(255, 255, 255, 0.75);
    --glass-b: blur(12px);
    --sh-p: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
    --rad-p: clamp(16px, 4vw, 24px);
    --trans: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 101% Responsive Base */
.main { background: #f1f5f9; }
.pg { max-width: 1200px; margin: 0 auto; animation: slideUp 0.6s ease-out; }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

/* Premium Header Card */
.adm-hero {
    background: linear-gradient(135deg, var(--p-d), var(--p));
    border-radius: var(--rad-p);
    padding: clamp(1.5rem, 5cvh, 3rem) clamp(1rem, 3vw, 1.5rem);
    margin-bottom: clamp(1.5rem, 4dvh, 2rem);
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 184, 148, 0.2);
}
.adm-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px;
    background: rgba(255,255,255,0.1); border-radius: 50%;
}
.adm-hero-content { position: relative; z-index: 2; text-align: center; }
.adm-hero-ico { width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 28px; backdrop-filter: blur(4px); }

/* Form Sections as Premium Cards */
.sc-adm {
    background: var(--glass);
    backdrop-filter: var(--glass-b);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: clamp(16px, 3vw, 20px);
    padding: clamp(1rem, 4vw, 2rem);
    margin-bottom: clamp(1.5rem, 4dvh, 2rem);
    box-shadow: var(--sh-p);
}

.sc-title { font-size: clamp(0.95rem, 2.5vw, 1.1rem); font-weight: 800; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; }
.sc-title i { color: var(--p); width: 32px; height: 32px; background: var(--p-lt); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; }

/* Grid System - Strictly Mobile-First */
.grid-box { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }

/* Desktop Enhancements */
@media (min-width: 768px) {
    .grid-2 { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 1024px) {
    .grid-3 { grid-template-columns: repeat(3, 1fr); }
}

/* Premium Inputs */
.f-grp { margin-bottom: 0.5rem; }
.f-lbl { display: block; font-size: clamp(11px, 1.2vw, 13px); font-weight: 700; color: #475569; margin-bottom: 8px; margin-left: 4px; }
.f-lbl.req::after { content: '*'; color: var(--a); margin-left: 4px; }

.ipt-box { position: relative; }
.ipt-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; pointer-events: none; transition: var(--trans); }

.fi {
    width: 100%; padding: clamp(10px, 2vw, 14px) clamp(12px, 2vw, 16px) clamp(10px, 2vw, 14px) 48px; border: 2px solid #e2e8f0; border-radius: clamp(10px, 2vw, 14px);
    font-size: clamp(13px, 1.5vw, 14px); font-weight: 600; outline: none; transition: var(--trans); background: #fff;
    color: #1e293b; font-family: inherit;
}
.fi:focus { border-color: var(--p); box-shadow: 0 0 0 5px var(--p-lt); }
.fi:focus ~ i,
.ipt-box:focus-within i { color: var(--p); }

/* Custom Select Styling */
.fi-sel { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 16px center; background-size: 18px; }

/* Priority & Security Callouts */
.callout-p { background: #f0fdf4; border: 2px dashed #bbf7d0; box-shadow: none; position: relative; padding-top: 3rem; }
.callout-p::before { content: 'HIGH PRIORITY'; position: absolute; top: 1rem; left: 2rem; background: var(--p); color: #fff; font-size: 10px; font-weight: 900; padding: 4px 12px; border-radius: 50px; letter-spacing: 0.05em; }

.callout-s { border-color: #fecdd3; background: #fff1f2; }

/* Buttons */
.btn-p { width: 100%; padding: clamp(14px, 3vw, 18px); background: linear-gradient(135deg, var(--p-d), var(--p)); color: #fff; border: none; border-radius: 16px; font-size: clamp(14px, 1.5vw, 16px); font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; transition: var(--trans); box-shadow: 0 10px 25px -5px rgba(0, 184, 148, 0.4); }
.btn-p:hover { transform: translateY(-3px); box-shadow: 0 20px 35px -5px rgba(0, 184, 148, 0.5); }
.btn-p:active { transform: translateY(0); }

/* Mode Selection Overlay */
.mode-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(15px); z-index: 20000; display: flex; align-items: center; justify-content: center; padding: 2rem; }
.mode-card { background: #fff; border-radius: 32px; padding: clamp(2rem, 5vw, 4rem); max-width: 800px; width: 100%; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.5); text-align: center; }
.mode-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; margin-top: 3rem; }
@media (min-width: 640px) { .mode-grid { grid-template-columns: 1fr 1fr; } }
.mode-opt { padding: 2rem; border: 3px solid #f1f5f9; border-radius: 24px; cursor: pointer; transition: var(--trans); position: relative; overflow: hidden; }
.mode-opt:hover { border-color: var(--p); background: var(--p-lt); transform: translateY(-5px); }
.mode-opt i { font-size: 40px; color: var(--p); margin-bottom: 1.5rem; }
.mode-opt h3 { font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem; }
.mode-opt p { font-size: 14px; color: #64748b; font-weight: 500; }
.mode-opt.active { border-color: var(--p); background: var(--p-lt); }

/* Search Results for Existing Student */
.search-results { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 100; max-height: 250px; overflow-y: auto; display: none; }
.search-item { padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f1f5f9; }
.search-item:last-child { border-bottom: none; }
.search-item:hover { background: #f8fafc; }
.search-item img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
.search-item .name { font-weight: 700; color: #1e293b; font-size: 14px; }
.search-item .meta { font-size: 11px; color: #94a3b8; }

/* Batch Multi-Select Chips */
.batch-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.chip { background: var(--p); color: #fff; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; animation: scaleIn 0.2s ease-out; }
.chip i { cursor: pointer; opacity: 0.8; }
.chip i:hover { opacity: 1; }
@keyframes scaleIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.hidden-section { display: none; }

/* Custom Validation Styles */
.f-grp.error .fi { border-color: var(--a); background: #fff1f2; box-shadow: 0 0 0 5px rgba(255, 118, 117, 0.1); }
.f-grp.error .f-lbl { color: var(--a); }
.f-grp.error i { color: var(--a); }
.err-msg { color: var(--a); font-size: 11px; font-weight: 700; margin-top: 6px; margin-left: 4px; display: none; animation: shake 0.4s ease-in-out; }
.f-grp.error .err-msg { display: block; }

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

/* Breadcrumb Styling */
.bc { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; color: #94a3b8; }
.bc a { color: #64748b; text-decoration: none; transition: var(--trans); }
.bc a:hover { color: var(--p); }
.bc-sep { font-size: 16px; opacity: 0.5; }
.bc-cur { color: var(--p); }
.bc-cur { color: var(--p); }

/* Premium Success Modal System */
.m-overlay { 
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); 
    z-index: 99999; display: none; align-items: center; justify-content: center; 
    padding: 2rem; opacity: 0; transition: all 0.4s ease; backdrop-filter: blur(0px); 
}
.m-overlay.active { opacity: 1; backdrop-filter: blur(12px); pointer-events: auto; }

.m-card { 
    background: #fff; border-radius: 32px; padding: clamp(2rem, 5vw, 3.5rem); 
    max-width: 550px; width: 100%; box-shadow: 0 40px 100px -20px rgba(0,0,0,0.3); 
    text-align: center; transform: scale(0.85) translateY(40px); opacity: 0; transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); 
}
.m-overlay.active .m-card { transform: scale(1) translateY(0); opacity: 1; }

.m-ico { 
    width: 84px; height: 84px; border-radius: 24px; display: flex; align-items: center; 
    justify-content: center; font-size: 32px; color: #fff; margin: 0 auto 2rem; 
    box-shadow: 0 20px 40px -10px rgba(0, 184, 148, 0.4); 
}

/* Credentials Premium Box */
.cred-box { 
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; 
    padding: 1.5rem; text-align: left; margin-top: 1.5rem; position: relative; overflow: hidden; 
}
.cred-box::before { 
    content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--p); 
}
.cred-title { font-size: 11px; font-weight: 800; color: var(--p); margin-bottom: 12px; letter-spacing: 0.05em; text-transform: uppercase; }
.cred-line { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #1e293b; }
.cred-line span { opacity: 0.5; width: 70px; font-weight: 700; color: #64748b; }
.cred-val { font-family: 'JetBrains Mono', 'Courier New', monospace; background: #fff; padding: 4px 10px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; color: var(--p); }
</style>

<!-- Main Admission Form Container -->
<div class="sc-adm-box" id="scBox_<?= $componentId ?>">

<div class="pg">


    <div class="pg-head" style="background: none; border: none; padding: 0; margin-bottom: clamp(1rem, 3dvh, 1.5rem);">
        <div class="pg-left">
            <div class="pg-ico"><i class="fas fa-user-plus"></i></div>
            <div>
                <h1 class="pg-title"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
        </div>
    </div>

    <form id="<?= $formId ?>" onsubmit="handleAdmissionSubmit_<?= $componentId ?>(event)" novalidate>
        <div id="studentProfileSections_<?= $componentId ?>">
            <!-- Section 1: Academic High Priority -->
            <div class="sc-adm callout-p">
                <h3 class="sc-title"><i class="fas fa-book-reader"></i> Academic Placement</h3>
                <div class="grid-box grid-2">
                    <div class="f-grp">
                        <label class="f-lbl req">Target Course</label>
                        <div class="ipt-box">
                            <i class="fas fa-award"></i>
                            <select id="<?= $selCourseId ?>" name="course_id" class="fi fi-sel" required 
                                    data-fallback='<?= $coursesFallbackJson ?>'>
                                <option value="" disabled selected>⏳ Loading courses...</option>
                            </select>
                            <div class="err-msg">Please select a target course.</div>
                        </div>
                    </div>
                    <div class="f-grp">
                        <label class="f-lbl req">Assigned Batch</label>
                        <div class="ipt-box">
                            <i class="fas fa-clock"></i>
                            <select id="<?= $selBatchId ?>" class="fi fi-sel" disabled required>
                                <option value="">— Select course first —</option>
                            </select>
                            <div class="err-msg">At least one batch enrollment is required.</div>
                        </div>
                        <button type="button" class="btn bt" style="margin-top: 10px; width: auto; font-size: 12px; padding: 8px 15px;" onclick="addBatchChip_<?= $componentId ?>()">
                            <i class="fas fa-plus"></i> Add Batch
                        </button>
                        <div id="batchChips_<?= $componentId ?>" class="batch-chips"></div>
                        <input type="hidden" name="batch_id" id="hiddenSingleBatch_<?= $componentId ?>"> 
                    </div>
                </div>
            </div>

            <!-- Section 2: Primary Identity -->
            <div class="sc-adm">
            <h3 class="sc-title"><i class="fas fa-id-card"></i> Student Identity</h3>
            <div class="grid-box grid-2">
                <div class="f-grp">
                    <label class="f-lbl req">Full Name</label>
                    <div class="ipt-box">
                        <i class="fas fa-user-edit"></i>
                        <input type="text" name="full_name" class="fi" placeholder="e.g. Roshan Sharma" required>
                        <div class="err-msg">Student name is required.</div>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl req">Contact Number</label>
                    <div class="ipt-box">
                        <i class="fas fa-mobile-screen-button"></i>
                        <input type="tel" name="contact_number" class="fi" placeholder="98XXXXXXXX" pattern="[0-9]{10}" required>
                        <div class="err-msg">Enter a valid 10-digit mobile number.</div>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl req">Email Address</label>
                    <div class="ipt-box">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="fi" placeholder="email@example.com" required>
                        <div class="err-msg">Enter a valid email address.</div>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl req">Gender Selection</label>
                    <div class="ipt-box">
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" class="fi fi-sel" required>
                            <option value="">Choose Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="err-msg">Please select a gender.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Detailed Information -->
        <div class="sc-adm">
            <h3 class="sc-title"><i class="fas fa-folder-open"></i> Background Details</h3>
            <div class="grid-box grid-3">
                <div class="f-grp">
                    <label class="f-lbl req">DOB (BS)</label>
                    <input type="text" name="dob_bs" id="<?= $dobBsId ?>" class="fi" placeholder="YYYY-MM-DD" style="padding-left: 20px;" required onblur="window.handleDobSync_<?= $componentId ?>(this.value)">
                    <input type="hidden" name="dob_ad" id="inpDobAd_<?= $componentId ?>">
                    <div class="err-msg">Date of birth (BS) is required.</div>
                </div>
            </div>

            <div class="grid-box" style="margin-top: 1.5rem;">
                <div class="f-grp">
                    <label class="f-lbl req">Student Address</label>
                    <textarea name="permanent_address" class="fi" style="padding-left: 16px; min-height: 80px;" placeholder="Full Address..." required></textarea>
                    <div class="err-msg">Address details are required.</div>
                </div>
            </div>
        </div>

        <!-- Section 4: Security Callout -->
        <div class="sc-adm callout-p" style="border-color: #e2e8f0; background: #f8fafc;">
            <h3 class="sc-title" style="color: #1e293b;"><i class="fas fa-key"></i> System Access</h3>
            <p style="font-size: 12px; color: #64748b; margin: -1rem 0 1.5rem 3.5rem; font-weight: 600;">Standard student portal credentials</p>
            <div class="grid-box">
                <div class="f-grp">
                    <label class="f-lbl req">Access Password</label>
                    <div class="ipt-box">
                        <i class="fas fa-user-lock"></i>
                        <input type="password" name="password" id="<?= $passId ?>" class="fi" placeholder="e.g. Student@123" minlength="8" required>
                        <div class="err-msg">Password must be at least 8 characters.</div>
                        <span onclick="togglePassView_<?= $componentId ?>('<?= $passId ?>')" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer; padding: 5px; z-index:2;">
                            <i class="fas fa-eye" id="<?= $passId ?>Eye"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- End studentProfileSections -->

    <!-- Final Submit Actions (Always Visible) -->
    <div id="submitActions_<?= $componentId ?>" class="sc-adm" style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 2rem; background: #fff;">
            <div style="display: flex; gap: 1rem; align-items: center; justify-content: center; flex-wrap: wrap;">
                <button type="submit" id="<?= $btnId ?>" class="btn-p" style="flex: 1; min-width: 280px; margin: 0; box-shadow: 0 4px 12px rgba(0, 184, 148, 0.25);">
                    <i class="fas fa-check-circle"></i> <span id="btnText_<?= $componentId ?>">FINALISE &amp; SUBMIT ADMISSION</span>
                </button>
                
                <button type="button" class="btn-p" style="flex: 0 1 200px; background: #fff; color: #64748b; border: 2px solid #e2e8f0; box-shadow: none; margin: 0;" onclick="window.history.back()">
                    <i class="fas fa-times-circle"></i> CANCEL
                </button>
            </div>

            <p id="infoTextContainer_<?= $componentId ?>" style="text-align: center; font-size: 11px; color: #64748b; margin-top: 1.5rem; font-weight: 600;">
                <i class="fas fa-info-circle"></i> <span id="infoText_<?= $componentId ?>">This will generate a unique Student Roll Number automatically.</span>
            </p>
        </div>
    </form>
</div>
</div> <!-- Close sc-adm-box -->

<!-- PREMIUM SUCCESS DIALOG -->
<div class="m-overlay" id="<?= $dialogId ?>">
    <div class="m-card" id="<?= $scCardId ?>">
        <div class="m-ico" id="mIcon_<?= $componentId ?>"></div>
        <h3 id="mTitle_<?= $componentId ?>" style="font-size: 26px; font-weight: 900; color: #0f172a; margin: 0 0 0.5rem;"></h3>
        <div id="mBody_<?= $componentId ?>" style="font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 2.5rem;"></div>
        <div id="mActions_<?= $componentId ?>" style="display: flex; flex-direction: column; gap: 12px;"></div>
    </div>
</div>

<script>
/* ── ADMISSION FORM JS (Component: <?= $componentId ?>) ── */
(function() {
    // State management
    let admissionMode = '<?= $initialMode ?? 'new' ?>';
    const CID          = '<?= $componentId ?>';
    const API_ENDPOINT = '<?= addslashes($apiEndpoint) ?>';
    const REDIRECT_URL = '<?= addslashes($successRedirectUrl) ?>';
    let selectedBatches = [];

    <?php
    $user     = $_SESSION['userData'] ?? [];
    $userRole = strtolower($user['role'] ?? '');
    echo "window.currentUser = window.currentUser || " . json_encode($user) . ";\n";
    echo "window.APP_URL     = window.APP_URL     || '" . APP_URL . "';\n";
    // Set _userRole so NexusDataLoader._getApiBase() picks the right API path
    echo "window._userRole   = window._userRole   || '" . addslashes($userRole) . "';\n";
    ?>

    // ── Mode Selection ──
    window['setAdmissionMode_' + CID] = function(mode) {
        admissionMode = mode;
        const overlay = document.getElementById('modeOverlay_' + CID);
        const searchSec = document.getElementById('existingStudentSearch_' + CID);
        const profileSecs = document.getElementById('studentProfileSections_' + CID);
        
        if (overlay) overlay.style.display = 'none';
        
        if (mode === 'existing') {
            if (searchSec) searchSec.classList.remove('hidden-section');
            if (profileSecs) profileSecs.classList.add('hidden-section');
            
            // Remove required from profile fields
            if (profileSecs) {
                profileSecs.querySelectorAll('[required]').forEach(el => {
                    el.dataset.wasRequired = 'true';
                    el.removeAttribute('required');
                });
            }

            // Populate students dropdown
            window['populateStudentsDropdown_' + CID]();

            // Update Submit Button Label
            const btnText = document.getElementById('btnText_' + CID);
            if (btnText) btnText.textContent = 'CONFIRM NEW ENROLLMENT';
            
            const infoText = document.getElementById('infoText_' + CID);
            if (infoText) infoText.textContent = 'This enrollment will be added to the student profile records.';
        } else {
            if (searchSec) searchSec.classList.add('hidden-section');
            if (profileSecs) profileSecs.classList.remove('hidden-section');
            
            // Re-add required
            if (profileSecs) {
                profileSecs.querySelectorAll('[data-was-required="true"]').forEach(el => {
                    el.setAttribute('required', '');
                });
            }

            // Update Submit Button Label
            const btnText = document.getElementById('btnText_' + CID);
            if (btnText) btnText.textContent = 'FINALISE & SUBMIT ADMISSION';

            const infoText = document.getElementById('infoText_' + CID);
            if (infoText) infoText.textContent = 'This will generate a unique Student Roll Number automatically.';
        }
    };

    // ── Student Selection Logic (Dropdown) ──
    let allStudentsData = [];
    window['populateStudentsDropdown_' + CID] = async function() {
        const sel = document.getElementById('stuSelect_' + CID);
        try {
            // Determine student fetch endpoint based on the main API endpoint to maintain role-based access
            const fetchPath = API_ENDPOINT.includes('/frontdesk/') ? '/api/frontdesk/students' : '/api/admin/students';
            const res = await fetch(`${window.APP_URL}${fetchPath}?per_page=1000`);
            const result = await res.json();
            if (result.success && result.data) {
                allStudentsData = result.data;
                sel.innerHTML = '<option value="">-- Choose Student --</option>' + 
                    result.data.map(s => `<option value="${s.id}">${s.full_name} (${s.roll_no || 'No Roll'})</option>`).join('');
            } else {
                sel.innerHTML = '<option value="">Failed to load students</option>';
            }
        } catch (e) {
            console.error('Fetch students error', e);
            sel.innerHTML = '<option value="">Error loading list</option>';
        }
    };

    window['onStudentDropdownChange_' + CID] = function(id) {
        if (!id) {
            window['clearStuSelection_' + CID]();
            return;
        }
        const s = allStudentsData.find(stu => stu.id == id);
        if (s) {
            window['selectExistingStudent_' + CID](s.id, s.full_name, s.roll_no, s.photo_url);
        }
    };

    window['selectExistingStudent_' + CID] = function(id, name, roll, photo) {
        document.getElementById('valStuId_' + CID).value = id;
        document.getElementById('selStuName_' + CID).textContent = name;
        document.getElementById('selStuMeta_' + CID).textContent = roll;
        document.getElementById('selStuCard_' + CID).style.display = 'flex';
        // Hide the select box once selected to match the premium card look
        document.getElementById('stuSelect_' + CID).parentElement.style.display = 'none';
        
        if (photo) {
            document.getElementById('selStuImg_' + CID).src = photo;
            document.getElementById('selStuImg_' + CID).style.display = 'block';
        } else {
            document.getElementById('selStuImg_' + CID).style.display = 'none';
        }
    };
    
    window['clearStuSelection_' + CID] = function() {
        document.getElementById('valStuId_' + CID).value = '';
        document.getElementById('selStuCard_' + CID).style.display = 'none';
        document.getElementById('stuSelect_' + CID).parentElement.style.display = 'block';
        document.getElementById('stuSelect_' + CID).value = '';
    };

    // ── Batch Multi-Enrollment Logic ──
    window['addBatchChip_' + CID] = function() {
        const sel = document.getElementById('<?= $selBatchId ?>');
        const courseSel = document.getElementById('<?= $selCourseId ?>');
        if (!sel.value) return;
        
        if (selectedBatches.some(b => b.id == sel.value)) return;
        
        const batchName = sel.options[sel.selectedIndex].text;
        const courseName = courseSel.options[courseSel.selectedIndex].text;
        
        selectedBatches.push({ id: sel.value, name: batchName, course: courseName });
        renderBatchChips();
        sel.selectedIndex = 0;
    };
    
    function renderBatchChips() {
        const container = document.getElementById('batchChips_' + CID);
        container.innerHTML = selectedBatches.map((b, idx) => `
            <div class="chip">
                <span>${b.course} › ${b.name}</span>
                <i class="fas fa-times-circle" onclick="removeBatchChip_${CID}(${idx})"></i>
            </div>
        `).join('');
    }
    
    window['removeBatchChip_' + CID] = function(idx) {
        selectedBatches.splice(idx, 1);
        renderBatchChips();
    };

    function escJsAdm(str) {
        return (str || '').replace(/'/g, "\\'");
    }

    // ── Course & Batch Loading ──
    const courseEl = document.getElementById('<?= $selCourseId ?>');
    const batchEl  = document.getElementById('<?= $selBatchId ?>');

    // PHP-server-rendered fallback data (always available, no extra request)
    const PHP_COURSES = <?= json_encode($courses ?? []) ?>;
    const PHP_BATCHES = <?= json_encode($batches ?? []) ?>;

    /**
     * initCourseBatchSelects()
     * Called immediately (no DOMContentLoaded wrapper) because:
     * - In SPA mode, the script is eval()'d AFTER the DOM is injected, so
     *   DOMContentLoaded has already fired — the callback would never run.
     * - In full-page mode, the script runs after the HTML, so the elements
     *   are already in the DOM.
     */
    function initCourseBatchSelects() {
        if (!courseEl) return;

        if (window.NexusDataLoader) {
            // NexusDataLoader auto-detects role via window._userRole
            // and calls /api/frontdesk/courses OR /api/admin/courses accordingly
            NexusDataLoader.loadCourses(courseEl).then(function(courses) {
                // If API returned empty, fall back to PHP data
                if (courses.length === 0 && PHP_COURSES.length > 0) {
                    populateSelectFromArray(courseEl, PHP_COURSES, 'Select Course');
                }
            });

            courseEl.addEventListener('change', function() {
                if (!this.value) {
                    batchEl.innerHTML = '<option value="">— Select course first —</option>';
                    batchEl.disabled  = true;
                    return;
                }
                batchEl.innerHTML = '<option value="">Loading batches...</option>';
                batchEl.disabled  = true;
                NexusDataLoader.loadBatches(this.value, batchEl).then(function(batches) {
                    if (batches.length === 0) {
                        // Fallback to PHP batch data filtered by course_id
                        const matches = PHP_BATCHES.filter(b => b.course_id == courseEl.value);
                        if (matches.length > 0) {
                            batchEl.innerHTML = '<option value="">Select Batch</option>';
                            matches.forEach(b => {
                                batchEl.innerHTML += `<option value="${b.id}">${b.name}${b.shift ? ' (' + b.shift + ')' : ''}</option>`;
                            });
                            batchEl.disabled = false;
                        }
                    }
                });
            });

        } else {
            // NexusDataLoader not available — use PHP data directly
            populateSelectFromArray(courseEl, PHP_COURSES, 'Select Course');

            courseEl.addEventListener('change', function() {
                const courseId = this.value;
                batchEl.disabled  = true;
                batchEl.innerHTML = '<option value="">— Choose Batch —</option>';
                const matches = PHP_BATCHES.filter(b => b.course_id == courseId);
                if (matches.length) {
                    matches.forEach(b => {
                        const o = document.createElement('option');
                        o.value = b.id;
                        o.textContent = b.name + (b.shift ? ' (' + b.shift + ')' : '');
                        batchEl.appendChild(o);
                    });
                    batchEl.disabled = false;
                } else {
                    batchEl.innerHTML = '<option value="">No batches for this course</option>';
                }
            });
        }
    }

    function populateSelectFromArray(selectEl, items, placeholder) {
        selectEl.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
        items.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.name + (c.code ? ' (' + c.code + ')' : '');
            selectEl.appendChild(o);
        });
        selectEl.disabled = items.length === 0;
    }

    // Run immediately — works in both full-page and SPA (eval) contexts
    initCourseBatchSelects();

    // ── Pre-set Initial Mode ──
    const urlParams = new URLSearchParams(window.location.search);
    const preSelectedStuId = urlParams.get('student_id');

    if (admissionMode === 'existing') {
        setTimeout(async () => {
            await window['setAdmissionMode_' + CID]('existing');
            if (preSelectedStuId) {
                // Wait for dropdown to populate before selecting
                let attempts = 0;
                const checkAndSelect = setInterval(() => {
                    const sel = document.getElementById('stuSelect_' + CID);
                    if (sel && sel.options.length > 1) {
                        sel.value = preSelectedStuId;
                        window['onStudentDropdownChange_' + CID](preSelectedStuId);
                        clearInterval(checkAndSelect);
                    }
                    if (++attempts > 50) clearInterval(checkAndSelect); // Timeout after 5s
                }, 100);
            }
        }, 50);
    } else {
        setTimeout(() => window['setAdmissionMode_' + CID]('new'), 50);
    }

    // ── DOB Sync (AD → BS) ──
    window['handleDobSync_' + CID] = async function(val) {
        if (!val || val.length < 10) return;
        try {
            const res  = await fetch(`${window.APP_URL}/api/admin/date-convert?date=${encodeURIComponent(val)}&type=bs-to-ad`);
            const data = await res.json();
            const adEl = document.getElementById('inpDobAd_' + CID);
            if (adEl && data.success && data.date) {
                adEl.value = data.date;
                console.log('[Admission] Sync OK:', data.date);
            }
        } catch(err) {
            console.warn('[AdmissionForm] DOB conversion failed:', err);
        }
    };

    // ── Password Toggle ──
    window['togglePassView_' + CID] = function(id) {
        const inp = document.getElementById(id);
        const ico = document.getElementById(id + 'Eye');
        if (!inp || !ico) return;
        const isPass = inp.type === 'password';
        inp.type      = isPass ? 'text' : 'password';
        ico.className = isPass ? 'fas fa-eye-slash' : 'fas fa-eye';
    };

    // ── Validation Helpers ──
    function validateAdmForm_CID(_f) {
        let isValid = true;
        const requiredFields = _f.querySelectorAll('[required]');
        
        // Custom Check for Batches
        if (admissionMode === 'new' && selectedBatches.length === 0) {
            const batchGrp = document.getElementById('<?= $selBatchId ?>').closest('.f-grp');
            if (batchGrp) batchGrp.classList.add('error');
            isValid = false;
        }

        requiredFields.forEach(field => {
            const grp = field.closest('.f-grp');
            if (grp && grp.classList.contains('hidden-section')) return; // Skip hidden
            
            let fieldValid = true;
            if (field.tagName === 'SELECT') {
                if (!field.value) fieldValid = false;
            } else if (field.type === 'tel') {
                const telRegex = /^[0-9]{10}$/;
                if (!telRegex.test(field.value)) fieldValid = false;
            } else if (field.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value)) fieldValid = false;
            } else if (field.minlength && field.value.length < field.minlength) {
                fieldValid = false;
            } else {
                if (!field.value.trim()) fieldValid = false;
            }

            if (!fieldValid) {
                if (grp) grp.classList.add('error');
                isValid = false;
            } else {
                if (grp) grp.classList.remove('error');
            }
        });

        // Live reset on input
        _f.querySelectorAll('.fi').forEach(fi => {
            ['input', 'change'].forEach(evt => {
                fi.addEventListener(evt, () => {
                    const g = fi.closest('.f-grp');
                    if (g) g.classList.remove('error');
                });
            });
        });

        return isValid;
    }

    // ── Form Submit ──
    window['handleAdmissionSubmit_' + CID] = async function(e) {
        e.preventDefault();
        const _form = e.target;
        
        // 1. Run Custom Validation
        if (!validateAdmForm_CID(_form)) {
            const firstErr = _form.querySelector('.f-grp.error');
            if (firstErr) {
                firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // If it's a select or input, focus it
                const inp = firstErr.querySelector('.fi');
                if (inp) setTimeout(() => inp.focus(), 500);
            }
            return;
        }

        const btn = document.getElementById('<?= $btnId ?>');
        if (btn && btn.disabled) return;

        if (btn) { btn.disabled = true; }
        const oldBtnHTML = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Admission...';

        const payload = {
            student_id:             admissionMode === 'existing' ? (document.getElementById('valStuId_' + CID).value) : null,
            full_name:              admissionMode === 'new' ? (_form.full_name?.value     || '').trim() : null,
            contact_number:         admissionMode === 'new' ? (_form.contact_number?.value || '').trim() : null,
            email:                  admissionMode === 'new' ? (_form.email?.value          || '').trim() : null,
            password:               admissionMode === 'new' ? (_form.password?.value        || '') : null,
            batch_ids:              selectedBatches.map(b => b.id),
            batch_id:               selectedBatches.length > 0 ? selectedBatches[0].id : (_form.batch_id?.value || null),
            dob_bs:                 admissionMode === 'new' ? (_form.dob_bs?.value          || '') : null,
            dob_ad:                 admissionMode === 'new' ? (_form.dob_ad?.value          || '') : null,
            gender:                 admissionMode === 'new' ? (_form.gender?.value          || '') : null,
            permanent_address:      (admissionMode === 'new' && _form.permanent_address?.value.trim())
                                    ? JSON.stringify({ address: _form.permanent_address.value.trim() })
                                    : null
        };

        if (payload.batch_ids.length === 0 && !payload.batch_id) {
            Swal.fire('Selection Required', 'Please add at least one course/batch for enrollment.', 'warning');
            if (btn) btn.disabled = false;
            if (btn) btn.innerHTML = oldBtnHTML;
            return;
        }

        if (admissionMode === 'existing' && !payload.student_id) {
            Swal.fire('Selection Required', 'Please select an existing student.', 'warning');
            if (btn) btn.disabled = false;
            if (btn) btn.innerHTML = oldBtnHTML;
            return;
        }

        try {
            const res    = await fetch(API_ENDPOINT, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN || ''
                },
                body:    JSON.stringify(payload),
            });
            const result = await res.json();

            // Name for success message
            const displayName = admissionMode === 'existing' 
                ? document.getElementById('selStuName_' + CID).textContent 
                : payload.full_name;

            if (result.success) {
                let successHtml = `<p>Student <strong>${escHtmlAdm(displayName)}</strong> has been registered successfully.</p>`;
                
                if (admissionMode === 'new') {
                    successHtml += `
                    <div class="cred-box">
                        <div class="cred-title">Portal Credentials</div>
                        <div class="cred-line"><span>Email</span> <strong>${escHtmlAdm(payload.email)}</strong></div>
                        <div class="cred-line"><span>Password</span> <span class="cred-val">${escHtmlAdm(payload.password)}</span></div>
                    </div>`;
                }

                showAdmModal_CID('success', 'Admission Complete!',
                    successHtml,
                    [
                        { label: 'View Records', click: `window.location.href='${REDIRECT_URL}'`, style: 'background:linear-gradient(135deg,#00b894,#009e7e);color:#fff;' },
                        { label: 'Add Another', click: `location.reload()`, style: 'background:#f1f5f9;color:#1e293b;box-shadow:none;' }
                    ]
                );

            } else {
                if (window.Swal) {
                    Swal.fire({ title: 'Admission Failed', text: result.message || 'Unknown server error.', icon: 'error' });
                } else { alert('Admission Failed: ' + (result.message || 'Unknown error.')); }
            }
        } catch (err) {
            console.error('[AdmissionForm] Submit error:', err);
            if (window.Swal) {
                Swal.fire('Network Error', 'Could not connect to server. Please try again.', 'error');
            } else { alert('Network error. Try again.'); }
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = oldBtnHTML; }
        }
    };

    // Re-init selects after reset (called from "New Registration" modal button)
    window['initSelectsAdm_' + CID] = function() {
        if (courseEl) {
            courseEl.selectedIndex = 0;
        }
        if (batchEl) {
            batchEl.innerHTML = '<option value="">— Select course first —</option>';
            batchEl.disabled  = true;
        }
    };

    // ── Modal ──
    function showAdmModal_CID(type, title, html, actions) {
        const ov = document.getElementById('<?= $dialogId ?>');
        const cd = document.getElementById('<?= $scCardId ?>');
        const ic = document.getElementById('mIcon_<?= $componentId ?>');
        if (!ov || !ic) {
            // Fallback if modal elements missing
            if (window.Swal) Swal.fire(title, '', type === 'success' ? 'success' : 'error');
            return;
        }
        ic.style.background = type === 'success'
            ? 'linear-gradient(135deg,#00b894,#009e7e)'
            : 'linear-gradient(135deg,#ff7675,#e11d48)';
        ic.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'times'}"></i>`;
        document.getElementById('mTitle_<?= $componentId ?>').textContent = title;
        document.getElementById('mBody_<?= $componentId ?>').innerHTML    = html;
        document.getElementById('mActions_<?= $componentId ?>').innerHTML = actions.map(a =>
            `<button onclick="${a.click}" class="btn-p" style="height:52px;font-size:14px;${a.style}">${a.label}</button>`
        ).join('');
        ov.style.display = 'flex';
        // Force reflow and add active class for animations
        setTimeout(() => ov.classList.add('active'), 10);
    }

    window['closeAdmModal_CID'] = function() {
        const ov = document.getElementById('<?= $dialogId ?>');
        if (ov) ov.classList.remove('active');
        setTimeout(() => { if (ov) ov.style.display = 'none'; }, 400);
    };


    function escHtmlAdm(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})();
</script>

