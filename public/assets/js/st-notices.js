/**
 * Hamro ERP — Student Portal · st-notices.js
 * Notices & Announcements Page
 */

window.renderSTNotices = async function (filterType = 'all') {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `<div class="container-fluid p-4 text-center text-muted" style="min-height:200px;padding-top:80px !important;">
        <i class="fa-solid fa-circle-notch fa-spin fs-3 mb-3 d-block"></i>Loading notices...
    </div>`;

    try {
        const res = await fetch(`${window.APP_URL}/api/student/notices?action=list&per_page=50`);
        const result = await res.json();
        const notices = result.success ? (result.data || []) : [];
        const unreadCount = result.unread_count || 0;
        const pagination = result.pagination || {};

        // Store globally for filtering
        window._stAllNotices = notices;
        window._stNoticeFilter = filterType;

        _renderNoticesPage(notices, unreadCount, pagination, filterType);

    } catch (e) {
        console.error('Notices error:', e);
        mc.innerHTML = `<div class="container-fluid p-4">
            <div class="alert alert-danger rounded-3">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>Failed to load notices. Please try again.
            </div>
        </div>`;
    }
};

function _renderNoticesPage(notices, unreadCount, pagination, activeFilter) {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    const filters = [
        { key: 'all',          label: 'All',           icon: 'fa-list' },
        { key: 'unread',       label: 'Unread',        icon: 'fa-envelope' },
        { key: 'announcement', label: 'Announcements', icon: 'fa-bullhorn' },
        { key: 'exam',         label: 'Exams',         icon: 'fa-file-alt' },
        { key: 'fee',          label: 'Finance',       icon: 'fa-money-bill-wave' },
        { key: 'event',        label: 'Events',        icon: 'fa-calendar' },
        { key: 'holiday',      label: 'Holidays',      icon: 'fa-umbrella-beach' },
    ];

    mc.innerHTML = `
    <div class="container-fluid p-4" style="max-width:1000px;">

        <!-- Header Banner -->
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div style="background:linear-gradient(135deg,#009E7E,#007a62);padding:28px 32px;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:52px;height:52px;background:rgba(255,255,255,0.18);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;flex-shrink:0;">
                            <i class="fa-solid fa-bullhorn"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold text-white">Notices & Announcements</h4>
                            <p class="mb-0 text-white opacity-75 small">
                                ${pagination.total || notices.length} notice${(pagination.total || notices.length) !== 1 ? 's' : ''}
                                ${unreadCount > 0 ? `&nbsp;·&nbsp;<span class="badge" style="background:rgba(255,255,255,0.25);font-size:11px;">${unreadCount} unread</span>` : ''}
                            </p>
                        </div>
                    </div>
                    ${unreadCount > 0 ? `
                    <button onclick="markAllNoticesRead()" class="btn btn-sm d-flex align-items-center gap-2 rounded-3 px-3 py-2"
                        style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);font-size:13px;">
                        <i class="fa-solid fa-check-double"></i> Mark All Read
                    </button>` : ''}
                </div>
            </div>
        </div>

        <!-- Filter Pills -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            ${filters.map(f => {
                const count = f.key === 'all' ? notices.length
                    : f.key === 'unread' ? notices.filter(n => !parseInt(n.is_read)).length
                    : notices.filter(n => n.notice_type === f.key || n.category === f.key).length;

                const isActive = activeFilter === f.key;
                return `
                <button onclick="filterNotices('${f.key}')"
                    class="btn btn-sm rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 notice-filter-btn"
                    id="nf-${f.key}"
                    style="font-size:12px;font-weight:600;transition:all .15s;
                        ${isActive
                            ? 'background:#009E7E;color:#fff;border-color:#009E7E;'
                            : 'background:#fff;color:#6b7280;border-color:#e5e7eb;'
                        }">
                    <i class="fa-solid ${f.icon}" style="font-size:11px;"></i>
                    ${f.label}
                    ${count > 0 ? `<span class="badge rounded-pill ms-1" style="font-size:10px;padding:2px 7px;${isActive ? 'background:rgba(255,255,255,0.3);' : 'background:#f3f4f6;color:#374151;'}">${count}</span>` : ''}
                </button>`;
            }).join('')}
        </div>

        <!-- Notices List -->
        <div id="noticesListContainer">
            ${_renderFilteredNotices(notices, activeFilter)}
        </div>

    </div>`;
}

