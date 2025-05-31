<?php

namespace Services;

/**
 * Rate Limiting Service
 * Implements file-based rate limiting for API endpoints
 */
class RateLimitService
{
    private $cacheDir;
    private $limits;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->cacheDir = $config['cache']['path'] . '/rate_limits';
        $this->limits = $config['security']['rate_limit'];
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Check if request is within rate limits
     */
    public function checkLimit($ip, $endpoint)
    {
        $cacheKey = $this->getCacheKey($ip, $endpoint);
        $cacheFile = $this->getCacheFile($cacheKey);
        
        // Get limit for endpoint
        $limit = $this->getLimit($endpoint);
        $window = $this->getWindow($endpoint);
        
        // Read existing data
        $data = $this->readCacheFile($cacheFile);
        
        // Check if within time window
        $currentTime = time();
        if ($data && ($currentTime - $data['timestamp']) > $window) {
            // Reset counter if window expired
            $data = null;
        }
        
        // Check limit
        if ($data && $data['count'] >= $limit) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Record a request
     */
    public function recordRequest($ip, $endpoint)
    {
        $cacheKey = $this->getCacheKey($ip, $endpoint);
        $cacheFile = $this->getCacheFile($cacheKey);
        
        // Read existing data
        $data = $this->readCacheFile($cacheFile);
        
        $currentTime = time();
        $window = $this->getWindow($endpoint);
        
        // Initialize or update counter
        if (!$data || ($currentTime - $data['timestamp']) > $window) {
            $data = [
                'count' => 1,
                'timestamp' => $currentTime,
                'first_request' => $currentTime
            ];
        } else {
            $data['count']++;
        }
        
        // Write to cache
        $this->writeCacheFile($cacheFile, $data);
    }
    
    /**
     * Get rate limit for endpoint
     */
    private function getLimit($endpoint)
    {
        return $this->limits[$endpoint] ?? 10; // Default 10 requests
    }
    
    /**
     * Get time window for endpoint (in seconds)
     */
    private function getWindow($endpoint)
    {
        $windows = [
            'contact_form' => 60,    // 1 minute
            'admin_login' => 300,    // 5 minutes
            'general' => 60          // 1 minute default
        ];
        
        return $windows[$endpoint] ?? 60;
    }
    
    /**
     * Generate cache key
     */
    private function getCacheKey($ip, $endpoint)
    {
        return md5($ip . '_' . $endpoint);
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($cacheKey)
    {
        return $this->cacheDir . '/' . $cacheKey . '.json';
    }
    
    /**
     * Read cache file
     */
    private function readCacheFile($cacheFile)
    {
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $content = file_get_contents($cacheFile);
        return json_decode($content, true);
    }
    
    /**
     * Write cache file
     */
    private function writeCacheFile($cacheFile, $data)
    {
        file_put_contents($cacheFile, json_encode($data));
    }
    
    /**
     * Clean expired cache files
     */
    public function cleanup()
    {
        $files = glob($this->cacheDir . '/*.json');
        $currentTime = time();
        
        foreach ($files as $file) {
            $data = $this->readCacheFile($file);
            if ($data && ($currentTime - $data['timestamp']) > 3600) { // 1 hour
                unlink($file);
            }
        }
    }
}
