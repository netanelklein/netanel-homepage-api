<?php
/**
 * Comprehensive API Endpoint Tester
 * Tests all defined endpoints and reports their status
 * 
 * Usage: php test-all-endpoints.php
 */

// Configuration
define('API_BASE_URL', 'http://localhost:8000');
define('TEST_TIMEOUT', 10); // seconds

// ANSI color codes for terminal output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

class EndpointTester 
{
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    /**
     * Define all endpoints to test
     */
    private function getEndpoints() 
    {
        return [
            // Health Check Routes
            'Health Check' => [
                'GET /api/health' => [
                    'url' => '/api/health',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => 'status',
                    'description' => 'Basic health check'
                ],
                'GET /api/health/status' => [
                    'url' => '/api/health/status',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => 'status',
                    'description' => 'Detailed health status'
                ],
                'GET /api/health/database' => [
                    'url' => '/api/health/database',
                    'method' => 'GET',
                    'expected_status' => [200, 500], // May fail if no DB
                    'expected_content' => null,
                    'description' => 'Database health check'
                ]
            ],
            
            // Portfolio Routes
            'Portfolio Data' => [
                'GET /api/portfolio/personal-info' => [
                    'url' => '/api/portfolio/personal-info',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => null,
                    'description' => 'Personal information'
                ],
                'GET /api/portfolio/projects' => [
                    'url' => '/api/portfolio/projects',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => null,
                    'description' => 'Project list'
                ],
                'GET /api/portfolio/skills' => [
                    'url' => '/api/portfolio/skills',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => null,
                    'description' => 'Skills list'
                ],
                'GET /api/portfolio/experience' => [
                    'url' => '/api/portfolio/experience',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => null,
                    'description' => 'Work experience'
                ],
                'GET /api/portfolio/education' => [
                    'url' => '/api/portfolio/education',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => null,
                    'description' => 'Education history'
                ]
            ],
            
            // CV Routes
            'CV Management' => [
                'GET /api/cv/download' => [
                    'url' => '/api/cv/download',
                    'method' => 'GET',
                    'expected_status' => [200, 404],
                    'expected_content' => null,
                    'description' => 'CV download'
                ],
                'GET /api/cv/stats' => [
                    'url' => '/api/cv/stats',
                    'method' => 'GET',
                    'expected_status' => 200,
                    'expected_content' => null,
                    'description' => 'CV statistics'
                ]
            ],
            
            // Contact Routes
            'Contact' => [
                'POST /api/contact/submit' => [
                    'url' => '/api/contact/submit',
                    'method' => 'POST',
                    'expected_status' => [200, 400, 422],
                    'expected_content' => null,
                    'description' => 'Contact form submission',
                    'data' => [
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'message' => 'Test message'
                    ]
                ]
            ],
            
            // Auth Routes
            'Authentication' => [
                'POST /api/auth/login' => [
                    'url' => '/api/auth/login',
                    'method' => 'POST',
                    'expected_status' => [200, 400, 401],
                    'expected_content' => null,
                    'description' => 'User login',
                    'data' => [
                        'username' => 'test',
                        'password' => 'test'
                    ]
                ],
                'GET /api/auth/verify' => [
                    'url' => '/api/auth/verify',
                    'method' => 'GET',
                    'expected_status' => [200, 401],
                    'expected_content' => null,
                    'description' => 'Token verification'
                ]
            ],
            
            // Admin Routes (will likely fail without auth)
            'Admin' => [
                'GET /api/admin/dashboard' => [
                    'url' => '/api/admin/dashboard',
                    'method' => 'GET',
                    'expected_status' => [200, 401],
                    'expected_content' => null,
                    'description' => 'Admin dashboard'
                ]
            ]
        ];
    }
    
