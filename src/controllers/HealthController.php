<?php

namespace App\Controllers;

use App\Core\Response;
use App\Services\RemoteDatabaseService;
use App\Services\ExtensionService;

/**
 * System Health Controller
 * Monitor API health, database connectivity, and performance
 */
class HealthController extends BaseController 
{
    /**
     * Basic health check
     */
    public function index(): void 
    {
        Response::json([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'api_version' => '1.0.0',
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
            ]
        ]);
    }

    /**
     * Detailed system status
     */
    public function status(): void 
    {
        $status = [
            'api' => [
                'status' => 'healthy',
                'version' => '1.0.0',
                'environment' => $_ENV['APP_ENV'] ?? 'production',
            ],
            'database' => $this->getDatabaseStatus(),
            'extensions' => ExtensionService::getCapabilities(),
            'cache' => $this->getCacheStatus(),
            'performance' => $this->getPerformanceMetrics(),
        ];

        Response::json($status);
    }

    /**
     * Database connectivity test
     */
    public function database(): void 
    {
        $dbStatus = $this->getDatabaseStatus();
        
        if ($dbStatus['connected']) {
            Response::json($dbStatus);
        } else {
            Response::json($dbStatus, 503);
        }
    }

    /**
     * Get database connection status
     */
    private function getDatabaseStatus(): array 
    {
        $connectionTest = RemoteDatabaseService::testConnection();
        $connectionStats = RemoteDatabaseService::getConnectionStats();
        
        return [
            'connected' => $connectionTest['success'],
            'host' => $_ENV['DB_HOST'] ?? 'not configured',
            'database' => $_ENV['DB_NAME'] ?? 'not configured',
            'latency' => $connectionTest['latency'] ?? null,
            'server_version' => $connectionTest['server_version'] ?? null,
            'server_time' => $connectionTest['server_time'] ?? null,
            'compression' => $connectionTest['connection_compressed'] ?? false,
            'error' => $connectionTest['error'] ?? null,
            'connection_stats' => $connectionStats,
        ];
    }

    /**
     * Get cache system status
     */
    private function getCacheStatus(): array 
    {
        $cacheBackend = ExtensionService::getCacheBackend();
        
        $status = [
            'backend' => $cacheBackend,
            'available' => true,
        ];

        // Get cache-specific stats
        switch ($cacheBackend) {
            case 'redis':
                if (ExtensionService::isAvailable('redis')) {
                    try {
                        $redis = new \Redis();
                        $redis->connect('127.0.0.1', 6379);
                        $info = $redis->info();
                        
                        $status['redis'] = [
                            'connected' => true,
                            'memory_used' => $info['used_memory_human'] ?? 'unknown',
                            'connected_clients' => $info['connected_clients'] ?? 0,
                            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                            'hit_rate' => $this->calculateHitRate($info),
                        ];
                    } catch (\Exception $e) {
                        $status['redis'] = [
                            'connected' => false,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
                break;
                
            case 'apcu':
                if (ExtensionService::isAvailable('apcu')) {
                    $info = apcu_cache_info();
                    $status['apcu'] = [
                        'memory_size' => $info['memory_type'] ?? 'unknown',
                        'num_entries' => $info['num_entries'] ?? 0,
                        'hits' => $info['num_hits'] ?? 0,
                        'misses' => $info['num_misses'] ?? 0,
                        'hit_rate' => $this->calculateHitRate($info, 'num_hits', 'num_misses'),
                    ];
                }
                break;
                
            case 'file':
                $cacheDir = __DIR__ . '/../../storage/cache/';
                $status['file'] = [
                    'cache_dir' => $cacheDir,
                    'writable' => is_writable($cacheDir),
                    'files' => count(glob($cacheDir . '*')),
                ];
                break;
        }

        return $status;
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array 
    {
        return [
            'memory' => [
                'current_usage' => memory_get_usage(true),
                'peak_usage' => memory_get_peak_usage(true),
                'limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
                'usage_percentage' => round((memory_get_usage(true) / $this->parseMemoryLimit(ini_get('memory_limit'))) * 100, 2),
            ],
            'server' => [
                'load_average' => $this->getLoadAverage(),
                'uptime' => $this->getUptime(),
                'disk_free' => disk_free_space('.'),
                'disk_total' => disk_total_space('.'),
            ],
            'opcache' => $this->getOpcacheStatus(),
        ];
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(array $info, string $hitKey = 'keyspace_hits', string $missKey = 'keyspace_misses'): float 
    {
        $hits = $info[$hitKey] ?? 0;
        $misses = $info[$missKey] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int 
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int) $limit;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Get server load average
     */
    private function getLoadAverage(): ?array 
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2],
            ];
        }
        
        return null;
    }

    /**
     * Get server uptime
     */
    private function getUptime(): ?int 
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            return (int) floatval($uptime);
        }
        
        return null;
    }

    /**
     * Get OPcache status
     */
    private function getOpcacheStatus(): ?array 
    {
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            
            if ($status) {
                return [
                    'enabled' => $status['opcache_enabled'],
                    'memory_usage' => $status['memory_usage'],
                    'statistics' => $status['opcache_statistics'],
                ];
            }
        }
        
        return null;
    }
}
