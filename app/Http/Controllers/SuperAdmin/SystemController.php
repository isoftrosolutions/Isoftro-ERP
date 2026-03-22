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

    public function handle($action = 'index') {
        switch ($action) {
            case 'settings':
            case 'system': return $this->index();
            default: return $this->index();
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
