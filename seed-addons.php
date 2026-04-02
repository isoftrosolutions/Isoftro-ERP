<?php
/**
 * Quick seeding script for add-on features
 * Run from command line: php seed-addons.php
 */

require_once __DIR__ . '/config/config.php';

echo "[*] Seeding add-on features...\n";

try {
    $db = getDBConnection();

    // 1. CREATE ADD-ON FEATURES
    $addons = [
        // Analytics & Reporting
        ['key' => 'advanced-analytics', 'name' => 'Advanced Analytics & Reports', 'desc' => 'Custom dashboards, predictive analytics, and advanced reporting tools', 'monthly' => 50, 'annual' => 500, 'category' => 'analytics', 'status' => 'active', 'approval' => 0],
        ['key' => 'bi-dashboard', 'name' => 'Business Intelligence Dashboard', 'desc' => 'Real-time BI dashboards and data visualization with drill-down capabilities', 'monthly' => 75, 'annual' => 750, 'category' => 'analytics', 'status' => 'active', 'approval' => 0],

        // Communications
        ['key' => 'sms-gateway', 'name' => 'SMS Gateway Integration', 'desc' => 'Send bulk SMS notifications to students, parents, and staff', 'monthly' => 40, 'annual' => 400, 'category' => 'communications', 'status' => 'active', 'approval' => 0],
        ['key' => 'email-campaigns', 'name' => 'Email Marketing Campaigns', 'desc' => 'Advanced email templates, bulk mailing, and campaign tracking', 'monthly' => 30, 'annual' => 300, 'category' => 'communications', 'status' => 'active', 'approval' => 0],
        ['key' => 'whatsapp-integration', 'name' => 'WhatsApp Business Integration', 'desc' => 'Send notifications and messages via WhatsApp Business API', 'monthly' => 60, 'annual' => 600, 'category' => 'communications', 'status' => 'beta', 'approval' => 1],

        // Integrations
        ['key' => 'google-classroom', 'name' => 'Google Classroom Sync', 'desc' => 'Automatically sync classes, assignments, and grades with Google Classroom', 'monthly' => 25, 'annual' => 250, 'category' => 'integrations', 'status' => 'active', 'approval' => 0],
        ['key' => 'zoom-integration', 'name' => 'Zoom Meeting Integration', 'desc' => 'Schedule and manage Zoom meetings directly from iSoftro portal', 'monthly' => 35, 'annual' => 350, 'category' => 'integrations', 'status' => 'active', 'approval' => 0],
        ['key' => 'microsoft-teams', 'name' => 'Microsoft Teams Integration', 'desc' => 'Integration with Microsoft Teams for seamless collaboration', 'monthly' => 35, 'annual' => 350, 'category' => 'integrations', 'status' => 'active', 'approval' => 0],
        ['key' => 'google-meet', 'name' => 'Google Meet Integration', 'desc' => 'Schedule and launch Google Meet sessions from iSoftro', 'monthly' => 20, 'annual' => 200, 'category' => 'integrations', 'status' => 'active', 'approval' => 0],

        // Automation
        ['key' => 'workflow-automation', 'name' => 'Workflow Automation Engine', 'desc' => 'Create custom workflows and automate repetitive tasks with triggers and actions', 'monthly' => 55, 'annual' => 550, 'category' => 'automation', 'status' => 'active', 'approval' => 0],
        ['key' => 'api-access', 'name' => 'Advanced API Access', 'desc' => 'Premium API limits (100k calls/month), webhook support, and custom integrations', 'monthly' => 80, 'annual' => 800, 'category' => 'automation', 'status' => 'active', 'approval' => 0],
        ['key' => 'webhooks-premium', 'name' => 'Premium Webhooks & Events', 'desc' => 'Custom webhooks, event streaming, and real-time notifications', 'monthly' => 45, 'annual' => 450, 'category' => 'automation', 'status' => 'active', 'approval' => 0],

        // Compliance & Security
        ['key' => 'advanced-security', 'name' => 'Advanced Security Package', 'desc' => 'IP whitelisting, 2FA enforcement, SSO integration, and advanced audit logs', 'monthly' => 70, 'annual' => 700, 'category' => 'compliance', 'status' => 'active', 'approval' => 1],
        ['key' => 'gdpr-compliance', 'name' => 'GDPR Compliance Suite', 'desc' => 'Data privacy tools, consent management, and compliance audit reports', 'monthly' => 50, 'annual' => 500, 'category' => 'compliance', 'status' => 'active', 'approval' => 0],
        ['key' => 'backup-recovery', 'name' => 'Premium Backup & Recovery', 'desc' => '24-hour backup frequency, disaster recovery, and point-in-time restore', 'monthly' => 45, 'annual' => 450, 'category' => 'compliance', 'status' => 'active', 'approval' => 0],
        ['key' => 'data-encryption', 'name' => 'End-to-End Data Encryption', 'desc' => 'AES-256 encryption at rest and in transit with key management', 'monthly' => 60, 'annual' => 600, 'category' => 'compliance', 'status' => 'active', 'approval' => 1],

        // Support
        ['key' => 'priority-support', 'name' => 'Priority Support', 'desc' => '24/7 priority email and phone support with 2-hour response time', 'monthly' => 100, 'annual' => 1000, 'category' => 'support', 'status' => 'active', 'approval' => 0],
        ['key' => 'dedicated-manager', 'name' => 'Dedicated Account Manager', 'desc' => 'Dedicated manager for onboarding, training, and optimization', 'monthly' => 200, 'annual' => 2000, 'category' => 'support', 'status' => 'active', 'approval' => 1],
        ['key' => 'custom-training', 'name' => 'Custom Training Program', 'desc' => 'Customized training sessions for your staff (10 sessions)', 'monthly' => 150, 'annual' => 1500, 'category' => 'support', 'status' => 'active', 'approval' => 1],
    ];

    $stmt = $db->prepare("
        INSERT INTO addon_features
        (addon_key, addon_name, description, monthly_price, annual_price, category, status, requires_approval, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
        addon_name = VALUES(addon_name),
        description = VALUES(description),
        monthly_price = VALUES(monthly_price),
        annual_price = VALUES(annual_price),
        category = VALUES(category),
        status = VALUES(status),
        requires_approval = VALUES(requires_approval),
        updated_at = NOW()
    ");

    foreach ($addons as $addon) {
        $stmt->execute([
            $addon['key'],
            $addon['name'],
            $addon['desc'],
            $addon['monthly'],
            $addon['annual'],
            $addon['category'],
            $addon['status'],
            $addon['approval'],
        ]);
    }

    echo "[✓] Added " . count($addons) . " add-on features\n";

    // 2. SET UP ADD-ON REQUIREMENTS
    $requirements = [
        ['addon_key' => 'advanced-analytics', 'type' => 'requires_plan', 'value' => 'growth', 'reason' => 'Only available for Growth plan and above'],
        ['addon_key' => 'bi-dashboard', 'type' => 'requires_plan', 'value' => 'growth', 'reason' => 'Only available for Growth plan and above'],
        ['addon_key' => 'dedicated-manager', 'type' => 'requires_plan', 'value' => 'growth', 'reason' => 'Only available for Growth plan and above'],
        ['addon_key' => 'advanced-security', 'type' => 'requires_plan', 'value' => 'enterprise', 'reason' => 'Only available for Enterprise plan'],
    ];

    $reqStmt = $db->prepare("
        INSERT INTO addon_requirements
        (addon_id, requirement_type, requirement_key, reason, created_at, updated_at)
        SELECT id, ?, ?, ?, NOW(), NOW()
        FROM addon_features WHERE addon_key = ?
        ON DUPLICATE KEY UPDATE
        requirement_type = VALUES(requirement_type),
        requirement_key = VALUES(requirement_key),
        reason = VALUES(reason),
        updated_at = NOW()
    ");

    foreach ($requirements as $req) {
        $reqStmt->execute([
            $req['type'],
            $req['value'],
            $req['reason'],
            $req['addon_key']
        ]);
    }

    echo "[✓] Added " . count($requirements) . " add-on requirements\n";
    echo "\n[✅] Seeding completed successfully!\n";

} catch (Exception $e) {
    echo "[✗] Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
