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

/* Success Modal Modern */
.m-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px); z-index: 10000; align-items: center; justify-content: center; padding: 1rem; }
.m-card { background: #fff; border-radius: 32px; padding: 3rem; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5); transform: translateY(20px); opacity: 0; transition: var(--trans); }
.m-card.active { transform: translateY(0); opacity: 1; }
.m-ico { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #fff; }
</style>

<div class="pg">

    <!-- Standard Header with Breadcrumb Integration -->
    <div class="pg-head" style="background: none; border: none; padding: 0; margin-bottom: clamp(1rem, 3dvh, 1.5rem);">
        <div class="pg-left">
            <div class="pg-ico"><i class="fas fa-user-plus"></i></div>
            <div>
                <h1 class="pg-title"><?= htmlspecialchars($pageTitle) ?></h1>
               
            </div>
        </div>
       
    </div>

    <form id="<?= $formId ?>" onsubmit="handleAdmissionSubmit_<?= $componentId ?>(event)">



        <!-- Section 1: Academic High Priority -->
        <div class="sc-adm callout-p">
            <h3 class="sc-title"><i class="fas fa-book-reader"></i> Academic Placement</h3>
            <div class="grid-box grid-2">
                <div class="f-grp">
                    <label class="f-lbl req">Target Course</label>
                    <div class="ipt-box">
                        <i class="fas fa-award"></i>
                        <select name="course_id" id="<?= $selCourseId ?>" class="fi fi-sel" required
                                data-fallback='<?= $coursesFallbackJson ?>'>
                            <option value="" disabled selected>⏳ Loading courses...</option>
                        </select>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl req">Assigned Batch</label>
                    <div class="ipt-box">
                        <i class="fas fa-clock"></i>
                        <select name="batch_id" id="<?= $selBatchId ?>" class="fi fi-sel" required disabled>
                            <option value="">— Select course first —</option>
                        </select>
                    </div>
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
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl req">Contact Number</label>
                    <div class="ipt-box">
                        <i class="fas fa-mobile-screen-button"></i>
                        <input type="tel" name="contact_number" class="fi" placeholder="98XXXXXXXX" pattern="[0-9]{10}" required>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl req">Email Address</label>
                    <div class="ipt-box">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="fi" placeholder="email@example.com" required>
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Detailed Information -->
        <div class="sc-adm">
            <h3 class="sc-title"><i class="fas fa-folder-open"></i> Background Details</h3>
            <div class="grid-box grid-3">
                <div class="f-grp">
                    <label class="f-lbl req">DOB (AD)</label>
                    <input type="date" name="dob_ad" class="fi"
                           onchange="handleDobSync_<?= $componentId ?>(this.value)"
                           style="padding-left: 20px;" required>
                </div>
                <div class="f-grp">
                    <label class="f-lbl">DOB (BS)</label>
                    <input type="text" name="dob_bs" id="<?= $dobBsId ?>" class="fi" placeholder="YYYY-MM-DD" style="padding-left: 20px;">
                </div>
                <div class="f-grp">
                    <label class="f-lbl">Blood Group</label>
                    <select name="blood_group" class="fi fi-sel" style="padding-left: 20px;">
                        <option value="">Maybe Later</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                        <option value="<?= $bg ?>"><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-box grid-2" style="margin-top: 1.5rem;">
                <div class="f-grp">
                    <label class="f-lbl req">Guardian/Father Name</label>
                    <div class="ipt-box">
                        <i class="fas fa-user-shield"></i>
                        <input type="text" name="father_name" class="fi" placeholder="Full name of guardian" required>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl">Citizenship No.</label>
                    <div class="ipt-box">
                        <i class="fas fa-passport"></i>
                        <input type="text" name="citizenship_no" class="fi" placeholder="ID Number (Optional)">
                    </div>
                </div>
            </div>

            <div class="grid-box grid-2" style="margin-top: 1.5rem;">
                <div class="f-grp">
                    <label class="f-lbl req">Permanent Address</label>
                    <textarea name="permanent_address" class="fi" style="padding-left: 16px; min-height: 100px;" placeholder="Full Address..." required></textarea>
                </div>
                <div class="f-grp">
                    <label class="f-lbl">Temporary Address</label>
                    <textarea name="temporary_address" class="fi" style="padding-left: 16px; min-height: 100px;" placeholder="Current Stay..."></textarea>
                </div>
            </div>

            <div class="f-grp" style="margin-top: 1.5rem;">
                <label class="f-lbl">Past Academic Qualification</label>
                <textarea name="academic_qualification" class="fi" style="padding-left: 16px; min-height: 100px;" placeholder="Previous degrees, years, and institutions..."></textarea>
            </div>
        </div>

        <!-- Section 4: Security Callout -->
        <div class="sc-adm callout-p callout-s">
            <h3 class="sc-title" style="color: #e11d48;"><i class="fas fa-key"></i> System Access</h3>
            <p style="font-size: 12px; color: #be123c; margin: -1rem 0 1.5rem 3.5rem; font-weight: 600;">Standard student portal credentials</p>
            <div class="grid-box">
                <div class="f-grp">
                    <label class="f-lbl req">Access Password</label>
                    <div class="ipt-box">
                        <i class="fas fa-user-lock"></i>
                        <input type="password" name="password" id="<?= $passId ?>" class="fi" placeholder="e.g. Student@123" minlength="8" required>
                        <span onclick="togglePassView_<?= $componentId ?>('<?= $passId ?>')" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer; padding: 5px; z-index:2;">
                            <i class="fas fa-eye" id="<?= $passId ?>Eye"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Final Submit -->
        <div style="margin: 4rem 0;">
            <button type="submit" id="<?= $btnId ?>" class="btn-p">
                <i class="fas fa-rocket"></i> FINALISE &amp; SUBMIT ADMISSION
            </button>
            <p style="text-align: center; font-size: 12px; color: #64748b; margin-top: 1.5rem; font-weight: 600;">
                <i class="fas fa-info-circle"></i> This will generate a unique Student Roll Number automatically.
            </p>
        </div>
    </form>
</div>

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
    const CID          = '<?= $componentId ?>';
    const API_ENDPOINT = '<?= addslashes($apiEndpoint) ?>';
    const REDIRECT_URL = '<?= addslashes($successRedirectUrl) ?>';

    <?php
    $user     = $_SESSION['userData'] ?? [];
    $userRole = strtolower($user['role'] ?? '');
    echo "window.currentUser = window.currentUser || " . json_encode($user) . ";\n";
    echo "window.APP_URL     = window.APP_URL     || '" . APP_URL . "';\n";
    // Set _userRole so NexusDataLoader._getApiBase() picks the right API path
    echo "window._userRole   = window._userRole   || '" . addslashes($userRole) . "';\n";
    ?>

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

    // ── DOB Sync (AD → BS) ──
    window['handleDobSync_' + CID] = async function(val) {
        if (!val || val.length < 10) return;
        try {
            const res  = await fetch(`${window.APP_URL}/api/admin/date-convert?date=${encodeURIComponent(val)}&type=ad-to-bs`);
            const data = await res.json();
            const bsEl = document.getElementById('<?= $dobBsId ?>');
            if (bsEl && data.success && data.date) {
                bsEl.value = data.date;
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

    // ── Form Submit ──
    window['handleAdmissionSubmit_' + CID] = async function(e) {
        e.preventDefault();
        const btn = document.getElementById('<?= $btnId ?>');
        if (btn && btn.disabled) return;

        const form = e.target;

        if (btn) { btn.disabled = true; }
        const oldBtnHTML = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Admission...';

        const payload = {
            full_name:              (form.full_name?.value     || '').trim(),
            contact_number:         (form.contact_number?.value || '').trim(),
            email:                  (form.email?.value          || '').trim(),
            password:               form.password?.value        || '',
            course_id:              form.course_id?.value       || null,
            batch_id:               form.batch_id?.value        || null,
            dob_ad:                 form.dob_ad?.value          || '',
            dob_bs:                 form.dob_bs?.value          || '',
            gender:                 form.gender?.value          || '',
            blood_group:            form.blood_group?.value     || '',
            father_name:            (form.father_name?.value    || '').trim(),
            citizenship_no:         (form.citizenship_no?.value || '').trim(),
            permanent_address:      form.permanent_address?.value.trim()
                                    ? JSON.stringify({ address: form.permanent_address.value.trim() })
                                    : null,
            temporary_address:      form.temporary_address?.value.trim()
                                    ? JSON.stringify({ address: form.temporary_address.value.trim() })
                                    : null,
            academic_qualification: (form.academic_qualification?.value || '').trim(),
            registration_status:    'fully_registered'
        };

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

            if (result.success) {
                showAdmModal_CID('success', 'Admission Complete!',
                    `<p>Student <strong>${escHtmlAdm(payload.full_name)}</strong> has been registered successfully.</p>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:18px;padding:1.5rem;text-align:left;margin-top:1.5rem;">
                        <p style="font-size:11px;font-weight:700;color:var(--p);margin-bottom:8px;letter-spacing:0.05em;">PORTAL CREDENTIALS</p>
                        <div style="margin-bottom:6px;font-size:14px;"><span style="opacity:0.6;width:90px;display:inline-block;">Email:</span> <strong>${escHtmlAdm(payload.email)}</strong></div>
                        <div style="font-size:14px;"><span style="opacity:0.6;width:90px;display:inline-block;">Password:</span> <span style="font-family:monospace;background:#fff;padding:2px 8px;border-radius:6px;border:1px solid #ddd;">${escHtmlAdm(payload.password)}</span></div>
                    </div>`,
                    [
                        { label: 'View Students', click: `window.location.href='${REDIRECT_URL}'`, style: 'background:linear-gradient(135deg,#00b894,#009e7e);color:#fff;' },
                        { label: 'New Registration', click: `closeAdmModal_CID();document.getElementById('${form.id}').reset();initSelectsAdm_${CID}();`, style: 'background:#f1f5f9;color:#1e293b;box-shadow:none;' }
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
        setTimeout(() => cd && cd.classList.add('active'), 10);
    }

    window['closeAdmModal_CID'] = function() {
        const cd = document.getElementById('<?= $scCardId ?>');
        const ov = document.getElementById('<?= $dialogId ?>');
        if (cd) cd.classList.remove('active');
        setTimeout(() => { if (ov) ov.style.display = 'none'; }, 300);
    };

    function escHtmlAdm(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

})();
</script>

