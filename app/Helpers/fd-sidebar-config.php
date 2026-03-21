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
                ],
                [
                    'id' => 'attendance',
                    'label' => "Today's Attendance",
                    'icon' => 'fa-calendar-check',
                    'badge_key' => 'pending_attendance',
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
                ],
                [
                    'id' => 'admissions-adm-form',
                    'label' => 'New Admission',
                    'icon' => 'fa-user-plus',
                ],
                [
                    'id' => 'alumni',
                    'label' => 'Alumni Directory',
                    'icon' => 'fa-graduation-cap',
                ],
                [
                    'id' => 'operations-inq-list',
                    'label' => 'Inquiries',
                    'icon' => 'fa-comments',
                    'badge_key' => 'new_inquiries',
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
                ],
                [
                    'id' => 'transactions',
                    'label' => 'Transactions',
                    'icon' => 'fa-exchange-alt',
                ],
                [
                    'id' => 'pending-dues',
                    'label' => 'Pending Dues',
                    'icon' => 'fa-clock',
                    'badge_key' => 'outstanding_dues',
                ],
                [
                    'id' => 'receipts',
                    'label' => 'Receipts',
                    'icon' => 'fa-receipt',
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
                ],
                [
                    'id' => 'library',
                    'label' => 'Library Desk',
                    'icon' => 'fa-book',
                ],
                [
                    'id' => 'timetable',
                    'label' => "Today's Timetable",
                    'icon' => 'fa-table',
                ],
                [
                    'id' => 'announcements',
                    'label' => 'Announcements',
                    'icon' => 'fa-bullhorn',
                    'badge_key' => 'new_announcements',
                ],
                [
                    'id' => 'qbank',
                    'label' => 'Question Bank',
                    'icon' => 'fa-database',
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
                ],
                [
                    'id' => 'audit-log',
                    'label' => 'Activity Log',
                    'icon' => 'fa-shield-alt',
                ],
            ],
        ],
    ];
}
