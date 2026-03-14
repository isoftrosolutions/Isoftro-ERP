<?php
/**
 * Course Categories API Controller
 * Handles CRUD for course categories scoped by tenant
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../../config/config.php';
}

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $_SESSION['userData']['tenant_id'] ?? null;
if (!$tenantId) {
    echo json_encode(['success' => false, 'message' => 'Tenant ID missing']);
    exit;
}

$role = $_SESSION['userData']['role'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// RBAC check
if (!in_array($role, ['instituteadmin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        // Check if categories exist, if not, seed default ones based on institute type
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM course_categories WHERE tenant_id = :tid AND deleted_at IS NULL");
        $checkStmt->execute(['tid' => $tenantId]);
        if ($checkStmt->fetchColumn() == 0) {
            seedDefaultCategories($db, $tenantId);
        }

        // List categories
        $query = "SELECT * FROM course_categories WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute(['tid' => $tenantId]);
        $categories = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $categories]);
    }

    else if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $name = sanitizeInput($input['name'] ?? '');
        if (empty($name)) throw new Exception("Category name is required");

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        $stmt = $db->prepare("
            INSERT INTO course_categories (tenant_id, name, slug, description, is_active, created_at, updated_at) 
            VALUES (:tid, :name, :slug, :desc, :active, NOW(), NOW())
        ");

        $stmt->execute([
            'tid' => $tenantId,
            'name' => $name,
            'slug' => $slug,
            'desc' => sanitizeInput($input['description'] ?? null),
            'active' => isset($input['is_active']) ? (int)$input['is_active'] : 1
        ]);

        echo json_encode(['success' => true, 'message' => 'Category created successfully', 'id' => $db->lastInsertId()]);
    }

    else if ($method === 'PUT' || $method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Category ID is required");

        $fields = [];
        $params = ['id' => $id, 'tid' => $tenantId];
        $allowed = ['name', 'description', 'is_active'];

        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[$f] = ($f === 'name') ? sanitizeInput($input[$f]) : $input[$f];
                if ($f === 'name') {
                    $fields[] = "slug = :slug";
                    $params['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input[$f]), '-'));
                }
            }
        }

        if (empty($fields)) throw new Exception("No fields to update");

        $fields[] = "updated_at = NOW()";
        $stmt = $db->prepare("UPDATE course_categories SET " . implode(', ', $fields) . " WHERE id = :id AND tenant_id = :tid");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    }

    else if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_GET;

        $id = $input['id'] ?? null;
        if (!$id) throw new Exception("Category ID is required");

        // Check if linked to courses
        $stmt = $db->prepare("SELECT COUNT(*) FROM courses WHERE course_category_id = :id AND deleted_at IS NULL");
        $stmt->execute(['id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete category as it is linked to active courses.");
        }

        $stmt = $db->prepare("UPDATE course_categories SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tid");
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);

        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Seed default categories based on institute type
 */
function seedDefaultCategories($db, $tenantId) {
    // Get institute type
    $stmt = $db->prepare("SELECT institute_type FROM tenants WHERE id = ?");
    $stmt->execute([$tenantId]);
    $type = $stmt->fetchColumn();

    $seeds = [
        'loksewa preparation' => ['Loksewa (PSC)', 'Health (Nurse/AHW)', 'Banking', 'TSC', 'Engineering', 'Forestry', 'Agriculture'],
        'computer training' => ['Basic Computer', 'Diploma in Computer', 'Graphic Designing', 'Web Development', 'Digital Marketing', 'Accounting (Tally)'],
        'bridge course' => ['Science Bridge Course', 'Management Bridge Course', 'Nursing Bridge Course', 'Entrance Preparation'],
        'tuition' => ['School Level (G8-10)', 'Plus Two (+2) Science', 'Plus Two (+2) Management', 'A-Levels', 'Bachelor Level'],
        'other' => ['General Academy', 'Skill Development', 'Language Class']
    ];

    $categories = $seeds[$type] ?? $seeds['other'];

    $ins = $db->prepare("INSERT INTO course_categories (tenant_id, name, slug, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
    foreach ($categories as $cat) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat), '-'));
        $ins->execute([$tenantId, $cat, $slug]);
    }
}
