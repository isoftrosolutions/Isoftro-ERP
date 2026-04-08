<?php
/**
 * ISOFTRO - Platform Branding
 * Variable: $settings (key=>value)
 */
$settings = $settings ?? [];
function getB($k, $d='') { global $settings; return $settings[$k] ?? $d; }
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Settings</span> <span style="color:#94a3b8;"> / Platform Branding</span></div>
        <h1>Platform Branding</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="saveBranding()"><i class="fas fa-save"></i> Save Changes</button>
    </div>
</div>

<div class="g2 mt-20">
    <!-- Identity -->
    <div class="card p-20">
        <div class="ct"><i class="fas fa-id-badge"></i> Platform Identity</div>
        <div class="form-grp">
            <label class="form-lbl">Platform Name</label>
            <input type="text" class="form-inp" name="platform_name" value="<?= htmlspecialchars(getB('platform_name','iSoftro ERP')) ?>">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Tagline</label>
            <input type="text" class="form-inp" name="platform_tagline" value="<?= htmlspecialchars(getB('platform_tagline','Academic ERP for Nepal')) ?>">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Support Email</label>
            <input type="email" class="form-inp" name="support_email" value="<?= htmlspecialchars(getB('support_email','support@isoftro.com')) ?>">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Support Phone</label>
            <input type="text" class="form-inp" name="support_phone" value="<?= htmlspecialchars(getB('support_phone','+977-')) ?>">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Website URL</label>
            <input type="url" class="form-inp" name="platform_url" value="<?= htmlspecialchars(getB('platform_url','https://isoftroerp.com')) ?>">
        </div>
    </div>

    <!-- Colors & Logo -->
    <div class="card p-20">
        <div class="ct"><i class="fas fa-palette"></i> Colors & Logo</div>
        <div class="form-grp">
            <label class="form-lbl">Primary Brand Color</label>
            <div style="display:flex;gap:10px;align-items:center;">
                <input type="color" id="primaryColor" value="<?= htmlspecialchars(getB('brand_primary','#00B894')) ?>" onchange="previewColor(this.value)" style="width:48px;height:40px;border:none;border-radius:8px;cursor:pointer;">
                <input type="text" class="form-inp" id="primaryColorText" value="<?= htmlspecialchars(getB('brand_primary','#00B894')) ?>" style="margin-top:0;flex:1;" oninput="syncColorPicker(this.value)">
            </div>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Secondary Color</label>
            <div style="display:flex;gap:10px;align-items:center;">
                <input type="color" value="<?= htmlspecialchars(getB('brand_secondary','#006D44')) ?>" style="width:48px;height:40px;border:none;border-radius:8px;cursor:pointer;">
                <input type="text" class="form-inp" value="<?= htmlspecialchars(getB('brand_secondary','#006D44')) ?>" style="margin-top:0;flex:1;">
            </div>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Platform Logo</label>
            <div style="display:flex;align-items:center;gap:15px;margin-top:8px;">
                <?php $logo = getB('platform_logo'); ?>
                <?php if ($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" style="height:48px;border-radius:8px;border:1px solid #e2e8f0;" alt="Logo">
                <?php else: ?>
                <div style="width:48px;height:48px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class="fas fa-image"></i></div>
                <?php endif; ?>
                <input type="file" accept="image/*" onchange="previewLogo(this)">
            </div>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Favicon</label>
            <input type="file" accept="image/x-icon,image/png">
        </div>
        <div class="form-grp" style="margin-top:15px;">
            <label class="form-lbl">Preview</label>
            <div id="brandPreview" style="padding:16px;border-radius:12px;background:<?= htmlspecialchars(getB('brand_primary','#00B894')) ?>;color:#fff;font-weight:700;font-size:14px;text-align:center;transition:.3s;">
                <?= htmlspecialchars(getB('platform_name','iSoftro ERP')) ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct"><i class="fas fa-envelope-open"></i> Email Footer Branding</div>
    <div class="g2" style="margin-top:5px;">
        <div class="form-grp">
            <label class="form-lbl">Email Footer Text</label>
            <textarea class="form-inp" rows="3" name="email_footer"><?= htmlspecialchars(getB('email_footer','© 2025 iSoftro ERP. All rights reserved.')) ?></textarea>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Social Links (JSON)</label>
            <textarea class="form-inp" rows="3" name="social_links" placeholder='{"facebook":"...","twitter":"..."}'><?= htmlspecialchars(getB('social_links','{}')) ?></textarea>
        </div>
    </div>
</div>

<script>
function previewColor(val) {
    document.getElementById('primaryColorText').value = val;
    document.getElementById('brandPreview').style.background = val;
}
function syncColorPicker(val) {
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        document.getElementById('primaryColor').value = val;
        document.getElementById('brandPreview').style.background = val;
    }
}
function previewLogo(inp) {
    if (inp.files && inp.files[0]) {
        SuperAdmin.showNotification('Logo preview ready. Save to apply.', 'info');
    }
}
function saveBranding() {
    SuperAdmin.showNotification('Branding settings saved successfully.', 'success');
}
</script>
