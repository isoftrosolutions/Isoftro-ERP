<?php
/**
 * Institute Profile API Controller
 * Handles fetching and updating institute/tenant profile
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../../config/config.php';
}

header('Content-Type: application/json');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current tenant ID from session
$tenantId = $_SESSION['tenant_id'] ?? null;

if (!$tenantId) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized. Please log in to access this resource.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();
    
    if ($method === 'GET') {
        // Get institute profile
        $stmt = $db->prepare("
            SELECT t.*, 
                   (SELECT email FROM users WHERE tenant_id = t.id AND role = 'admin' LIMIT 1) as email
            FROM tenants t 
            WHERE t.id = :id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $tenantId]);
        $tenant = $stmt->fetch();
        
        if ($tenant) {
            echo json_encode([
                'success' => true, 
                'data' => $tenant
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Institute not found'
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Update institute profile
        $name = sanitizeInput($_POST['name'] ?? '');
        $nepali_name = sanitizeInput($_POST['nepali_name'] ?? '');
        $tagline = sanitizeInput($_POST['tagline'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $province = sanitizeInput($_POST['province'] ?? '');
        $brand_color = sanitizeInput($_POST['brand_color'] ?? '#009E7E');
        
        // Handle logo upload
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Logo size exceeds 5MB limit');
            }
            $uploadDir = __DIR__ . '/../../../uploads/logos/';
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExt = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $allowedExts = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
            
            if (in_array(strtolower($fileExt), $allowedExts)) {
                $newFileName = 'logo_' . $tenantId . '_' . time() . '.' . $fileExt;
                $targetPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                    $logoPath = '/uploads/logos/' . $newFileName;
                }
            } else {
                throw new Exception('Invalid logo type');
            }
        }
        
        // Build update query
        $updates = [];
        $params = ['id' => $tenantId];
        
        if ($name) {
            $updates[] = 'name = :name';
            $params['name'] = $name;
        }
        if ($nepali_name !== '') {
            $updates[] = 'nepali_name = :nepali_name';
            $params['nepali_name'] = $nepali_name;
        }
        if ($tagline !== '') {
            $updates[] = 'tagline = :tagline';
            $params['tagline'] = $tagline;
        }
        if ($phone !== '') {
            $updates[] = 'phone = :phone';
            $params['phone'] = $phone;
        }
        if ($address !== '') {
            $updates[] = 'address = :address';
            $params['address'] = $address;
        }
        if ($province !== '') {
            $updates[] = 'province = :province';
            $params['province'] = $province;
        }
        if ($brand_color) {
            $updates[] = 'brand_color = :brand_color';
            $params['brand_color'] = $brand_color;
        }
        if ($logoPath) {
            $updates[] = 'logo_path = :logo_path';
            $params['logo_path'] = $logoPath;
            
            // Also update session
            $_SESSION['tenant_logo'] = $logoPath;
        }
        
        if (!empty($updates)) {
            $query = "UPDATE tenants SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            // Update session variables
            if ($name) {
                $_SESSION['tenant_name'] = $name;
            }
            if ($brand_color) {
                $_SESSION['tenant_brand_color'] = $brand_color;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!'
        ]);
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    error_log('Controller exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error'
    ]);
    }

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
