<?php

namespace App\Services;

use Core\Database;

/**
 * Session Management Service
 * Handles admin authentication sessions
 */
class SessionService
{
    private $sessionName;
    private $sessionLifetime;
    
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->sessionName = $config['security']['session_name'];
        $this->sessionLifetime = $config['security']['session_lifetime'];
        
        $this->startSession();
    }
    
    /**
     * Start secure session
     */
    private function startSession()
    {
        // Configure session settings
        ini_set('session.name', $this->sessionName);
        ini_set('session.gc_maxlifetime', $this->sessionLifetime);
        ini_set('session.cookie_lifetime', $this->sessionLifetime);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Authenticate user and create session
     */
    public function authenticate($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        
        // Update last login time
        $db = Database::getInstance();
        $db->update('admin_users', 
            ['last_login' => date('Y-m-d H:i:s')], 
            'id = :id', 
            ['id' => $user['id']]
        );
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > $this->sessionLifetime) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Get authenticated user
     */
    public function getUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->fetch(
            'SELECT id, username, email FROM admin_users WHERE id = :id',
            ['id' => $_SESSION['user_id']]
        );
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    }
    
    /**
     * Get user ID
     */
    public function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get username
     */
    public function getUsername()
    {
        return $_SESSION['username'] ?? null;
    }
}
