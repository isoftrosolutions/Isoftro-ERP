/**
 * iSoftro ERP — Super Admin Core Module
 * Handles initialization, navigation, and common utilities.
 */

window.SuperAdmin = (function (existing) {
  "use strict";

  let charts     = existing.charts || {};
  let dataTables = existing.dataTables || {};
  let expanded   = JSON.parse(localStorage.getItem('sa_expanded') || '{}');
  const SIDEBAR_DESKTOP_MIN = 768;
  const SIDEBAR_COLLAPSED_KEY = "sa-sb-collapsed";
  const SIDEBAR_COLLAPSED_KEY_LEGACY = "_sa_sb_collapsed";
  let lastSidebarFocusEl = null;

  function isDesktopSidebar() {
    return window.matchMedia(`(min-width: ${SIDEBAR_DESKTOP_MIN}px)`).matches;
  }

  function getSavedSidebarCollapsed() {
    return (
      localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === "1" ||
      localStorage.getItem(SIDEBAR_COLLAPSED_KEY_LEGACY) === "1"
    );
  }

  function setSavedSidebarCollapsed(isCollapsed) {
    const val = isCollapsed ? "1" : "0";
    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, val);
    localStorage.setItem(SIDEBAR_COLLAPSED_KEY_LEGACY, val);
  }

  /* Build flat nav from PHP-injected config */
  function buildFlatNav() {
    const cfg = window._SA_NAV_CONFIG || [];
    const flat = [];
    cfg.forEach(section => {
        (section.items || []).forEach(item => {
            flat.push({
                id: item.id,
                icon: item.icon,
                label: item.label,
                sub: item.sub || null,
                sec: section.section,
                badge_key: item.badge_key || null,
            });
        });
    });
    return flat;
  }

  const SA_NAV = buildFlatNav();

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

  const compoundPages = [
    'view-tenant', 'edit-tenant', 'add-tenant',
    'tenants-suspended',
    'plans-flags', 'plans-assign',
    'revenue-invoices',
    'support-resolved', 'support-impersonate',
    'system-maintenance', 'system-push',
    'logs-errors', 'logs-api',
    'settings-brand', 'settings-sms-tpl'
  ];
  let initialPage = getInitialPage();
  let activeNav = initialPage;
  let activeSub = null;
  
  if (initialPage.includes('-') && !compoundPages.includes(initialPage)) {
    activeNav = initialPage.split('-')[0];
    activeSub = initialPage.split('-')[1];
  }

  function init() {
    // Restore desktop collapsed state early to reduce layout flash
    if (isDesktopSidebar() && getSavedSidebarCollapsed()) {
      document.body.classList.add("sb-collapsed");
    }

    initSidebar();
    renderSidebar();

    // Demo mode: only showcase the sidebar behavior (skip SPA navigation and heavy widgets)
    if (document.body && document.body.dataset && document.body.dataset.saDemo === "1") {
      return;
    }

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
    // All pages load via the SPA Laravel endpoint
    switch(activeNav) {
      case 'overview':
        if (typeof SuperAdmin.renderDashboard === 'function') {
          SuperAdmin.renderDashboard();
        } else {
          fetchSPAPage('overview');
        }
        break;
      case 'tenants':
        if (activeSub === 'add') fetchSPAPage('add-tenant');
        else if (activeSub === 'suspended') fetchSPAPage('tenants-suspended');
        else {
          if (typeof SuperAdmin.renderTenants === 'function') SuperAdmin.renderTenants();
          else fetchSPAPage('tenants');
        }
        break;
      case 'users':
        fetchSPAPage('users');
        break;
      case 'plans':
        if (activeSub === 'flags') fetchSPAPage('plans-flags');
        else if (activeSub === 'assign') fetchSPAPage('plans-assign');
        else fetchSPAPage('plans'); // 'sub-plans' or no sub
        break;
      case 'view-tenant':
      case 'edit-tenant':
      case 'add-tenant':
      case 'tenants-suspended':
      case 'plans-flags':
      case 'plans-assign':
      case 'revenue-invoices':
      case 'support-resolved':
      case 'support-impersonate':
      case 'system-maintenance':
      case 'system-push':
      case 'logs-errors':
      case 'logs-api':
      case 'settings-brand':
      case 'settings-sms-tpl':
        fetchSPAPage(activeNav);
        break;
      case 'revenue':
        if (activeSub === 'invoices') fetchSPAPage('revenue-invoices');
        else fetchSPAPage('revenue'); // 'mrr' and 'payments' already shown in revenue.php
        break;
      case 'analytics':
        fetchSPAPage('analytics'); // all sub-items shown in analytics.php
        break;
      case 'support':
        if (activeSub === 'resolved') fetchSPAPage('support-resolved');
        else if (activeSub === 'impersonate') fetchSPAPage('support-impersonate');
        else fetchSPAPage('support'); // 'open' or no sub
        break;
      case 'system':
        if (activeSub === 'maintenance') fetchSPAPage('system-maintenance');
        else if (activeSub === 'push') fetchSPAPage('system-push');
        else fetchSPAPage('system'); // 'toggles' or no sub
        break;
      case 'settings':
        if (activeSub === 'brand') fetchSPAPage('settings-brand');
        else if (activeSub === 'sms-tpl') fetchSPAPage('settings-sms-tpl');
        else fetchSPAPage('settings'); // 'email' shown in main settings.php
        break;
      case 'logs':
        if (activeSub === 'errors') fetchSPAPage('logs-errors');
        else if (activeSub === 'api') fetchSPAPage('logs-api');
        else fetchSPAPage('logs'); // 'audit' or no sub
        break;
      case 'profile':
        fetchSPAPage('profile');
        break;
      default:
        console.warn("[SuperAdmin] No renderer for:", activeNav);
        fetchSPAPage(activeNav);
    }
  }

  function goNav(id, params = {}) {
    activeNav = id;
    // Track sub-selection so renderPage can route to the correct sub-page
    activeSub = (typeof params === 'string' && params) ? params : null;

    if (document.body && document.body.dataset && document.body.dataset.saDemo === "1") {
      updateSidebarActiveStates(id, params);
      return;
    }
    
    const baseUrl = (window.APP_URL || '') + '/dash/super-admin/';
    const url = new URL(baseUrl, window.location.origin);
    
    // Support legacy subId-style page val or just simple id
    url.searchParams.set('page', id);
    
    // Add additional parameters
    if (typeof params === 'object') {
        Object.keys(params).forEach(key => url.searchParams.set(key, params[key]));
    } else if (params) {
        // old subId behavior
        url.searchParams.set('page', `${id}-${params}`);
    }
    
    window.history.pushState({ pageVal: url.searchParams.get('page') }, '', url.toString());
    
    if (!isDesktopSidebar()) {
      if (typeof initSidebar._closeMobile === "function") {
        initSidebar._closeMobile({ restoreFocus: false });
      } else {
        document.body.classList.remove("sb-mobile-open");
      }
    }
    
    updateSidebarActiveStates(id, params);
    renderPage();
  }

  function updateSidebarActiveStates(navId, subId) {
    document.querySelectorAll('.sb-btn.active').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sub-btn.active').forEach(el => el.classList.remove('active'));
    
    const navButtons = document.querySelectorAll('.sb-btn');
    navButtons.forEach(btn => {
      const btnText = btn.querySelector('.sb-lbl')?.textContent?.toLowerCase() || '';
      
      const navToLabelMap = {
        'overview': 'overview', 'tenants': 'tenant', 'institutes': 'tenant',
        'plans': 'plan', 'revenue': 'revenue', 'analytics': 'analytics', 'support': 'support',
        'system': 'system', 'logs': 'audit', 'settings': 'setting', 'profile': 'profile'
      };
      
      const expectedLabel = navToLabelMap[navId] || navId;
      if (btnText.includes(expectedLabel) || btnText === expectedLabel) {
        btn.classList.add('active');
        
        // Auto-expand if has sub
        const submenu = btn.nextElementSibling;
        if (submenu && submenu.classList.contains('sub-menu')) {
          submenu.classList.add('open');
          const chevron = btn.querySelector('.nbc');
          if (chevron) chevron.classList.add('open');
        }
      }
    });
    
    // Sub-item active state
    if (subId) {
      document.querySelectorAll('.sub-btn').forEach(btn => {
        const onclick = btn.getAttribute('onclick') || '';
        if (onclick.includes(`'${navId}'`) && onclick.includes(`'${subId}'`)) {
          btn.classList.add('active');
        }
      });
    }
  }

  function toggleExp(id) {
    if (isDesktopSidebar() && document.body.classList.contains("sb-collapsed")) {
      document.body.classList.remove("sb-collapsed");
      setSavedSidebarCollapsed(false);
    }

    expanded[id] = !expanded[id];
    localStorage.setItem('sa_expanded', JSON.stringify(expanded));
    
    const btn = document.querySelector(`.sb-btn[onclick*="toggleExp('${id}')"]`);
    const menu = document.getElementById(`sub-${id}`);
    const chev = btn?.querySelector('.nbc');
    
    if (menu) {
      if (expanded[id]) {
        menu.classList.add('open');
        if (chev) chev.classList.add('open');
      } else {
        menu.classList.remove('open');
        if (chev) chev.classList.remove('open');
      }
    }
  }

  function renderSidebar(filter = '') {
    const sbBody = document.getElementById('sbBody');
    if (!sbBody) return;
    
    const badges = window._SA_BADGES || {};
    const sections = [...new Set(SA_NAV.map(n => n.sec))];
    let html = '';

    sections.forEach(sec => {
        const items = SA_NAV.filter(n => {
            if (n.sec !== sec) return false;
            if (!filter) return true;
            return n.label.toLowerCase().includes(filter) || (n.sub && n.sub.some(s => s.l.toLowerCase().includes(filter)));
        });
        if (!items.length) return;

        html += `<div class="sb-sec-lbl">${sec}</div>`;

        items.forEach(nav => {
            const hasSub = !!(nav.sub && nav.sub.length);
            const isActive = activeNav === nav.id;
            const isExp = filter ? true : !!expanded[nav.id];
            
            const badgeVal = nav.badge_key && badges[nav.badge_key] ? badges[nav.badge_key] : null;
            const badgeHtml = badgeVal ? `<span class="sb-badge" style="margin-left:auto;">${badgeVal}</span>` : '';

            if (hasSub) {
                html += `
                    <button type="button" class="sb-btn ${isActive ? 'active' : ''}" onclick="toggleExp('${nav.id}')" title="${nav.label}" aria-expanded="${isExp ? 'true' : 'false'}" aria-controls="sub-${nav.id}">
                        <i class="sb-ic" data-lucide="${nav.icon || 'circle'}" aria-hidden="true"></i>
                        <span class="sb-lbl">${nav.label}</span>
                        ${badgeHtml}
                        <i class="sb-ic nbc ${isExp ? 'open' : ''}" data-lucide="chevron-right" aria-hidden="true"></i>
                    </button>
                    <div class="sub-menu ${isExp ? 'open' : ''}" id="sub-${nav.id}" role="region" aria-label="${nav.label} submenu">
                `;

                nav.sub.forEach(s => {
                    const subBadge = s.badge_key && badges[s.badge_key] ? `<span class="sb-badge sm" style="margin-left:auto; opacity:0.7;">${badges[s.badge_key]}</span>` : '';
                    html += `
                        <button type="button" class="sub-btn" onclick="goNav('${nav.id}', '${s.id}')" title="${s.l}">
                            <i class="sb-ic" data-lucide="${s.icon || 'circle'}" aria-hidden="true" style="width:16px;height:16px;opacity:0.75;"></i>
                            ${s.l}
                            ${subBadge}
                        </button>
                    `;
                });

                html += `</div>`;
            } else {
                html += `
                    <button type="button" class="sb-btn ${isActive ? 'active' : ''}" onclick="goNav('${nav.id}')" title="${nav.label}">
                        <i class="sb-ic" data-lucide="${nav.icon || 'circle'}" aria-hidden="true"></i>
                        <span class="sb-lbl">${nav.label}</span>
                        ${badgeHtml}
                    </button>
                `;
            }
        });
    });

    sbBody.innerHTML = html;

    if (window.lucide && typeof window.lucide.createIcons === "function") {
      window.lucide.createIcons();
    }
  }

  async function fetchAPI(url, methodOrOptions = {}, body = null) {
    // Support both:
    //   fetchAPI(url, 'POST')
    //   fetchAPI(url, { method: 'POST', body: ... })
    let options = {};
    if (typeof methodOrOptions === 'string') {
        options = { method: methodOrOptions };
        if (body !== null) {
            options.body = typeof body === 'object' ? JSON.stringify(body) : body;
        }
    } else if (typeof methodOrOptions === 'object') {
        options = { ...methodOrOptions };
    }
    // Default to POST when body is provided but method isn't set
    if (options.body && !options.method) {
        options.method = 'POST';
    }

    // JWT state check
    const isJwtSecure = window.JWT_STATE || false;
    
    const defaults = {
      method: 'GET',
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      // Ensure cookies are sent for JWT stateless auth
      credentials: "include",
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

  // Fetch a Super Admin SPA page via the Laravel router
  function fetchSPAPage(page) {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;

    const currentParams = new URLSearchParams(window.location.search);
    currentParams.delete('partial');
    currentParams.delete('page');

    let qs = currentParams.toString();
    let urlPath = 'pages/super_admin/' + page;
    if (qs) urlPath += '?' + qs;

    fetchAndRender(urlPath);
  }

  // Legacy fallback (kept for compatibility)
  function fetchGenericPage(page) {
    fetchSPAPage(page);
  }

  function initSidebar() {
    const body = document.body;
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sbOverlay");

    const btnHeaderToggle = document.getElementById("sbToggle");
    const btnFabToggle = document.getElementById("sbFab");
    const btnClose = document.getElementById("sbClose");
    const btnCollapse = document.getElementById("sbCollapse");

    const setTogglesExpanded = (expanded) => {
      const val = expanded ? "true" : "false";
      if (btnHeaderToggle) btnHeaderToggle.setAttribute("aria-expanded", val);
      if (btnFabToggle) btnFabToggle.setAttribute("aria-expanded", val);
    };

    const setSidebarHidden = (isHidden) => {
      if (!sidebar) return;
      sidebar.setAttribute("aria-hidden", isHidden ? "true" : "false");
    };

    const onEsc = (e) => {
      if (e.key === "Escape") {
        closeMobile({ restoreFocus: true });
      }
    };

    const openMobile = () => {
      if (!sidebar) return;
      if (body.classList.contains("sb-mobile-open")) return;

      lastSidebarFocusEl = document.activeElement instanceof HTMLElement ? document.activeElement : null;
      body.classList.add("sb-mobile-open");
      setSidebarHidden(false);
      setTogglesExpanded(true);

      // Move focus inside for keyboard users
      requestAnimationFrame(() => {
        if (btnClose && typeof btnClose.focus === "function") btnClose.focus();
      });

      document.addEventListener("keydown", onEsc);
    };

    const closeMobile = ({ restoreFocus } = { restoreFocus: true }) => {
      if (!sidebar) return;
      if (!body.classList.contains("sb-mobile-open")) {
        setSidebarHidden(!isDesktopSidebar());
        setTogglesExpanded(isDesktopSidebar() ? !body.classList.contains("sb-collapsed") : false);
        return;
      }

      body.classList.remove("sb-mobile-open");
      setSidebarHidden(true);
      setTogglesExpanded(false);
      document.removeEventListener("keydown", onEsc);

      if (restoreFocus && lastSidebarFocusEl && typeof lastSidebarFocusEl.focus === "function") {
        lastSidebarFocusEl.focus();
      }
      lastSidebarFocusEl = null;
    };

    const toggleDesktopCollapsed = () => {
      const next = !body.classList.contains("sb-collapsed");
      body.classList.toggle("sb-collapsed", next);
      setSavedSidebarCollapsed(next);
      setSidebarHidden(false);
      setTogglesExpanded(!next);
    };

    const syncForViewport = () => {
      if (isDesktopSidebar()) {
        body.classList.toggle("sb-collapsed", getSavedSidebarCollapsed());
        body.classList.remove("sb-mobile-open");
        setSidebarHidden(false);
        setTogglesExpanded(!body.classList.contains("sb-collapsed"));
        document.removeEventListener("keydown", onEsc);
        lastSidebarFocusEl = null;
      } else {
        // On mobile: collapsed state means hidden (no icon-only mode)
        body.classList.remove("sb-collapsed");
        closeMobile({ restoreFocus: false });
        setSidebarHidden(true);
        setTogglesExpanded(false);
      }
    };

    // External toggles (header + FAB)
    [btnHeaderToggle, btnFabToggle].forEach((btn) => {
      if (!btn) return;
      btn.addEventListener("click", () => toggleSidebar());
    });

    // Inside sidebar controls
    if (btnClose) btnClose.addEventListener("click", () => closeMobile({ restoreFocus: true }));
    if (btnCollapse) {
      btnCollapse.addEventListener("click", () => {
        if (isDesktopSidebar()) toggleDesktopCollapsed();
        else closeMobile({ restoreFocus: true });
      });
    }

    if (overlay) overlay.addEventListener("click", () => closeMobile({ restoreFocus: true }));

    // Keep behavior consistent across breakpoint changes
    window.addEventListener("resize", syncForViewport);
    syncForViewport();

    // Expose for other internal calls (e.g., toggleSidebar)
    initSidebar._openMobile = openMobile;
    initSidebar._closeMobile = closeMobile;
    initSidebar._toggleDesktopCollapsed = toggleDesktopCollapsed;
  }

  function toggleSidebar() {
    const body = document.body;
    if (isDesktopSidebar()) {
      if (typeof initSidebar._toggleDesktopCollapsed === "function") {
        initSidebar._toggleDesktopCollapsed();
      } else {
        const next = !body.classList.contains("sb-collapsed");
        body.classList.toggle("sb-collapsed", next);
        setSavedSidebarCollapsed(next);
      }
      return;
    }

    const isOpen = body.classList.contains("sb-mobile-open");
    if (isOpen) {
      if (typeof initSidebar._closeMobile === "function") initSidebar._closeMobile({ restoreFocus: true });
      else body.classList.remove("sb-mobile-open");
    } else {
      if (typeof initSidebar._openMobile === "function") initSidebar._openMobile();
      else body.classList.add("sb-mobile-open");
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

    const closeAll = () => {
      if (dropdown) {
        dropdown.classList.remove("active");
      }
      if (notifDropdown) {
        notifDropdown.classList.remove("active");
      }
      if (chip) {
        chip.setAttribute("aria-expanded", "false");
      }
      if (notifChip) {
        notifChip.setAttribute("aria-expanded", "false");
      }
    };

    const toggleDropdown = (toggleEl, menuEl) => {
      if (!toggleEl || !menuEl) return;
      const willOpen = !menuEl.classList.contains("active");
      closeAll();
      menuEl.classList.toggle("active", willOpen);
      toggleEl.setAttribute("aria-expanded", willOpen ? "true" : "false");
    };

    if (chip && dropdown) {
      chip.setAttribute("aria-expanded", "false");
      chip.addEventListener("click", (e) => {
        e.stopPropagation();
        toggleDropdown(chip, dropdown);
      });
      chip.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          toggleDropdown(chip, dropdown);
        }
      });
    }

    if (dropdown) {
      dropdown.addEventListener("click", (e) => e.stopPropagation());
    }

    if (notifChip && notifDropdown) {
      notifChip.setAttribute("aria-expanded", "false");
      notifChip.addEventListener("click", (e) => {
        e.stopPropagation();
        toggleDropdown(notifChip, notifDropdown);
      });
      notifChip.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          toggleDropdown(notifChip, notifDropdown);
        }
      });
    }

    document.addEventListener("click", closeAll);
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeAll();
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
    if (document.body && document.body.dataset && document.body.dataset.saDemo === "1") {
      return;
    }

    let pageVal = e.state?.pageVal || (new URLSearchParams(window.location.search)).get('page') || 'overview';
    activeNav = pageVal;
    activeSub = null;
    
    if (pageVal.includes('-') && !compoundPages.includes(pageVal)) {
      activeNav = pageVal.split('-')[0];
      activeSub = pageVal.split('-')[1];
    }
    
    updateSidebarActiveStates(activeNav, activeSub);
    renderPage();
  });

  return Object.assign(existing, {
    init, goNav, toggleExp, toggleSidebar, toggleMenu, showNotification, confirmAction, fetchAPI, fetchAndRender, openModal, closeModal,
    charts, dataTables,
    get activeNav() { return activeNav; },
    get activeSub() { return activeSub; }
  });
})(window.SuperAdmin || {});

document.addEventListener("DOMContentLoaded", () => SuperAdmin.init());
window.goNav = (id, subId) => SuperAdmin.goNav(id, subId);
window.toggleExp = (id) => SuperAdmin.toggleExp(id);
window.toggleMenu = (id) => SuperAdmin.toggleMenu(id);
