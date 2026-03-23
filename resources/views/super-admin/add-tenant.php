<?php
/**
 * ISOFTRO - Super Admin Dashboard (Add New Institute)
 * Multi-section wizard-style form
 */

$PDO = getDBConnection();

// Fetch available features for selection
try {
    $stmt = $PDO->query("SELECT * FROM system_features ORDER BY feature_name ASC");
    $features = $stmt->fetchAll();
} catch (Exception $e) {
    $features = [];
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
                    <?php foreach ($features as $f): ?>
                    <label class="mod-check-item">
                        <input type="checkbox" name="features[]" value="<?= $f['id'] ?>" data-slug="<?= $f['feature_key'] ?>" <?= $f['is_core'] ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($f['feature_name']) ?></span>
                    </label>
                    <?php endforeach; ?>

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
function toggleAllModules(checked) {
    document.querySelectorAll('#moduleGrid input[type="checkbox"]').forEach(i => i.checked = checked);
}

function updateModulePresets(plan) {
    const starterModules = ['attendance', 'inquiry', 'students', 'accounting', 'system', 'dashboard', 'academic'];
    const growthModules = [...starterModules, 'exams', 'homework', 'lms', 'reports', 'frontdesk', 'payroll'];
    
    if (plan === 'starter') {
        document.querySelectorAll('#featureGrid input').forEach(i => {
            const slug = i.dataset.slug ? i.dataset.slug.toLowerCase() : '';
            i.checked = starterModules.includes(slug);
        });
        document.querySelector('[name="student_limit"]').value = 200;
    } else if (plan === 'growth') {
        document.querySelectorAll('#featureGrid input').forEach(i => {
            const slug = i.dataset.slug ? i.dataset.slug.toLowerCase() : '';
            i.checked = growthModules.includes(slug);
        });
        document.querySelector('[name="student_limit"]').value = 500;
    } else if (plan === 'professional') {
        toggleAllModules(true);
        document.querySelector('[name="student_limit"]').value = 2000;
    } else {
        document.querySelector('[name="student_limit"]').value = 10000;
    }
}



async function submitNewTenant() {
    const form = document.getElementById('addTenantForm');
    if (!form.reportValidity()) return;

    const formData = new FormData(form);
    
    SuperAdmin.showNotification("Initiating deployment...", "info");

    try {
        const csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch(window.APP_URL + '/api/super-admin/tenants/save', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification("Institute launched successfully!", "success");
            goNav('tenants');
        } else {
            SuperAdmin.showNotification(result.error || result.message || "Launch failed", "error");
        }
    } catch (e) {
        console.error(e);
        SuperAdmin.showNotification("Network error", "error");
    }
}
</script>
