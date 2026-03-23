<?php
/**
 * ISOFTRO - Plan Management
 * Variables: $plans, $systemFeatures
 */
?>

<div class="pg-hdr">
    <div>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span>Plans</span>
        </div>
        <h1>Subscription Plans</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="openPlanModal()"><i class="fas fa-plus mr-10"></i> Add New Plan</button>
    </div>
</div>

<div class="g4 mt-20" id="plansGrid">
    <?php foreach ($plans as $p): ?>
    <div class="card p-20 plan-card" data-id="<?= $p['id'] ?>" style="display:flex; flex-direction:column; gap:15px; border-top: 4px solid <?= $p['is_featured'] ? '#00B894' : '#E2E8F0' ?>;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <h3 style="margin:0; font-size:18px; color:var(--text-dark);"><?= htmlspecialchars($p['name']) ?></h3>
                    <?php if ($p['badge_text']): ?>
                        <span class="badge badge-success" style="font-size:10px;"><?= htmlspecialchars($p['badge_text']) ?></span>
                    <?php endif; ?>
                </div>
                <span style="font-size:11px; font-weight:700; color:var(--text-light); text-transform:uppercase;"><?= $p['active_tenants'] ?> ACTIVE INSTITUTES</span>
            </div>
            <div style="text-align:right;">
                <div style="font-size:22px; font-weight:800; color:var(--sa-primary);">Rs. <?= number_format($p['price']) ?></div>
                <div style="font-size:10px; color:var(--text-light); font-weight:600;">PER MONTH</div>
            </div>
        </div>

        <div style="background:#f8fafc; padding:15px; border-radius:12px; display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; justify-content:space-between; font-size:13px;">
                <span style="color:var(--text-light);"><i class="fas fa-users-viewfinder mr-10"></i> Max Students</span>
                <span style="font-weight:700; color:var(--text-dark);"><?= $p['students'] == 0 ? 'Unlimited' : $p['students'] ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13px;">
                <span style="color:var(--text-light);"><i class="fas fa-layer-group mr-10"></i> Plan Status</span>
                <span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($p['status']) ?></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top: auto;">
            <button class="btn bs sm" onclick="openPlanModal(<?= htmlspecialchars(json_encode($p)) ?>)">
                <i class="fas fa-edit mr-5"></i> Edit Details
            </button>
            <button class="btn bs sm" onclick="manageSystemFeatures(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">
                <i class="fas fa-shield-halved mr-5"></i> Core Access
            </button>
            <button class="btn bs sm" onclick="manageDisplayFeatures(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">
                <i class="fas fa-list-check mr-5"></i> Display Features
            </button>
            <button class="btn bs sm text-danger" onclick="deletePlan(<?= $p['id'] ?>)">
                <i class="fas fa-trash-can"></i> Delete
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mt-20">
    <div class="ct"><i class="fas fa-toggle-on"></i> Global Module Toggles</div>
    <p style="font-size:12px; color:var(--text-light); margin-bottom:15px;">Enable or disable system-wide modules instantly. This affects all institutes regardless of their plan.</p>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:15px;">
        <?php foreach ($systemFeatures as $sf): ?>
        <label class="toggle-card">
            <input type="checkbox" <?= $sf['status'] === 'active' ? 'checked' : '' ?> onchange="toggleGlobalFeature('<?= $sf['feature_key'] ?>', this.checked)">
            <div class="toggle-content">
                <i class="fas fa-cube"></i>
                <div>
                    <span style="display:block;"><?= htmlspecialchars($sf['feature_name']) ?></span>
                    <small style="color:var(--text-light); font-size:10px;"><?= $sf['is_core'] ? 'CORE MODULE' : 'OPTIONAL' ?></small>
                </div>
            </div>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Plan Details -->
