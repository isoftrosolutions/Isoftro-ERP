/**
 * iSoftro ERP — Institute Admin · ia-sidebar.js v4.0
 * Complete SaaS-grade sidebar system
 * Mobile-first, accessible, production-ready
 *
 * Depends on: window._IA (from ia-core.js), window._IA_NAV_CONFIG, window._IA_BADGES (from PHP)
 * Exposes: _iaRenderSidebar(), toggleExp(), SidebarManager
 */
(function () {
    'use strict';
    if (window.__ia_sidebar_v4) return;
    window.__ia_sidebar_v4 = true;

    /* ── Constants ─────────────────────────────────────────────── */
    const COLLAPSED_KEY = '_ia_sb_collapsed';
    const EXPANDED_KEY  = '_ia_expanded';
    const DESKTOP_BP    = 1024;
    const PINNED_IDS    = ['overview', 'students', 'fee', 'accounting'];

    /* ── Helpers ───────────────────────────────────────────────── */
    const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const isDesktop = () => window.innerWidth >= DESKTOP_BP;
    const body = () => document.body;

    /* ── Flat nav builder ─────────────────────────────────────── */
    function buildFlatNav() {
        const cfg = window._IA_NAV_CONFIG || [];
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

    /* ── State persistence ────────────────────────────────────── */
    function getExpanded() {
        try { return JSON.parse(localStorage.getItem(EXPANDED_KEY) || '{}'); } catch { return {}; }
    }
    function saveExpanded() {
        localStorage.setItem(EXPANDED_KEY, JSON.stringify(window._IA.expanded));
    }

    /* ── Immediate collapsed restore (FOUC prevention) ─────── */
    (function restoreCollapsed() {
        if (window.innerWidth >= DESKTOP_BP && localStorage.getItem(COLLAPSED_KEY) === '1') {
            document.documentElement.classList.add('sb-collapsed');
            if (document.body) document.body.classList.add('sb-collapsed');
        }
    })();

    /* ── Tooltip ───────────────────────────────────────────────── */
    let tooltipEl = null;
    function showTooltip(btn, text) {
        if (!body().classList.contains('sb-collapsed')) return;
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.className = 'sidebar-tooltip';
            document.body.appendChild(tooltipEl);
        }
        tooltipEl.textContent = text;
        const r = btn.getBoundingClientRect();
        tooltipEl.style.top = `${r.top + r.height / 2 - 14}px`;
        tooltipEl.style.left = `${r.right + 10}px`;
        tooltipEl.classList.add('visible');
    }
    function hideTooltip() {
        if (tooltipEl) tooltipEl.classList.remove('visible');
    }

    /* ── Sidebar Renderer ─────────────────────────────────────── */
    window._iaRenderSidebar = function (filter) {
        const sbBody = document.getElementById('sbBody');
        if (!sbBody) return;

        const _IA = window._IA;
        const nav = buildFlatNav();
        const badges = window._IA_BADGES || {};
        const f = (filter || '').toLowerCase();

        const sections = [...new Set(nav.map(n => n.sec))];
        let html = '';

        sections.forEach(sec => {
            const items = nav.filter(n => n.sec === sec);
            let sectionHtml = '';
            let hasVisible = false;

            items.forEach(item => {
                const hasSub = !!(item.sub && item.sub.length);
                const isActive = _IA.activeNav === item.id;
                const isExp = _IA.expanded[item.id];
                const id = esc(item.id);
                const badgeVal = item.badge_key && badges[item.badge_key] ? badges[item.badge_key] : null;
                const badgeHtml = badgeVal ? `<span class="sidebar-item-badge">${badgeVal}</span>` : '';
                const labelMatch = !f || item.label.toLowerCase().includes(f);

                if (hasSub) {
                    let subHtml = '';
                    let subMatch = false;
                    item.sub.forEach(s => {
                        const sId = esc(s.id);
                        const isSubActive = _IA.activeNav === item.id && _IA.activeSub === s.id;
                        const sLabel = esc(s.l);
                        const sBadge = s.badge_key && badges[s.badge_key] ? `<span class="sidebar-item-badge sm">${badges[s.badge_key]}</span>` : '';
                        const action = s.onclick || `goNav('${id}','${sId}')`;
                        const match = !f || s.l.toLowerCase().includes(f);
                        if (f && !match) return;
                        subMatch = true;
                        subHtml += `<button class="sidebar-subitem${isSubActive ? ' active' : ''}" onclick="${action}" tabindex="0">
                            <i class="fa-solid ${esc(s.icon)} sidebar-subitem-icon"></i>
                            <span class="sidebar-subitem-label">${sLabel}</span>
                            ${sBadge}
                        </button>`;
                    });

                    if (f && !labelMatch && !subMatch) return;
                    hasVisible = true;
                    const forceOpen = f ? subMatch : isExp;

                    sectionHtml += `<div class="sidebar-item-group">
                        <button class="sidebar-item${isActive ? ' active' : ''}"
                                onclick="toggleExp('${id}')"
                                onmouseenter="_iaSbTip(this,'${esc(item.label)}')"
                                onmouseleave="_iaSbTipHide()"
                                aria-expanded="${forceOpen ? 'true' : 'false'}"
                                aria-controls="submenu-${id}"
                                tabindex="0">
                            <i class="fa-solid ${esc(item.icon)} sidebar-item-icon"></i>
                            <span class="sidebar-item-label">${esc(item.label)}</span>
                            ${badgeHtml}
                            <i class="fa-solid fa-chevron-right sidebar-item-chevron${forceOpen ? ' open' : ''}"></i>
                        </button>
                        <div class="sidebar-submenu${forceOpen ? ' open' : ''}" id="submenu-${id}">
                            ${subHtml}
                        </div>
                    </div>`;
                } else {
                    if (f && !labelMatch) return;
                    hasVisible = true;
                    sectionHtml += `<button class="sidebar-item${isActive ? ' active' : ''}"
                            onclick="goNav('${id}')"
                            onmouseenter="_iaSbTip(this,'${esc(item.label)}')"
                            onmouseleave="_iaSbTipHide()"
                            tabindex="0">
                        <i class="fa-solid ${esc(item.icon)} sidebar-item-icon"></i>
                        <span class="sidebar-item-label">${esc(item.label)}</span>
                        ${badgeHtml}
                    </button>`;
                }
            });

            if (hasVisible) {
                html += `<div class="sidebar-section" data-sec="${esc(sec)}">
                    <div class="sidebar-section-label">${esc(sec)}</div>
                    ${sectionHtml}
                </div>`;
            }
        });

        html += `<div class="sidebar-install">
            <button class="sidebar-install-btn" onclick="typeof openPwaModal==='function'&&openPwaModal()">
                <i class="fa-solid fa-bolt"></i>
                <span>Install App</span>
            </button>
        </div>`;

        sbBody.innerHTML = html;
        renderBottomNav();
    };

    /* Expose tooltip helpers for inline handlers */
    window._iaSbTip = showTooltip;
    window._iaSbTipHide = hideTooltip;

    /* ── Toggle submenu expand/collapse ───────────────────────── */
    window.toggleExp = function (id) {
        const _IA = window._IA;
        _IA.expanded[id] = !_IA.expanded[id];
        saveExpanded();

        const submenu = document.getElementById(`submenu-${id}`);
        const btn = submenu?.previousElementSibling;
        const chevron = btn?.querySelector('.sidebar-item-chevron');

        if (submenu) {
            submenu.classList.toggle('open', _IA.expanded[id]);
        }
        if (chevron) {
            chevron.classList.toggle('open', _IA.expanded[id]);
        }
        if (btn) {
            btn.setAttribute('aria-expanded', _IA.expanded[id] ? 'true' : 'false');
        }
    };

    /* ── Active state update (called by goNav in ia-core.js) ── */
    window._iaUpdateSidebarActive = function () {
        const _IA = window._IA;
        document.querySelectorAll('.sidebar-item, .sidebar-subitem').forEach(el => el.classList.remove('active'));

        // Highlight parent
        const parentBtn = document.querySelector(`.sidebar-item[onclick*="'${_IA.activeNav}'"]`);
        if (parentBtn) parentBtn.classList.add('active');

        // Highlight sub
        if (_IA.activeSub) {
            const subBtn = document.querySelector(`.sidebar-subitem[onclick*="'${_IA.activeNav}','${_IA.activeSub}'"]`) ||
                           document.querySelector(`.sidebar-subitem[onclick*="'${_IA.activeNav}', '${_IA.activeSub}'"]`);
            if (subBtn) subBtn.classList.add('active');
        }

        // Update bottom nav
        document.querySelectorAll('.mb-nav-btn').forEach(el => {
            el.classList.toggle('active', el.dataset.navId === _IA.activeNav);
        });
    };

    /* ── Bottom Navigation (Mobile) ───────────────────────────── */
    function renderBottomNav() {
        let bNav = document.getElementById('bottomNav');
        if (!bNav) {
            bNav = document.createElement('nav');
            bNav.id = 'bottomNav';
            bNav.className = 'mobile-bottom-nav';
            bNav.setAttribute('aria-label', 'Quick navigation');
            document.body.appendChild(bNav);
        }

        const nav = buildFlatNav();
        const items = nav
            .filter(n => PINNED_IDS.includes(n.id))
            .sort((a, b) => PINNED_IDS.indexOf(a.id) - PINNED_IDS.indexOf(b.id))
            .slice(0, 4);

        const _IA = window._IA;
        let html = items.map(i => {
            const id = esc(i.id);
            const hasSub = i.sub && i.sub.length;
            const action = hasSub ? `goNav('${id}','${esc(i.sub[0].id)}')` : `goNav('${id}')`;
            const isActive = _IA.activeNav === i.id;
            return `<button class="mb-nav-btn${isActive ? ' active' : ''}" data-nav-id="${id}" onclick="${action}" aria-label="${esc(i.label)}">
                <i class="fa-solid ${esc(i.icon)}"></i>
                <span>${esc(i.label)}</span>
            </button>`;
        }).join('');

        html += `<button class="mb-nav-btn" onclick="document.body.classList.add('sb-active')" aria-label="Open menu">
            <i class="fa-solid fa-bars"></i>
            <span>More</span>
        </button>`;

        bNav.innerHTML = html;
    }

    /* ── Desktop collapse/expand ──────────────────────────────── */
    function toggleCollapse(forceClose) {
        if (isDesktop()) {
            const willCollapse = forceClose === true ? true : !body().classList.contains('sb-collapsed');
            body().classList.toggle('sb-collapsed', willCollapse);
            localStorage.setItem(COLLAPSED_KEY, willCollapse ? '1' : '0');
        } else {
            const willOpen = forceClose === true ? false : !body().classList.contains('sb-active');
            body().classList.toggle('sb-active', willOpen);
        }
    }

    /* ── Event Delegation ─────────────────────────────────────── */
    function initListeners() {
        // Unified click delegation
        document.addEventListener('click', e => {
            const toggle = e.target.closest('.js-sidebar-toggle');
            const overlay = e.target.closest('.sidebar-overlay') || e.target.id === 'sbOverlay' || e.target.classList.contains('sb-overlay');
            const close = e.target.closest('#sbClose') || e.target.id === 'sbClose';

            if (toggle) { e.preventDefault(); toggleCollapse(); }
            else if (overlay || close) { body().classList.remove('sb-active'); }
        });

        // Resize cleanup
        window.addEventListener('resize', () => {
            if (isDesktop() && body().classList.contains('sb-active')) {
                body().classList.remove('sb-active');
            }
        });

        // Swipe-to-close on mobile
        let sx = 0, sy = 0;
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.addEventListener('touchstart', e => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
            sidebar.addEventListener('touchend', e => {
                if (isDesktop()) return;
                const dx = e.changedTouches[0].clientX - sx;
                const dy = Math.abs(e.changedTouches[0].clientY - sy);
                if (dx < -60 && dy < 60) body().classList.remove('sb-active');
            }, { passive: true });
        }

        // Keyboard: Escape closes mobile sidebar
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && body().classList.contains('sb-active')) {
                body().classList.remove('sb-active');
            }
        });

        // Sidebar search
        const sbSearch = document.getElementById('sbSearch');
        if (sbSearch) {
            let debounce;
            sbSearch.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => {
                    _iaRenderSidebar(sbSearch.value.trim());
                }, 150);
            });
        }
    }

    /* ── Init ──────────────────────────────────────────────────── */
    function init() {
        // Re-apply collapsed to body
        if (document.documentElement.classList.contains('sb-collapsed')) {
            document.body.classList.add('sb-collapsed');
        }
        initListeners();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ── Public API ────────────────────────────────────────────── */
    window.SidebarManager = {
        toggle: toggleCollapse,
        closeMobile: () => body().classList.remove('sb-active'),
        isCollapsed: () => body().classList.contains('sb-collapsed'),
        render: () => _iaRenderSidebar(),
    };

})();
