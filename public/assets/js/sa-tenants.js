/**
 * Hamro ERP — Super Admin Tenant Management Module
 */
(function(SuperAdmin) {
    "use strict";

    let tenants = [];

    SuperAdmin.renderTenants = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        if (SuperAdmin.activeSub === 'add') {
             // Directly load add-tenant specialized page if in SPA mode
             // Or better, we can have a dedicated renderer for it if we want to stay entirely SPA
             // But the user has a specialized add-tenant.php with complex multi-step. 
             // We'll use fetchAndRender from core.
             SuperAdmin.fetchAndRender('pages/super_admin/add-tenant');
             return;
        }

        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

        try {
            const status = SuperAdmin.activeSub === 'suspended' ? 'suspended' : 'all';
            const result = await SuperAdmin.fetchAPI(`TenantsApi.php?action=list&status=${status}`);
            tenants = result.data || [];
            
            renderTenantsList(mainContent, tenants);
        } catch (err) {
            console.error("[SuperAdmin] Tenants Error:", err);
            mainContent.innerHTML = `<div class="pg fu"><p style="color:red;">Error loading tenants: ${err.message}</p></div>`;
        }
    };

    function renderTenantsList(container, tenantsList) {
        container.innerHTML = `
            <div class="pg fu">
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-building"></i></div>
                        <div>
                            <div class="pg-title">Tenant Management</div>
                            <div class="pg-sub">Manage all institutes on the platform</div>
                        </div>
                    </div>
                    <div class="pg-acts">
                        <button class="btn bt" onclick="SuperAdmin.goNav('tenants', 'add')"><i class="fa-solid fa-plus"></i> Add Institute</button>
                    </div>
                </div>
                
                <div class="card">
                    <div style="display:flex;gap:12px;margin-bottom:20px;">
                        <input type="text" id="tenantSearch" placeholder="Search institutes..." style="flex:1;padding:10px 15px;border:1px solid var(--cb);border-radius:8px;" onkeyup="SuperAdmin.filterTenants()">
                        <select id="tenantStatusFilter" style="padding:10px 15px;border:1px solid var(--cb);border-radius:8px;" onchange="SuperAdmin.filterTenants()">
                            <option value="">All Status</option>
                            <option value="active" ${SuperAdmin.activeSub !== 'suspended' ? 'selected' : ''}>Active</option>
                            <option value="trial">Trial</option>
                            <option value="suspended" ${SuperAdmin.activeSub === 'suspended' ? 'selected' : ''}>Suspended</option>
                        </select>
                    </div>
                    
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid var(--cb);">
                                    <th style="padding:12px;text-align:left;">Institute</th>
                                    <th style="padding:12px;text-align:left;">Plan</th>
                                    <th style="padding:12px;text-align:left;">Status</th>
                                    <th style="padding:12px;text-align:left;">Users</th>
                                    <th style="padding:12px;text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tenantsTableBody">
                                ${renderTenantsRows(tenantsList)}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function renderTenantsRows(list) {
        if (list.length === 0) return '<tr><td colspan="5" style="text-align:center;padding:30px;">No tenants found</td></tr>';
        return list.map(t => `
            <tr style="border-bottom:1px solid var(--cb);">
                <td style="padding:14px 12px;">
                    <div style="font-weight:600;">${t.name}</div>
                    <div style="font-size:11px;color:var(--tl);">${t.subdomain}.hamroerp.com</div>
                </td>
                <td style="padding:14px 12px;"><span class="tag bg-p">${t.plan}</span></td>
                <td style="padding:14px 12px;"><span class="tag ${t.status === 'active' ? 'bg-g' : t.status === 'suspended' ? 'bg-r' : 'bg-t'}">${t.status}</span></td>
                <td style="padding:14px 12px;">${t.user_count || 0}</td>
                <td style="padding:14px 12px;text-align:right;">
                    <button class="btn-icon" onclick="SuperAdmin.viewTenant(${t.id})"><i class="fa-solid fa-eye"></i></button>
                    <button class="btn-icon" onclick="SuperAdmin.editTenant(${t.id})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn-icon" onclick="SuperAdmin.impersonateTenant(${t.id})" title="Impersonate"><i class="fa-solid fa-user-secret"></i></button>
                </td>
            </tr>
        `).join('');
    }

    SuperAdmin.filterTenants = function() {
        const query = document.getElementById('tenantSearch').value.toLowerCase();
        const status = document.getElementById('tenantStatusFilter').value.toLowerCase();
        
        const filtered = tenants.filter(t => {
            const matchesQuery = t.name.toLowerCase().includes(query) || t.subdomain.toLowerCase().includes(query);
            const matchesStatus = status === "" || t.status.toLowerCase() === status;
            return matchesQuery && matchesStatus;
        });

        const tbody = document.getElementById('tenantsTableBody');
        if (tbody) tbody.innerHTML = renderTenantsRows(filtered);
    };

    SuperAdmin.impersonateTenant = async function(id) {
        const confirm = await SuperAdmin.confirmAction("Impersonate Admin", "You will be logged in as an admin of this institute. Log activity will be tracked.");
        if (confirm.isConfirmed) {
            SuperAdmin.showNotification("Redirecting to institute dashboard...", "info");
            window.location.href = `${window.APP_URL}/dash/super-admin/impersonate/${id}`;
        }
    };

    SuperAdmin.viewTenant = (id) => console.log('View tenant:', id);
    SuperAdmin.editTenant = (id) => console.log('Edit tenant:', id);

})(window.SuperAdmin);
