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
            ['id' => 'timetable', 'icon' => 'fa-calendar-alt', 'label' => 'My Timetable'],
            ['id' => 'attendance', 'icon' => 'fa-calendar-check', 'label' => 'Attendance'],
            ['id' => 'leave', 'icon' => 'fa-user-clock', 'label' => 'Apply Leave'],
        ]
    ],
    [
        'section' => 'Learning',
        'items' => [
            ['id' => 'materials', 'icon' => 'fa-book', 'label' => 'Study Materials'],
            ['id' => 'assignments', 'icon' => 'fa-tasks', 'label' => 'Assignments'],
            ['id' => 'classes', 'icon' => 'fa-video', 'label' => 'Online Classes'],
        ]
    ],
    [
        'section' => 'Exams & Results',
        'items' => [
            ['id' => 'exams', 'icon' => 'fa-file-alt', 'label' => 'Mock Exams'],
            ['id' => 'results', 'icon' => 'fa-trophy', 'label' => 'My Results'],
            ['id' => 'leaderboard', 'icon' => 'fa-medal', 'label' => 'Leaderboard'],
        ]
    ],
    [
        'section' => 'Finance',
        'items' => [
            ['id' => 'fees', 'icon' => 'fa-money-bill-wave', 'label' => 'Fee Status'],
            ['id' => 'receipts', 'icon' => 'fa-receipt', 'label' => 'Receipts'],
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
            ['id' => 'profile', 'icon' => 'fa-user-graduate', 'label' => 'My Profile'],
            ['id' => 'password', 'icon' => 'fa-key', 'label' => 'Change Password'],
            ['id' => 'idcard', 'icon' => 'fa-id-card', 'label' => 'Digital ID Card'],
        ]
    ],
];
?>
<!-- Sidebar content rendered via st-core.js -->
<div class="sb-body" id="sbBody"></div>
<script>window._ST_NAV_CONFIG = <?php echo json_encode($studentNavConfig); ?>;</script>