function _renderFilteredNotices(notices, filter) {
    let filtered = notices;

    if (filter === 'unread') {
        filtered = notices.filter(n => !parseInt(n.is_read));
    } else if (filter !== 'all') {
        filtered = notices.filter(n => n.notice_type === filter || n.category === filter);
    }

    if (filtered.length === 0) {
        return `
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="fa-regular fa-bell-slash fs-1 mb-3 d-block opacity-25"></i>
                <p class="fw-medium mb-1">No notices found</p>
                <p class="small">There are no notices in this category yet.</p>
            </div>
        </div>`;
    }

    // Group by date
    const groups = {};
    filtered.forEach(n => {
        const dateKey = n.created_at ? new Date(n.created_at).toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) : 'Unknown Date';
        if (!groups[dateKey]) groups[dateKey] = [];
        groups[dateKey].push(n);
    });

    return Object.entries(groups).map(([date, items]) => `
        <div class="mb-2">
            <div class="text-muted small fw-semibold px-1 mb-2" style="letter-spacing:.4px;text-transform:uppercase;font-size:11px;">${date}</div>
            <div class="card border-0 shadow-sm overflow-hidden mb-3">
                ${items.map((n, idx) => _renderNoticeRow(n, idx, items.length)).join('')}
            </div>
        </div>
    `).join('');
}

function _renderNoticeRow(n, idx, total) {
    const isRead    = parseInt(n.is_read);
    const isImportant = parseInt(n.is_important);

    const typeConfig = {
        announcement: { color: '#3b82f6', bg: '#eff6ff', icon: 'fa-bullhorn',          label: 'Announcement' },
        exam:         { color: '#8b5cf6', bg: '#f5f3ff', icon: 'fa-file-alt',          label: 'Exam'         },
        fee:          { color: '#10b981', bg: '#ecfdf5', icon: 'fa-money-bill-wave',    label: 'Finance'      },
        event:        { color: '#f59e0b', bg: '#fffbeb', icon: 'fa-calendar-alt',       label: 'Event'        },
        holiday:      { color: '#06b6d4', bg: '#ecfeff', icon: 'fa-umbrella-beach',     label: 'Holiday'      },
        finance:      { color: '#10b981', bg: '#ecfdf5', icon: 'fa-coins',             label: 'Finance'      },
        system:       { color: '#6b7280', bg: '#f9fafb', icon: 'fa-gear',              label: 'System'       },
    };

    const priorityBadge = {
        critical: { label: 'Critical', cls: 'bg-danger'  },
        high:     { label: 'High',     cls: 'bg-warning text-dark'  },
        normal:   { label: '',         cls: '' },
        low:      { label: '',         cls: '' },
    };

    const cfg = typeConfig[n.notice_type] || typeConfig.announcement;
    const pri = priorityBadge[n.priority] || { label: '', cls: '' };

    const borderBottom = idx < total - 1 ? 'border-bottom' : '';
    const unreadDot    = !isRead ? `<span style="width:8px;height:8px;background:#009E7E;border-radius:50%;flex-shrink:0;margin-top:6px;"></span>` : `<span style="width:8px;height:8px;flex-shrink:0;"></span>`;

    const excerpt = n.content ? n.content.replace(/<[^>]+>/g, '').substring(0, 120) + (n.content.length > 120 ? '...' : '') : '';
    const timeAgo = _timeAgo(n.created_at);

    return `
    <div class="d-flex align-items-start gap-3 px-4 py-3 ${borderBottom} notice-row"
         style="cursor:pointer;transition:background .15s;${!isRead ? 'background:#fafffe;' : ''}"
         onclick="viewNoticeDetail(${n.id})"
         onmouseover="this.style.background='#f0fdf9'"
         onmouseout="this.style.background='${!isRead ? '#fafffe' : 'transparent'}'">

        <!-- Type Icon -->
        <div style="width:40px;height:40px;border-radius:10px;background:${cfg.bg};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <i class="fa-solid ${cfg.icon}" style="color:${cfg.color};font-size:1rem;"></i>
        </div>

        <!-- Content -->
        <div class="flex-grow-1 min-width-0">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                ${isImportant ? `<i class="fa-solid fa-star" style="color:#f59e0b;font-size:12px;" title="Important"></i>` : ''}
                <span class="fw-semibold text-dark" style="font-size:14px;">${escapeHtmlNotice(n.title || 'Notice')}</span>
                ${pri.label ? `<span class="badge rounded-pill ${pri.cls}" style="font-size:10px;">${pri.label}</span>` : ''}
                <span class="badge rounded-pill" style="font-size:10px;background:${cfg.bg};color:${cfg.color};">${cfg.label}</span>
            </div>
            ${excerpt ? `<p class="text-muted mb-0" style="font-size:12px;line-height:1.5;">${escapeHtmlNotice(excerpt)}</p>` : ''}
        </div>

        <!-- Right: time + unread dot -->
        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
            <span class="text-muted" style="font-size:11px;white-space:nowrap;">${timeAgo}</span>
            ${unreadDot}
        </div>

    </div>`;
}

