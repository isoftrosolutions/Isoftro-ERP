<?php
/**
 * ISOFTRO - Super Admin Dashboard (Institute Profile)
 * Variables: $tenant, $assignedModules, $auditLogs
 */

if (!$tenant) {
    echo "<div class='card p-20'>Institute not found.</div>";
    return;
}

$statusColor = 'bg-green';
if ($tenant['status'] === 'suspended') $statusColor = 'bg-red';
if ($tenant['status'] === 'trial') $statusColor = 'bg-amber';

// Mock stats if not available in $tenant array
$tenant['student_count'] = $tenant['student_count'] ?? 0;
$tenant['teacher_count'] = $tenant['teacher_count'] ?? 0;
?>

<div class="pg-hdr">
    <div style="display:flex; align-items:center; gap:20px;">
        <div class="inst-av">
            <?= strtoupper(substr($tenant['name'], 0, 1)) ?>
        </div>
        <div>
            <div class="breadcrumb">
                <i class="fas fa-home"></i>
                <span><a href="#" onclick="goNav('tenants')">Institutes</a></span>
                <span>/</span>
                <span>Profile</span>
            </div>
            <h1 style="margin-bottom:5px;"><?= htmlspecialchars($tenant['name']) ?></h1>
            <div style="display:flex; gap:10px; align-items:center;">
                <span class="bdg <?= $statusColor ?>"><?= strtoupper($tenant['status']) ?></span>
                <span style="font-size:12px; color:var(--text-light);"><i class="fas fa-globe"></i> <?= $tenant['subdomain'] ?>.isoftro.com</span>
            </div>
        </div>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('tenants')"><i class="fas fa-arrow-left"></i> Back</button>
        <button class="btn bt" onclick="goNav('edit-tenant', {id: <?= $tenant['id'] ?>})"><i class="fas fa-edit"></i> Edit Account</button>
        <button class="btn" style="background:var(--purple); color:white; border:none;" onclick="impersonateTenant(<?= $tenant['id'] ?>)">
            <i class="fas fa-user-secret"></i> Impersonate
        </button>
    </div>
</div>

