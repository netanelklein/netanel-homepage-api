<?php

namespace Controllers;

use Models\AdminModel;
use Services\SessionService;
use Services\LoggingService;

/**
 * Authentication Controller
 * Handles admin login and logout
 */
class AuthController extends BaseController
{
    private $adminModel;
    private $sessionService;
    private $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->adminModel = new AdminModel();
        $this->sessionService = new SessionService();
        $this->logger = new LoggingService();
    }
    
    /**
     * Admin login
     */
    public function login()
    {
        try {
            // Get and sanitize input
            $data = $this->sanitizeInput($this->request->all());
            
            // Validate required fields
            $errors = $this->validateRequired($data, ['username', 'password']);
            
            if (!empty($errors)) {
                return $this->response->validationError($errors);
            }
            
            $username = $data['username'];
            $password = $data['password'];
            
            // Find admin user
            $user = $this->adminModel->findByUsername($username);
            
            if (!$user) {
                $this->logger->warning('Login attempt with invalid username', [
                    'username' => $username,
                    'ip' => $this->request->ip()
                ]);
                
                return $this->response->unauthorized('Invalid credentials');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->logger->warning('Login attempt with invalid password', [
                    'username' => $username,
                    'ip' => $this->request->ip()
                ]);
                
                return $this->response->unauthorized('Invalid credentials');
            }
            
            // Create session
            $this->sessionService->authenticate($user);
            
            $this->logger->info('Admin user logged in', [
                'user_id' => $user['id'],
                'username' => $username,
                'ip' => $this->request->ip()
            ]);
            
            return $this->response->success([
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ], 'Login successful');
            
        } catch (Exception $e) {
            $this->logger->error('Login error', [
                'error' => $e->getMessage(),
                'ip' => $this->request->ip()
            ]);
            
            return $this->response->serverError('Login failed. Please try again.');
        }
    }
    
    /**
     * Admin logout
     */
    public function logout()
    {
        try {
            $user = $this->sessionService->getUser();
            
            if ($user) {
                $this->logger->info('Admin user logged out', [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'ip' => $this->request->ip()
                ]);
            }
            
            $this->sessionService->logout();
            
            return $this->response->success(null, 'Logout successful');
            
        } catch (Exception $e) {
            $this->logger->error('Logout error', [
                'error' => $e->getMessage(),
                'ip' => $this->request->ip()
            ]);
            
            return $this->response->serverError('Logout failed');
        }
    }
    
    /**
     * Get current user info
     */
    public function me()
    {
        try {
            $user = $this->sessionService->getUser();
            
            if (!$user) {
                return $this->response->unauthorized('Not authenticated');
            }
            
            return $this->response->success([
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'last_login' => $user['last_login']
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to get user information');
        }
    }
}
