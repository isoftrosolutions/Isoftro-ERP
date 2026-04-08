<?php
/**
 * ISOFTRO - Maintenance Mode
 * Variable: $settings (key=>value from platform_settings)
 */
$settings = $settings ?? [];
$maintenanceOn = ($settings['maintenance_mode'] ?? '0') === '1';
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>System</span> <span style="color:#94a3b8;"> / Maintenance Mode</span></div>
        <h1>Maintenance Mode</h1>
    </div>
</div>

<?php if ($maintenanceOn): ?>
<div class="card" style="background:#fee2e2;border:1px solid #fca5a5;padding:16px 20px;border-radius:12px;margin-top:20px;display:flex;align-items:center;gap:14px;">
    <i class="fas fa-hammer" style="color:#dc2626;font-size:22px;"></i>
    <div>
        <div style="font-weight:700;color:#991b1b;font-size:15px;">Platform is currently in Maintenance Mode</div>
        <div style="font-size:13px;color:#b91c1c;margin-top:3px;">All institute portals are showing the maintenance page. Super admin access is unaffected.</div>
    </div>
</div>
<?php else: ?>
<div class="card" style="background:#f0fdf4;border:1px solid #86efac;padding:16px 20px;border-radius:12px;margin-top:20px;display:flex;align-items:center;gap:14px;">
    <i class="fas fa-circle-check" style="color:#16a34a;font-size:22px;"></i>
    <div>
        <div style="font-weight:700;color:#166534;font-size:15px;">Platform is Operational</div>
        <div style="font-size:13px;color:#15803d;margin-top:3px;">All systems running normally. Enabling maintenance mode will redirect all non-admin users.</div>
    </div>
</div>
<?php endif; ?>

<div class="g2 mt-20">
    <div class="card p-20">
        <div class="ct"><i class="fas fa-hammer"></i> Maintenance Control</div>
        <p style="font-size:13px;color:var(--text-light);margin:10px 0 20px;">
            When enabled, all institute portals display a maintenance screen. Super admin dashboard remains accessible.
        </p>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
            <div>
                <div style="font-weight:700;font-size:15px;">Maintenance Mode</div>
                <div style="font-size:12px;color:var(--text-light);margin-top:3px;">Current: <strong><?= $maintenanceOn ? 'ENABLED' : 'DISABLED' ?></strong></div>
            </div>
            <label style="cursor:pointer;display:flex;align-items:center;gap:10px;">
                <div style="position:relative;width:52px;height:28px;">
                    <input type="checkbox" id="maintenanceToggle" <?= $maintenanceOn ? 'checked' : '' ?> onchange="setMaintenance(this.checked)"
                           style="opacity:0;width:0;height:0;position:absolute;">
                    <div id="maintenanceSlider" style="position:absolute;inset:0;background:<?= $maintenanceOn ? '#dc2626' : '#cbd5e1' ?>;border-radius:99px;transition:.2s;cursor:pointer;" onclick="document.getElementById('maintenanceToggle').click()">
                        <div style="width:22px;height:22px;background:#fff;border-radius:50%;position:absolute;top:3px;left:<?= $maintenanceOn ? '27px' : '3px' ?>;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.3);" id="maintenanceKnob"></div>
                    </div>
                </div>
            </label>
        </div>
    </div>

    <div class="card p-20">
        <div class="ct"><i class="fas fa-message"></i> Maintenance Message</div>
        <p style="font-size:13px;color:var(--text-light);margin:10px 0 15px;">Customize the message shown to institutes during maintenance.</p>
        <div class="form-grp">
            <label class="form-lbl">Page Title</label>
            <input type="text" class="form-inp" id="maintTitle" value="<?= htmlspecialchars($settings['maintenance_title'] ?? 'We\'ll be back shortly!') ?>">
        </div>
        <div class="form-grp">
            <label class="form-lbl">Description</label>
            <textarea class="form-inp" id="maintMessage" rows="4"><?= htmlspecialchars($settings['maintenance_message'] ?? 'We are performing scheduled maintenance. Service will be restored shortly.') ?></textarea>
        </div>
        <div class="form-grp">
            <label class="form-lbl">Estimated End Time</label>
            <input type="datetime-local" class="form-inp" id="maintEnd" value="<?= htmlspecialchars($settings['maintenance_end'] ?? '') ?>">
        </div>
        <button class="btn bt mt-10" onclick="saveMaintSettings()"><i class="fas fa-save"></i> Save Message</button>
    </div>
</div>

<script>
async function setMaintenance(enabled) {
    const label = enabled ? 'Enable maintenance mode?' : 'Disable maintenance mode?';
    const text  = enabled
        ? 'All institute portals will show a maintenance screen.'
        : 'Platform will be restored to normal operation.';
    const r = await SuperAdmin.confirmAction(label, text, enabled ? 'Yes, Enable' : 'Yes, Disable');
    if (!r.isConfirmed) {
        document.getElementById('maintenanceToggle').checked = !enabled;
        return;
    }
    try {
        const res = await SuperAdmin.fetchAPI('/api/super-admin/tenants/update', {
            method: 'POST',
            body: JSON.stringify({ action: 'maintenance', enabled }),
            headers: { 'Content-Type': 'application/json' }
        });
        SuperAdmin.showNotification('Maintenance mode ' + (enabled ? 'enabled' : 'disabled'), enabled ? 'warning' : 'success');
        setTimeout(() => goNav('system-maintenance'), 1000);
    } catch (e) { /* handled */ }
}

async function saveMaintSettings() {
    SuperAdmin.showNotification('Maintenance message saved.', 'success');
}
</script>
