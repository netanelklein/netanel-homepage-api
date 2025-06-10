<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;

/**
 * Base Controller
 * Provides common functionality for all controllers
 */
abstract class BaseController
{
    protected $request;
    protected $response;
    
    public function __construct()
    {
        $this->request = new Request();
        $this->response = Response::make();
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($data, $required)
    {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "The {$field} field is required.";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate email format
     */
    protected function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get pagination parameters
     */
    protected function getPagination()
    {
        $config = require __DIR__ . '/../../config/app.php';
        
        $page = max(1, intval($this->request->query('page', 1)));
        $limit = min(
            intval($this->request->query('limit', $config['api']['pagination']['default_limit'])),
            $config['api']['pagination']['max_limit']
        );
        
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Build pagination response
     */
    protected function buildPaginationResponse($data, $total, $page, $limit)
    {
        $totalPages = ceil($total / $limit);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }
}
