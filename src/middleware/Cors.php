<?php

namespace App\Middleware;

use Core\Response;

/**
 * CORS Middleware
 * Handles Cross-Origin Resource Sharing headers
 */
class Cors
{
    public function handle()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $allowedOrigins = $config['security']['allowed_origins'];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Set CORS headers
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 hours
        
        // Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
