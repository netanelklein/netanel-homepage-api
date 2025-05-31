<?php

namespace Core;

/**
 * HTTP Request handler
 * Provides easy access to request data and headers
 */
class Request
{
    private $method;
    private $uri;
    private $headers;
    private $body;
    private $query;
    private $input;
    
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->headers = $this->getAllHeaders();
        $this->query = $_GET;
        $this->parseBody();
    }
    
    /**
     * Get request method
     */
    public function method()
    {
        return $this->method;
    }
    
    /**
     * Get request URI
     */
    public function uri()
    {
        return $this->uri;
    }
    
    /**
     * Get all headers
     */
    public function headers()
    {
        return $this->headers;
    }
    
    /**
     * Get specific header
     */
    public function header($name, $default = null)
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }
    
    /**
     * Get query parameters
     */
    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }
    
    /**
     * Get input data (POST/PUT body)
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->input;
        }
        
        return $this->input[$key] ?? $default;
    }
    
    /**
     * Get all input data
     */
    public function all()
    {
        return array_merge($this->query, $this->input);
    }
    
    /**
     * Check if input key exists
     */
    public function has($key)
    {
        return isset($this->input[$key]) || isset($this->query[$key]);
    }
    
    /**
     * Get only specified keys from input
     */
    public function only($keys)
    {
        $result = [];
        $all = $this->all();
        
        foreach ($keys as $key) {
            if (isset($all[$key])) {
                $result[$key] = $all[$key];
            }
        }
        
        return $result;
    }
    
    /**
     * Get IP address
     */
    public function ip()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get user agent
     */
    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjax()
    {
        return strtolower($this->header('X-Requested-With')) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is JSON
     */
    public function isJson()
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }
    
    /**
     * Parse request body
     */
    private function parseBody()
    {
        $this->input = [];
        
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            if ($this->isJson()) {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                $this->input = $jsonData ?: [];
            } else {
                $this->input = $_POST;
            }
        }
    }
    
    /**
     * Get all headers (compatible with different PHP versions)
     */
    private function getAllHeaders()
    {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                }
            }
        }
        
        // Convert keys to lowercase for consistency
        return array_change_key_case($headers, CASE_LOWER);
    }
}
