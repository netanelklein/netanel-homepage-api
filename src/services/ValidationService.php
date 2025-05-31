<?php

namespace Services;

/**
 * Validation Service
 * Provides common validation methods
 */
class ValidationService
{
    /**
     * Validate email format
     */
    public function email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL format
     */
    public function url($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate required fields
     */
    public function required($data, $fields)
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = "The {$field} field is required.";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate string length
     */
    public function length($value, $min = null, $max = null)
    {
        $length = strlen($value);
        
        if ($min !== null && $length < $min) {
            return false;
        }
        
        if ($max !== null && $length > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($value, $min = null, $max = null)
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $number = (float) $value;
        
        if ($min !== null && $number < $min) {
            return false;
        }
        
        if ($max !== null && $number > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date format
     */
    public function date($date, $format = 'Y-m-d')
    {
        $dateObj = DateTime::createFromFormat($format, $date);
        return $dateObj && $dateObj->format($format) === $date;
    }
    
    /**
     * Validate enum value
     */
    public function enum($value, $allowedValues)
    {
        return in_array($value, $allowedValues);
    }
    
    /**
     * Sanitize input string
     */
    public function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate JSON
     */
    public function json($json)
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Validate phone number (basic)
     */
    public function phone($phone)
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it's a reasonable length
        return strlen($cleaned) >= 7 && strlen($cleaned) <= 15;
    }
    
    /**
     * Validate file upload
     */
    public function file($file, $allowedTypes = [], $maxSize = null)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Check file size
        if ($maxSize && $file['size'] > $maxSize) {
            return false;
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileType, $allowedTypes)) {
                return false;
            }
        }
        
        return true;
    }
}
