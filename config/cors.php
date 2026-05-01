<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        'https://isoftroerp.com',
        'https://www.isoftroerp.com',
    ],
    'allowed_origins_patterns' => [
        '#^https://[a-z0-9-]+\.isoftroerp\.com$#',
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'],
    'exposed_headers' => ['X-CSRF-Token'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