// ── Notice Detail View ──────────────────────────────────────────────────────
window.viewNoticeDetail = async function (noticeId) {
    const mc = document.getElementById('mainContent');
    mc.innerHTML = `<div class="container-fluid p-4 text-center text-muted" style="padding-top:80px !important;">
        <i class="fa-solid fa-circle-notch fa-spin fs-3 d-block mb-3"></i>Loading...
    </div>`;

    try {
        const res    = await fetch(`${window.APP_URL}/api/student/notices?action=detail&notice_id=${noticeId}`);
        const result = await res.json();

        if (!result.success || !result.data) {
            mc.innerHTML = `<div class="container-fluid p-4"><div class="alert alert-warning">Notice not found.</div></div>`;
            return;
        }

        const n = result.data;
        const typeConfig = {
            announcement: { color: '#3b82f6', icon: 'fa-bullhorn',       label: 'Announcement' },
            exam:         { color: '#8b5cf6', icon: 'fa-file-alt',       label: 'Exam'         },
            fee:          { color: '#10b981', icon: 'fa-money-bill-wave', label: 'Finance'      },
            event:        { color: '#f59e0b', icon: 'fa-calendar-alt',   label: 'Event'        },
            holiday:      { color: '#06b6d4', icon: 'fa-umbrella-beach', label: 'Holiday'      },
        };
        const cfg = typeConfig[n.notice_type] || typeConfig.announcement;
        const postedDate = n.created_at ? new Date(n.created_at).toLocaleDateString('en-US', { weekday:'long', day:'numeric', month:'long', year:'numeric' }) : '—';
        const isImportant = parseInt(n.is_important);

        mc.innerHTML = `
        <div class="container-fluid p-4" style="max-width:800px;">

            <!-- Back -->
            <button onclick="window.renderSTNotices()" class="btn btn-sm btn-outline-secondary rounded-3 mb-4 px-3">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Notices
            </button>

            <!-- Notice Card -->
            <div class="card border-0 shadow-sm overflow-hidden">

                <!-- Color top bar -->
                <div style="height:5px;background:${cfg.color};"></div>

                <div class="card-body p-4 p-md-5">

                    <!-- Type + Priority badges -->
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                        <span class="badge rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1"
                            style="background:${cfg.color}18;color:${cfg.color};font-size:12px;">
                            <i class="fa-solid ${cfg.icon}"></i> ${cfg.label}
                        </span>
                        ${isImportant ? `<span class="badge rounded-pill px-3 py-2" style="background:#fef3c7;color:#92400e;font-size:12px;"><i class="fa-solid fa-star me-1"></i>Important</span>` : ''}
                        ${n.priority === 'high' || n.priority === 'critical'
                            ? `<span class="badge rounded-pill bg-danger px-3 py-2" style="font-size:12px;">${n.priority.charAt(0).toUpperCase() + n.priority.slice(1)}</span>`
                            : ''}
                    </div>

                    <!-- Title -->
                    <h3 class="fw-bold text-dark mb-2" style="line-height:1.3;">${escapeHtmlNotice(n.title || 'Notice')}</h3>

                    <!-- Meta -->
                    <div class="d-flex align-items-center gap-3 text-muted mb-4 flex-wrap" style="font-size:12px;">
                        <span><i class="fa-regular fa-calendar me-1"></i>${postedDate}</span>
                        ${n.posted_by_name ? `<span><i class="fa-regular fa-user me-1"></i>${escapeHtmlNotice(n.posted_by_name)}</span>` : ''}
                        ${n.display_until ? `<span><i class="fa-regular fa-clock me-1"></i>Valid until ${new Date(n.display_until).toLocaleDateString()}</span>` : ''}
                    </div>

                    <!-- Divider -->
                    <hr class="mb-4" style="opacity:.1;">

                    <!-- Content -->
                    <div style="font-size:15px;line-height:1.8;color:#374151;" class="notice-content">
                        ${n.content || '<p class="text-muted">No content provided.</p>'}
                    </div>

                    <!-- Attachment -->
                    ${n.attachment_path ? `
                    <div class="mt-4 pt-3 border-top">
                        <a href="${window.APP_URL}/${n.attachment_path}" target="_blank"
                            class="btn btn-outline-primary rounded-3 d-inline-flex align-items-center gap-2 px-4 py-2">
                            <i class="fa-solid fa-paperclip"></i>
                            View Attachment
                        </a>
                    </div>` : ''}

                </div>
            </div>
        </div>`;

        // Update our cached list to mark this notice as read
        if (window._stAllNotices) {
            const idx = window._stAllNotices.findIndex(x => x.id == noticeId);
            if (idx !== -1) window._stAllNotices[idx].is_read = 1;
        }

    } catch (e) {
        console.error('Notice detail error:', e);
        mc.innerHTML = `<div class="container-fluid p-4"><div class="alert alert-danger">Failed to load notice.</div></div>`;
    }
};

