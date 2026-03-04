<?php
/**
 * Front Desk — Inquiry Management
 * Modern, elegant listing of all student inquiries
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (!isset($_GET['partial'])) {
    $pageTitle = 'Inquiry Management';
    require_once VIEWS_PATH . '/layouts/header_1.php';
    require_once __DIR__ . '/sidebar.php';
}
?>

<?php
if (!isset($_GET['partial'])) {
    renderFrontDeskHeader();
    renderFrontDeskSidebar('inquiries');
}
?>
    <div class="pg">
        <!-- Page Header -->
        <div class="pg-head">
            <div>
                <h1 class="pg-title">Inquiry Management</h1>
                <p class="pg-sub">Track potential students and lead conversions</p>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="loadInquiries()">
                    <i class="fa-solid fa-arrows-rotate"></i> Refresh
                </button>
                <a href="<?= APP_URL ?>/dash/front-desk/index?page=inquiry-add" class="btn bs">
                    <i class="fa-solid fa-plus"></i> New Inquiry
                </a>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stat-grid mb">
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Total Inquiries</span></div>
                <div class="stat-value" id="totalInquiries">...</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Pending Follow-ups</span></div>
                <div class="stat-value" id="pendingFollowups">...</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Converted Today</span></div>
                <div class="stat-value" id="convertedToday">...</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-label">Conv. Rate (MTD)</span></div>
                <div class="stat-value" id="conversionRate">...%</div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card mb" style="padding:16px 20px;">
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div style="flex:1; min-width:250px; position:relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:14px;"></i>
                    <input type="text" id="inquirySearch" class="fi" placeholder="Search name, phone, or course..." style="padding-left:40px;" oninput="filterInquiries()">
                </div>
                <select id="statusFilter" class="fi" style="width:180px;" onchange="filterInquiries()">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="follow_up">Follow Up</option>
                    <option value="converted">Converted</option>
                    <option value="closed">Closed / Lost</option>
                </select>
                <select id="sourceFilter" class="fi" style="width:180px;" onchange="filterInquiries()">
                    <option value="">All Sources</option>
                    <option value="walk_in">Walk-in</option>
                    <option value="phone">Phone Call</option>
                    <option value="facebook">Facebook</option>
                    <option value="website">Website</option>
                </select>
            </div>
        </div>

        <!-- Inquiries Table -->
        <div class="card">
            <div class="tw">
                <table id="inquiryTable">
                    <thead>
                        <tr>
                            <th>Inquirer Details</th>
                            <th>Interested Course</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inquiryTableBody">
                        <?php for($i=0; $i<6; $i++): ?>
                        <tr><td colspan="6"><div class="skeleton" style="height:40px;"></div></td></tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Bar -->
            <div id="paginationBar" style="padding:16px 20px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between;">
                <div style="font-size:13px; color:#64748b;" id="paginationInfo">Page 1 of 1</div>
                <div style="display:flex; gap:8px;" id="paginationBtns"></div>
            </div>
        </div>
    </div>

    <!-- Inquiry Detail Modal -->
    <div id="inquiryDetailModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width:90%; max-width:600px; padding:0; border-radius:16px; overflow:hidden;">
            <div style="padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:18px;">Inquiry Details</h3>
                <button onclick="closeModal('inquiryDetailModal')" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:20px;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="inquiryDetailContent" style="padding:24px; max-height:70vh; overflow-y:auto;">
                <div class="pg-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>

    <!-- Add Follow-up Modal -->
    <div id="followupModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
        <div class="card" style="width:90%; max-width:450px; padding:0; border-radius:16px; overflow:hidden;">
            <div style="padding:20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:18px;">Add Follow-up</h3>
                <button onclick="closeModal('followupModal')" style="background:none; border:none; color:#64748b; cursor:pointer; font-size:20px;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="followupForm" style="padding:24px;" onsubmit="handleFollowupSubmit(event)">
                <input type="hidden" name="inquiry_id" id="fu_inquiry_id">
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Remarks</label>
                    <textarea name="remarks" id="fu_remarks" required class="fi" style="height:100px; resize:none;" placeholder="What was the result of the call?"></textarea>
                </div>
                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#64748b; margin-bottom:8px;">Next Follow-up Date</label>
                    <input type="date" name="next_followup_date" id="fu_next_date" class="fi">
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn bt" onclick="closeModal('followupModal')">Cancel</button>
                    <button type="submit" class="btn" style="background:linear-gradient(135deg, #8B5CF6, #7C3AED); color:#fff;">Save Follow-up</button>
                </div>
            </form>
        </div>
    </div>
<style>
.fi { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; background:#fff; box-sizing:border-box; }
.fi:focus { border-color:#8B5CF6; box-shadow:0 0 0 3px rgba(139, 92, 246, 0.1); }
.btn { padding:10px 20px; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; border:none; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.bt { background:#fff; color:#374151; border:1.5px solid #e2e8f0; }
.bt:hover { background:#f8fafc; border-color:#cbd5e1; }
.st-tag { font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; text-transform:uppercase; letter-spacing:0.3px; }
.st-pending { background:#F3E8FF; color:#7E22CE; }
.st-follow_up { background:#FEF3C7; color:#92400E; }
.st-converted { background:#DCFCE7; color:#166534; }
.st-closed { background:#F1F5F9; color:#475569; }
.st-lost { background:#FEE2E2; color:#B91C1C; }
</style>

<script>
let currentPage = 1;
let totalPages = 1;
let pageSize = 20;

async function loadInquiries(page = 1) {
    currentPage = page;
    const search = document.getElementById('inquirySearch').value;
    const status = document.getElementById('statusFilter').value;
    const source = document.getElementById('sourceFilter').value;
    
    const tbody = document.getElementById('inquiryTableBody');
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:24px; margin-bottom:10px; display:block;"></i>Loading...</td></tr>`;

    try {
        const url = new URL('<?= APP_URL ?>/api/frontdesk/inquiries');
        url.searchParams.set('page', page);
        url.searchParams.set('limit', pageSize);
        if (search) url.searchParams.set('search', search);
        if (status) url.searchParams.set('status', status);
        if (source) url.searchParams.set('source', source);

        const res = await fetch(url.toString(), getHeaders());
        const result = await res.json();
        
        if (result.success) {
            allInquiries = result.data || [];
            totalPages = result.total_pages || 1;
            
            // Update stats with counts from response if available, or just update based on what we have
            // Actually, for stats we might need separate API calls or return counts in response
            // For now let's use the totals returned for the current filter
            document.getElementById('totalInquiries').textContent = result.total || 0;
            
            renderTable(allInquiries);
            renderPagination();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:24px; margin-bottom:10px; display:block;"></i> Error: ${error.message}</td></tr>`;
    }
}

function renderPagination() {
    const info = document.getElementById('paginationInfo');
    const btns = document.getElementById('paginationBtns');
    
    info.textContent = `Page ${currentPage} of ${totalPages}`;
    
    let html = '';
    // Previous
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadInquiries(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-left"></i>
    </button>`;
    
    // Page numbers
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);
    
    for (let i = start; i <= end; i++) {
        const active = i === currentPage;
        html += `<button class="btn ${active ? '' : 'bt'}" 
            style="padding:4px 10px; font-size:12px; ${active ? 'background:#8B5CF6; color:#fff; border:none;' : ''}" 
            onclick="loadInquiries(${i})">${i}</button>`;
    }
    
    // Next
    html += `<button class="btn bt" style="padding:4px 10px; font-size:12px;" onclick="loadInquiries(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-right"></i>
    </button>`;
    
    btns.innerHTML = html;
}

function filterInquiries() {
    // Reset to page 1 on filter/search
    loadInquiries(1);
}

function updateStats() {
    const total = allInquiries.length;
    const pending = allInquiries.filter(i => i.status === 'pending' || i.status === 'follow_up').length;
    const convertedToday = allInquiries.filter(i => i.status === 'converted' && i.updated_at?.startsWith(new Date().toISOString().split('T')[0])).length;
    
    document.getElementById('totalInquiries').textContent = total;
    document.getElementById('pendingFollowups').textContent = pending;
    document.getElementById('convertedToday').textContent = convertedToday;
    
    const convertedTotal = allInquiries.filter(i => i.status === 'converted').length;
    const rate = total > 0 ? Math.round((convertedTotal / total) * 100) : 0;
    document.getElementById('conversionRate').textContent = rate + '%';
}

function filterInquiries() {
    const search = document.getElementById('inquirySearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const source = document.getElementById('sourceFilter').value;
    
    const filtered = allInquiries.filter(i => {
        const matchSearch = !search || 
            (i.full_name || '').toLowerCase().includes(search) || 
            (i.phone || '').toLowerCase().includes(search) || 
            (i.course_name || '').toLowerCase().includes(search);
        const matchStatus = !status || i.status === status;
        const matchSource = !source || i.source === source;
        return matchSearch && matchStatus && matchSource;
    });
    
    renderTable(filtered);
}

function renderTable(data) {
    const tbody = document.getElementById('inquiryTableBody');
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;">No inquiries found matching criteria.</td></tr>`;
        return;
    }
    
    tbody.innerHTML = data.map(i => {
        const statusClass = `st-${i.status || 'pending'}`;
        const sourceLabel = (i.source || 'Walk-in').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        return `
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:14px 16px;">
                    <div style="font-weight:600; color:#1a1a2e;">${esc(i.full_name)}</div>
                    <div style="font-size:12px; color:#64748b;">${esc(i.phone)}</div>
                </td>
                <td style="padding:14px 16px;">
                    <div style="font-size:13px; font-weight:500; color:#1a1a2e;">${esc(i.course_name || 'N/A')}</div>
                </td>
                <td style="padding:14px 16px;">
                    <div style="font-size:12px; color:#64748b;"><i class="fa-solid ${getSourceIcon(i.source)}" style="margin-right:6px; opacity:0.7;"></i> ${sourceLabel}</div>
                </td>
                <td style="padding:14px 16px;">
                    <span class="st-tag ${statusClass}">${(i.status || 'pending').replace('_', ' ')}</span>
                </td>
                <td style="padding:14px 16px; font-size:12px; color:#64748b;">
                    ${formatDate(i.created_at)}
                </td>
                <td style="padding:14px 16px; text-align:center;">
                    <div style="display:flex; justify-content:center; gap:8px;">
                        <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="viewInquiry(${i.id})" title="View Details">
                            <i class="fa-solid fa-eye" style="color:#3B82F6;"></i>
                        </button>
                        <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="addFollowup(${i.id})" title="Add Follow-up">
                            <i class="fa-solid fa-phone" style="color:#F59E0B;"></i>
                        </button>
                        <button class="btn bt" style="padding:6px 10px; font-size:12px;" onclick="convertToAdmission(${i.id})" title="Convert to Admission">
                            <i class="fa-solid fa-user-plus" style="color:#10B981;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function getSourceIcon(source) {
    switch(source) {
        case 'walk_in': return 'fa-person-walking';
        case 'phone': return 'fa-phone';
        case 'facebook': return 'fa-facebook';
        case 'website': return 'fa-globe';
        default: return 'fa-circle-question';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function esc(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = 'auto';
}

async function viewInquiry(id) {
    const modal = document.getElementById('inquiryDetailModal');
    const content = document.getElementById('inquiryDetailContent');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    content.innerHTML = `<div style="text-align:center; padding:40px; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:32px; margin-bottom:15px; display:block;"></i>Fetching details...</div>`;

    try {
        const res = await fetch(`<?= APP_URL ?>/api/frontdesk/inquiries?id=${id}`, getHeaders());
        const result = await res.json();
        if (result.success) {
            const i = result.data;
            content.innerHTML = `
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Full Name</div>
                        <div style="font-weight:600; font-size:15px; color:#1e293b;">${esc(i.full_name)}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Course Interest</div>
                        <div style="font-weight:600; font-size:15px; color:#1e293b;">${esc(i.course_name || 'N/A')}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Phone</div>
                        <div style="font-weight:600; font-size:15px; color:#1e293b;">${esc(i.phone)}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Email</div>
                        <div style="font-weight:600; font-size:15px; color:#1e293b;">${esc(i.email || 'N/A')}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Source</div>
                        <div style="font-weight:600; font-size:15px; color:#1e293b;">${esc(i.source.replace('_',' ')).replace(/\b\w/g, l => l.toUpperCase())}</div>
                    </div>
                    <div>
                        <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Date Created</div>
                        <div style="font-weight:600; font-size:15px; color:#1e293b;">${formatDate(i.created_at)}</div>
                    </div>
                </div>
                <div style="margin-top:24px;">
                    <div style="font-size:12px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:8px;">Notes</div>
                    <div style="background:#f8fafc; padding:12px 16px; border-radius:10px; color:#475569; font-size:14px; line-height:1.6; min-height:60px;">
                        ${esc(i.notes) || '<span style="color:#94a3b8; font-style:italic;">No notes provided.</span>'}
                    </div>
                </div>
                <div style="margin-top:24px; display:flex; gap:12px; justify-content:flex-end;">
                    <a href="<?= APP_URL ?>/dash/front-desk/inquiry-edit?id=${i.id}" class="btn bt"><i class="fa-solid fa-pen"></i> Edit Profile</a>
                    <button class="btn" style="background:#10B981; color:#fff;" onclick="convertToAdmission(${i.id})"><i class="fa-solid fa-user-plus"></i> Admit Student</button>
                </div>
            `;
        } else {
            throw new Error(result.message);
        }
    } catch (e) {
        content.innerHTML = `<div style="text-align:center; padding:40px; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:32px; margin-bottom:15px; display:block;"></i> ${e.message}</div>`;
    }
}

function addFollowup(id) {
    const modal = document.getElementById('followupModal');
    document.getElementById('fu_inquiry_id').value = id;
    document.getElementById('fu_remarks').value = '';
    document.getElementById('fu_next_date').value = '';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

async function handleFollowupSubmit(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...`;

    const data = {
        action: 'followup',
        inquiry_id: document.getElementById('fu_inquiry_id').value,
        remarks: document.getElementById('fu_remarks').value,
        next_followup_date: document.getElementById('fu_next_date').value
    };

    try {
        const res = await fetch('<?= APP_URL ?>/api/frontdesk/inquiries', getHeaders({
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }));
        const result = await res.json();
        
        if (result.success) {
            closeModal('followupModal');
            loadInquiries(); // Refresh list to update status
            // Show toast if available
            if (window.showToast) window.showToast('success', result.message);
            else alert(result.message);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alert(error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function convertToAdmission(id) { window.location.href = '<?= APP_URL ?>/dash/front-desk/admission-form?inquiry_id=' + id; }

document.addEventListener('DOMContentLoaded', loadInquiries);
</script>

<?php
if (!isset($_GET['partial'])) {
    renderSuperAdminCSS();
    echo '<script src="' . APP_URL . '/public/assets/js/frontdesk.js"></script>';
    echo '</body></html>';
}
?>
