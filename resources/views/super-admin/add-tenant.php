<?php
/**
 * ISOFTRO - Super Admin Dashboard (Add New Institute)
 * Multi-section wizard-style form
 */

$PDO = getDBConnection();

// Fetch plan-based features
$features = [];
$growthFeatureKeys = [];

try {
    // Get all active system features
    $stmt = $PDO->query("
        SELECT id, feature_key, feature_name, is_core, status
        FROM system_features
        WHERE status = 'active'
        ORDER BY is_core DESC, feature_name ASC
    ");
    $features = $stmt->fetchAll();

    // Get feature keys included in Growth plan (default)
    $stmt = $PDO->query("
        SELECT feature_key FROM plan_features
        WHERE is_enabled = 1 AND JSON_CONTAINS(plans, '\"growth\"')
    ");
    $growthFeatureKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log("Feature fetch error: " . $e->getMessage());
    $features = [];
    $growthFeatureKeys = [];
}


?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span><a href="#" onclick="goNav('tenants')">Institutes</a></span>
            <span>/</span>
            <span>Onboard New Tenant</span>
        </div>
        <h1>Onboard New Institute</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('tenants')">Cancel</button>
        <button class="btn bt" onclick="submitNewTenant()">
            <i class="fas fa-rocket"></i> Launch Institute
        </button>
    </div>
</div>

<form id="addTenantForm" class="fu">
    <div class="g2">
        <!-- LEFT COLUMN: ORGANIZATION & PLAN -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <!-- CARD 1: BASIC INFO -->
            <div class="card">
                <div class="ct"><i class="fas fa-building"></i> Organization Profile</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:15px;">
                    <div class="form-group" style="grid-column: span 1;">
                        <label class="form-label">Institute Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Kathmandu Model College" required>
                    </div>
                    <div class="form-group" style="grid-column: span 1;">
                        <label class="form-label">Nepali Name</label>
                        <input type="text" name="nepaliName" class="form-control" placeholder="e.g. काठमाडौं मोडल कलेज">
                    </div>
                    <div class="form-group" style="grid-column: span 1;">
                        <label class="form-label">Institute Type *</label>
                        <select name="instituteType" class="form-control" required>
                            <option value="" disabled selected>Select Type</option>
                            <option value="bridge course">Bridge Course Center</option>
                            <option value="loksewa preparation">Loksewa Preparation Center</option>
                            <option value="tuition">Tuition Center</option>
                            <option value="other">Other Training Center</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subdomain *</label>
                        <div style="display:flex; align-items:center;">
                            <input type="text" name="subdomain" class="form-control" placeholder="kmc" required style="border-top-right-radius:0; border-bottom-right-radius:0;">
                            <span style="background:#f1f5f9; border:1px solid var(--card-border); border-left:none; padding:10px; font-size:12px; border-radius:10px; border-top-left-radius:0; border-bottom-left-radius:0;">.isoftro.com</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primary Email *</label>
                        <input type="email" name="email" class="form-control" placeholder="info@kmc.edu.np" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+977-984xxxxxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="panNumber" class="form-control" placeholder="e.g. 123456789">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Brand Color</label>
                        <input type="color" name="brandColor" class="form-control" value="#009E7E" style="padding:2px; height:40px; cursor:pointer;">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Institute Logo (Optional)</label>
                        <input type="file" name="logo" class="form-control" accept="image/*" style="padding: 7px;">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="tagline" class="form-control" placeholder="Empowering Education...">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Institute Address</label>
                        <input type="text" name="address" class="form-control" placeholder="City, Location">
                    </div>
                </div>
            </div>

            <!-- CARD 2: PLAN & QUOTA -->
            <div class="card">
                <div class="ct"><i class="fas fa-star"></i> Subscription Plan & Quota</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-top:15px;">
                    <div class="form-group">
                        <label class="form-label">Initial Plan *</label>
                        <select name="plan" class="form-control" required onchange="updateModulePresets(this.value)">
                            <option value="starter">Starter (LITE)</option>
                            <option value="growth" selected>Growth (Recommended)</option>
                            <option value="professional">Professional (Full)</option>
                            <option value="enterprise">Enterprise (Custom)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student Limit *</label>
                        <input type="number" name="student_limit" class="form-control" value="500" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Billing Cycle</label>
                        <select name="billing_cycle" class="form-control">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly (10% Discount)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initial Status</label>
                        <select name="status" class="form-control">
                            <option value="trial">Free Trial (15 Days)</option>
                            <option value="active" selected>Active</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: MODULES & ADMIN -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <!-- CARD 3: MODULE SELECTION -->
            <div class="card">
                <div class="ct" style="justify-content:space-between;">
                    <span><i class="fas fa-cubes"></i> Feature Modules</span>
                    <label style="font-size:11px; font-weight:600; cursor:pointer;"><input type="checkbox" onclick="toggleAllModules(this.checked)"> Select All</label>
                </div>
                <p style="font-size:11px; color:var(--text-light); margin-bottom:15px;">Enable specific features for this institute.</p>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; max-height:300px; overflow-y:auto; padding-right:5px;" id="moduleGrid">
                    <?php
                    if (!empty($features)):
                        foreach ($features as $f):
                            $isChecked = in_array($f['feature_key'], $growthFeatureKeys) ? 'checked' : '';
                    ?>
                    <label class="mod-check-item">
                        <input type="checkbox" name="features[]" value="<?= (int)$f['id'] ?>"
                               data-slug="<?= htmlspecialchars($f['feature_key']) ?>"
                               data-core="<?= $f['is_core'] ? '1' : '0' ?>"
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

            <!-- CARD 4: ADMIN CREDENTIALS -->
            <div class="card">
                <div class="ct"><i class="fas fa-user-shield"></i> Institute Admin Account</div>
                <p style="font-size:11px; color:var(--text-light); margin-bottom:15px;">Credentials for the first administrator.</p>
                <div style="display:grid; grid-template-columns: 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Admin Full Name *</label>
                        <input type="text" name="adminName" class="form-control" placeholder="e.g. Principal's Name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Login Email *</label>
                        <input type="email" name="adminEmail" class="form-control" placeholder="admin@institute.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Login Password *</label>
                        <?php
                        $lower = 'abcdefghijklmnopqrstuvwxyz';
                        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $num = '0123456789';
                        $special = '@#$!*^';
                        $pass = $lower[rand(0,25)] . $upper[rand(0,25)] . $num[rand(0,9)] . $special[rand(0,5)] . substr(str_shuffle($lower.$upper.$num.$special), 0, 6);
                        $pass = str_shuffle($pass);
                        ?>
                        <input type="text" name="adminPass" class="form-control" value="<?= $pass ?>" required>
                        <small style="color:var(--text-light); display:block; margin-top:5px;">Randomly generated. Institute can change this later.</small>
                    </div>
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
.mod-check-item:hover {
    background: #f1f5f9;
    border-color: var(--green);
}
.mod-check-item input {
    accent-color: var(--green);
    width: 16px;
    height: 16px;
}
.mod-check-item span {
    font-size: 13px;
    font-weight: 500;
}
.form-group {
    margin-bottom: 5px;
}
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--card-border);
    border-radius: 10px;
    font-size: 13px;
    font-family: inherit;
    transition: 0.2s;
}
.form-control:focus {
    border-color: var(--green);
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1);
}
.form-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-body);
    margin-bottom: 6px;
}
</style>