<div id="planModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:500px;">
        <div class="mh">
            <h2 id="planModalTitle">Add/Edit Plan</h2>
            <button onclick="closeModal('planModal')">&times;</button>
        </div>
        <form id="planForm" onsubmit="savePlan(event)">
            <input type="hidden" name="id" id="plan_id">
            <div class="mb-15">
                <label>Plan Name</label>
                <input type="text" name="name" id="plan_name" placeholder="e.g. Growth Plan" required class="form-control">
            </div>
            <div class="mb-15">
                <label>Internal Slug (Unique)</label>
                <input type="text" name="slug" id="plan_slug" placeholder="growth" required class="form-control">
            </div>
            <div class="g2">
                <div class="mb-15">
                    <label>Monthly Price (Rs.)</label>
                    <input type="number" name="price_monthly" id="plan_price" required class="form-control">
                </div>
                <div class="mb-15">
                    <label>Student Limit (0 = Unlimited)</label>
                    <input type="number" name="student_limit" id="plan_limit" required class="form-control">
                </div>
            </div>
            <div class="mb-15">
                <label>Description</label>
                <textarea name="description" id="plan_desc" rows="2" class="form-control"></textarea>
            </div>
            <div class="mb-15">
                <label>Badge Text (Optional)</label>
                <input type="text" name="badge_text" id="plan_badge" placeholder="e.g. Best Value" class="form-control">
            </div>
            <div class="g2">
                <label class="mb-15" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="is_featured" id="plan_featured"> Featured Plan
                </label>
                <div class="mb-15">
                    <label>Status</label>
                    <select name="status" id="plan_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="bt-r">
                <button type="button" class="btn bs" onclick="closeModal('planModal')">Cancel</button>
                <button type="submit" class="btn bt">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Display Features -->
<div id="displayFeaturesModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:500px;">
        <div class="mh">
            <h2 id="dispFeaturesTitle">Pricing Table Features</h2>
            <button onclick="closeModal('displayFeaturesModal')">&times;</button>
        </div>
        <p style="font-size:12px; color:var(--text-light); margin-bottom:15px;">These are displayed on the public pricing page/landing page.</p>
        <div id="dispFeaturesList" style="display:flex; flex-direction:column; gap:10px; max-height:400px; overflow-y:auto; padding:5px;">
            <!-- Dynamic list -->
        </div>
        <button class="btn bs sm mt-15" onclick="addFeatureRow()"><i class="fas fa-plus"></i> Add Feature Line</button>
        <div class="bt-r mt-20">
            <button class="btn bs" onclick="closeModal('displayFeaturesModal')">Cancel</button>
            <button class="btn bt" onclick="saveDisplayFeatures()">Save Features</button>
        </div>
    </div>
</div>

<!-- Modal: System Features (Core Access) -->
<div id="systemFeaturesModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:500px;">
        <div class="mh">
            <h2 id="sysFeaturesTitle">Core Module Access</h2>
            <button onclick="closeModal('systemFeaturesModal')">&times;</button>
        </div>
        <p style="font-size:12px; color:var(--text-light); margin-bottom:15px;">Select which actual system modules are included in this plan.</p>
        <div id="sysFeaturesList" style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <!-- Dynamic list -->
        </div>
        <div class="bt-r mt-20">
            <button class="btn bs" onclick="closeModal('systemFeaturesModal')">Cancel</button>
            <button class="btn bt" onclick="saveSystemFeatures()">Apply Changes</button>
        </div>
    </div>
</div>

