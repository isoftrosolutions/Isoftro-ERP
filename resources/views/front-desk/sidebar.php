<?php
/**
 * Hamro ERP — Front Desk Sidebar Component
 * Light theme, green accents, reorganized as per mockup
 */

function renderFrontDeskHeader() {
    if (isset($_GET['partial']) && $_GET['partial'] == 'true') return;
    require __DIR__ . '/header.php';
}

function getFrontDeskMenu() {
    return [
        'main' => [
            'title' => 'Main',
            'items' => [
                [
                    'label' => 'Overview',
                    'icon'  => 'fa-chart-pie',
                    'href'  => "javascript:goNav('dashboard')",
                    'id'    => 'dashboard'
                ]
            ]
        ],
        'admissions' => [
            'title' => 'Admissions',
            'items' => [
                [
                    'label' => 'New Admission',
                    'icon'  => 'fa-user-plus',
                    'href'  => "javascript:goNav('admissions', 'adm-form')",
                    'id'    => 'adm-form'
                ],
                [
                    'label' => 'Students List',
                    'icon'  => 'fa-users',
                    'href'  => "javascript:goNav('admissions', 'adm-all')",
                    'id'    => 'adm-all'
                ]
            ]
        ],
        'finance' => [
            'title' => 'Fee & Finance',
            'items' => [
                [
                    'label' => 'Collect Fee',
                    'icon'  => 'fa-money-bill-transfer',
                    'href'  => "javascript:goNav('fee', 'fee-coll')",
                    'id'    => 'fee-coll'
                ],
                [
                    'label' => 'Fee Reports',
                    'icon'  => 'fa-file-invoice-dollar',
                    'href'  => "javascript:goNav('fee', 'fee-sum')",
                    'id'    => 'fee-sum'
                ]
            ]
        ],
        'operations' => [
            'title' => 'Operations',
            'items' => [
                [
                    'label' => 'Inquiries',
                    'icon'  => 'fa-magnifying-glass-plus',
                    'href'  => "javascript:goNav('operations', 'inq-list')",
                    'id'    => 'inq-list'
                ],
                [
                    'label' => 'Visits Log',
                    'icon'  => 'fa-address-book',
                    'href'  => "javascript:goNav('operations', 'visitor-log')",
                    'id'    => 'visitor-log'
                ]
            ]
        ]
    ];
}

function renderFrontDeskSidebar($activePage = null) {
    if (isset($_GET['partial']) && $_GET['partial'] == 'true') return;
    $menu = getFrontDeskMenu();
    $pageParam = $_GET['page'] ?? '';
    ?>
    <style>
    .sb {
        position: fixed; top: var(--hdr-h); left: 0; bottom: 0; width: var(--sb-w);
        background: #fff; border-right: 1px solid #eef2f6; z-index: 999;
        transition: transform 0.3s ease; overflow-y: auto;
    }
    @media (max-width: 1024px) { .sb { transform: translateX(-100%); } body.sb-active .sb { transform: translateX(0); } }
    .sb-sec { padding: 12px 0 0; }
    .sb-lbl { padding: 10px 24px; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    .nb-btn {
        display: flex; align-items: center; gap: 12px; padding: 12px 24px;
        color: #64748b; font-size: 13.5px; font-weight: 600; text-decoration: none;
        transition: .2s; border-left: 3px solid transparent;
    }
    .nb-btn:hover { background: #f8fafc; color: var(--green); }
    .nb-btn.active { background: #f0fdf4; color: var(--green); border-left-color: var(--green); }
    .nb-btn i { width: 18px; text-align: center; font-size: 15px; }
    .sb-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.3); backdrop-filter: blur(2px);
        z-index: 998; display: none;
    }
    body.sb-active .sb-overlay { display: block; }
    </style>

    <nav class="sb" id="sbBody">
        <?php foreach ($menu as $secKey => $section): ?>
            <div class="sb-sec">
                <div class="sb-lbl"><?= $section['title'] ?></div>
                <?php foreach ($section['items'] as $item): 
                    $isActive = false;
                    if (empty($pageParam) && $item['id'] == 'dashboard') $isActive = true;
                    if (!empty($pageParam) && strpos($item['href'], "page=".$pageParam) !== false) $isActive = true;
                ?>
                    <a href="<?= $item['href'] ?>" class="nb-btn <?= $isActive ? 'active' : '' ?>">
                        <i class="fa <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
    <div class="sb-overlay" onclick="document.body.classList.remove('sb-active')"></div>
    <?php
}
?>
