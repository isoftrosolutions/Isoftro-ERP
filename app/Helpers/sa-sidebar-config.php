<?php
/**
 * Super Admin — Sidebar Configuration (Single Source of Truth)
 * 
 * This file defines the complete sidebar navigation structure for the
 * Super Admin role. Menu items are filtered server-side by permission
 * before being injected into the page.
 * 
 * Structure: Section → Items → Sub-items (optional → Children)
 * Each item can have: id, label, icon, permission, badge_key, sub (array), onclick
 */

function getSASidebarConfig()
{
    return [
        // ─── 1. MAIN ───
        [
            'section' => 'MAIN',
            'items' => [
                [
                    'id' => 'overview',
                    'label' => 'Overview',
                    'icon' => 'layout-dashboard',
                    'permission' => 'dashboard.view',
                    'feature' => 'dashboard',
                ],
                [
                    'id' => 'tenants',
                    'label' => 'Tenant Management',
                    'icon' => 'building-2',
                    'permission' => 'tenants.view',
                    'feature' => 'tenants',
                    'sub' => [
                        ['id' => 'all', 'l' => 'All Institutes', 'icon' => 'list'],
                        ['id' => 'add', 'l' => 'Add New Institute', 'icon' => 'plus-circle'],
                        ['id' => 'suspended', 'l' => 'Suspended Institutes', 'icon' => 'ban'],
                    ]
                ],
            ],
        ],

        // ─── 2. PLANNING & REVENUE ───
        [
            'section' => 'PLANNING & REVENUE',
            'items' => [
                [
                    'id' => 'plans',
                    'label' => 'Plan Management',
                    'icon' => 'clipboard-list',
                    'permission' => 'plans.view',
                    'feature' => 'plans',
                    'sub' => [
                        ['id' => 'sub-plans', 'l' => 'Subscription Plans', 'icon' => 'tags'],
                        ['id' => 'flags', 'l' => 'Feature Flags', 'icon' => 'toggle-right'],
                        ['id' => 'assign', 'l' => 'Plan Assignment', 'icon' => 'user-check'],
                    ]
                ],
                [
                    'id' => 'revenue',
                    'label' => 'Revenue Analytics',
                    'icon' => 'wallet',
                    'permission' => 'revenue.view',
                    'feature' => 'revenue',
                    'sub' => [
                        ['id' => 'mrr', 'l' => 'MRR / ARR Dashboard', 'icon' => 'line-chart'],
                        ['id' => 'payments', 'l' => 'Payment History', 'icon' => 'history'],
                        ['id' => 'invoices', 'l' => 'Invoice Generator', 'icon' => 'file-text'],
                    ]
                ],
            ],
        ],

        // ─── 3. PLATFORM ───
        [
            'section' => 'PLATFORM',
            'items' => [
                [
                    'id' => 'analytics',
                    'label' => 'Platform Analytics',
                    'icon' => 'pie-chart',
                    'permission' => 'analytics.view',
                    'feature' => 'analytics',
                    'sub' => [
                        ['id' => 'users', 'l' => 'Active Users', 'icon' => 'users'],
                        ['id' => 'heatmap', 'l' => 'Feature Usage Heatmap', 'icon' => 'flame'],
                        ['id' => 'sms', 'l' => 'SMS Credit Consumption', 'icon' => 'message-square'],
                    ]
                ],
                [
                    'id' => 'support',
                    'label' => 'Support Tickets',
                    'icon' => 'ticket',
                    'permission' => 'support.view',
                    'feature' => 'support',
                    'sub' => [
                        ['id' => 'open', 'l' => 'Open Tickets', 'icon' => 'mail-open'],
                        ['id' => 'impersonate', 'l' => 'Tenant Impersonation Log', 'icon' => 'user-cog'],
                        ['id' => 'resolved', 'l' => 'Resolved History', 'icon' => 'check-check'],
                    ]
                ],
            ],
        ],

        // ─── 4. SYSTEM ───
        [
            'section' => 'SYSTEM',
            'items' => [
                [
                    'id' => 'system',
                    'label' => 'System Configuration',
                    'icon' => 'wrench',
                    'permission' => 'system.view',
                    'feature' => 'system',
                    'sub' => [
                        ['id' => 'toggles', 'l' => 'Feature Toggles', 'icon' => 'toggle-left'],
                        ['id' => 'maintenance', 'l' => 'Maintenance Mode', 'icon' => 'hammer'],
                        ['id' => 'push', 'l' => 'Push Announcements', 'icon' => 'megaphone'],
                    ]
                ],
                [
                    'id' => 'logs',
                    'label' => 'System Logs',
                    'icon' => 'scroll-text',
                    'permission' => 'logs.view',
                    'feature' => 'logs',
                    'sub' => [
                        ['id' => 'audit', 'l' => 'Audit Logs', 'icon' => 'shield-check'],
                        ['id' => 'errors', 'l' => 'Error Logs', 'icon' => 'bug'],
                        ['id' => 'api', 'l' => 'API Request Logs', 'icon' => 'network'],
                    ]
                ],
                [
                    'id' => 'settings',
                    'label' => 'Settings',
                    'icon' => 'settings',
                    'permission' => 'settings.view',
                    'feature' => 'settings',
                    'sub' => [
                        ['id' => 'brand', 'l' => 'Platform Branding', 'icon' => 'palette'],
                        ['id' => 'sms-tpl', 'l' => 'Default SMS Templates', 'icon' => 'message-circle'],
                        ['id' => 'email', 'l' => 'Email Config', 'icon' => 'at-sign'],
                    ]
                ],
            ],
        ],
    ];
}

/**
 * Filter sidebar config by user permissions.
 * Returns only sections/items the current user is allowed to see.
 */
function filterSASidebarByPermission($config)
{
    $filtered = [];
    foreach ($config as $section) {
        $allowedItems = [];
        foreach ($section['items'] as $item) {
            $perm = $item['permission'] ?? 'dashboard.view';
            $feature = $item['feature'] ?? null;

            // Check both permission AND feature access for parent item
            if (!hasPermission($perm) || ($feature !== null && !hasFeature($feature))) {
                continue;
            }

            // Filter sub-items if they have their own permission/feature constraints
            if (!empty($item['sub'])) {
                $allowedSubs = [];
                foreach ($item['sub'] as $sub) {
                    $subPerm = $sub['permission'] ?? null;
                    $subFeature = $sub['feature'] ?? null;
                    // If sub-item has its own permission check, enforce it
                    if ($subPerm !== null && !hasPermission($subPerm)) {
                        continue;
                    }
                    // If sub-item has its own feature check, enforce it
                    if ($subFeature !== null && !hasFeature($subFeature)) {
                        continue;
                    }
                    $allowedSubs[] = $sub;
                }

                $item['sub'] = $allowedSubs;
                // If all children were filtered out, skip the parent too
                if (empty($allowedSubs)) {
                    continue;
                }
            }

            $allowedItems[] = $item;
        }
        if (!empty($allowedItems)) {
            $section['items'] = $allowedItems;
            $filtered[] = $section;
        }
    }
    return $filtered;
}
