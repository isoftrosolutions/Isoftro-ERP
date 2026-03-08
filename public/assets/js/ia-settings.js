/**
 * Hamro ERP — ia-settings.js
 * Institute Profile / Settings page
 */
window.renderInstituteProfile = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Institute Profile</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-building-circle-check"></i></div><div><div class="pg-title">Institute Identity</div><div class="pg-sub">Manage your organization's public profile and branding</div></div></div></div>
        
        <div style="max-width:1100px;margin:0 auto;">
            <div class="tabs" style="display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #e2e8f0;padding-bottom:10px;">
                <button class="btn bs" onclick="renderInstituteProfile()">Profile</button>
                <button class="btn bt-sm" onclick="renderSecuritySettings()">Security & 2FA</button>
            </div>
            
            <div id="profileLoadingSpinner" style="text-align:center;padding:100px;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:3rem;color:var(--teal)"></i><p style="margin-top:15px;color:var(--tl)">Fetching organization data...</p></div>
            <!-- ... rest of the form ... -->
                <div style="display:grid;grid-template-columns:350px 1fr;gap:30px;align-items:start;">
                    <!-- LEFT COLUMN: Branding & Logo -->
                    <div style="display:grid;gap:25px;">
                        <div class="card" style="padding:30px;text-align:center;">
                            <div class="sc-lbl" style="padding-left:0;margin-bottom:20px;text-align:left;">Brand Assets</div>
                            <div id="logoPreviewWrap" style="width:140px;height:140px;border-radius:24px;border:2px dashed #e2e8f0;overflow:hidden;margin:0 auto 20px;background:#f8fafc;display:flex;align-items:center;justify-content:center;position:relative;transition:all 0.3s ease;">
                                <img id="logoPreview" src="" alt="Logo" style="width:100%;height:100%;object-fit:contain;display:none;padding:15px;">
                                <div id="logoPlaceholder" style="text-align:center;">
                                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:2rem;color:#cbd5e1;"></i>
                                    <div style="font-size:10px;color:#94a3b8;margin-top:8px;font-weight:700;text-transform:uppercase;">Upload Logo</div>
                                </div>
                                <label style="position:absolute;top:0;left:0;width:100%;height:100%;cursor:pointer;">
                                    <input type="file" name="logo" accept="image/*" style="display:none;" onchange="previewLogo(this)">
                                </label>
                            </div>
                            <p style="font-size:11px;color:#94a3b8;margin-bottom:20px;">Recommended: 512x512px PNG or SVG</p>
                            
                            <hr style="border:none;border-top:1px solid #f1f5f9;margin:20px 0;">
                            
                            <div style="text-align:left;">
                                <label class="form-label">Primary Brand Color</label>
                                <div style="display:flex;gap:12px;align-items:center;">
                                    <input type="color" name="brand_color" id="profBrandColor" value="#006D44" style="width:50px;height:50px;padding:2px;border-radius:12px;border:1px solid #e2e8f0;cursor:pointer;">
                                    <input type="text" id="profBrandColorHex" class="form-control" style="font-family:monospace;text-transform:uppercase;" placeholder="#006D44">
                                </div>
                                <small style="display:block;margin-top:8px;color:#64748b;font-size:11px;">This color defines your institute's UI theme across the portal.</small>
                            </div>
                        </div>
                        
                        <div class="card" style="padding:25px;background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%);color:#fff;">
                            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;opacity:0.6;margin-bottom:15px;">Subscription</div>
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <h3 id="profPlan" style="margin:0;color:var(--teal-lt);">Enterprise</h3>
                                <div class="bdg" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);">Active</div>
                            </div>
                            <button type="button" class="btn bs fu bt-sm" style="margin-top:20px;background:rgba(255,255,255,0.05);color:#fff;border:1px solid rgba(255,255,255,0.1);" onclick="goNav('settings','billing')">Billing & Limits</button>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Details -->
                    <div class="card" style="padding:40px;">
                        <div class="sc-lbl mb" style="padding-left:0;border-bottom:1px solid #f1f5f9;padding-bottom:15px;margin-bottom:25px;">Organization Details</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">
                            <div class="form-group" style="grid-column:span 2;">
                                <label class="form-label">Institute Full Name (English) *</label>
                                <input type="text" name="name" id="profName" class="form-control form-control-lg" required placeholder="e.g. Bright Future Secondary School">
                            </div>
                            <div class="form-group" style="grid-column:span 2;">
                                <label class="form-label">Institute Name (Nepali)</label>
                                <input type="text" name="nepali_name" id="profNameNep" class="form-control" placeholder="उदा. उज्वल भविष्य माध्यमिक विद्यालय">
                            </div>
                            <div class="form-group" style="grid-column:span 2;">
                                <label class="form-label">Tagline / Motto</label>
                                <input type="text" name="tagline" id="profTagline" class="form-control" placeholder="e.g. Leading the way in digital education">
                            </div>
                            
                            <div class="sc-lbl mb" style="grid-column:span 2;padding-left:0;border-bottom:1px solid #f1f5f9;padding-bottom:15px;margin:20px 0 5px;">Contact & Location</div>
                            
                            <div class="form-group">
                                <label class="form-label">Public Email Address</label>
                                <input type="email" name="email" id="profEmail" class="form-control" placeholder="info@institute.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Phone Number</label>
                                <input type="text" name="phone" id="profPhone" class="form-control" placeholder="+977-01XXXXXX">
                            </div>
                            <div class="form-group" style="grid-column:span 2;">
                                <label class="form-label">Street Address</label>
                                <input type="text" name="address" id="profAddress" class="form-control" placeholder="Street, Ward, City/Municipality">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Province</label>
                                <select name="province" id="profProvince" class="form-control">
                                    <option value="">Select Province</option>
                                    <option value="1">Koshi Province</option>
                                    <option value="2">Madhesh Province</option>
                                    <option value="3">Bagmati Province</option>
                                    <option value="4">Gandaki Province</option>
                                    <option value="5">Lumbini Province</option>
                                    <option value="6">Karnali Province</option>
                                    <option value="7">Sudurpashchim Province</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Registration No. / PAN</label>
                                <input type="text" name="pan_no" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                        
                        <div style="margin-top:40px;display:flex;justify-content:flex-end;gap:15px;border-top:1px solid #f1f5f9;padding-top:30px;">
                            <button type="submit" class="btn bt bt-lg" style="padding:12px 35px;"><i class="fa-solid fa-save"></i> Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>`;
    
    // Sync color inputs
    const cp = document.getElementById('profBrandColor');
    const ct = document.getElementById('profBrandColorHex');
    if(cp && ct) {
        cp.oninput = (e) => ct.value = e.target.value.toUpperCase();
        ct.oninput = (e) => { if(/^#[0-9A-F]{6}$/i.test(e.target.value)) cp.value = e.target.value; };
    }

    await _loadInstituteProfile();
    document.getElementById('instituteProfileForm').onsubmit = _saveInstituteProfile;
};

async function _loadInstituteProfile() {
    try {
        const res = await fetch(APP_URL + '/api/admin/profile?type=institute');
        const r = await res.json();
        if (r.success && r.data) {
            const p = r.data;
            document.getElementById('profName').value    = p.name||'';
            document.getElementById('profNameNep').value = p.nepali_name||'';
            document.getElementById('profPhone').value   = p.phone||'';
            document.getElementById('profEmail').value   = p.email||'';
            document.getElementById('profAddress').value = p.address||'';
            document.getElementById('profTagline').value = p.tagline||'';
            document.getElementById('profPlan').innerText = (p.plan||'Starter').toUpperCase();
            if (p.province) document.getElementById('profProvince').value = p.province;
            
            if (p.brand_color) {
                document.getElementById('profBrandColor').value = p.brand_color;
                document.getElementById('profBrandColorHex').value = p.brand_color.toUpperCase();
            }

            if (p.logo_url) {
                const img = document.getElementById('logoPreview');
                img.src = p.logo_url;
                img.style.display = 'block';
                document.getElementById('logoPlaceholder').style.display = 'none';
                document.getElementById('logoPreviewWrap').style.borderStyle = 'solid';
            }
        }
    } catch(e) { console.warn('Profile load error',e); }
    finally {
        document.getElementById('profileLoadingSpinner').style.display='none';
        document.getElementById('instituteProfileForm').style.display='block';
    }
}

async function _saveInstituteProfile(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const btn = form.querySelector('button[type="submit"]'); const orig=btn.innerHTML;
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
    try {
        const res = await fetch(APP_URL+'/api/admin/profile?type=institute', { method:'POST', body:formData });
        const result = await res.json();
        if (result.success) {
            Swal.fire({
                title: 'Saved!',
                text: 'Institute profile updated successfully. Some changes might require a page refresh.',
                icon: 'success',
                confirmButtonColor: 'var(--teal)'
            }).then(() => {
                // Refresh header if name changed
                if (window._refreshHeaderInfo) _refreshHeaderInfo();
            });
        }
        else throw new Error(result.message);
    } catch(err) { Swal.fire('Error', err.message, 'error'); }
    finally { btn.disabled=false; btn.innerHTML=orig; }
}

window.previewLogo = function(input) {
    const file = input.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('logoPreview');
        img.src = e.target.result;
        img.style.display = 'block';
        document.getElementById('logoPlaceholder').style.display = 'none';
        document.getElementById('logoPreviewWrap').style.borderStyle = 'solid';
    };
    reader.readAsDataURL(file);
};
window.renderEmailSettings = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Email Notifications</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#4F46E5,#6366F1);"><i class="fa-solid fa-envelope-circle-check"></i></div>
                <div><div class="pg-title">Email Notifications</div><div class="pg-sub">Configure how students receive their welcome emails</div></div>
            </div>
        </div>

        <div style="max-width:680px;margin:0 auto;">

            <!-- System Managed Banner -->
            <div style="background:linear-gradient(135deg,#EFF6FF,#EDE9FE);border:1px solid #C7D2FE;border-radius:14px;padding:20px 24px;margin-bottom:24px;display:flex;gap:16px;align-items:center;">
                <div style="width:48px;height:48px;background:linear-gradient(135deg,#4F46E5,#6366F1);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-shield-halved" style="color:#fff;font-size:18px;"></i>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#3730A3;margin-bottom:4px;">Powered by Hamro ERP Mail System</div>
                    <div style="font-size:12px;color:#4F46E5;line-height:1.5;">Emails are sent securely through our platform. You don't need to configure any SMTP settings — just enter your sender name and the email address where students can reply to you.</div>
                </div>
            </div>

            <div class="card" style="padding:32px;">
                <div id="emailFormLoading" style="text-align:center;padding:30px;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:#4F46E5"></i><p style="margin-top:10px;color:#64748b;">Loading settings...</p></div>

                <form id="emailSettingsForm" style="display:none;">

                    <div class="form-group" style="margin-bottom:24px;">
                        <label class="form-label" style="font-size:14px;font-weight:700;color:#1e293b;">
                            <i class="fa-solid fa-user-tie" style="color:#4F46E5;margin-right:6px;"></i>
                            Sender Display Name <span style="color:#ef4444">*</span>
                        </label>
                        <input type="text" name="sender_name" id="emSenderName" class="form-control"
                               placeholder="e.g. Hamro Loksewa Institute" required
                               style="font-size:15px;padding:12px 16px;">
                        <div style="font-size:12px;color:#64748b;margin-top:6px;">
                            This name will appear in the <strong>"From"</strong> field of every student email.
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:24px;">
                        <label class="form-label" style="font-size:14px;font-weight:700;color:#1e293b;">
                            <i class="fa-solid fa-reply" style="color:#4F46E5;margin-right:6px;"></i>
                            Your Institute Email <span style="font-size:11px;color:#94a3b8;">(Optional — for replies)</span>
                        </label>
                        <input type="email" name="reply_to_email" id="emReplyTo" class="form-control"
                               placeholder="e.g. info@mymyinstitute.com"
                               style="font-size:15px;padding:12px 16px;">
                        <div style="font-size:12px;color:#64748b;margin-top:6px;">
                            When a student replies to their welcome email, it will go to this address.
                        </div>
                    </div>

                    <!-- Enable toggle -->
                    <div style="background:#F8FAFF;border:1.5px solid #C7D2FE;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1e293b;">Enable Student Email Notifications</div>
                            <div style="font-size:12px;color:#64748b;margin-top:2px;">Send login credentials automatically when a student is registered</div>
                        </div>
                        <label style="position:relative;display:inline-block;width:48px;height:26px;cursor:pointer;">
                            <input type="checkbox" name="is_active" id="emActive" value="1" style="opacity:0;width:0;height:0;">
                            <span id="emToggleTrack" style="position:absolute;inset:0;background:#e2e8f0;border-radius:13px;transition:0.3s;"></span>
                            <span id="emToggleThumb" style="position:absolute;left:3px;top:3px;width:20px;height:20px;background:#fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,0.2);transition:0.3s;"></span>
                        </label>
                    </div>

                    <!-- Preview card -->
                    <div id="emailPreviewCard" style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:1px;margin-bottom:10px;">Email Preview</div>
                        <div style="font-size:12px;color:#374151;">
                            <div style="margin-bottom:4px;"><span style="color:#94a3b8;width:60px;display:inline-block;">From:</span> <strong id="pvFromName">Your Institute Name</strong> &lt;noreply@hamroerp.com&gt;</div>
                            <div style="margin-bottom:4px;"><span style="color:#94a3b8;width:60px;display:inline-block;">Reply-To:</span> <span id="pvReplyTo" style="color:#4F46E5;">—</span></div>
                            <div><span style="color:#94a3b8;width:60px;display:inline-block;">Subject:</span> Welcome to <span id="pvSubjectInst">Your Institute Name</span> — Your Student Account Details</div>
                        </div>
                    </div>

                    <div style="display:flex;gap:12px;">
                        <button type="button" class="btn bs" onclick="testEmailSend()" style="flex:1;">
                            <i class="fa-solid fa-paper-plane"></i> Send Test Email
                        </button>
                        <button type="submit" class="btn bt" style="flex:2;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;border:none;">
                            <i class="fa-solid fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>`;

    await _loadEmailSettings();

    const form = document.getElementById('emailSettingsForm');
    form.onsubmit = _saveEmailSettings;

    // Live preview sync
    const nameInput    = document.getElementById('emSenderName');
    const replyInput   = document.getElementById('emReplyTo');
    const pvFrom       = document.getElementById('pvFromName');
    const pvReply      = document.getElementById('pvReplyTo');
    const pvSubjInst   = document.getElementById('pvSubjectInst');
    const toggle       = document.getElementById('emActive');
    const track        = document.getElementById('emToggleTrack');
    const thumb        = document.getElementById('emToggleThumb');

    const syncToggle = () => {
        track.style.background = toggle.checked ? '#4F46E5' : '#e2e8f0';
        thumb.style.left       = toggle.checked ? '25px'   : '3px';
    };
    toggle.addEventListener('change', syncToggle);
    syncToggle();

    nameInput.addEventListener('input', () => {
        const v = nameInput.value || 'Your Institute Name';
        pvFrom.textContent    = v;
        pvSubjInst.textContent = v;
    });
    replyInput.addEventListener('input', () => {
        pvReply.textContent = replyInput.value || '—';
    });
};

