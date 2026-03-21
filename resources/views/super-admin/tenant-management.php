<?php
/**
 * Hamro ERP — Super Admin Tenant Management
 */

// Load global config if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pageTitle = 'Tenant Management';
require_once VIEWS_PATH . '/layouts/header_1.php';
?>

<?php renderSuperAdminHeader(); ?>
<?php renderSidebar('tenants'); ?>

<!-- ── MAIN CONTENT (mirrors institute-admin .main) ── -->
<main class="main" id="mainContent">
    <div class="pg">
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico"><i class="fa-solid fa-hotel"></i></div>
                <div>
                    <h1 class="pg-title">Tenant Management</h1>
                    <p class="pg-sub">Manage all institutes, their subscriptions, and branding settings.</p>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bt" onclick="openAddTenantModal()"><i class="fa-solid fa-plus"></i> Add New Institute</button>
            </div>
        </div>

    <!-- Filters & Search -->
    <div class="sc fu" style="margin-bottom: 24px; padding: 16px;">
        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center; justify-content:space-between;">
            <div style="display:flex; gap:12px; flex:1; min-width:300px;">
                <div class="search-box" style="flex:1;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="tenantSearch" placeholder="Search by name, subdomain, or phone..." onkeyup="filterTenants()">
                </div>
                <select id="planFilter" class="form-control" style="width:150px;" onchange="filterTenants()">
                    <option value="">All Plans</option>
                    <option value="starter">Starter</option>
                    <option value="growth">Growth</option>
                    <option value="professional">Professional</option>
                    <option value="enterprise">Enterprise</option>
                </select>
                <select id="statusFilter" class="form-control" style="width:150px;" onchange="filterTenants()">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="trial">Trial</option>
                </select>
            </div>
            <div style="font-size:12px; color:var(--tl); font-weight:600;">
                Showing <span id="tenantCount">0</span> Institutes
            </div>
        </div>
    </div>

    <!-- Tenants Grid -->
    <div id="tenantsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        <!-- Loaded via JS -->
        <div style="grid-column: 1/-1; text-align:center; padding:50px;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:30px; color:var(--sa-primary);"></i>
            <p style="margin-top:10px; color:var(--tl);">Loading institutes...</p>
        </div>
    </div>
</div>

<!-- Add/Edit Tenant Modal -->
<div id="tenantModal" class="modal-root">
    <div class="modal-card" style="max-width:700px;">
        <div class="modal-head">
            <h2 id="modalTitle">Register New Institute</h2>
            <button class="modal-close" onclick="closeTenantModal()">&times;</button>
        </div>
        <form id="tenantForm" onsubmit="saveTenant(event)">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <!-- Basic Info -->
                    <div>
                        <div class="sb-lbl" style="padding-left:0; margin-bottom:12px;">Institute Details</div>
                        <div class="form-group mb-3">
                            <label class="form-label">Institute Name (English) *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required placeholder="e.g. Bright Future Academy">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Institute Name (Nepali)</label>
                            <input type="text" name="nepaliName" id="edit_nepaliName" class="form-control" placeholder="उदा. ब्राइट फ्युचर एकेडेमी">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Subdomain *</label>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="text" name="subdomain" id="edit_subdomain" class="form-control" required placeholder="brightfuture" style="flex:1;">
                                <span style="font-size:13px; color:var(--tl); font-weight:700;">.hamroerp.com</span>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Primary Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" placeholder="98XXXXXXXX">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Institute Type *</label>
                            <select name="instituteType" id="edit_instituteType" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="loksewa preparation">Loksewa Preparation</option>
                                <option value="computer training">Computer Training</option>
                                <option value="bridge course">Bridge Course</option>
                                <option value="tuition">Tuition</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Branding & Plan -->
                    <div>
                        <div class="sb-lbl" style="padding-left:0; margin-bottom:12px;">Branding & Plan</div>
                        <div class="form-group mb-3">
                            <label class="form-label">Brand Color</label>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="color" name="brandColor" id="edit_brandColor" class="form-control" style="width:50px; height:40px; padding:2px;" value="#009E7E">
                                <input type="text" id="brandColorHex" class="form-control" style="flex:1;" value="#009E7E" onkeyup="document.getElementById('edit_brandColor').value = this.value">
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="tagline" id="edit_tagline" class="form-control" placeholder="Evolving Education Digitally">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Subscription Plan</label>
                            <select name="plan" id="edit_plan" class="form-control">
                                <option value="starter">Starter (100 Students)</option>
                                <option value="growth">Growth (500 Students)</option>
                                <option value="professional">Professional (2000 Students)</option>
                                <option value="enterprise">Enterprise (Unlimited)</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Account Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="trial">Trial Period</option>
                                <option value="active">Active / Paid</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="adminSection">
                    <hr style="margin:20px 0; border:none; border-top:1px solid var(--cb);">
                    <div class="sb-lbl" style="padding-left:0; margin-bottom:12px;">Initial Admin Credentials (Cannot be changed here)</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div class="form-group mb-3">
                            <label class="form-label">Admin Name *</label>
                            <input type="text" name="adminName" id="edit_adminName" class="form-control" placeholder="Full Name">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Admin Email *</label>
                            <input type="email" name="adminEmail" id="edit_adminEmail" class="form-control" placeholder="admin@institute.com">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Admin Phone</label>
                            <input type="text" name="adminPhone" id="edit_adminPhone" class="form-control" placeholder="98XXXXXXXX">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Temporary Password *</label>
                            <input type="password" name="adminPass" id="edit_adminPass" class="form-control" placeholder="Min 8 characters">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn bs" onclick="closeTenantModal()">Cancel</button>
                <button type="submit" class="btn bt" id="saveBtn">Save & Register</button>
   </div>
</main>
<?php include 'footer.php'; ?>
}
</style>
</main>
<?php include 'footer.php'; ?>
