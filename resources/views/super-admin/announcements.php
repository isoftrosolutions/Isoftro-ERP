<?php
/**
 * Hamro ERP — Push Announcements
 * Platform-wide announcement management
 */

// Config should already be loaded via bootstrap, but include if needed
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pdo = getDBConnection();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("
                        INSERT INTO announcements (title, message, target_audience, priority, is_active, starts_at, ends_at, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['message'],
                        $_POST['target_audience'] ?? 'all',
                        $_POST['priority'] ?? 'normal',
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['starts_at'] ?? date('Y-m-d H:i:s'),
                        $_POST['ends_at'] ?? null,
                        $_SESSION['userData']['id'] ?? 1
                    ]);
                    $message = 'Announcement created successfully!';
                    $messageType = 'success';
                    break;
                    
                case 'toggle':
                    $stmt = $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Announcement status updated!';
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Announcement deleted!';
                    $messageType = 'success';
                    break;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Fetch announcements
$announcements = $pdo->query("
    SELECT a.*, u.email as created_by_email
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.created_at DESC
")->fetchAll();

$pageTitle = 'Push Announcements';
include __DIR__ . '/header.php';

renderSuperAdminHeader();
renderSidebar('announcements.php');
?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-a"><i class="fa-solid fa-bullhorn"></i></div>
                <div>
                    <div class="pg-title">Push Announcements</div>
                    <div class="pg-sub">Broadcast messages to all tenants or specific groups</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <div class="g7-3">
            <!-- Create New Announcement -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-plus-circle"></i> Create Announcement</div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="frm">
                        <label>Title</label>
                        <input type="text" name="title" required placeholder="Announcement title">
                    </div>
                    
                    <div class="frm">
                        <label>Message</label>
                        <textarea name="message" rows="4" required placeholder="Enter your announcement message..."></textarea>
                    </div>
                    
                    <div class="frm-grid">
                        <div class="frm">
                            <label>Target Audience</label>
                            <select name="target_audience">
                                <option value="all">All Tenants</option>
                                <option value="tenants">Active Tenants Only</option>
                                <option value="admins">Admins Only</option>
                                <option value="trial">Trial Users</option>
                            </select>
                        </div>
                        
                        <div class="frm">
                            <label>Priority</label>
                            <select name="priority">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="frm-grid">
                        <div class="frm">
                            <label>Start Date</label>
                            <input type="datetime-local" name="starts_at" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        
                        <div class="frm">
                            <label>End Date (Optional)</label>
                            <input type="datetime-local" name="ends_at">
                        </div>
                    </div>
                    
                    <div class="frm-check">
                        <label>
                            <input type="checkbox" name="is_active" checked>
                            Publish Immediately
                        </label>
                    </div>
                    
                    <button type="submit" class="btn bt" style="width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i> Send Announcement
                    </button>
                </form>
            </div>

            <!-- Existing Announcements -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-list"></i> Recent Announcements</div>
                
                <?php if (empty($announcements)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bullhorn"></i>
                    <p>No announcements yet</p>
                </div>
                <?php else: ?>
                <div class="announcement-list">
                    <?php foreach ($announcements as $ann): ?>
                    <div class="announcement-item <?php echo $ann['is_active'] ? 'active' : 'inactive'; ?>">
                        <div class="ann-header">
                            <div class="ann-title">
                                <?php if ($ann['priority'] === 'urgent'): ?>
                                <span class="tag bg-r">URGENT</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ann['title']); ?>
                            </div>
                            <div class="ann-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" class="btn-icon" title="<?php echo $ann['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fa-solid fa-toggle-<?php echo $ann['is_active'] ? 'on' : 'off'; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                    <button type="submit" class="btn-icon danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="ann-body">
                            <?php echo nl2br(htmlspecialchars($ann['message'])); ?>
                        </div>
                        <div class="ann-meta">
                            <span><i class="fa-solid fa-users"></i> <?php echo ucfirst($ann['target_audience']); ?></span>
                            <span><i class="fa-solid fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($ann['created_at'])); ?></span>
                            <span><i class="fa-solid fa-calendar"></i> <?php echo $ann['starts_at'] ? date('M d', strtotime($ann['starts_at'])) : 'Now'; ?> - <?php echo $ann['ends_at'] ? date('M d', strtotime($ann['ends_at'])) : '∞'; ?></span>
                            <span class="status <?php echo $ann['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $ann['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.announcement-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.announcement-item {
    padding: 16px;
    background: var(--sa-bg);
    border-radius: 8px;
    border-left: 4px solid var(--sa-primary);
}
.announcement-item.inactive {
    border-left-color: var(--tl);
    opacity: 0.7;
}
.ann-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.ann-title {
    font-weight: 600;
    font-size: 15px;
}
.ann-actions {
    display: flex;
    gap: 8px;
}
.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    color: var(--tl);
    transition: color 0.2s;
}
.btn-icon:hover {
    color: var(--sa-primary);
}
.btn-icon.danger:hover {
    color: var(--danger);
}
.ann-body {
    font-size: 13px;
    color: var(--tl);
    margin-bottom: 12px;
    line-height: 1.5;
}
.ann-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 12px;
    color: var(--tl);
}
.ann-meta .status {
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 500;
}
.ann-meta .status.active {
    background: rgba(0, 158, 126, 0.1);
    color: var(--sa-primary);
}
.ann-meta .status.inactive {
    background: rgba(128, 128, 128, 0.1);
    color: var(--tl);
}
.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--tl);
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.3;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
