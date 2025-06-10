<?php

namespace App\Controllers;

use App\Models\PortfolioModel;
use App\Services\ValidationService;
use App\Services\LoggingService;

/**
 * Contact Controller
 * Handles contact form submissions with spam protection
 */
class ContactController extends BaseController
{
    private $portfolioModel;
    private $validator;
    private $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->portfolioModel = new PortfolioModel();
        $this->validator = new ValidationService();
        $this->logger = new LoggingService();
    }
    
    /**
     * Submit contact form
     */
    public function submit()
    {
        try {
            // Get and sanitize input data
            $data = $this->sanitizeInput($this->request->all());
            
            // Validate required fields
            $errors = $this->validateContactForm($data);
            
            if (!empty($errors)) {
                return $this->response->validationError($errors);
            }
            
            // Check for spam indicators
            if ($this->isSpam($data)) {
                $this->logger->warning('Spam contact form submission detected', [
                    'ip' => $this->request->ip(),
                    'data' => $data
                ]);
                
                // Return success to avoid revealing spam detection
                return $this->response->success(null, 'Thank you for your message. We will get back to you soon.');
            }
            
            // Prepare message data
            $messageData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'status' => 'unread'
            ];
            
            // Save to database
            $messageId = $this->portfolioModel->createContactMessage($messageData);
            
            if ($messageId) {
                $this->logger->info('Contact form submission received', [
                    'message_id' => $messageId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'ip' => $this->request->ip()
                ]);
                
                return $this->response->success([
                    'message_id' => $messageId
                ], 'Thank you for your message. We will get back to you soon.');
            } else {
                throw new Exception('Failed to save contact message');
            }
            
        } catch (Exception $e) {
            $this->logger->error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'ip' => $this->request->ip()
            ]);
            
            return $this->response->serverError('Failed to submit message. Please try again later.');
        }
    }
    
    /**
     * Validate contact form data
     */
    private function validateContactForm($data)
    {
        $errors = [];
        
        // Required fields
        $required = ['name', 'email', 'subject', 'message'];
        $requiredErrors = $this->validateRequired($data, $required);
        $errors = array_merge($errors, $requiredErrors);
        
        // Email validation
        if (isset($data['email']) && !empty($data['email'])) {
            if (!$this->validateEmail($data['email'])) {
                $errors['email'] = 'Please provide a valid email address.';
            }
        }
        
        // Length validations
        if (isset($data['name']) && strlen($data['name']) > 100) {
            $errors['name'] = 'Name must be less than 100 characters.';
        }
        
        if (isset($data['subject']) && strlen($data['subject']) > 200) {
            $errors['subject'] = 'Subject must be less than 200 characters.';
        }
        
        if (isset($data['message']) && strlen($data['message']) > 2000) {
            $errors['message'] = 'Message must be less than 2000 characters.';
        }
        
        if (isset($data['message']) && strlen($data['message']) < 10) {
            $errors['message'] = 'Message must be at least 10 characters long.';
        }
        
        return $errors;
    }
    
    /**
     * Basic spam detection
     */
    private function isSpam($data)
    {
        $spamIndicators = [
            // Common spam phrases
            'viagra', 'cialis', 'lottery', 'winner', 'congratulations',
            'click here', 'make money', 'work from home', 'guaranteed',
            'free money', 'investment opportunity', 'bitcoin', 'crypto',
            
            // Suspicious patterns
            'http://', 'https://', 'www.', '.com', '.net', '.org'
        ];
        
        $message = strtolower($data['message'] . ' ' . $data['subject']);
        
        foreach ($spamIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }
        
        // Check for excessive links
        $linkCount = substr_count($message, 'http') + substr_count($message, 'www.');
        if ($linkCount > 2) {
            return true;
        }
        
        // Check for excessive uppercase
        $uppercaseRatio = strlen(preg_replace('/[^A-Z]/', '', $data['message'])) / strlen($data['message']);
        if ($uppercaseRatio > 0.5) {
            return true;
        }
        
        // Check honeypot field (if implemented in frontend)
        if (isset($data['website']) && !empty($data['website'])) {
            return true;
        }
        
        return false;
    }
}
