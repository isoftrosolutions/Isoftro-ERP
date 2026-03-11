/**
 * Hamro ERP — Add Tenant Page JS
 * Loaded via <script src> inside <main> so the SPA's processPartialHtml
 * re-creates and re-executes this on every navigation to the add-tenant page.
 */
(function () {
    /* HELPERS */
    const _gid = id => document.getElementById(id);
    const _val = id => _gid(id)?.value?.trim() ?? '';

    function syncPreview() {
        const name      = _val('instName')     || 'Institute Name';
        const nameNp    = _val('instNameNp')   || 'संस्थाको नाम';
        const tagline   = _val('instTagline')  || 'Tagline goes here';
        const email     = _val('instEmail')    || 'info@institute.com';
        const phone     = _val('instPhone')    || '+977-XXXXXXXXX';
        const address   = _val('instAddress')  || 'Institute address';
        const website   = _val('instWebsite');
        const subdomain = _val('subdomainInp') || 'subdomain';
        const plan      = _gid('billingPlan')?.value  || 'starter';
        const status    = _gid('tenantStatus')?.value || 'trial';
        const color     = _gid('themeColor')?.value   || '#009E7E';
        const adminN    = _val('adminName')    || 'Admin Name';
        const adminE    = _val('adminEmail')   || 'admin@institute.com';

        _setText('mockName', name);       _setText('mockNameNp', nameNp);
        _setText('mockTagline', tagline); _setText('mockEmail', email);
        _setText('mockPhone', phone);     _setText('mockAddress', address);
        _setText('mockSubdomain', subdomain + '.hamrolabs.com.np');

        const wRow = _gid('mockWebsiteRow');
        if (wRow) {
            if (website) { wRow.style.display = 'flex'; _setText('mockWebsite', website); }
            else         { wRow.style.display = 'none'; }
        }

        const hero   = _gid('mockHero');        const cpb    = _gid('colorPreviewBig');
        const hexLbl = _gid('colorHexLabel');   const ava    = _gid('mockAdminAvatar');
        if (hero)   hero.style.background   = color;
        if (cpb)    cpb.style.background    = color;
        if (hexLbl) hexLbl.textContent      = color.toUpperCase();
        if (ava)    ava.style.background    = color;

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
        const inp   = _gid('subdomainInp');
        const clean = inp.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
        inp.value   = clean;
        const pill  = _gid('subPillText');
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
        reader.onload = function (e) {
            const src = e.target.result;
            const es  = _gid('logoEmptyState'); const fi = _gid('logoFilePreview');
            const cb  = _gid('clearLogoBtn');   const mi = _gid('mockLogoIcon'); const ml = _gid('mockLogoImg');
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
        const il = _gid('instLogo');         if (il) il.value = '';
        const fi = _gid('logoFilePreview');  if (fi) fi.style.display = 'none';
        const es = _gid('logoEmptyState');   if (es) es.style.display = 'flex';
        const cb = _gid('clearLogoBtn');     if (cb) cb.style.display = 'none';
        const ml = _gid('mockLogoImg');      if (ml) ml.style.display = 'none';
        const mi = _gid('mockLogoIcon');     if (mi) mi.style.display = 'flex';
        updateChecklist();
    }

    function checkPassStrength() {
        const p    = _val('adminPass');
        const reqs = { length: p.length >= 8, upper: /[A-Z]/.test(p), number: /[0-9]/.test(p), special: /[^A-Za-z0-9]/.test(p) };
        ['length','upper','number','special'].forEach(function (k) {
            const el = _gid('req-' + k); if (!el) return;
            el.className  = 'preq' + (reqs[k] ? ' met' : '');
            const ico = el.querySelector('i');
            if (ico) ico.className = reqs[k] ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-dot';
        });
        const score = Object.values(reqs).filter(Boolean).length;
        const cfgs  = [null,
            {w:'25%',  bg:'#E11D48', txt:'Weak',   col:'#E11D48'},
            {w:'50%',  bg:'#F59E0B', txt:'Fair',   col:'#F59E0B'},
            {w:'75%',  bg:'#3B82F6', txt:'Good',   col:'#3B82F6'},
            {w:'100%', bg:'#009E7E', txt:'Strong', col:'#009E7E'},
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
        else                         { inp.type = 'password'; ico.className = 'fa-solid fa-eye'; }
    }

    function updateChecklist() {
        _chkSet('chk-name',      !!_val('instName'));
        _chkSet('chk-subdomain', _val('subdomainInp').length >= 3);
        _chkSet('chk-contact',   !!_val('instEmail') && !!_val('instPhone'));
        _chkSet('chk-address',   !!_val('instAddress'));
        _chkSet('chk-logo',      _gid('mockLogoImg') ? _gid('mockLogoImg').style.display !== 'none' : false);
        _chkSet('chk-admin',     !!_val('adminName') && !!_val('adminEmail'));
        const p      = _val('adminPass');
        const strong = p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p) && /[^A-Za-z0-9]/.test(p);
        _chkSet('chk-pass', strong && p === _val('adminPassConfirm') && !!_val('adminPassConfirm'));
    }

    function _chkSet(id, done) {
        const el = _gid(id); if (!el) return;
        el.className  = 'chk-row' + (done ? ' done' : '');
        const ico = el.querySelector('i');
        if (ico) ico.className = done ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle-dot';
    }

    function validateAll() {
        let ok = true;
        const rules = [
            {id:'instName',     err:'err-instName',        msg:'Institute name is required'},
            {id:'instAddress',  err:'err-instAddress',     msg:'Address is required'},
            {id:'instPhone',    err:'err-instPhone',       msg:'Phone is required',       pat:/^[\d\+\-\s]{7,15}$/, patMsg:'Enter a valid phone'},
            {id:'instEmail',    err:'err-instEmail',       msg:'Email is required',       type:'email'},
            {id:'subdomainInp', err:'err-subdomain',       msg:'Subdomain is required',   min:3, minMsg:'Min 3 characters'},
            {id:'adminName',    err:'err-adminName',       msg:'Admin name is required'},
            {id:'adminPhone',   err:'err-adminPhone',      msg:'Admin phone is required'},
            {id:'adminEmail',   err:'err-adminEmail',      msg:'Admin email is required', type:'email'},
            {id:'adminPass',    err:'err-adminPass',       msg:'Password is required'},
        ];
        rules.forEach(function (r) {
            const e = _gid(r.err); if (e) e.textContent = '';
            const i = _gid(r.id);  if (i) i.classList.remove('is-err','is-ok');
        });
        rules.forEach(function (r) {
            const el = _gid(r.id); if (!el) return;
            const v  = el.value.trim(); let msg = '';
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
            const e = _gid('err-adminPass');  if (e) e.textContent = 'Password not strong enough';
            const inp = _gid('adminPass');    if (inp) inp.classList.add('is-err'); ok = false;
        }
        const p2 = _val('adminPassConfirm');
        if (!p2) {
            const e = _gid('err-adminPassConfirm');  if (e) e.textContent = 'Please confirm your password';
            const inp = _gid('adminPassConfirm');    if (inp) inp.classList.add('is-err'); ok = false;
        } else if (p !== p2) {
            const e = _gid('err-adminPassConfirm');  if (e) e.textContent = 'Passwords do not match';
            const inp = _gid('adminPassConfirm');    if (inp) inp.classList.add('is-err'); ok = false;
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

        doConfirm.then(function (res) {
            if (!res.isConfirmed) return;
            var btn = _gid('btnFinalSubmit');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Registering...'; }
            fetch(window.APP_URL + '/api/super-admin/tenants/save', {
                method: 'POST', headers: {'X-CSRF-Token': csrf}, body: fd
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification(data.message, 'success');
                    setTimeout(function () {
                        if (typeof SuperAdmin !== 'undefined') SuperAdmin.goNav('tenants');
                        else window.location.href = 'tenant-management.php';
                    }, 1500);
                } else {
                    if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification(data.message, 'error');
                    else alert(data.message);
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i>Register Institute'; }
                }
            })
            .catch(function (err) {
                if (typeof SuperAdmin !== 'undefined') SuperAdmin.showNotification('Network error. Please try again.', 'error');
                console.error(err);
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i>Register Institute'; }
            });
        });
    }

    /* ── Expose on window so inline onclick handlers work ── */
    window.saveInstitute        = saveInstitute;
    window.syncPreview          = syncPreview;
    window.handleSubdomainInput = handleSubdomainInput;
    window.setColor             = setColor;
    window.handleLogoUpload     = handleLogoUpload;
    window.clearLogo            = clearLogo;
    window.checkPassStrength    = checkPassStrength;
    window.checkConfirmPass     = checkConfirmPass;
    window.togglePass           = togglePass;

    /* ── Init ── */
    try { syncPreview(); } catch(e) {}

    var dz = _gid('logoDropZone');
    if (dz) {
        dz.addEventListener('dragover',  function (e) { e.preventDefault(); dz.classList.add('drag-active'); });
        dz.addEventListener('dragleave', function ()  { dz.classList.remove('drag-active'); });
        dz.addEventListener('drop',      function (e) {
            e.preventDefault(); dz.classList.remove('drag-active');
            if (e.dataTransfer.files[0]) handleLogoUpload({files: e.dataTransfer.files});
        });
    }
})();
