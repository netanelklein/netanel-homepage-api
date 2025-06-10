<?php

namespace Core;

/**
 * HTTP Response handler
 * Provides easy methods for sending API responses
 */
class Response
{
    private $statusCode = 200;
    private $headers = [];
    private $data = null;
    
    /**
     * Set status code
     */
    public function status($code)
    {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * Set header
     */
    public function header($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set a response header
     * 
     * @param string $name
     * @param string $value
     * @return void
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Get response headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get response status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    /**
     * Send JSON response
     */
    public function json($data, $statusCode = null)
    {
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }
        
        $this->header('Content-Type', 'application/json');
        $this->data = $data;
        
        return $this->send();
    }
    
    /**
     * Send success response
     */
    public function success($data = null, $message = 'Success')
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send error response
     */
    public function error($message, $statusCode = 400, $errors = null)
    {
        $response = [
            'error' => true,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Send validation error response
     */
    public function validationError($errors, $message = 'Validation failed')
    {
        return $this->error($message, 422, $errors);
    }
    
    /**
     * Send not found response
     */
    public function notFound($message = 'Resource not found')
    {
        return $this->error($message, 404);
    }
    
    /**
     * Send unauthorized response
     */
    public function unauthorized($message = 'Unauthorized')
    {
        return $this->error($message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public function forbidden($message = 'Forbidden')
    {
        return $this->error($message, 403);
    }
    
    /**
     * Send server error response
     */
    public function serverError($message = 'Internal server error')
    {
        return $this->error($message, 500);
    }
    
    /**
     * Send rate limit error
     */
    public function rateLimitExceeded($message = 'Rate limit exceeded')
    {
        return $this->error($message, 429);
    }
    
    /**
     * Send paginated response
     */
    public function paginated($data, $pagination)
    {
        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination
        ]);
    }
    
    /**
     * Send file download response
     */
    public function download($filePath, $filename = null)
    {
        if (!file_exists($filePath)) {
            return $this->notFound('File not found');
        }
        
        $filename = $filename ?: basename($filePath);
        $mimeType = mime_content_type($filePath);
        
        $this->header('Content-Type', $mimeType);
        $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->header('Content-Length', filesize($filePath));
        
        $this->sendHeaders();
        readfile($filePath);
        exit;
    }
    
    /**
     * Send the response
     */
    private function send()
    {
        // Set status code
        http_response_code($this->statusCode);
        
        // Send headers
        $this->sendHeaders();
        
        // Send body
        if ($this->data !== null) {
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        exit;
    }
    
    /**
     * Send headers
     */
    private function sendHeaders()
    {
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }
    
    /**
     * Create a new response instance
     */
    public static function make()
    {
        return new static();
    }
}
