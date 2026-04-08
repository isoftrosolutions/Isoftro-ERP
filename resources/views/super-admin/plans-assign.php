<?php
/**
 * ISOFTRO - Plan Assignment
 * Variables: $tenants, $plans
 */
$tenants = $tenants ?? [];
$plans   = $plans   ?? [];
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Plans</span> <span style="color:#94a3b8;"> / Plan Assignment</span></div>
        <h1>Plan Assignment</h1>
    </div>
    <div class="toolbar-right">
        <div class="search-box"><i class="fas fa-search"></i>
            <input type="text" class="search-inp" placeholder="Search institute..." onkeyup="filterAssign(this.value)">
        </div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-user-check"></i> Assign Plans to Institutes</span>
        <span style="font-size:12px;color:var(--text-light);"><?= count($tenants) ?> institutes</span>
    </div>
    <div class="tbl-wrap mt-15">
        <table id="assignTable">
            <thead>
                <tr>
                    <th>Institute</th>
                    <th>Current Plan</th>
                    <th>Status</th>
                    <th>Change Plan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($t['name']) ?></div>
                        <div style="font-size:11px;color:var(--text-light);"><?= htmlspecialchars($t['subdomain']) ?>.isoftroerp.com</div>
                    </td>
                    <td>
                        <span class="pill pp"><?= htmlspecialchars($t['plan_name'] ?? strtoupper($t['plan'] ?? 'N/A')) ?></span>
                    </td>
                    <td>
                        <?php $s = $t['status'] ?? 'active'; ?>
                        <span class="pill" style="background:<?= $s==='active'?'#dcfce7':($s==='trial'?'#fef9c3':'#fee2e2') ?>;color:<?= $s==='active'?'#166534':($s==='trial'?'#854d0e':'#991b1b') ?>;">
                            <?= strtoupper($s) ?>
                        </span>
                    </td>
                    <td>
                        <select class="filter-sel" id="plan_<?= $t['id'] ?>" style="min-width:150px;">
                            <?php foreach ($plans as $p): ?>
                            <option value="<?= htmlspecialchars($p['slug']) ?>" <?= ($p['slug'] === $t['plan']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> — Rs.<?= number_format($p['price_monthly']) ?>/mo
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button class="btn bt sm" onclick="assignPlan(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>')">
                            <i class="fas fa-save"></i> Apply
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterAssign(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#assignTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

async function assignPlan(tenantId, name) {
    const plan = document.getElementById('plan_' + tenantId).value;
    const result = await SuperAdmin.confirmAction('Update plan for ' + name + '?', 'Plan will be changed to: ' + plan, 'Yes, Update');
    if (!result.isConfirmed) return;
    try {
        const res = await SuperAdmin.fetchAPI('/api/super-admin/tenants/update-plan', {
            method: 'POST',
            body: JSON.stringify({ tenant_id: tenantId, plan: plan }),
            headers: { 'Content-Type': 'application/json' }
        });
        if (res.success) {
            SuperAdmin.showNotification('Plan updated for ' + name, 'success');
        }
    } catch (e) { /* handled */ }
}
</script>
