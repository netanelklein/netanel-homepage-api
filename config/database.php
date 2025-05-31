<?php

return [
    // Database table configurations
    'tables' => [
        'personal_info' => 'personal_info',
        'projects' => 'projects',
        'skills' => 'skills',
        'experience' => 'experience',
        'education' => 'education',
        'contact_messages' => 'contact_messages',
        'admin_users' => 'admin_users',
        'admin_logs' => 'admin_logs'
    ],
    
    // Connection settings
    'connections' => [
        'default' => 'mysql',
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_NAME'] ?? 'netanel_portfolio',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ]
    ],
    
    // Migration settings
    'migrations' => [
        'path' => __DIR__ . '/../database/migrations',
        'table' => 'migrations'
    ]
];
