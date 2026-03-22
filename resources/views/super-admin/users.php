<?php
/**
 * ISOFTRO - Super Admin Dashboard (Users)
 * Partial view loaded via AJAX
 */

$PDO = getDBConnection();

// Fetch super admin users
try {
    $stmt = $PDO->query("
        SELECT id, name, email, status, last_login_at
        FROM users
        WHERE role = 'superadmin'
        ORDER BY name ASC
    ");
    $admins = $stmt->fetchAll();
} catch (Exception $e) {
    $admins = [];
}

?>
<div class="pg-hdr">
    <div>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span>Admin Users</span>
        </div>
        <h1>Super Admin Users</h1>
    </div>
    <button class="btn bt">
        <i class="fas fa-user-plus"></i>
        Add Admin User
    </button>
</div>

<div class="toolbar">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" class="search-inp" placeholder="Search users...">
    </div>
</div>

<div class="tbl-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $admin): ?>
            <tr>
                <td><strong><?= htmlspecialchars($admin['name'] ?: 'Unnamed') ?></strong></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td><span class="pill pp">Super Admin</span></td>
                <td><span class="pill <?= $admin['status'] === 'active' ? 'pg' : 'pr' ?>"><?= ucfirst($admin['status']) ?></span></td>
                <td><?= $admin['last_login_at'] ? date('M d, H:i', strtotime($admin['last_login_at'])) : 'Never' ?></td>
                <td>
                    <button class="btn bs btn-sm" title="Edit Admin"><i class="fas fa-edit"></i></button>
                    <?php if (getCurrentUser()['id'] !== $admin['id']): ?>
                    <button class="btn bs btn-sm btn-red" title="Deactivate"><i class="fas fa-ban"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($admins)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">No other super admins found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
