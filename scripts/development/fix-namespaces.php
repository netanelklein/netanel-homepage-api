<?php
/**
 * Systematic Namespace Fixer
 * Analyzes and fixes namespace issues across the API codebase
 * 
 * Usage: php fix-namespaces.php
 */

define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

class NamespaceFixer 
{
    private $fixes = [];
    private $srcDir = __DIR__ . '/src';
    
    public function run() 
    {
        echo COLOR_BLUE . "🔧 Systematic Namespace Fixer\n";
        echo "==============================\n" . COLOR_RESET;
        
        $this->analyzeCurrentState();
        $this->planFixes();
        $this->applyFixes();
        $this->printSummary();
    }
    
    private function analyzeCurrentState() 
    {
        echo COLOR_YELLOW . "🔍 Analyzing current namespace state...\n" . COLOR_RESET;
        
        $files = $this->getAllPhpFiles();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $namespace = $this->extractNamespace($content);
            $uses = $this->extractUseStatements($content);
            $classes = $this->extractClassNames($content);
            
            $relativePath = str_replace($this->srcDir . '/', '', $file);
            
            echo "  📄 {$relativePath}\n";
            echo "     Namespace: {$namespace}\n";
            
            if (!empty($uses)) {
                echo "     Uses: " . implode(', ', $uses) . "\n";
            }
        }
        echo "\n";
    }
    
    private function planFixes() 
    {
        echo COLOR_YELLOW . "📋 Planning systematic fixes...\n" . COLOR_RESET;
        
        // Define the target namespace structure
        $namespaceMap = [
            'controllers' => 'App\\Controllers',
            'models' => 'App\\Models', 
            'services' => 'App\\Services',
            'middleware' => 'App\\Middleware',
            'core' => 'Core', // Keep core as is
        ];
        
        $files = $this->getAllPhpFiles();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->srcDir . '/', '', $file);
            $directory = dirname($relativePath);
            
            if (isset($namespaceMap[$directory])) {
                $targetNamespace = $namespaceMap[$directory];
                $currentNamespace = $this->extractNamespace($content);
                
                if ($currentNamespace !== $targetNamespace) {
                    $this->fixes[] = [
                        'type' => 'namespace',
                        'file' => $file,
                        'current' => $currentNamespace,
                        'target' => $targetNamespace
                    ];
                }
                
                // Check use statements that need updating
                $uses = $this->extractUseStatements($content);
                foreach ($uses as $use) {
                    $newUse = $this->mapOldNamespaceToNew($use, $namespaceMap);
                    if ($newUse !== $use) {
                        $this->fixes[] = [
                            'type' => 'use',
                            'file' => $file,
                            'current' => $use,
                            'target' => $newUse
                        ];
                    }
                }
            }
        }
        
        // Print planned fixes
        foreach ($this->fixes as $fix) {
            echo "  🔧 {$fix['type']}: " . basename($fix['file']) . "\n";
            echo "     {$fix['current']} → {$fix['target']}\n";
        }
        echo "\n";
    }
    
    private function applyFixes() 
    {
        echo COLOR_YELLOW . "⚙️  Applying fixes...\n" . COLOR_RESET;
        
        $fileChanges = [];
        
        // Group fixes by file
        foreach ($this->fixes as $fix) {
            $file = $fix['file'];
            if (!isset($fileChanges[$file])) {
                $fileChanges[$file] = [];
            }
            $fileChanges[$file][] = $fix;
        }
        
        foreach ($fileChanges as $file => $fixes) {
            $content = file_get_contents($file);
            $originalContent = $content;
            
            foreach ($fixes as $fix) {
                if ($fix['type'] === 'namespace') {
                    $content = preg_replace(
                        '/^namespace\s+' . preg_quote($fix['current'], '/') . '\s*;/m',
                        'namespace ' . $fix['target'] . ';',
                        $content
                    );
                } elseif ($fix['type'] === 'use') {
                    $content = preg_replace(
                        '/^use\s+' . preg_quote($fix['current'], '/') . '\s*;/m',
                        'use ' . $fix['target'] . ';',
                        $content
                    );
                }
            }
            
            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                echo "  ✅ Updated: " . basename($file) . "\n";
            }
        }
        
        // Update autoloader to include new namespaces
        $this->updateAutoloader();
        
        echo "\n";
    }
    
    private function updateAutoloader() 
    {
        echo COLOR_YELLOW . "🔄 Updating autoloader...\n" . COLOR_RESET;
        
        $autoloaderFile = $this->srcDir . '/core/Autoloader.php';
        $content = file_get_contents($autoloaderFile);
        
        // Check if App namespaces are already added
        if (strpos($content, 'App\\Models') === false) {
            $newNamespaces = "        // Add App namespace mappings
        \$this->addNamespace('App\\\\Controllers', __DIR__ . '/../controllers');
        \$this->addNamespace('App\\\\Models', __DIR__ . '/../models');
        \$this->addNamespace('App\\\\Services', __DIR__ . '/../services');
        \$this->addNamespace('App\\\\Middleware', __DIR__ . '/../middleware');
        \$this->addNamespace('App\\\\Core', __DIR__);
        \$this->addNamespace('Api\\\\Core', __DIR__);";
            
            $content = str_replace(
                "\$this->addNamespace('Helpers', __DIR__ . '/../helpers');",
                "\$this->addNamespace('Helpers', __DIR__ . '/../helpers');\n        \n" . $newNamespaces,
                $content
            );
            
            file_put_contents($autoloaderFile, $content);
            echo "  ✅ Updated autoloader\n";
        } else {
            echo "  ℹ️  Autoloader already updated\n";
        }
    }
    
    private function mapOldNamespaceToNew($oldNamespace, $namespaceMap) 
    {
        // Map old namespaces to new ones
        $mappings = [
            'Models\\' => 'App\\Models\\',
            'Controllers\\' => 'App\\Controllers\\',
            'Services\\' => 'App\\Services\\',
            'Middleware\\' => 'App\\Middleware\\',
            'Core\\Response' => 'Api\\Core\\Response',
        ];
        
        foreach ($mappings as $old => $new) {
            if (strpos($oldNamespace, $old) === 0) {
                return str_replace($old, $new, $oldNamespace);
            }
        }
        
        return $oldNamespace;
    }
    
    private function getAllPhpFiles() 
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->srcDir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    private function extractNamespace($content) 
    {
        if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    private function extractUseStatements($content) 
    {
        $uses = [];
        if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
            $uses = $matches[1];
        }
        return $uses;
    }
    
    private function extractClassNames($content) 
    {
        $classes = [];
        if (preg_match_all('/^(?:abstract\s+)?class\s+(\w+)/m', $content, $matches)) {
            $classes = $matches[1];
        }
        return $classes;
    }
    
    private function printSummary() 
    {
        echo COLOR_GREEN . "✅ Namespace fixes completed!\n";
        echo "=============================\n" . COLOR_RESET;
        echo "Total fixes applied: " . count($this->fixes) . "\n";
        echo "\nNext steps:\n";
        echo "1. Run: php test-all-endpoints.php\n";
        echo "2. Test individual endpoints manually\n";
        echo "3. Fix any remaining issues\n\n";
    }
}

// Run the fixer
if (php_sapi_name() === 'cli') {
    $fixer = new NamespaceFixer();
    $fixer->run();
} else {
    echo "This script should be run from the command line.\n";
    exit(1);
}
?>
