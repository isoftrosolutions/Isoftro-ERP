<?php
/**
 * Hamro ERP — Super Admin Dashboard Shell
 * Refactored to match Institute Admin layout structure.
 */

// Load global config if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

// Load sidebar component - required for renderSidebar() and getSuperAdminMenu()
require_once __DIR__ . '/sidebar.php';

$pageTitle = 'Super Admin Dashboard';
$stats = \App\Helpers\StatsHelper::getSuperAdminStats();
// Use the Super Admin specific header from layouts
// require_once VIEWS_PATH . '/super-admin/sidebar.php';
require_once VIEWS_PATH . '/layouts/header_1.php';
?>


<?php renderSuperAdminHeader(); ?>
<?php renderSidebar('index'); ?>


<!-- ── MAIN CONTENT (mirrors institute-admin .main) ── -->
<main class="main" id="mainContent">
    <div class="pg">
        <!-- Dashboard Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico"><i class="fa-solid fa-house"></i></div>
                <div>
                    <h1 class="pg-title">Platform Overview</h1>
                    <p class="pg-sub">HAMRO LABS INTERNAL ACCESS | PLATFORM OWNER</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs d-none-mob"><i class="fa-solid fa-download"></i> Export Data</button>
                <button class="btn bt"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
            </div>
        </div>

        <!-- ── QUICK ACTIONS ── -->
        <div style="margin-bottom: 24px;">
            <div class="sb-lbl" style="padding-left:0; margin-bottom:8px;">Quick Actions</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
                <a href="<?php echo APP_URL; ?>/dash/super-admin/add-tenant" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                    <div style="width:36px; height:36px; border-radius:8px; background:var(--sa-primary-lt); color:var(--sa-primary); display:flex; align-items:center; justify-content:center; font-size:18px;">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <span style="font-weight:600; font-size:14px;">Add New Institute</span>
                </a>
                <a href="<?php echo APP_URL; ?>/dash/super-admin/plan-assign" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                    <div style="width:36px; height:36px; border-radius:8px; background:#eff6ff; color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:18px;">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <span style="font-weight:600; font-size:14px;">Assign Plan</span>
                </a>
                <a href="<?php echo APP_URL; ?>/dash/super-admin/announcements" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                    <div style="width:36px; height:36px; border-radius:8px; background:#fef9e7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px;">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <span style="font-weight:600; font-size:14px;">Send Platform Announcement</span>
                </a>
                <a href="<?php echo APP_URL; ?>/dash/super-admin/flags" class="sc fu" style="display:flex; align-items:center; gap:12px; padding:12px; text-decoration:none; color:inherit;">
                    <div style="width:36px; height:36px; border-radius:8px; background:#f3e8ff; color:#8141A5; display:flex; align-items:center; justify-content:center; font-size:18px;">
                        <i class="fa-solid fa-toggle-on"></i>
                    </div>
                    <span style="font-weight:600; font-size:14px;">Toggle Feature</span>
                </a>
            </div>
        </div>

        <!-- ── STATS ROW 1 ── -->
        <div class="sg">
            <!-- Active Tenants -->
            <div class="sc fu">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Active Tenants</span>
                    <span class="tag bg-t">+<?php echo $stats['newTenantsThisMonth'] ?? 0; ?> this month</span>
                </div>
                <div class="sc-val"><?php echo $stats['totalTenants'] ?? 0; ?></div>
                <p class="sc-delta">Institutes currently on platform</p>
            </div>

            <!-- Active Subscriptions breakdown -->
            <div class="sc fu">
                <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Subscribed Plans</span>
                <div style="display:flex; align-items:center; gap:12px; margin-top:12px;">
                    <div style="flex:1;">
                        <div class="sc-val" style="font-size:20px;"><?php echo array_sum($stats['planStats'] ?? []); ?></div>
                        <div style="display:flex; gap:4px; margin-top:8px;">
                            <div title="Starter" style="height:6px; flex:<?php echo $stats['planStats']['starter'] ?? 1; ?>; background:#e2e8f0; border-radius:3px;"></div>
                            <div title="Growth" style="height:6px; flex:<?php echo $stats['planStats']['growth'] ?? 1; ?>; background:#3b82f6; border-radius:3px;"></div>
                            <div title="Professional" style="height:6px; flex:<?php echo $stats['planStats']['professional'] ?? 1; ?>; background:var(--sa-primary); border-radius:3px;"></div>
                            <div title="Enterprise" style="height:6px; flex:<?php echo $stats['planStats']['enterprise'] ?? 1; ?>; background:#1e293b; border-radius:3px;"></div>
                        </div>
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:9px; margin-top:8px; font-weight:700;">
                    <span>S: <?php echo $stats['planStats']['starter'] ?? 0; ?></span> 
                    <span>G: <?php echo $stats['planStats']['growth'] ?? 0; ?></span> 
                    <span>P: <?php echo $stats['planStats']['professional'] ?? 0; ?></span> 
                    <span>E: <?php echo $stats['planStats']['enterprise'] ?? 0; ?></span>
                </div>
            </div>

            <!-- SMS Credits Consumed -->
            <div class="sc fu">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">SMS Consumption</span>
                    <span class="tag bg-r"><?php echo $stats['sms']['consumedPercent'] ?? 0; ?>% of quota</span>
                </div>
                <div class="sc-val"><?php echo round(($stats['sms']['usedCredits'] ?? 0) / 1000, 1); ?>K</div>
                <div style="height:6px; width:100%; background:#f1f5f9; border-radius:3px; margin-top:12px; overflow:hidden;">
                    <div style="height:100%; width:<?php echo $stats['sms']['consumedPercent'] ?? 0; ?>%; background:var(--red); border-radius:3px;"></div>
                </div>
                <p class="sc-delta">Monthly platform-wide usage</p>
            </div>

            <!-- System Health -->
            <div class="sc fu">
                <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">System Health</span>
                <div style="margin-top:12px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-size:11px; font-weight:600;">Uptime</span>
                        <span style="font-size:11px; font-weight:700; color:var(--success);"><?php echo $stats['health']['uptime'] ?? '99.9%'; ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="font-size:11px; font-weight:600;">API Latency</span>
                        <span style="font-size:11px; font-weight:700;"><?php echo $stats['health']['latency'] ?? '0ms'; ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-size:11px; font-weight:600;">Redis Mem</span>
                        <span style="font-size:11px; font-weight:700;"><?php echo $stats['health']['redis'] ?? '0GB'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── REVENUE & TICKETS ── -->
        <div class="g65">
            <!-- Revenue Analytics -->
            <div class="sc fu" style="min-height:300px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
                    <div>
                        <h3 style="font-size:16px; font-weight:800; color:var(--td);">Monthly Recurring Revenue (MRR)</h3>
                        <p style="font-size:12px; color:var(--tl);">Revenue trends with Year-over-Year comparison</p>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:22px; font-weight:800; color:var(--td);"><?php echo $stats['mrrFormatted'] ?? 'रू 0'; ?></div>
                        <div style="font-size:11px; color:var(--success); font-weight:700;"><i class="fa-solid fa-arrow-trend-up"></i> <?php echo $stats['yoyGrowth'] ?? 0; ?>% YoY</div>
                    </div>
                </div>
                <div style="height:200px; position:relative;">
                    <canvas id="mrrChart"></canvas>
                </div>
            </div>

            <!-- Support Tickets -->
            <div class="sc fu">
                <h3 style="font-size:16px; font-weight:800; color:var(--td); margin-bottom:16px;">Support Tickets</h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; align-items:center; gap:12px; padding:12px; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px;">
                        <div style="width:8px; height:8px; border-radius:50%; background:var(--red); animation: pulse 2s infinite;"></div>
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:700; color:#9f1239;">Critical Priority</div>
                            <div style="font-size:10px; color:#be123c;"><?php echo $stats['tickets']['critical'] ?? 0; ?> Tickets awaiting action</div>
                        </div>
                        <div style="font-size:18px; font-weight:800; color:#9f1239;"><?php echo $stats['tickets']['critical'] ?? 0; ?></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--cb); border-radius:12px; background:#fff;">
                        <div style="width:8px; height:8px; border-radius:50%; background:var(--amber);"></div>
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:700; color:var(--td);">High Priority</div>
                            <div style="font-size:10px; color:var(--tl);"><?php echo $stats['tickets']['high'] ?? 0; ?> Pending tickets</div>
                        </div>
                        <div style="font-size:18px; font-weight:800; color:var(--td);"><?php echo $stats['tickets']['high'] ?? 0; ?></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--cb); border-radius:12px; background:#fff;">
                        <div style="width:8px; height:8px; border-radius:50%; background:var(--blue);"></div>
                        <div style="flex:1;">
                            <div style="font-size:13px; font-weight:700; color:var(--td);">Standard</div>
                            <div style="font-size:10px; color:var(--tl);"><?php echo $stats['tickets']['normal'] ?? 0; ?> Open tickets</div>
                        </div>
                        <div style="font-size:18px; font-weight:800; color:var(--td);"><?php echo $stats['tickets']['normal'] ?? 0; ?></div>
                    </div>
                </div>
                <a href="<?php echo APP_URL; ?>/dash/super-admin/support-tickets" class="btn bt" style="width:100%; margin-top:20px; justify-content:center;">Manage Tickets</a>
            </div>
        </div>

        <!-- ── BOTTOM ROW: Recent Signups & Security ── -->
        <div class="g65">
            <!-- Recent Signups -->
            <div class="sc fu">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--td);">Recent Institute Signups</h3>
                    <a href="<?php echo APP_URL; ?>/dash/super-admin/tenant-management" class="btn bs" style="padding:4px 12px; font-size:11px;">View Ledger</a>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid var(--cb);">
                                <th style="padding:12px 0; font-size:10px; color:var(--tl);">Institute Name</th>
                                <th style="padding:12px 0; font-size:10px; color:var(--tl);">Plan Tier</th>
                                <th style="padding:12px 0; font-size:10px; color:var(--tl);">Joined At</th>
                                <th style="padding:12px 0; font-size:10px; color:var(--tl);">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recentSignups'] ?? [] as $s): ?>
                            <tr>
                                <td style="padding:14px 0;">
                                    <div style="font-size:13px; font-weight:700; color:var(--td);"><?php echo htmlspecialchars($s['name']); ?></div>
                                    <div style="font-size:10px; color:var(--tl);"><?php echo htmlspecialchars($s['subdomain']); ?>.hamroerp.com</div>
                                </td>
                                <td style="padding:14px 0;"><span class="tag bg-p"><?php echo ucfirst($s['plan']); ?></span></td>
                                <td style="padding:14px 0; font-size:12px; font-weight:500;"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                                <td style="padding:14px 0;"><span class="tag bg-g"><?php echo ucfirst($s['status']); ?></span></td>
                            </tr>
                            <?php endforeach; if(empty($stats['recentSignups'])) echo '<tr><td colspan="4" style="text-align:center;padding:10px;">No institutes found.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>

             <!-- Security Alert Panel -->
            <div class="sc fu" style="background:#0F172A; border-color:#1e293b;">
                <h3 style="font-size:16px; font-weight:800; color:#fff; margin-bottom:20px;">Security Alert Center</h3>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:12px; border-bottom:1px solid #1e293b;">
                        <span style="font-size:12px; color:rgba(255,255,255,0.6); font-weight:600;">Failed Logins (Prev 24h)</span>
                        <span style="background:rgba(225,29,72,0.15); color:#f43f5e; padding:2px 10px; border-radius:12px; font-size:11px; font-weight:800; border:1px solid rgba(225,29,72,0.2);"><?php echo $stats['failedLogins'] ?? 0; ?> Incidents</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.15); display:flex; align-items:center; justify-content:center; color:#f87171;">
                            <i class="fa-solid fa-shield-virus"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px; color:#fff; font-weight:700;">Suspected Brute Force</div>
                            <div style="font-size:10px; color:rgba(255,255,255,0.4);">IP: 192.168.1.104 — 8 min ago</div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.15); display:flex; align-items:center; justify-content:center; color:#fbbf24;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:13px; color:#fff; font-weight:700;">SMS Gateway Latency</div>
                            <div style="font-size:10px; color:rgba(255,255,255,0.4);">Platform-wide delay (2.4s)</div>
                        </div>
                    </div>
                    <a href="<?php echo APP_URL; ?>/dash/super-admin/logs?type=security" style="margin-top:8px; text-align:center; display:block; padding:12px; border-radius:10px; border:1px solid #1e293b; color:#fff; font-size:12px; text-decoration:none; font-weight:700; background:rgba(255,255,255,0.03); transition:0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.07)'" onmouseout="this.style.background='rgba(255,255,255,0.03)'">Investigate Security Logs</a>
                </div>
            </div>
        </div>

        <!-- ── DAILY WORKFLOW ── -->
        <div style="margin-bottom: 24px;">
            <div class="sc fu" style="background:var(--bg-card); border-left:4px solid var(--sa-primary);">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                    <div>
                        <h3 style="font-size:16px; font-weight:800; color:var(--td); margin:0;">Daily Workflow</h3>
                        <p style="font-size:12px; color:var(--tl); margin:4px 0 0;">Platform initialization checklist</p>
                    </div>
                    <span class="tag bg-g" id="workflowStatus">0 / 5 Completed</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="updateWorkflowStatus()">
                        <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--td);">1. Check System Health</div>
                            <div style="font-size:12px; color:var(--tl);">Review the System Health widget for any overnight alerts or errors</div>
                        </div>
                    </label>
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="updateWorkflowStatus()">
                        <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--td);">2. Process New Signups</div>
                            <div style="font-size:12px; color:var(--tl);">Review new institute signups and assign subscription plans</div>
                        </div>
                    </label>
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="updateWorkflowStatus()">
                        <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--td);">3. Manage Support Tickets</div>
                            <div style="font-size:12px; color:var(--tl);">Process pending support tickets and impersonate admins if needed</div>
                        </div>
                    </label>
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="updateWorkflowStatus()">
                        <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--td);">4. Monitor MRR Dashboard</div>
                            <div style="font-size:12px; color:var(--tl);">Analyze revenue trends and identify churn signals</div>
                        </div>
                    </label>
                    <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;" onclick="updateWorkflowStatus()">
                        <input type="checkbox" class="wf-check" style="width:18px; height:18px; margin-top:2px; accent-color:var(--sa-primary);">
                        <div>
                            <div style="font-size:14px; font-weight:600; color:var(--td);">5. Security Audit</div>
                            <div style="font-size:12px; color:var(--tl);">Review security alerts panel for unusual login activity</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
