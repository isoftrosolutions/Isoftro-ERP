<?php
/**
 * Hamro ERP — Plan Assignment Page
 * Refactored to match Super Admin layout and design system.
 */

require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pdo = getDBConnection();

// Fetch dynamic plans from subscription_plans
$dbPlans = $pdo->query("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY sort_order ASC")->fetchAll();
$plansByKey = [];
foreach($dbPlans as $p) {
    $plansByKey[$p['slug']] = $p;
}

// Fetch summary counts for plans from tenants
$planCountsRaw = $pdo->query("SELECT plan, COUNT(*) as count FROM tenants GROUP BY plan")->fetchAll(PDO::FETCH_KEY_PAIR);
$planCounts = [
    'starter' => $planCountsRaw['starter'] ?? 0,
    'growth' => $planCountsRaw['growth'] ?? 0,
    'professional' => ($planCountsRaw['professional'] ?? 0) + ($planCountsRaw['pro'] ?? 0),
    'enterprise' => $planCountsRaw['enterprise'] ?? 0
];

// Fetch all institutes for the table
$tenants = $pdo->query("SELECT * FROM tenants ORDER BY name ASC")->fetchAll();

$pageTitle = 'Plan Assignment';
$activePage = 'plan-assign.php';
?>

<?php renderSuperAdminHeader(); renderSidebar($activePage); ?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-t" style="background:var(--soft-purple); color:var(--purple);"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <div class="pg-title">Plan Assignment</div>
                    <div class="pg-sub">Assign or change subscription plans for registered institutes.</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="location.reload()"><i class="fa-solid fa-refresh"></i> Refresh</button>
                <button class="btn bt" onclick="openBulkModal()"><i class="fa-solid fa-bolt"></i> Bulk Assign</button>
            </div>
        </div>

        <!-- Stats Row (Dynamic) -->
        <div class="sg" style="margin-bottom:24px;">
            <?php foreach($dbPlans as $p): 
                $color = $p['css_class'] === 'starter' ? '#16a34a' : ($p['css_class'] === 'growth' ? '#3b82f6' : '#8b5cf6');
                $bg = $p['css_class'] === 'starter' ? '#f0fdf4' : ($p['css_class'] === 'growth' ? '#eff6ff' : 'var(--soft-purple)');
                $count = $planCounts[$p['slug']] ?? 0;
            ?>
            <div class="sc">
                <div class="sc-top">
                    <div class="sc-ico" style="background:<?php echo $bg; ?>; color:<?php echo $color; ?>;"><?php echo $p['icon_emoji'] ?: '📦'; ?></div>
                    <span class="tag" style="background:<?php echo $bg; ?>; color:<?php echo $color; ?>; font-weight:700;"><?php echo htmlspecialchars($p['name']); ?></span>
                </div>
                <div class="sc-val"><?php echo $count; ?></div>
                <div class="sc-lbl">Active Institutes</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="tbl-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <div class="ct"><i class="fa-solid fa-building"></i> Plan Assignments</div>
                <div style="display:flex; gap:10px;">
                    <input type="text" class="form-inp" placeholder="Search institute..." style="width:220px;" oninput="filterTable(this.value)">
                    <select class="form-inp" style="width:160px; appearance:auto;" onchange="filterPlan(this.value)">
                        <option value="">All Plans</option>
                        <?php foreach($dbPlans as $p): ?>
                        <option value="<?php echo $p['slug']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="tw" style="border:none; border-radius:0;">
                <table id="planTable">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" onchange="selectAll(this)"></th>
                            <th>Institute</th>
                            <th>Current Plan</th>
                            <th>Students</th>
                            <th>Renewal Date</th>
                            <th>Status</th>
                            <th>Quick Assign</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="planTbody">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Bulk Assign Drawer (Modern Sidebar Style) -->
