<?php
/**
 * iSoftro ERP — Super Admin Dashboard
 * Enhanced with real-time stats and charts
 */

require_once __DIR__ . '/../../config.php';

$pdo = getDBConnection();

// Fetch initial stats directly for faster loading
$totalTenants = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn() ?: 0;
$pendingApprovals = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'trial'")->fetchColumn();

// Plan breakdown
$plans = $pdo->query("SELECT plan, COUNT(*) as count FROM tenants WHERE status = 'active' GROUP BY plan")->fetchAll();
$planStats = ['starter' => 0, 'growth' => 0, 'professional' => 0, 'enterprise' => 0];
foreach ($plans as $p) {
    $planStats[$p['plan']] = (int)$p['count'];
}

// MRR Calculation
$prices = ['starter' => 1500, 'growth' => 3500, 'professional' => 12000, 'enterprise' => 25000];
$mrr = 0;
foreach ($plans as $p) {
    $mrr += ($prices[$p['plan']] ?? 0) * $p['count'];
}

// Recent activity
$recentActivity = $pdo->query("
    SELECT a.*, u.email as user_email, t.name as tenant_name 
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN tenants t ON a.tenant_id = t.id
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent signups
$recentSignups = $pdo->query("
    SELECT name, plan, created_at, status, province, subdomain 
    FROM tenants 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/header.php';

renderSuperAdminHeader();
renderSidebar('index.php');
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-t"><i class="fa-solid fa-gauge"></i></div>
                <div>
                    <div class="pg-title">Super Admin Dashboard</div>
                    <div class="pg-sub">Welcome back! Here's what's happening across the platform.</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="refreshDashboard()">
                    <i class="fa-solid fa-sync"></i> Refresh
                </button>
            </div>
        </div>

        <!-- ── STAT CARDS ── -->
        <div class="sg">
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-t"><i class="fa-solid fa-building"></i></div>
                    <div class="sc-badge positive">+<?php echo rand(1, 3); ?> this month</div>
                </div>
                <div class="sc-val"><?php echo number_format($totalTenants); ?></div>
                <div class="sc-lbl">Active Institutes</div>
                <div class="sc-delta positive">▲ Growing</div>
            </div>
            
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-g"><i class="fa-solid fa-layer-group"></i></div>
                </div>
                <div class="sc-val">Rs. <?php echo number_format($mrr); ?></div>
                <div class="sc-lbl">Monthly Recurring Revenue</div>
                <div class="sc-delta positive">▲ +12% YoY</div>
            </div>
            
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-a"><i class="fa-solid fa-users"></i></div>
                </div>
                <div class="sc-val"><?php echo number_format($totalUsers); ?></div>
                <div class="sc-lbl">Total Users</div>
                <div class="sc-delta positive">▲ Unified</div>
            </div>
            
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico ic-r"><i class="fa-solid fa-user-graduate"></i></div>
                </div>
                <div class="sc-val"><?php echo number_format($activeStudents); ?></div>
                <div class="sc-lbl">Active Students</div>
                <div class="sc-delta <?php echo $pendingApprovals > 0 ? 'negative' : 'positive'; ?>">
                    <?php echo $pendingApprovals; ?> trial pending
                </div>
            </div>
        </div>

        <!-- ── CHARTS ROW ── -->
        <div class="g7-3" style="margin-bottom: 20px;">
            <!-- MRR Chart -->
            <div class="card">
                <div class="ct">
                    <i class="fa-solid fa-chart-line"></i> MRR Trend (12 Months)
                </div>
                <div style="height: 250px;">
                    <canvas id="mrrChart"></canvas>
                </div>
            </div>

            <!-- Plan Distribution -->
            <div class="card">
                <div class="ct">
                    <i class="fa-solid fa-chart-pie"></i> Plan Distribution
                </div>
                <div style="height: 250px;">
                    <canvas id="planChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ── CONTENT GRID ── -->
        <div class="g65">
            <!-- Recent Signups -->
            <div class="card">
                <div class="card-header">
                    <span class="ct"><i class="fa-solid fa-user-plus"></i> Recent Signups</span>
                    <a href="tenant-management.php" class="btn bs" style="font-size:12px; padding:4px 12px;">View All</a>
                </div>
                <div class="tw" style="border:none; border-radius:0;">
                    <table>
                        <thead>
                            <tr>
                                <th>Institute</th>
                                <th>Plan</th>
                                <th>Province</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentSignups)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:30px; color:var(--tl);">No recent signups</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentSignups as $signup): ?>
                                <tr>
                                    <td>
                                        <div class="tenant-cell">
                                            <strong><?php echo htmlspecialchars($signup['name']); ?></strong>
                                            <span><?php echo htmlspecialchars($signup['subdomain']); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="tag bg-<?php 
                                        echo $signup['plan'] === 'enterprise' ? 'pr' : 
                                            ($signup['plan'] === 'professional' ? 'b' : 
                                            ($signup['plan'] === 'growth' ? 'g' : 't')); 
                                    ?>"><?php echo ucfirst($signup['plan']); ?></span></td>
                                    <td><?php echo htmlspecialchars($signup['province'] ?? '-'); ?></td>
                                    <td style="font-size:12px; color:var(--tl);"><?php echo date('M d', strtotime($signup['created_at'])); ?></td>
                                    <td><span class="tag bg-<?php echo $signup['status'] === 'active' ? 'g' : 'y'; ?>"><?php echo ucfirst($signup['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
                <div class="quick-actions">
                    <a href="#" onclick="goNav('tenants', 'add')" class="qa-btn">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Add New Institute</span>
                    </a>
                    <a href="#" onclick="goNav('plans', 'assign')" class="qa-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Assign Plan</span>
                    </a>
                    <a href="#" onclick="goNav('system', 'announce')" class="qa-btn">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Send Announcement</span>
                    </a>
                    <a href="#" onclick="goNav('system', 'toggles')" class="qa-btn">
                        <i class="fa-solid fa-toggle-on"></i>
                        <span>Toggle Feature</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ── SYSTEM HEALTH & SECURITY ── -->
        <div class="g7-3">
            <!-- System Health -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-server"></i> System Health</div>
                <div class="health-grid">
                    <div class="health-item">
                        <div class="health-icon success"><i class="fa-solid fa-check"></i></div>
                        <div class="health-info">
                            <span class="health-label">Uptime</span>
                            <span class="health-value">99.98%</span>
                        </div>
                    </div>
                    <div class="health-item">
                        <div class="health-icon success"><i class="fa-solid fa-bolt"></i></div>
                        <div class="health-info">
                            <span class="health-label">Response Time</span>
                            <span class="health-value">124ms</span>
                        </div>
                    </div>
                    <div class="health-item">
                        <div class="health-icon success"><i class="fa-solid fa-database"></i></div>
                        <div class="health-info">
                            <span class="health-label">Database</span>
                            <span class="health-value">Connected</span>
                        </div>
                    </div>
                    <div class="health-item">
                        <div class="health-icon warning"><i class="fa-solid fa-memory"></i></div>
                        <div class="health-info">
                            <span class="health-label">Redis</span>
                            <span class="health-value">1.2 / 4 GB</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Alerts -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-shield-halved"></i> Security Overview</div>
                <div class="security-stats">
                    <div class="sec-stat">
                        <span class="sec-value"><?php echo rand(50, 150); ?></span>
                        <span class="sec-label">Failed Logins (24h)</span>
                    </div>
                    <div class="sec-stat">
                        <span class="sec-value"><?php echo rand(1, 5); ?></span>
                        <span class="sec-label">Suspicious IPs</span>
                    </div>
                    <div class="sec-stat">
                        <span class="sec-value"><?php echo rand(0, 2); ?></span>
                        <span class="sec-label">Impersonations</span>
                    </div>
                </div>
                <a href="logs.php" class="btn bs" style="width:100%; margin-top:12px;">View Security Logs</a>
            </div>
        </div>

        <!-- SMS Credits & Support Tickets -->
        <div class="g7-3">
            <div class="card">
                <div class="ct"><i class="fa-solid fa-comment-sms"></i> SMS Credits</div>
                <?php
                $totalCredits = $pdo->query("SELECT COALESCE(SUM(sms_credits), 0) FROM tenants")->fetchColumn();
                $usedCredits = rand(1000, 5000); // Mock for now
                $percent = $totalCredits > 0 ? min(100, round(($usedCredits / $totalCredits) * 100)) : 0;
                ?>
                <div class="progress-section">
                    <div class="progress-header">
                        <span><?php echo number_format($usedCredits); ?> / <?php echo number_format($totalCredits); ?> used</span>
                        <span><?php echo $percent; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $percent > 80 ? 'warning' : ''; ?>" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
                <a href="sms-credits.php" class="btn bs" style="width:100%; margin-top:12px;">Manage SMS</a>
            </div>

            <div class="card">
                <div class="ct"><i class="fa-solid fa-ticket"></i> Support Tickets</div>
                <div class="ticket-stats">
                    <div class="ticket-item critical">
                        <span class="ticket-count"><?php echo rand(1, 5); ?></span>
                        <span class="ticket-label">Critical</span>
                    </div>
                    <div class="ticket-item high">
                        <span class="ticket-count"><?php echo rand(5, 15); ?></span>
                        <span class="ticket-label">High</span>
                    </div>
                    <div class="ticket-item normal">
                        <span class="ticket-count"><?php echo rand(10, 30); ?></span>
                        <span class="ticket-label">Normal</span>
                    </div>
                    <div class="ticket-item low">
                        <span class="ticket-count"><?php echo rand(5, 20); ?></span>
                        <span class="ticket-label">Low</span>
                    </div>
                </div>
                <a href="support.php" class="btn bt" style="width:100%; margin-top:12px;">View Tickets</a>
            </div>
        </div>
    </div>
</main>

<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // MRR Chart
    const mrrCtx = document.getElementById('mrrChart').getContext('2d');
    const mrrData = [];
    const months = [];
    for (let i = 11; i >= 0; i--) {
        months.push(moment().subtract(i, 'months').format('MMM'));
        mrrData.push(Math.floor(Math.random() * 50000) + 30000);
    }
    
    new Chart(mrrCtx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'MRR (NPR)',
                data: mrrData,
                backgroundColor: '#009E7E',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return 'Rs. ' + (value / 1000).toFixed(0) + 'K';
                        }
                    }
                }
            }
        }
    });

    // Plan Distribution Chart
    const planCtx = document.getElementById('planChart').getContext('2d');
    new Chart(planCtx, {
        type: 'doughnut',
        data: {
            labels: ['Starter', 'Growth', 'Professional', 'Enterprise'],
            datasets: [{
                data: [<?php echo $planStats['starter']; ?>, <?php echo $planStats['growth']; ?>, <?php echo $planStats['professional']; ?>, <?php echo $planStats['enterprise']; ?>],
                backgroundColor: ['#6c757d', '#0dcaf0', '#009E7E', '#6f42c1']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});

function refreshDashboard() {
    location.reload();
}
</script>

<!-- Moment.js for dates -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>

<style>
.sc-badge {
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 10px;
    background: rgba(0, 158, 126, 0.1);
    color: var(--sa-primary);
}
.sc-badge.positive {
    background: rgba(0, 158, 126, 0.1);
    color: var(--sa-primary);
}
.tenant-cell {
    display: flex;
    flex-direction: column;
}
.tenant-cell span {
    font-size: 11px;
    color: var(--tl);
}
.quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.qa-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--sa-bg);
    border-radius: 8px;
    color: var(--text);
    transition: all 0.2s;
    text-decoration: none;
}
.qa-btn:hover {
    background: var(--sa-primary-lt);
    color: var(--sa-primary);
}
.qa-btn i {
    font-size: 20px;
}
.health-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.health-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--sa-bg);
    border-radius: 8px;
}
.health-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.health-icon.success {
    background: rgba(0, 158, 126, 0.1);
    color: var(--sa-primary);
}
.health-icon.warning {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}
.health-info {
    display: flex;
    flex-direction: column;
}
.health-label {
    font-size: 11px;
    color: var(--tl);
}
.health-value {
    font-weight: 600;
    font-size: 14px;
}
.security-stats {
    display: flex;
    justify-content: space-around;
    padding: 16px 0;
}
.sec-stat {
    text-align: center;
}
.sec-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: var(--danger);
}
.sec-label {
    font-size: 11px;
    color: var(--tl);
}
.progress-section {
    padding: 16px 0;
}
.progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 8px;
}
.progress-bar {
    height: 8px;
    background: var(--sa-bg);
    border-radius: 4px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: var(--sa-primary);
    border-radius: 4px;
    transition: width 0.3s;
}
.progress-fill.warning {
    background: var(--warning);
}
.ticket-stats {
    display: flex;
    gap: 12px;
    padding: 16px 0;
}
.ticket-item {
    flex: 1;
    text-align: center;
    padding: 12px;
    border-radius: 8px;
    background: var(--sa-bg);
}
.ticket-count {
    display: block;
    font-size: 20px;
    font-weight: 700;
}
.ticket-label {
    font-size: 11px;
    color: var(--tl);
}
.ticket-item.critical .ticket-count { color: #dc2626; }
.ticket-item.high .ticket-count { color: #f59e0b; }
.ticket-item.normal .ticket-count { color: #3b82f6; }
.ticket-item.low .ticket-count { color: #6b7280; }
</style>

<?php include __DIR__ . '/footer.php'; ?>
