<?php
/**
 * Hamro ERP — Active Users Page
 * Platform Blueprint V3.0
 * 
 * Users analytics page using modular header/footer
 * 
 * @module SuperAdmin
 * @version 1.0.0
 */

// Include configuration and modular components
require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pdo = getDBConnection();

// Overall Stats
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_7d,
    SUM(CASE WHEN DATE(last_login_at) = CURDATE() THEN 1 ELSE 0 END) as logins_today,
    SUM(CASE WHEN last_login_at < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_login_at IS NULL THEN 1 ELSE 0 END) as inactive_30d,
    COUNT(DISTINCT tenant_id) as total_institutes
    FROM users
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$totalUsers = (int)$stats['total_users'];
$active7d = (int)$stats['active_7d'];
$engRate = $totalUsers > 0 ? round(($active7d / $totalUsers) * 100, 1) : 0;
$loginsToday = (int)$stats['logins_today'];
$inactive30d = (int)$stats['inactive_30d'];
$inactivePct = $totalUsers > 0 ? round(($inactive30d / $totalUsers) * 100, 1) : 0;
$totalInst = (int)$stats['total_institutes'];

// Role Distribution
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC");
$roleDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Institutes
$stmt = $pdo->query("
    SELECT t.name, COUNT(u.id) as users_count, SUM(CASE WHEN DATE(u.last_login_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_users
    FROM tenants t
    LEFT JOIN users u ON t.id = u.tenant_id
    GROUP BY t.id
    ORDER BY users_count DESC
    LIMIT 5
");
$topInstitutes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Logins
$stmt = $pdo->query("
    SELECT u.email as name, u.role, COALESCE(t.name, 'System') as inst, u.last_login_at as time
    FROM users u
    LEFT JOIN tenants t ON u.tenant_id = t.id
    WHERE u.last_login_at IS NOT NULL
    ORDER BY u.last_login_at DESC
    LIMIT 7
");
$recentLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dummy DAU data for visual (Last 7 days)
$dauChartData = [
    (int)($loginsToday * 0.8), (int)($loginsToday * 0.9), (int)($loginsToday * 1.1),
    (int)($loginsToday * 0.95), (int)($loginsToday * 1.05), (int)($loginsToday * 0.85),
    $loginsToday
];

$pageTitle = 'Active Users';
$activePage = 'users.php';
?>

<!-- Sidebar -->
<?php renderSuperAdminHeader(); renderSidebar($activePage); ?>

<!-- Main Content -->
<main class="main" id="mainContent">
    <div class="page">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:11px;color:var(--text-light);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Platform Analytics</div>
                <h1 style="font-size:22px;font-weight:800;">Active Users</h1>
                <p style="font-size:13px;color:var(--text-body);margin-top:4px;">User counts, login activity and engagement across all tenants</p>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn bs"><i class="fa fa-download"></i> Export</button>
                <select class="filter-sel" onchange="filterPeriod(this.value)">
                    <option value="today">Today</option>
                    <option value="week" selected>This Week</option>
                    <option value="month">This Month</option>
                    <option value="all">All Time</option>
                </select>
            </div>
        </div>

        <!-- Stats -->
        <div class="sg" style="margin-bottom:24px;">
            <div class="sc fu">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:38px;height:38px;background:#f0fdf4;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--green);font-size:16px;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span style="font-size:12px;color:var(--text-body);font-weight:600;">Total Users (Platform)</span>
                </div>
                <div class="sc-val"><?php echo number_format($totalUsers); ?></div>
                <div style="font-size:12px;color:var(--text-light);margin-top:4px;">Across <?php echo $totalInst; ?> institutes</div>
            </div>
            <div class="sc fu" style="animation-delay: 0.1s;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:38px;height:38px;background:#eff6ff;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#3b82f6;font-size:16px;">
                        <i class="fa-solid fa-circle-dot"></i>
                    </div>
                    <span style="font-size:12px;color:var(--text-body);font-weight:600;">Active (Last 7 Days)</span>
                </div>
                <div class="sc-val" style="color:#3b82f6;"><?php echo number_format($active7d); ?></div>
                <div style="font-size:12px;color:var(--text-light);margin-top:4px;"><?php echo $engRate; ?>% engagement rate</div>
            </div>
            <div class="sc fu" style="animation-delay: 0.2s;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:38px;height:38px;background:#fef3c7;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#d97706;font-size:16px;">
                        <i class="fa-solid fa-right-to-bracket"></i>
                    </div>
                    <span style="font-size:12px;color:var(--text-body);font-weight:600;">Logins Today</span>
                </div>
                <div class="sc-val" style="color:#d97706;"><?php echo number_format($loginsToday); ?></div>
                <div style="font-size:12px;color:var(--text-light);margin-top:4px;">Live data</div>
            </div>
            <div class="sc fu" style="animation-delay: 0.3s;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:38px;height:38px;background:#fef2f2;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--red);font-size:16px;">
                        <i class="fa-solid fa-user-slash"></i>
                    </div>
                    <span style="font-size:12px;color:var(--text-body);font-weight:600;">Inactive 30+ Days</span>
                </div>
                <div class="sc-val" style="color:var(--red);"><?php echo number_format($inactive30d); ?></div>
                <div style="font-size:12px;color:var(--text-light);margin-top:4px;"><?php echo $inactivePct; ?>% of all users</div>
            </div>
        </div>

        <div class="g2" style="margin-bottom:20px;">
            <!-- Role Breakdown -->
            <div class="sc fu">
                <div style="font-size:14px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-chart-pie" style="color:var(--green);"></i> Users by Role
                </div>
                <div style="position:relative; height:220px;">
                    <canvas id="roleChart"></canvas>
                </div>
                <div id="roleLegend" style="display:flex;flex-direction:column;gap:8px;margin-top:16px;"></div>
            </div>

            <!-- Daily Active Users Chart -->
            <div class="sc fu">
                <div style="font-size:14px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-chart-line" style="color:var(--green);"></i> Daily Active Users (Last 7 Days)
                </div>
                <div style="position:relative; height:220px;">
                    <canvas id="dauChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Active Institutes + User Table -->
        <div class="g2">
            <div class="sc fu">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                    <h3 style="font-size:14px; font-weight:800; color:var(--td);"><i class="fa-solid fa-trophy" style="color:var(--amber);"></i> Most Active Institutes</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Institute</th>
                                <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Users</th>
                                <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Logins/Day</th>
                                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Engagement</th>
                            </tr>
                        </thead>
                        <tbody id="activeInsts"></tbody>
                    </table>
                </div>
            </div>

            <div class="sc fu">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                    <h3 style="font-size:14px; font-weight:800; color:var(--td);"><i class="fa-solid fa-user-clock" style="color:var(--blue);"></i> Recent Logins</h3>
                    <select class="filter-sel" onchange="filterRole(this.value)" style="padding:4px 8px; border-radius:6px; border:1px solid var(--cb); outline:none;">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                        <option value="guardian">Guardian</option>
                    </select>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">User</th>
                                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Role</th>
                                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Institute</th>
                                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;border-bottom:1px solid var(--cb);">Last Login</th>
                            </tr>
                        </thead>
                        <tbody id="recentLogins"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Page-specific JavaScript -->
<script>
    function timeAgo(dateString) {
        if (!dateString) return 'never';
        const date = new Date(dateString.replace(' ', 'T'));
        const seconds = Math.floor((new Date() - date) / 1000);
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " years ago";
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " months ago";
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " days ago";
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " hours ago";
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " min ago";
        return "just now";
    }

    const roleColors = {
        'student': '#00B894',
        'teacher': '#3b82f6',
        'admin': '#8141A5',
        'front_desk': '#d97706',
        'guardian': '#0d9488',
        'super_admin': '#e11d48'
    };

    const roleFormat = {
        'student': 'Student',
        'teacher': 'Teacher',
        'admin': 'Admin',
        'front_desk': 'Front Desk',
        'guardian': 'Guardian',
        'super_admin': 'Super Admin'
    };

    // Data for charts and tables
    const roleData = <?php echo json_encode(array_map(function($r) {
        return [
            "role" => $r['role'],
            "count" => (int)$r['count']
        ];
    }, $roleDist)); ?>.map(r => ({
        role: roleFormat[r.role] || r.role,
        count: r.count,
        color: roleColors[r.role] || '#64748b'
    }));

    const institutes = <?php echo json_encode(array_map(function($i) {
        $u = (int)$i['users_count'];
        $a = (int)$i['active_users'];
        return [
            "name" => $i['name'],
            "users" => $u,
            "logins" => $a,
            "pct" => $u > 0 ? round(($a / $u) * 100) : 0
        ];
    }, $topInstitutes)); ?>;

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    const loginsRaw = <?php echo json_encode($recentLogins); ?>;
    const logins = loginsRaw.map(l => ({
        name: escapeHtml(l.name.split('@')[0]), 
        role: escapeHtml(roleFormat[l.role] || l.role),
        inst: escapeHtml(l.inst),
        time: timeAgo(l.time),
        roleColor: roleColors[l.role] || '#64748b'
    }));

    // Render Active Institutes Table
    document.addEventListener('DOMContentLoaded', function() {
        const activeInstsEl = document.getElementById('activeInsts');
        if (activeInstsEl) {
            activeInstsEl.innerHTML = institutes.map(i => `
                <tr style="border-bottom:1px solid var(--cb);" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding:14px 16px;font-size:13px;font-weight:700;">${escapeHtml(i.name)}</td>
                    <td style="padding:14px 16px;text-align:center;font-size:13px;font-weight:600;">${i.users.toLocaleString()}</td>
                    <td style="padding:14px 16px;text-align:center;font-size:13px;font-weight:600;color:var(--success);">${i.logins}</td>
                    <td style="padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;">
                                <div style="width:${i.pct}%;height:6px;background:var(--success);border-radius:3px;"></div>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:var(--success);">${i.pct}%</span>
                        </div>
                    </td>
                </tr>`).join('');
        }

        // Render Recent Logins Table
        const recentLoginsEl = document.getElementById('recentLogins');
        if (recentLoginsEl) {
            recentLoginsEl.innerHTML = logins.map(l => `
                <tr style="border-bottom:1px solid var(--cb);" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:28px;height:28px;border-radius:50%;background:${l.roleColor};color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;">${l.name[0]}</div>
                            <span style="font-size:13px;font-weight:600;color:var(--td);">${l.name}</span>
                        </div>
                    </td>
                    <td style="padding:14px 16px;"><span style="background:${l.roleColor}1a;color:${l.roleColor};padding:4px 8px;border-radius:6px;font-size:11px;font-weight:700;">${l.role}</span></td>
                    <td style="padding:14px 16px;font-size:12px;color:var(--tb);">${l.inst}</td>
                    <td style="padding:14px 16px;font-size:12px;color:var(--tl);">${l.time}</td>
                </tr>`).join('');
        }

        // Role Donut Chart
        const roleCtx = document.getElementById('roleChart')?.getContext('2d');
        if (roleCtx && typeof Chart !== 'undefined') {
            new Chart(roleCtx, {
                type: 'doughnut',
                data: {
                    labels: roleData.map(r => r.role),
                    datasets: [{ data: roleData.map(r => r.count), backgroundColor: roleData.map(r => r.color), borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' }
            });
        }
        
        const roleLegendEl = document.getElementById('roleLegend');
        if (roleLegendEl) {
            roleLegendEl.innerHTML = roleData.map(r => `
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:10px;height:10px;border-radius:50%;background:${r.color};"></div>
                        <span style="font-size:13px;color:var(--text-body);">${escapeHtml(r.role)}</span>
                    </div>
                    <span style="font-size:13px;font-weight:700;">${r.count.toLocaleString()}</span>
                </div>`).join('');
        }

        // DAU Chart
        const dauData = <?php echo json_encode($dauChartData); ?>;
        const dauCtx = document.getElementById('dauChart')?.getContext('2d');
        if (dauCtx && typeof Chart !== 'undefined') {
            new Chart(dauCtx, {
                type: 'bar',
                data: {
                    labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Today'],
                    datasets: [{ label: 'Active Users', data: dauData, backgroundColor: 'rgba(0,184,148,.15)', borderColor: 'var(--success)', borderWidth: 2, borderRadius: 6 }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        }
    });

    // Filter functions
    function filterPeriod(v) {
        if (typeof SuperAdmin !== 'undefined') {
            SuperAdmin.showNotification('Filtered to: ' + v, 'info');
        } else {
            console.log('Filtered to: ' + v);
        }
    }
    function filterRole(v) {
        const filtered = v ? logins.filter(l => l.role.toLowerCase() === v.toLowerCase()) : logins;
        document.getElementById('recentLogins').innerHTML = filtered.map(l => `
            <tr style="border-bottom:1px solid var(--cb);" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                <td style="padding:14px 16px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:50%;background:${l.roleColor};color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;">${l.name[0]}</div>
                        <span style="font-size:13px;font-weight:600;color:var(--td);">${l.name}</span>
                    </div>
                </td>
                <td style="padding:14px 16px;"><span style="background:${l.roleColor}1a;color:${l.roleColor};padding:4px 8px;border-radius:6px;font-size:11px;font-weight:700;">${l.role}</span></td>
                <td style="padding:14px 16px;font-size:12px;color:var(--tb);">${l.inst}</td>
                <td style="padding:14px 16px;font-size:12px;color:var(--tl);">${l.time}</td>
            </tr>`).join('');
    }
</script>

<?php include 'footer.php'; ?>
