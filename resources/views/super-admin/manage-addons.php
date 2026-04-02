<?php
/**
 * iSoftro ERP — Super Admin Add-on Management
 * Manage premium features, pricing, and tenant assignments
 */

require_once __DIR__ . '/../../../config/config.php';
requirePermission('dashboard.view');

$user = getCurrentUser();
if (!$user || !in_array($user['role'], ['superadmin', 'super-admin'])) {
    header('Location: ' . APP_URL);
    exit;
}

$pageTitle = "Add-on Features Management";
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="h3">Add-on Features Management</h1>
        <p class="text-muted">Manage premium features, pricing, and tenant assignments</p>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="addons-tab" data-bs-toggle="tab" data-bs-target="#addons" type="button">All Add-ons</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button">Assign to Tenant</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button">Pricing & Plans</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: ALL ADD-ONS -->
        <div class="tab-pane fade show active" id="addons" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Available Add-ons</h5>
                <button class="btn btn-success btn-sm" onclick="showCreateAddonModal()">
                    <i class="fas fa-plus"></i> Create Add-on
                </button>
            </div>

            <div id="addonsList" class="row"></div>
        </div>

        <!-- TAB 2: ASSIGN TO TENANT -->
        <div class="tab-pane fade" id="assign" role="tabpanel">
            <h5 class="mb-4">Assign Add-ons to Tenant</h5>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Select Tenant:</label>
                    <select id="tenantSelect" class="form-select" onchange="loadTenantAddons()">
                        <option value="">-- Loading Tenants --</option>
                    </select>
                </div>
            </div>

            <div id="tenantAddonContainer" style="display:none;">
                <div class="alert alert-info">
                    <h6 id="tenantInfo"></h6>
                    <p id="tenantPlan" class="mb-0"></p>
                </div>

                <h6 class="mb-3">Current Add-ons (<span id="activeAddonCount">0</span> active)</h6>
                <div id="currentAddonsContainer" class="row mb-4"></div>

                <hr>

                <h6 class="mb-3">Add New Add-ons</h6>
                <div id="availableAddonsForTenant" class="row mb-4"></div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="saveAddonAssignments()">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button class="btn btn-secondary" onclick="resetAddonSelection()">Reset</button>
                </div>
            </div>
        </div>

        <!-- TAB 3: PRICING & PLANS -->
        <div class="tab-pane fade" id="pricing" role="tabpanel">
            <h5 class="mb-4">Add-on Pricing Overview</h5>

            <div class="mb-3">
                <label class="form-label">Billing Cycle:</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="billingCycle" id="monthly" value="monthly" checked onchange="loadPricingTable()">
                        <label class="form-check-label" for="monthly">Monthly</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="billingCycle" id="annual" value="annual" onchange="loadPricingTable()">
                        <label class="form-check-label" for="annual">Annual (with savings)</label>
                    </div>
                </div>
            </div>

            <table class="table table-bordered" id="pricingTable">
                <thead class="table-light">
                    <tr>
                        <th>Category</th>
                        <th>Add-on Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pricingTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Add-on Modal -->
<div class="modal fade" id="addonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addonModalTitle">Create Add-on</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addonForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Add-on Key:</label>
                            <input type="text" id="addonKey" class="form-control" placeholder="e.g., advanced-analytics" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Add-on Name:</label>
                            <input type="text" id="addonName" class="form-control" placeholder="e.g., Advanced Analytics" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Description:</label>
                        <textarea id="addonDesc" class="form-control" rows="2" placeholder="Describe this add-on..."></textarea>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-label">Monthly Price ($):</label>
                            <input type="number" id="addonMonthly" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Annual Price ($):</label>
                            <input type="number" id="addonAnnual" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category:</label>
                            <select id="addonCategory" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <option value="analytics">Analytics</option>
                                <option value="integrations">Integrations</option>
                                <option value="communications">Communications</option>
                                <option value="automation">Automation</option>
                                <option value="compliance">Compliance</option>
                                <option value="support">Support</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Status:</label>
                            <select id="addonStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="beta">Beta</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check mt-4">
                                <input type="checkbox" id="addonApproval" class="form-check-input">
                                Requires Manual Approval
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveAddon()">Save Add-on</button>
            </div>
        </div>
    </div>
