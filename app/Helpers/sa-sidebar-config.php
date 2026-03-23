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
                    'icon' => 'fa-house',
                    'permission' => 'dashboard.view',
                    'feature' => 'dashboard',
                ],
                [
                    'id' => 'tenants',
                    'label' => 'Tenant Management',
                    'icon' => 'fa-building',
                    'permission' => 'tenants.view',
                    'feature' => 'tenants',
                    'sub' => [
                        ['id' => 'all', 'l' => 'All Institutes', 'icon' => 'fa-list'],
                        ['id' => 'add', 'l' => 'Add New Institute', 'icon' => 'fa-plus-circle'],
                        ['id' => 'suspended', 'l' => 'Suspended Institutes', 'icon' => 'fa-ban'],
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
                    'icon' => 'fa-clipboard-list',
                    'permission' => 'plans.view',
                    'feature' => 'plans',
                    'sub' => [
                        ['id' => 'sub-plans', 'l' => 'Subscription Plans', 'icon' => 'fa-tags'],
                        ['id' => 'flags', 'l' => 'Feature Flags', 'icon' => 'fa-toggle-on'],
                        ['id' => 'assign', 'l' => 'Plan Assignment', 'icon' => 'fa-user-tag'],
                    ]
                ],
                [
                    'id' => 'revenue',
                    'label' => 'Revenue Analytics',
                    'icon' => 'fa-money-bill-wave',
                    'permission' => 'revenue.view',
                    'feature' => 'revenue',
                    'sub' => [
                        ['id' => 'mrr', 'l' => 'MRR / ARR Dashboard', 'icon' => 'fa-chart-line'],
                        ['id' => 'payments', 'l' => 'Payment History', 'icon' => 'fa-history'],
                        ['id' => 'invoices', 'l' => 'Invoice Generator', 'icon' => 'fa-file-invoice-dollar'],
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
                    'icon' => 'fa-chart-pie',
                    'permission' => 'analytics.view',
                    'feature' => 'analytics',
                    'sub' => [
                        ['id' => 'users', 'l' => 'Active Users', 'icon' => 'fa-users'],
                        ['id' => 'heatmap', 'l' => 'Feature Usage Heatmap', 'icon' => 'fa-fire'],
                        ['id' => 'sms', 'l' => 'SMS Credit Consumption', 'icon' => 'fa-comment-sms'],
                    ]
                ],
                [
                    'id' => 'support',
                    'label' => 'Support Tickets',
                    'icon' => 'fa-ticket',
                    'permission' => 'support.view',
                    'feature' => 'support',
                    'sub' => [
                        ['id' => 'open', 'l' => 'Open Tickets', 'icon' => 'fa-envelope-open-text'],
                        ['id' => 'impersonate', 'l' => 'Tenant Impersonation Log', 'icon' => 'fa-user-secret'],
                        ['id' => 'resolved', 'l' => 'Resolved History', 'icon' => 'fa-check-double'],
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
                    'icon' => 'fa-wrench',
                    'permission' => 'system.view',
                    'feature' => 'system',
                    'sub' => [
                        ['id' => 'toggles', 'l' => 'Feature Toggles', 'icon' => 'fa-toggle-off'],
                        ['id' => 'maintenance', 'l' => 'Maintenance Mode', 'icon' => 'fa-hammer'],
                        ['id' => 'push', 'l' => 'Push Announcements', 'icon' => 'fa-bullhorn'],
                    ]
                ],
                [
                    'id' => 'logs',
                    'label' => 'System Logs',
                    'icon' => 'fa-scroll',
                    'permission' => 'logs.view',
                    'feature' => 'logs',
                    'sub' => [
                        ['id' => 'audit', 'l' => 'Audit Logs', 'icon' => 'fa-shield-halved'],
                        ['id' => 'errors', 'l' => 'Error Logs', 'icon' => 'fa-bug'],
                        ['id' => 'api', 'l' => 'API Request Logs', 'icon' => 'fa-network-wired'],
                    ]
                ],
                [
                    'id' => 'settings',
                    'label' => 'Settings',
                    'icon' => 'fa-gear',
                    'permission' => 'settings.view',
                    'feature' => 'settings',
                    'sub' => [
                        ['id' => 'brand', 'l' => 'Platform Branding', 'icon' => 'fa-palette'],
                        ['id' => 'sms-tpl', 'l' => 'Default SMS Templates', 'icon' => 'fa-message'],
                        ['id' => 'email', 'l' => 'Email Config', 'icon' => 'fa-at'],
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