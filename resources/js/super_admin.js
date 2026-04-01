/**
 * iSoftro ERP — Super Admin JavaScript Module
 * Refactored to use unified class names matching institute-admin.
 *
 * Sidebar classes : .sb, body.sb-active, body.sb-collapsed
 * Header classes  : .hdr, .sb-toggle, .hbtn
 * Nav classes     : .nb-btn, .nbc, .sub-menu, .sub-btn
 */

const SuperAdmin = (function () {
  "use strict";

  let charts     = {};
  let dataTables = {};

  /* ============================================================
     STATE & CONFIG
     ============================================================ */

  const urlParams = new URLSearchParams(window.location.search);
  const initialPage = urlParams.get('page') || 'overview';
  
  let activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
  let activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;

  /* ============================================================
     INIT
     ============================================================ */

  function init() {
    initSidebar();
    initDropdowns();
    initCharts();
    initModals();
    renderPage();
    console.log("[SuperAdmin] Module initialised");
  }

  /* ============================================================
     PAGE RENDERING
     ============================================================ */

  function renderPage() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

    // Route to appropriate page
    switch(activeNav) {
      case 'overview':
        renderDashboard();
        break;
      case 'tenants':
        fetchAndRender('pages/super_admin/tenant-management.php');
        break;
      case 'plans':
        if (activeSub === 'sub-plans') fetchAndRender('pages/super_admin/plans.php');
        else if (activeSub === 'flags') fetchAndRender('pages/super_admin/flags.php');
        else if (activeSub === 'assign') fetchAndRender('pages/super_admin/plan-assign.php');
        else fetchAndRender('pages/super_admin/plans.php');
        break;
      case 'revenue':
        if (activeSub === 'mrr') fetchAndRender('pages/super_admin/revenue.php');
        else if (activeSub === 'payments') fetchAndRender('pages/super_admin/payments.php');
        else if (activeSub === 'invoices') fetchAndRender('pages/super_admin/invoices.php');
        else fetchAndRender('pages/super_admin/revenue.php');
        break;
      case 'analytics':
        if (activeSub === 'users') fetchAndRender('pages/super_admin/users.php');
        else if (activeSub === 'heatmap') fetchAndRender('pages/super_admin/heatmap.php');
        else if (activeSub === 'sms') fetchAndRender('pages/super_admin/sms-credits.php');
        else fetchAndRender('pages/super_admin/users.php');
        break;
      case 'support':
        if (activeSub === 'open' || activeSub === 'resolved' || activeSub === 'impersonate') fetchAndRender('pages/super_admin/support.php');
        else fetchAndRender('pages/super_admin/support.php');
        break;
      case 'system':
        if (activeSub === 'toggles') fetchAndRender('pages/super_admin/flags.php');
        else if (activeSub === 'maintenance') fetchAndRender('pages/super_admin/maintenance.php');
        else if (activeSub === 'announce') fetchAndRender('pages/super_admin/announcements.php');
        else if (activeSub === 'email-cfg') fetchAndRender('pages/super_admin/email-config.php');
        else fetchGenericPage('System Configuration');
        break;
      case 'logs':
        if (activeSub === 'audit' || activeSub === 'errors' || activeSub === 'api') fetchAndRender('pages/super_admin/logs.php');
        else if (activeSub === 'db') fetchAndRender('pages/super_admin/db-insights.php');
        else fetchAndRender('pages/super_admin/logs.php');
        break;
      case 'settings':
        if (activeSub === 'branding') fetchAndRender('pages/super_admin/branding.php');
        else if (activeSub === 'sms-tpl') fetchAndRender('pages/super_admin/sms-templates.php');
        else if (activeSub === 'email-cfg') fetchAndRender('pages/super_admin/email-config.php');
        else fetchGenericPage('Settings');
        break;
      case 'profile':
        if (activeSub === 'view') fetchAndRender('pages/super_admin/profile.php');
        else if (activeSub === 'password') fetchAndRender('pages/super_admin/change-password.php');
        else if (activeSub === 'activity') fetchAndRender('pages/super_admin/activity-log.php');
        else fetchAndRender('pages/super_admin/profile.php');
        break;
      default:
        fetchGenericPage(activeNav);
    }
  }

  // Helper function to fetch and render a PHP page
  function fetchAndRender(pagePath) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    fetch(pagePath)
      .then(response => response.text())
      .then(html => {
        mainContent.innerHTML = html;
      })
      .catch(err => {
        console.error('Error loading page:', err);
        mainContent.innerHTML = '<div class="pg fu"><p>Error loading page</p></div>';
      });
  }

  // Helper function to show generic page
  function fetchGenericPage(title) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = `
      <div class="pg fu">
        <div style="text-align:center;padding:60px 20px;">
          <i class="fa-solid fa-tools" style="font-size:4rem;color:var(--tl);margin-bottom:20px;"></i>
          <h2>${title} Module</h2>
          <p style="color:var(--tl);margin-top:10px;">This module is being prepared.</p>
        </div>
      </div>
    `;
  }

  function renderDashboard() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    // Fetch dashboard data via API
    fetchSuperAdminStats().then(stats => {
      mainContent.innerHTML = `
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
                    <button class="btn bs"><i class="fa-solid fa-download"></i> Export Report</button>
                    <button class="btn bt"><i class="fa-solid fa-plus"></i> Add Admin</button>
                </div>
            </div>

            <!-- Stat Cards -->

            
            <div class="sg">
                <div class="sc">
                    <div class="sc-top">
                        <div class="sc-ico ic-t"><i class="fa-solid fa-building"></i></div>
                    </div>
                    <div class="sc-val">${stats.totalInstitutes || 0}</div>
                    <div class="sc-lbl">Total Institutes</div>
                    <div class="sc-delta positive">▲ Live platform data</div>
                </div>
                <div class="sc">
                    <div class="sc-top">
                        <div class="sc-ico ic-g"><i class="fa-solid fa-users"></i></div>
                    </div>
                    <div class="sc-val">${stats.totalUsers || 0}</div>
                    <div class="sc-lbl">Total Users</div>
                    <div class="sc-delta positive">▲ Unified user base</div>
                </div>
                <div class="sc">
                    <div class="sc-top">
                        <div class="sc-ico ic-a"><i class="fa-solid fa-user-graduate"></i></div>
                    </div>
                    <div class="sc-val">${stats.activeStudents || 0}</div>
                    <div class="sc-lbl">Active Students</div>
                    <div class="sc-delta positive">▲ Enrolled globally</div>
                </div>
                <div class="sc">
                    <div class="sc-top">
                        <div class="sc-ico ic-r"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="sc-val">${stats.pendingApprovals || 0}</div>
                    <div class="sc-lbl">Trial Institutes</div>
                    <div class="sc-delta ${(stats.pendingApprovals || 0) > 0 ? 'negative' : 'positive'}">
                        ${(stats.pendingApprovals || 0) > 0 ? '▼ Requires conversion' : '▲ All converted'}
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="g65">
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <span class="ct"><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</span>
                        <button class="btn bs" style="font-size:12px; padding:4px 12px;">View All</button>
                    </div>
                    <div class="tw" style="border:none; border-radius:0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Institute</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${renderRecentActivity(stats.recentActivity)}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="ct"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <button class="btn bt" style="width:100%; justify-content:flex-start;" onclick="SuperAdmin.goNav('tenants', 'add')">
                            <i class="fa-solid fa-plus-circle"></i> Add New Institute
                        </button>
                        <button class="btn bs" style="width:100%; justify-content:flex-start;" onclick="SuperAdmin.goNav('users', 'add')">
                            <i class="fa-solid fa-user-plus"></i> Create Admin Account
                        </button>
                        <button class="btn bs" style="width:100%; justify-content:flex-start;" onclick="SuperAdmin.goNav('db-insights')">
                            <i class="fa-solid fa-database"></i> Backup Database
                        </button>
                        <button class="btn bs" style="width:100%; justify-content:flex-start;" onclick="SuperAdmin.goNav('settings')">
                            <i class="fa-solid fa-gear"></i> System Settings
                        </button>
                        <button class="btn bs" style="width:100%; justify-content:flex-start;" onclick="SuperAdmin.goNav('revenue-analytics')">
                            <i class="fa-solid fa-chart-bar"></i> View Analytics
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-circle-dot"></i> System Status</div>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap:12px;">
                    <div style="text-align:center; padding:16px; background:var(--sa-primary-lt); border-radius:10px;">
                        <i class="fa-solid fa-server" style="color:var(--sa-primary); font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                        <div style="font-weight:700; font-size:13px;">Server</div>
                        <div style="font-size:11px; color:var(--sa-primary);">Online</div>
                    </div>
                    <div style="text-align:center; padding:16px; background:var(--sa-primary-lt); border-radius:10px;">
                        <i class="fa-solid fa-database" style="color:var(--sa-primary); font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                        <div style="font-weight:700; font-size:13px;">Database</div>
                        <div style="font-size:11px; color:var(--sa-primary);">Connected</div>
                    </div>
                    <div style="text-align:center; padding:16px; background:#fef9e7; border-radius:10px;">
                        <i class="fa-solid fa-envelope" style="color:var(--amber); font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                        <div style="font-weight:700; font-size:13px;">Email Service</div>
                        <div style="font-size:11px; color:var(--amber);">Degraded</div>
                    </div>
                    <div style="text-align:center; padding:16px; background:var(--sa-primary-lt); border-radius:10px;">
                        <i class="fa-solid fa-shield-halved" style="color:var(--sa-primary); font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                        <div style="font-weight:700; font-size:13px;">Security</div>
                        <div style="font-size:11px; color:var(--sa-primary);">Protected</div>
                    </div>
                </div>
            </div>
        </div>
      `;
    }).catch(err => {
      console.error("[SuperAdmin] Error loading dashboard:", err);
      mainContent.innerHTML = `
        <div class="pg fu">
            <div class="card" style="text-align:center; padding:60px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:3rem; color:var(--red); margin-bottom:20px;"></i>
                <h2>Error Loading Dashboard</h2>
                <p style="color:var(--tb); margin-top:10px;">${err.message || 'Unable to connect to server. Please try again.'}</p>
                <button class="btn bt" style="margin-top:20px;" onclick="location.reload()">
                    <i class="fa-solid fa-rotate-right"></i> Retry
                </button>
            </div>
        </div>
      `;
    });
  }

  function renderRecentActivity(activity) {
    if (!activity || activity.length === 0) {
      return '<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--tl);">No recent activity found in audit logs.</td></tr>';
    }
    
    return activity.map(act => `
      <tr>
        <td style="font-weight:600;">${act.level || act.action || 'N/A'}</td>
        <td style="font-size:12px;">${act.user_id ? 'User #' + act.user_id : 'System'}</td>
        <td>${act.ip_address || '-'}</td>
        <td style="font-size:12px; color:var(--tl);">${act.time ? new Date(act.time).toLocaleString('en-US', {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'}) : '-'}</td>
        <td><span class="tag bg-t">Logged</span></td>
      </tr>
    `).join('');
  }

  function renderGenericPage() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    mainContent.innerHTML = `
      <div class="pg fu">
        <div class="pg-head">
          <div class="pg-left">
            <div class="pg-ico ic-t"><i class="fa-solid fa-folder"></i></div>
            <div>
              <div class="pg-title">${activeSub ? activeNav + ' - ' + activeSub : activeNav.charAt(0).toUpperCase() + activeNav.slice(1)}</div>
              <div class="pg-sub">Module under development</div>
            </div>
          </div>
        </div>
        <div class="card" style="text-align:center; padding:100px 40px;">
          <i class="fa-solid fa-cubes-stacked" style="font-size:3rem; color:var(--tl); margin-bottom:20px;"></i>
          <h2>${activeSub ? activeNav + ' ' + activeSub : activeNav.toUpperCase()} Module</h2>
          <p style="color:var(--tb); margin-top:10px;">This specific view is being developed.</p>
        </div>
      </div>
    `;
  }

  async function fetchSuperAdminStats() {
    try {
      const response = await fetch('api/super_admin_stats.php', {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch dashboard stats');
      }
      
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || 'API returned error');
      }
      
      return result.data;
    } catch (err) {
      console.error("[SuperAdmin] API Error:", err);
      // Return default values on error
      return {
        totalInstitutes: 0,
        totalUsers: 0,
        activeStudents: 0,
        pendingApprovals: 0,
        recentActivity: []
      };
    }
  }

