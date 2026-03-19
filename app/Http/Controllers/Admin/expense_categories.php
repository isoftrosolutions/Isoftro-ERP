<?php
/**
 * Admin Expense Categories API
 * Route: /api/admin/expense-categories
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('expenses.view')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenant_id) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();

    switch ($action) {
        case 'list':
            $stmt = $db->prepare("SELECT id, name, description, color, icon, parent_id, is_active FROM expense_categories WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name ASC");
            $stmt->execute(['tid' => $tenant_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no categories, seed default ones
            if (empty($categories)) {
                $defaults = [
                    ['name' => 'Staff Salaries & Benefits', 'icon' => 'user-tie', 'color' => '#4e73df'],
                    ['name' => 'Operational Costs', 'icon' => 'building', 'color' => '#1cc88a'],
                    ['name' => 'Educational Materials & Resources', 'icon' => 'book', 'color' => '#36b9cc'],
                    ['name' => 'Marketing & Promotional Expenses', 'icon' => 'ad', 'color' => '#f6c23e'],
                    ['name' => 'Technology & Infrastructure', 'icon' => 'laptop', 'color' => '#e74a3b'],
                    ['name' => 'Miscellaneous', 'icon' => 'ellipsis-h', 'color' => '#858796'],
                ];

                $ins = $db->prepare("INSERT INTO expense_categories (tenant_id, name, icon, color, is_active, created_at, updated_at) VALUES (:tid, :name, :icon, :color, 1, NOW(), NOW())");
                foreach ($defaults as $d) {
                    $ins->execute(['tid' => $tenant_id, 'name' => $d['name'], 'icon' => $d['icon'], 'color' => $d['color']]);
                }

                $stmt->execute(['tid' => $tenant_id]);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode(['success' => true, 'categories' => $categories]);
            break;

        case 'save':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid Method");
            
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

            if (empty($name)) throw new Exception("Category name is required");

            if ($id > 0) {
                $stmt = $db->prepare("UPDATE expense_categories SET name = :name, description = :description, color = :color, icon = :icon, parent_id = :parent_id, is_active = :is_active, updated_at = NOW() WHERE id = :id AND tenant_id = :tid");
                $stmt->execute(['name' => $name, 'description' => $description, 'color' => $color, 'icon' => $icon, 'parent_id' => $parent_id, 'is_active' => $is_active, 'id' => $id, 'tid' => $tenant_id]);
                $message = "Category updated successfully";
            } else {
                $stmt = $db->prepare("INSERT INTO expense_categories (tenant_id, name, description, color, icon, parent_id, is_active, created_at, updated_at) VALUES (:tid, :name, :description, :color, :icon, :parent_id, :is_active, NOW(), NOW())");
                $stmt->execute(['tid' => $tenant_id, 'name' => $name, 'description' => $description, 'color' => $color, 'icon' => $icon, 'parent_id' => $parent_id, 'is_active' => $is_active]);
                $id = $db->lastInsertId();
                $message = "Category created successfully";
            }

            echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Invalid Method");
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception("ID missing");

            // Check if there are expenses in this category
            $check = $db->prepare("SELECT COUNT(*) FROM expenses WHERE expense_category_id = :id AND tenant_id = :tid AND deleted_at IS NULL");
            $check->execute(['id' => $id, 'tid' => $tenant_id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception("Cannot delete category with associated expenses. Please move or delete the expenses first.");
            }

            $stmt = $db->prepare("UPDATE expense_categories SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
            $stmt->execute(['id' => $id, 'tid' => $tenant_id]);

            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            break;

        default:
            throw new Exception("Invalid Action");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
