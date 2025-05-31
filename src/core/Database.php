<?php

namespace Core;

use PDO;
use PDOException;
use App\Services\ExtensionService;

/**
 * Database connection and query builder
 * Enhanced for remote database hosting with caching and connection pooling
 */
class Database
{
    private static $instance = null;
    private $connection = null;
    private $config;
    private static $queryCache = [];
    private static $cacheService = null;
    private static $connectionStats = [
        'queries' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'connection_time' => 0
    ];
    
    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';
        $this->initializeCaching();
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Initialize caching system
     */
    private function initializeCaching()
    {
        if (self::$cacheService === null) {
            $cacheBackend = ExtensionService::getCacheBackend();
            
            switch ($cacheBackend) {
                case 'redis':
                    self::$cacheService = $this->initRedisCache();
                    break;
                case 'apcu':
                    self::$cacheService = 'apcu';
                    break;
                default:
                    self::$cacheService = 'file';
            }
        }
    }

    /**
     * Initialize Redis cache
     */
    private function initRedisCache()
    {
        if (ExtensionService::isAvailable('redis')) {
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->select(2); // Use database 2 for query cache
                return $redis;
            } catch (\Exception $e) {
                error_log("Redis connection failed: " . $e->getMessage());
                return 'file';
            }
        }
        return 'file';
    }
    
    /**
     * Get the PDO connection
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Connect to the database with remote optimization
     */
    private function connect()
    {
        try {
            $startTime = microtime(true);
            $config = $this->config['connections']['mysql'];
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            // Remote database optimization options
            $options = array_merge($config['options'], [
                PDO::ATTR_PERSISTENT => true, // Connection pooling
                PDO::ATTR_TIMEOUT => 30, // 30 second timeout
                PDO::MYSQL_ATTR_COMPRESS => true, // Compress data over network
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['charset']}_unicode_ci",
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]);
            
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
            
            // Additional remote database optimizations
            $this->connection->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $this->connection->exec("SET SESSION time_zone = '+00:00'");
            $this->connection->exec("SET SESSION wait_timeout = 300");
            $this->connection->exec("SET SESSION interactive_timeout = 300");
            
            self::$connectionStats['connection_time'] = microtime(true) - $startTime;
            
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a cached query for frequently accessed data
     */
    public function cachedQuery($sql, $params = [], $ttl = 300)
    {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        // Check cache first
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            self::$connectionStats['cache_hits']++;
            return $cached;
        }

        // Execute query
        self::$connectionStats['cache_misses']++;
        self::$connectionStats['queries']++;
        
        $result = $this->fetchAll($sql, $params);
        
        // Cache the result
        $this->setCache($cacheKey, $result, $ttl);
        
        return $result;
    }

    /**
     * Get data from cache
     */
    private function getFromCache($key)
    {
        switch (self::$cacheService) {
            case 'redis':
                if (is_object(self::$cacheService)) {
                    $data = self::$cacheService->get($key);
                    return $data ? unserialize($data) : null;
                }
                break;
                
            case 'apcu':
                if (ExtensionService::isAvailable('apcu')) {
                    $success = false;
                    $data = apcu_fetch($key, $success);
                    return $success ? $data : null;
                }
                break;
                
            case 'file':
                $cacheFile = __DIR__ . '/../../storage/cache/query/' . md5($key) . '.cache';
                if (file_exists($cacheFile)) {
                    $data = unserialize(file_get_contents($cacheFile));
                    if ($data['expires'] > time()) {
                        return $data['value'];
                    } else {
                        unlink($cacheFile);
                    }
                }
                break;
        }
        
        return null;
    }

    /**
     * Set data in cache
     */
    private function setCache($key, $value, $ttl = 300)
    {
        switch (self::$cacheService) {
            case 'redis':
                if (is_object(self::$cacheService)) {
                    self::$cacheService->setex($key, $ttl, serialize($value));
                }
                break;
                
            case 'apcu':
                if (ExtensionService::isAvailable('apcu')) {
                    apcu_store($key, $value, $ttl);
                }
                break;
                
            case 'file':
                $cacheDir = __DIR__ . '/../../storage/cache/query/';
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                
                $cacheFile = $cacheDir . md5($key) . '.cache';
                $data = [
                    'value' => $value,
                    'expires' => time() + $ttl,
                ];
                file_put_contents($cacheFile, serialize($data));
                break;
        }
    }

    /**
     * Invalidate cache for specific tables
     */
    public function invalidateCache($table)
    {
        switch (self::$cacheService) {
            case 'redis':
                if (is_object(self::$cacheService)) {
                    $keys = self::$cacheService->keys('query_*' . $table . '*');
                    if ($keys) {
                        self::$cacheService->del($keys);
                    }
                }
                break;
                
            case 'apcu':
                // APCu doesn't support pattern deletion, clear all
                if (ExtensionService::isAvailable('apcu')) {
                    apcu_clear_cache();
                }
                break;
                
            case 'file':
                $cacheDir = __DIR__ . '/../../storage/cache/query/';
                $files = glob($cacheDir . '*.cache');
                foreach ($files as $file) {
                    unlink($file);
                }
                break;
        }
    }
    
    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = [])
    {
        try {
            self::$connectionStats['queries']++;
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Test database connectivity and performance
     */
    public function testConnection()
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->getConnection()->query('SELECT VERSION() as version, NOW() as server_time');
            $result = $stmt->fetch();
            
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'latency' => $latency . 'ms',
                'server_version' => $result['version'],
                'server_time' => $result['server_time'],
                'connection_compressed' => $this->connection->getAttribute(PDO::MYSQL_ATTR_COMPRESS),
                'stats' => self::$connectionStats,
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'latency' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
                'stats' => self::$connectionStats,
            ];
        }
    }

    /**
     * Get connection statistics
     */
    public function getStats()
    {
        return [
            'queries_executed' => self::$connectionStats['queries'],
            'cache_hits' => self::$connectionStats['cache_hits'],
            'cache_misses' => self::$connectionStats['cache_misses'],
            'cache_hit_rate' => self::$connectionStats['cache_hits'] + self::$connectionStats['cache_misses'] > 0 
                ? round((self::$connectionStats['cache_hits'] / (self::$connectionStats['cache_hits'] + self::$connectionStats['cache_misses'])) * 100, 2) 
                : 0,
            'connection_time' => round(self::$connectionStats['connection_time'] * 1000, 2) . 'ms',
            'cache_backend' => is_object(self::$cacheService) ? 'redis' : self::$cacheService,
        ];
    }
    
    /**
     * Fetch all results
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single result
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Insert a record
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Update records
     */
    public function update($table, $data, $where, $params = [])
    {
        $setPairs = [];
        foreach (array_keys($data) as $column) {
            $setPairs[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setPairs);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $stmt = $this->query($sql, array_merge($data, $params));
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Get table name from config
     */
    public function table($name)
    {
        return $this->config['tables'][$name] ?? $name;
    }
}
