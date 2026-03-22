<?php
/**
 * ISOFTRO - Super Admin Dashboard (Overview)
 * Partial view loaded via AJAX
 * Variables: $stats, $recentTenants, $sysHealth, $totalTenants
 */

$stats = $stats ?? [];
$recentTenants = $recentTenants ?? [];
$sysHealth = $sysHealth ?? [];

?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Dashboard</span></div>
        <h1>Platform Overview</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" data-modal="announcementModal"><i class="fas fa-bullhorn"></i> Announcement</button>
        <button class="btn bt" data-modal="addTenantModal"><i class="fas fa-plus"></i> New Institute</button>
    </div>
</div>

<!-- TOP STATS -->
<div class="stat-grid">
    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-icon-box ic-green"><i class="fas fa-building"></i></div>
            <span class="stat-badge bg-t">+<?= $stats['new_tenants_this_month'] ?? 4 ?> this month</span>
        </div>
        <div class="stat-val"><?= $stats['total_tenants'] ?? 0 ?></div>
        <div class="stat-sub">Total Active Institutes</div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-icon-box ic-blue"><i class="fas fa-sync-alt"></i></div>
            <span class="stat-badge bg-t">Growth</span>
        </div>
        <div class="stat-val"><?= $stats['active_subscribers'] ?? 0 ?></div>
        <div class="stat-sub">Active Subscriptions</div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-icon-box ic-purple"><i class="fas fa-wallet"></i></div>
            <span class="stat-badge bg-t">12.5% YoY</span>
        </div>
        <div class="stat-val">NPR <?= number_format($stats['mrr'] ?? 0) ?></div>
        <div class="stat-sub">Monthly Recurring Revenue</div>
    </div>
    <div class="card stat-card">
        <div class="stat-top">
            <div class="stat-icon-box ic-amber"><i class="fas fa-shield-virus"></i></div>
            <span class="stat-badge bg-r">Alerts</span>
        </div>
        <div class="stat-val"><?= $stats['security_alerts'] ?? 0 ?></div>
        <div class="stat-sub">Failed Login Attempts (24h)</div>
    </div>
</div>

