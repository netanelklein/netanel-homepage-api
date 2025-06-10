<?php

namespace App\Middleware;

use Api\Core\Response;
use App\Services\SessionService;

/**
 * Authentication Middleware
 * Checks if user is authenticated for admin endpoints
 */
class Auth
{
    public function handle()
    {
        $sessionService = new SessionService();
        
        if (!$sessionService->isAuthenticated()) {
            $response = Response::make();
            $response->unauthorized('Authentication required');
        }
        
        // Set authenticated user in global context
        $GLOBALS['auth_user'] = $sessionService->getUser();
    }
}
