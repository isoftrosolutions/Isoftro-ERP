/**
 * Hamro ERP — Student Portal · st-leave.js
 * Student Leave Request Module
 */

window.renderSTLeave = async function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = '<div style="padding:24px;"><div class="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading...</div></div>';

    try {
        // Fetch leave requests and stats
        const [reqRes, statsRes] = await Promise.all([
            fetch(`${window.APP_URL}/api/student/leave?action=my_requests`),
            fetch(`${window.APP_URL}/api/student/leave?action=stats`)
        ]);
        
        const reqResult = await reqRes.json();
        const statsResult = await statsRes.json();
        
        const requests = reqResult.success ? (reqResult.data?.requests || []) : [];
        const counts = reqResult.success ? (reqResult.data?.counts || {}) : {};
        const stats = statsResult.success ? (statsResult.data || {}) : {};
        
        const today = new Date().toISOString().split('T')[0];
        
        mc.innerHTML = `
            <div style="padding:24px;">
                <!-- Header -->
                <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));color:#fff;">
                    <div class="card-body" style="padding:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                            <div style="display:flex;align-items:center;gap:16px;">
                                <div style="width:50px;height:50px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--sa-primary);font-size:1.5rem;">
                                    <i class="fa-solid fa-user-clock"></i>
                                </div>
                                <div>
                                    <h2 style="margin:0;font-size:1.3rem;">Apply Leave</h2>
                                    <p style="margin:5px 0 0;opacity:0.9;font-size:13px;">Submit and track your leave requests</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:24px;">
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#d97706;">${stats.pending || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Pending</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#16a34a;">${stats.approved || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Approved</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#dc2626;">${stats.rejected || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Rejected</div>
                        </div>
                    </div>
                    <div class="card">
                        <div style="padding:20px;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:var(--sa-primary);">${stats.total_days || 0}</div>
                            <div style="font-size:12px;color:var(--tl);">Total Days</div>
                        </div>
                    </div>
                </div>
                
                <!-- Apply Leave Form -->
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-hdr">
                        <div class="ct"><i class="fa-solid fa-plus-circle" style="margin-right:8px;color:var(--sa-primary);"></i> Apply for Leave</div>
                    </div>
                    <div class="card-body">
                        <form id="leaveForm" onsubmit="submitLeave(event)">
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:16px;">
                                <div>
                                    <label style="display:block;margin-bottom:6px;font-weight:600;color:var(--td);">From Date *</label>
                                    <input type="date" id="fromDate" name="from_date" class="form-control" required min="${today}" style="width:100%;padding:10px;border:1px solid var(--cb);border-radius:8px;background:var(--bg);color:var(--td);">
                                </div>
                                <div>
                                    <label style="display:block;margin-bottom:6px;font-weight:600;color:var(--td);">To Date *</label>
                                    <input type="date" id="toDate" name="to_date" class="form-control" required min="${today}" style="width:100%;padding:10px;border:1px solid var(--cb);border-radius:8px;background:var(--bg);color:var(--td);">
                                </div>
                            </div>
                            <div style="margin-bottom:16px;">
                                <label style="display:block;margin-bottom:6px;font-weight:600;color:var(--td);">Reason *</label>
                                <textarea id="leaveReason" name="reason" class="form-control" rows="3" required minlength="10" placeholder="Please provide a valid reason for your leave request (at least 10 characters)..." style="width:100%;padding:10px;border:1px solid var(--cb);border-radius:8px;background:var(--bg);color:var(--td);resize:vertical;"></textarea>
                                <div style="font-size:12px;color:var(--tl);margin-top:4px;">Minimum 10 characters required</div>
                            </div>
                            <button type="submit" class="btn bs" id="submitBtn" style="background:var(--sa-primary);color:#fff;">
                                <i class="fa-solid fa-paper-plane"></i> Submit Request
                            </button>
                        </form>
                        <div id="leaveMessage" style="margin-top:16px;display:none;"></div>
                    </div>
                </div>
                
                <!-- Leave Requests List -->
                <div class="card">
                    <div class="card-hdr" style="display:flex;justify-content:space-between;align-items:center;">
                        <div class="ct"><i class="fa-solid fa-list" style="margin-right:8px;color:var(--sa-primary);"></i> My Leave Requests</div>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-sm" onclick="filterLeaveRequests('all')" style="padding:6px 12px;font-size:12px;background:var(--cb);border:1px solid var(--cb);">All</button>
                            <button class="btn btn-sm" onclick="filterLeaveRequests('pending')" style="padding:6px 12px;font-size:12px;background:#fef3c7;border:1px solid #fbbf24;color:#92400e;">Pending</button>
                            <button class="btn btn-sm" onclick="filterLeaveRequests('approved')" style="padding:6px 12px;font-size:12px;background:#dcfce7;border:1px solid #22c55e;color:#166534;">Approved</button>
                            <button class="btn btn-sm" onclick="filterLeaveRequests('rejected')" style="padding:6px 12px;font-size:12px;background:#fee2e2;border:1px solid #ef4444;color:#991b1b;">Rejected</button>
                        </div>
                    </div>
                    <div class="card-body" id="leaveList">
                        ${renderLeaveList(requests)}
                    </div>
                </div>
            </div>
        `;
        
        // Store requests globally for filtering
        window._leaveRequests = requests;
        
    } catch (e) {
        console.error('Leave load error:', e);
        mc.innerHTML = '<div style="padding:24px;"><div class="card"><div class="card-body" style="text-align:center;padding:40px;"><i class="fa-solid fa-exclamation-triangle" style="font-size:3rem;color:var(--red);margin-bottom:15px;"></i><p>Error loading leave requests.</p></div></div></div>';
    }
};

