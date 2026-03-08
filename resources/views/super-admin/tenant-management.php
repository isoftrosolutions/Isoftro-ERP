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
        </form>
    </div>
</div>

<script>
let allTenants = [];

async function loadTenants() {
    try {
        const res = await fetch('<?php echo APP_URL; ?>/api/super-admin/tenants');
        const result = await res.json();
        if (result.success) {
            allTenants = result.data;
            renderTenants(allTenants);
        }
    } catch (e) {
        console.error("Failed to load tenants", e);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderTenants(tenants) {
    const grid = document.getElementById('tenantsGrid');
    const count = document.getElementById('tenantCount');
    grid.innerHTML = '';
    count.textContent = tenants.length;

    if (tenants.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:50px; color:var(--tl);">No institutes found matching your filters.</div>';
        return;
    }

    tenants.forEach(t => {
        const card = document.createElement('div');
        card.className = 'sc fu';
        card.style.padding = '0';
        card.style.overflow = 'hidden';
        
        const planClass = 'bg-' + (t.plan === 'starter' ? 't' : t.plan === 'growth' ? 'y' : t.plan === 'professional' ? 'p' : 'i');
        const statusClass = 'bg-' + (t.status === 'active' ? 'g' : t.status === 'trial' ? 'y' : 'r');

        const escapedName = escapeHtml(t.name);
        const escapedSubdomain = escapeHtml(t.subdomain);
        const escapedNepaliName = escapeHtml(t.nepali_name);
        const escapedTagline = escapeHtml(t.tagline);
        const escapedPlan = escapeHtml(t.plan);
        const escapedStatus = escapeHtml(t.status);

        card.innerHTML = `
            <div style="height:60px; background:${t.brand_color || '#009E7E'}; position:relative;">
                <div style="position:absolute; bottom:-20px; left:20px; width:48px; height:48px; border-radius:12px; background:#fff; box-shadow:0 4px 10px rgba(0,0,0,0.1); display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; color:${t.brand_color || '#009E7E'};">
                    ${escapedName.charAt(0)}
                </div>
            </div>
            <div style="padding:24px 20px 20px; margin-top:10px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                    <h3 style="font-size:16px; font-weight:800; color:var(--td); margin:0;">${escapedName}</h3>
                    <span class="tag ${statusClass}">${escapedStatus}</span>
                </div>
                <div style="font-size:11px; color:var(--tl); margin-bottom:12px; display:flex; gap:8px;">
                    <span><i class="fa-solid fa-link"></i> ${escapedSubdomain}.hamroerp.com</span>
                    ${escapedNepaliName ? `<span>| ${escapedNepaliName}</span>` : ''}
                </div>
                
                <p style="font-size:12px; color:var(--tb); line-height:1.4; margin-bottom:16px; height:34px; overflow:hidden;">
                    ${escapedTagline || 'No tagline set for this institute.'}
                </p>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; padding:12px; background:#f8fafc; border-radius:10px;">
                    <div>
                        <div style="font-size:9px; color:var(--tl); font-weight:700; text-transform:uppercase;">Plan</div>
                        <div style="font-size:12px; font-weight:700;"><span class="tag ${planClass}">${escapedPlan}</span></div>
                    </div>
                    <div>
                        <div style="font-size:9px; color:var(--tl); font-weight:700; text-transform:uppercase;">SMS Balance</div>
                        <div style="font-size:12px; font-weight:700;">${t.sms_credits}</div>
                    </div>
                </div>

                <div style="display:flex; gap:8px;">
                    <button class="btn bs" style="flex:1; justify-content:center; padding:8px;" onclick='openEditTenantModal(${JSON.stringify(t)})'><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                    <button class="btn bs" style="flex:1; justify-content:center; padding:8px;" onclick="impersonateAdmin('${escapedSubdomain}')"><i class="fa-solid fa-user-secret"></i> Log In</button>
                    <button class="btn bs" style="width:40px; justify-content:center; padding:8px; color:var(--red);" onclick="confirmDeleteTenant(${t.id}, '${escapedName.replace(/'/g, "\\'")}')"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

function filterTenants() {
    const q = document.getElementById('tenantSearch').value.toLowerCase();
    const p = document.getElementById('planFilter').value;
    const s = document.getElementById('statusFilter').value;

    const filtered = allTenants.filter(t => {
        const matchesQuery = t.name.toLowerCase().includes(q) || 
                            t.subdomain.toLowerCase().includes(q) || 
                            (t.phone && t.phone.includes(q));
        const matchesPlan = !p || t.plan === p;
        const matchesStatus = !s || t.status === s;
        return matchesQuery && matchesPlan && matchesStatus;
    });

    renderTenants(filtered);
}

function openAddTenantModal() {
    document.getElementById('edit_id').value = '';
    document.getElementById('tenantForm').reset();
    document.getElementById('modalTitle').textContent = 'Register New Institute';
    document.getElementById('saveBtn').textContent = 'Save & Register';
    document.getElementById('adminSection').style.display = 'block';
    document.getElementById('adminSection').querySelectorAll('input').forEach(i => i.required = true);
    document.getElementById('tenantModal').classList.add('active');
}

function openEditTenantModal(t) {
    document.getElementById('edit_id').value = t.id;
    document.getElementById('edit_name').value = t.name;
    document.getElementById('edit_nepaliName').value = t.nepali_name || '';
    document.getElementById('edit_subdomain').value = t.subdomain;
    document.getElementById('edit_phone').value = t.phone || '';
    document.getElementById('edit_brandColor').value = t.brand_color || '#009E7E';
    document.getElementById('brandColorHex').value = t.brand_color || '#009E7E';
    document.getElementById('edit_tagline').value = t.tagline || '';
    document.getElementById('edit_plan').value = t.plan;
    document.getElementById('edit_status').value = t.status;
    
    document.getElementById('modalTitle').textContent = 'Update Institute Settings';
    document.getElementById('saveBtn').textContent = 'Update Institute';
    document.getElementById('adminSection').style.display = 'none';
    document.getElementById('adminSection').querySelectorAll('input').forEach(i => i.required = false);
    document.getElementById('tenantModal').classList.add('active');
}

function closeTenantModal() {
    document.getElementById('tenantModal').classList.remove('active');
}

async function saveTenant(e) {
    e.preventDefault();
    const btn = document.getElementById('saveBtn');
    const id = document.getElementById('edit_id').value;
    const formData = new FormData(e.target);
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

    const url = id ? '<?php echo APP_URL; ?>/api/super-admin/tenants/update' : '<?php echo APP_URL; ?>/api/super-admin/tenants/save';

    try {
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.success) {
            Swal.fire('Success', result.message, 'success');
            closeTenantModal();
            loadTenants();
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (e) {
        Swal.fire('System Error', 'Could not complete the request.', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = id ? 'Update Institute' : 'Save & Register';
    }
}

function confirmDeleteTenant(id, name) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to suspend and hide "${name}". All users of this institute will also be suspended.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Yes, delete it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('<?php echo APP_URL; ?>/api/super-admin/tenants/delete', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    Swal.fire('Deleted!', result.message, 'success');
                    loadTenants();
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Failed to delete tenant.', 'error');
            }
        }
    });
}

function impersonateAdmin(subdomain) {
    Swal.fire('Coming Soon', 'Impersonation module is currently being finalized.', 'info');
}

document.addEventListener('DOMContentLoaded', loadTenants);
</script>

<style>
.search-box {
    position: relative;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.search-box i { color: var(--tl); }
.search-box input {
    background: transparent;
    border: none;
    outline: none;
    padding: 10px 0;
    font-size: 13px;
    width: 100%;
}
.modal-root {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    visibility: hidden;
    opacity: 0;
    transition: 0.3s;
}
.modal-root.active { visibility: visible; opacity: 1; }
.modal-card {
    background: #fff;
    width: 100%;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    overflow: hidden;
    transform: translateY(20px);
    transition: 0.3s;
}
.modal-root.active .modal-card { transform: translateY(0); }
.modal-head { padding: 20px 24px; border-bottom: 1px solid var(--cb); display: flex; justify-content: space-between; align-items: center; }
.modal-head h2 { font-size: 18px; font-weight: 800; margin: 0; color: var(--td); }
.modal-close { background: none; border: none; font-size: 24px; color: var(--tl); cursor: pointer; }
.modal-body { padding: 24px; max-height: 80vh; overflow-y: auto; }
.modal-foot { padding: 16px 24px; border-top: 1px solid var(--cb); display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc; }
</style>
</main>
<?php include 'footer.php'; ?>
