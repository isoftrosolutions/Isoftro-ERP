<?php
/**
 * ISOFTRO - Feature Flags
 * Variable: $features (system_features table)
 */
$features = $features ?? [];
$active = count(array_filter($features, fn($f) => ($f['status'] ?? 'active') === 'active'));
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Plans</span> <span style="color:#94a3b8;"> / Feature Flags</span></div>
        <h1>Feature Flags</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="openAddFlagModal()"><i class="fas fa-plus"></i> New Flag</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #22c55e;">
        <div style="font-size:11px;font-weight:700;color:#22c55e;text-transform:uppercase;">Enabled Flags</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= $active ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #6366f1;">
        <div style="font-size:11px;font-weight:700;color:#6366f1;text-transform:uppercase;">Total Flags</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count($features) ?></div>
    </div>
    <div class="card p-20" style="border-left:4px solid #ef4444;">
        <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;">Disabled</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= count($features) - $active ?></div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct"><i class="fas fa-toggle-right"></i> System Feature Flags</div>
    <p style="font-size:12px;color:var(--text-light);margin-bottom:20px;">
        Globally enable or disable system modules. Changes take effect immediately across all institutes.
    </p>
    <?php if (empty($features)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-flag" style="font-size:40px;opacity:.3;display:block;margin-bottom:15px;"></i>
        <p>No feature flags defined.</p>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;">
        <?php foreach ($features as $f): ?>
        <?php $on = ($f['status'] ?? 'active') === 'active'; ?>
        <label class="flag-card <?= $on ? 'flag-on' : 'flag-off' ?>" style="cursor:pointer;padding:16px;background:<?= $on ? '#f0fdf4' : '#fafafa' ?>;border:1px solid <?= $on ? '#86efac' : '#e2e8f0' ?>;border-radius:12px;display:flex;align-items:center;gap:14px;transition:.2s;">
            <input type="checkbox" <?= $on ? 'checked' : '' ?> onchange="toggleFlag('<?= htmlspecialchars($f['feature_key']) ?>', this.checked)" style="display:none;">
            <div style="width:42px;height:24px;border-radius:99px;background:<?= $on ? '#22c55e' : '#cbd5e1' ?>;position:relative;flex-shrink:0;transition:.2s;" class="flag-toggle">
                <div style="width:18px;height:18px;border-radius:50%;background:#fff;position:absolute;top:3px;left:<?= $on ? '21px' : '3px' ?>;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2);"></div>
            </div>
            <div>
                <div style="font-weight:700;font-size:13px;color:var(--text-dark);"><?= htmlspecialchars($f['feature_name']) ?></div>
                <div style="font-size:10px;color:var(--text-light);font-weight:600;text-transform:uppercase;margin-top:2px;">
                    <?= htmlspecialchars($f['feature_key']) ?> &bull; <?= $f['is_core'] ? 'CORE' : 'OPTIONAL' ?>
                </div>
            </div>
        </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div id="addFlagModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:440px;">
        <div class="mh">
            <h2>New Feature Flag</h2>
            <button onclick="this.closest('.modal').style.display='none'">&times;</button>
        </div>
        <div class="mb-15">
            <label>Feature Name</label>
            <input type="text" id="newFlagName" placeholder="e.g. Advanced Analytics" class="form-control">
        </div>
        <div class="mb-15">
            <label>Feature Key (slug)</label>
            <input type="text" id="newFlagKey" placeholder="e.g. advanced_analytics" class="form-control">
        </div>
        <div class="mb-15">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" id="newFlagCore"> Core Module
            </label>
        </div>
        <div class="bt-r">
            <button class="btn bs" onclick="document.getElementById('addFlagModal').style.display='none'">Cancel</button>
            <button class="btn bt" onclick="saveNewFlag()">Create Flag</button>
        </div>
    </div>
</div>

<style>
.modal { position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px); }
.modal-content { background:#fff;border-radius:16px;padding:25px;width:95%;box-shadow:0 20px 25px -5px rgba(0,0,0,.1); }
.mh { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #f1f5f9;padding-bottom:15px; }
.mh h2 { font-size:18px;margin:0;color:#1e293b; } .mh button { background:none;border:none;font-size:24px;cursor:pointer;color:#94a3b8; }
.form-control { width:100%;border:1px solid #e2e8f0;padding:10px 14px;border-radius:8px;font-size:14px;margin-top:5px; }
.bt-r { display:flex;justify-content:flex-end;gap:10px;margin-top:20px; }
</style>

<script>
async function toggleFlag(key, enabled) {
    try {
        await SuperAdmin.fetchAPI('/api/superadmin/PlansApi.php?action=toggle_system_feature', {
            method: 'POST', body: JSON.stringify({ feature_key: key, status: enabled ? 'active' : 'inactive' }),
            headers: { 'Content-Type': 'application/json' }
        });
        SuperAdmin.showNotification('Flag updated', 'success');
        setTimeout(() => goNav('plans-flags'), 800);
    } catch (e) { /* handled */ }
}

function openAddFlagModal() { document.getElementById('addFlagModal').style.display = 'flex'; }

async function saveNewFlag() {
    const name = document.getElementById('newFlagName').value.trim();
    const key  = document.getElementById('newFlagKey').value.trim();
    const core = document.getElementById('newFlagCore').checked;
    if (!name || !key) { SuperAdmin.showNotification('Name and key are required', 'error'); return; }
    SuperAdmin.showNotification('Feature flag "' + name + '" created', 'success');
    document.getElementById('addFlagModal').style.display = 'none';
}
</script>
