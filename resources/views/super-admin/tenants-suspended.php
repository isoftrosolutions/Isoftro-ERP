<?php
/**
 * ISOFTRO - Suspended Institutes
 */
$tenants = $tenants ?? [];
$count = count($tenants);
?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i> <span>Institutes</span> <span style="color:#94a3b8;"> / Suspended</span></div>
        <h1>Suspended Institutes</h1>
    </div>
    <div class="toolbar-right">
        <button class="btn bs" onclick="goNav('tenants')"><i class="fas fa-arrow-left"></i> All Institutes</button>
    </div>
</div>

<div class="g3 mt-20">
    <div class="card p-20" style="border-left:4px solid #ef4444;">
        <div style="font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;">Suspended</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= $count ?></div>
        <div style="font-size:12px;color:var(--text-light);">Requires action</div>
    </div>
    <div class="card p-20" style="border-left:4px solid #f59e0b;">
        <div style="font-size:11px;font-weight:700;color:#f59e0b;text-transform:uppercase;">Students Affected</div>
        <div style="font-size:32px;font-weight:800;color:var(--text-dark);margin:6px 0;"><?= array_sum(array_column($tenants, 'student_count')) ?></div>
        <div style="font-size:12px;color:var(--text-light);">Across suspended institutes</div>
    </div>
</div>

<div class="card mt-20">
    <div class="ct" style="justify-content:space-between;">
        <span><i class="fas fa-ban"></i> Suspended Institutes</span>
        <div class="search-box"><i class="fas fa-search"></i> <input type="text" class="search-inp" placeholder="Search..." onkeyup="filterSuspended(this.value)"></div>
    </div>
    <?php if (empty($tenants)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-light);">
        <i class="fas fa-check-circle" style="font-size:40px;opacity:.3;display:block;margin-bottom:15px;color:#22c55e;"></i>
        <p>No suspended institutes. All systems running normally.</p>
    </div>
    <?php else: ?>
    <div class="tbl-wrap mt-15">
        <table id="suspendedTable">
            <thead>
                <tr>
                    <th>Institute</th>
                    <th>Subdomain</th>
                    <th>Plan</th>
                    <th>Students</th>
                    <th>Suspended On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:var(--text-dark);"><?= htmlspecialchars($t['name']) ?></div>
                        <div style="font-size:11px;color:var(--text-light);"><?= htmlspecialchars($t['email'] ?? '') ?></div>
                    </td>
                    <td><code style="font-size:12px;"><?= htmlspecialchars($t['subdomain']) ?>.isoftroerp.com</code></td>
                    <td><span class="pill pp"><?= strtoupper($t['plan'] ?? 'N/A') ?></span></td>
                    <td><?= number_format($t['student_count'] ?? 0) ?></td>
                    <td style="font-size:12px;color:var(--text-light);"><?= date('M d, Y', strtotime($t['updated_at'] ?? $t['created_at'])) ?></td>
                    <td style="display:flex;gap:6px;">
                        <button class="btn bt sm" onclick="activateTenant(<?= $t['id'] ?>, '<?= htmlspecialchars($t['name']) ?>')">
                            <i class="fas fa-check"></i> Activate
                        </button>
                        <button class="btn bs sm" onclick="goNav('view-tenant', {id: <?= $t['id'] ?>})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
function filterSuspended(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#suspendedTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

async function activateTenant(id, name) {
    const result = await SuperAdmin.confirmAction('Activate ' + name + '?', 'This will restore full access for this institute.', 'Yes, Activate');
    if (!result.isConfirmed) return;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window._CSRF_TOKEN || '';
        const res = await fetch((window.APP_URL || '') + '/api/superadmin/TenantsApi.php?action=activate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            credentials: 'include',
            body: JSON.stringify({ id: id })
        });
        const data = await res.json();
        if (data.success) {
            SuperAdmin.showNotification(data.message, 'success');
            setTimeout(() => goNav('tenants-suspended'), 1200);
        } else {
            SuperAdmin.showNotification(data.message || 'Failed to activate.', 'error');
        }
    } catch (e) {
        SuperAdmin.showNotification('Network error.', 'error');
    }
}
</script>