async function _loadEmailSettings() {
    try {
        const res = await fetch(APP_URL + '/api/admin/email-settings');
        const data = await res.json();
        if (data.success && data.data) {
            const e = data.data;
            const sn = document.getElementById('emSenderName');
            const rt = document.getElementById('emReplyTo');
            const ac = document.getElementById('emActive');
            const track = document.getElementById('emToggleTrack');
            const thumb = document.getElementById('emToggleThumb');
            if (sn) sn.value = e.sender_name || e.from_name || '';
            if (rt) rt.value = e.reply_to_email || e.from_email || '';
            if (ac) { ac.checked = e.is_active == 1; track.style.background = ac.checked ? '#4F46E5' : '#e2e8f0'; thumb.style.left = ac.checked ? '25px' : '3px'; }
            // Sync preview
            const pv = document.getElementById('pvFromName');
            const pr = document.getElementById('pvReplyTo');
            const ps = document.getElementById('pvSubjectInst');
            if (pv && sn?.value) pv.textContent = sn.value;
            if (pr && rt?.value) pr.textContent = rt.value;
            if (ps && sn?.value) ps.textContent = sn.value;
        }
    } catch(err) { console.warn('Email config load failed', err); }
    finally {
        document.getElementById('emailFormLoading').style.display = 'none';
        document.getElementById('emailSettingsForm').style.display = 'block';
    }
}

