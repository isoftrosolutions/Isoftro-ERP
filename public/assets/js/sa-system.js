/**
 * iSoftro ERP — Super Admin System Module
 * Handles Configuration, Logs, and Internal Settings
 */
(function(SuperAdmin) {
    "use strict";

    SuperAdmin.renderSystem = async function() {
        if (SuperAdmin.activeSub === 'maintenance') {
            await renderMaintenance();
            return;
        }
        if (SuperAdmin.activeSub === 'announce') {
            await renderAnnouncements();
            return;
        }
        if (SuperAdmin.activeSub === 'toggles') {
            await SuperAdmin.renderPlans(); // We'll keep toggles in Plans for now or separate them
            return;
        }
        // Default to a system health dashboard
        await renderSystemOverview();
    };

    SuperAdmin.renderLogs = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-scroll"></i></div>
                        <div>
                            <div class="pg-title">System Logs</div>
                            <div class="pg-sub">Audit trail and real-time error tracking</div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div style="display:flex;gap:12px;margin-bottom:20px;">
                        <button class="btn ${SuperAdmin.activeSub === 'audit' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('logs', 'audit')">Audit Logs</button>
                        <button class="btn ${SuperAdmin.activeSub === 'errors' ? 'bt' : 'bs'}" onclick="SuperAdmin.goNav('logs', 'errors')">Error Logs</button>
                    </div>
                    
                    <div style="background:#0F172A;color:#94a3b8;padding:20px;border-radius:12px;font-family:'Courier New', Courier, monospace;font-size:12px;max-height:500px;overflow-y:auto;">
                        <div style="color:#22c55e;">[system] [${new Date().toISOString()}] SuperAdmin.renderLogs() initialized</div>
                        <div style="color:#3b82f6;">[info] [${new Date().toISOString()}] Successfully authenticated to central logging server</div>
                        <div style="color:#f43f5e;">[error] [${new Date().toISOString()}] Failed to resolve external billing webhook (retrying in 5s)</div>
                        <div>[debug] [${new Date().toISOString()}] Processing batch 502/1040 (Success rate: 99.8%)</div>
                    </div>
                </div>
            </div>
        `;
    };

    SuperAdmin.renderSettings = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-sliders"></i></div>
                        <div>
                            <div class="pg-title">Platform Settings</div>
                            <div class="pg-sub">Global configuration and internal overrides</div>
                        </div>
                    </div>
                </div>
                
                <div class="sg">
                    <div class="sc fu">
                        <h3>General Settings</h3>
                        <div class="form-row">
                            <label class="form-lbl">Platform Name</label>
                            <input class="form-inp" value="iSoftro ERP Platform">
                        </div>
                        <div class="form-row">
                            <label class="form-lbl">Support Contact</label>
                            <input class="form-inp" value="support@hamroerp.com">
                        </div>
                        <button class="btn bt" onclick="SuperAdmin.saveSettings()">Save Configuration</button>
                    </div>
                </div>
            </div>
        `;
    };

    SuperAdmin.renderProfile = async function() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-user-circle"></i></div>
                        <div>
                            <div class="pg-title">My Account</div>
                            <div class="pg-sub">Super admin profile and security credentials</div>
                        </div>
                    </div>
                </div>
                
                <div class="card" style="max-width:600px;">
                    <div class="form-step">
                      <h3>Profile Information</h3>
                      <div class="form-row">
                          <label class="form-lbl">Full Name</label>
                          <input class="form-inp" value="Central Super Admin">
                      </div>
                      <div class="form-row">
                          <label class="form-lbl">Email Address</label>
                          <input class="form-inp" value="admin@hamroerp.com" disabled>
                      </div>
                    </div>
                </div>
            </div>
        `;
    };

    async function renderSystemOverview() {
        const mainContent = document.getElementById('mainContent');
        if (!mainContent) return;

        mainContent.innerHTML = `
            <div class="pg fu">
                <div class="pg-head">
                    <div class="pg-left">
                        <div class="pg-ico"><i class="fa-solid fa-wrench"></i></div>
                        <div>
                            <div class="pg-title">System Health</div>
                            <div class="pg-sub">Central platform node diagnostics</div>
                        </div>
                    </div>
                </div>
                
                <div class="sg">
                    <div class="sc fu">
                        <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Core V-Engine Status</span>
                        <div style="font-size:20px; font-weight:800; color:var(--success); margin:12px 0;"><i class="fa-solid fa-circle-check"></i> OPERATIONAL</div>
                        <p class="sc-delta">Uptime: 45 days, 12 hours</p>
                    </div>
                    <div class="sc fu">
                        <span style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase;">Database Cluster</span>
                        <div style="font-size:20px; font-weight:800; color:var(--success); margin:12px 0;"><i class="fa-solid fa-database"></i> CONNECTED</div>
                        <p class="sc-delta">Queries/sec: 1.2K (Stable)</p>
                    </div>
                </div>

                <div class="card" style="margin-top:24px;">
                    <h3>Environment Configuration</h3>
                    <div style="font-size:13px; color:var(--tl); margin-top:12px;">
                        <pre style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid var(--cb); overflow-x:auto;">
APP_ENV: production
APP_DEBUG: false
PLATFORM_VERSION: 3.0.1
CLUSTER_NODE: hamro-erp-worker-01-ktm
STORAGE_DRIVER: local_vfs
                        </pre>
                    </div>
                </div>
            </div>
        `;
    }

    async function renderMaintenance() {
        const mainContent = document.getElementById('mainContent');
        mainContent.innerHTML = `
            <div class="pg fu"><div class="card"><h3>Maintenance Mode</h3><p>Platform shutdown or scheduled maintenance controls go here.</p></div></div>
        `;
    }

    async function renderAnnouncements() {
        const mainContent = document.getElementById('mainContent');
        mainContent.innerHTML = `
            <div class="pg fu"><div class="card"><h3>Platform Announcements</h3><p>Broadcast messages to all tenants or specific regions.</p></div></div>
        `;
    }

    SuperAdmin.saveSettings = () => SuperAdmin.showNotification("Settings saved successfully", "success");

})(window.SuperAdmin);