    /**
     * Test a single endpoint
     */
    private function testEndpoint($endpoint_name, $config) 
    {
        $this->totalTests++;
        
        $url = API_BASE_URL . $config['url'];
        $method = $config['method'];
        $expected_status = is_array($config['expected_status']) 
            ? $config['expected_status'] 
            : [$config['expected_status']];
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => TEST_TIMEOUT,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false
        ]);
        
        // Add POST data if present
        if ($method === 'POST' && isset($config['data'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($config['data']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($config['data']))
            ]);
        }
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Parse response
        if ($response === false || $error) {
            $status = 'ERROR';
            $message = "cURL Error: " . $error;
            $this->failedTests++;
        } else {
            list($headers, $body) = explode("\r\n\r\n", $response, 2);
            
            // Check for PHP fatal errors or warnings in response body
            $hasFatalError = strpos($body, 'Fatal error') !== false;
            $hasWarning = strpos($body, 'Warning') !== false;
            $hasNotice = strpos($body, 'Notice') !== false;
            
            if ($hasFatalError) {
                $status = 'FAIL';
                $message = "HTTP {$http_code} - PHP Fatal Error detected";
                $this->failedTests++;
            } elseif ($hasWarning) {
                $status = 'WARN';
                $message = "HTTP {$http_code} - PHP Warning detected";
            } elseif (in_array($http_code, $expected_status)) {
                $status = 'PASS';
                $message = "HTTP {$http_code}";
                $this->passedTests++;
                
                // Check for expected content if specified
                if ($config['expected_content'] && strpos($body, $config['expected_content']) === false) {
                    $status = 'WARN';
                    $message = "HTTP {$http_code} but missing expected content: {$config['expected_content']}";
                }
            } else {
                $status = 'FAIL';
                $message = "HTTP {$http_code} (expected: " . implode('/', $expected_status) . ")";
                $this->failedTests++;
            }
        }
        
        // Store result
        $this->results[] = [
            'endpoint' => $endpoint_name,
            'url' => $config['url'],
            'method' => $method,
            'status' => $status,
            'message' => $message,
            'response_time' => $response_time,
            'description' => $config['description'],
            'body' => isset($body) ? $body : null
        ];
        
        return $status;
    }
    
    /**
     * Run all endpoint tests
     */
    public function runTests() 
    {
        echo COLOR_BLUE . "🧪 API Endpoint Comprehensive Test Suite\n";
        echo "========================================\n" . COLOR_RESET;
        echo "Testing API at: " . API_BASE_URL . "\n";
        echo "Timeout: " . TEST_TIMEOUT . " seconds\n\n";
        
        $endpoints = $this->getEndpoints();
        
        foreach ($endpoints as $category => $categoryEndpoints) {
            echo COLOR_BLUE . "📁 Testing {$category}:\n" . COLOR_RESET;
            echo str_repeat('-', 50) . "\n";
            
            foreach ($categoryEndpoints as $name => $config) {
                $status = $this->testEndpoint($name, $config);
                
                // Color code the output
                $color = '';
                $icon = '';
                switch ($status) {
                    case 'PASS':
                        $color = COLOR_GREEN;
                        $icon = '✅';
                        break;
                    case 'FAIL':
                        $color = COLOR_RED;
                        $icon = '❌';
                        break;
                    case 'WARN':
                        $color = COLOR_YELLOW;
                        $icon = '⚠️';
                        break;
                    case 'ERROR':
                        $color = COLOR_RED;
                        $icon = '💥';
                        break;
                }
                
                $result = end($this->results);
                printf("%s%s %-35s %s (%sms)%s\n", 
                    $color, 
                    $icon, 
                    $name, 
                    $result['message'],
                    $result['response_time'],
                    COLOR_RESET
                );
            }
            echo "\n";
        }
        
        $this->printSummary();
    }
    
    /**
     * Print test summary and analysis
     */
    private function printSummary() 
    {
        echo COLOR_BLUE . "📊 Test Summary:\n";
        echo "================\n" . COLOR_RESET;
        echo "Total Tests: {$this->totalTests}\n";
        echo COLOR_GREEN . "Passed: {$this->passedTests}\n" . COLOR_RESET;
        echo COLOR_RED . "Failed: {$this->failedTests}\n" . COLOR_RESET;
        echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 1) . "%\n\n";
        
        // Analysis of failures
        echo COLOR_BLUE . "🔍 Failure Analysis:\n";
        echo "====================\n" . COLOR_RESET;
        
        $errorTypes = [];
        foreach ($this->results as $result) {
            if ($result['status'] === 'FAIL' || $result['status'] === 'ERROR') {
                // Analyze the error
                if (strpos($result['body'], 'Fatal error') !== false) {
                    if (strpos($result['body'], 'Class') !== false && strpos($result['body'], 'not found') !== false) {
                        $errorTypes['namespace_issues'][] = $result;
                    } elseif (strpos($result['body'], 'Cannot redeclare') !== false) {
                        $errorTypes['class_redeclaration'][] = $result;
                    } else {
                        $errorTypes['other_fatal'][] = $result;
                    }
                } elseif (strpos($result['body'], 'Warning') !== false) {
                    $errorTypes['warnings'][] = $result;
                } else {
                    $errorTypes['other'][] = $result;
                }
            }
        }
        
        foreach ($errorTypes as $type => $errors) {
            echo COLOR_YELLOW . ucwords(str_replace('_', ' ', $type)) . ": " . count($errors) . " issues\n" . COLOR_RESET;
        }
        
        echo "\n" . COLOR_BLUE . "🛠️  Recommended Actions:\n";
        echo "========================\n" . COLOR_RESET;
        
        if (isset($errorTypes['namespace_issues'])) {
            echo "1. Fix namespace issues in controllers and models\n";
        }
        if (isset($errorTypes['class_redeclaration'])) {
            echo "2. Resolve class redeclaration conflicts\n";
        }
        if (isset($errorTypes['warnings'])) {
            echo "3. Set up environment configuration for database\n";
        }
        if ($this->passedTests > 0) {
            echo "4. " . COLOR_GREEN . "Good news: {$this->passedTests} endpoints are working!" . COLOR_RESET . "\n";
        }
    }
    
    /**
     * Get detailed results for further analysis
     */
    public function getResults() 
    {
        return $this->results;
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $tester = new EndpointTester();
    $tester->runTests();
} else {
    echo "This script should be run from the command line.\n";
    exit(1);
}
?>