async function _saveEmailSettings(ev) {
    ev.preventDefault();
    const btn = ev.target.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';

    const fd = new FormData(ev.target);
    if (!fd.has('is_active')) fd.append('is_active', '0');

    try {
        const res = await fetch(APP_URL + '/api/admin/email-settings', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Email notification settings updated. Students will now receive login credentials automatically.',
                confirmButtonColor: '#4F46E5'
            });
        } else throw new Error(result.message);
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = orig; }
}

window.testEmailSend = async function() {
    const { value: testEmail } = await Swal.fire({
        title: 'Send Test Email',
        html: '<p style="font-size:13px;color:#64748b;margin-bottom:12px;">We will send a sample student welcome email to this address so you can preview exactly what your students will receive.</p>',
        input: 'email',
        inputPlaceholder: 'Enter your email address',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-paper-plane"></i> Send',
        confirmButtonColor: '#4F46E5'
    });

    if (testEmail) {
        Swal.fire({ title: 'Sending...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        try {
            const fd = new FormData();
            fd.append('test_email', testEmail);
            fd.append('sender_name', document.getElementById('emSenderName')?.value || 'Hamro ERP');
            const res = await fetch(APP_URL + '/api/admin/email-settings/test', { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Test Email Sent!',
                    html: `<p>Check <strong>${testEmail}</strong> for your welcome email preview.<br><small style="color:#94a3b8;">Check your spam folder if it doesn't appear within a minute.</small></p>`,
                    confirmButtonColor: '#4F46E5'
                });
            } else throw new Error(result.message);
        } catch(e) { Swal.fire('Failed to Send', e.message, 'error'); }
    }
};