<style>
.modal { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); animation: fadeIn 0.2s; }
.modal-content { background: white; border-radius: 16px; padding: 25px; width: 95%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.mh { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
.mh h2 { font-size: 18px; margin: 0; color: #1e293b; }
.mh button { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; }
.form-control { width: 100%; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-top: 5px; }
.bt-r { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
.badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; color: white; display: inline-block; }
.badge-success { background: #00B894; }
.badge-danger { background: #FF7675; }

.toggle-card { cursor: pointer; }
.toggle-card input { display: none; }
.toggle-content { padding: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; align-items: center; gap: 12px; transition: 0.2s; }
.toggle-content i { font-size: 18px; color: #94a3b8; }
.toggle-content span { font-size: 13px; font-weight: 600; color: #1e293b; }
.toggle-card input:checked + .toggle-content { background: #f0fdf4; border-color: #22c55e; }
.toggle-card input:checked + .toggle-content i { color: #22c55e; }

.plan-card { transition: transform 0.2s, box-shadow 0.2s; }
.plan-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>

<script>
let currentPlanId = null;

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function openPlanModal(plan = null) {
    document.getElementById('planModal').style.display = 'flex';
    document.getElementById('planForm').reset();
    
    if (plan) {
        document.getElementById('planModalTitle').innerText = 'Edit Plan: ' + plan.name;
        document.getElementById('plan_id').value = plan.id;
        document.getElementById('plan_name').value = plan.name;
        document.getElementById('plan_slug').value = plan.slug;
        document.getElementById('plan_price').value = plan.price;
        document.getElementById('plan_limit').value = plan.students;
        document.getElementById('plan_desc').value = plan.description;
        document.getElementById('plan_badge').value = plan.badge_text;
        document.getElementById('plan_featured').checked = plan.is_featured;
        document.getElementById('plan_status').value = plan.status;
    } else {
        document.getElementById('planModalTitle').innerText = 'Add New Plan';
        document.getElementById('plan_id').value = '';
    }
}

async function savePlan(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.is_featured = e.target.is_featured.checked;
    
    try {
        const res = await fetch('app/Http/Controllers/SuperAdmin/PlansApi.php?action=save', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 
                'Content-Type': 'application/json'
            }
        });
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification(result.message, 'success');
            location.reload();
        } else {
            SuperAdmin.showNotification(result.message, 'error');
        }
    } catch (err) {
        SuperAdmin.showNotification('Error saving plan', 'error');
    }
}

async function deletePlan(id) {
    if (!confirm('Are you sure you want to delete this plan? This action cannot be undone.')) return;
    
    try {
        const res = await fetch(`app/Http/Controllers/SuperAdmin/PlansApi.php?action=delete&id=${id}`, {
            method: 'POST'
        });
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification(result.message, 'success');
            location.reload();
        } else {
            SuperAdmin.showNotification(result.message, 'error');
        }
    } catch (err) {
        SuperAdmin.showNotification('Error deleting plan', 'error');
    }
}

// Display Features (Pricing lines)
async function manageDisplayFeatures(id, name) {
    currentPlanId = id;
    document.getElementById('displayFeaturesModal').style.display = 'flex';
    document.getElementById('dispFeaturesTitle').innerText = name + ': Features';
    document.getElementById('dispFeaturesList').innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    try {
        const res = await fetch(`app/Http/Controllers/SuperAdmin/PlansApi.php?action=get&id=${id}`);
        const result = await res.json();
        if (result.success) {
            renderDisplayFeatures(result.data.features || []);
        }
    } catch (err) {
        SuperAdmin.showNotification('Error loading features', 'error');
    }
}

function renderDisplayFeatures(features) {
    const list = document.getElementById('dispFeaturesList');
    list.innerHTML = '';
    
    if (features.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding:20px; color:#94a3b8; font-size:13px;">No feature lines added yet.</div>';
    }
    
    features.forEach((f, index) => {
        addFeatureRow(f.feature_text, f.is_included == 1);
    });
}

function addFeatureRow(text = '', included = true) {
    const list = document.getElementById('dispFeaturesList');
    const div = document.createElement('div');
    div.style.display = 'flex';
    div.style.gap = '10px';
    div.style.alignItems = 'center';
    div.innerHTML = `
        <input type="checkbox" class="feat-inc" ${included ? 'checked' : ''} style="width:20px; height:20px;">
        <input type="text" class="feat-txt form-control" style="margin-top:0;" value="${text}" placeholder="e.g. Up to 500 students">
        <button onclick="this.parentElement.remove()" style="color:#ef4444; background:none; border:none; cursor:pointer;"><i class="fas fa-trash"></i></button>
    `;
    
    if (list.querySelector('div[style*="text-align:center"]')) {
        list.innerHTML = '';
    }
    list.appendChild(div);
}

async function saveDisplayFeatures() {
    const rows = document.querySelectorAll('#dispFeaturesList > div');
    const features = Array.from(rows).map(row => ({
        text: row.querySelector('.feat-txt').value,
        is_included: row.querySelector('.feat-inc').checked
    })).filter(f => f.text.trim() !== '');
    
    try {
        const res = await fetch('app/Http/Controllers/SuperAdmin/PlansApi.php?action=update_display_features', {
            method: 'POST',
            body: JSON.stringify({ plan_id: currentPlanId, features }),
            headers: { 
                'Content-Type': 'application/json'
            }
        });
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification(result.message, 'success');
            closeModal('displayFeaturesModal');
        }
    } catch (err) {
        SuperAdmin.showNotification('Error saving features', 'error');
    }
}

// System Features (Core Access)
async function manageSystemFeatures(id, name) {
    currentPlanId = id;
    document.getElementById('systemFeaturesModal').style.display = 'flex';
    document.getElementById('sysFeaturesTitle').innerText = name + ': Module Access';
    document.getElementById('sysFeaturesList').innerHTML = '<div style="text-align:center; padding:20px; grid-column: 1 / span 2;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    try {
        const res = await fetch(`app/Http/Controllers/SuperAdmin/PlansApi.php?action=get_system_features&id=${id}`);
        const result = await res.json();
        if (result.success) {
            renderSystemFeatures(result.all, result.enabled);
        }
    } catch (err) {
        SuperAdmin.showNotification('Error loading core modules', 'error');
    }
}

function renderSystemFeatures(all, enabled) {
    const list = document.getElementById('sysFeaturesList');
    list.innerHTML = '';
    
    all.forEach(f => {
        const isEnabled = enabled.includes(f.id.toString()) || enabled.includes(parseInt(f.id));
        const div = document.createElement('label');
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.gap = '10px';
        div.style.background = '#f8fafc';
        div.style.padding = '8px 12px';
        div.style.borderRadius = '8px';
        div.style.cursor = 'pointer';
        div.style.fontSize = '13px';
        div.innerHTML = `
            <input type="checkbox" value="${f.id}" class="sys-feat-check" ${isEnabled ? 'checked' : ''}>
            <span>${f.feature_name}</span>
        `;
        list.appendChild(div);
    });
}

async function saveSystemFeatures() {
    const checks = document.querySelectorAll('.sys-feat-check:checked');
    const featureIds = Array.from(checks).map(c => c.value);
    
    try {
        const res = await fetch('app/Http/Controllers/SuperAdmin/PlansApi.php?action=update_system_features', {
            method: 'POST',
            body: JSON.stringify({ plan_id: currentPlanId, feature_ids: featureIds }),
            headers: { 
                'Content-Type': 'application/json'
            }
        });
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification(result.message, 'success');
            closeModal('systemFeaturesModal');
        }
    } catch (err) {
        SuperAdmin.showNotification('Error saving module access', 'error');
    }
}

// Global Toggle
async function toggleGlobalFeature(key, status) {
    try {
        const res = await fetch('app/Http/Controllers/SuperAdmin/PlansApi.php?action=toggle_system_feature', {
            method: 'POST',
            body: JSON.stringify({ feature_key: key, status: status ? 'active' : 'inactive' }),
            headers: { 
                'Content-Type': 'application/json'
            }
        });
        const result = await res.json();
        if (result.success) {
            SuperAdmin.showNotification(result.message, 'success');
        }
    } catch (err) {
        SuperAdmin.showNotification('Error updating global toggle', 'error');
    }
}
</script>
