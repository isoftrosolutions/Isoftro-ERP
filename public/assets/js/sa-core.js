/**
 * Hamro ERP — Super Admin Core Module
 * Handles initialization, navigation, and common utilities.
 */

window.SuperAdmin = window.SuperAdmin || (function () {
  "use strict";

  let charts     = {};
  let dataTables = {};

  const getInitialPage = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get('page');
    if (pageParam) return pageParam;
    
    const pathParts = window.location.pathname.split('/');
    let lastPart = pathParts[pathParts.length - 1];
    if (!lastPart && pathParts.length > 1) {
      lastPart = pathParts[pathParts.length - 2];
    }
    
    if (!lastPart || lastPart === 'index' || lastPart === 'super-admin' || lastPart === 'index.php' || lastPart === 'erp') {
      return 'overview';
    }
    return lastPart.replace('.php', '');
  };

  let initialPage = getInitialPage();
  let activeNav = initialPage.includes('-') ? initialPage.split('-')[0] : initialPage;
  let activeSub = initialPage.includes('-') ? initialPage.split('-')[1] : null;

  function init() {
    initSidebar();
    initDropdowns();
    initCharts();
    initModals();
    renderPage();
  }

  function renderPage() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    // Check if we should skip overwrite for specialized PHP pages (like add-tenant.php)
    const currentParams = new URLSearchParams(window.location.search);
    if (!currentParams.has('page') && activeNav !== 'overview' && activeNav !== 'index' && !window.location.pathname.includes('/dash/super-admin/index')) {
        const isIndex = window.location.pathname.endsWith('/super-admin/') || window.location.pathname.endsWith('/index.php');
        if (!isIndex) {
            console.log("[SuperAdmin] Server-rendered page detected, skipping JS overwrite:", activeNav);
            initCharts();
            return;
        }
    }

    mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

    // Route to appropriate page renderer
    // These functions are defined in separate sa-*.js files
    switch(activeNav) {
      case 'overview':
        if (typeof SuperAdmin.renderDashboard === 'function') SuperAdmin.renderDashboard();
        break;
      case 'tenants':
        if (typeof SuperAdmin.renderTenants === 'function') SuperAdmin.renderTenants();
        break;
      case 'plans':
        if (typeof SuperAdmin.renderPlans === 'function') SuperAdmin.renderPlans();
        break;
      case 'revenue':
        if (typeof SuperAdmin.renderRevenue === 'function') SuperAdmin.renderRevenue();
        break;
      case 'analytics':
        if (typeof SuperAdmin.renderAnalytics === 'function') SuperAdmin.renderAnalytics();
        break;
      case 'support':
        if (typeof SuperAdmin.renderSupport === 'function') SuperAdmin.renderSupport();
        break;
      case 'system':
        if (typeof SuperAdmin.renderSystem === 'function') SuperAdmin.renderSystem();
        break;
      case 'logs':
        if (typeof SuperAdmin.renderLogs === 'function') SuperAdmin.renderLogs();
        break;
      case 'settings':
        if (typeof SuperAdmin.renderSettings === 'function') SuperAdmin.renderSettings();
        break;
      case 'profile':
        if (typeof SuperAdmin.renderProfile === 'function') SuperAdmin.renderProfile();
        break;
      default:
        console.warn("[SuperAdmin] No renderer for:", activeNav);
        fetchGenericPage(activeNav);
    }
  }

  function goNav(id, subId = null) {
    activeNav = id;
    activeSub = subId;
    
    const baseUrl = window.APP_URL ? window.APP_URL + '/dash/super-admin/' : '/erp/dash/super-admin/';
    const url = new URL(baseUrl, window.location.origin);
    const pageVal = subId ? `${id}-${subId}` : id;
    url.searchParams.set('page', pageVal);
    
    window.history.pushState({ pageVal }, '', url.toString());
    
    if (window.innerWidth < 1024) {
      document.body.classList.remove('sb-active');
      const overlay = document.getElementById('sbOverlay');
      if (overlay) overlay.classList.remove('active');
    }
    
    updateSidebarActiveStates(id, subId);
    renderPage();
  }

  function updateSidebarActiveStates(navId, subId) {
    document.querySelectorAll('.nb-btn.active').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sub-btn.active').forEach(el => el.classList.remove('active'));
    
    const navButtons = document.querySelectorAll('.nb-btn');
    navButtons.forEach(btn => {
      const btnText = btn.querySelector('.nbl')?.textContent?.toLowerCase() || '';
      const navToLabelMap = {
        'overview': 'overview', 'tenants': 'tenant management', 'plans': 'plan management',
        'revenue': 'revenue analytics', 'analytics': 'platform analytics', 'support': 'support tickets',
        'system': 'system config', 'logs': 'system logs', 'settings': 'platform settings', 'profile': 'profile'
      };
      
      const expectedLabel = navToLabelMap[navId] || navId;
      if (btnText.includes(expectedLabel) || btnText === expectedLabel) {
        btn.classList.add('active');
        const submenu = btn.nextElementSibling;
        if (submenu && submenu.classList.contains('sub-menu')) {
          submenu.style.display = 'block';
          const chevron = btn.querySelector('.nbc');
          if (chevron) chevron.classList.add('open');
        }
      }
    });
    
    if (subId) {
      const subButtons = document.querySelectorAll('.sub-btn');
      subButtons.forEach(btn => {
        const btnText = btn.textContent?.toLowerCase().trim() || '';
        const subToTextMap = {
          'all': 'all institutes', 'add': 'add new', 'suspended': 'suspended',
          'sub-plans': 'subscription plans', 'flags': 'feature flags', 'assign': 'plan assignment',
          'mrr': 'mrr / arr dashboard', 'payments': 'payment history', 'invoices': 'invoice generator',
          'users': 'active users', 'heatmap': 'feature heatmap', 'sms': 'sms credit consumption',
          'open': 'open tickets', 'resolved': 'resolved', 'impersonate': 'impersonation log'
        };
        const expectedText = subToTextMap[subId];
        if (expectedText && btnText.includes(expectedText)) {
          btn.classList.add('active');
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

  async function fetchAPI(url, options = {}) {
    // Get CSRF token from multiple sources for compatibility
    const csrfToken = window.CSRF_TOKEN || 
                      window.csrfToken || 
                      document.querySelector('meta[name="csrf-token"]')?.content;
    
    const defaults = {
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        // Use X-CSRF-Token (hyphen) to match PHP's HTTP_X_CSRF_TOKEN
        ...(csrfToken ? { "X-CSRF-Token": csrfToken } : {})
      },
      credentials: "same-origin",
    };

    // Deep merge headers
    if (options.headers) {
        options.headers = { ...defaults.headers, ...options.headers };
    }

    let fullUrl = url;
    if (!url.includes('://')) {
        if (url.startsWith('/')) {
            fullUrl = (window.APP_URL || '') + url;
        } else {
            fullUrl = (window.APP_URL || '') + '/api/superadmin/' + url;
        }
    }
    
    try {
      const res  = await fetch(fullUrl, { ...defaults, ...options });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || "An error occurred");
      return data;
    } catch (err) {
      console.error("[SuperAdmin] API Error:", err);
      showNotification(err.message, "error");
      throw err;
    }
  }

  function processPartialHtml(html, container) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const incomingMain = doc.querySelector('main#mainContent') || doc.querySelector('main');
    
    container.innerHTML = incomingMain ? incomingMain.innerHTML : html;
    
    // Search and execute scripts in the newly loaded HTML
    const scripts = container.querySelectorAll("script");
    scripts.forEach(oldScript => {
        const newScript = document.createElement("script");
        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
        if (oldScript.src) {
            newScript.src = oldScript.src;
        } else {
            newScript.textContent = oldScript.textContent;
        }
        // Ensure scripts are truly global by appending to body or head instead of inner container if needed,
        // but replacing in-place is usually fine for side-effects.
        oldScript.parentNode.replaceChild(newScript, oldScript);
    });
    initCharts();
  }

  function fetchAndRender(pagePath) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;
    
    let fullUrl = pagePath;
    if (!pagePath.includes('://')) {
        fullUrl = pagePath.startsWith('/') ? (window.APP_URL || '') + pagePath : (window.APP_URL || '') + '/' + pagePath;
    }
    
    fullUrl += (fullUrl.includes('?') ? '&' : '?') + 'partial=true';
    
    fetch(fullUrl)
      .then(response => response.text())
      .then(html => processPartialHtml(html, mainContent))
      .catch(err => {
        console.error('Error loading page:', err);
        mainContent.innerHTML = '<div class="pg fu"><p>Error loading page</p></div>';
      });
  }

  function fetchGenericPage(page) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;

    let pageUrl = (window.APP_URL || '') + '/pages/super_admin/' + page.replace('.php', '');
    pageUrl += (pageUrl.includes('?') ? '&' : '?') + 'partial=true';
    
    fetch(pageUrl)
      .then(res => res.text())
      .then(html => processPartialHtml(html, mainContent))
      .catch(err => {
        console.error("[SuperAdmin] Error loading page:", err);
        mainContent.innerHTML = `<div class="pg fu"><div class="card">Error loading page ${page}</div></div>`;
      });
  }

  function initSidebar() {
    const toggleBtns = document.querySelectorAll(".sb-toggle");
    const overlay    = document.getElementById("sbOverlay");

    toggleBtns.forEach((btn) => {
      btn.addEventListener("click", () => toggleSidebar());
    });

    if (overlay) {
      overlay.addEventListener("click", () => {
        document.body.classList.remove("sb-active");
        overlay.classList.remove("active");
      });
    }

    if (window.innerWidth >= 1024 && localStorage.getItem("sa-sb-collapsed") === "1") {
      document.body.classList.add("sb-collapsed");
    }
  }

  function toggleSidebar() {
    const overlay = document.getElementById("sbOverlay");
    if (window.innerWidth < 1024) {
      document.body.classList.toggle("sb-active");
      if (overlay) overlay.classList.toggle("active");
    } else {
      document.body.classList.toggle("sb-collapsed");
      localStorage.setItem("sa-sb-collapsed", document.body.classList.contains("sb-collapsed") ? "1" : "0");
    }
  }

  function toggleMenu(menuId) {
    const submenu = document.getElementById(menuId);
    const chevron = document.getElementById("chev-" + menuId);
    if (!submenu) return;
    const isOpen = submenu.style.display !== "none" && submenu.style.display !== "";
    submenu.style.display = isOpen ? "none" : "block";
    if (chevron) chevron.classList.toggle("open", !isOpen);
  }

  function initDropdowns() {
    const chip = document.getElementById("userChip");
    const dropdown = document.getElementById("userDropdown");
    const notifChip = document.getElementById("notifChip");
    const notifDropdown = document.getElementById("notifDropdown");

    if (chip && dropdown) {
      chip.addEventListener("click", (e) => {
        e.stopPropagation();
        if (notifDropdown) notifDropdown.classList.remove("active");
        dropdown.classList.toggle("active");
      });
    }

    if (notifChip && notifDropdown) {
      notifChip.addEventListener("click", (e) => {
        e.stopPropagation();
        if (dropdown) dropdown.classList.remove("active");
        notifDropdown.classList.toggle("active");
      });
    }

    document.addEventListener("click", () => {
      if (dropdown) dropdown.classList.remove("active");
      if (notifDropdown) notifDropdown.classList.remove("active");
    });
  }

  function initCharts() {
    if (typeof Chart === "undefined") return;
    document.querySelectorAll("[data-chart]").forEach((canvas) => {
      const type = canvas.dataset.chart;
      const rawData = canvas.dataset.chartData;
      const chartData = rawData ? JSON.parse(rawData) : {};
      charts[canvas.id] = new Chart(canvas, {
        type: type,
        data: chartData,
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } } }
      });
    });
  }

  function initModals() {
    document.querySelectorAll("[data-modal]").forEach((trigger) => {
      trigger.addEventListener("click", function (e) {
        e.preventDefault();
        openModal(this.dataset.modal);
      });
    });
  }

  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById("sbOverlay");
    if (modal) modal.classList.add("active");
    if (overlay) overlay.classList.add("active");
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const overlay = document.getElementById("sbOverlay");
    if (modal) modal.classList.remove("active");
    if (overlay) overlay.classList.remove("active");
  }

  function showNotification(message, type = "info") {
    if (typeof Swal === "undefined") {
      console.warn("[SuperAdmin] SweetAlert2 not loaded");
      return;
    }
    Swal.mixin({
      toast: true, position: "top-end", showConfirmButton: false, timer: 3500, timerProgressBar: true,
    }).fire({ icon: type, title: message });
  }

  function confirmAction(title = "Are you sure?", text = "This action cannot be undone.", confirmButtonText = "Yes, proceed") {
    return Swal.fire({
      title, text, icon: "warning", showCancelButton: true, confirmButtonColor: "#009E7E", cancelButtonColor: "#E2E8F0", confirmButtonText, cancelButtonText: "Cancel",
    });
  }

  // Handle browser back/forward navigation
  window.addEventListener('popstate', (e) => {
    let pageVal = e.state?.pageVal || (new URLSearchParams(window.location.search)).get('page') || 'overview';
    activeNav = pageVal.includes('-') ? pageVal.split('-')[0] : pageVal;
    activeSub = pageVal.includes('-') ? pageVal.split('-')[1] : null;
    updateSidebarActiveStates(activeNav, activeSub);
    renderPage();
  });

  return {
    init, goNav, toggleSidebar, toggleMenu, showNotification, confirmAction, fetchAPI, fetchAndRender, openModal, closeModal,
    charts, dataTables,
    get activeNav() { return activeNav; },
    get activeSub() { return activeSub; }
  };
})();

document.addEventListener("DOMContentLoaded", () => SuperAdmin.init());
window.goNav = (id, subId) => SuperAdmin.goNav(id, subId);
window.toggleMenu = (id) => SuperAdmin.toggleMenu(id);