async function _loadEmailSettings() {
    try {
        const res = await fetch(APP_URL + '/api/admin/email-settings');
        const data = await res.json();
        if (data.success && data.data) {
            const e = data.data;
            document.getElementById('emHost').value = e.smtp_host || '';
            document.getElementById('emPort').value = e.smtp_port || 587;
            document.getElementById('emEnc').value = e.smtp_encryption || 'tls';
            document.getElementById('emUser').value = e.smtp_user || '';
            document.getElementById('emPass').value = e.smtp_pass || '';
            document.getElementById('emFrom').value = e.from_email || '';
            document.getElementById('emFromName').value = e.from_name || '';
            document.getElementById('emActive').checked = e.is_active == 1;
        }
    } catch(err) { console.warn('Email config load failed', err); }
    finally {
        document.getElementById('emailFormLoading').style.display = 'none';
        document.getElementById('emailSettingsForm').style.display = 'block';
    }
}

async function _saveEmailSettings(ev) {
    ev.preventDefault();
    const btn = ev.target.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
    
    const fd = new FormData(ev.target);
    if (!fd.has('is_active')) fd.append('is_active', '0');

    try {
        const res = await fetch(APP_URL + '/api/admin/email-settings', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) Swal.fire('Updated!', 'Email settings saved successfully.', 'success');
        else throw new Error(result.message);
    } catch(e) { Swal.fire('Error', e.message, 'error'); }
    finally { btn.disabled = false; btn.innerHTML = orig; }
}

