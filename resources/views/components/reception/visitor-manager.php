<?php
/**
 * Shared Visitor Manager Component
 * Nexus Design System
 */

$apiEndpoint = $apiEndpoint ?? APP_URL . '/api/frontdesk/visitor-log';
$componentId = $componentId ?? 'shared_vis';
?>

<div class="pg-nexus">
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Visitor Log</span>
    </div>

    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(245, 158, 11, 0.08); color: #F59E0B;">
                <i class="fa-solid fa-person-walking-arrow-right"></i>
            </div>
            <div>
                <h1 class="pg-title">Visitor Entry Log</h1>
                <p class="pg-sub">Real-time tracking of institutional visitors & security check-ins</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="refreshVisitors()">
                <i class="fa-solid fa-rotate"></i>
            </button>
            <button class="btn" style="background: #F59E0B; color: #fff;" onclick="openCheckInModal()">
                <i class="fa-solid fa-plus-circle"></i> New Entry
            </button>
        </div>
    </div>

    <div class="card" style="border-radius: 14px; overflow: hidden; margin-top: 24px;">
        <div class="table-responsive">
            <table class="table" id="vis_table">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding-left: 24px;">Visitor Profile</th>
                        <th>Purpose of Visit</th>
                        <th style="text-align: center;">Checked In</th>
                        <th style="text-align: center;">Checked Out</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right; padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="vis_table_body">
                    <tr><td colspan="6" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Initializing visitor data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Check-In Modal -->
<div id="checkInModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Visitor Registration</h3>
            <button onclick="closeModal('checkInModal')">&times;</button>
        </div>
        <form id="checkInForm" onsubmit="handleCheckInSubmit(event)" style="padding: 24px;">
            <div class="form-group mb-4">
                <label class="lbl">Visitor Full Name *</label>
                <input type="text" id="vis_name" required class="fi" placeholder="As per Identity Card">
            </div>
            <div class="form-group mb-4">
                <label class="lbl">Mobile Number *</label>
                <input type="text" id="vis_phone" required class="fi" placeholder="Current reachable number">
            </div>
            <div class="form-group mb-4">
                <label class="lbl">Reason for Visit</label>
                <textarea id="vis_notes" class="fi" style="height: 80px; resize: none;" placeholder="e.g. Admission inquiry, Interview..."></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn bt" style="flex: 1;" onclick="closeModal('checkInModal')">Cancel</button>
                <button type="submit" class="btn" style="flex: 2; background: #F59E0B; color: #fff;">Confirm Entry</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const API_URL = "<?= $apiEndpoint ?>";
    let currentVisPage = 1;
    
    window.refreshVisitors = () => loadVisitors(currentVisPage);

    window.loadVisitors = async (page = 1) => {
        currentVisPage = page;
        const tbody = document.getElementById('vis_table_body');
        
        try {
            const res = await fetch(`${API_URL}?page=${page}&limit=20`, getHeaders());
            const r = await res.json();
            if (r.success) {
                const data = r.data || [];
                if (!data.length) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 60px; color: var(--text-light);">No visitor entries found for today.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.map(v => {
                    const isInside = !v.check_out_at;
                    const statusTag = isInside 
                        ? `<span class="tag" style="background:#FFF7ED; color:#C2410C; font-weight:800; border-radius: 6px;">ON PREMISES</span>`
                        : `<span class="tag" style="background:#F1F5F9; color:#475569; font-weight:600; border-radius: 6px;">EXITED</span>`;
                    
                    return `
                        <tr>
                            <td style="padding-left: 24px;">
                                <div style="font-weight: 700; color: var(--text-dark);">${v.full_name}</div>
                                <div style="font-size: 11px; color: var(--text-light); margin-top: 2px;">${v.phone}</div>
                            </td>
                            <td>
                                <div style="font-size: 13px; color: #475569; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${v.notes || '—'}</div>
                            </td>
                            <td style="text-align: center; font-size: 12px; font-weight: 600;">
                                ${new Date(v.check_in_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </td>
                            <td style="text-align: center; font-size: 12px; color: var(--text-light);">
                                ${v.check_out_at ? new Date(v.check_out_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '—'}
                            </td>
                            <td style="text-align: center;">${statusTag}</td>
                            <td style="text-align: right; padding-right: 24px;">
                                ${isInside ? `
                                    <button class="btn sm" style="background: #1e293b; color: #fff;" onclick="checkOutVis(${v.id})">
                                        <i class="fa-solid fa-door-open"></i> Exit
                                    </button>
                                ` : `<i class="fa-solid fa-circle-check" style="color: #10B981;"></i>`}
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--red);">${e.message}</td></tr>`;
        }
    }

    window.checkOutVis = async (id) => {
        if (!confirm('Confirm visitor exit?')) return;
        try {
            const res = await fetch(API_URL, getHeaders({
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action: 'checkout' })
            }));
            const r = await res.json();
            if (r.success) loadVisitors(currentVisPage);
        } catch (e) { alert(e.message); }
    };

    window.openCheckInModal = () => {
        document.getElementById('checkInModal').style.display = 'flex';
        document.getElementById('checkInForm').reset();
    };

    window.closeModal = (id) => {
        document.getElementById(id).style.display = 'none';
    };

    window.handleCheckInSubmit = async (e) => {
        e.preventDefault();
        const data = {
            full_name: document.getElementById('vis_name').value,
            phone: document.getElementById('vis_phone').value,
            notes: document.getElementById('vis_notes').value
        };

        try {
            const res = await fetch(API_URL, getHeaders({
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }));
            const r = await res.json();
            if (r.success) {
                closeModal('checkInModal');
                loadVisitors(1);
            }
        } catch (e) { alert(e.message); }
    };

    loadVisitors();
})();
</script>

<style>
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; }
.modal-content { background: #fff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; margin: 16px; overflow: hidden; animation: zoomIn 0.2s ease-out; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.lbl { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block; }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