// ── Filter notices in place ─────────────────────────────────────────────────
window.filterNotices = function (filterKey) {
    window._stNoticeFilter = filterKey;

    // Update pill styles
    document.querySelectorAll('.notice-filter-btn').forEach(btn => {
        const id = btn.id.replace('nf-', '');
        if (id === filterKey) {
            btn.style.cssText += 'background:#009E7E !important;color:#fff !important;border-color:#009E7E !important;';
        } else {
            btn.style.cssText += 'background:#fff !important;color:#6b7280 !important;border-color:#e5e7eb !important;';
        }
    });

    const container = document.getElementById('noticesListContainer');
    if (container && window._stAllNotices) {
        container.innerHTML = _renderFilteredNotices(window._stAllNotices, filterKey);
    }
};

// ── Mark all as read ────────────────────────────────────────────────────────
window.markAllNoticesRead = async function () {
    try {
        const res    = await fetch(`${window.APP_URL}/api/student/notices?action=mark_all_read`, { method: 'POST' });
        const result = await res.json();
        if (result.success) {
            // Reload the page
            window.renderSTNotices(window._stNoticeFilter || 'all');
        }
    } catch (e) {
        console.error('Mark all read error:', e);
    }
};

// ── Helpers ─────────────────────────────────────────────────────────────────
function _timeAgo(dateStr) {
    if (!dateStr) return '';
    const now  = new Date();
    const past = new Date(dateStr);
    const diffMs = now - past;
    const mins   = Math.floor(diffMs / 60000);
    const hours  = Math.floor(mins / 60);
    const days   = Math.floor(hours / 24);

    if (mins < 1)    return 'Just now';
    if (mins < 60)   return `${mins}m ago`;
    if (hours < 24)  return `${hours}h ago`;
    if (days < 7)    return `${days}d ago`;
    return past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function escapeHtmlNotice(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

window.renderSTNotices = window.renderSTNotices;