<script>
// Toggle all module checkboxes
function toggleAllModules(checked) {
    document.querySelectorAll('#moduleGrid input.feature-checkbox').forEach(checkbox => {
        checkbox.checked = checked;
    });
}

// Update module presets based on selected plan
async function updateModulePresets(plan) {
    console.log('[TENANT FORM] Updating modules for plan:', plan);

    // Fetch features for selected plan from API
    try {
        const token = sessionStorage.getItem('access_token');
        const response = await fetch(`${window.APP_URL || ''}/api/super/tenants/0/addons`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        // For now, use plan-based presets
        const planPresets = {
            'starter': ['student', 'academic', 'attendance', 'dashboard'],
            'growth': ['student', 'academic', 'attendance', 'dashboard', 'exam', 'homework', 'accounting', 'inquiry', 'frontdesk'],
            'professional': ['student', 'academic', 'attendance', 'dashboard', 'exam', 'homework', 'accounting', 'inquiry', 'frontdesk', 'payroll', 'teacher'],
            'enterprise': [] // All modules enabled
        };

        const enabledSlugs = planPresets[plan] || [];

        // Update checkboxes based on feature slugs
        document.querySelectorAll('#moduleGrid input.feature-checkbox').forEach(checkbox => {
            const slug = checkbox.dataset.slug || '';
            if (plan === 'enterprise') {
                checkbox.checked = true;
            } else {
                checkbox.checked = enabledSlugs.includes(slug.toLowerCase());
            }
        });

        // Update student limit based on plan
        const limits = {'starter': 200, 'growth': 500, 'professional': 2000, 'enterprise': 10000};
        document.querySelector('[name="student_limit"]').value = limits[plan] || 500;

        console.log('[TENANT FORM] Modules updated for plan:', plan);
    } catch (e) {
        console.warn('[TENANT FORM] Could not fetch plan features, using defaults:', e);
        toggleAllModules(plan === 'enterprise');
    }
}

// Submit new tenant form
async function submitNewTenant() {
    const form = document.getElementById('addTenantForm');
    if (!form.reportValidity()) {
        console.error('[TENANT FORM] Form validation failed');
        return;
    }

    const formData = new FormData(form);

    // Show loading indicator
    if (window.SuperAdmin && window.SuperAdmin.showNotification) {
        SuperAdmin.showNotification("🚀 Initiating institute deployment...", "info");
    }

    try {
        // Get JWT token from sessionStorage
        const token = sessionStorage.getItem('access_token');

        if (!token) {
            console.error('[TENANT FORM] No authentication token found');
            throw new Error('Authentication required. Please log in again.');
        }

        // Build request headers
        const headers = new Headers({
            'Authorization': `Bearer ${token}`,
            'X-Requested-With': 'XMLHttpRequest'
        });

        console.log('[TENANT FORM] Submitting new tenant with features:',
            Array.from(formData.getAll('features[]')));

        const response = await fetch(`${window.APP_URL || ''}/api/super-admin/tenants/save`, {
            method: 'POST',
            body: formData,
            headers: headers
        });

        const result = await response.json();

        console.log('[TENANT FORM] Server response:', result);

        if (result.success) {
            if (window.SuperAdmin && window.SuperAdmin.showNotification) {
                SuperAdmin.showNotification("✅ Institute launched successfully!", "success");
            }
            // Redirect to tenants list after 1 second
            setTimeout(() => {
                if (window.goNav) goNav('tenants');
            }, 1000);
        } else {
            const errorMsg = result.error || result.message || "Institute launch failed. Check console for details.";
            console.error('[TENANT FORM] Server error:', errorMsg);
            if (window.SuperAdmin && window.SuperAdmin.showNotification) {
                SuperAdmin.showNotification(errorMsg, "error");
            }
        }
    } catch (e) {
        console.error('[TENANT FORM] Exception occurred:', e);
        const errorMsg = e.message || "Network error. Please check your connection and try again.";
        if (window.SuperAdmin && window.SuperAdmin.showNotification) {
            SuperAdmin.showNotification(errorMsg, "error");
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('[TENANT FORM] Initializing form...');
    // Pre-select modules for default plan (Growth)
    const planSelect = document.querySelector('[name="plan"]');
    if (planSelect) {
        updateModulePresets(planSelect.value);
    }
});
</script>