window.testEmailConnection = async function() {
    const { value: testEmail } = await Swal.fire({
        title: 'Test SMTP Connection',
        input: 'email',
        inputLabel: 'Enter email to send test message to',
        inputPlaceholder: 'someone@example.com',
        showCancelButton: true,
        confirmButtonText: 'Send Test Email',
        confirmButtonColor: 'var(--teal)'
    });

    if (testEmail) {
        Swal.fire({ title: 'Testing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        try {
            const fd = new FormData(document.getElementById('emailSettingsForm'));
            fd.append('test_email', testEmail);
            const res = await fetch(APP_URL + '/api/admin/email-settings/test', { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success) Swal.fire('Success!', 'Test email sent successfully. Please check your inbox.', 'success');
            else throw new Error(result.message);
        } catch(e) { Swal.fire('Connection Failed', e.message, 'error'); }
    }
};

/* ══════════════════════════════════════════════════════════════════
   MY PROFILE (USER)
══════════════════════════════════════════════════════════════════ */
window.renderUserProfile = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">My Profile</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-circle-user"></i></div><div><div class="pg-title">My Profile</div><div class="pg-sub">Manage your personal information and security</div></div></div></div>
        
        <div style="display:grid;grid-template-columns:300px 1fr;gap:30px;max-width:1100px;margin:0 auto;">
            <div class="card" style="text-align:center;padding:30px;">
                <div id="uavPreviewWrap" style="width:120px;height:120px;border-radius:50%;border:4px solid #fff;box-shadow:0 8px 20px rgba(0,0,0,0.1);overflow:hidden;margin:0 auto 20px;background:#f8fafc;display:flex;align-items:center;justify-content:center;">
                    <img id="uavPreview" src="" alt="Avatar" style="width:100%;height:100%;object-fit:cover;display:none;">
                    <i id="uavPlaceholder" class="fa-solid fa-user" style="font-size:3rem;color:#cbd5e1;"></i>
                </div>
                <h3 id="uDisplayName" style="margin:0 0 5px;">Admin</h3>
                <p id="uDisplayRole" style="font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Institute Admin</p>
                <hr style="margin:20px 0;border:none;border-top:1px solid #f1f5f9;">
                <label class="btn bs btn-sm" style="display:inline-block;cursor:pointer;">
                    <i class="fa-solid fa-camera"></i> Change Photo
                    <input type="file" name="avatar" accept="image/*" style="display:none;" onchange="_uavPreview(this)">
                </label>
            </div>

            <div class="card" style="padding:40px;">
                <form id="userProfileForm">
                    <div class="sc-lbl mb" style="padding-left:0;">Personal Details</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:30px;">
                        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" id="uName" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" id="uPhone" class="form-control"></div>
                        <div class="form-group" style="grid-column:span 2;"><label class="form-label">Email Address *</label><input type="email" name="email" id="uEmail" class="form-control" readonly style="background:#f1f5f9;"></div>
                    </div>

                    <div class="sc-lbl mb" style="padding-left:0;">Change Password</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current"></div>
                        <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password"></div>
                    </div>
                    
                    <div style="margin-top:40px;text-align:right;">
                        <button type="submit" class="btn bt"><i class="fa-solid fa-user-check"></i> Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>`;
    
    // Load existing data
    try {
        const res = await fetch(APP_URL + '/api/admin/profile?type=user');
        const data = await res.json();
        if (data.success && data.data) {
            const u = data.data;
            document.getElementById('uName').value = u.name || '';
            document.getElementById('uPhone').value = u.phone || '';
            document.getElementById('uEmail').value = u.email || '';
            document.getElementById('uDisplayName').innerText = u.name || 'Admin';
            if (u.avatar_url) {
                document.getElementById('uavPreview').src = u.avatar_url;
                document.getElementById('uavPreview').style.display = 'block';
                document.getElementById('uavPlaceholder').style.display = 'none';
            }
        }
    } catch(e) {}

    document.getElementById('userProfileForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        try {
            const res = await fetch(APP_URL + '/api/admin/profile?type=user', { method: 'POST', body: fd });
            const result = await res.json();
            if (result.success) Swal.fire('Success', 'Profile updated successfully', 'success');
            else throw new Error(result.message);
        } catch(err) { Swal.fire('Error', err.message, 'error'); }
    };
};

window._uavPreview = function(input) {
    const file = input.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('uavPreview').src = e.target.result;
        document.getElementById('uavPreview').style.display='block';
        document.getElementById('uavPlaceholder').style.display='none';
    };
    reader.readAsDataURL(file);
};

/* ══════════════════════════════════════════════════════════════════
   SUBSCRIPTION & BILLING
══════════════════════════════════════════════════════════════════ */
window.renderBillingSettings = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Subscription & Billing</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-credit-card"></i></div><div><div class="pg-title">Subscription & Billing</div><div class="pg-sub">Manage your plan, payments and usage limits</div></div></div></div>
        
        <div style="display:grid;grid-template-columns:1fr 350px;gap:30px;max-width:1200px;margin:0 auto;">
            <div>
                <div class="card mb" style="padding:40px;background:linear-gradient(135deg, #006D44 0%, #009E7E 100%);color:#fff;border-radius:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div>
                            <div style="font-size:12px;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;opacity:0.8;margin-bottom:10px;">Current Plan</div>
                            <h1 id="planName" style="margin:0;font-size:2.5rem;">PROFESSIONAL</h1>
                            <p id="planTagline" style="margin:10px 0 0;opacity:0.9;">Best for growing institutes with up to 1000 students.</p>
                        </div>
                        <div class="bdg" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.4);padding:8px 16px;font-size:14px;border-radius:12px;">ACTIVE</div>
                    </div>
                    <div style="margin-top:40px;display:flex;gap:30px;border-top:1px solid rgba(255,255,255,0.2);padding-top:30px;">
                        <div><div style="font-size:11px;opacity:0.7;text-transform:uppercase;margin-bottom:5px;">Renewal Date</div><div style="font-weight:700;font-size:1.1rem;">March 28, 2026</div></div>
                        <div><div style="font-size:11px;opacity:0.7;text-transform:uppercase;margin-bottom:5px;">Price</div><div style="font-weight:700;font-size:1.1rem;">NPR 7,500 / Month</div></div>
                    </div>
                </div>

                <div class="card" style="padding:30px;">
                    <div class="ct">Payment History</div>
                    <table class="tbl" style="margin-top:20px;">
                        <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead>
                        <tbody id="billingHistory">
                            <tr><td colspan="5" style="text-align:center;padding:40px;color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="card mb" style="padding:25px;">
                    <div class="ct">Usage Limits</div>
                    <div style="margin-top:20px;">
                        <div class="fg" style="margin-bottom:20px;">
                            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;"><span>Student Slots</span><strong id="limitStudents">0 / 500</strong></div>
                            <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;"><div id="barStudents" style="height:100%;background:var(--teal);width:0%;"></div></div>
                        </div>
                        <div class="fg" style="margin-bottom:20px;">
                            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;"><span>SMS Credits</span><strong id="limitSMS">0 / 500</strong></div>
                            <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;"><div id="barSMS" style="height:100%;background:#3b82f6;width:0%;"></div></div>
                        </div>
                        <div class="fg">
                            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px;"><span>Storage Used</span><strong id="limitStorage">1.2 GB / 10 GB</strong></div>
                            <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;"><div id="barStorage" style="height:100%;background:#8b5cf6;width:12%;"></div></div>
                        </div>
                    </div>
                    <button class="btn bt fu" style="margin-top:25px;">Upgrade Plan <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;margin-left:5px;"></i></button>
                    <button class="btn bs fu" style="margin-top:10px;">Buy More SMS Credits</button>
                </div>

                <div class="card" style="padding:25px;">
                    <div class="ct">Billing Support</div>
                    <p style="font-size:13px;color:#64748b;margin-top:10px;">Have questions about your invoice? Our portal billing team is here to help.</p>
                    <div style="margin-top:15px;display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;">
                        <i class="fa-solid fa-envelope" style="color:var(--teal)"></i> billing@hamrolabs.com
                    </div>
                </div>
            </div>
        </div>
    </div>`;

    _loadBillingData();
};

async function _loadBillingData() {
    try {
        const res = await fetch(APP_URL + '/api/admin/billing');
        const data = await res.json();
        if (data.success && data.data) {
            const b = data.data;
            document.getElementById('planName').innerText = (b.plan || 'STARTER').toUpperCase();
            document.getElementById('limitStudents').innerText = `${b.student_count || 0} / ${b.student_limit || 0}`;
            document.getElementById('barStudents').style.width = `${Math.min(100, (b.student_count/b.student_limit)*100)}%`;
            document.getElementById('limitSMS').innerText = `${b.sms_credits || 0} / ${b.sms_limit || 500}`;
            document.getElementById('barSMS').style.width = `${Math.min(100, (b.sms_credits/b.sms_limit)*100)}%`;

            const bh = document.getElementById('billingHistory');
            if (b.history && b.history.length) {
                bh.innerHTML = b.history.map(h => `<tr>
                    <td>${new Date(h.paid_at).toLocaleDateString()}</td>
                    <td>${h.plan.toUpperCase()} Plan Subscription</td>
                    <td>NPR ${parseFloat(h.amount).toLocaleString()}</td>
                    <td><span class="bdg bg-t">${h.status.toUpperCase()}</span></td>
                    <td><button class="btn bs btn-sm"><i class="fa-solid fa-file-invoice"></i> PDF</button></td>
                </tr>`).join('');
            } else {
                bh.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">No payment records found.</td></tr>`;
            }
        }
    } catch(e) {}
}

/* ══════════════════════════════════════════════════════════════════
   BRANDING SETTINGS
══════════════════════════════════════════════════════════════════ */
window.renderBrandingSettings = function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Branding & White-label</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-palette"></i></div><div><div class="pg-title">Branding & UI</div><div class="pg-sub">Customize the platform look and feel for your institute</div></div></div></div>
        
        <div class="card fu" style="max-width:900px;margin:0 auto;padding:40px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;">
                <div>
                    <div class="sc-lbl mb" style="padding-left:0;">Visual Identity</div>
                    <div class="form-group"><label class="form-label">Primary Brand Color</label><div style="display:flex;gap:10px;"><input type="color" id="brColor" class="form-control" style="width:60px;padding:2px;height:45px;"><input type="text" id="brColorHex" class="form-control" placeholder="#006D44"></div><small style="color:#94a3b8;margin-top:5px;display:block;">This color appears on badges, buttons, and headers.</small></div>
                    <div class="form-group" style="margin-top:20px;"><label class="form-label">Login Page Background</label><select class="form-control"><option>Dynamic Geometric (Default)</option><option>Solid Color</option><option>Custom Image</option></select></div>
                </div>
                <div style="background:#f8fafc;border-radius:16px;padding:30px;border:1px dashed #e2e8f0;">
                    <div style="text-align:center;font-size:11px;text-transform:uppercase;color:#94a3b8;font-weight:700;margin-bottom:15px;">Live Preview</div>
                    <div style="background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);overflow:hidden;">
                        <div id="pvHdr" style="height:40px;background:#006D44;display:flex;align-items:center;padding:0 15px;"><div style="width:15px;height:15px;border-radius:50%;background:rgba(255,255,255,0.3);"></div></div>
                        <div style="padding:20px;">
                            <div style="width:60%;height:10px;background:#f1f5f9;border-radius:5px;margin-bottom:10px;"></div>
                            <div style="width:100%;height:30px;background:#006D44;border-radius:6px;opacity:0.8;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top:40px;text-align:right;border-top:1px solid #f1f5f9;padding-top:25px;">
                <button class="btn bt" onclick="Swal.fire('Saved!','Branding configuration updated.','success')"><i class="fa-solid fa-cloud-arrow-up"></i> Apply Changes</button>
            </div>
        </div>
    </div>`;
    
    const cp = document.getElementById('brColor');
    const ct = document.getElementById('brColorHex');
    if(cp && ct) {
        cp.oninput = (e) => { ct.value = e.target.value.toUpperCase(); document.getElementById('pvHdr').style.backgroundColor = e.target.value; };
    }
};

/* ══════════════════════════════════════════════════════════════════
   RBAC & PERMISSIONS
══════════════════════════════════════════════════════════════════ */
window.renderRBACSettings = function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Role-Based Access Control</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-user-shield"></i></div><div><div class="pg-title">RBAC Configuration</div><div class="pg-sub">Manage system roles and feature permissions</div></div></div></div>
        
        <div class="card fu" style="max-width:1000px;margin:0 auto;padding:0;overflow:hidden;">
            <div style="display:grid;grid-template-columns:250px 1fr;height:600px;">
                <div style="background:#f8fafc;border-right:1px solid #e2e8f0;padding:20px;">
                    <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;margin-bottom:15px;">System Roles</div>
                    <div class="rb-role" style="padding:12px;background:#fff;border:1.5px solid var(--teal);border-radius:10px;margin-bottom:10px;cursor:pointer;">
                        <div style="font-weight:700;font-size:13px;color:var(--teal-d);">Front Desk</div>
                        <div style="font-size:11px;color:#64748b;">3 Users Assigned</div>
                    </div>
                    <div class="rb-role" style="padding:12px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;cursor:pointer;opacity:0.7;">
                        <div style="font-weight:700;font-size:13px;">Teacher</div>
                        <div style="font-size:11px;color:#64748b;">12 Users Assigned</div>
                    </div>
                    <div class="rb-role" style="padding:12px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;cursor:pointer;opacity:0.7;">
                        <div style="font-weight:700;font-size:13px;">Accountant</div>
                        <div style="font-size:11px;color:#64748b;">1 User Assigned</div>
                    </div>
                    <button class="btn bs fu btn-sm" style="margin-top:10px;"><i class="fa-solid fa-plus"></i> Create Custom Role</button>
                </div>
                <div style="padding:30px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
                        <h3 style="margin:0;">Permissions for "Front Desk"</h3>
                        <button class="btn bt btn-sm">Save Permissions</button>
                    </div>
                    <table class="tbl">
                        <thead><tr><th>Module</th><th>View</th><th>Create</th><th>Edit</th><th>Delete</th></tr></thead>
                        <tbody>
                            ${['Students','Inquiries','Academic','Fees','Attendance'].map(m => `
                                <tr>
                                    <td style="font-weight:600;">${m}</td>
                                    <td><input type="checkbox" checked></td>
                                    <td><input type="checkbox" ${m==='Fees'?'':'checked'}></td>
                                    <td><input type="checkbox"></td>
                                    <td><input type="checkbox"></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>`;
};

/* ══════════════════════════════════════════════════════════════════
   NOTIFICATIONS & AUTOMATION
══════════════════════════════════════════════════════════════════ */
window.renderNotificationSettings = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Notification Rules</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#F59E0B,#D97706);"><i class="fa-solid fa-robot"></i></div>
                <div><div class="pg-title">Automation Engine</div><div class="pg-sub">Configure automatic SMS alerts for students and parents</div></div>
            </div>
            <div class="pg-right">
                <button class="btn bt" onclick="openRuleModal()"><i class="fa-solid fa-plus"></i> Create New Rule</button>
            </div>
        </div>

        <div style="max-width:1100px;margin:0 auto;">
            <div id="rulesLoading" style="text-align:center;padding:100px;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:3rem;color:#F59E0B"></i><p style="margin-top:15px;color:#64748b">Loading automation rules...</p></div>
            <div id="rulesContainer" class="kpi-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:20px;margin-top:20px;">
                <!-- Rules will be injected here -->
            </div>
            <div id="noRulesMsg" style="display:none;text-align:center;padding:100px;background:#f8fafc;border-radius:24px;border:2px dashed #e2e8f0;">
                <i class="fa-solid fa-ghost" style="font-size:3rem;color:#cbd5e1;margin-bottom:20px;"></i>
                <h3>No automation rules yet</h3>
                <p style="color:#64748b;margin-bottom:20px;">Create your first rule to start automating student notifications.</p>
                <button class="btn bt" onclick="openRuleModal()"><i class="fa-solid fa-plus"></i> Build First Rule</button>
            </div>
        </div>
    </div>`;

    await _loadAutomationRules();
};

