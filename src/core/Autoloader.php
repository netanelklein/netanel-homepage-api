<?php

namespace Core;

/**
 * Simple autoloader for vanilla PHP application
 * Maps namespaces to file paths without Composer
 */
class Autoloader
{
    private $prefixes = [];
    
    public function __construct()
    {
        $this->addNamespace('Core', __DIR__);
        $this->addNamespace('Models', __DIR__ . '/../models');
        $this->addNamespace('Controllers', __DIR__ . '/../controllers');
        $this->addNamespace('Middleware', __DIR__ . '/../middleware');
        $this->addNamespace('Services', __DIR__ . '/../services');
        $this->addNamespace('Helpers', __DIR__ . '/../helpers');
    }
    
    /**
     * Register the autoloader
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Add a namespace and its base directory
     */
    public function addNamespace($prefix, $baseDir)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }
        
        array_push($this->prefixes[$prefix], $baseDir);
    }
    
    /**
     * Load the class file for a given class name
     */
    public function loadClass($class)
    {
        $prefix = $class;
        
        // Walk through the namespaces to find a matching file
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }
            
            $prefix = rtrim($prefix, '\\');
        }
        
        return false;
    }
    
    /**
     * Load the mapped file for a namespace prefix and relative class
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }
        
        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            
            if ($this->requireFile($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    /**
     * Require the file if it exists
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        
        return false;
    }
}
