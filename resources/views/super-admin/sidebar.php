<?php
/**
 * Hamro ERP — Super Admin Sidebar Component
 * Refactored to match Institute Admin sidebar structure. to 
 */

function getSuperAdminMenu() {
    return [
        'overview' => [
            'title' => 'Dashboard',
            'items' => [
                [
                    'label' => 'Overview',
                    'icon'  => 'fa-house',
                    'nav'   => 'overview',
                    'href'  => APP_URL . '/dash/super-admin/index',
                ]
            ]
        ],
        'tenants' => [
            'title' => 'Tenants',
            'items' => [
                [
                    'label'       => 'Tenant Management',
                    'icon'        => 'fa-building',
                    'nav'         => 'tenants',
                    'sub'         => 'all',
                    'href'        => APP_URL . '/dash/super-admin/tenant-management',
                    'has_submenu' => true,
                    'submenu_id'  => 'tenant_mgmt',
                    'submenu'     => [
                        ['label' => 'All Institutes',      'nav' => 'tenants', 'sub' => 'all',   'href' => APP_URL . '/dash/super-admin/tenant-management'],
                        ['label' => 'Add New Institute',   'nav' => 'tenants', 'sub' => 'add',   'href' => APP_URL . '/dash/super-admin/add-tenant'],
                        ['label' => 'Suspended Institutes','nav' => 'tenants', 'sub' => 'suspended', 'href' => APP_URL . '/dash/super-admin/tenant-management?filter=suspended'],
                    ]
                ]
            ]
        ],
        'plans' => [
            'title' => 'Subscription',
            'items' => [
                [
                    'label'       => 'Plan Management',
                    'icon'        => 'fa-clipboard-list',
                    'nav'         => 'plans',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'plan_mgmt',
                    'submenu'     => [
                        ['label' => 'Subscription Plans', 'nav' => 'plans', 'sub' => 'sub-plans', 'href' => APP_URL . '/dash/super-admin/plans'],
                        ['label' => 'Feature Flags',      'nav' => 'plans', 'sub' => 'flags',     'href' => APP_URL . '/dash/super-admin/flags'],
                        ['label' => 'Plan Assignment',    'nav' => 'plans', 'sub' => 'assign',    'href' => APP_URL . '/dash/super-admin/plan-assign'],
                    ]
                ]
            ]
        ],
        'revenue' => [
            'title' => 'Revenue',
            'items' => [
                [
                    'label'       => 'Revenue Analytics',
                    'icon'        => 'fa-file-invoice-dollar',
                    'nav'         => 'revenue',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'rev_mgmt',
                    'submenu'     => [
                        ['label' => 'MRR / ARR Dashboard', 'nav' => 'revenue', 'sub' => 'mrr',      'href' => APP_URL . '/dash/super-admin/revenue-analytics'],
                        ['label' => 'Payment History',     'nav' => 'revenue', 'sub' => 'payments', 'href' => APP_URL . '/dash/super-admin/payments'],
                        ['label' => 'Invoice Generator',   'nav' => 'revenue', 'sub' => 'invoices', 'href' => APP_URL . '/dash/super-admin/invoices'],
                    ]
                ]
            ]
        ],
        'platform' => [
            'title' => 'Platform',
            'items' => [
                [
                    'label'       => 'Platform Analytics',
                    'icon'        => 'fa-chart-pie',
                    'nav'         => 'analytics',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'plat_mgmt',
                    'submenu'     => [
                        ['label' => 'Active Users',           'nav' => 'analytics', 'sub' => 'users',   'href' => APP_URL . '/dash/super-admin/users'],
                        ['label' => 'Feature Usage Heatmap',  'nav' => 'analytics', 'sub' => 'heatmap', 'href' => APP_URL . '/dash/super-admin/heatmap'],
                        ['label' => 'SMS Credit Consumption', 'nav' => 'analytics', 'sub' => 'sms',     'href' => APP_URL . '/dash/super-admin/sms-credits'],
                        ['label' => 'Report Engine',          'nav' => 'analytics', 'sub' => 'reports', 'href' => APP_URL . '/dash/super-admin/reports'],
                    ]
                ]
            ]
        ],
        'support' => [
            'title' => 'Support',
            'items' => [
                [
                    'label'       => 'Support Tickets',
                    'icon'        => 'fa-ticket',
                    'nav'         => 'support',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'supp_mgmt',
                    'submenu'     => [
                        ['label' => 'Open Tickets',            'nav' => 'support', 'sub' => 'open',      'href' => APP_URL . '/dash/super-admin/support-tickets?status=open'],
                        ['label' => 'User Feedbacks',          'nav' => 'support', 'sub' => 'feedbacks', 'href' => APP_URL . '/dash/super-admin/feedbacks'],
                        ['label' => 'Tenant Impersonation Log', 'nav' => 'support', 'sub' => 'impersonate','href' => APP_URL . '/dash/super-admin/impersonation-logs'],
                        ['label' => 'Resolved History',        'nav' => 'support', 'sub' => 'resolved',  'href' => APP_URL . '/dash/super-admin/support-tickets?status=resolved'],
                    ]
                ]
            ]
        ],
        'configuration' => [
            'title' => 'Internal',
            'items' => [
                [
                    'label'       => 'System Configuration',
                    'icon'        => 'fa-wrench',
                    'nav'         => 'system',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'sys_conf',
                    'submenu'     => [
                        ['label' => 'Feature Toggles',   'nav' => 'system', 'sub' => 'toggles',     'href' => APP_URL . '/dash/super-admin/flags'],
                        ['label' => 'Maintenance Mode',  'nav' => 'system', 'sub' => 'maintenance', 'href' => APP_URL . '/dash/super-admin/maintenance'],
                        ['label' => 'Push Announcements','nav' => 'system', 'sub' => 'announce',    'href' => APP_URL . '/dash/super-admin/announcements'],
                    ]
                ],
                [
                    'label'       => 'System Logs',
                    'icon'        => 'fa-scroll',
                    'nav'         => 'logs',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'sys_logs',
                    'submenu'     => [
                        ['label' => 'Audit Logs',        'nav' => 'logs', 'sub' => 'audit',  'href' => APP_URL . '/dash/super-admin/logs?type=audit'],
                        ['label' => 'Error Logs',        'nav' => 'logs', 'sub' => 'errors', 'href' => APP_URL . '/dash/super-admin/logs?type=error'],
                        ['label' => 'API Request Logs',  'nav' => 'logs', 'sub' => 'api',    'href' => APP_URL . '/dash/super-admin/logs?type=api'],
                    ]
                ]
            ]
        ],
        'sett' => [
            'title' => 'Settings',
            'items' => [
                [
                    'label'       => 'Settings',
                    'icon'        => 'fa-gear',
                    'nav'         => 'settings',
                    'href'        => '#',
                    'has_submenu' => true,
                    'submenu_id'  => 'plat_sett',
                    'submenu'     => [
                        ['label' => 'Platform Branding',    'nav' => 'settings', 'sub' => 'branding', 'href' => APP_URL . '/dash/super-admin/branding'],
                        ['label' => 'Default SMS Templates','nav' => 'settings', 'sub' => 'sms-tpl',  'href' => APP_URL . '/dash/super-admin/sms-templates'],
                        ['label' => 'Email Config',         'nav' => 'settings', 'sub' => 'email-cfg','href' => APP_URL . '/dash/super-admin/email-config'],
                    ]
                ]
            ]
        ]
    ];
}

