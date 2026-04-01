/**
 * iSoftro ERP — Super Admin Plan Management Module
 */
(function(SuperAdmin) {
    "use strict";

    SuperAdmin.renderPlans = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = '<div class="pg fu" style="display:flex;align-items:center;justify-content:center;height:50vh;"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem;color:var(--sa-primary);"></i></div>';

        try {
            const result = await SuperAdmin.fetchAPI('PlansApi.php?action=list');
            const plans = result.data || [];
            
            mainContent.innerHTML = `
                <div class="pg fu">
                    <div class="pg-head">
                        <div class="pg-left">
                            <div class="pg-ico"><i class="fa-solid fa-clipboard-list"></i></div>
                            <div>
                                <div class="pg-title">Plan Management</div>
                                <div class="pg-sub">Define and manage subscription plans</div>
                            </div>
                        </div>
                        <div class="pg-acts">
                            <button class="btn bt" onclick="SuperAdmin.addPlan()"><i class="fa-solid fa-plus"></i> Create Plan</button>
                        </div>
                    </div>
                    
                    <div class="sg">
                        ${plans.map(p => `
                            <div class="sc fu" style="border-top: 4px solid var(--sa-primary);">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                    <h3 style="margin:0;">${p.name}</h3>
                                    <span class="tag bg-g">${p.is_active ? 'Active' : 'Inactive'}</span>
                                </div>
                                <div class="sc-val" style="font-size:24px;">रू ${p.price_monthly.toLocaleString()}<small style="font-size:12px;color:var(--tl);">/mo</small></div>
                                <ul style="list-style:none;padding:0;margin:16px 0;font-size:13px;color:var(--tl);">
                                    <li><i class="fa-solid fa-check" style="color:var(--success);"></i> ${p.student_limit} Students</li>
                                    <li><i class="fa-solid fa-check" style="color:var(--success);"></i> ${p.sms_limit} SMS / month</li>
                                    <li><i class="fa-solid fa-check" style="color:var(--success);"></i> ${p.features_count} Modules Included</li>
                                </ul>
                                <div style="display:flex;gap:8px;">
                                    <button class="btn bs" style="flex:1;" onclick="SuperAdmin.editPlan(${p.id})">Edit</button>
                                    <button class="btn bs" style="flex:1;" onclick="SuperAdmin.manageFeatures(${p.id})">Features</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } catch (err) {
            console.error("[SuperAdmin] Plans Error:", err);
            mainContent.innerHTML = `<div class="pg fu"><p style="color:red;">Error loading plans.</p></div>`;
        }
    };

    SuperAdmin.addPlan = () => SuperAdmin.showNotification("Plan creation coming soon", "info");
    SuperAdmin.editPlan = (id) => console.log('Edit plan:', id);
    SuperAdmin.manageFeatures = (id) => console.log('Manage features:', id);

})(window.SuperAdmin);