<div class="sb-overlay" id="bulkOverlay" onclick="closeBulkModal()"></div>
<div id="bulkDrawer" style="position:fixed; top:0; right:-400px; width:400px; height:100vh; background:#fff; z-index:1100; box-shadow:-10px 0 30px rgba(0,0,0,0.1); transition:0.3s cubic-bezier(0.4, 0, 0.2, 1); display:flex; flex-direction:column;">
    <div style="padding:20px; border-bottom:1px solid var(--cb); display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:36px; height:36px; background:var(--sa-primary-lt); color:var(--sa-primary); border-radius:10px; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-bolt"></i></div>
            <div>
                <div style="font-weight:700; font-size:15px;">Bulk Assignment</div>
                <div style="font-size:11px; color:var(--tl);">Update multiple institutes at once</div>
            </div>
        </div>
        <button class="btn-icon" onclick="closeBulkModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div style="flex:1; overflow-y:auto; padding:20px;">
        <div class="form-row" style="margin-bottom:20px;">
            <label class="form-lbl">Target Group</label>
            <select class="form-inp" style="appearance:auto;">
                <option>All Active Institutes</option>
                <option>Starter Plan Institutes</option>
                <option>Trial Institutes</option>
            </select>
        </div>
        <div class="form-row" style="margin-bottom:20px;">
            <label class="form-lbl">Apply New Plan</label>
            <select class="form-inp" style="appearance:auto;">
                <?php foreach($dbPlans as $p): ?>
                <option value="<?php echo $p['slug']; ?>"><?php echo ($p['icon_emoji'] ?: '📦') . ' ' . htmlspecialchars($p['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row" style="margin-bottom:20px;">
            <label class="form-lbl">Effective Date</label>
            <input type="date" class="form-inp">
        </div>
        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:15px; margin-top:10px; display:flex; gap:10px;">
            <i class="fa-solid fa-triangle-exclamation" style="color:#d97706; margin-top:2px;"></i>
            <div style="font-size:12px; color:#92400e; line-height:1.4;">
                <strong>Warning:</strong> This will override existing plan assignments. Active billing will be prorated.
            </div>
        </div>
    </div>
    <div style="padding:20px; border-top:1px solid var(--cb); display:flex; gap:12px;">
        <button class="btn bs" style="flex:1;" onclick="closeBulkModal()">Cancel</button>
        <button class="btn bt" style="flex:1;" onclick="applyBulk()">Apply Change</button>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%) scale(0.9); background:#fff; border-radius:16px; padding:24px; width:90%; max-width:400px; z-index:1200; opacity:0; visibility:hidden; transition:0.2s; box-shadow:0 20px 60px rgba(0,0,0,0.15);">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <h3 style="font-size:16px; font-weight:800;">Change Plan</h3>
        <button class="btn-icon" onclick="closeAssignModal()"><i class="fa fa-times"></i></button>
    </div>
    <div id="modalInstName" style="font-size:13px; color:var(--text-body); margin-bottom:20px; padding:12px; background:var(--bg); border-radius:10px;"></div>
    <div class="form-row" style="margin-bottom:20px;">
        <label class="form-lbl">Select New Plan</label>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;" id="planCards">
            <?php foreach($dbPlans as $p): 
                $color = $p['css_class'] === 'starter' ? '#16a34a' : ($p['css_class'] === 'growth' ? '#3b82f6' : '#8b5cf6');
            ?>
            <div class="plan-card" data-plan="<?php echo $p['slug']; ?>" onclick="selectPlan(this)">
                <div style="font-size:18px;"><?php echo $p['icon_emoji'] ?: '📦'; ?></div>
                <div style="font-weight:700; color:<?php echo $color; ?>;"><?php echo htmlspecialchars($p['name']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn bs" style="flex:1" onclick="closeAssignModal()">Cancel</button>
        <button class="btn bt" style="flex:1" onclick="saveIndividualPlan()">Save Plan</button>
    </div>
</div>

<style>
    .plan-card {
        border: 2px solid var(--cb);
        border-radius: 12px;
        padding: 12px;
        cursor: pointer;
        transition: 0.2s;
        text-align: center;
    }
    .plan-card:hover { border-color: var(--sa-primary-h); background: var(--bg); }
    .plan-card.active { border-color: var(--sa-primary); background: var(--sa-primary-lt); }

    .plan-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }
    .plan-starter { background: #f0fdf4; color: #16a34a; }
    .plan-growth { background: #eff6ff; color: #3b82f6; }
    .plan-professional { background: var(--soft-purple); color: var(--purple); }
    .plan-enterprise { background: #fffbeb; color: #d97706; }

    .tag-active { background: #dcfce7; color: #16a34a; }
    .tag-trial { background: #eff6ff; color: #3b82f6; }
    .tag-suspended { background: #fee2e2; color: var(--red); }
</style>

<script>
const institutes = <?php echo json_encode($tenants); ?>;

const planMeta = {
    <?php foreach($dbPlans as $p): 
        $cls = 'plan-' . ($p['css_class'] ?: 'starter');
    ?>
    "<?php echo $p['slug']; ?>": { label: "<?php echo ($p['icon_emoji'] ?: '📦') . ' ' . htmlspecialchars($p['name']); ?>", cls: "<?php echo $cls; ?>" },
    <?php endforeach; ?>
    "pro": { label: "⭐ Professional", cls: "plan-professional" } // Legacy mapping
};

function renderTable(data) {
    const tbody = document.getElementById('planTbody');
    tbody.innerHTML = data.map(inst => `
        <tr style="border-bottom:1px solid var(--cb);">
            <td style="padding:14px 16px;"><input type="checkbox" class="row-cb" data-id="${inst.id}"></td>
            <td style="padding:14px 16px;">
                <div style="font-weight:700; font-size:13px; color:var(--td);">${inst.name}</div>
                <div style="font-size:11px; color:var(--tl);">${inst.subdomain}.hamrolabs.com.np</div>
            </td>
            <td style="padding:14px 16px;">
                <span class="plan-badge ${planMeta[inst.plan]?.cls || 'plan-starter'}">${planMeta[inst.plan]?.label || inst.plan}</span>
            </td>
            <td style="padding:14px 16px; font-weight:700; font-size:13px; color:var(--td);">${(inst.student_limit || 0).toLocaleString()}</td>
            <td style="padding:14px 16px; font-size:12px; color:var(--tl);">${inst.next_renewal || 'N/A'}</td>
            <td style="padding:14px 16px;">
                <span class="tag ${inst.status === 'active' ? 'tag-active' : 'tag-suspended'}">${inst.status || 'inactive'}</span>
            </td>
            <td style="padding:14px 16px;">
                <div style="display:flex; gap:6px;">
                    <button class="btn-icon" title="Starter" onclick="quickAssign(${inst.id}, 'starter')"><i class="fa-solid fa-seedling" style="color:#16a34a;"></i></button>
                    <button class="btn-icon" title="Growth" onclick="quickAssign(${inst.id}, 'growth')"><i class="fa-solid fa-rocket" style="color:#3b82f6;"></i></button>
                    <button class="btn-icon" title="Pro" onclick="quickAssign(${inst.id}, 'professional')"><i class="fa-solid fa-star" style="color:var(--purple);"></i></button>
                </div>
            </td>
            <td style="padding:14px 16px; text-align:center;">
                <button class="btn bs" style="height:32px; padding:0 12px; font-size:11px;" onclick="openAssignModal(${inst.id})">Update Plan</button>
            </td>
        </tr>
    `).join('');
}

let currentInstId = null;
let currentSelectedPlan = null;

function filterTable(val) {
    const filtered = institutes.filter(i => i.name.toLowerCase().includes(val.toLowerCase()));
    renderTable(filtered);
}

function filterPlan(val) {
    const filtered = val ? institutes.filter(i => i.plan === val) : institutes;
    renderTable(filtered);
}

function selectAll(cb) {
    document.querySelectorAll('.row-cb').forEach(c => c.checked = cb.checked);
}

function openBulkModal() {
    document.getElementById('bulkOverlay').style.display = 'block';
    setTimeout(() => { document.getElementById('bulkDrawer').style.right = '0'; }, 10);
}

function closeBulkModal() {
    document.getElementById('bulkDrawer').style.right = '-400px';
    setTimeout(() => { document.getElementById('bulkOverlay').style.display = 'none'; }, 300);
}

function openAssignModal(id) {
    const inst = institutes.find(i => i.id == id);
    currentInstId = id;
    document.getElementById('modalInstName').innerHTML = `Updating plan for <strong>${inst.name}</strong>`;
    
    document.querySelectorAll('.plan-card').forEach(c => {
        c.classList.remove('active');
        if(c.dataset.plan === inst.plan) {
            c.classList.add('active');
            currentSelectedPlan = inst.plan;
        }
    });

    document.getElementById('assignModal').style.visibility = 'visible';
    document.getElementById('assignModal').style.opacity = '1';
    document.getElementById('assignModal').style.transform = 'translate(-50%,-50%) scale(1)';
}

function closeAssignModal() {
    document.getElementById('assignModal').style.visibility = 'hidden';
    document.getElementById('assignModal').style.opacity = '0';
    document.getElementById('assignModal').style.transform = 'translate(-50%,-50%) scale(0.9)';
}

function selectPlan(el) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    currentSelectedPlan = el.dataset.plan;
}

function saveIndividualPlan() {
    if(!currentSelectedPlan) return SuperAdmin.showNotification('Please select a plan', 'error');
    quickAssign(currentInstId, currentSelectedPlan);
    closeAssignModal();
}

function quickAssign(id, plan) {
    SuperAdmin.showNotification('Updating plan...', 'info');
    fetch('../../api/super-admin/tenants/update-plan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `tenant_id=${id}&plan=${plan}&csrf_token=${window.CSRF_TOKEN}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            SuperAdmin.showNotification('Plan updated successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            SuperAdmin.showNotification(data.message || 'Error updating plan', 'error');
        }
    });
}

function applyBulk() {
    SuperAdmin.showNotification('This feature is still being wired up with the backend.', 'warning');
    closeBulkModal();
}

document.addEventListener('DOMContentLoaded', () => {
    renderTable(institutes);
});
</script>

<?php include 'footer.php'; ?>
