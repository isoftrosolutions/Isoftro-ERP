/**
 * iSoftro ERP — Super Admin Core Module
 * Handles initialization, navigation, and common utilities.
 */

window.SuperAdmin = (function (existing) {
  "use strict";

  let charts     = existing.charts || {};
  let dataTables = existing.dataTables || {};
  let expanded   = JSON.parse(localStorage.getItem('sa_expanded') || '{}');

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

  const compoundPages = ['view-tenant', 'edit-tenant', 'add-tenant'];
  let initialPage = getInitialPage();
  let activeNav = initialPage;
  let activeSub = null;
  
  if (initialPage.includes('-') && !compoundPages.includes(initialPage)) {
    activeNav = initialPage.split('-')[0];
    activeSub = initialPage.split('-')[1];
  }

  function init() {
    // Restore sidebar collapse state
    if (localStorage.getItem("sa-sb-collapsed") === "1") {
      document.body.classList.add("sb-collapsed");
    }

    initSidebar();
    renderSidebar();
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
        if (typeof SuperAdmin.renderTenants === 'function') {
          SuperAdmin.renderTenants();
        } else {
          fetchSPAPage('tenants');
        }
        break;
      case 'users':
        fetchSPAPage('users');
        break;
      case 'plans':
        fetchSPAPage('plans');
        break;
      case 'view-tenant':
      case 'edit-tenant':
      case 'add-tenant':
        fetchSPAPage(activeNav);
        break;
      case 'revenue':
        fetchSPAPage('revenue');
        break;
      case 'analytics':
        fetchSPAPage('analytics');
        break;
      case 'support':
        fetchSPAPage('support');
        break;
      case 'system':
      case 'settings':
        fetchSPAPage(activeNav);
        break;
      case 'logs':
        fetchSPAPage('logs');
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
    
    if (window.innerWidth < 1024) {
      document.body.classList.remove('sb-active');
      const overlay = document.getElementById('sbOverlay');
      if (overlay) overlay.classList.remove('active');
    }
    
    updateSidebarActiveStates(id, params);
    renderPage();
  }

  function updateSidebarActiveStates(navId, subId) {
    // Clear all active states
    document.querySelectorAll('.sidebar-item.active').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.sidebar-subitem.active').forEach(el => el.classList.remove('active'));

    // Highlight parent nav item by onclick attribute
    const parentBtn = document.querySelector(`.sidebar-item[onclick*="'${navId}'"]`);
    if (parentBtn) {
      parentBtn.classList.add('active');
      // Auto-expand submenu if it exists
      const submenu = document.getElementById(`submenu-${navId}`);
      const chevron = parentBtn.querySelector('.sidebar-item-chevron');
      if (submenu) submenu.classList.add('open');
      if (chevron) chevron.classList.add('open');
    }

    // Sub-item active state
    if (subId) {
      document.querySelectorAll('.sidebar-subitem').forEach(btn => {
        const onclick = btn.getAttribute('onclick') || '';
        if (onclick.includes(`'${navId}'`) && onclick.includes(`'${subId}'`)) {
          btn.classList.add('active');
        }
      });
    }
  }

  function toggleExp(id) {
    expanded[id] = !expanded[id];
    localStorage.setItem('sa_expanded', JSON.stringify(expanded));

    const btn = document.querySelector(`#submenu-${id}`)?.previousElementSibling;
    const menu = document.getElementById(`submenu-${id}`);
    const chev = btn?.querySelector('.sidebar-item-chevron');

    if (menu) {
      menu.classList.toggle('open', expanded[id]);
      if (chev) chev.classList.toggle('open', expanded[id]);
      if (btn) btn.setAttribute('aria-expanded', expanded[id] ? 'true' : 'false');
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

        html += `<div class="sidebar-section" data-sec="${sec}">
            <div class="sidebar-section-label">${sec}</div>`;

        items.forEach(nav => {
            const hasSub = !!(nav.sub && nav.sub.length);
            const isActive = activeNav === nav.id;
            const isExp = filter ? true : !!expanded[nav.id];

            const badgeVal = nav.badge_key && badges[nav.badge_key] ? badges[nav.badge_key] : null;
            const badgeHtml = badgeVal ? `<span class="sidebar-item-badge">${badgeVal}</span>` : '';

            if (hasSub) {
                html += `<div class="sidebar-item-group">
                    <button class="sidebar-item${isActive ? ' active' : ''}"
                            onclick="toggleExp('${nav.id}')"
                            aria-expanded="${isExp ? 'true' : 'false'}">
                        <i class="fa-solid ${nav.icon} sidebar-item-icon"></i>
                        <span class="sidebar-item-label">${nav.label}</span>
                        ${badgeHtml}
                        <i class="fa-solid fa-chevron-right sidebar-item-chevron${isExp ? ' open' : ''}"></i>
                    </button>
                    <div class="sidebar-submenu${isExp ? ' open' : ''}" id="submenu-${nav.id}">
                        <div>`;

                nav.sub.forEach(s => {
                    const isSubActive = activeNav === nav.id && activeSub === s.id;
                    const subBadge = s.badge_key && badges[s.badge_key] ? `<span class="sidebar-item-badge sm">${badges[s.badge_key]}</span>` : '';
                    html += `<button class="sidebar-subitem${isSubActive ? ' active' : ''}"
                                onclick="goNav('${nav.id}', '${s.id}')">
                        <i class="fa-solid ${s.icon} sidebar-subitem-icon"></i>
                        <span class="sidebar-subitem-label">${s.l}</span>
                        ${subBadge}
                    </button>`;
                });

                html += `</div></div></div>`;
            } else {
                html += `<button class="sidebar-item${isActive ? ' active' : ''}"
                            onclick="goNav('${nav.id}')">
                    <i class="fa-solid ${nav.icon} sidebar-item-icon"></i>
                    <span class="sidebar-item-label">${nav.label}</span>
                    ${badgeHtml}
                </button>`;
            }
        });
        html += `</div>`;
    });

    sbBody.innerHTML = html;
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
