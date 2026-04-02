<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddonFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CREATE ADD-ON FEATURES
        $addons = [
            // Analytics & Reporting
            [
                'key' => 'advanced-analytics',
                'name' => 'Advanced Analytics & Reports',
                'description' => 'Custom dashboards, predictive analytics, and advanced reporting tools',
                'monthly' => 50,
                'annual' => 500,
                'category' => 'analytics',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'bi-dashboard',
                'name' => 'Business Intelligence Dashboard',
                'description' => 'Real-time BI dashboards and data visualization with drill-down capabilities',
                'monthly' => 75,
                'annual' => 750,
                'category' => 'analytics',
                'status' => 'active',
                'approval' => false,
            ],

            // Communications
            [
                'key' => 'sms-gateway',
                'name' => 'SMS Gateway Integration',
                'description' => 'Send bulk SMS notifications to students, parents, and staff',
                'monthly' => 40,
                'annual' => 400,
                'category' => 'communications',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'email-campaigns',
                'name' => 'Email Marketing Campaigns',
                'description' => 'Advanced email templates, bulk mailing, and campaign tracking',
                'monthly' => 30,
                'annual' => 300,
                'category' => 'communications',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'whatsapp-integration',
                'name' => 'WhatsApp Business Integration',
                'description' => 'Send notifications and messages via WhatsApp Business API',
                'monthly' => 60,
                'annual' => 600,
                'category' => 'communications',
                'status' => 'beta',
                'approval' => true,
            ],

            // Integrations
            [
                'key' => 'google-classroom',
                'name' => 'Google Classroom Sync',
                'description' => 'Automatically sync classes, assignments, and grades with Google Classroom',
                'monthly' => 25,
                'annual' => 250,
                'category' => 'integrations',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'zoom-integration',
                'name' => 'Zoom Meeting Integration',
                'description' => 'Schedule and manage Zoom meetings directly from iSoftro portal',
                'monthly' => 35,
                'annual' => 350,
                'category' => 'integrations',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'microsoft-teams',
                'name' => 'Microsoft Teams Integration',
                'description' => 'Integration with Microsoft Teams for seamless collaboration',
                'monthly' => 35,
                'annual' => 350,
                'category' => 'integrations',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'google-meet',
                'name' => 'Google Meet Integration',
                'description' => 'Schedule and launch Google Meet sessions from iSoftro',
                'monthly' => 20,
                'annual' => 200,
                'category' => 'integrations',
                'status' => 'active',
                'approval' => false,
            ],

            // Automation
            [
                'key' => 'workflow-automation',
                'name' => 'Workflow Automation Engine',
                'description' => 'Create custom workflows and automate repetitive tasks with triggers and actions',
                'monthly' => 55,
                'annual' => 550,
                'category' => 'automation',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'api-access',
                'name' => 'Advanced API Access',
                'description' => 'Premium API limits (100k calls/month), webhook support, and custom integrations',
                'monthly' => 80,
                'annual' => 800,
                'category' => 'automation',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'webhooks-premium',
                'name' => 'Premium Webhooks & Events',
                'description' => 'Custom webhooks, event streaming, and real-time notifications',
                'monthly' => 45,
                'annual' => 450,
                'category' => 'automation',
                'status' => 'active',
                'approval' => false,
            ],

            // Compliance & Security
            [
                'key' => 'advanced-security',
                'name' => 'Advanced Security Package',
                'description' => 'IP whitelisting, 2FA enforcement, SSO integration, and advanced audit logs',
                'monthly' => 70,
                'annual' => 700,
                'category' => 'compliance',
                'status' => 'active',
                'approval' => true,
            ],
            [
                'key' => 'gdpr-compliance',
                'name' => 'GDPR Compliance Suite',
                'description' => 'Data privacy tools, consent management, and compliance audit reports',
                'monthly' => 50,
                'annual' => 500,
                'category' => 'compliance',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'backup-recovery',
                'name' => 'Premium Backup & Recovery',
                'description' => '24-hour backup frequency, disaster recovery, and point-in-time restore',
                'monthly' => 45,
                'annual' => 450,
                'category' => 'compliance',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'data-encryption',
                'name' => 'End-to-End Data Encryption',
                'description' => 'AES-256 encryption at rest and in transit with key management',
                'monthly' => 60,
                'annual' => 600,
                'category' => 'compliance',
                'status' => 'active',
                'approval' => true,
            ],

            // Support
            [
                'key' => 'priority-support',
                'name' => 'Priority Support',
                'description' => '24/7 priority email and phone support with 2-hour response time',
                'monthly' => 100,
                'annual' => 1000,
                'category' => 'support',
                'status' => 'active',
                'approval' => false,
            ],
            [
                'key' => 'dedicated-manager',
                'name' => 'Dedicated Account Manager',
                'description' => 'Dedicated manager for onboarding, training, and optimization',
                'monthly' => 200,
                'annual' => 2000,
                'category' => 'support',
                'status' => 'active',
                'approval' => true,
            ],
            [
                'key' => 'custom-training',
                'name' => 'Custom Training Program',
                'description' => 'Customized training sessions for your staff (10 sessions)',
                'monthly' => 150,
                'annual' => 1500,
                'category' => 'support',
                'status' => 'active',
                'approval' => true,
            ],
        ];

        foreach ($addons as $addon) {
            DB::table('addon_features')->updateOrInsert(
                ['addon_key' => $addon['key']],
                [
                    'addon_name' => $addon['name'],
                    'description' => $addon['description'],
                    'monthly_price' => $addon['monthly'],
                    'annual_price' => $addon['annual'],
                    'category' => $addon['category'],
                    'status' => $addon['status'],
                    'requires_approval' => $addon['approval'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. SET UP ADD-ON REQUIREMENTS
        $addonRequirements = [
            // Advanced analytics requires at least Growth plan
            [
                'addon_key' => 'advanced-analytics',
                'type' => 'requires_plan',
                'value' => 'growth',
                'reason' => 'Only available for Growth plan and above'
            ],
            // BI Dashboard requires Growth plan
            [
                'addon_key' => 'bi-dashboard',
                'type' => 'requires_plan',
                'value' => 'growth',
                'reason' => 'Only available for Growth plan and above'
            ],
            // Dedicated manager requires Growth plan
            [
                'addon_key' => 'dedicated-manager',
                'type' => 'requires_plan',
                'value' => 'growth',
                'reason' => 'Only available for Growth plan and above'
            ],
            // Advanced security requires Enterprise plan
            [
                'addon_key' => 'advanced-security',
                'type' => 'requires_plan',
                'value' => 'enterprise',
                'reason' => 'Only available for Enterprise plan'
            ],
        ];

        foreach ($addonRequirements as $req) {
            $addonId = DB::table('addon_features')
                ->where('addon_key', $req['addon_key'])
                ->value('id');

            if ($addonId) {
                DB::table('addon_requirements')->updateOrInsert(
                    ['addon_id' => $addonId, 'requirement_key' => $req['value']],
                    [
                        'requirement_type' => $req['type'],
                        'reason' => $req['reason'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        echo "[✓] Add-on features seeded successfully\n";
    }
}
