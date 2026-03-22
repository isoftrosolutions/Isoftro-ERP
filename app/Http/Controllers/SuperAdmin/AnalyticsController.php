<?php

namespace App\Http\Controllers\SuperAdmin;

use PDO;

class AnalyticsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        // Active users today (logged in within 24h)
        $activeToday = $this->db->query("
            SELECT COUNT(DISTINCT u.id) 
            FROM users u 
            WHERE u.last_login_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND u.role != 'superadmin'
        ")->fetchColumn() ?: 0;

        // Peak concurrent: max users with overlapping login sessions in last 7 days
        // We approximate with max logins in a 1-day window
        $peakRow = $this->db->query("
            SELECT COUNT(*) as cnt, DATE(last_login_at) as day
            FROM users
            WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND role != 'superadmin'
            GROUP BY DATE(last_login_at)
            ORDER BY cnt DESC
            LIMIT 1
        ")->fetch();
        $peakConcurrent = $peakRow['cnt'] ?? 0;

        // Total registered students
        $totalStudents = $this->db->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;

        // Total active institutes
        $activeInstitutes = $this->db->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn() ?: 0;

        // Feature usage: count institute_modules enabled per module
        $featureUsage = $this->db->query("
            SELECT m.name, COUNT(im.module_id) as usage_count
            FROM modules m
            LEFT JOIN institute_modules im ON im.module_id = m.id AND im.is_enabled = 1
            GROUP BY m.id, m.name
            ORDER BY usage_count DESC
            LIMIT 10
        ")->fetchAll() ?: [];

        // Compute percentage relative to total institutes
        $totalInstitutes = $this->db->query("SELECT COUNT(*) FROM tenants")->fetchColumn() ?: 1;
        foreach ($featureUsage as &$f) {
            $f['pct'] = $totalInstitutes > 0 ? round(($f['usage_count'] / $totalInstitutes) * 100) : 0;
        }
        unset($f);

        // SMS consumption per tenant (top 5)
        $smsConsumption = $this->db->query("
            SELECT t.name, 
                   COUNT(sl.id) as sms_used,
                   t.sms_credits as sms_limit
            FROM tenants t
            LEFT JOIN sms_logs sl ON sl.tenant_id = t.id 
                AND sl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY t.id, t.name, t.sms_credits
            ORDER BY sms_used DESC
            LIMIT 5
        ")->fetchAll() ?: [];

        return view('super-admin.analytics', [
            'activeUsers'      => $activeToday,
            'peakConcurrent'   => $peakConcurrent,
            'totalStudents'    => $totalStudents,
            'activeInstitutes' => $activeInstitutes,
            'featureUsage'     => $featureUsage,
            'smsConsumption'   => $smsConsumption,
            'totalInstitutes'  => $totalInstitutes,
        ]);
    }

    public function handle($action = 'index') {
        switch ($action) {
            case 'analytics': return $this->index();
            default:          return $this->index();
        }
    }
}
