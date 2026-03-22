<?php
/**
 * ISOFTRO - Platform Analytics
 * Variables: $activeUsers, $peakConcurrent, $totalStudents, $activeInstitutes,
 *            $featureUsage (array: name, pct), $smsConsumption (array: name, sms_used, sms_limit)
 */
$activeUsers      = $activeUsers      ?? 0;
$peakConcurrent   = $peakConcurrent   ?? 0;
$totalStudents    = $totalStudents    ?? 0;
$activeInstitutes = $activeInstitutes ?? 0;
$featureUsage     = $featureUsage     ?? [];
$smsConsumption   = $smsConsumption   ?? [];
$totalInstitutes  = $totalInstitutes  ?? 1;
?>

<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Analytics</span></div>
        <h1>Platform Analytics</h1>
    </div>
</div>

<div class="g4 mt-20">
    <div class="card p-20" style="background:linear-gradient(135deg,#1e40af,#3b82f6);border:none;color:white;">
        <div style="font-size:11px;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-users" style="margin-right:6px;"></i>Active Users (24h)</div>
        <div style="font-size:34px;font-weight:800;"><?= number_format($activeUsers) ?></div>
        <div style="font-size:11px;opacity:.7;margin-top:6px;">All institute users</div>
    </div>
    <div class="card p-20" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);border:none;color:white;">
        <div style="font-size:11px;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-bolt" style="margin-right:6px;"></i>Peak Users (7d)</div>
        <div style="font-size:34px;font-weight:800;"><?= number_format($peakConcurrent) ?></div>
        <div style="font-size:11px;opacity:.7;margin-top:6px;">Max in a single day</div>
    </div>
    <div class="card p-20" style="background:linear-gradient(135deg,#0369a1,#0ea5e9);border:none;color:white;">
        <div style="font-size:11px;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-user-graduate" style="margin-right:6px;"></i>Total Students</div>
        <div style="font-size:34px;font-weight:800;"><?= number_format($totalStudents) ?></div>
        <div style="font-size:11px;opacity:.7;margin-top:6px;">Across all institutes</div>
    </div>
    <div class="card p-20" style="background:linear-gradient(135deg,#065f46,#059669);border:none;color:white;">
        <div style="font-size:11px;font-weight:700;opacity:.8;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-building" style="margin-right:6px;"></i>Active Institutes</div>
        <div style="font-size:34px;font-weight:800;"><?= number_format($activeInstitutes) ?></div>
        <div style="font-size:11px;opacity:.7;margin-top:6px;">Currently active tenants</div>
    </div>
</div>

<div class="g2 mt-20">
    <div class="card p-20">
        <div class="ct"><i class="fas fa-fire"></i> Feature Usage Heatmap</div>
        <p style="font-size:12px;color:var(--text-light);margin-bottom:15px;">Modules enabled across <?= $totalInstitutes ?> institutes.</p>

        <?php if (empty($featureUsage)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-light);">
            <i class="fas fa-chart-bar" style="font-size:30px;opacity:.3;"></i>
            <p style="margin-top:10px;">No module data found.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:15px;">
            <?php
            $colors = ['#059669','#0284c7','#7c3aed','#d97706','#dc2626','#0891b2','#16a34a','#db2777','#b45309','#6366f1'];
            foreach ($featureUsage as $i => $f):
                $c = $colors[$i % count($colors)];
            ?>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:var(--text-dark);margin-bottom:5px;">
                    <span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $f['name']))) ?></span>
                    <span><?= $f['pct'] ?>% <span style="color:var(--text-light);font-weight:400;">(<?= $f['usage_count'] ?> institutes)</span></span>
                </div>
                <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:<?= $f['pct'] ?>%;background:<?= $c ?>;border-radius:99px;transition:width 1.2s ease;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card p-20">
        <div class="ct"><i class="fas fa-comment-sms"></i> SMS Credit Consumption (30d)</div>
        <p style="font-size:12px;color:var(--text-light);margin-bottom:15px;">Top institutes by SMS usage this month.</p>

        <?php if (empty($smsConsumption)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-light);">
            <i class="fas fa-sms" style="font-size:30px;opacity:.3;"></i>
            <p style="margin-top:10px;">No SMS logs found for the last 30 days.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:10px;">
            <?php foreach ($smsConsumption as $s):
                $limit = max((int)$s['sms_limit'], 1);
                $used  = (int)$s['sms_used'];
                $pct   = min(round(($used / $limit) * 100), 100);
                $color = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#d97706' : '#059669');
            ?>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:var(--text-dark);margin-bottom:5px;">
                    <span><?= htmlspecialchars($s['name']) ?></span>
                    <span style="color:<?= $color ?>;"><?= number_format($used) ?> / <?= number_format($limit) ?></span>
                </div>
                <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:99px;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
