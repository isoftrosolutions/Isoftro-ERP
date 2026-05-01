<?php
/**
 * Institute Admin — Profile Controller
 * Handles both Institute Settings and User Profile
 */
header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user || ($user['role'] !== 'instituteadmin' && $user['role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenantId = $user['tenant_id'];
$db = getDBConnection();
$type = $_GET['type'] ?? 'institute'; // 'institute' or 'user'

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($type === 'user') {
        // Fetch User Personal Profile
        $stmt = $db->prepare("SELECT id, name, email, phone, avatar FROM users WHERE id = :uid AND tenant_id = :tid");
        $stmt->execute(['uid' => $user['id'], 'tid' => $tenantId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['avatar_url'] = $row['avatar'] ? APP_URL . $row['avatar'] : null;
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        // Fetch Institute Profile
        $stmt = $db->prepare("SELECT name, nepali_name, phone, email, website, address, logo_path, tagline, brand_color, province, pan_no, plan FROM tenants WHERE id = :tid");
        $stmt->execute(['tid' => $tenantId]);
        $row = $stmt->fetch();
        if ($row) {
            $lp = $row['logo_path'] ? (strpos($row['logo_path'], '/public/') === 0 ? substr($row['logo_path'], 7) : $row['logo_path']) : null;
            $row['logo_url'] = $lp ? APP_URL . $lp : null;
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Institute not found']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($type === 'user') {
            // Update User Profile
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!$name) throw new Error('Name is required');

            $params = ['name' => $name, 'phone' => $phone, 'uid' => $user['id']];
            $sql = "UPDATE users SET name = :name, phone = :phone";

            if ($newPassword) {
                if ($newPassword !== $confirmPassword) throw new Error('Passwords do not match');
                $sql .= ", password_hash = :hash";
                $params['hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            // Handle Avatar Upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
                if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) throw new Error('Avatar exceeds 5MB limit');
                $dir = "public/uploads/avatars/";
                if (!is_dir(APP_ROOT . '/' . $dir)) mkdir(APP_ROOT . '/' . $dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) throw new Error('Invalid avatar type');
                $filename = "user_" . $user['id'] . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], APP_ROOT . '/' . $dir . $filename)) {
                    $sql .= ", avatar = :avatar";
                    $params['avatar'] = '/' . $dir . $filename;
                }
            }

            $sql .= " WHERE id = :uid";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Personal profile updated']);
        } else {
            // Update Institute Profile
            $name = $_POST['name'] ?? '';
            $nname = $_POST['nepali_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $website = $_POST['website'] ?? '';
            $address = $_POST['address'] ?? '';
            $tagline = $_POST['tagline'] ?? '';
            $color = $_POST['brand_color'] ?? '#006D44';
            $pan = $_POST['pan_no'] ?? '';

            if (!$name) throw new Error('Institute name is required');

            $params = [
                'name' => $name, 
                'nname' => $nname, 
                'phone' => $phone, 
                'email' => $email, 
                'website' => $website,
                'address' => $address, 
                'tagline' => $tagline, 
                'color' => $color, 
                'pan' => $pan,
                'tid' => $tenantId
            ];
            $sql = "UPDATE tenants SET name = :name, nepali_name = :nname, phone = :phone, email = :email, website = :website, address = :address, tagline = :tagline, brand_color = :color, pan_no = :pan";

            // Handle Logo Upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
                if ($_FILES['logo']['size'] > 5 * 1024 * 1024) throw new Error('Logo exceeds 5MB limit');
                $dir = "public/uploads/logos/";
                if (!is_dir(APP_ROOT . '/' . $dir)) mkdir(APP_ROOT . '/' . $dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) throw new Error('Invalid logo type');
                $filename = "tenant_" . $tenantId . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], APP_ROOT . '/' . $dir . $filename)) {
                    $sql .= ", logo_path = :logo";
                    $params['logo'] = '/uploads/logos/' . $filename;
                }
            }

            $sql .= " WHERE id = :tid";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Update Session
            $_SESSION['tenant_name'] = $name;

            // Return brand data so frontend can apply immediately
            $logoPath = $params['logo'] ?? null;
            if (!$logoPath) {
                $stmtLogo = $db->prepare("SELECT logo_path FROM tenants WHERE id = :tid");
                $stmtLogo->execute(['tid' => $tenantId]);
                $logoPath = $stmtLogo->fetchColumn();
            }

            if ($logoPath) {
                $_SESSION['institute_logo'] = $logoPath;
                $_SESSION['tenant_logo'] = $logoPath;
            }

            echo json_encode([
                'success' => true, 
                'message' => 'Institute profile updated',
                'brand_color' => $color,
                'logo_url' => $logoPath ? APP_URL . (strpos($logoPath, '/public/') === 0 ? substr($logoPath, 7) : $logoPath) : null,
                'institute_name' => $name
            ]);
        }
    } catch (Throwable $e) {
        error_log('Controller exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
}