<!-- SECOND ROW: Analytics & Health -->
<div class="g2">
    <!-- SYSTEM HEALTH -->
    <div class="card">
        <div class="ct" style="display:flex; justify-content:space-between; align-items:center;">
            <span><i class="fas fa-microchip"></i> System Health</span>
            <span class="bdg bg-green">Online</span>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
            <div class="health-item" style="background:#f8fafc; padding:12px; border-radius:8px;">
                <small style="color: var(--text-light); font-weight:700; text-transform:uppercase; font-size:9px;">Uptime</small>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--green);"><?= $sysHealth['uptime'] ?? '99.9%' ?></div>
            </div>
            <div class="health-item" style="background:#f8fafc; padding:12px; border-radius:8px;">
                <small style="color: var(--text-light); font-weight:700; text-transform:uppercase; font-size:9px;">API Latency p95</small>
                <div style="font-size: 1.1rem; font-weight: 700; color: #3b82f6;"><?= $sysHealth['api_p95'] ?? '120ms' ?></div>
            </div>
            <div class="health-item" style="background:#f8fafc; padding:12px; border-radius:8px;">
                <small style="color: var(--text-light); font-weight:700; text-transform:uppercase; font-size:9px;">Queue Depth</small>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark);"><?= $sysHealth['queue_depth'] ?? 0 ?> Jobs</div>
            </div>
            <div class="health-item" style="background:#f8fafc; padding:12px; border-radius:8px;">
                <small style="color: var(--text-light); font-weight:700; text-transform:uppercase; font-size:9px;">Memory Usage</small>
                <div style="font-size: 1.1rem; font-weight: 700; color: #16a34a;">Safe</div>
            </div>
        </div>
    </div>

    <!-- RECENT SIGNUPS -->
    <div class="card">
        <div class="ct"><i class="fas fa-clock-rotate-left"></i> Recent Signups</div>
        <div class="tbl-wrap" style="border: none; margin-top: 10px;">
            <table style="min-width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                <tbody>
                    <?php if (empty($recentTenants)) { ?>
                        <tr><td style="text-align:center; color:var(--text-light); padding: 20px;">No recent signups found.</td></tr>
                    <?php } else { ?>
                        <?php foreach ($recentTenants as $tenant) { ?>
                        <tr style="background:#f8fafc;">
                            <td style="padding:10px; border-radius: 8px 0 0 8px;"><strong><?= htmlspecialchars($tenant['name']) ?></strong></td>
                            <td style="padding:10px;"><span class="plan-badge plan-<?= strtolower($tenant['plan']) ?>" style="font-size: 10px;"><?= ucfirst($tenant['plan']) ?></span></td>
                            <td style="padding:10px; text-align: right; border-radius: 0 8px 8px 0;">
                                <span style="font-size: 11px; color: var(--text-light);"><?= date('M d', strtotime($tenant['created_at'])) ?></span>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- THIRD ROW: Usage & Support -->
<div class="g2">
    <div class="card">
        <div class="ct"><i class="fas fa-comment-sms"></i> SMS Credits Consumption</div>
        <div style="margin-top:20px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                <span style="font-size:12px; color:var(--text-body);">Platform-wide Usage (Monthly)</span>
                <span style="font-size:12px; font-weight:600;">42.8k / 100k</span>
            </div>
            <div style="height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                <div style="height:100%; width:42%; background:var(--purple); border-radius:4px;"></div>
            </div>
        </div>
        
        <div style="margin-top:20px; height: 100px; display: flex; align-items: flex-end; gap: 8px;">
            <!-- Dummy Bars -->
            <div style="flex:1; background:#f1f5f9; height:40%; border-radius:4px;"></div>
            <div style="flex:1; background:#f1f5f9; height:50%; border-radius:4px;"></div>
            <div style="flex:1; background:#f1f5f9; height:35%; border-radius:4px;"></div>
            <div style="flex:1; background:#f1f5f9; height:70%; border-radius:4px;"></div>
            <div style="flex:1; background:#f1f5f9; height:90%; border-radius:4px;"></div>
            <div style="flex:1; background:var(--purple); height:42%; border-radius:4px;"></div>
        </div>
    </div>
    
    <div class="card">
        <div class="ct"><i class="fas fa-life-ring"></i> Support Pulse</div>
        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #fff1f2; border-radius: 8px; border-left: 4px solid #f43f5e;">
                <div style="font-size: 13px; font-weight: 700; color: #9f1239;">URGENT TICKETS</div>
                <strong style="color: #9f1239; font-size: 1.2rem;">3</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #cbd5e1;">
                <div style="font-size: 13px; font-weight: 600; color: #475569;">PENDING APPROVALS</div>
                <strong style="font-size: 1.1rem;">12</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #22c55e;">
                <div style="font-size: 13px; font-weight: 600; color: #166534;">RESOLVED (LAST 24h)</div>
                <strong style="color: #166534; font-size: 1.1rem;">45</strong>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="card mt-20" style="background: linear-gradient(to right, #009E7E, #007a62); border:none; color:white;">
    <div class="ct" style="color:white; border-bottom-color: rgba(255,255,255,0.2);">Quick Platform Actions</div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
        <button class="btn" style="background:rgba(255,255,255,0.2); border:none; color:white;" onclick="openModal('addTenantModal')"><i class="fas fa-plus"></i> Add New Institute</button>
        <button class="btn" style="background:rgba(255,255,255,0.2); border:none; color:white;"><i class="fas fa-user-tag"></i> Assign Plan</button>
        <button class="btn" style="background:rgba(255,255,255,0.2); border:none; color:white;"><i class="fas fa-bullhorn"></i> Send Announcement</button>
        <button class="btn" style="background:rgba(255,255,255,0.2); border:none; color:white;"><i class="fas fa-toggle-on"></i> Toggle Feature</button>
    </div>
</div>
