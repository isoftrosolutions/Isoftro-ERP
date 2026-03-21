<?php
/**
 * Front Desk — Sidebar Configuration
 */

function getFDSidebarConfig() {
    return [
        [
            'section' => 'Overview',
            'items' => [
                [
                    'id' => 'dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'fa-th-large',
                    'module' => 'dashboard',
                ],
                [
                    'id' => 'attendance',
                    'label' => "Today's Attendance",
                    'icon' => 'fa-calendar-check',
                    'badge_key' => 'pending_attendance',
                    'module' => 'attendance',
                ],
            ],
        ],
        [
            'section' => 'Admissions',
            'items' => [
                [
                    'id' => 'admissions-adm-all',
                    'label' => 'Student Lookup',
                    'icon' => 'fa-user-graduate',
                    'module' => 'student',
                ],
                [
                    'id' => 'admissions-adm-form',
                    'label' => 'New Admission',
                    'icon' => 'fa-user-plus',
                    'module' => 'inquiry',
                ],
                [
                    'id' => 'alumni',
                    'label' => 'Alumni Directory',
                    'icon' => 'fa-graduation-cap',
                    'module' => 'student',
                ],
                [
                    'id' => 'operations-inq-list',
                    'label' => 'Inquiries',
                    'icon' => 'fa-comments',
                    'badge_key' => 'new_inquiries',
                    'module' => 'inquiry',
                ],
            ],
        ],
        [
            'section' => 'Fee & Finance',
            'items' => [
                [
                    'id' => 'fee-fee-coll',
                    'label' => 'Fee Collection',
                    'icon' => 'fa-money-bill-wave',
                    'module' => 'finance',
                ],
                [
                    'id' => 'transactions',
                    'label' => 'Transactions',
                    'icon' => 'fa-exchange-alt',
                    'module' => 'finance',
                ],
                [
                    'id' => 'pending-dues',
                    'label' => 'Pending Dues',
                    'icon' => 'fa-clock',
                    'badge_key' => 'outstanding_dues',
                    'module' => 'finance',
                ],
                [
                    'id' => 'receipts',
                    'label' => 'Receipts',
                    'icon' => 'fa-receipt',
                    'module' => 'finance',
                ],
            ],
        ],
        [
            'section' => 'Operations',
            'items' => [
                [
                    'id' => 'leave-requests',
                    'label' => 'Leave Requests',
                    'icon' => 'fa-user-clock',
                    'badge_key' => 'pending_leaves',
                    'module' => 'attendance',
                ],
                [
                    'id' => 'library',
                    'label' => 'Library Desk',
                    'icon' => 'fa-book',
                    'module' => 'library',
                ],
                [
                    'id' => 'timetable',
                    'label' => "Today's Timetable",
                    'icon' => 'fa-table',
                    'module' => 'academic',
                ],
                [
                    'id' => 'announcements',
                    'label' => 'Announcements',
                    'icon' => 'fa-bullhorn',
                    'badge_key' => 'new_announcements',
                    'module' => 'communication',
                ],
                [
                    'id' => 'qbank',
                    'label' => 'Question Bank',
                    'icon' => 'fa-database',
                    'module' => 'exams',
                ],
            ],
        ],
        [
            'section' => 'System',
            'items' => [
                [
                    'id' => 'support',
                    'label' => 'Support Tickets',
                    'icon' => 'fa-headset',
                    'module' => 'system',
                ],
                [
                    'id' => 'audit-log',
                    'label' => 'Activity Log',
                    'icon' => 'fa-shield-alt',
                    'module' => 'system',
                ],
            ],
        ],
    ];
}
