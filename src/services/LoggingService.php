<?php

namespace App\Services;

/**
 * Logging Service
 * Simple file-based logging for the API
 */
class LoggingService
{
    private $logPath;
    private $logLevel;
    private $maxFiles;
    
    // Log levels
    const EMERGENCY = 0;
    const ALERT = 1;
    const CRITICAL = 2;
    const ERROR = 3;
    const WARNING = 4;
    const NOTICE = 5;
    const INFO = 6;
    const DEBUG = 7;
    
    private $levels = [
        'emergency' => self::EMERGENCY,
        'alert' => self::ALERT,
        'critical' => self::CRITICAL,
        'error' => self::ERROR,
        'warning' => self::WARNING,
        'notice' => self::NOTICE,
        'info' => self::INFO,
        'debug' => self::DEBUG
    ];
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->logPath = $config['logging']['path'];
        $this->logLevel = $this->levels[$config['logging']['level']] ?? self::INFO;
        $this->maxFiles = $config['logging']['max_files'];
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    /**
     * Log emergency message
     */
    public function emergency($message, $context = [])
    {
        $this->log('emergency', $message, $context);
    }
    
    /**
     * Log alert message
     */
    public function alert($message, $context = [])
    {
        $this->log('alert', $message, $context);
    }
    
    /**
     * Log critical message
     */
    public function critical($message, $context = [])
    {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = [])
    {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = [])
    {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Log notice message
     */
    public function notice($message, $context = [])
    {
        $this->log('notice', $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = [])
    {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = [])
    {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Log message with level
     */
    public function log($level, $message, $context = [])
    {
        // Check if we should log this level
        if (!isset($this->levels[$level]) || $this->levels[$level] > $this->logLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Format log entry
        $logEntry = "[{$timestamp}] {$levelUpper}: {$message}";
        
        // Add context if provided
        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= PHP_EOL;
        
        // Write to daily log file
        $logFile = $this->getLogFile();
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Clean up old log files
        $this->cleanup();
    }
    
    /**
     * Get current log file path
     */
    private function getLogFile()
    {
        $date = date('Y-m-d');
        return $this->logPath . "/api-{$date}.log";
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup()
    {
        $files = glob($this->logPath . '/api-*.log');
        
        if (count($files) <= $this->maxFiles) {
            return;
        }
        
        // Sort files by modification time (oldest first)
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remove excess files
        $filesToRemove = array_slice($files, 0, count($files) - $this->maxFiles);
        
        foreach ($filesToRemove as $file) {
            unlink($file);
        }
    }
    
    /**
     * Get recent log entries
     */
    public function getRecentLogs($lines = 100)
    {
        $logFile = $this->getLogFile();
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $output = [];
        $file = new SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $output[] = $line;
            }
            $file->next();
        }
        
        return $output;
    }
    
    /**
     * Get log statistics
     */
    public function getStats()
    {
        $files = glob($this->logPath . '/api-*.log');
        $totalSize = 0;
        $totalLines = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $totalLines += count(file($file));
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_lines' => $totalLines,
            'log_path' => $this->logPath
        ];
    }
}
