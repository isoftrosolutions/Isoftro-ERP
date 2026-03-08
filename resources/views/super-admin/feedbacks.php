<?php
/**
 * Hamro ERP — User Feedbacks Page
 * Platform Blueprint V3.0
 */

require_once __DIR__ . '/../../../config/config.php';
require_once VIEWS_PATH . '/layouts/header_1.php';

$pageTitle = 'User Feedbacks';
$activePage = 'feedbacks.php';

// Fetch feedbacks from DB
try {
    $db = getDBConnection();
    $stmt = $db->query("
        SELECT f.*, t.name as tenant_name, u.name as user_name 
        FROM feedbacks f 
        JOIN tenants t ON f.tenant_id = t.id 
        JOIN users u ON f.user_id = u.id 
        ORDER BY f.created_at DESC
    ");
    $feedbacks = $stmt->fetchAll();
} catch (Exception $e) {
    $feedbacks = [];
}
?>

<?php renderSuperAdminHeader(); renderSidebar($activePage); ?>

<main class="main" id="mainContent">
    <div class="page fu">
        <div class="pg-hdr">
            <div class="pg-hdr-left">
                <div class="breadcrumb">
                    <span class="bc-root">Dashboard</span>
                    <span class="bc-sep">›</span>
                    <span class="bc-cur">User Feedbacks</span>
                </div>
                <h1 style="display:flex; align-items:center; gap:10px;">
                    <i class="fa fa-comment-dots" style="color:var(--green); font-size:1.1rem;"></i>
                    User Feedbacks
                </h1>
                <p>Monitor bug reports and feature suggestions from all institutes</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn bs" onclick="exportFeedbacks()">
                    <i class="fa fa-file-export"></i> Export CSV
                </button>
            </div>
        </div>

        <div class="card">
            <div class="tbl-head">
                <div class="tbl-title"><i class="fa fa-list"></i> All Submissions</div>
                <div style="display:flex; gap:10px;">
                    <input type="text" placeholder="Search feedbacks..." class="filter-sel" style="width:200px; padding:6px 12px;">
                    <select class="filter-sel">
                        <option>All Modules</option>
                        <option>Dashboard</option>
                        <option>Students</option>
                        <option>Fees</option>
                    </select>
                </div>
            </div>
            <div class="tbl-responsive" style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                            <th style="padding:16px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">User / Institute</th>
                            <th style="padding:16px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Context</th>
                            <th style="padding:16px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Feedback / Problem</th>
                            <th style="padding:16px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Status</th>
                            <th style="padding:16px; text-align:right; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="5" style="padding:40px; text-align:center; color:#94a3b8;">No feedbacks found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                        <tr style="border-bottom:1px solid #f1f5f9; transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding:16px;">
                                <div style="font-weight:600; color:#1e293b; font-size:13.5px;"><?php echo htmlspecialchars($fb['user_name']); ?></div>
                                <div style="font-size:11.5px; color:#64748b; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                    <i class="fa-solid fa-school" style="font-size:10px;"></i>
                                    <?php echo htmlspecialchars($fb['tenant_name']); ?>
                                </div>
                            </td>
                            <td style="padding:16px;">
                                <span style="display:inline-block; background:#eff6ff; color:#2563eb; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase;"><?php echo htmlspecialchars($fb['module']); ?></span>
                                <div style="font-size:12px; color:#64748b; margin-top:4px;"><?php echo htmlspecialchars($fb['page'] ?: 'Unknown Page'); ?></div>
                            </td>
                            <td style="padding:16px;">
                                <div style="font-size:13.5px; color:#334155; line-height:1.5; max-width:400px;"><?php echo nl2br(htmlspecialchars($fb['problem'])); ?></div>
                                <?php if ($fb['screenshot_path']): ?>
                                <div style="margin-top:10px;">
                                    <a href="<?php echo APP_URL . $fb['screenshot_path']; ?>" target="_blank" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none; color:var(--green); font-size:12px; font-weight:600;">
                                        <i class="fa-solid fa-image"></i> View Screenshot
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:16px;">
                                <?php 
                                $statusClass = match($fb['status']) {
                                    'open' => 'bg-r',
                                    'in-progress' => 'bg-y',
                                    'resolved' => 'bg-g',
                                    default => 'bg-y'
                                };
                                ?>
                                <span class="stat-badge <?php echo $statusClass; ?>" style="text-transform:capitalize;"><?php echo $fb['status']; ?></span>
                                <div style="font-size:11px; color:#94a3b8; margin-top:6px;"><?php echo date('M d, Y H:i', strtotime($fb['created_at'])); ?></div>
                            </td>
                            <td style="padding:16px; text-align:right;">
                                <div style="display:flex; gap:6px; justify-content:flex-end;">
                                    <button class="btn btn-icon" title="View Details" onclick="viewFeedbackDetails(<?php echo $fb['id']; ?>)">
                                        <i class="fa-solid fa-eye" style="color:#64748b;"></i>
                                    </button>
                                    <button class="btn btn-icon" title="Resolve" onclick="updateFeedbackStatus(<?php echo $fb['id']; ?>, 'resolved')">
                                        <i class="fa-solid fa-circle-check" style="color:var(--green);"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function viewFeedbackDetails(id) {
    // Implement modal for detailed view
    SuperAdmin.showNotification('Loading feedback details...', 'info');
}

function updateFeedbackStatus(id, status) {
    // Implement status update logic
    SuperAdmin.showNotification('Updating status for #' + id + ' to ' + status, 'info');
}

function exportFeedbacks() {
    SuperAdmin.showNotification('Preparing CSV export...', 'info');
}
</script>

<?php include 'footer.php'; ?>
