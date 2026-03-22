<?php
/**
 * Student Portal Sidebar
 * Used by the SPA student portal
 */

// Student navigation config - will be used by JS
$studentNavConfig = [
    [
        'section' => 'Overview',
        'items' => [
            ['id' => 'dashboard', 'icon' => 'fa-house', 'label' => 'Dashboard']
        ]
    ],
    [
        'section' => 'Academic',
        'items' => [
            [
                'id' => 'academics', 
                'icon' => 'fa-graduation-cap', 
                'label' => 'Academic Info',
                'sub' => [
                    ['id' => 'timetable', 'l' => 'My Timetable'],
                    ['id' => 'attendance', 'l' => 'Attendance'],
                    ['id' => 'leave', 'l' => 'Apply Leave'],
                ]
            ],
        ]
    ],
    [
        'section' => 'Learning',
        'items' => [
            [
                'id' => 'learning',
                'icon' => 'fa-book-open',
                'label' => 'Knowledge Hub',
                'sub' => [
                    ['id' => 'materials', 'l' => 'Study Materials'],
                    ['id' => 'assignments', 'l' => 'Assignments'],
                    ['id' => 'classes', 'l' => 'Online Classes'],
                ]
            ],
        ]
    ],
    [
        'section' => 'Exams & Results',
        'items' => [
            [
                'id' => 'exams_results',
                'icon' => 'fa-award',
                'label' => 'Performance',
                'sub' => [
                    ['id' => 'exams', 'l' => 'Mock Exams'],
                    ['id' => 'results', 'l' => 'My Results'],
                    ['id' => 'leaderboard', 'l' => 'Leaderboard'],
                ]
            ],
        ]
    ],
    [
        'section' => 'Finance',
        'items' => [
            [
                'id' => 'finance',
                'icon' => 'fa-wallet',
                'label' => 'Payments',
                'sub' => [
                    ['id' => 'fees', 'l' => 'Fee Status'],
                    ['id' => 'receipts', 'l' => 'Receipts'],
                ]
            ],
        ]
    ],
    [
        'section' => 'Library',
        'items' => [
            ['id' => 'library', 'icon' => 'fa-book-reader', 'label' => 'My Books'],
        ]
    ],
    [
        'section' => 'Support',
        'items' => [
            ['id' => 'notices', 'icon' => 'fa-bullhorn', 'label' => 'Notices'],
            ['id' => 'contact', 'icon' => 'fa-headset', 'label' => 'Contact Admin'],
        ]
    ],
    [
        'section' => 'Profile',
        'items' => [
            [
                'id' => 'my_profile',
                'icon' => 'fa-user-circle',
                'label' => 'Account',
                'sub' => [
                    ['id' => 'profile', 'l' => 'My Profile'],
                    ['id' => 'password', 'l' => 'Change Password'],
                    ['id' => 'idcard', 'l' => 'Digital ID Card'],
                ]
            ],
        ]
    ],
];
?>
<!-- Sidebar content rendered via st-core.js -->
<div class="sb-body" id="sbBody"></div>
<script>window._ST_NAV_CONFIG = <?php echo json_encode($studentNavConfig); ?>;</script>
