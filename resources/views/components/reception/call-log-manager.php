<?php
/**
 * Shared Call Log Manager Component
 * Nexus Design System
 */

$apiEndpoint = $apiEndpoint ?? APP_URL . '/api/frontdesk/call-logs';
$componentId = $componentId ?? 'shared_calls';
?>

<div class="pg-nexus">
    <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Call Records</span>
    </div>

    <div class="pg-head">
        <div class="pg-left">
            <div class="pg-ico" style="background: rgba(14, 165, 233, 0.08); color: #0EA5E9;">
                <i class="fa-solid fa-phone-volume"></i>
            </div>
            <div>
                <h1 class="pg-title">Telephonic Log</h1>
                <p class="pg-sub">Manage and audit institutional phone interactions</p>
            </div>
        </div>
        <div class="pg-acts">
            <button class="btn bt" onclick="refreshCalls()">
                <i class="fa-solid fa-rotate"></i>
            </button>
            <button class="btn" style="background: #0EA5E9; color: #fff;" onclick="openCallModal()">
                <i class="fa-solid fa-plus-circle"></i> Log Call
            </button>
        </div>
    </div>

    <div class="card" style="border-radius: 14px; overflow: hidden; margin-top: 24px;">
        <div class="table-responsive">
            <table class="table" id="call_table">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="padding-left: 24px;">Date & Time</th>
                        <th>Caller Detail</th>
                        <th style="text-align: center;">Type</th>
                        <th>Conversation Summary</th>
                        <th style="text-align: right; padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="call_table_body">
                    <tr><td colspan="5" style="text-align: center; padding: 60px;"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading call logs...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Call Modal -->
<div id="callModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Log New Call</h3>
            <button onclick="closeModal('callModal')">&times;</button>
        </div>
        <form id="callForm" onsubmit="handleCallSubmit(event)" style="padding: 24px;">
            <div class="form-group mb-4">
                <label class="lbl">Caller / Contact Name *</label>
                <input type="text" id="call_name" required class="fi" placeholder="Name of person">
            </div>
            <div class="form-group mb-4">
                <label class="lbl">Mobile Number *</label>
                <input type="text" id="call_phone" required class="fi" placeholder="Contact number">
            </div>
            <div class="form-group mb-4">
                <label class="lbl">Direction</label>
                <select id="call_type" class="fi">
                    <option value="incoming">Incoming Call</option>
                    <option value="outgoing">Outgoing Call</option>
                </select>
            </div>
            <div class="form-group mb-4">
                <label class="lbl">Interaction Notes</label>
                <textarea id="call_notes" class="fi" style="height: 80px; resize: none;" placeholder="Discussed about..."></textarea>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 32px;">
                <button type="button" class="btn bt" style="flex: 1;" onclick="closeModal('callModal')">Cancel</button>
                <button type="submit" class="btn" style="flex: 2; background: #0EA5E9; color: #fff;">Save Record</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const API_URL = "<?= $apiEndpoint ?>";
    let currentCallPage = 1;
    
    window.refreshCalls = () => loadCalls(currentCallPage);

    window.loadCalls = async (page = 1) => {
        currentCallPage = page;
        const tbody = document.getElementById('call_table_body');
        
        try {
            const res = await fetch(`${API_URL}?page=${page}&limit=20`, getHeaders());
            const r = await res.json();
            if (r.success) {
                const data = r.data || [];
                if (!data.length) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 60px; color: var(--text-light);">No call logs found.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.map(c => {
                    const isIn = c.source === 'incoming';
                    const typeTag = isIn 
                        ? `<span class="tag" style="background:#DBEAFE; color:#1E40AF; border-radius: 6px;"><i class="fa-solid fa-arrow-left"></i> INBOUND</span>`
                        : `<span class="tag" style="background:#F3E8FF; color:#6B21A8; border-radius: 6px;"><i class="fa-solid fa-arrow-right"></i> OUTBOUND</span>`;
                    
                    return `
                        <tr>
                            <td style="padding-left: 24px; font-size: 13px; font-weight: 500;">
                                ${new Date(c.created_at).toLocaleDateString()}
                                <div style="font-size: 11px; color: var(--text-light);">${new Date(c.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-dark);">${c.full_name}</div>
                                <div style="font-size: 11px; color: var(--text-light); margin-top: 2px;">${c.phone}</div>
                            </td>
                            <td style="text-align: center;">${typeTag}</td>
                            <td style="font-size: 13px; color: #475569;">
                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${c.notes || '—'}</div>
                            </td>
                            <td style="text-align: right; padding-right: 24px;">
                                <button class="btn sm bt" style="color: #ef4444;" onclick="deleteCallRecord(${c.id})">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--red);">${e.message}</td></tr>`;
        }
    }

    window.deleteCallRecord = async (id) => {
        if (!confirm('Delete this call log permanentally?')) return;
        try {
            const res = await fetch(`${API_URL}?id=${id}`, getHeaders({ method: 'DELETE' }));
            const r = await res.json();
            if (r.success) loadCalls(currentCallPage);
        } catch (e) { alert(e.message); }
    };

    window.openCallModal = () => {
        document.getElementById('callModal').style.display = 'flex';
        document.getElementById('callForm').reset();
    };

    window.closeModal = (id) => {
        document.getElementById(id).style.display = 'none';
    };

    window.handleCallSubmit = async (e) => {
        e.preventDefault();
        const data = {
            full_name: document.getElementById('call_name').value,
            phone: document.getElementById('call_phone').value,
            call_type: document.getElementById('call_type').value,
            notes: document.getElementById('call_notes').value
        };

        try {
            const res = await fetch(API_URL, getHeaders({
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            }));
            const r = await res.json();
            if (r.success) {
                closeModal('callModal');
                loadCalls(1);
            }
        } catch (e) { alert(e.message); }
    };

    loadCalls();
})();
</script>

<style>
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; }
.modal-content { background: #fff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; margin: 16px; overflow: hidden; animation: zoomIn 0.2s ease-out; }
.modal-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.lbl { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block; }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
