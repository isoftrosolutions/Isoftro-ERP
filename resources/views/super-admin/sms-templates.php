<?php
/**
 * Hamro ERP — SMS Templates
 * Manage default SMS notification templates
 */

// Config should already be loaded via bootstrap, but include if needed
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

$pdo = getDBConnection();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                case 'update':
                    $id = $_POST['action'] === 'update' ? $_POST['id'] : null;
                    if ($id) {
                        $stmt = $pdo->prepare("
                            UPDATE sms_templates 
                            SET name = ?, slug = ?, content = ?, is_default = ?, is_active = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['slug'],
                            $_POST['content'],
                            isset($_POST['is_default']) ? 1 : 0,
                            isset($_POST['is_active']) ? 1 : 0,
                            $id
                        ]);
                        $message = 'Template updated successfully!';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO sms_templates (name, slug, content, is_default, is_active)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['slug'],
                            $_POST['content'],
                            isset($_POST['is_default']) ? 1 : 0,
                            isset($_POST['is_active']) ? 1 : 0
                        ]);
                        $message = 'Template created successfully!';
                    }
                    $messageType = 'success';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM sms_templates WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Template deleted!';
                    $messageType = 'success';
                    break;
                    
                case 'toggle':
                    $stmt = $pdo->prepare("UPDATE sms_templates SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Template status updated!';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch templates
try {
    $templates = $pdo->query("SELECT * FROM sms_templates ORDER BY is_default DESC, name ASC")->fetchAll();
} catch (Exception $e) {
    $templates = [];
}

// Get template for editing
$editTemplate = null;
if (isset($_GET['edit'])) {
    foreach ($templates as $t) {
        if ($t['id'] == $_GET['edit']) {
            $editTemplate = $t;
            break;
        }
    }
}

$pageTitle = 'SMS Templates';
include __DIR__ . '/header.php';

renderSuperAdminHeader();
renderSidebar('sms-templates.php');
?>

<main class="main" id="mainContent">
    <div class="pg fu">
        <!-- Page Header -->
        <div class="pg-head">
            <div class="pg-left">
                <div class="pg-ico ic-g"><i class="fa-solid fa-comment-sms"></i></div>
                <div>
                    <div class="pg-title">SMS Templates</div>
                    <div class="pg-sub">Manage default SMS notification templates for tenants</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Variable Reference -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="ct"><i class="fa-solid fa-code"></i> Available Variables</div>
            <div class="variable-list">
                <code>{{institute_name}}</code>
                <code>{{student_name}}</code>
                <code>{{parent_name}}</code>
                <code>{{fee_amount}}</code>
                <code>{{due_date}}</code>
                <code>{{date}}</code>
                <code>{{subject}}</code>
                <code>{{exam_name}}</code>
                <code>{{exam_date}}</code>
                <code>{{exam_time}}</code>
                <code>{{room}}</code>
                <code>{{grade}}</code>
                <code>{{username}}</code>
                <code>{{password}}</code>
            </div>
        </div>

        <div class="g7-3">
            <!-- Template Form -->
            <div class="card">
                <div class="ct">
                    <i class="fa-solid fa-<?php echo $editTemplate ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $editTemplate ? 'Edit Template' : 'Create Template'; ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editTemplate ? 'update' : 'create'; ?>">
                    <?php if ($editTemplate): ?>
                    <input type="hidden" name="id" value="<?php echo $editTemplate['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="frm">
                        <label>Template Name</label>
                        <input type="text" name="name" value="<?php echo $editTemplate ? htmlspecialchars($editTemplate['name']) : ''; ?>" required placeholder="e.g., Fee Reminder">
                    </div>
                    
                    <div class="frm">
                        <label>Slug (Identifier)</label>
                        <input type="text" name="slug" value="<?php echo $editTemplate ? htmlspecialchars($editTemplate['slug']) : ''; ?>" required placeholder="e.g., fee_reminder" pattern="[a-z_]+">
                        <small>Lowercase letters and underscores only</small>
                    </div>
                    
                    <div class="frm">
                        <label>Message Content</label>
                        <textarea name="content" rows="5" required placeholder="Enter SMS template with variables..."><?php echo $editTemplate ? htmlspecialchars($editTemplate['content']) : ''; ?></textarea>
                        <small>Use {{variable_name}} for dynamic content</small>
                    </div>
                    
                    <div class="frm-check-group">
                        <div class="frm-check">
                            <label>
                                <input type="checkbox" name="is_default" <?php echo $editTemplate && $editTemplate['is_default'] ? 'checked' : ''; ?>>
                                Set as Default
                            </label>
                        </div>
                        <div class="frm-check">
                            <label>
                                <input type="checkbox" name="is_active" <?php echo !$editTemplate || $editTemplate['is_active'] ? 'checked' : ''; ?>>
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn bt">
                            <i class="fa-solid fa-save"></i> Save Template
                        </button>
                        <?php if ($editTemplate): ?>
                        <a href="sms-templates.php" class="btn bs">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Template List -->
            <div class="card">
                <div class="ct"><i class="fa-solid fa-list"></i> Existing Templates</div>
                
                <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-comment-sms"></i>
                    <p>No templates yet</p>
                </div>
                <?php else: ?>
                <div class="template-list">
                    <?php foreach ($templates as $template): ?>
                    <div class="template-item <?php echo $template['is_active'] ? 'active' : 'inactive'; ?>">
                        <div class="template-header">
                            <div class="template-name">
                                <?php if ($template['is_default']): ?>
                                <span class="tag bg-g">DEFAULT</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($template['name']); ?>
                            </div>
                            <div class="template-actions">
                                <a href="?edit=<?php echo $template['id']; ?>" class="btn-icon" title="Edit">
                                    <i class="fa-solid fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                    <button type="submit" class="btn-icon" title="<?php echo $template['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fa-solid fa-toggle-<?php echo $template['is_active'] ? 'on' : 'off'; ?>"></i>
                                    </button>
                                </form>
                                <?php if (!$template['is_default']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this template?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                    <button type="submit" class="btn-icon danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="template-slug"><?php echo htmlspecialchars($template['slug']); ?></div>
                        <div class="template-content">
                            <?php echo htmlspecialchars($template['content']); ?>
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
.variable-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.variable-list code {
    background: var(--sa-bg);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    color: var(--sa-primary);
}
.template-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.template-item {
    padding: 16px;
    background: var(--sa-bg);
    border-radius: 8px;
    border-left: 4px solid var(--sa-primary);
}
.template-item.inactive {
    border-left-color: var(--tl);
    opacity: 0.7;
}
.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}
.template-name {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.template-actions {
    display: flex;
    gap: 8px;
}
.template-slug {
    font-size: 11px;
    color: var(--tl);
    font-family: monospace;
    margin-bottom: 8px;
}
.template-content {
    font-size: 13px;
    color: var(--tl);
    line-height: 1.5;
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
.frm small {
    display: block;
    font-size: 11px;
    color: var(--tl);
    margin-top: 4px;
}
.frm-check-group {
    display: flex;
    gap: 24px;
    margin-bottom: 16px;
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
