/**
 * Hamro ERP — Message Log Module
 * Unified tracking for SMS and Email
 */
window.renderMessageLog = async function() {
    const mc = document.getElementById('mainContent');
    
    mc.innerHTML = `<div class="pg fu">
        <div class="bc"><a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> <span class="bc-cur">Message Log</span></div>
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico" style="background:linear-gradient(135deg,#64748b,#94a3b8);"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div><div class="pg-title">Message Log</div><div class="pg-sub">Audit trail of all outgoing SMS and Email messages</div></div>
            </div>
        </div>

        <div class="card" style="padding:0;overflow:hidden;border:none;box-shadow:0 4px 20px rgba(0,0,0,0.05);">
            <div style="padding:15px;background:#f8fafc;border-bottom:1px solid #f1f5f9;display:flex;gap:10px;">
                <button class="btn bt active-log-tab" id="tabEmailLogs" onclick="_switchLogType('email')" style="background:#fff;border:1px solid #e2e8f0;font-size:13px;padding:8px 16px;font-weight:600;"><i class="fa-solid fa-envelope" style="margin-right:5px;"></i> Email Logs</button>
                <button class="btn bt" id="tabSmsLogs" onclick="_switchLogType('sms')" style="background:#fff;border:1px solid #e2e8f0;font-size:13px;padding:8px 16px;color:#64748b;"><i class="fa-solid fa-comment-sms" style="margin-right:5px;"></i> SMS Logs</button>
            </div>
            <div id="logTableContainer" style="min-height:500px;overflow-x:auto;">
                <div style="text-align:center;padding:100px 0;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:#cbd5e1;"></i></div>
            </div>
        </div>
    </div>`;

    _switchLogType('email');
};

window._switchLogType = async function(type) {
    const container = document.getElementById('logTableContainer');
    const tabs = ['tabEmailLogs', 'tabSmsLogs'];
    tabs.forEach(t => {
        const el = document.getElementById(t);
        if (t === (type === 'email' ? 'tabEmailLogs' : 'tabSmsLogs')) {
            el.style.borderColor = '#6366f1';
            el.style.color = '#6366f1';
            el.style.zIndex = '1';
        } else {
            el.style.borderColor = '#e2e8f0';
            el.style.color = '#64748b';
            el.style.zIndex = '0';
        }
    });

    container.innerHTML = `<div style="text-align:center;padding:100px 0;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:#cbd5e1;"></i></div>`;

    try {
        const res = await fetch(APP_URL + '/api/admin/communications?action=list_logs&type=' + type);
        const r = await res.json();
        
        if (r.success && r.data) {
            if (r.data.length === 0) {
                container.innerHTML = `<div style="text-align:center;padding:120px 0;color:#94a3b8;"><i class="fa-solid fa-ghost" style="font-size:3rem;display:block;margin-bottom:15px;opacity:.2"></i><p>No ${type} logs found.</p></div>`;
                return;
            }

            let html = `<table class="tbl">
                <thead>
                    <tr>
                        <th style="padding-left:24px;">Recipient</th>
                        <th>${type === 'email' ? 'Subject' : 'Message'}</th>
                        <th>Status</th>
                        <th>Sent Date</th>
                        <th style="text-align:right;padding-right:24px;">Action</th>
                    </tr>
                </thead>
                <tbody>`;

            r.data.forEach(l => {
                const recipient = type === 'email' ? (l.student_name ? `${l.student_name}<br><small style="color:#64748b">${l.email}</small>` : l.email) : l.recipient_phone;
                const content = type === 'email' ? l.subject : (l.message.length > 50 ? l.message.substring(0, 50) + '...' : l.message);
                const statusClass = (l.status === 'sent' || l.status === 'delivered') ? 'bg-t' : (l.status === 'failed' ? 'bg-e' : 'bg-w');
                
                html += `<tr>
                    <td style="padding-left:24px;font-weight:600;color:#1e293b;">${recipient}</td>
                    <td><div style="font-size:13px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${content}</div></td>
                    <td><span class="bdg ${statusClass}" style="font-size:10px;">${l.status.toUpperCase()}</span></td>
                    <td style="font-size:12px;color:#64748b;">${new Date(l.created_at).toLocaleString()}</td>
                    <td style="text-align:right;padding-right:24px;">
                        <button class="btn bt" onclick="_viewLogDetails('${type}', ${l.id})" style="padding:5px 10px;font-size:11px;">View</button>
                    </td>
                </tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        }
    } catch (e) {
        container.innerHTML = `<div style="padding:100px;text-align:center;color:#ef4444;">Failed to load logs.</div>`;
    }
};

window._viewLogDetails = async function(type, id) {
    // For now, just a simple showAlert or expand
    Swal.fire({
        title: type.toUpperCase() + ' Details',
        html: `<div style="text-align:center;padding:20px;color:#94a3b8;"><i class="fa-solid fa-gear fa-spin" style="font-size:2rem;margin-bottom:15px;"></i><p>Detailed log view coming in V3.2.</p></div>`,
        confirmButtonColor: '#6366f1'
    });
};
