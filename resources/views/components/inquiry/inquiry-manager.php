<?php
/**
 * Shared Inquiry Management Component
 * Nexus Design System — Advanced Dashboard Version
 * 
 * Includes: KPI Stats, Advanced Filters, Search, and Action-Rich Table
 */

$apiEndpoint = $apiEndpoint ?? APP_URL . '/api/admin/inquiries';
$componentId = $componentId ?? 'shared_inq';
$canAddInquiry = $canAddInquiry ?? true;
?>

<div class="pg-nexus">
    <!-- ── BREADCRUMB ── -->
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Inquiry Management</span>
    </div>

    <!-- ── PAGE HEADER ── -->
    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(0, 184, 148, 0.08); color: var(--green);">
                <i class="fa-solid fa-address-book"></i>
            </div>
            <div>
                <h1 class="pg-title">Inquiry Management</h1>
                <p class="pg-sub">Capture, track and convert admission leads effectively</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="refreshInquiries()">
                <i class="fa-solid fa-rotate"></i>
            </button>
            <?php if ($canAddInquiry): ?>
            <button class="btn" style="background: var(--green); color: #fff;" onclick="goNav('inq', 'add-inq')">
                <i class="fa-solid fa-plus"></i> New Inquiry
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── KPI STATS (Premium Style) ── -->
    <div class="stat-group mb">
        <div class="stat-item">
            <span class="lbl">Total Inquiries</span>
            <span class="val" id="inq_stat_total">-</span>
            <span class="sub">Life-time inquiries</span>
        </div>
        <div class="stat-item">
            <span class="lbl" style="color: #F59E0B;">Pending Follow-ups</span>
            <span class="val" id="inq_stat_pending">-</span>
            <span class="sub">Requiring attention</span>
        </div>
        <div class="stat-item">
            <span class="lbl" style="color: var(--green);">Conversions</span>
            <span class="val" id="inq_stat_converted">-</span>
            <span class="sub">Converted to students</span>
        </div>
        <div class="stat-item">
            <span class="lbl" style="color: #3B82F6;">Conv. Rate</span>
            <span class="val" id="inq_stat_rate">-%</span>
            <span class="sub">Performance metric</span>
        </div>
    </div>

    <!-- ── FILTERS & SEARCH ── -->
    <div class="card mb" style="padding: 16px; border-radius: 14px;">
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 280px; position: relative;">
                <i class="fa-solid fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 14px;"></i>
                <input type="text" id="inq_search" class="fi" placeholder="Search by name, phone, or interested course..." style="padding-left: 38px; height: 44px; background: #f8fafc; border-color: transparent;" oninput="filterInq()">
            </div>
            
            <div style="display: flex; gap: 8px;">
                <select id="inq_status_filter" class="fi" style="width: 150px; height: 44px; background: #f8fafc; border-color: transparent;" onchange="filterInq()">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="contacted">Contacted</option>
                    <option value="interested">Interested</option>
                    <option value="converted">Converted</option>
                    <option value="not_interested">Closed</option>
                </select>
                
                <select id="inq_source_filter" class="fi" style="width: 150px; height: 44px; background: #f8fafc; border-color: transparent;" onchange="filterInq()">
                    <option value="">All Sources</option>
                    <option value="walkin">Walk-in</option>
                    <option value="phone">Phone Call</option>
                    <option value="website">Website</option>
                    <option value="facebook">Facebook</option>
                    <option value="referral">Referral</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ── DATA TABLE ── -->
    <div class="card" style="border-radius: 14px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table" id="inq_table">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding-left: 24px;">Inquirer Details</th>
                        <th>Interested Course</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Date</th>
                        <th style="text-align: right; padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="inq_table_body">
                    <tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="inq_pagination" style="padding: 16px 24px; border-top: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <!-- Pagination Rendered by JS -->
        </div>
    </div>
</div>

<!-- Modal Structure for Follow-ups or Details -->
<div id="inq_modal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="inq_modal_title">Inquiry Details</h3>
            <button onclick="closeInqModal()">&times;</button>
        </div>
        <div class="modal-body" id="inq_modal_body">
            <!-- Dynamic Content -->
        </div>
    </div>
</div>

