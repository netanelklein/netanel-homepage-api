<?php

namespace Services;

/**
 * File-based Cache Service
 * Simple caching implementation for vanilla PHP
 */
class CacheService
{
    private $cacheDir;
    private $defaultTtl;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->cacheDir = $config['cache']['path'];
        $this->defaultTtl = $config['cache']['ttl'];
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key)
    {
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (!$data || !isset($data['expires_at']) || !isset($data['value'])) {
            return null;
        }
        
        // Check if cache has expired
        if (time() > $data['expires_at']) {
            unlink($cacheFile);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTtl;
        $cacheFile = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($cacheFile, json_encode($data)) !== false;
    }
    
    /**
     * Delete cached value
     */
    public function delete($key)
    {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Check if key exists in cache and is not expired
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key)
    {
        $filename = md5($key) . '.cache';
        return $this->cacheDir . '/' . $filename;
    }
    
    /**
     * Clean expired cache files
     */
    public function cleanup()
    {
        $files = glob($this->cacheDir . '/*.cache');
        $currentTime = time();
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data || !isset($data['expires_at']) || $currentTime > $data['expires_at']) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $validFiles = 0;
        $expiredFiles = 0;
        $currentTime = time();
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = json_decode(file_get_contents($file), true);
            
            if ($data && isset($data['expires_at'])) {
                if ($currentTime <= $data['expires_at']) {
                    $validFiles++;
                } else {
                    $expiredFiles++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $validFiles,
            'expired_files' => $expiredFiles,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir
        ];
    }
}