function renderLeaveList(requests) {
    if (!requests || requests.length === 0) {
        return `
            <div style="text-align:center;padding:40px;color:var(--tl);">
                <i class="fa-solid fa-calendar-xmark" style="font-size:3rem;margin-bottom:15px;opacity:0.3;"></i>
                <p>No leave requests found</p>
            </div>
        `;
    }
    
    return `
        <table class="table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                ${requests.map(r => {
                    const fromDate = new Date(r.from_date);
                    const toDate = new Date(r.to_date);
                    const days = Math.ceil((toDate - fromDate) / (1000 * 60 * 60 * 24)) + 1;
                    
                    let statusBadge = '';
                    let statusClass = '';
                    switch(r.status) {
                        case 'pending':
                            statusBadge = '<span class="badge" style="background:#fef3c7;color:#92400e;">Pending</span>';
                            break;
                        case 'approved':
                            statusBadge = '<span class="badge" style="background:#dcfce7;color:#166534;">Approved</span>';
                            break;
                        case 'rejected':
                            statusBadge = '<span class="badge" style="background:#fee2e2;color:#991b1b;">Rejected</span>';
                            break;
                    }
                    
                    const canCancel = r.status === 'pending';
                    
                    return `
                        <tr>
                            <td>${formatDate(r.from_date)}</td>
                            <td>${formatDate(r.to_date)}</td>
                            <td>${days}</td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapeHtml(r.reason)}">${escapeHtml(r.reason)}</td>
                            <td>${statusBadge}</td>
                            <td style="font-size:12px;color:var(--tl);">${formatDate(r.created_at)}</td>
                            <td>
                                ${canCancel ? `<button class="btn btn-sm" onclick="cancelLeave(${r.id})" style="padding:4px 8px;font-size:11px;background:#fee2e2;border:1px solid #ef4444;color:#991b1b;">Cancel</button>` : '-'}
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
}

function filterLeaveRequests(status) {
    const requests = window._leaveRequests || [];
    let filtered = requests;
    
    if (status !== 'all') {
        filtered = requests.filter(r => r.status === status);
    }
    
    document.getElementById('leaveList').innerHTML = renderLeaveList(filtered);
}

window.submitLeave = async function(e) {
    e.preventDefault();
    
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const reason = document.getElementById('leaveReason').value;
    const msgDiv = document.getElementById('leaveMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    // Validation
    if (new Date(fromDate) > new Date(toDate)) {
        showMessage('From date cannot be after To date', 'error');
        return;
    }
    
    if (reason.length < 10) {
        showMessage('Please provide a reason (at least 10 characters)', 'error');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
    
    try {
        const res = await fetch(`${window.APP_URL}/api/student/leave?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ from_date: fromDate, to_date: toDate, reason: reason })
        });
        
        const result = await res.json();
        
        if (result.success) {
            showMessage(result.message || 'Leave request submitted successfully', 'success');
            document.getElementById('leaveForm').reset();
            // Reload the page to show new request
            setTimeout(() => {
                window.renderSTLeave();
            }, 1500);
        } else {
            showMessage(result.message || 'Failed to submit leave request', 'error');
        }
    } catch (err) {
        console.error('Submit error:', err);
        showMessage('Error submitting leave request', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Submit Request';
    }
    
    function showMessage(msg, type) {
        msgDiv.style.display = 'block';
        msgDiv.innerHTML = `
            <div style="padding:12px;border-radius:8px;${type === 'success' ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;'}">
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}
            </div>
        `;
        setTimeout(() => {
            msgDiv.style.display = 'none';
        }, 5000);
    }
};

window.cancelLeave = async function(id) {
    if (!confirm('Are you sure you want to cancel this leave request?')) {
        return;
    }
    
    try {
        const res = await fetch(`${window.APP_URL}/api/student/leave?action=cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await res.json();
        
        if (result.success) {
            alert(result.message || 'Leave request cancelled');
            window.renderSTLeave();
        } else {
            alert(result.message || 'Failed to cancel request');
        }
    } catch (err) {
        console.error('Cancel error:', err);
        alert('Error cancelling request');
    }
};

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.renderSTLeave = window.renderSTLeave;