function goNav(id, subId = null) {
    activeNav = id;
    activeSub = subId;
    
    // Update URL
    const url = new URL(window.location);
    const pageVal = subId ? `${id}-${subId}` : id;
    url.searchParams.set('page', pageVal);
    window.history.pushState({ pageVal }, '', url);
    
    if (window.innerWidth < 1024) {
      const sidebar = document.getElementById('sidebar');
      if (sidebar) sidebar.classList.remove('sb-active');
    }
    
    // Update sidebar active states
    updateSidebarActiveStates(id, subId);
    
    renderPage();
  }

  /**
   * Update sidebar active states after navigation
   * This highlights the current menu item and opens parent submenus
   */
  function updateSidebarActiveStates(navId, subId) {
    // Remove all active classes first
    document.querySelectorAll('.nb-btn.active').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sub-btn.active').forEach(el => el.classList.remove('active'));
    
    // Find and activate the current nav item
    const navButtons = document.querySelectorAll('.nb-btn');
    navButtons.forEach(btn => {
      const btnText = btn.querySelector('.nbl')?.textContent?.toLowerCase() || '';
      
      // Map nav IDs to readable menu labels
      const navToLabelMap = {
        'overview': 'overview',
        'tenants': 'tenant management',
        'plans': 'plan management',
        'revenue': 'revenue analytics',
        'analytics': 'platform analytics',
        'support': 'support tickets',
        'system': 'system config',
        'logs': 'system logs',
        'settings': 'platform settings',
        'profile': 'profile'
      };
      
      const expectedLabel = navToLabelMap[navId] || navId;
      if (btnText.includes(expectedLabel) || btnText === expectedLabel) {
        btn.classList.add('active');
        // Open parent submenu if exists
        const submenu = btn.nextElementSibling;
        if (submenu && submenu.classList.contains('sub-menu')) {
          submenu.style.display = 'block';
          const chevron = submenu.previousElementSibling.querySelector('.nbc');
          if (chevron) chevron.classList.add('open');
        }
      }
    });
    
    // If we have a subId, find and activate the specific submenu item
    if (subId) {
      const subButtons = document.querySelectorAll('.sub-btn');
      subButtons.forEach(btn => {
        const btnText = btn.textContent?.toLowerCase().trim() || '';
        
        // Map subIds to button text patterns
        const subToTextMap = {
          'all': 'all institutes',
          'add': 'add new',
          'suspended': 'suspended',
          'sub-plans': 'subscription plans',
          'flags': 'feature flags',
          'assign': 'plan assignment',
          'mrr': 'mrr / arr dashboard',
          'payments': 'payment history',
          'invoices': 'invoice generator',
          'users': 'active users',
          'heatmap': 'feature heatmap',
          'sms': 'sms credit consumption',
          'open': 'open tickets',
          'resolved': 'resolved',
          'impersonate': 'impersonation log',
          'toggles': 'feature toggles',
          'maintenance': 'maintenance mode',
          'announce': 'push announcements',
          'email-cfg': 'email config',
          'audit': 'audit logs',
          'errors': 'error logs',
          'api': 'api request logs',
          'db': 'db insights',
          'branding': 'platform branding',
          'sms-tpl': 'sms templates',
          'view': 'profile',
          'password': 'change password',
          'activity': 'activity log'
        };
        
        const expectedText = subToTextMap[subId];
        if (expectedText && btnText.includes(expectedText)) {
          btn.classList.add('active');
          // Ensure parent submenu is open
          const parentSubmenu = btn.closest('.sub-menu');
          if (parentSubmenu) {
            parentSubmenu.style.display = 'block';
            const chevron = parentSubmenu.previousElementSibling?.querySelector('.nbc');
            if (chevron) chevron.classList.add('open');
          }
        }
      });
    }
  }

  // Expose goNav globally for sidebar onclick handlers
  window.goNav = goNav;
  window.toggleMenu = toggleMenu;

