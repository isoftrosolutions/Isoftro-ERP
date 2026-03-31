<?php
/**
 * Application Configuration
 */

return [
    'name' => env('APP_NAME', 'iSoftro Academic ERP'),
    'version' => '3.0.0',
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://isoftroerp.com'),
    'timezone' => 'Asia/Kathmandu',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    
    // JWT Configuration
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'algorithm' => 'HS256',
        'access_token_ttl' => 28800, // 8 hours in seconds
        'refresh_token_ttl' => 2592000, // 30 days in seconds
    ],
    
    // Security
    'hash_algo' => 'sha256',
    'session_lifetime' => 3600,
    'max_login_attempts' => 5,
    'login_lockout_time' => 900,
    
    // Pagination
    'per_page' => 20,
    'max_page_links' => 10,
    
    // File Upload
    'upload_path' => 'uploads/',
    'max_file_size' => 5242880, // 5MB
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
    
    // Roles
    'roles' => [
        'superadmin' => [
            'name' => 'Super Admin',
            'permissions' => ['*'],
            'color' => '#8141A5'
        ],
        'instituteadmin' => [
            'name' => 'Institute Admin',
            'permissions' => [
                'dashboard.view', 'dashboard.manage',
                'students.view', 'students.add', 'students.edit', 'students.delete',
                'teachers.view', 'teachers.add', 'teachers.edit', 'teachers.delete',
                'courses.view', 'courses.add', 'courses.edit', 'courses.delete',
                'attendance.view', 'attendance.mark',
                'exams.view', 'exams.add', 'exams.edit', 'exams.delete',
                'fees.view', 'fees.add', 'fees.edit', 'fees.delete',
                'reports.view', 'reports.export',
                'settings.view', 'settings.edit',
                'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expense_categories.view'
            ],
            'color' => '#00B894'
        ],
        'teacher' => [
            'name' => 'Teacher',
            'permissions' => [
                'dashboard.view',
                'attendance.view', 'attendance.mark',
                'exams.view', 'exams.add', 'exams.edit',
                'grades.view', 'grades.add', 'grades.edit',
                'students.view', 'reports.view'
            ],
            'color' => '#3B82F6'
        ],
        'student' => [
            'name' => 'Student',
            'permissions' => [
                'dashboard.view',
                'attendance.view', 'exams.view', 'grades.view',
                'timetable.view', 'fees.view'
            ],
            'color' => '#F59E0B'
        ],
        'guardian' => [
            'name' => 'Guardian',
            'permissions' => [
                'dashboard.view',
                'attendance.view', 'exams.view', 'grades.view',
                'timetable.view', 'fees.view'
            ],
            'color' => '#009E7E'
        ],
        'frontdesk' => [
            'name' => 'Front Desk',
            'permissions' => [
                'dashboard.view',
                'students.view', 'students.add', 'students.edit',
                'attendance.view', 'fees.view', 'fees.add',
                'reports.view'
            ],
            'color' => '#E11D48'
        ]
    ],
    
    // Plans
    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'price' => 1500,
            'student_limit' => 150,
            'admin_accounts' => 1,
            'sms_credits' => 500,
            'features' => ['attendance', 'fees', 'reports']
        ],
        'growth' => [
            'name' => 'Growth',
            'price' => 3500,
            'student_limit' => 500,
            'admin_accounts' => 3,
            'sms_credits' => 2000,
            'features' => ['attendance', 'fees', 'reports', 'exams', 'lms']
        ],
        'professional' => [
            'name' => 'Professional',
            'price' => 12000,
            'student_limit' => 1500,
            'admin_accounts' => 10,
            'sms_credits' => 5000,
            'features' => ['attendance', 'fees', 'reports', 'exams', 'lms', 'library']
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 25000,
            'student_limit' => -1, // unlimited
            'admin_accounts' => -1,
            'sms_credits' => -1, // unlimited
            'features' => ['attendance', 'fees', 'reports', 'exams', 'lms', 'library', 'api', 'white_label']
        ]
    ]
];
