<?php

namespace Controllers;

use Models\PortfolioModel;
use Services\CacheService;

/**
 * Portfolio Controller
 * Handles public portfolio data endpoints
 */
class PortfolioController extends BaseController
{
    private $portfolioModel;
    private $cache;
    
    public function __construct()
    {
        parent::__construct();
        $this->portfolioModel = new PortfolioModel();
        $this->cache = new CacheService();
    }
    
    /**
     * Get personal information
     */
    public function getPersonalInfo()
    {
        try {
            // Use database caching (5 minutes TTL for personal info)
            $data = $this->portfolioModel->getCachedPersonalInfo();
            
            if (!$data) {
                return $this->response->notFound('Personal information not found');
            }
            
            // Get the first result if it's an array
            if (is_array($data) && isset($data[0])) {
                $data = $data[0];
            }
            
            // Remove sensitive data from public response
            unset($data['email'], $data['phone']);
            
            return $this->response->success($data);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to fetch personal information');
        }
    }
    
    /**
     * Get projects list
     */
    public function getProjects()
    {
        try {
            // Use database caching (10 minutes TTL for projects)
            $data = $this->portfolioModel->getCachedProjects();
            
            return $this->response->success($data ?: []);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to fetch projects');
        }
    }
    
    /**
     * Get skills list
     */
    public function getSkills()
    {
        try {
            // Use database caching (15 minutes TTL for skills)
            $data = $this->portfolioModel->getCachedSkills();
            
            return $this->response->success($data ?: []);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to fetch skills');
        }
    }
    
    /**
     * Get work experience
     */
    public function getExperience()
    {
        try {
            // Use database caching (20 minutes TTL for experience)
            $data = $this->portfolioModel->getCachedExperience();
            
            return $this->response->success($data ?: []);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to fetch experience');
        }
    }
    
    /**
     * Get education background
     */
    public function getEducation()
    {
        try {
            // Use database caching (20 minutes TTL for education)
            $data = $this->portfolioModel->getCachedEducation();
            
            return $this->response->success($data ?: []);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to fetch education');
        }
    }
}
