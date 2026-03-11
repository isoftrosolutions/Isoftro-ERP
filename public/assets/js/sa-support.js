/**
 * Hamro ERP — Super Admin Support Module
 */
(function(SuperAdmin) {
    "use strict";

    SuperAdmin.renderSupport = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

        try {
            const status = SuperAdmin.activeSub || 'open';
            const result = await SuperAdmin.fetchAPI(`SupportApi.php?action=list&status=${status}`);
            const tickets = result.data || [];
            const counts = result.status_counts || {};
            
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-head">
                        <div class="pg-left">
                            <div class="pg-ico"><i class="fa-solid fa-ticket"></i></div>
                            <div>
                                <div class="pg-title">Support Tickets</div>
                                <div class="pg-sub">Manage platform-wide support requests</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:12px;margin-bottom:20px;">
                        <button class="btn ${status === 'open' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('support', 'open')">Open (${counts.open || 0})</button>
                        <button class="btn ${status === 'resolved' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('support', 'resolved')">Resolved (${counts.resolved || 0})</button>
                        <button class="btn ${status === 'all' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('support', 'all')">All</button>
                    </div>
                    
                    <div class="card">
                        ${tickets.length === 0 ? 
                            '<div style="text-align:center;padding:40px;color:var(--tl);">No support tickets found</div>' : 
                            tickets.map(t => `
                                <div style="padding:16px;border-bottom:1px solid var(--cb);display:flex;align-items:center;gap:16px;">
                                    <div style="width:12px;height:12px;border-radius:50%;background:${t.priority === 'critical' ? 'var(--red)' : t.priority === 'high' ? 'var(--amber)' : 'var(--blue)'}" title="${t.priority} priority"></div>
                                    <div style="flex:1;">
                                        <div style="font-weight:700;">${t.subject}</div>
                                        <div style="font-size:11px;color:var(--tl);">${t.tenant_name} (${t.subdomain}) - ${new Date(t.created_at).toLocaleDateString()}</div>
                                    </div>
                                    <span class="tag ${t.status === 'open' ? 'bg-r' : 'bg-g'}">${t.status}</span>
                                    <button class="btn-icon" onclick="SuperAdmin.viewTicket(${t.id})"><i class="fa-solid fa-eye"></i></button>
                                </div>
                            `).join('')
                        }
                    </div>
                </div>
            `;
        } catch (err) {
            console.error("[SuperAdmin] Support Error:", err);
            mainContent.innerHTML = `<div class="pg fu"><p style="color:red;">Error loading support tickets.</p></div>`;
        }
    };

    SuperAdmin.viewTicket = (id) => console.log('View ticket:', id);

})(window.SuperAdmin);
