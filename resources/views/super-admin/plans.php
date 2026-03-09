<?php
/**
 * Hamro ERP — Subscription Plans Page
 * Refactored to match Super Admin layout and design system.
 */

require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pdo = getDBConnection();

// Fetch dynamic plans and features
$dbPlans = $pdo->query("SELECT * FROM subscription_plans ORDER BY sort_order ASC")->fetchAll();
$allFeatures = [];
foreach ($dbPlans as $p) {
    $stmt = $pdo->prepare("SELECT * FROM plan_features WHERE plan_id = :pid ORDER BY sort_order ASC");
    $stmt->execute(['pid' => $p['id']]);
    $allFeatures[$p['id']] = $stmt->fetchAll();
}

$pageTitle = 'Subscription Plans';
$activePage = 'plans.php';
?>

<?php renderSuperAdminHeader(); renderSidebar($activePage); ?>

<main class="main" id="mainContent">
    <div class="pg fu">

        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-p"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <div class="pg-title">Subscription Plans</div>
                    <div class="pg-sub">Manage pricing tiers and feature access for all platform plans.</div>
                </div>
            </div>
            <div class="pg-acts">
                <button class="btn bs" onclick="location.reload()"><i class="fa-solid fa-refresh"></i> Refresh</button>
                <button class="btn bt" onclick="openNewPlanModal()"><i class="fa-solid fa-plus"></i> New Plan</button>
            </div>
        </div>

        <!-- Plan Cards Grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:24px; margin-bottom:32px;" id="planGrid">
            <?php foreach($dbPlans as $p): 
                $color = $p['css_class'] === 'starter' ? '#16a34a' : ($p['css_class'] === 'growth' ? '#3b82f6' : '#8b5cf6');
                $features = $allFeatures[$p['id']] ?? [];
            ?>
            <div class="card" style="border:1px solid var(--cb); border-top: 4px solid <?php echo $color; ?>; position:relative; display:flex; flex-direction:column;">
                <?php if($p['is_featured']): ?>
                    <span style="position:absolute; top:-12px; right:20px; background:<?php echo $color; ?>; color:#fff; font-size:10px; font-weight:800; padding:4px 12px; border-radius:20px; text-transform:uppercase; letter-spacing:1px;">Featured</span>
                <?php endif; ?>
                <div style="margin-bottom:20px;">
                    <div style="font-size:12px; font-weight:700; color:var(--tl); text-transform:uppercase; margin-bottom:4px; letter-spacing:1px;">Tier</div>
                    <div style="font-size:20px; font-weight:800; color:<?php echo $color; ?>;"><?php echo htmlspecialchars($p['name']); ?> <?php echo $p['icon_emoji']; ?></div>
                </div>
                <div style="margin-bottom:24px;">
                    <span style="font-size:40px; font-weight:800; color:var(--td);">Rs <?php echo number_format($p['price_monthly'], 0); ?></span>
                    <span style="color:var(--tl); font-weight:600;">/month</span>
                </div>
                <ul style="list-style:none; padding:0; margin:0 0 30px; flex:1;">
                    <?php foreach($features as $f): ?>
                        <li style="padding:8px 0; font-size:13px; color:var(--tb); display:flex; align-items:center; gap:10px;">
                            <i class="fa-solid fa-circle-check" style="color:<?php echo $color; ?>; font-size:14px;"></i>
                            <?php echo htmlspecialchars($f['feature_text']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="btn" onclick="openEditPlanModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" style="width:100%; height:44px; border:1px solid <?php echo $color; ?>; color:<?php echo $color; ?>; font-weight:700; background:transparent; transition:0.2s;">
                    Edit Plan & Features
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Feature Matrix Table (Dynamic) -->
        <div class="card">
            <div class="tbl-head">
                <div class="ct"><i class="fa-solid fa-table-list"></i> Feature Comparison Matrix</div>
            </div>
            <div class="tw" style="border:none; border-radius:0; overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="min-width:240px; text-align:left; padding:16px 20px; background:var(--bg-light); border-bottom:2px solid var(--cb);">Platform Feature</th>
                            <?php foreach($dbPlans as $p): ?>
                                <th style="text-align:center; padding:16px 20px; background:var(--bg-light); border-bottom:2px solid var(--cb);"><?php echo htmlspecialchars($p['name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Collect all unique feature texts for rows
                        $uniqueFeatures = [];
                        foreach($allFeatures as $pid => $features) {
                            foreach($features as $f) {
                                if(!in_array($f['feature_text'], $uniqueFeatures)) {
                                    $uniqueFeatures[] = $f['feature_text'];
                                }
                            }
                        }
                        // Sort features for readability
                        sort($uniqueFeatures);

                        foreach($uniqueFeatures as $fName): ?>
                            <tr style="border-bottom:1px solid var(--cb);">
                                <td style="padding:14px 20px; font-size:13px; font-weight:700; color:var(--td); border-right:1px solid var(--cb);"><?php echo htmlspecialchars($fName); ?></td>
                                <?php foreach($dbPlans as $p): 
                                    $hasFeature = false;
                                    foreach($allFeatures[$p['id']] ?? [] as $pf) {
                                        if($pf['feature_text'] === $fName && $pf['is_included']) {
                                            $hasFeature = true;
                                            break;
                                        }
                                    }
                                ?>
                                    <td style="padding:14px 20px; text-align:center; font-size:13px; border-right:1px solid var(--cb);">
                                        <?php if($hasFeature): ?>
                                            <i class="fa-solid fa-check" style="color:#16a34a; font-size:16px;"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-minus" style="color:var(--tl); font-size:14px; opacity:0.3;"></i>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit Plan Modal -->
<div id="editPlanModal" class="sb-overlay" style="display:none; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#fff; width:100%; max-width:600px; border-radius:16px; overflow:hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.2);">
        <div style="padding:24px; border-bottom:1px solid var(--cb); display:flex; justify-content:space-between; align-items:center;">
            <h3 id="modalTitle" style="font-size:18px; font-weight:800;">Edit Plan</h3>
            <button class="btn-icon" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="planForm" style="padding:24px;">
            <input type="hidden" name="id" id="planId">
            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                <div>
                    <label class="form-lbl">Plan Name</label>
                    <input type="text" name="name" id="planName" class="form-inp" required>
                </div>
                <div>
                    <label class="form-lbl">Monthly Price (Rs)</label>
                    <input type="number" name="price" id="planPrice" class="form-inp" required>
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label class="form-lbl">Features (One per line)</label>
                <textarea name="features" id="planFeatures" class="form-inp" style="height:200px; font-family:monospace; font-size:13px;"></textarea>
                <small style="color:var(--tl); font-size:11px;">Features will be saved exactly as listed above.</small>
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button type="button" class="btn bs" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn bt">Save Plan Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditPlanModal(plan) {
    document.getElementById('planId').value = plan.id;
    document.getElementById('planName').value = plan.name;
    document.getElementById('planPrice').value = plan.price_monthly;
    document.getElementById('modalTitle').innerText = 'Edit ' + plan.name;
    
    // Fetch features via API or use preloaded
    SuperAdmin.showNotification('Loading features...', 'info');
    fetch('../../api/get_plan_features.php?plan_id=' + plan.id)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('planFeatures').value = data.features.map(f => f.feature_text).join('\n');
            document.getElementById('editPlanModal').style.display = 'flex';
        }
    });
}

function closeModal() {
    document.getElementById('editPlanModal').style.display = 'none';
}

document.getElementById('planForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    SuperAdmin.showNotification('Saving changes...', 'info');
    fetch('../../api/update_plan_features.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            SuperAdmin.showNotification('Plan updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            SuperAdmin.showNotification(data.message, 'error');
        }
    });
};

function openNewPlanModal() {
    SuperAdmin.showNotification('Feature coming soon!', 'info');
}
</script>

<style>
.sb-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
}
</style>

<?php include 'footer.php'; ?>