async function _loadAutomationRules() {
    try {
        const res = await fetch(APP_URL + '/api/admin/automation-rules');
        const r = await res.json();
        const container = document.getElementById('rulesContainer');
        const loading = document.getElementById('rulesLoading');
        const empty = document.getElementById('noRulesMsg');
        
        loading.style.display = 'none';
        if (r.success && r.data && r.data.length > 0) {
            empty.style.display = 'none';
            container.style.display = 'grid';
            container.innerHTML = r.data.map(rule => `
                <div class="card rule-card glass" style="padding:25px;position:relative;border-left:4px solid ${rule.trigger_type === 'absent' ? '#ef4444' : '#3b82f6'};">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px;">
                        <div>
                            <div class="bdg" style="background:${rule.is_active ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'};color:${rule.is_active ? '#10b981' : '#ef4444'};margin-bottom:8px;">
                                ${rule.is_active ? 'Active' : 'Paused'}
                            </div>
                            <h3 style="margin:0;font-size:16px;">${rule.name}</h3>
                        </div>
                        <div class="dropdown">
                            <button class="btn bs btn-sm" onclick="openRuleModal(${rule.id})"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn bs btn-sm" style="color:#ef4444;" onclick="deleteRule(${rule.id})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    <div style="font-size:13px;color:#64748b;margin-bottom:15px;">
                        <i class="fa-solid fa-bolt" style="color:#F59E0B;margin-right:5px;"></i> Trigger: <strong>${rule.trigger_type === 'absent' ? 'Student Absence' : 'Fee Due'}</strong>
                    </div>
                    <div style="background:rgba(0,0,0,0.03);padding:12px;border-radius:10px;font-size:12px;color:#1e293b;font-family:monospace;white-space:pre-wrap;margin-top:10px;">${rule.message_template}</div>
                </div>
            `).join('');
        } else {
            container.style.display = 'none';
            empty.style.display = 'block';
        }
    } catch(e) {
        console.error('Rules load error', e);
        Swal.fire('Error', 'Failed to load rules', 'error');
    }
}

