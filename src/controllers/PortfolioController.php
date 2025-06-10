<?php

namespace App\Controllers;

use App\Models\PortfolioModel;
use App\Services\CacheService;

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
    
    /**
     * Get all portfolio data in one request (optimized for frontend)
     */
    public function getAllData()
    {
        try {
            // Use cached response if available (10 minutes TTL for complete data)
            $cacheKey = 'portfolio_all_data';
            $cachedData = $this->cache->get($cacheKey);
            
            if ($cachedData) {
                return $this->response->json($cachedData);
            }
            
            // Fetch all data using cached methods
            $personalInfoRaw = $this->portfolioModel->getCachedPersonalInfo();
            $projects = $this->portfolioModel->getCachedProjects();
            $skills = $this->portfolioModel->getCachedSkills();
            $experience = $this->portfolioModel->getCachedExperience();
            $education = $this->portfolioModel->getCachedEducation();
            
            // Transform personal info to match Flutter model
            $personalInfo = $this->transformPersonalInfoForFrontend($personalInfoRaw);
            
            $portfolioData = [
                'personalInfo' => $personalInfo,
                'projects' => $projects,
                'experiences' => $experience,
                'education' => $education,
                'skillCategories' => $this->formatSkillsForFrontend($skills),
                'lastUpdated' => date('c'),
                'version' => '1.0.0'
            ];
            
            // Cache the complete data for 10 minutes
            $this->cache->set($cacheKey, $portfolioData, 600);
            
            // Return data directly for frontend compatibility
            $this->response->json($portfolioData);
            
        } catch (Exception $e) {
            return $this->response->serverError('Failed to load portfolio data: ' . $e->getMessage());
        }
    }

    /**
     * Format skills data for frontend consumption
     */
    private function formatSkillsForFrontend($skills)
    {
        // Handle skills that are already grouped by category (from getCachedSkills)
        if (is_array($skills) && !empty($skills)) {
            $skillCategories = [];
            
            // If skills are already grouped (associative array with category keys)
            if (array_keys($skills) !== range(0, count($skills) - 1)) {
                foreach ($skills as $category => $categorySkills) {
                    $skillCategories[] = [
                        'category' => $category,
                        'skills' => array_map(function($skill) {
                            return [
                                'name' => $skill['name'],
                                'level' => $skill['level'],
                                'description' => $skill['description'] ?? null
                            ];
                        }, $categorySkills)
                    ];
                }
            } else {
                // If skills are flat array, group them by category
                foreach ($skills as $skill) {
                    $category = $skill['category'] ?? 'Other';
                    
                    if (!isset($skillCategories[$category])) {
                        $skillCategories[$category] = [
                            'category' => $category,
                            'skills' => []
                        ];
                    }
                    
                    $skillCategories[$category]['skills'][] = [
                        'name' => $skill['name'],
                        'level' => $skill['level'],
                        'description' => $skill['description'] ?? null
                    ];
                }
                
                // Convert to indexed array
                $skillCategories = array_values($skillCategories);
            }
            
            return $skillCategories;
        }
        
        return [];
    }

    /**
     * Transform personal info to match Flutter frontend model
     */
    private function transformPersonalInfoForFrontend($personalInfo)
    {
        if (empty($personalInfo)) {
            return null;
        }
        
        // Handle array format like the working getPersonalInfo method
        if (is_array($personalInfo) && isset($personalInfo[0])) {
            $info = $personalInfo[0];
        } else {
            $info = $personalInfo;
        }
        
        return [
            'fullName' => $info['name'] ?? '',
            'title' => $info['title'] ?? '',
            'tagline' => $info['tagline'] ?? '',
            'summary' => $info['summary'] ?? '',
            'contact' => [
                'email' => $info['5'] ?? $info['email'] ?? '', // Handle numbered index
                'phone' => $info['6'] ?? $info['phone'] ?? null,
                'location' => $info['location'] ?? null,
                'socialLinks' => [
                    'github' => $info['github_url'] ?? '',
                    'linkedin' => $info['linkedin_url'] ?? ''
                ]
            ],
            'languages' => ['English', 'Hebrew'], // Default languages - can be made dynamic later
            'profileImageUrl' => $info['profile_image_url'] ?? null
        ];
    }
}
