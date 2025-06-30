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
            
            // Transform to Flutter-compatible format without sensitive data
            $personalInfo = $this->transformPersonalInfoForFrontend($data);
            
            return $this->response->success($personalInfo);
            
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
            
            // Transform projects to Flutter-compatible format
            $projects = array_map([$this, 'transformProjectForFrontend'], $data ?: []);
            
            return $this->response->success($projects);
            
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
            
            // Transform experience to Flutter-compatible format
            $experiences = array_map([$this, 'transformExperienceForFrontend'], $data ?: []);
            
            return $this->response->success($experiences);
            
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
            
            // Transform education to Flutter-compatible format
            $education = array_map([$this, 'transformEducationForFrontend'], $data ?: []);
            
            return $this->response->success($education);
            
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
                'projects' => array_map([$this, 'transformProjectForFrontend'], $projects ?: []),
                'experiences' => array_map([$this, 'transformExperienceForFrontend'], $experience ?: []),
                'education' => array_map([$this, 'transformEducationForFrontend'], $education ?: []),
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
     * Groups skills by category with Flutter-compatible structure
     */
    private function formatSkillsForFrontend($skills)
    {
        if (empty($skills)) {
            return [];
        }
        
        $skillCategories = [];
        
        // Group skills by category
        foreach ($skills as $skill) {
            // Skip invalid skills
            if (empty($skill['name'])) {
                continue;
            }
            
            $category = $skill['category'] ?? 'Other';
            
            if (!isset($skillCategories[$category])) {
                $skillCategories[$category] = [
                    'name' => $category,
                    'description' => $this->getCategoryDescription($category),
                    'skills' => [],
                    'iconName' => null,
                    'displayOrder' => $this->getCategoryOrder($category)
                ];
            }
            
            $skillName = $skill['name'];
            $skillCategories[$category]['skills'][] = [
                'id' => strtolower(str_replace([' ', '/', '.'], ['_', '_', ''], $skillName)),
                'name' => $skillName,
                'level' => $skill['level'] ?? 'intermediate',
                'category' => $category,
                'yearsOfExperience' => $this->getYearsOfExperience($skillName),
                'description' => $skill['description'] ?? null,
                'iconName' => null,
                'isHighlighted' => $this->isSkillHighlighted($skillName)
            ];
        }
        
        // Remove empty categories
        $skillCategories = array_filter($skillCategories, function($category) {
            return !empty($category['skills']);
        });
        
        // Sort categories by display order and convert to indexed array
        uasort($skillCategories, function($a, $b) {
            return $a['displayOrder'] <=> $b['displayOrder'];
        });
        
        return array_values($skillCategories);
    }

    /**
     * Transform personal info to match Flutter frontend model
     * Removes sensitive data like email and phone
     */
    private function transformPersonalInfoForFrontend($personalInfo)
    {
        if (empty($personalInfo)) {
            return null;
        }
        
        // Handle array format
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
                // Removed email and phone for privacy
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

    /**
     * Transform project data to Flutter-compatible format
     */
    private function transformProjectForFrontend($project)
    {
        if (empty($project)) {
            return null;
        }
        
        // Parse technologies JSON string to array
        $technologies = [];
        if (!empty($project['technologies'])) {
            $tech = json_decode($project['technologies'], true);
            $technologies = is_array($tech) ? $tech : [];
        }
        
        // Map status to Flutter enum values
        $status = $project['status'] ?? 'active';
        $statusMap = [
            'active' => 'inProgress',
            'completed' => 'completed',
            'archived' => 'archived',
            'maintained' => 'maintained'
        ];
        $flutterStatus = $statusMap[$status] ?? 'inProgress';
        
        return [
            'id' => (string)$project['id'],
            'title' => $project['title'] ?? '',
            'description' => $project['description'] ?? '',
            'longDescription' => $project['long_description'] ?? $project['description'] ?? '',
            'type' => 'personal', // Default type - can be made dynamic
            'status' => $flutterStatus,
            'startDate' => isset($project['created_at']) ? date('c', strtotime($project['created_at'])) : null,
            'endDate' => isset($project['updated_at']) && $flutterStatus === 'completed' 
                ? date('c', strtotime($project['updated_at'])) : null,
            'technologies' => $technologies,
            'features' => [], // Can be added to database later
            'links' => $this->buildProjectLinks($project),
            'images' => [],
            'iconName' => null,
            'priority' => (int)($project['priority'] ?? 0)
        ];
    }

    /**
     * Transform experience data to Flutter-compatible format
     */
    private function transformExperienceForFrontend($experience)
    {
        if (empty($experience)) {
            return null;
        }
        
        return [
            'id' => (string)$experience['id'],
            'company' => $experience['company'] ?? '',
            'position' => $experience['position'] ?? '',
            'description' => $experience['description'] ?? '',
            'startDate' => isset($experience['start_date']) ? date('c', strtotime($experience['start_date'])) : null,
            'endDate' => isset($experience['end_date']) ? date('c', strtotime($experience['end_date'])) : null,
            'technologies' => [], // Can be added to database later
            'achievements' => [], // Can be added to database later
            'companyUrl' => null,
            'companyLogo' => null
        ];
    }

    /**
     * Transform education data to Flutter-compatible format
     */
    private function transformEducationForFrontend($education)
    {
        if (empty($education)) {
            return null;
        }
        
        return [
            'id' => (string)$education['id'],
            'institution' => $education['institution'] ?? '',
            'degree' => $education['degree'] ?? '',
            'field' => $education['field_of_study'] ?? '',
            'startDate' => isset($education['start_date']) ? date('c', strtotime($education['start_date'])) : null,
            'endDate' => isset($education['end_date']) ? date('c', strtotime($education['end_date'])) : null,
            'gpa' => isset($education['grade']) ? (float)$education['grade'] : null,
            'description' => $education['description'] ?? '',
            'courses' => [], // Can be added to database later
            'achievements' => [], // Can be added to database later
            'institutionUrl' => null,
            'institutionLogo' => null
        ];
    }

    /**
     * Build project links array
     */
    private function buildProjectLinks($project)
    {
        $links = [];
        
        if (!empty($project['github_url'])) {
            $links[] = [
                'type' => 'github',
                'url' => $project['github_url'],
                'label' => 'View Source'
            ];
        }
        
        if (!empty($project['demo_url'])) {
            $links[] = [
                'type' => 'demo',
                'url' => $project['demo_url'],
                'label' => 'Live Demo'
            ];
        }
        
        return $links;
    }

    /**
     * Get category description
     */
    private function getCategoryDescription($category)
    {
        $descriptions = [
            'Frontend' => 'Client-side technologies and frameworks',
            'Backend' => 'Server-side development and APIs',
            'Database' => 'Data storage and management systems',
            'DevOps' => 'Development operations and deployment',
            'Mobile' => 'Mobile application development',
            'Programming Languages' => 'Core programming languages',
            'Frameworks' => 'Development frameworks and libraries',
            'Tools' => 'Development tools and utilities',
            'Other' => 'Additional technical skills'
        ];
        
        return $descriptions[$category] ?? 'Technical skills';
    }

    /**
     * Get category display order
     */
    private function getCategoryOrder($category)
    {
        $order = [
            'Frontend' => 1,
            'Backend' => 2,
            'Database' => 3,
            'Mobile' => 4,
            'DevOps' => 5,
            'Programming Languages' => 6,
            'Frameworks' => 7,
            'Tools' => 8,
            'Other' => 9
        ];
        
        return $order[$category] ?? 999;
    }

    /**
     * Get estimated years of experience for a skill
     */
    private function getYearsOfExperience($skillName)
    {
        // This can be made dynamic with database storage later
        $experience = [
            'Flutter' => 4,
            'PHP' => 5,
            'React' => 3,
            'Vue.js' => 3,
            'TypeScript' => 3,
            'HTML/CSS' => 6,
            'Node.js' => 4,
            'Python' => 3,
            'RESTful APIs' => 5,
            'MySQL' => 4,
            'MongoDB' => 2,
            'Docker' => 3,
            'Git' => 6,
            'Linux' => 4,
            'Oracle Cloud' => 2
        ];
        
        return $experience[$skillName] ?? 1;
    }

    /**
     * Check if skill should be highlighted
     */
    private function isSkillHighlighted($skillName)
    {
        $highlighted = ['Flutter', 'PHP', 'React', 'Docker', 'Git', 'RESTful APIs'];
        return in_array($skillName, $highlighted);
    }
}
