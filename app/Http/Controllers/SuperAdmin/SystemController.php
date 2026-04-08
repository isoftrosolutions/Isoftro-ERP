<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\SuperAdmin\AuditLogModel;

class SystemController {
    private $db;
    private $auditModel;

    public function __construct($db) {
        $this->db = $db;
        $this->auditModel = new AuditLogModel($db);
    }

    public function index() {
        try {
            $settings = $this->db->query("SELECT setting_key, setting_value FROM platform_settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            $settings = [];
        }
        return view('super-admin.settings', ['settings' => $settings]);
    }

    public function maintenanceView() {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM platform_settings");
            $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            $settings = [];
        }
        include resource_path('views/super-admin/system-maintenance.php');
    }

    public function pushView() {
        try {
            $announcements = $this->db->query("
                SELECT a.*, u.email as created_by_email
                FROM announcements a
                LEFT JOIN users u ON a.created_by = u.id
                ORDER BY a.created_at DESC LIMIT 50
            ")->fetchAll();
        } catch (\Exception $e) {
            $announcements = [];
        }
        include resource_path('views/super-admin/system-push.php');
    }

    public function brandView() {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM platform_settings");
            $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Exception $e) {
            $settings = [];
        }
        include resource_path('views/super-admin/settings-brand.php');
    }

    public function smsTemplatesView() {
        try {
            $templates = $this->db->query("SELECT * FROM sms_templates ORDER BY event_key ASC")->fetchAll();
        } catch (\Exception $e) {
            $templates = [];
        }
        include resource_path('views/super-admin/settings-sms-tpl.php');
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'settings':
            case 'system':
            case 'toggles':          return $this->index();
            case 'system-maintenance':return $this->maintenanceView();
            case 'system-push':       return $this->pushView();
            case 'settings-brand':    return $this->brandView();
            case 'settings-sms-tpl':  return $this->smsTemplatesView();
            default:                  return $this->index();
        }
    }

    public function saveSettings() {
        $data = $_POST;
        foreach ($data as $key => $value) {
            $stmt = $this->db->prepare("INSERT INTO platform_settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'string') ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $this->auditModel->logAction(getCurrentUser()['id'], null, 'settings_update', array_keys($data));
        echo json_encode(['success' => true]);
    }

    public function toggleMaintenance($enabled) {
        $value = $enabled ? '1' : '0';
        $stmt = $this->db->prepare("INSERT INTO platform_settings (setting_key, setting_value, setting_type) VALUES ('maintenance_mode', ?, 'boolean') ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$value, $value]);
        $this->auditModel->logAction(getCurrentUser()['id'], null, 'platform_maintenance', ['enabled' => $enabled]);
        echo json_encode(['success' => true]);
    }

    public function announce() {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $stmt = $this->db->prepare("INSERT INTO announcements (title, message, target_audience, created_by) VALUES (?, ?, 'all', ?)");
        $stmt->execute([$title, $message, getCurrentUser()['id']]);
        $this->auditModel->logAction(getCurrentUser()['id'], null, 'platform_announcement', ['title' => $title]);
        echo json_encode(['success' => true]);
    }
}
