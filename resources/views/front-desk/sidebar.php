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
    /* ══════════════════ SIDEBAR ══════════════════ */
    .sb-overlay{
      position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(2px);
      z-index:998;opacity:0;visibility:hidden;transition:.3s;
    }
    .sb{
      position:fixed;top:var(--hdr-h);left:0;bottom:0;width:var(--sb-w);
      background:var(--white, #ffffff);z-index:999;
      border-right:1px solid var(--card-border, #E2E8F0);
      display:flex;flex-direction:column;overflow:hidden;
      transform:translateX(0);transition:transform .3s cubic-bezier(.4,0,.2,1);
    }
    .sb-body{flex:1;overflow-y:auto;padding:10px 0 20px}
    .sb-sec-lbl{
      font-size:10px;font-weight:700;color:var(--text-light, #94A3B8);
      text-transform:uppercase;padding:14px 20px 6px;letter-spacing:.6px;
    }
    .sb-btn{
      width:100%;display:flex;align-items:center;gap:11px;
      padding:10px 20px;border:none;background:none;color:var(--text-body, #475569);
      font-size:13.5px;font-weight:500;cursor:pointer;transition:.15s;
      border-left:3px solid transparent;font-family:var(--font, 'Plus Jakarta Sans', sans-serif);text-align:left;
    }
    .sb-btn:hover{background:#f1f5f9;color:var(--text-dark, #1E293B)}
    .sb-btn.active{background:#e6f7f3;color:var(--green, #00B894);border-left-color:var(--green, #00B894);font-weight:700}
    .sb-btn i{width:18px;text-align:center;font-size:14px;flex-shrink:0}
    .sb-lbl{flex:1}
    .sb-badge{
      font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;
      background:var(--red, #E11D48);color:#fff;min-width:18px;text-align:center;
    }
    .sb-badge.green{background:var(--green, #00B894)}
    .sb-badge.amber{background:var(--amber, #F59E0B)}
    .sb-divider{height:1px;background:var(--card-border, #E2E8F0);margin:8px 16px}
    .sb-footer{
      padding:14px 16px;border-top:1px solid var(--card-border, #E2E8F0);
      display:flex;align-items:center;gap:10px;
    }
    .sb-user-av{
      width:34px;height:34px;border-radius:50%;background:var(--green, #00B894);
      display:flex;align-items:center;justify-content:center;
      color:#fff;font-weight:800;font-size:13px;flex-shrink:0;
    }
    .sb-user-name{font-size:12px;font-weight:700;color:var(--text-dark, #1E293B)}
    .sb-user-role{font-size:11px;color:var(--text-light, #94A3B8)}
    .online-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;flex-shrink:0}
    
    @media(max-width:1024px){
      .sb{transform:translateX(-100%)}
      body.sb-active .sb{transform:translateX(0)}
      body.sb-active .sb-overlay{opacity:1;visibility:visible}
    }
    </style>

    <aside class="sb" id="sidebar">
      <div class="sb-body">
        <div class="sb-sec-lbl">Overview</div>
        <button class="sb-btn active" onclick="goNav('dashboard')">
          <i class="fa fa-th-large"></i><span class="sb-lbl">Dashboard</span>
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
        <button class="sb-btn" onclick="goNav('operations', 'inq-list')">
          <i class="fa fa-comments"></i><span class="sb-lbl">Inquiries</span>
          <span class="sb-badge">7</span>
        </button>

        <div class="sb-sec-lbl">Fee &amp; Finance</div>
        <button class="sb-btn" onclick="goNav('fee', 'fee-coll')">
          <i class="fa fa-money-bill-wave"></i><span class="sb-lbl">Fee Collection</span>
        </button>
        <button class="sb-btn" onclick="goNav('transactions')">
          <i class="fa fa-exchange-alt"></i><span class="sb-lbl">Transactions</span>
        </button>
        <button class="sb-btn" onclick="goNav('pending-dues')">
          <i class="fa fa-clock"></i><span class="sb-lbl">Pending Dues</span>
          <span class="sb-badge">18</span>
        </button>
        <button class="sb-btn" onclick="goNav('receipts')">
          <i class="fa fa-receipt"></i><span class="sb-lbl">Receipts</span>
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
        <div class="sb-user-av">SD</div>
        <div>
          <div class="sb-user-name">Sunita Devi</div>
          <div class="sb-user-role">Front Desk · Nepal Cyber Firm</div>
        </div>
        <div style="margin-left:auto"><span class="online-dot"></span></div>
      </div>
    </aside>
    <div class="sb-overlay" onclick="document.body.classList.remove('sb-active')"></div>
    <?php
}
?>