<script>
(function() {
    const API_URL = "<?= $apiEndpoint ?>";
    let allInquiries = [];
    let filteredInquiries = [];
    
    window.refreshInquiries = async () => {
        const tbody = document.getElementById('inq_table_body');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Refreshing...</td></tr>';
        await loadInquiries();
    };

    async function loadInquiries() {
        try {
            const res = await fetch(API_URL, typeof getHeaders === 'function' ? getHeaders() : {});
            const r = await res.json();
            if (r.success) {
                allInquiries = r.data || [];
                updateInqStats();
                filterInq();
            } else {
                throw new Error(r.message);
            }
        } catch (e) {
            document.getElementById('inq_table_body').innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--red);">${e.message}</td></tr>`;
        }
    }

    function updateInqStats() {
        const total = allInquiries.length;
        const pending = allInquiries.filter(i => i.status === 'pending').length;
        const converted = allInquiries.filter(i => i.status === 'converted').length;
        const rate = total > 0 ? Math.round((converted / total) * 100) : 0;
        
        document.getElementById('inq_stat_total').textContent = total;
        document.getElementById('inq_stat_pending').textContent = pending;
        document.getElementById('inq_stat_converted').textContent = converted;
        document.getElementById('inq_stat_rate').textContent = rate + '%';
    }

    window.filterInq = () => {
        const search = document.getElementById('inq_search').value.toLowerCase();
        const status = document.getElementById('inq_status_filter').value;
        const source = document.getElementById('inq_source_filter').value;
        
        filteredInquiries = allInquiries.filter(i => {
            const matchesSearch = !search || 
                (i.full_name || '').toLowerCase().includes(search) || 
                (i.phone || '').toLowerCase().includes(search) || 
                (i.course_name || '').toLowerCase().includes(search);
            const matchesStatus = !status || i.status === status;
            const matchesSource = !source || i.source === source;
            return matchesSearch && matchesStatus && matchesSource;
        });
        
        renderInqTable();
    };

    function renderInqTable() {
        const tbody = document.getElementById('inq_table_body');
        if (!filteredInquiries.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px; color: var(--text-light);">No inquiries found based on filters.</td></tr>';
            return;
        }
        
        tbody.innerHTML = filteredInquiries.map(i => {
            const statusTag = getStatusTag(i.status);
            const sourceTag = i.source ? `<span class="tag" style="background: #e0f2fe; color: #075985;">${i.source.toUpperCase()}</span>` : '-';
            const date = i.created_at ? new Date(i.created_at).toLocaleDateString() : '-';
            
            return `
                <tr>
                    <td style="padding-left: 24px;">
                        <div style="font-weight: 700; color: var(--text-dark);">${i.full_name}</div>
                        <div style="font-size: 11px; color: var(--text-light); margin-top: 2px;">
                            <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 4px;"></i> ${i.phone}
                        </div>
                    </td>
                    <td><div style="font-size: 13px; font-weight: 600;">${i.course_name || 'N/A'}</div></td>
                    <td>${statusTag}</td>
                    <td>${sourceTag}</td>
                    <td style="font-size: 12px; color: var(--text-light);">${date}</td>
                    <td style="text-align: right; padding-right: 24px;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <button class="btn bt sm" title="Quick View" onclick="quickViewInq(${i.id})"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn bt sm" title="Follow Up" onclick="followUpInq(${i.id})"><i class="fa-solid fa-clock-rotate-left"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function getStatusTag(status) {
        const s = (status || 'pending').toLowerCase();
        let color = '#475569', bg = '#f1f5f9';
        if (s === 'pending') { color = '#b45309'; bg = '#fef3c7'; }
        if (s === 'contacted') { color = '#0369a1'; bg = '#e0f2fe'; }
        if (s === 'interested' || s === 'converted') { color = '#15803d'; bg = '#dcfce7'; }
        if (s === 'not_interested') { color = '#b91c1c'; bg = '#fee2e2'; }
        
        return `<span class="tag" style="background: ${bg}; color: ${color}; font-weight: 700;">${s.toUpperCase()}</span>`;
    }

    window.quickViewInq = (id) => {
        const inq = allInquiries.find(i => i.id == id);
        if (!inq) return;
        
        const modal = document.getElementById('inq_modal');
        const title = document.getElementById('inq_modal_title');
        const body = document.getElementById('inq_modal_body');
        
        title.textContent = "Inquiry Detail: " + inq.full_name;
        body.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div><div class="fl">Personal Info</div><div style="font-weight: 700;">${inq.full_name}</div><div style="font-size: 13px;">${inq.phone}</div><div style="font-size: 12px; color: var(--text-light);">${inq.email || ''}</div></div>
                <div><div class="fl">Academic Interest</div><div style="font-weight: 700;">${inq.course_name || 'N/A'}</div><div style="font-size: 12px;">Inquired on ${new Date(inq.created_at).toLocaleString()}</div></div>
                <div><div class="fl">Address</div><div style="font-size: 13px;">${inq.address || 'Not provided'}</div></div>
                <div><div class="fl">Source / Attribution</div><span class="tag">${(inq.source || 'Walk-in').toUpperCase()}</span></div>
            </div>
            <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                <div class="fl">Internal Counselor's Notes</div>
                <div style="font-size: 13px; line-height: 1.6; color: #475569;">${inq.notes || 'No notes available for this inquiry.'}</div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                <button class="btn bt" onclick="closeInqModal()">Close</button>
                <button class="btn" style="background: var(--green); color: #fff;" onclick="window.location.href='${APP_URL}/dash/front-desk/index?page=admission-form&inquiry_id=${inq.id}'"><i class="fa-solid fa-user-plus"></i> Convert to Admission</button>
            </div>
        `;
        modal.style.display = 'flex';
    };

    window.closeInqModal = () => {
        document.getElementById('inq_modal').style.display = 'none';
    };

    loadInquiries();
})();
</script>

<style>
.pg-nexus { animation: fadeIn 0.4s ease-out; }
.stat-item { padding: 20px; border-radius: 14px; position: relative; overflow: hidden; background: #fff !important; }
.stat-item .lbl { font-size: 11px; text-transform: uppercase; font-weight: 800; opacity: 0.7; margin-bottom: 8px; display: block; }
.stat-item .val { font-size: 24px; font-weight: 850; color: var(--text-dark); display: block; }
.stat-item .sub { font-size: 11px; color: var(--text-light); margin-top: 4px; display: block; }
.fi { border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 16px; font-size: 14px; transition: all 0.2s; font-family: inherit; }
.fi:focus { border-color: var(--green); box-shadow: 0 0 0 4px rgba(0, 184, 148, 0.1); }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; }
.modal-content { background: #fff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; margin: 16px; overflow: hidden; animation: zoomIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 24px; }
.btn.sm { padding: 6px 10px; font-size: 12px; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
