<?php

namespace Core;

/**
 * Simple routing system for the API
 * Handles HTTP methods and URL patterns
 */
class Router
{
    private $routes = [];
    private $middleware = [];
    
    /**
     * Add a GET route
     */
    public function get($pattern, $handler, $middleware = [])
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }
    
    /**
     * Add a POST route
     */
    public function post($pattern, $handler, $middleware = [])
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }
    
    /**
     * Add a PUT route
     */
    public function put($pattern, $handler, $middleware = [])
    {
        $this->addRoute('PUT', $pattern, $handler, $middleware);
    }
    
    /**
     * Add a DELETE route
     */
    public function delete($pattern, $handler, $middleware = [])
    {
        $this->addRoute('DELETE', $pattern, $handler, $middleware);
    }
    
    /**
     * Add middleware to all routes
     */
    public function addGlobalMiddleware($middleware)
    {
        $this->middleware[] = $middleware;
    }
    
    /**
     * Add a route to the collection
     */
    private function addRoute($method, $pattern, $handler, $middleware = [])
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Handle the incoming request
     */
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove leading slash for consistency
        $uri = ltrim($uri, '/');
        
        // Set CORS headers
        $this->setCorsHeaders();
        
        // Handle OPTIONS requests for CORS
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Find matching route
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $uri)) {
                $params = $this->extractParams($route['pattern'], $uri);
                $this->executeRoute($route, $params);
                return;
            }
        }
        
        // No route found
        $this->sendNotFound();
    }
    
    /**
     * Check if route matches the request
     */
    private function matchRoute($route, $method, $uri)
    {
        if ($route['method'] !== $method) {
            return false;
        }
        
        $pattern = $this->convertPatternToRegex($route['pattern']);
        return preg_match($pattern, $uri);
    }
    
    /**
     * Convert route pattern to regex
     */
    private function convertPatternToRegex($pattern)
    {
        // Remove leading slash
        $pattern = ltrim($pattern, '/');
        
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);
        
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^\/]+)', $pattern);
        
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Extract parameters from URI
     */
    private function extractParams($pattern, $uri)
    {
        $regex = $this->convertPatternToRegex($pattern);
        preg_match($regex, $uri, $matches);
        
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }
    
    /**
     * Execute the matched route
     */
    private function executeRoute($route, $params)
    {
        try {
            // Execute global middleware
            foreach ($this->middleware as $middleware) {
                $this->executeMiddleware($middleware);
            }
            
            // Execute route-specific middleware
            foreach ($route['middleware'] as $middleware) {
                $this->executeMiddleware($middleware);
            }
            
            // Execute the handler
            if (is_string($route['handler'])) {
                $this->executeStringHandler($route['handler'], $params);
            } elseif (is_callable($route['handler'])) {
                call_user_func($route['handler'], $params);
            }
            
        } catch (Exception $e) {
            $this->sendError(500, 'Internal server error', $e->getMessage());
        }
    }
    
    /**
     * Execute middleware
     */
    private function executeMiddleware($middleware)
    {
        if (is_string($middleware)) {
            $middlewareClass = "\\Middleware\\{$middleware}";
            if (class_exists($middlewareClass)) {
                $instance = new $middlewareClass();
                $instance->handle();
            }
        } elseif (is_callable($middleware)) {
            call_user_func($middleware);
        }
    }
    
    /**
     * Execute string handler (Controller@method format)
     */
    private function executeStringHandler($handler, $params)
    {
        $parts = explode('@', $handler);
        if (count($parts) !== 2) {
            throw new Exception("Invalid handler format: {$handler}");
        }
        
        $controllerName = "\\Controllers\\{$parts[0]}";
        $methodName = $parts[1];
        
        if (!class_exists($controllerName)) {
            throw new Exception("Controller not found: {$controllerName}");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method not found: {$methodName}");
        }
        
        $controller->$methodName($params);
    }
    
    /**
     * Set CORS headers
     */
    private function setCorsHeaders()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $allowedOrigins = $config['security']['allowed_origins'];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json');
    }
    
    /**
     * Send 404 response
     */
    private function sendNotFound()
    {
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'Endpoint not found'
        ]);
        exit;
    }
    
    /**
     * Send error response
     */
    private function sendError($code, $message, $debug = null)
    {
        http_response_code($code);
        $response = [
            'error' => true,
            'message' => $message
        ];
        
        $config = require __DIR__ . '/../../config/app.php';
        if ($config['app']['debug'] && $debug) {
            $response['debug'] = $debug;
        }
        
        echo json_encode($response);
        exit;
    }
}
