<?php
/**
 * ISOFTRO - Super Admin Dashboard (Edit Institute)
 */

$PDO = getDBConnection();

// Fetch available features with proper schema
$allFeatures = [];
$assignedModuleIds = $assignedModules ?? [];

try {
    // Get all active system features
    $stmt = $PDO->query("
        SELECT id, feature_key, feature_name, category, status
        FROM system_features
        WHERE status = 'active'
        ORDER BY category, feature_name ASC
    ");
    $allFeatures = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Feature fetch error in edit-tenant: " . $e->getMessage());
    $allFeatures = [];
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
                        <label class="form-label">Institute Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($tenant['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nepali Name</label>
                        <input type="text" name="nepaliName" class="form-control" value="<?= htmlspecialchars($tenant['nepali_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subdomain * (Read-only)</label>
                        <input type="text" name="subdomain" class="form-control" value="<?= htmlspecialchars($tenant['subdomain']) ?>" readonly style="background:#f8fafc; opacity:0.7;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Institute Type *</label>
                        <select name="instituteType" class="form-control" required>
                            <option value="bridge course" <?= $tenant['institute_type'] === 'bridge course' ? 'selected' : '' ?>>Bridge Course Center</option>
                            <option value="loksewa preparation" <?= $tenant['institute_type'] === 'loksewa preparation' ? 'selected' : '' ?>>Loksewa Preparation Center</option>
                            <option value="tuition" <?= $tenant['institute_type'] === 'tuition' ? 'selected' : '' ?>>Tuition Center</option>
                            <option value="other" <?= $tenant['institute_type'] === 'other' ? 'selected' : '' ?>>Other Training Center</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Primary Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($tenant['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($tenant['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="panNumber" class="form-control" value="<?= htmlspecialchars($tenant['pan_number'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 1;">
                        <label class="form-label">Brand Color</label>
                        <input type="color" name="brandColor" class="form-control" value="<?= htmlspecialchars($tenant['brand_color'] ?? '#009e7e') ?>" style="padding:2px; height:40px; cursor:pointer;">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Institute Logo (Optional)</label>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <?php if(!empty($tenant['logo_path'])): ?>
                                <img src="<?= APP_URL . htmlspecialchars($tenant['logo_path']) ?>" style="height:40px; border-radius:4px; border:1px solid #ccc; object-fit:contain;">
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept="image/*" style="padding: 7px;">
                        </div>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($tenant['tagline'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($tenant['address'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="ct"><i class="fas fa-cogs"></i> Preferences & Status</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:15px;">
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
                        <label class="form-label">Student Limit</label>
                        <input type="number" name="student_limit" class="form-control" value="<?= $tenant['student_limit'] ?>" required>
                    </div>
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
                    <?php if (!empty($allFeatures)):
                        foreach ($allFeatures as $f):
                            $isChecked = in_array($f['id'], $assignedModuleIds) ? 'checked' : '';
                    ?>
                    <label class="mod-check-item">
                        <input type="checkbox" name="features[]" value="<?= htmlspecialchars($f['id']) ?>"
                               data-slug="<?= htmlspecialchars($f['feature_key']) ?>"
                               data-category="<?= htmlspecialchars($f['category']) ?>"
                               <?= $isChecked ?>
                               class="feature-checkbox">
                        <span><?= htmlspecialchars($f['feature_name']) ?></span>
                    </label>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div style="grid-column: 1/-1; padding:20px; text-align:center; color:var(--text-light);">
                        <p>📦 Features loading... If this persists, check the console (F12) for errors.</p>
                    </div>
                    <?php endif; ?>
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
// Toggle all module checkboxes
function toggleAllModules(checked) {
    document.querySelectorAll('#moduleGrid input.feature-checkbox').forEach(checkbox => {
        checkbox.checked = checked;
    });
}

// Update tenant configuration
async function updateTenant() {
    const form = document.getElementById('editTenantForm');
    if (!form.reportValidity()) {
        console.error('[EDIT TENANT] Form validation failed');
        return;
    }

    const formData = new FormData(form);

    // Show loading indicator
    if (window.SuperAdmin && window.SuperAdmin.showNotification) {
        SuperAdmin.showNotification("🔄 Updating institute configuration...", "info");
    }

    try {
        // Get JWT token from sessionStorage
        const token = sessionStorage.getItem('access_token');

        if (!token) {
            console.error('[EDIT TENANT] No authentication token found');
            throw new Error('Authentication required. Please log in again.');
        }

        // Build request headers
        const headers = new Headers({
            'Authorization': `Bearer ${token}`,
            'X-Requested-With': 'XMLHttpRequest'
        });

        const tenantId = document.querySelector('[name="id"]').value;

        console.log('[EDIT TENANT] Updating tenant with ID:', tenantId);

        const response = await fetch(`${window.APP_URL || ''}/api/super/tenants/${tenantId}`, {
            method: 'PUT',
            body: formData,
            headers: headers
        });

        const result = await response.json();

        console.log('[EDIT TENANT] Server response:', result);

        if (result.success) {
            if (window.SuperAdmin && window.SuperAdmin.showNotification) {
                SuperAdmin.showNotification("✅ Institute updated successfully!", "success");
            }
            // Redirect to tenants list after 1 second
            setTimeout(() => {
                if (window.goNav) goNav('tenants');
            }, 1000);
        } else {
            const errorMsg = result.error || result.message || "Institute update failed. Check console for details.";
            console.error('[EDIT TENANT] Server error:', errorMsg);
            if (window.SuperAdmin && window.SuperAdmin.showNotification) {
                SuperAdmin.showNotification(errorMsg, "error");
            }
        }
    } catch (e) {
        console.error('[EDIT TENANT] Exception occurred:', e);
        const errorMsg = e.message || "Network error. Please check your connection and try again.";
        if (window.SuperAdmin && window.SuperAdmin.showNotification) {
            SuperAdmin.showNotification(errorMsg, "error");
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('[EDIT TENANT] Form initialized');
});
</script>
