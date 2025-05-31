<?php

namespace Api\Controllers;

use Api\Core\Request;
use Api\Core\Response;
use Api\Models\PortfolioModel;
use Api\Models\AdminModel;
use Api\Services\ValidationService;
use Api\Services\LoggingService;

/**
 * Admin Controller
 * 
 * Handles all admin panel operations for content management
 * Requires authentication for all endpoints
 */
class AdminController extends BaseController
{
    private PortfolioModel $portfolioModel;
    private AdminModel $adminModel;
    private ValidationService $validator;
    private LoggingService $logger;

    public function __construct()
    {
        parent::__construct();
        $this->portfolioModel = new PortfolioModel();
        $this->adminModel = new AdminModel();
        $this->validator = new ValidationService();
        $this->logger = new LoggingService();
    }

    // =====================================================
    // CONTACT MESSAGES MANAGEMENT
    // =====================================================

    /**
     * Get all contact messages with pagination and filtering
     * 
     * @param Request $request
     * @return Response
     */
    public function getMessages(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->getQuery('page', 1));
            $limit = min(100, max(10, (int) $request->getQuery('limit', 20)));
            $status = $request->getQuery('status', 'all'); // all, read, unread
            $search = $request->getQuery('search', '');

            $offset = ($page - 1) * $limit;

            $filters = [
                'status' => $status,
                'search' => $search,
                'limit' => $limit,
                'offset' => $offset
            ];

            $messages = $this->adminModel->getContactMessages($filters);
            $total = $this->adminModel->getContactMessagesCount($filters);

            $response = [
                'messages' => $messages,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ];