// Handle browser back/forward navigation
  window.addEventListener('popstate', (e) => {
    let pageVal;
    if (e.state && e.state.pageVal) {
      pageVal = e.state.pageVal;
    } else {
      const urlParams = new URLSearchParams(window.location.search);
      pageVal = urlParams.get('page') || 'overview';
    }
    activeNav = pageVal.includes('-') ? pageVal.split('-')[0] : pageVal;
    activeSub = pageVal.includes('-') ? pageVal.split('-')[1] : null;
    
    // Update sidebar active states for back/forward navigation
    updateSidebarActiveStates(activeNav, activeSub);
    
    renderPage();
  });

  /* ============================================================
     SIDEBAR — mirrors institute-admin sidebar behaviour
     Uses body.sb-active  (mobile open)
         body.sb-collapsed (desktop icon rail)
     ============================================================ */

  function initSidebar() {
    const toggleBtns = document.querySelectorAll(".sb-toggle");
    const overlay    = document.getElementById("sbOverlay");

    toggleBtns.forEach((btn) => {
      btn.addEventListener("click", function () {
        toggleSidebar();
      });
    });

    // Close on overlay click (mobile)
    if (overlay) {
      overlay.addEventListener("click", function () {
        document.body.classList.remove("sb-active");
        overlay.classList.remove("active");
      });
    }

    // On desktop, restore collapsed state from localStorage
    if (window.innerWidth >= 1024) {
      if (localStorage.getItem("sa-sb-collapsed") === "1") {
        document.body.classList.add("sb-collapsed");
      }
    }
  }

  /**
   * Toggle sidebar open/closed
   * Mobile  → toggles body.sb-active
   * Desktop → toggles body.sb-collapsed
   */
  function toggleSidebar() {
    const overlay = document.getElementById("sbOverlay");

    if (window.innerWidth < 1024) {
      // Mobile: slide-in drawer
      document.body.classList.toggle("sb-active");
      if (overlay) overlay.classList.toggle("active");
    } else {
      // Desktop: collapse to icon rail
      document.body.classList.toggle("sb-collapsed");
      const isCollapsed = document.body.classList.contains("sb-collapsed");
      localStorage.setItem("sa-sb-collapsed", isCollapsed ? "1" : "0");
    }
  }

  /**
   * Toggle a submenu open/closed
   * @param {string} menuId - ID of the submenu element
   */
  function toggleMenu(menuId) {
    const submenu = document.getElementById(menuId);
    const chevron = document.getElementById("chev-" + menuId);

    if (!submenu) return;

    const isOpen = submenu.style.display !== "none" && submenu.style.display !== "";
    submenu.style.display = isOpen ? "none" : "block";

    if (chevron) {
      chevron.classList.toggle("open", !isOpen);
    }
  }

  /* ============================================================
     DROPDOWNS — same pattern as institute-admin
     ============================================================ */

  function initDropdowns() {
    const chip     = document.getElementById("userChip");
    const dropdown = document.getElementById("userDropdown");

    if (chip && dropdown) {
      chip.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("active");
      });
    }

    document.addEventListener("click", function () {
      if (dropdown) dropdown.classList.remove("active");
    });
  }

  /* ============================================================
     CHARTS
     ============================================================ */

  function initCharts() {
    if (typeof Chart === "undefined") return;

    document.querySelectorAll("[data-chart]").forEach((canvas) => {
      const type     = canvas.dataset.chart;
      const rawData  = canvas.dataset.chartData;
      const chartData = rawData ? JSON.parse(rawData) : {};

      charts[canvas.id] = new Chart(canvas, {
        type: type,
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: "bottom" },
          },
        },
      });
    });
  }

  /* ============================================================
     MODALS
     ============================================================ */

  function initModals() {
    document.querySelectorAll("[data-modal]").forEach((trigger) => {
      trigger.addEventListener("click", function (e) {
        e.preventDefault();
        openModal(this.dataset.modal);
      });
    });
  }

  function openModal(modalId) {
    const modal   = document.getElementById(modalId);
    const overlay = document.getElementById("sbOverlay");
    if (modal)   modal.classList.add("active");
    if (overlay) overlay.classList.add("active");
  }

  function closeModal(modalId) {
    const modal   = document.getElementById(modalId);
    const overlay = document.getElementById("sbOverlay");
    if (modal)   modal.classList.remove("active");
    if (overlay) overlay.classList.remove("active");
  }

  function closeAllModals() {
    document.querySelectorAll(".sa-modal.active").forEach((m) =>
      m.classList.remove("active")
    );
    const overlay = document.getElementById("sbOverlay");
    if (overlay) overlay.classList.remove("active");
  }

  /* ============================================================
     NOTIFICATIONS — SweetAlert2 toast
     ============================================================ */

  function showNotification(message, type = "info") {
    if (typeof Swal === "undefined") {
      console.warn("[SuperAdmin] SweetAlert2 not loaded");
      return;
    }
    Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3500,
      timerProgressBar: true,
    }).fire({ icon: type, title: message });
  }

  /* ============================================================
     API HELPER
     ============================================================ */

  async function fetchAPI(url, options = {}) {
    const defaults = {
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      credentials: "same-origin",
    };

    try {
      const res  = await fetch(url, { ...defaults, ...options });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "An error occurred");
      return data;
    } catch (err) {
      console.error("[SuperAdmin] API Error:", err);
      showNotification(err.message, "error");
      throw err;
    }
  }

  /* ============================================================
     CONFIRM DIALOG
     ============================================================ */

  async function confirmAction(
    title             = "Are you sure?",
    text              = "This action cannot be undone.",
    confirmButtonText = "Yes, proceed"
  ) {
    return await Swal.fire({
      title,
      text,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#009E7E",
      cancelButtonColor: "#E2E8F0",
      confirmButtonText,
      cancelButtonText: "Cancel",
    });
  }

  /* ============================================================
     STAT CARD UPDATER
     ============================================================ */

  function updateStatCard(cardId, value, change = null) {
    const card = document.getElementById(cardId);
    if (!card) return;

    const valEl    = card.querySelector(".sc-val");
    const deltaEl  = card.querySelector(".sc-delta");

    if (valEl)   valEl.textContent = value.toLocaleString();

    if (deltaEl && change !== null) {
      deltaEl.textContent = (change > 0 ? "+" : "") + change + "% vs last month";
      deltaEl.className   = "sc-delta " + (change >= 0 ? "positive" : "negative");
    }
  }

  /* ============================================================
     PUBLIC API
     ============================================================ */

  return {
    init,
    goNav,
    toggleSidebar,
    toggleMenu,
    showNotification,
    confirmAction,
    fetchAPI,
    openModal,
    closeModal,
    closeAllModals,
    updateStatCard,
    charts,
    dataTables,
  };
})();

// Auto-init on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  SuperAdmin.init();
});

// Make goNav available globally for onclick handlers
window.goNav = function(id, subId) {
  SuperAdmin.goNav(id, subId);
};

// CommonJS export guard
if (typeof module !== "undefined" && module.exports) {
  module.exports = SuperAdmin;
}
