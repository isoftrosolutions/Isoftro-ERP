<?php
/**
 * ISOFTRO - Revenue Dashboard
 * Variables: $mrr, $recentPayments, $yearlyRev, $activeInstituteCount
 */
$mrr                 = $mrr                 ?? 0;
$recentPayments      = $recentPayments      ?? [];
$yearlyRev           = $yearlyRev           ?? 0;
$activeInstituteCount = $activeInstituteCount ?? 1;
?>

<div class="pg-hdr">
    <div>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span>Finance</span>
        </div>
        <h1>Revenue Analytics</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs"><i class="fas fa-file-export"></i> Export Report</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); color:white; border:none;">
        <div style="font-size:12px; font-weight:700; opacity:0.8; text-transform:uppercase;">Monthly Recurring Revenue (MRR)</div>
        <div style="font-size:32px; font-weight:800; margin:10px 0;">Rs. <?= number_format($mrr) ?></div>
        <div style="font-size:12px; display:flex; align-items:center; gap:5px;">
            <i class="fas fa-arrow-trend-up"></i>
            <span>+12.5% from last month</span>
        </div>
    </div>
    <div class="card p-20" style="background: linear-gradient(135deg, #059669, #10b981); color:white; border:none;">
        <div style="font-size:12px; font-weight:700; opacity:0.8; text-transform:uppercase;">Annual Forecasted Rev</div>
        <div style="font-size:32px; font-weight:800; margin:10px 0;">Rs. <?= number_format(($mrr * 12) + $yearlyRev) ?></div>
        <div style="font-size:12px; display:flex; align-items:center; gap:5px;">
            <i class="fas fa-calendar-check"></i>
            <span>Based on current subscriptions</span>
        </div>
    </div>
    <div class="card p-20" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); color:white; border:none;">
        <div style="font-size:12px; font-weight:700; opacity:0.8; text-transform:uppercase;">Avg Revenue Per User (ARPU)</div>
        <div style="font-size:32px; font-weight:800; margin:10px 0;">Rs. <?= $mrr > 0 && $activeInstituteCount > 0 ? number_format($mrr / $activeInstituteCount) : '0' ?></div>
        <div style="font-size:12px; display:flex; align-items:center; gap:5px;">
            <i class="fas fa-id-card-clip"></i>
            <span>Per Active Institute</span>
        </div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-history"></i> Recent Payment History</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Institute</th>
                    <th>Ref ID</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPayments)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:40px; color:var(--text-light);">No payment records found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentPayments as $p): ?>
                    <tr>
                        <td style="font-weight:700; color:var(--text-dark);"><?= htmlspecialchars($p['tenant_name']) ?></td>
                        <td style="font-family:monospace; font-size:12px;"><?= $p['reference_id'] ?? 'N/A' ?></td>
                        <td><span class="bdg bg-gray"><?= strtoupper($p['plan'] ?? 'STANDARD') ?></span></td>
                        <td style="font-weight:700;">Rs. <?= number_format($p['amount']) ?></td>
                        <td>
                            <?php if (($p['status'] ?? 'paid') === 'paid'): ?>
                                <span class="bdg bg-green">SUCCESS</span>
                            <?php else: ?>
                                <span class="bdg bg-red"><?= strtoupper($p['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;"><?= date('M d, Y H:i', strtotime($p['created_at'])) ?></td>
                        <td>
                            <button class="btn bs sm" onclick="SuperAdmin.showNotification('Downloading invoice...', 'info')">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
