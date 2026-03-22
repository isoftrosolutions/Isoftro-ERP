<?php
/**
 * ISOFTRO - Super Admin Dashboard (Institutes)
 * Refactored Premium Responsive View
 */

$PDO = getDBConnection();

// Fetch institutes with stats
try {
    $stmt = $PDO->query("
        SELECT t.*, 
               (SELECT COUNT(*) FROM students s WHERE s.tenant_id = t.id) as student_count
        FROM tenants t
        ORDER BY t.created_at DESC
    ");
    $tenants = $stmt->fetchAll();
} catch (Exception $e) {
    $tenants = [];
}

// Calculate summary stats
$totalInst = count($tenants);
$activeInst = count(array_filter($tenants, fn($t) => $t['status'] === 'active'));
$trialInst = count(array_filter($tenants, fn($t) => $t['status'] === 'trial'));
$suspendedInst = count(array_filter($tenants, fn($t) => $t['status'] === 'suspended'));
?>

<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Institutes</span></div>
        <h1>Institute Management</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bt" onclick="goNav('add-tenant')">
            <i class="fas fa-plus"></i> Add New Institute
        </button>
    </div>
</div>

<!-- PREMIUM STATS HEADER -->
<div class="premium-stats-grid">
    <div class="premium-stat-card">
        <div class="stat-icon-box ic-green"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <div class="stat-label">TOTAL INSTITUTES</div>
            <div class="stat-value"><?= $totalInst ?></div>
        </div>
    </div>
    <div class="premium-stat-card">
        <div class="stat-icon-box ic-blue"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-label">ACTIVE TENANTS</div>
            <div class="stat-value"><?= $activeInst ?></div>
        </div>
    </div>
    <div class="premium-stat-card">
        <div class="stat-icon-box ic-amber"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-label">TRIAL PERIOD</div>
            <div class="stat-value"><?= $trialInst ?></div>
        </div>
    </div>
    <div class="premium-stat-card">
        <div class="stat-icon-box ic-red"><i class="fas fa-ban"></i></div>
        <div class="stat-info">
            <div class="stat-label">SUSPENDED</div>
            <div class="stat-value"><?= $suspendedInst ?></div>
        </div>
    </div>
</div>

<!-- PREMIUM FILTER BAR -->
<div class="premium-filter-bar">
    <div class="premium-search">
        <i class="fas fa-search"></i>
        <input type="text" id="tenantSearch" placeholder="Search by name, subdomain or email..." onkeyup="filterTenants()">
    </div>
    <select class="filter-sel" id="planFilter" onchange="filterTenants()">
        <option value="">All Plans</option>
        <option value="starter">Starter</option>
        <option value="growth">Growth</option>
        <option value="professional">Professional</option>
        <option value="enterprise">Enterprise</option>
    </select>
    <select class="filter-sel" id="statusFilter" onchange="filterTenants()">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="suspended">Suspended</option>
        <option value="trial">Trial</option>
    </select>
    <button class="btn bs" onclick="exportTenants()">
        <i class="fas fa-download"></i> Export
    </button>
</div>

<div class="tbl-wrap" style="border:none; background:transparent;">
    <table id="tenantTable" class="premium-student-table" style="box-shadow: var(--shadow);">
        <thead>
            <tr>
                <th>Institute Info</th>
                <th>Plan Details</th>
                <th>Usage</th>
                <th>Status</th>
                <th>Joined</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody id="tenantTableBody">
            <?php foreach ($tenants as $tenant): ?>
            <tr data-plan="<?= strtolower($tenant['plan']) ?>" data-status="<?= strtolower($tenant['status']) ?>">
                <td data-label="Institute">
                    <div class="inst-card">
                        <div class="inst-icon"><?= strtoupper(substr($tenant['name'], 0, 1)) ?></div>
                        <div class="inst-info">
                            <span class="name"><?= htmlspecialchars($tenant['name']) ?></span>
                            <span class="sub"><?= htmlspecialchars($tenant['subdomain']) ?>.isoftro.com</span>
                        </div>
                    </div>
                </td>
                <td data-label="Plan">
                    <span class="plan-badge plan-<?= strtolower($tenant['plan']) ?>">
                        <?= ucfirst($tenant['plan']) ?>
                    </span>
                </td>
                <td data-label="Usage">
                    <div style="font-weight:700;"><?= number_format($tenant['student_count']) ?> Students</div>
                    <div style="font-size:10px; color:var(--text-light);">Limit: <?= $tenant['student_limit'] ?></div>
                </td>
                <td data-label="Status">
                    <span class="pill <?= $tenant['status'] === 'active' ? 'pg' : ($tenant['status'] === 'trial' ? 'py' : 'pr') ?>">
                        <?= ucfirst($tenant['status']) ?>
                    </span>
                </td>
                <td data-label="Joined">
                    <?= date('M d, Y', strtotime($tenant['created_at'])) ?>
                </td>
                <td style="text-align:right;" data-label="Actions">
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button class="btn-icon-p" onclick="viewTenant(<?= $tenant['id'] ?>)" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon-p" onclick="goNav('edit-tenant', {id: <?= $tenant['id'] ?>})" title="Settings">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn-icon-p" style="color:var(--purple);" onclick="impersonateTenant(<?= $tenant['id'] ?>)" title="Impersonate">
                            <i class="fas fa-user-secret"></i>
                        </button>
                        <button class="btn-icon-p" style="color:var(--red);" onclick="suspendTenant(<?= $tenant['id'] ?>)" title="Suspend">
                            <i class="fas fa-ban"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function filterTenants() {
        const search = document.getElementById('tenantSearch').value.toLowerCase();
        const plan = document.getElementById('planFilter').value;
        const status = document.getElementById('statusFilter').value;

        document.querySelectorAll('#tenantTableBody tr').forEach(tr => {
            const text = tr.innerText.toLowerCase();
            const trPlan = tr.dataset.plan;
            const trStatus = tr.dataset.status;

            const matchesSearch = text.includes(search);
            const matchesPlan = !plan || trPlan === plan;
            const matchesStatus = !status || trStatus === status;

            tr.style.display = (matchesSearch && matchesPlan && matchesStatus) ? '' : 'none';
        });
    }

    function viewTenant(id) {
        goNav('view-tenant', {id: id});
    }

    async function impersonateTenant(id) {
        const confirm = await SuperAdmin.confirmAction(
            "Impersonate Institute?",
            "You will be logged in as the primary admin of this institute.",
            "Yes, Impersonate"
        );

        if (!confirm.isConfirmed) return;

        try {
            Swal.showLoading();
            const res = await SuperAdmin.fetchAPI(`/api/super-admin/impersonate/${id}`, 'POST');

            if (res.success && res.token) {
                window.open(`${window.APP_URL}/impersonate-login?token=${res.token}`, '_blank');
                Swal.close();
            } else {
                throw new Error(res.message || 'Impersonation failed');
            }
        } catch (e) {
            Swal.fire('Error', e.message || 'Something went wrong', 'error');
        }
    }

    function exportTenants() {
        SuperAdmin.showNotification("Preparing CSV export...", "info");
    }
</script>