</div>

<script>
    const APP_URL = '<?= APP_URL ?>';

    // Helper to make authenticated API calls
    async function apiCall(url, options = {}) {
        const token = sessionStorage.getItem('access_token');
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        };

        const response = await fetch(APP_URL + url, {
            ...options,
            headers
        });

        if (response.status === 401) {
            window.location.href = APP_URL + '/auth/login?expired=1';
            return null;
        }

        return response.json();
    }

    // Load all add-ons
    async function loadAddons() {
        const data = await apiCall('/api/super/addons');
        if (!data?.success) {
            alert('Failed to load add-ons');
            return;
        }

        let html = '';
        for (const [category, addons] of Object.entries(data.addons || {})) {
            html += `
                <div class="col-12 mb-4">
                    <h6 class="text-uppercase text-muted mb-3">${category}</h6>
                    <div class="row">
            `;
            addons.forEach(addon => {
                const statusBadge = `
                    <span class="badge bg-${addon.status === 'active' ? 'success' : addon.status === 'beta' ? 'warning' : 'secondary'}">
                        ${addon.status.toUpperCase()}
                    </span>
                `;
                html += `
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">${addon.addon_name}</h6>
                                <p class="card-text small text-muted">${addon.description || 'No description'}</p>
                                <div class="pricing my-3">
                                    <strong class="text-primary">$${parseFloat(addon.monthly_price).toFixed(2)}/mo</strong>
                                    ${addon.annual_price ? `<br><small class="text-success">$${parseFloat(addon.annual_price).toFixed(2)}/yr</small>` : ''}
                                </div>
                                <div>${statusBadge}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += `</div></div>`;
        }

        document.getElementById('addonsList').innerHTML = html;
    }

    // Load tenants for assignment
    async function loadTenants() {
        const data = await apiCall('/api/super/tenants');
        if (!data?.success || !data.tenants) return;

        const select = document.getElementById('tenantSelect');
        select.innerHTML = '<option value="">-- Select Tenant --</option>';
        data.tenants.forEach(tenant => {
            const option = document.createElement('option');
            option.value = tenant.id;
            option.textContent = `${tenant.name} (${tenant.plan || 'free'})`;
            select.appendChild(option);
        });
    }

    // Load tenant's current and available add-ons
    async function loadTenantAddons() {
        const tenantId = document.getElementById('tenantSelect').value;
        if (!tenantId) return;

        const data = await apiCall(`/api/super/tenants/${tenantId}/addons`);
        if (!data?.success) {
            alert('Failed to load tenant add-ons');
            return;
        }

        document.getElementById('tenantInfo').textContent = `Tenant: ${data.tenant_id}`;
        document.getElementById('tenantPlan').textContent = `Plan: ${data.plan || 'free'}`;
        document.getElementById('activeAddonCount').textContent = data.active_count || 0;

        // Show current add-ons
        let currentHtml = '';
        if (data.addons && data.addons.length > 0) {
            data.addons.forEach(addon => {
                const statusBadge = `<span class="badge bg-${addon.status === 'active' ? 'success' : 'secondary'}">${addon.status}</span>`;
                currentHtml += `
                    <div class="col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h6>${addon.addon_name}</h6>
                                    ${statusBadge}
                                </div>
                                <small class="text-muted">$${addon.monthly_price}/mo (${addon.billing_cycle})</small>
                                ${addon.expires_at ? `<br><small class="text-danger">Expires: ${new Date(addon.expires_at).toLocaleDateString()}</small>` : ''}
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-danger" onclick="confirmRemoveAddon(${tenantId}, ${addon.id})">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            currentHtml = '<div class="col-12"><p class="text-muted">No add-ons assigned yet.</p></div>';
        }

        document.getElementById('currentAddonsContainer').innerHTML = currentHtml;

        // Load available add-ons
        const availableData = await apiCall(`/api/super/addons/pricing?tenant_id=${tenantId}`);
        if (availableData?.success) {
            let availableHtml = '';
            for (const [category, addons] of Object.entries(availableData.addons || {})) {
                availableHtml += `<div class="col-12 mb-3"><h6 class="text-uppercase text-muted">${category}</h6></div>`;
                addons.forEach(addon => {
                    if (!addon.is_assigned) {
                        availableHtml += `
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input addon-checkbox" type="checkbox" value="${addon.id}" id="addon_${addon.id}">
                                            <label class="form-check-label" for="addon_${addon.id}">
                                                <strong>${addon.addon_name}</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-1">$${addon.pricing}/mo${addon.savings ? ` (Save ${addon.savings}% annually)` : ''}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                });
            }
            document.getElementById('availableAddonsForTenant').innerHTML = availableHtml;
        }

        document.getElementById('tenantAddonContainer').style.display = 'block';
    }

    // Save selected add-ons for tenant
    async function saveAddonAssignments() {
        const tenantId = document.getElementById('tenantSelect').value;
        const selected = Array.from(document.querySelectorAll('.addon-checkbox:checked')).map(el => ({
            addon_id: parseInt(el.value),
            billing_cycle: 'monthly'
        }));

        if (selected.length === 0) {
            alert('Please select at least one add-on');
            return;
        }

        const data = await apiCall(`/api/super/tenants/${tenantId}/addons/batch`, {
            method: 'POST',
            body: JSON.stringify({ addons: selected })
        });

        if (data?.success) {
            alert('Add-ons assigned successfully');
            loadTenantAddons();
        } else {
            alert('Error: ' + (data?.message || 'Unknown error'));
        }
    }

    // Remove add-on
    async function confirmRemoveAddon(tenantId, addonId) {
        if (!confirm('Remove this add-on?')) return;

        const data = await apiCall(`/api/super/tenants/${tenantId}/addons/${addonId}`, {
            method: 'DELETE'
        });

        if (data?.success) {
            loadTenantAddons();
        } else {
            alert('Error removing add-on');
        }
    }

    // Load pricing table
    async function loadPricingTable() {
        const billingCycle = document.querySelector('input[name="billingCycle"]:checked').value;
        const data = await apiCall(`/api/super/addons/pricing?billing_cycle=${billingCycle}`);

        if (!data?.success) return;

        let html = '';
        for (const [category, addons] of Object.entries(data.addons || {})) {
            addons.forEach(addon => {
                const statusBadge = `
                    <span class="badge bg-${addon.status === 'active' ? 'success' : addon.status === 'beta' ? 'warning' : 'secondary'}">
                        ${addon.status}
                    </span>
                `;
                html += `
                    <tr>
                        <td><span class="badge bg-light text-dark">${category}</span></td>
                        <td><strong>${addon.addon_name}</strong></td>
                        <td>$${addon.pricing}${addon.savings ? ` <small class="text-success">(Save ${addon.savings}%)</small>` : ''}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="showCreateAddonModal(${addon.id})">Edit</button>
                        </td>
                    </tr>
                `;
            });
        }

        document.getElementById('pricingTableBody').innerHTML = html;
    }

    // Show create add-on modal
    function showCreateAddonModal(addonId = null) {
        document.getElementById('addonModalTitle').textContent = addonId ? 'Edit Add-on' : 'Create New Add-on';
        document.getElementById('addonForm').reset();
        new bootstrap.Modal(document.getElementById('addonModal')).show();
    }

    // Save add-on
    async function saveAddon() {
        const data = {
            addon_key: document.getElementById('addonKey').value,
            addon_name: document.getElementById('addonName').value,
            description: document.getElementById('addonDesc').value,
            monthly_price: parseFloat(document.getElementById('addonMonthly').value),
            annual_price: parseFloat(document.getElementById('addonAnnual').value) || null,
            category: document.getElementById('addonCategory').value,
            status: document.getElementById('addonStatus').value,
            requires_approval: document.getElementById('addonApproval').checked
        };

        const result = await apiCall('/api/super/addons', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (result?.success) {
            alert('Add-on saved successfully');
            bootstrap.Modal.getInstance(document.getElementById('addonModal')).hide();
            loadAddons();
        } else {
            alert('Error: ' + (result?.message || 'Unknown error'));
        }
    }

    // Reset selection
    function resetAddonSelection() {
        document.querySelectorAll('.addon-checkbox').forEach(el => el.checked = false);
    }

    // Load data on page load
    document.addEventListener('DOMContentLoaded', () => {
        loadAddons();
        loadTenants();
        loadPricingTable();
    });
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
