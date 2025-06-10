<?php

namespace App\Middleware;

use Api\Core\Request;
use Api\Core\Response;
use Api\Services\RateLimitService;
use Api\Services\LoggingService;

/**
 * Rate Limiting Middleware
 * 
 * Prevents abuse by limiting requests per IP address and endpoint
 * Supports different rate limits for different endpoint types
 */
class RateLimit
{
    private RateLimitService $rateLimitService;
    private LoggingService $logger;

    public function __construct()
    {
        $this->rateLimitService = new RateLimitService();
        $this->logger = new LoggingService();
    }

    /**
     * Handle rate limiting for incoming request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $ip = $this->getClientIp($request);
        $endpoint = $this->getEndpointCategory($request);
        
        // Check if rate limit is exceeded
        $rateLimitCheck = $this->rateLimitService->checkLimit($ip, $endpoint);
        
        if (!$rateLimitCheck['allowed']) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $ip,
                'endpoint' => $endpoint,
                'requests_made' => $rateLimitCheck['requests_made'],
                'limit' => $rateLimitCheck['limit'],
                'reset_time' => $rateLimitCheck['reset_time'],
                'user_agent' => $request->getHeader('User-Agent')
            ]);

            $headers = [
                'X-RateLimit-Limit' => $rateLimitCheck['limit'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $rateLimitCheck['reset_time'],
                'Retry-After' => $rateLimitCheck['retry_after']
            ];

            return new Response(
                json_encode([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $rateLimitCheck['retry_after']
                ]),
                429,
                array_merge(['Content-Type' => 'application/json'], $headers)
            );
        }
        
        // Record the request
        $this->rateLimitService->recordRequest($ip, $endpoint);
        
        // Continue to next middleware/controller
        $response = $next($request);
        
        // Add rate limit headers to response
        $response->setHeader('X-RateLimit-Limit', $rateLimitCheck['limit']);
        $response->setHeader('X-RateLimit-Remaining', max(0, $rateLimitCheck['limit'] - $rateLimitCheck['requests_made'] - 1));
        $response->setHeader('X-RateLimit-Reset', $rateLimitCheck['reset_time']);
        
        return $response;
    }

    /**
     * Get client IP address with proper proxy handling
     * 
     * @param Request $request
     * @return string
     */
    private function getClientIp(Request $request): string
    {
        // Check for shared internet/proxy
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'HTTP_CLIENT_IP',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    // Validate IP and exclude private/reserved ranges for security
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        // Fallback to REMOTE_ADDR even if it's private (for local development)
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get current endpoint for rate limiting
     */
    private function getCurrentEndpoint()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Determine endpoint type for rate limiting
        if (strpos($uri, '/api/contact/submit') !== false) {
            return 'contact_form';
        }
        
        if (strpos($uri, '/api/auth/login') !== false) {
            return 'admin_login';
        }
        
        return 'general';
    }

    /**
     * Categorize endpoint for rate limiting rules
     * 
     * @param Request $request
     * @return string
     */
    private function getEndpointCategory(Request $request): string
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        
        // Contact form submissions - strictest limits
        if (strpos($path, '/api/contact') === 0 && $method === 'POST') {
            return 'contact_submit';
        }
        
        // Authentication endpoints - moderate limits
        if (strpos($path, '/api/auth') === 0) {
            return 'auth';
        }
        
        // Admin endpoints - moderate limits
        if (strpos($path, '/api/admin') === 0) {
            return 'admin';
        }
        
        // CV download - moderate limits
        if (strpos($path, '/api/cv') === 0) {
            return 'cv_download';
        }
        
        // General API endpoints - generous limits
        if (strpos($path, '/api/') === 0) {
            return 'api_general';
        }
        
        // Default category
        return 'general';
    }
}