<div class="g65 mt-20">
    <!-- LEFT COLUMN: CORE INFO -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        
        <!-- KEY METRICS -->
        <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); gap:15px; grid-template-areas: none; max-width: none;">
            <div class="card p-15" style="text-align:center;">
                <div style="font-size:11px; color:var(--text-light); font-weight:700; text-transform:uppercase;">Students</div>
                <div style="font-size:1.5rem; font-weight:800; color:var(--green);"><?= $tenant['student_count'] ?></div>
                <div style="font-size:10px; color:var(--text-light);">Limit: <?= $tenant['student_limit'] ?></div>
            </div>
            <div class="card p-15" style="text-align:center;">
                <div style="font-size:11px; color:var(--text-light); font-weight:700; text-transform:uppercase;">SMS Credits</div>
                <div style="font-size:1.5rem; font-weight:800; color:var(--purple);"><?= number_format($tenant['sms_credits'] ?? 0) ?></div>
                <div style="font-size:10px; color:var(--text-light);">Units Remaining</div>
            </div>
            <div class="card p-15" style="text-align:center;">
                <div style="font-size:11px; color:var(--text-light); font-weight:700; text-transform:uppercase;">Subscription</div>
                <div style="font-size:1.2rem; font-weight:800; color:#3b82f6; margin-top:5px;"><?= ucfirst($tenant['plan']) ?></div>
                <div style="font-size:10px; color:var(--text-light);">Tier Level</div>
            </div>
        </div>

        <!-- DETAILS CARD -->
        <div class="card">
            <div class="ct"><i class="fas fa-info-circle"></i> Institute Details</div>
            <div class="g2" style="margin-top:15px; gap:30px;">
                <div>
                    <label class="lbl">Administrator Email</label>
                    <div class="val"><?= htmlspecialchars($tenant['email'] ?? 'N/A') ?></div>
                    
                    <label class="lbl">Primary Phone</label>
                    <div class="val"><?= htmlspecialchars($tenant['phone'] ?? 'N/A') ?></div>
                    
                    <label class="lbl">Full Address</label>
                    <div class="val"><?= htmlspecialchars($tenant['address'] ?? 'Street, Nepal') ?></div>
                </div>
                <div>
                    <label class="lbl">Registered On</label>
                    <div class="val"><?= date('M d, Y', strtotime($tenant['created_at'])) ?></div>
                    
                    <label class="lbl">Last Modified</label>
                    <div class="val"><?= isset($tenant['updated_at']) ? date('M d, Y H:i', strtotime($tenant['updated_at'])) : 'N/A' ?></div>
                    
                    <label class="lbl">API Key / Access Point</label>
                    <code>st_<?= substr(md5($tenant['id']), 0, 16) ?>...</code>
                </div>
            </div>
        </div>

        <!-- MODULES CARD -->
        <div class="card">
            <div class="ct"><i class="fas fa-cubes"></i> Enabled Modules</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:15px;" id="profileModuleList">
                <?php 
                $PDO = getDBConnection();
                $stmt = $PDO->prepare("SELECT feature_name FROM system_features WHERE id IN (".(!empty($assignedModules) ? implode(',', array_fill(0, count($assignedModules), '?')) : '0').")");
                $stmt->execute($assignedModules);
                $feats = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($feats)): ?>
                    <p style="font-size:13px; color:var(--text-light);">No custom features enabled.</p>
                <?php else: foreach($feats as $f): ?>
                    <span class="mod-pill"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($f) ?></span>
                <?php endforeach; endif; ?>

            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: ACTIVITY & ALERTS -->
    <div style="display:flex; flex-direction:column; gap:20px;">
        <!-- RECENT ACTIVITY -->
        <div class="card" style="flex:1;">
            <div class="ct"><i class="fas fa-history"></i> Recent Activity</div>
            <div class="activity-feed">
                <?php if(empty($auditLogs)): ?>
                    <div style="padding:20px; text-align:center; color:var(--text-light); font-size:13px;">No recent activity recorded.</div>
                <?php else: foreach($auditLogs as $log): ?>
                <div class="feed-item">
                    <div class="feed-ico"><i class="fas fa-circle-notch"></i></div>
                    <div class="feed-body">
                        <div class="feed-desc"><strong><?= ucfirst(str_replace('_', ' ', $log['action'])) ?></strong> by <?= $log['user_email'] ?></div>
                        <div class="feed-meta"><?= date('M d, H:i', strtotime($log['created_at'])) ?> · IP: <?= $log['ip_address'] ?></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- ACCOUNT ACTIONS -->
        <div class="card" style="border-top: 4px solid var(--red);">
            <div class="ct" style="color:var(--red);"><i class="fas fa-exclamation-triangle"></i> Danger Zone</div>
            <div style="margin-top:15px; display:flex; flex-direction:column; gap:10px;">
                <?php if($tenant['status'] === 'active'): ?>
                <button class="btn btn-red" style="justify-content:center;" onclick="updateInstituteStatus(<?= $tenant['id'] ?>, 'suspend')">
                    <i class="fas fa-ban"></i> Suspend Account
                </button>
                <?php else: ?>
                <button class="btn btn-green" style="justify-content:center;" onclick="updateInstituteStatus(<?= $tenant['id'] ?>, 'activate')">
                    <i class="fas fa-check"></i> Activate Account
                </button>
                <?php endif; ?>
                
                <button class="btn" style="border-color:#ef4444; color:#ef4444; justify-content:center;" onclick="deleteInstitute(<?= $tenant['id'] ?>)">
                    <i class="fas fa-trash"></i> Delete Permanent
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.inst-av { width: 64px; height: 64px; background: linear-gradient(135deg, var(--green), #036b52); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: white; box-shadow: 0 10px 20px rgba(0, 184, 148, 0.2); }
.lbl { display: block; font-size: 11px; font-weight: 700; color: var(--text-light); text-transform: uppercase; margin-bottom: 4px; }
.val { font-size: 14px; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; }
.mod-pill { padding: 6px 12px; background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 20px; color: var(--green); font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.activity-feed { display: flex; flex-direction: column; gap: 0; margin-top: 10px; }
.feed-item { display: flex; gap: 12px; padding: 12px 15px; border-bottom: 1px solid #f1f5f9; position: relative; }
.feed-item:last-child { border-bottom: none; }
.feed-ico { width: 10px; height: 10px; border-radius: 50%; background: var(--green); margin-top: 5px; flex-shrink: 0; }
.feed-desc { font-size: 13px; color: var(--text-dark); line-height: 1.4; }
.feed-meta { font-size: 10px; color: var(--text-light); margin-top: 2px; }
.btn-red { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 700; }
.btn-red:hover { background: #fecaca; }
</style>

<script>
async function impersonateTenant(id) {
    const confirm = await Swal.fire({
        title: 'Impersonate Institute?',
        text: "You will be logged in as the primary admin of this institute.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00B894',
        confirmButtonText: 'Yes, Impersonate'
    });

    if (!confirm.isConfirmed) return;

    try {
        Swal.showLoading();

        // Secure token-based impersonation
        const res = await SuperAdmin.fetchAPI(`/api/super-admin/impersonate/${id}`, 'POST');

        if (res.success && res.token) {
            // Open new session with token
            window.open(`${window.APP_URL}/impersonate-login?token=${res.token}`, '_blank');
        } else {
            throw new Error(res.message || 'Impersonation failed');
        }

    } catch (e) {
        Swal.fire('Error', e.message || 'Something went wrong', 'error');
    }
}

async function updateInstituteStatus(id, action) {
    const confirm = await Swal.fire({
        title: `Are you sure you want to ${action}?`,
        text: `This will ${action === 'suspend' ? 'disable' : 'restore'} all user access.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `Continue ${action}`
    });

    if (!confirm.isConfirmed) return;

    try {
        Swal.showLoading();

        const res = await SuperAdmin.fetchAPI(
            `/api/super-admin/tenants/${action}/${id}`, 
            'POST'
        );

        if (res.success) {
            Swal.fire('Success', `Account ${action}ed successfully`, 'success');
            goNav('view-tenant', { id });
        } else {
            throw new Error(res.message);
        }

    } catch (e) {
        Swal.fire('Error', e.message || 'Action failed', 'error');
    }
}

async function deleteInstitute(id) {
    const confirm = await Swal.fire({
        title: 'IRREVERSIBLE ACTION!',
        text: "Type 'DELETE' to confirm permanent removal.",
        input: 'text',
        inputValidator: (value) => {
            if (value !== 'DELETE') return 'You must type DELETE';
        },
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Delete Permanently'
    });

    if (!confirm.isConfirmed) return;

    try {
        Swal.showLoading();

        const res = await SuperAdmin.fetchAPI(
            `/api/super-admin/tenants/delete/${id}`, 
            'DELETE'
        );

        if (res.success) {
            Swal.fire('Deleted', 'Institute removed permanently', 'success');
            goNav('tenants');
        } else {
            throw new Error(res.message);
        }

    } catch (e) {
        Swal.fire('Error', e.message || 'Delete failed', 'error');
    }
}
</script>
