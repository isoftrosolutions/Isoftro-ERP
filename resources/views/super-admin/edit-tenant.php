<?php
/**
 * ISOFTRO - Super Admin Dashboard (Edit Institute)
 */

$PDO = getDBConnection();

// Fetch available modules
try {
    $stmt = $PDO->query("SELECT * FROM modules ORDER BY name ASC");
    $allModules = $stmt->fetchAll();
} catch (Exception $e) {
    $allModules = [];
}

// $tenant and $assignedModules are passed from controller
if (!$tenant) {
    echo "<div class='card'>Tenant not found.</div>";
    return;
}
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span><a href="#" onclick="goNav('tenants')">Institutes</a></span>
            <span>/</span>
            <span>Edit Configuration</span>
        </div>
        <h1>Edit Settings: <?= htmlspecialchars($tenant['name']) ?></h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('tenants')">Cancel</button>
        <button class="btn bt" onclick="updateTenant()">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
</div>

<form id="editTenantForm" class="fu">
    <input type="hidden" name="id" value="<?= $tenant['id'] ?>">
    
    <div class="g2">
        <!-- LEFT COLUMN -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card">
                <div class="ct"><i class="fas fa-building"></i> Profile & Branding</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:15px;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Institute Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tenant['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subdomain (Read-only)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($tenant['subdomain']) ?>" readonly style="background:#f8fafc; opacity:0.7;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quota Plan</label>
                        <select name="plan" class="form-control" required>
                            <option value="starter" <?= $tenant['plan'] === 'starter' ? 'selected' : '' ?>>Starter</option>
                            <option value="growth" <?= $tenant['plan'] === 'growth' ? 'selected' : '' ?>>Growth</option>
                            <option value="professional" <?= $tenant['plan'] === 'professional' ? 'selected' : '' ?>>Professional</option>
                            <option value="enterprise" <?= $tenant['plan'] === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student Limit</label>
                        <input type="number" name="student_limit" class="form-control" value="<?= $tenant['student_limit'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= $tenant['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $tenant['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="trial" <?= $tenant['status'] === 'trial' ? 'selected' : '' ?>>Trial</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="ct"><i class="fas fa-coins"></i> Resource Allocation</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:15px;">
                    <div class="form-group">
                        <label class="form-label">SMS Credits Balance</label>
                        <input type="number" name="sms_credits" class="form-control" value="<?= $tenant['sms_credits'] ?? 0 ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="card">
                <div class="ct" style="justify-content:space-between;">
                    <span><i class="fas fa-cubes"></i> Feature Alignment</span>
                    <label style="font-size:11px; font-weight:600; cursor:pointer;"><input type="checkbox" onclick="toggleAllModules(this.checked)"> Select All</label>
                </div>
                <p style="font-size:11px; color:var(--text-light); margin-bottom:15px;">Modify feature access for this institute.</p>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; max-height:400px; overflow-y:auto; padding-right:5px;" id="moduleGrid">
                    <?php foreach ($allModules as $mod): ?>
                    <label class="mod-check-item">
                        <input type="checkbox" name="modules[]" value="<?= $mod['id'] ?>" <?= in_array($mod['id'], $assignedModules) ? 'checked' : '' ?>>
                        <span><?= ucfirst($mod['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.mod-check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8fafc;
    border: 1px solid var(--card-border);
    border-radius: 10px;
    cursor: pointer;
    transition: 0.2s;
}
.mod-check-item:hover { background: #f1f5f9; border-color: var(--green); }
.mod-check-item input { accent-color: var(--green); width: 16px; height: 16px; }
.mod-check-item span { font-size: 13px; font-weight: 500; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--card-border); border-radius: 10px; font-size: 13px; font-family: inherit; transition: 0.2s; }
.form-control:focus { border-color: var(--green); outline: none; box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1); }
.form-label { display: block; font-size: 12px; font-weight: 700; color: var(--text-body); margin-bottom: 6px; }
</style>

<script>
function toggleAllModules(checked) {
    document.querySelectorAll('#moduleGrid input[type="checkbox"]').forEach(i => i.checked = checked);
}

async function updateTenant() {
    const form = document.getElementById('editTenantForm');
    if (!form.reportValidity()) return;

    const formData = new FormData(form);
    SuperAdmin.showNotification("Updating configuration...", "loading");

    try {
        const res = await fetch(window.APP_URL + '/api/super-admin/tenants/update', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification("Institute updated successfully!", "success");
            goNav('tenants');
        } else {
            SuperAdmin.showNotification(result.error || "Update failed", "error");
        }
    } catch (e) {
        SuperAdmin.showNotification("Network error", "error");
    }
}
</script>