function getCurrentPage() {
    return basename($_SERVER['PHP_SELF']);
}

function renderSidebar($activePage = null) {
    if (isset($_GET['partial']) && $_GET['partial'] == 'true') {
        return;
    }
    $menu        = getSuperAdminMenu();
    $currentFile = $activePage ?? getCurrentPage();
    
    // Support both ?page= (JavaScript) and ?nav=/ ?sub= (legacy) parameters
    // Parse ?page=tenants-all format used by JavaScript navigation
    $pageParam = $_GET['page'] ?? '';
    if (!empty($pageParam)) {
        $parts = explode('-', $pageParam, 2);
        $currentNav = $parts[0] ?? '';
        $currentSub = $parts[1] ?? '';
    } else {
        $currentNav  = $_GET['nav'] ?? '';
        $currentSub  = $_GET['sub'] ?? '';
    }

    // Resolve active states
    foreach ($menu as &$section) {
        foreach ($section['items'] as &$item) {
            $itemHref = basename(strtok($item['href'], '?'));
            if ($itemHref === $currentFile) {
                $item['active'] = true;
            }
            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as $sub) {
                    if (basename(strtok($sub['href'], '?')) === $currentFile) {
                        $item['active']       = true;
                        $item['submenu_open'] = true;
                    }
                }
            }
        }
    }
    unset($section, $item);
    ?>
    <!-- ── SIDEBAR (same structure as institute-admin) ── -->
    <nav class="sb" id="sidebar">
        <div class="sb-body" id="sbBody">
            <?php foreach ($menu as $section): ?>
                <div class="sb-sec">
                    <div class="sb-lbl"><?php echo $section['title']; ?></div>

                    <?php foreach ($section['items'] as $item):
                        $isActive = !empty($item['active']);
                        $hasSubmenu = !empty($item['has_submenu']);
                    ?>
                        <?php if ($hasSubmenu): ?>
                            <button class="nb-btn <?php echo $isActive ? 'active' : ''; ?>"
                                    onclick="SuperAdmin.toggleMenu('<?php echo $item['submenu_id']; ?>')">
                                <i class="fa-solid <?php echo $item['icon']; ?> nbi"></i>
                                <span class="nbl"><?php echo $item['label']; ?></span>
                                <i class="fa-solid fa-chevron-right nbc <?php echo !empty($item['submenu_open']) ? 'open' : ''; ?>"
                                   id="chev-<?php echo $item['submenu_id']; ?>"></i>
                            </button>
                            <div class="sub-menu <?php echo !empty($item['submenu_open']) ? 'open' : ''; ?>"
                                 id="<?php echo $item['submenu_id']; ?>"
                                 style="<?php echo empty($item['submenu_open']) ? 'display:none;' : ''; ?>">
                                <?php foreach ($item['submenu'] as $sub):
                                    $subActive = false;
                                    if (isset($sub['nav']) && isset($sub['sub'])) {
                                        $subActive = ($sub['nav'] === $currentNav && $sub['sub'] === $currentSub);
                                    } else {
                                        $subActive = (basename(strtok($sub['href'] ?? '', '?')) === $currentFile);
                                    }
                                    $onClick = (isset($sub['nav'])) ? " onclick=\"goNav('" . $sub['nav'] . "', '" . ($sub['sub'] ?? '') . "')\"" : '';

                                    $href = (isset($sub['nav'])) ? '#' : ($sub['href'] ?? '#');
                                ?>
                                    <a href="<?php echo $href; ?>" <?php echo $onClick; ?>
                                       class="sub-btn <?php echo $subActive ? 'active' : ''; ?>">
                                        <i class="fa-solid fa-circle" style="font-size: 4px; opacity: 0.5;"></i>
                                        <?php echo $sub['label']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo $item['href']; ?>"
                               class="nb-btn <?php echo $isActive ? 'active' : ''; ?>">
                                <i class="fa-solid <?php echo $item['icon']; ?> nbi"></i>
                                <span class="nbl"><?php echo $item['label']; ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Platform Context Footer (mirrors IA footer) -->
        <div class="sb-footer" style="padding: 15px 20px; border-top: 1px solid var(--cb); margin-top: auto;">
             <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; background: var(--sa-primary-lt); color: var(--sa-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; border: 1px solid rgba(0, 158, 126, 0.2);">
                    HQ
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 12px; font-weight: 700; color: var(--td); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">PLATFORM HQ</div>
                    <div style="font-size: 10px; color: var(--tl);">Super Admin Node</div>
                </div>
             </div>
        </div>
    </nav>
    <?php
}
?>
