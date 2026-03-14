<?php
/**
 * Institute Admin — Sidebar Configuration (Single Source of Truth)
 * 
 * This file defines the complete sidebar navigation structure for the
 * Institute Admin role. Menu items are filtered server-side by permission
 * before being injected into the page.
 *
 * Structure: Section → Items → Sub-items (optional → Children)
 * Each item can have: id, label, icon, permission, badge_key, sub (array), onclick
 */

function getIASidebarConfig()
{
    return [
        // ─── 1. DASHBOARD ───
        [
            'section' => 'MAIN',
            'items' => [
                [
                    'id' => 'overview',
                    'label' => 'Dashboard',
                    'icon' => 'fa-house',
                    'permission' => 'dashboard.view',
                ],
            ],
        ],

        // ─── 2. ACADEMIC MANAGEMENT ───
        [
            'section' => 'ACADEMIC',
            'items' => [
                [
                    'id' => 'students',
                    'label' => 'Students',
                    'icon' => 'fa-user-graduate',
                    'permission' => 'students.view',
                    'badge_key' => 'total_students',
                    'sub' => [
                        ['id' => 'all', 'l' => 'All Students', 'icon' => 'fa-list'],
                        ['id' => 'add', 'l' => 'Add Student', 'icon' => 'fa-user-plus'],
                        ['id' => 'alumni', 'l' => 'Alumni Records', 'icon' => 'fa-user-tag'],
                        ['id' => 'vault', 'l' => 'Document Vault', 'icon' => 'fa-vault'],
                    ],
                ],
                [
                    'id' => 'teachers',
                    'label' => 'Teachers',
                    'icon' => 'fa-user-tie',
                    'permission' => 'teachers.view',
                    'sub' => [
                        ['id' => 'profiles', 'l' => 'Teacher List', 'icon' => 'fa-id-badge'],
                        ['id' => 'add', 'l' => 'Add Teacher', 'icon' => 'fa-user-plus'],
                        ['id' => 'allocation', 'l' => 'Subject Allocation', 'icon' => 'fa-book-open-reader'],
                        ['id' => 'performance', 'l' => 'Performance Analytics', 'icon' => 'fa-chart-simple'],
                    ],
                ],
                [
                    'id' => 'academic',
                    'label' => 'Courses & Batches',
                    'icon' => 'fa-book',
                    'permission' => 'courses.view',
                    'sub' => [
                        ['id' => 'courses', 'l' => 'Courses', 'icon' => 'fa-book-bookmark'],
                        ['id' => 'course-categories', 'l' => 'Course Categories', 'icon' => 'fa-folder-tree'],
                        ['id' => 'batches', 'l' => 'Batches', 'icon' => 'fa-layer-group'],
                        ['id' => 'subjects', 'l' => 'Subjects', 'icon' => 'fa-book'],
                        ['id' => 'allocation', 'l' => 'Subject Allocation', 'icon' => 'fa-users-rectangle'],
                        ['id' => 'timetable', 'l' => 'Timetable Builder', 'icon' => 'fa-calendar-plus'],
                        ['id' => 'calendar', 'l' => 'Academic Calendar', 'icon' => 'fa-calendar-days'],
                    ],
                ],
                [
                    'id' => 'attendance',
                    'label' => 'Attendance',
                    'icon' => 'fa-calendar-check',
                    'permission' => 'attendance.view',
                    'sub' => [
                        ['id' => 'take', 'l' => 'Mark Attendance', 'icon' => 'fa-user-check'],
                        ['id' => 'leave', 'l' => 'Leave Requests', 'icon' => 'fa-envelope-open-text'],
                        ['id' => 'report', 'l' => 'Reports', 'icon' => 'fa-chart-pie'],
                    ],
                ],
                [
                    'id' => 'exams',
                    'label' => 'Examinations',
                    'icon' => 'fa-file-signature',
                    'permission' => 'exams.view',
                    'sub' => [
                        ['id' => 'qbank', 'l' => 'Question Bank', 'icon' => 'fa-database'],
                        ['id' => 'create-ex', 'l' => 'Create Exam', 'icon' => 'fa-circle-plus'],
                        ['id' => 'schedule', 'l' => 'Exam Schedule', 'icon' => 'fa-calendar-week'],
                        ['id' => 'results', 'l' => 'Results & Rankings', 'icon' => 'fa-trophy'],
                    ],
                ],
                [
                    'id' => 'homework',
                    'label' => 'Homework',
                    'icon' => 'fa-clipboard-list',
                    'permission' => 'exams.view',
                    'sub' => [
                        ['id' => 'list', 'l' => 'All Homework', 'icon' => 'fa-list'],
                        ['id' => 'create', 'l' => 'Assign Homework', 'icon' => 'fa-plus'],
                    ],
                ],
            ],
        ],

        // ─── 3. ADMISSIONS & INQUIRIES ───
        [
            'section' => 'ADMISSIONS',
            'items' => [
                [
                    'id' => 'inq',
                    'label' => 'Inquiries',
                    'icon' => 'fa-magnifying-glass',
                    'permission' => 'students.view',
                    'badge_key' => 'new_inquiries',
                    'sub' => [
                        ['id' => 'list', 'l' => 'Inquiry List', 'icon' => 'fa-clipboard-list'],
                        ['id' => 'add-inq', 'l' => 'Add Inquiry', 'icon' => 'fa-user-plus'],
                        ['id' => 'inq-analytics', 'l' => 'Conversion Analytics', 'icon' => 'fa-chart-pie'],
                        ['id' => 'adm-form', 'l' => 'Admission Form', 'icon' => 'fa-id-card'],
                    ],
                ],
            ],
        ],

        // ─── 4. FINANCE MANAGEMENT ───
        [
            'section' => 'FINANCE',
            'items' => [
                [
                    'id' => 'fee',
                    'label' => 'Fee Collection',
                    'icon' => 'fa-hand-holding-dollar',
                    'permission' => 'dashboard.view',
                    'badge_key' => 'outstanding_count',
                    'sub' => [
                        ['id' => 'setup', 'l' => 'Fee Items Setup', 'icon' => 'fa-sliders'],
                        ['id' => 'plans', 'l' => 'Installment Plans', 'icon' => 'fa-calendar-check'],
                        ['id' => 'record', 'l' => 'Record Payment', 'icon' => 'fa-money-bill-wave'],
                        ['id' => 'outstanding', 'l' => 'Outstanding Dues', 'icon' => 'fa-clock', 'badge_key' => 'outstanding_count'],
                        ['id' => 'fin-reports', 'l' => 'Fee Reports', 'icon' => 'fa-receipt'],
                    ],
                ],
                [
                    'id' => 'staff-salary',
                    'label' => 'Staff Salary',
                    'icon' => 'fa-wallet',
                    'permission' => 'dashboard.view',
                ],
            ],
        ],

        // ─── 5. STAFF MANAGEMENT ───
        [
            'section' => 'STAFF',
            'items' => [
                [
                    'id' => 'frontdesk',
                    'label' => 'Front Desk',
                    'icon' => 'fa-headset',
                    'permission' => 'dashboard.view',
                    'sub' => [
                        ['id' => 'list', 'l' => 'Front Desk List', 'icon' => 'fa-list'],
                        ['id' => 'add', 'l' => 'Add Front Desk', 'icon' => 'fa-user-plus'],
                    ],
                ],
            ],
        ],

        // ─── 6. LMS & COMMUNICATION ───
        [
            'section' => 'LEARNING',
            'items' => [
                [
                    'id' => 'lms',
                    'label' => 'Study Materials',
                    'icon' => 'fa-book-open',
                    'permission' => 'dashboard.view',
                    'badge_key' => 'total_materials',
                    'sub' => [
                        ['id' => 'overview', 'l' => 'Materials Dashboard', 'icon' => 'fa-chart-pie'],
                        ['id' => 'materials', 'l' => 'All Materials', 'icon' => 'fa-layer-group'],
                        ['id' => 'upload', 'l' => 'Upload Material', 'icon' => 'fa-cloud-arrow-up'],
                        ['id' => 'categories', 'l' => 'Categories', 'icon' => 'fa-folder-tree'],
                        ['id' => 'videos', 'l' => 'Video Lectures', 'icon' => 'fa-video'],
                        ['id' => 'assignments', 'l' => 'Assignments', 'icon' => 'fa-list-check'],
                        ['id' => 'analytics', 'l' => 'Analytics & Reports', 'icon' => 'fa-chart-line'],
                    ],
                ],
                [
                    'id' => 'comms',
                    'label' => 'SMS / Notices',
                    'icon' => 'fa-bell',
                    'permission' => 'dashboard.view',
                    'sub' => [
                        ['id' => 'sms', 'l' => 'SMS Broadcast', 'icon' => 'fa-message'],
                        ['id' => 'email', 'l' => 'Email Campaigns', 'icon' => 'fa-envelope-open-text'],
                        ['id' => 'templates', 'l' => 'SMS Templates', 'icon' => 'fa-comment-dots'],
                        ['id' => 'msg-log', 'l' => 'Message Log', 'icon' => 'fa-clock-rotate-left'],
                    ],
                ],
                [
                    'id' => 'library',
                    'label' => 'Library',
                    'icon' => 'fa-book',
                    'permission' => 'dashboard.view',
                    'sub' => [
                        ['id' => 'catalog', 'l' => 'Book Catalog', 'icon' => 'fa-rectangle-list'],
                        ['id' => 'issue', 'l' => 'Issue / Return', 'icon' => 'fa-right-left'],
                        ['id' => 'overdue', 'l' => 'Overdue Tracking', 'icon' => 'fa-triangle-exclamation'],
                        ['id' => 'stock', 'l' => 'Stock Report', 'icon' => 'fa-boxes-stacked'],
                    ],
                ],
            ],
        ],

        // ─── 7. REPORTS & ANALYTICS ───
        [
            'section' => 'REPORTS',
            'items' => [
                [
                    'id' => 'reports',
                    'label' => 'Reports',
                    'icon' => 'fa-chart-column',
                    'permission' => 'reports.view',
                    'sub' => [
                        ['id' => 'fee-rep', 'l' => 'Fee Reports', 'icon' => 'fa-file-invoice-dollar'],
                        ['id' => 'att-rep', 'l' => 'Attendance Reports', 'icon' => 'fa-clipboard-user'],
                        ['id' => 'ex-rep', 'l' => 'Exam Reports', 'icon' => 'fa-square-poll-vertical'],
                        ['id' => 'inq-rep', 'l' => 'Inquiry Reports', 'icon' => 'fa-magnifying-glass-chart'],
                        ['id' => 'lms-rep', 'l' => 'LMS Analytics', 'icon' => 'fa-book-open-reader'],
                       
                    ],
                ],
            ],
        ],

        // ─── 8. INSTITUTE SETTINGS & SYSTEM ───
        [
            'section' => 'SYSTEM',
            'items' => [
                [
                    'id' => 'settings',
                    'label' => 'Settings',
                    'icon' => 'fa-gear',
                    'permission' => 'settings.view',
                    'sub' => [
                        ['id' => 'prof', 'l' => 'Institute Profile', 'icon' => 'fa-building'],
                        ['id' => 'email', 'l' => 'Email Configuration', 'icon' => 'fa-envelope-circle-check'],
                        ['id' => 'brand', 'l' => 'Branding', 'icon' => 'fa-palette'],
                        ['id' => 'rbac', 'l' => 'RBAC Config', 'icon' => 'fa-user-shield'],
                        ['id' => 'notif', 'l' => 'Notification Rules', 'icon' => 'fa-bell-concierge'],
                        ['id' => 'year', 'l' => 'Academic Year', 'icon' => 'fa-calendar-check'],
                    ],
                ],
                [
                    'id' => 'auditlogs',
                    'label' => 'Audit Logs',
                    'icon' => 'fa-shield-halved',
                    'permission' => 'settings.view',
                ],
                [
                    'id' => 'support',
                    'label' => 'Help & Support',
                    'icon' => 'fa-headset',
                    'permission' => 'dashboard.view',
                    'onclick' => "window.location.href='" . APP_URL . "/dash/admin/support'",
                ],
            ],
        ],
    ];
}

/**
 * Filter sidebar config by user permissions.
 * Returns only sections/items the current user is allowed to see.
 */
function filterIASidebarByPermission($config)
{
    $filtered = [];
    foreach ($config as $section) {
        $allowedItems = [];
        foreach ($section['items'] as $item) {
            $perm = $item['permission'] ?? 'dashboard.view';
            if (hasPermission($perm)) {
                $allowedItems[] = $item;
            }
        }
        if (!empty($allowedItems)) {
            $section['items'] = $allowedItems;
            $filtered[] = $section;
        }
    }
    return $filtered;
}
