<?php
/**
 * ISOFTRO - Plan Management
 * Variable: $plans
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
        <button class="btn bt" onclick="SuperAdmin.showNotification('Feature coming soon...', 'info')"><i class="fas fa-plus"></i> Add New Plan</button>
    </div>
</div>

<div class="g4 mt-20">
    <?php foreach ($plans as $p): ?>
    <div class="card p-20" style="display:flex; flex-direction:column; gap:15px; border-top: 4px solid var(--sa-primary, #00B894);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h3 style="margin:0; font-size:18px; color:var(--text-dark);"><?= htmlspecialchars($p['name']) ?></h3>
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
                <span style="font-weight:700; color:var(--text-dark);"><?= $p['students'] == -1 ? 'Unlimited' : $p['students'] ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13px;">
                <span style="color:var(--text-light);"><i class="fas fa-comment-sms mr-10"></i> Included SMS</span>
                <span style="font-weight:700; color:var(--text-dark);"><?= $p['sms'] == -1 ? 'Unlimited' : $p['sms'] ?></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
            <button class="btn bs sm" onclick="editPlan('<?= $p['id'] ?>')">Manage Features</button>
            <button class="btn bs" style="border-color:#e2e8f0;">Pricing Settings</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card mt-20">
    <div class="ct"><i class="fas fa-shield-halved"></i> Global Feature Toggles</div>
    <p style="font-size:12px; color:var(--text-light); margin-bottom:15px;">Enable or disable features across all plans instantly.</p>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px;">
        <label class="toggle-card">
            <input type="checkbox" checked>
            <div class="toggle-content">
                <i class="fas fa-chart-line"></i>
                <span>Advanced Analytics</span>
            </div>
        </label>
        <label class="toggle-card">
            <input type="checkbox" checked>
            <div class="toggle-content">
                <i class="fas fa-mobile-screen"></i>
                <span>Mobile App Access</span>
            </div>
        </label>
        <label class="toggle-card">
            <input type="checkbox">
            <div class="toggle-content">
                <i class="fas fa-robot"></i>
                <span>AI Automated Grading</span>
            </div>
        </label>
    </div>
</div>

<style>
.toggle-card { cursor: pointer; }
.toggle-card input { display: none; }
.toggle-content { padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; align-items: center; gap: 12px; transition: 0.2s; }
.toggle-content i { font-size: 16px; color: #475569; }
.toggle-content span { font-size: 13px; font-weight: 600; color: #1e293b; }
.toggle-card input:checked + .toggle-content { background: #f0fdf4; border-color: #22c55e; }
.toggle-card input:checked + .toggle-content i { color: #22c55e; }
</style>

<script>
function editPlan(planId) {
    SuperAdmin.showNotification("Feature Configuration loading for " + planId, "info");
}
</script>
