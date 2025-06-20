<?php
/**
 * Netanel Klein Portfolio API
 * Entry point for all API requests
 * 
 * @author Netanel Klein
 * @version 1.0.0
 */

// Start output buffering to prevent header issues
ob_start();

// Set error reporting for development (should be disabled in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Include the autoloader
require_once __DIR__ . '/src/core/Autoloader.php';

// Initialize autoloader
$autoloader = new \Core\Autoloader();
$autoloader->register();

// Include configuration
$config = require_once __DIR__ . '/config/app.php';

// Initialize the application
try {
    // Create router instance
    $router = new \Core\Router();
    
    // Load routes
    require_once __DIR__ . '/routes/api.php';
    
    // Handle the request
    $router->handleRequest();
    
} catch (Exception $e) {
    // Handle application errors
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'error' => true,
        'message' => 'Internal server error',
        'debug' => $config['debug'] ? $e->getMessage() : null
    ]);
}

// Clean output buffer
ob_end_flush();
?>
