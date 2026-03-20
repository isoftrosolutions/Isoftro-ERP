<?php
/**
 * iSoftro — Front Desk Sidebar Component
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
    /* ══════════════════ SIDEBAR ══════════════════ */
    .sb-overlay{
      position:fixed;inset:0;background:rgba(15,23,42,.4);backdrop-filter:blur(2px);
      z-index:998;opacity:0;visibility:hidden;transition:.3s;
    }
    .sb{
      position:fixed;top:var(--hdr-h);left:0;bottom:0;width:var(--sb-w);
      background:#fff;z-index:999;
      border-right:1px solid var(--card-border);
      display:flex;flex-direction:column;overflow:hidden;
      transform:translateX(0);transition:transform .3s cubic-bezier(.4,0,.2,1);
    }
    .sb-body{flex:1;overflow-y:auto;padding:12px 0 30px}
    .sb-sec-lbl{
      font-size:10px;font-weight:800;color:var(--text-light);
      text-transform:uppercase;padding:18px 24px 8px;letter-spacing:.8px;
    }
    .sb-btn{
      width:100%;display:flex;align-items:center;gap:12px;
      padding:11px 24px;border:none;background:none;color:var(--text-body);
      font-size:14px;font-weight:500;cursor:pointer;transition:0.2s;
      border-left:4px solid transparent;font-family:var(--font);text-align:left;
    }
    .sb-btn:hover{background:#f8fafc;color:var(--green-d)}
    .sb-btn.active{background:#f0fdfa;color:var(--green-d);border-left-color:var(--green);font-weight:700}
    .sb-btn i{width:20px;text-align:center;font-size:15px;flex-shrink:0;opacity:0.8}
    .sb-btn.active i { opacity: 1; }
    .sb-lbl{flex:1}
    .sb-badge{
      font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;
      background:var(--red);color:#fff;min-width:18px;text-align:center;
    }
    .sb-badge.green{background:var(--green)}
    .sb-badge.amber{background:var(--amber)}
    .sb-divider{height:1px;background:var(--card-border);margin:10px 20px;opacity:0.6}
    .sb-footer{
      padding:15px 20px;border-top:1px solid var(--card-border);
      display:flex;align-items:center;gap:12px;background:#f8fafc;
    }
    .sb-user-av{
      width:38px;height:38px;border-radius:10px;background:var(--green-gradient);
      display:flex;align-items:center;justify-content:center;
      color:#fff;font-weight:800;font-size:14px;flex-shrink:0;
      box-shadow: 0 2px 4px rgba(26, 188, 156, 0.2);
    }
    .sb-user-name{font-size:13px;font-weight:700;color:var(--text-dark);line-height:1.2}
    .sb-user-role{font-size:11px;color:var(--text-light);font-weight:500}
    .online-dot{width:9px;height:9px;border-radius:50%;background:#10b981;display:inline-block;border:2px solid #fff}
    
    @media(max-width:1024px){
      .sb{transform:translateX(-100%)}
      body.sb-active .sb{transform:translateX(0)}
      body.sb-active .sb-overlay{opacity:1;visibility:visible}
    }
    </style>

    <?php
    $user = getCurrentUser();
    $userName = $user['name'] ?? 'Operator';
    $instituteName = $_SESSION['tenant_name'] ?? 'isoftro';
    
    // User initials for sidebar footer
    $parts = explode(' ', $userName);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    ?>
    <aside class="sb" id="sidebar">
      <div class="sb-body">
        <div class="sb-sec-lbl">Overview</div>
        <button class="sb-btn active" onclick="goNav('dashboard')">
          <i class="fa fa-chart-pie"></i><span class="sb-lbl">Dashboard</span>
        </button>
        <button class="sb-btn" onclick="goNav('attendance')">
          <i class="fa fa-calendar-check"></i><span class="sb-lbl">Today's Attendance</span>
          <span class="sb-badge amber">12</span>
        </button>

        <div class="sb-sec-lbl">Admissions</div>
        <button class="sb-btn" onclick="goNav('admissions', 'adm-all')">
          <i class="fa fa-user-graduate"></i><span class="sb-lbl">Student Lookup</span>
        </button>
        <button class="sb-btn" onclick="goNav('admissions', 'adm-form')">
          <i class="fa fa-user-plus"></i><span class="sb-lbl">New Admission</span>
        </button>
        <button class="sb-btn" onclick="goNav('admissions', 'enroll-existing')">
          <i class="fa fa-user-graduate"></i><span class="sb-lbl">Enroll Existing</span>
        </button>
        <button class="sb-btn" onclick="goNav('operations', 'inq-list')">
          <i class="fa fa-comments"></i><span class="sb-lbl">Inquiries</span>
          <span class="sb-badge">7</span>
        </button>

        <div class="sb-sec-lbl">Fee &amp; Finance</div>
        <button class="sb-btn" onclick="goNav('fee', 'fee-coll')">
          <i class="fa fa-money-bill-wave"></i><span class="sb-lbl">Fee Collection</span>
        </button>
        <button class="sb-btn" onclick="goNav('fee', 'fee-sum')">
          <i class="fa fa-file-invoice-dollar"></i><span class="sb-lbl">Fee Reports</span>
        </button>
        <button class="sb-btn" onclick="goNav('pending-dues')">
          <i class="fa fa-clock"></i><span class="sb-lbl">Pending Dues</span>
          <span class="sb-badge">18</span>
        </button>

        <div class="sb-sec-lbl">Operations</div>
        <button class="sb-btn" onclick="goNav('leave-requests')">
          <i class="fa fa-user-clock"></i><span class="sb-lbl">Leave Requests</span>
          <span class="sb-badge amber">5</span>
        </button>
        <button class="sb-btn" onclick="goNav('library')">
          <i class="fa fa-book"></i><span class="sb-lbl">Library Desk</span>
        </button>
        <button class="sb-btn" onclick="goNav('timetable')">
          <i class="fa fa-table"></i><span class="sb-lbl">Today's Timetable</span>
        </button>
        <button class="sb-btn" onclick="goNav('announcements')">
          <i class="fa fa-bullhorn"></i><span class="sb-lbl">Announcements</span>
          <span class="sb-badge green">2</span>
        </button>

        <div class="sb-divider"></div>

        <button class="sb-btn" onclick="goNav('support')">
          <i class="fa fa-headset"></i><span class="sb-lbl">Support Tickets</span>
        </button>
        <button class="sb-btn" onclick="goNav('audit-log')">
          <i class="fa fa-shield-alt"></i><span class="sb-lbl">Activity Log</span>
        </button>
      </div>

      <div class="sb-footer">
        <div class="sb-user-av"><?= $initials ?></div>
        <div style="overflow:hidden;">
          <div class="sb-user-name" style="white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= htmlspecialchars($userName) ?></div>
          <div class="sb-user-role"><?= htmlspecialchars($instituteName) ?></div>
        </div>
        <div style="margin-left:auto"><span class="online-dot"></span></div>
      </div>
    </aside>
    <div class="sb-overlay" onclick="document.body.classList.remove('sb-active')"></div>
    <?php
}
?>