window.openRuleModal = async function(id = null) {
    let rule = { name: '', trigger_type: 'absent', message_template: 'Dear parent, your ward {student_name} was marked absent today.', conditions: {}, is_active: 1 };
    
    if (id) {
        const res = await fetch(APP_URL + `/api/admin/automation-rules?id=${id}`);
        const r = await res.json();
        if (r.success) rule = r.data;
    }

    const { value: formValues } = await Swal.fire({
        title: id ? 'Edit Notification Rule' : 'Create New Automation Rule',
        width: '600px',
        html: `
            <div style="text-align:left;">
                <label class="form-label">Rule Name</label>
                <input id="swal-rule-name" class="swal2-input" placeholder="e.g. Absence Alert to Parents" value="${rule.name}" style="width:100%;margin:5px 0 15px;">
                
                <label class="form-label">Trigger Event</label>
                <select id="swal-rule-trigger" class="swal2-input" style="width:100%;margin:5px 0 15px;">
                    <option value="absent" ${rule.trigger_type === 'absent' ? 'selected' : ''}>Student marked Absent</option>
                    <option value="fee_due" ${rule.trigger_type === 'fee_due' ? 'selected' : ''}>Daily Fee Due Check</option>
                </select>

                <label class="form-label">Message Template</label>
                <textarea id="swal-rule-msg" class="swal2-textarea" style="width:100%;height:120px;margin:5px 0 10px;font-family:monospace;">${rule.message_template}</textarea>
                <div style="font-size:11px;color:#94a3b8;margin-bottom:15px;">
                    Tags: {student_name}, {guardian_name}, {date}, {amount_due}
                </div>

                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="checkbox" id="swal-rule-active" ${rule.is_active ? 'checked' : ''}>
                    <label for="swal-rule-active" style="font-size:14px;font-weight:700;">Active & Running</label>
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#F59E0B',
        preConfirm: () => {
            return {
                id: id,
                name: document.getElementById('swal-rule-name').value,
                trigger_type: document.getElementById('swal-rule-trigger').value,
                message_template: document.getElementById('swal-rule-msg').value,
                is_active: document.getElementById('swal-rule-active').checked ? 1 : 0,
                action: 'save'
            }
        }
    });

    if (formValues) {
        try {
            const res = await fetch(APP_URL + '/api/admin/automation-rules', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formValues)
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Success', result.message, 'success');
                _loadAutomationRules();
            } else throw new Error(result.message);
        } catch(e) { Swal.fire('Error', e.message, 'error'); }
    }
};

window.deleteRule = async function(id) {
    const confirm = await Swal.fire({
        title: 'Delete Rule?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirm.isConfirmed) {
        try {
            const res = await fetch(APP_URL + '/api/admin/automation-rules', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const result = await res.json();
            if (result.success) {
                Swal.fire('Deleted!', 'Rule has been removed.', 'success');
                _loadAutomationRules();
            } else throw new Error(result.message);
        } catch(e) { Swal.fire('Error', e.message, 'error'); }
    }
};

/* ══════════════════════════════════════════════════════════════════
   ACADEMIC YEAR
══════════════════════════════════════════════════════════════════ */
window.renderAcademicYearSettings = function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Academic Session Management</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico"><i class="fa-solid fa-calendar-check"></i></div><div><div class="pg-title">Academic Year / Session</div><div class="pg-sub">Manage terms, semesters and active academic cycles</div></div></div></div>
        <div class="card fu" style="max-width:900px;margin:0 auto;padding:40px;">
             <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
                <div><div class="tag bg-t" style="margin-bottom:8px;">Current Active Session</div><h3>Year 2081 - 2082 BS</h3></div>
                <button class="btn bt"><i class="fa-solid fa-rotate"></i> Change Active Session</button>
             </div>
             <div class="sc-lbl" style="padding-left:0;">Previous Sessions</div>
             <div style="margin-top:15px;display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <div class="card" style="padding:15px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                    <div><strong>Year 2080-2081</strong><div style="font-size:12px;color:#94a3b8;">Closed on April 12, 2024</div></div>
                    <button class="btn bs btn-sm">Archive</button>
                </div>
                <div class="card" style="padding:15px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                    <div><strong>Year 2079-2080</strong><div style="font-size:12px;color:#94a3b8;">Closed on April 10, 2023</div></div>
                    <button class="btn bs btn-sm">Archive</button>
                </div>
             </div>
        </div>
    </div>`;
};

window.renderSecuritySettings = async function() {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Security Settings</span></div>
        <div class="pg-head"><div class="pg-left"><div class="pg-ico" style="background:var(--red);"><i class="fa-solid fa-shield-halved"></i></div><div><div class="pg-title">Security & 2FA</div><div class="pg-sub">Manage authentication policies and two-factor security</div></div></div></div>
        
        <div style="max-width:800px;margin:0 auto;">
            <div class="tabs" style="display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #e2e8f0;padding-bottom:10px;">
                <button class="btn bt-sm" onclick="renderInstituteProfile()">Profile</button>
                <button class="btn bs" onclick="renderSecuritySettings()">Security & 2FA</button>
            </div>

            <div class="card" style="padding:40px;">
                <div class="sc-lbl mb">Two-Factor Authentication (2FA)</div>
                <div style="background:#fff7ed;border:1px solid #ffedd5;border-radius:12px;padding:20px;margin-bottom:30px;display:flex;gap:15px;align-items:flex-start;">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;font-size:1.5rem;margin-top:3px;"></i>
                    <div>
                        <div style="font-weight:700;color:#92400e;margin-bottom:5px;">Mandatory Admin Security</div>
                        <div style="font-size:13px;color:#b45309;line-height:1.6;">As per the institute security policy, it is highly recommended to enable 2FA for all administrative accounts to protect sensitive student and financial data.</div>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 0;border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-weight:700;">Enforce 2FA for all Admins</div>
                        <div style="font-size:12px;color:#64748b;">Require every staff member with admin access to setup Two-Factor Authentication.</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="2faEnforceToggle" onchange="_toggle2FAEnforcement(this.checked)">
                        <span class="slider round"></span>
                    </label>
                </div>

                <div style="margin-top:40px;">
                    <button class="btn bt" id="setupPersonal2FA"><i class="fa-solid fa-key"></i> Setup My Personal 2FA</button>
                    <p style="font-size:11px;color:#94a3b8;margin-top:10px;">Secure your own account using Authenticator Apps (Google/Microsoft).</p>
                </div>
            </div>
        </div>
    </div>`;
    
    _load2FAStatus();
};

async function _load2FAStatus() {
    try {
        const res = await fetch(APP_URL + '/api/admin/2fa_setup?action=status');
        const r = await res.json();
        if(r.success) {
            const toggle = document.getElementById('2faEnforceToggle');
            if(toggle) toggle.checked = r.data.institute_enforced;
        }
    } catch(e) {
        console.error('Failed to load 2FA status', e);
    }
}

async function _toggle2FAEnforcement(enabled) {
    try {
        const fd = new FormData();
        fd.append('enabled', enabled);
        fd.append('action', 'toggle_institute');
        const res = await fetch(APP_URL + '/api/admin/2fa_setup', { method: 'POST', body: fd });
        const r = await res.json();
        if(r.success) {
            Swal.fire({ icon:'success', title:'Security Updated', text:'2FA enforcement policy has been updated.', timer:2000, showConfirmButton:false });
        } else throw new Error(r.message);
    } catch(e) { 
        Swal.fire('Error', e.message, 'error');
        const toggle = document.getElementById('2faEnforceToggle');
        if(toggle) toggle.checked = !enabled;
    }
}