            return $this->jsonResponse($response);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve contact messages', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to retrieve messages', 500);
        }
    }

    /**
     * Mark contact message as read/unread
     * 
     * @param Request $request
     * @return Response
     */
    public function updateMessageStatus(Request $request): Response
    {
        try {
            $messageId = $request->getPathParam('id');
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'is_read' => 'required|boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            $success = $this->adminModel->updateContactMessageStatus(
                $messageId, 
                $data['is_read']
            );

            if (!$success) {
                return $this->errorResponse('Message not found', 404);
            }

            $this->logger->info('Contact message status updated', [
                'message_id' => $messageId,
                'is_read' => $data['is_read'],
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Message status updated successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update message status', [
                'error' => $e->getMessage(),
                'message_id' => $messageId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to update message status', 500);
        }
    }

    /**
     * Delete contact message
     * 
     * @param Request $request
     * @return Response
     */
    public function deleteMessage(Request $request): Response
    {
        try {
            $messageId = $request->getPathParam('id');

            $success = $this->adminModel->deleteContactMessage($messageId);

            if (!$success) {
                return $this->errorResponse('Message not found', 404);
            }

            $this->logger->info('Contact message deleted', [
                'message_id' => $messageId,
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Message deleted successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete message', [
                'error' => $e->getMessage(),
                'message_id' => $messageId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to delete message', 500);
        }
    }

    // =====================================================
    // PERSONAL INFO MANAGEMENT
    // =====================================================

    /**
     * Update personal information
     * 
     * @param Request $request
     * @return Response
     */
    public function updatePersonalInfo(Request $request): Response
    {
        try {
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'full_name' => 'required|max:100',
                'title' => 'required|max:100',
                'email' => 'required|email|max:100',
                'phone' => 'max:20',
                'location' => 'max:100',
                'website' => 'url|max:200',
                'linkedin' => 'url|max:200',
                'github' => 'url|max:200',
                'bio' => 'required|max:1000',
                'profile_image' => 'url|max:500'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            $success = $this->portfolioModel->updatePersonalInfo($data);

            if (!$success) {
                return $this->errorResponse('Failed to update personal information', 500);
            }

            $this->logger->info('Personal information updated', [
                'admin_id' => $_SESSION['admin_id'],
                'updated_fields' => array_keys($data)
            ]);

            return $this->jsonResponse(['message' => 'Personal information updated successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update personal info', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to update personal information', 500);
        }
    }

    // =====================================================
    // PROJECTS MANAGEMENT
    // =====================================================

    /**
     * Get all projects for admin (including hidden ones)
     * 
     * @param Request $request
     * @return Response
     */
    public function getAdminProjects(Request $request): Response
    {
        try {
            $projects = $this->portfolioModel->getAllProjects(true); // Include hidden
            return $this->jsonResponse(['projects' => $projects]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve admin projects', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to retrieve projects', 500);
        }
    }

    /**
     * Create new project
     * 
     * @param Request $request
     * @return Response
     */
    public function createProject(Request $request): Response
    {
        try {
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'title' => 'required|max:100',
                'short_description' => 'required|max:255',
                'long_description' => 'required|max:2000',
                'technologies' => 'required|max:500',
                'project_url' => 'url|max:500',
                'github_url' => 'url|max:500',
                'image_url' => 'url|max:500',
                'display_order' => 'integer|min:0',
                'is_featured' => 'boolean',
                'is_visible' => 'boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            // Set defaults
            $data['is_featured'] = $data['is_featured'] ?? false;
            $data['is_visible'] = $data['is_visible'] ?? true;
            $data['display_order'] = $data['display_order'] ?? 0;

            $projectId = $this->portfolioModel->createProject($data);

            if (!$projectId) {
                return $this->errorResponse('Failed to create project', 500);
            }

            // Invalidate projects cache
            $this->portfolioModel->invalidateCache('projects');

            $this->logger->info('Project created', [
                'project_id' => $projectId,
                'title' => $data['title'],
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse([
                'message' => 'Project created successfully',
                'project_id' => $projectId
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create project', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to create project', 500);
        }
    }

    /**
     * Update existing project
     * 
     * @param Request $request
     * @return Response
     */
    public function updateProject(Request $request): Response
    {
        try {
            $projectId = $request->getPathParam('id');
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'title' => 'max:100',
                'short_description' => 'max:255',
                'long_description' => 'max:2000',
                'technologies' => 'max:500',
                'project_url' => 'url|max:500',
                'github_url' => 'url|max:500',
                'image_url' => 'url|max:500',
                'display_order' => 'integer|min:0',
                'is_featured' => 'boolean',
                'is_visible' => 'boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            $success = $this->portfolioModel->updateProject($projectId, $data);

            if (!$success) {
                return $this->errorResponse('Project not found', 404);
            }

            // Invalidate projects cache
            $this->portfolioModel->invalidateCache('projects');

            $this->logger->info('Project updated', [
                'project_id' => $projectId,
                'updated_fields' => array_keys($data),
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Project updated successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update project', [
                'error' => $e->getMessage(),
                'project_id' => $projectId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to update project', 500);
        }
    }

    /**
     * Delete project
     * 
     * @param Request $request
     * @return Response
     */
    public function deleteProject(Request $request): Response
    {
        try {
            $projectId = $request->getPathParam('id');

            $success = $this->portfolioModel->deleteProject($projectId);

            if (!$success) {
                return $this->errorResponse('Project not found', 404);
            }

            // Invalidate projects cache
            $this->portfolioModel->invalidateCache('projects');

            $this->logger->info('Project deleted', [
                'project_id' => $projectId,
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Project deleted successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete project', [
                'error' => $e->getMessage(),
                'project_id' => $projectId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to delete project', 500);
        }
    }

    // =====================================================
    // SKILLS MANAGEMENT
    // =====================================================

    /**
     * Get all skills for admin
     * 
     * @param Request $request
     * @return Response
     */
    public function getAdminSkills(Request $request): Response
    {
        try {
            $skills = $this->portfolioModel->getAllSkills(true); // Include hidden
            return $this->jsonResponse(['skills' => $skills]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve admin skills', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to retrieve skills', 500);
        }
    }

    /**
     * Create new skill
     * 
     * @param Request $request
     * @return Response
     */
    public function createSkill(Request $request): Response
    {
        try {
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'name' => 'required|max:100',
                'category' => 'required|max:50',
                'proficiency_level' => 'required|integer|min:1|max:10',
                'display_order' => 'integer|min:0',
                'is_visible' => 'boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            // Set defaults
            $data['is_visible'] = $data['is_visible'] ?? true;
            $data['display_order'] = $data['display_order'] ?? 0;

            $skillId = $this->portfolioModel->createSkill($data);

            if (!$skillId) {
                return $this->errorResponse('Failed to create skill', 500);
            }

            $this->logger->info('Skill created', [
                'skill_id' => $skillId,
                'name' => $data['name'],
                'category' => $data['category'],
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse([
                'message' => 'Skill created successfully',
                'skill_id' => $skillId
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create skill', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to create skill', 500);
        }
    }

    /**
     * Update existing skill
     * 
     * @param Request $request
     * @return Response
     */
    public function updateSkill(Request $request): Response
    {
        try {
            $skillId = $request->getPathParam('id');
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'name' => 'max:100',
                'category' => 'max:50',
                'proficiency_level' => 'integer|min:1|max:10',
                'display_order' => 'integer|min:0',
                'is_visible' => 'boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            $success = $this->portfolioModel->updateSkill($skillId, $data);

            if (!$success) {
                return $this->errorResponse('Skill not found', 404);
            }

            $this->logger->info('Skill updated', [
                'skill_id' => $skillId,
                'updated_fields' => array_keys($data),
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Skill updated successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update skill', [
                'error' => $e->getMessage(),
                'skill_id' => $skillId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to update skill', 500);
        }
    }

    /**
     * Delete skill
     * 
     * @param Request $request
     * @return Response
     */
    public function deleteSkill(Request $request): Response
    {
        try {
            $skillId = $request->getPathParam('id');

            $success = $this->portfolioModel->deleteSkill($skillId);

            if (!$success) {
                return $this->errorResponse('Skill not found', 404);
            }

            $this->logger->info('Skill deleted', [
                'skill_id' => $skillId,
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Skill deleted successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete skill', [
                'error' => $e->getMessage(),
                'skill_id' => $skillId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to delete skill', 500);
        }
    }

    // =====================================================
    // EXPERIENCE MANAGEMENT
    // =====================================================

    /**
     * Get all experience entries for admin
     * 
     * @param Request $request
     * @return Response
     */
    public function getAdminExperience(Request $request): Response
    {
        try {
            $experience = $this->portfolioModel->getAllExperience(true); // Include hidden
            return $this->jsonResponse(['experience' => $experience]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve admin experience', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to retrieve experience', 500);
        }
    }

    /**
     * Create new experience entry
     * 
     * @param Request $request
     * @return Response
     */
    public function createExperience(Request $request): Response
    {
        try {
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'company' => 'required|max:100',
                'position' => 'required|max:100',
                'start_date' => 'required|date',
                'end_date' => 'date',
                'description' => 'required|max:1000',
                'display_order' => 'integer|min:0',
                'is_visible' => 'boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            // Set defaults
            $data['is_visible'] = $data['is_visible'] ?? true;
            $data['display_order'] = $data['display_order'] ?? 0;

            $experienceId = $this->portfolioModel->createExperience($data);

            if (!$experienceId) {
                return $this->errorResponse('Failed to create experience entry', 500);
            }

            $this->logger->info('Experience entry created', [
                'experience_id' => $experienceId,
                'company' => $data['company'],
                'position' => $data['position'],
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse([
                'message' => 'Experience entry created successfully',
                'experience_id' => $experienceId
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create experience entry', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to create experience entry', 500);
        }
    }

    /**
     * Update existing experience entry
     * 
     * @param Request $request
     * @return Response
     */
    public function updateExperience(Request $request): Response
    {
        try {
            $experienceId = $request->getPathParam('id');
            $data = $request->getJsonBody();

            $validation = $this->validator->validate($data, [
                'company' => 'max:100',
                'position' => 'max:100',
                'start_date' => 'date',
                'end_date' => 'date',
                'description' => 'max:1000',
                'display_order' => 'integer|min:0',
                'is_visible' => 'boolean'
            ]);

            if (!$validation['valid']) {
                return $this->errorResponse('Validation failed', 400, $validation['errors']);
            }

            $success = $this->portfolioModel->updateExperience($experienceId, $data);

            if (!$success) {
                return $this->errorResponse('Experience entry not found', 404);
            }

            $this->logger->info('Experience entry updated', [
                'experience_id' => $experienceId,
                'updated_fields' => array_keys($data),
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Experience entry updated successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update experience entry', [
                'error' => $e->getMessage(),
                'experience_id' => $experienceId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to update experience entry', 500);
        }
    }

    /**
     * Delete experience entry
     * 
     * @param Request $request
     * @return Response
     */
    public function deleteExperience(Request $request): Response
    {
        try {
            $experienceId = $request->getPathParam('id');

            $success = $this->portfolioModel->deleteExperience($experienceId);

            if (!$success) {
                return $this->errorResponse('Experience entry not found', 404);
            }

            $this->logger->info('Experience entry deleted', [
                'experience_id' => $experienceId,
                'admin_id' => $_SESSION['admin_id']
            ]);

            return $this->jsonResponse(['message' => 'Experience entry deleted successfully']);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete experience entry', [
                'error' => $e->getMessage(),
                'experience_id' => $experienceId ?? null,
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to delete experience entry', 500);
        }
    }

    // =====================================================
    // DASHBOARD ANALYTICS
    // =====================================================

    /**
     * Get admin dashboard statistics
     * 
     * @param Request $request
     * @return Response
     */
    public function getDashboardStats(Request $request): Response
    {
        try {
            $stats = [
                'total_projects' => $this->portfolioModel->getProjectsCount(),
                'total_skills' => $this->portfolioModel->getSkillsCount(),
                'total_experience' => $this->portfolioModel->getExperienceCount(),
                'total_education' => $this->portfolioModel->getEducationCount(),
                'unread_messages' => $this->adminModel->getUnreadMessagesCount(),
                'total_messages' => $this->adminModel->getTotalMessagesCount(),
                'recent_messages' => $this->adminModel->getRecentMessages(5),
                'system_info' => [
                    'php_version' => PHP_VERSION,
                    'server_time' => date('Y-m-d H:i:s'),
                    'storage_used' => $this->getStorageUsage()
                ]
            ];

            return $this->jsonResponse($stats);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve dashboard stats', [
                'error' => $e->getMessage(),
                'admin_id' => $_SESSION['admin_id'] ?? null
            ]);

            return $this->errorResponse('Failed to retrieve dashboard statistics', 500);
        }
    }

    /**
     * Get storage usage information
     * 
     * @return array
     */
    private function getStorageUsage(): array
    {
        $storagePath = dirname(__DIR__, 2) . '/storage';
        $totalSize = 0;
        $fileCount = 0;

        if (is_dir($storagePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                    $fileCount++;
                }
            }
        }

        return [
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'file_count' => $fileCount
        ];
    }
}
