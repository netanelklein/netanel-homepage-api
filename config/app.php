<?php

return [
    // Application settings
    'app' => [
        'name' => 'Netanel Klein Portfolio API',
        'version' => '1.0.0',
        'debug' => $_ENV['APP_DEBUG'] ?? true,
        'timezone' => 'UTC'
    ],
    
    // Database configuration
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'netanel_portfolio',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'port' => $_ENV['DB_PORT'] ?? 3306
    ],
    
    // Cache configuration
    'cache' => [
        'enabled' => $_ENV['CACHE_ENABLED'] ?? true,
        'driver' => 'file', // Only file cache for vanilla PHP
        'path' => __DIR__ . '/../storage/cache',
        'ttl' => 3600 // 1 hour default TTL
    ],
    
    // Security settings
    'security' => [
        'session_name' => 'NETANEL_API_SESSION',
        'session_lifetime' => 3600, // 1 hour
        'rate_limit' => [
            'contact_form' => 5, // 5 requests per minute
            'admin_login' => 3   // 3 attempts per 5 minutes
        ],
        'allowed_origins' => [
            'https://netanelk.com',
            'https://admin.netanelk.com',
            'http://localhost:3000', // For development
        ]
    ],
    
    // Logging configuration
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/logs',
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'max_files' => 30 // Keep 30 days of logs
    ],
    
    // File upload settings
    'uploads' => [
        'path' => __DIR__ . '/../storage/uploads',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf']
    ],
    
    // API settings
    'api' => [
        'version' => 'v1',
        'pagination' => [
            'default_limit' => 10,
            'max_limit' => 100
        ]
    ]
];
