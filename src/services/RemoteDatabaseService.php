<?php

namespace App\Services;

/**
 * Remote Database Connection Service
 * Optimized for external database hosting with connection pooling and caching
 */
class RemoteDatabaseService 
{
    private static $connections = [];
    private static $connectionPool = [];
    private static $maxConnections = 10;
    private static $connectionTimeout = 30;
    private static $queryCache = [];
    private static $cacheService;

    /**
     * Initialize the service with caching
     */
    public static function init() 
    {
        // Use Redis for query caching if available, fallback to APCu/file
        $cacheBackend = ExtensionService::getCacheBackend();
        
        switch ($cacheBackend) {
            case 'redis':
                self::$cacheService = new RedisCacheService();
                break;
            case 'apcu':
                self::$cacheService = new ApcuCacheService();
                break;
            default:
                self::$cacheService = new FileCacheService();
        }
    }

    /**
     * Get optimized database connection for remote hosting
     */
    public static function getConnection(string $host, string $database, string $username, string $password): ?\PDO 
    {
        $connectionKey = md5($host . $database . $username);
        
        // Check existing connections
        if (isset(self::$connections[$connectionKey])) {
            $connection = self::$connections[$connectionKey];
            
            // Verify connection is still alive
            try {
                $connection->query('SELECT 1');
                return $connection;
            } catch (\PDOException $e) {
                // Connection died, remove it
                unset(self::$connections[$connectionKey]);
            }
        }

        // Create new connection with remote database optimizations
        try {
            $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_PERSISTENT => true, // Connection pooling
                \PDO::ATTR_TIMEOUT => self::$connectionTimeout,
                
                // MySQL-specific optimizations for remote connections
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                \PDO::MYSQL_ATTR_COMPRESS => true, // Compress data over network
            ];

            $connection = new \PDO($dsn, $username, $password, $options);
            
            // Additional optimizations for remote database
            $connection->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $connection->exec("SET SESSION time_zone = '+00:00'");
            $connection->exec("SET SESSION wait_timeout = 300");
            $connection->exec("SET SESSION interactive_timeout = 300");
            
            self::$connections[$connectionKey] = $connection;
            return $connection;
            
        } catch (\PDOException $e) {
            error_log("Remote database connection failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute cached query for frequently accessed data
     */
    public static function cachedQuery(string $sql, array $params = [], int $ttl = 300): ?array 
    {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        // Check cache first
        $cached = self::$cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Execute query
        $connection = self::getConnection(
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );

        if (!$connection) {
            return null;
        }

        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // Cache the result
            self::$cacheService->set($cacheKey, $result, $ttl);
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Remote database query failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Invalidate cache for specific tables
     */
    public static function invalidateCache(string $table): void 
    {
        $pattern = 'query_*' . $table . '*';
        self::$cacheService->deletePattern($pattern);
    }

    /**
     * Get connection statistics
     */
    public static function getConnectionStats(): array 
    {
        return [
            'active_connections' => count(self::$connections),
            'max_connections' => self::$maxConnections,
            'cache_backend' => ExtensionService::getCacheBackend(),
            'cache_stats' => self::$cacheService->getStats(),
        ];
    }

    /**
     * Close all connections (for graceful shutdown)
     */
    public static function closeAllConnections(): void 
    {
        self::$connections = [];
        self::$connectionPool = [];
    }

    /**
     * Test remote database connectivity
     */
    public static function testConnection(): array 
    {
        $startTime = microtime(true);
        
        $connection = self::getConnection(
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Connection failed',
                'latency' => null,
            ];
        }

        try {
            // Test query
            $stmt = $connection->query('SELECT VERSION() as version, NOW() as server_time');
            $result = $stmt->fetch();
            
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'latency' => $latency . 'ms',
                'server_version' => $result['version'],
                'server_time' => $result['server_time'],
                'connection_compressed' => $connection->getAttribute(\PDO::MYSQL_ATTR_COMPRESS),
            ];
            
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'latency' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
            ];
        }
    }
}

/**
 * Redis Cache Service for remote database optimization
 */
class RedisCacheService 
{
    private $redis;
    
    public function __construct() 
    {
        if (ExtensionService::isAvailable('redis')) {
            $this->redis = new \Redis();
            $this->redis->connect('127.0.0.1', 6379);
            $this->redis->select(1); // Use database 1 for query cache
        }
    }
    
    public function get(string $key) 
    {
        if (!$this->redis) return null;
        
        $data = $this->redis->get($key);
        return $data ? unserialize($data) : null;
    }
    
    public function set(string $key, $value, int $ttl = 300): bool 
    {
        if (!$this->redis) return false;
        
        return $this->redis->setex($key, $ttl, serialize($value));
    }
    
    public function deletePattern(string $pattern): void 
    {
        if (!$this->redis) return;
        
        $keys = $this->redis->keys($pattern);
        if ($keys) {
            $this->redis->del($keys);
        }
    }
    
    public function getStats(): array 
    {
        if (!$this->redis) return [];
        
        $info = $this->redis->info();
        return [
            'used_memory' => $info['used_memory_human'] ?? 'unknown',
            'connected_clients' => $info['connected_clients'] ?? 0,
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
        ];
    }
}

/**
 * APCu Cache Service (fallback)
 */
class ApcuCacheService 
{
    public function get(string $key) 
    {
        if (!ExtensionService::isAvailable('apcu')) return null;
        
        $success = false;
        $data = apcu_fetch($key, $success);
        return $success ? $data : null;
    }
    
    public function set(string $key, $value, int $ttl = 300): bool 
    {
        if (!ExtensionService::isAvailable('apcu')) return false;
        
        return apcu_store($key, $value, $ttl);
    }
    
    public function deletePattern(string $pattern): void 
    {
        if (!ExtensionService::isAvailable('apcu')) return;
        
        // APCu doesn't support pattern deletion, would need to iterate
        // For now, just clear all cache
        apcu_clear_cache();
    }
    
    public function getStats(): array 
    {
        if (!ExtensionService::isAvailable('apcu')) return [];
        
        $info = apcu_cache_info();
        return [
            'memory_size' => $info['memory_type'] ?? 'unknown',
            'num_entries' => $info['num_entries'] ?? 0,
            'hits' => $info['num_hits'] ?? 0,
            'misses' => $info['num_misses'] ?? 0,
        ];
    }
}

/**
 * File Cache Service (final fallback)
 */
class FileCacheService 
{
    private $cacheDir;
    
    public function __construct() 
    {
        $this->cacheDir = __DIR__ . '/../../storage/cache/query/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get(string $key) 
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) return null;
        
        $data = unserialize(file_get_contents($file));
        
        // Check TTL
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, $value, int $ttl = 300): bool 
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public function deletePattern(string $pattern): void 
    {
        // Simple implementation - delete all cache files
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    public function getStats(): array 
    {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'cache_files' => count($files),
            'total_size' => round($totalSize / 1024, 2) . ' KB',
        ];
    }
}
