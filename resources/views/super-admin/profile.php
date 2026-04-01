<?php
/**
 * ISOFTRO - Super Admin Profile
 * Partial view loaded via AJAX
 */

$user = getCurrentUser() ?? [];
$name = $user['name'] ?? 'Super Admin';
$email = $user['email'] ?? '-';
$role = $user['role'] ?? 'superadmin';
$initial = strtoupper(substr($name ?: 'S', 0, 1));
?>

<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-user-circle"></i> <span>Account</span></div>
        <h1>My Profile</h1>
    </div>
</div>

<div class="g2">
    <div class="card">
        <div class="ct"><i class="fas fa-id-badge"></i> Account Details</div>

        <div style="display:flex; gap:16px; align-items:center; margin-top:14px;">
            <div style="width:56px;height:56px;border-radius:16px;background:rgba(0,184,148,0.12);display:flex;align-items:center;justify-content:center;color:var(--green);font-weight:900;font-size:22px;">
                <?= htmlspecialchars($initial) ?>
            </div>
            <div style="min-width:0;">
                <div style="font-weight:800; font-size:16px; color:var(--text-dark); line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($name) ?>
                </div>
                <div style="color:var(--text-body); font-size:13px; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($email) ?>
                </div>
                <div style="margin-top:8px;">
                    <span class="bdg bg-green" style="text-transform:uppercase; letter-spacing:0.4px;">
                        <?= htmlspecialchars($role) ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="height:1px;background:#f1f5f9;margin:18px 0;"></div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                <div style="font-size:10px; color:var(--text-light); font-weight:800; text-transform:uppercase;">User ID</div>
                <div style="font-weight:800; margin-top:4px;"><?= htmlspecialchars((string)($user['id'] ?? '-')) ?></div>
            </div>
            <div style="background:#f8fafc; padding:12px; border-radius:10px;">
                <div style="font-size:10px; color:var(--text-light); font-weight:800; text-transform:uppercase;">Tenant</div>
                <div style="font-weight:800; margin-top:4px;"><?= htmlspecialchars((string)($user['tenant_id'] ?? 'Platform')) ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="ct"><i class="fas fa-shield-halved"></i> Security</div>

        <div style="margin-top:14px; display:flex; flex-direction:column; gap:10px;">
            <a href="<?= APP_URL ?>/auth/change-password" class="btn bt" style="justify-content:center;">
                <i class="fas fa-key"></i> Change Password
            </a>
            <button class="btn bt" onclick="goNav('logs')" style="justify-content:center; background:#0ea5e9;">
                <i class="fas fa-fingerprint"></i> View Activity Log
            </button>
            <a href="<?= APP_URL ?>/logout.php" class="btn bt" style="justify-content:center; background:var(--red);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</div>

