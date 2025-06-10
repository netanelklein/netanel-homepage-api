<?php

namespace App\Models;

/**
 * Portfolio Model
 * Handles portfolio data operations
 */
class PortfolioModel extends BaseModel
{
    /**
     * Get personal information
     */
    public function getPersonalInfo()
    {
        $sql = "SELECT * FROM personal_info LIMIT 1";
        return $this->db->fetch($sql);
    }
    
    /**
     * Get all projects (active first)
     */
    public function getProjects()
    {
        $sql = "SELECT * FROM projects WHERE status != 'archived' ORDER BY priority DESC, created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get projects by status
     */
    public function getProjectsByStatus($status)
    {
        $sql = "SELECT * FROM projects WHERE status = :status ORDER BY priority DESC, created_at DESC";
        return $this->db->fetchAll($sql, ['status' => $status]);
    }
    
    /**
     * Get skills grouped by category
     */
    public function getSkills()
    {
        $sql = "SELECT * FROM skills ORDER BY category ASC, level DESC, name ASC";
        $skills = $this->db->fetchAll($sql);
        
        // Group by category
        $grouped = [];
        foreach ($skills as $skill) {
            $category = $skill['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $skill;
        }
        
        return $grouped;
    }
    
    /**
     * Get work experience (current first, then by start date desc)
     */
    public function getExperience()
    {
        $sql = "SELECT * FROM experience ORDER BY current DESC, start_date DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get education (by start date desc)
     */
    public function getEducation()
    {
        $sql = "SELECT * FROM education ORDER BY start_date DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get contact messages
     */
    public function getContactMessages($status = null, $page = 1, $limit = 20)
    {
        $where = $status ? "status = :status" : null;
        $params = $status ? ['status' => $status] : [];
        
        return $this->db->fetchAll(
            "SELECT * FROM contact_messages" . 
            ($where ? " WHERE {$where}" : "") . 
            " ORDER BY created_at DESC LIMIT {$limit} OFFSET " . (($page - 1) * $limit),
            $params
        );
    }
    
    /**
     * Create contact message
     */
    public function createContactMessage($data)
    {
        return $this->db->insert('contact_messages', $data);
    }
    
    /**
     * Mark message as read
     */
    public function markMessageAsRead($id)
    {
        return $this->db->update(
            'contact_messages',
            [
                'status' => 'read',
                'read_at' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $id]
        );
    }

    /**
     * Get all projects including hidden ones (for admin)
     * 
     * @param bool $includeHidden
     * @return array
     */
    public function getAllProjects(bool $includeHidden = false): array
    {
        if ($includeHidden) {
            $sql = "SELECT * FROM projects ORDER BY display_order ASC, created_at DESC";
            return $this->db->fetchAll($sql);
        } else {
            $sql = "SELECT * FROM projects WHERE is_visible = 1 ORDER BY display_order ASC, created_at DESC";
            return $this->db->fetchAll($sql);
        }
    }

    /**
     * Get all skills including hidden ones (for admin)
     * 
     * @param bool $includeHidden
     * @return array
     */
    public function getAllSkills(bool $includeHidden = false): array
    {
        if ($includeHidden) {
            $sql = "SELECT * FROM skills ORDER BY category ASC, display_order ASC, proficiency_level DESC";
            return $this->db->fetchAll($sql);
        } else {
            $sql = "SELECT * FROM skills WHERE is_visible = 1 ORDER BY category ASC, display_order ASC, proficiency_level DESC";
            return $this->db->fetchAll($sql);
        }
    }

    /**
     * Get all experience entries including hidden ones (for admin)
     * 
     * @param bool $includeHidden
     * @return array
     */
    public function getAllExperience(bool $includeHidden = false): array
    {
        if ($includeHidden) {
            $sql = "SELECT * FROM experience ORDER BY display_order ASC, start_date DESC";
            return $this->db->fetchAll($sql);
        } else {
            $sql = "SELECT * FROM experience WHERE is_visible = 1 ORDER BY display_order ASC, start_date DESC";
            return $this->db->fetchAll($sql);
        }
    }

    /**
     * Update personal information
     * 
     * @param array $data
     * @return bool
     */
    public function updatePersonalInfo(array $data): bool
    {
        $allowedFields = [
            'full_name', 'title', 'email', 'phone', 'location', 
            'website', 'linkedin', 'github', 'bio', 'profile_image'
        ];
        
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('personal_info', $updateData, '1=1') > 0;
    }

    /**
     * Create new project
     * 
     * @param array $data
     * @return int|false
     */
    public function createProject(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('projects', $data);
    }

    /**
     * Update existing project
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProject(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('projects', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete project
     * 
     * @param int $id
     * @return bool
     */
    public function deleteProject(int $id): bool
    {
        return $this->db->delete('projects', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Create new skill
     * 
     * @param array $data
     * @return int|false
     */
    public function createSkill(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('skills', $data);
    }

    /**
     * Update existing skill
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateSkill(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('skills', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete skill
     * 
     * @param int $id
     * @return bool
     */
    public function deleteSkill(int $id): bool
    {
        return $this->db->delete('skills', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Create new experience entry
     * 
     * @param array $data
     * @return int|false
     */
    public function createExperience(array $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('experience', $data);
    }

    /**
     * Update existing experience entry
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateExperience(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('experience', $data, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete experience entry
     * 
     * @param int $id
     * @return bool
     */
    public function deleteExperience(int $id): bool
    {
        return $this->db->delete('experience', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Get count methods for dashboard statistics
     */
    public function getProjectsCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM projects WHERE is_visible = 1");
        return (int) $result['count'];
    }

    public function getSkillsCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM skills WHERE is_visible = 1");
        return (int) $result['count'];
    }

    public function getExperienceCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM experience WHERE is_visible = 1");
        return (int) $result['count'];
    }

    public function getEducationCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM education WHERE is_visible = 1");
        return (int) $result['count'];
    }

    /**
     * Get cached personal information (optimized for remote database)
     */
    public function getCachedPersonalInfo()
    {
        $sql = "SELECT * FROM personal_info LIMIT 1";
        return $this->db->cachedQuery($sql, [], 300); // 5 minutes cache
    }
    
    /**
     * Get cached projects (optimized for remote database)
     */
    public function getCachedProjects()
    {
        $sql = "SELECT * FROM projects WHERE status != 'archived' ORDER BY priority DESC, created_at DESC";
        $results = $this->db->cachedQuery($sql, [], 600); // 10 minutes cache
        return $results ?: [];
    }
    
    /**
     * Get cached skills (optimized for remote database)
     */
    public function getCachedSkills()
    {
        $sql = "SELECT * FROM skills ORDER BY category ASC, level DESC, name ASC";
        $skills = $this->db->cachedQuery($sql, [], 900); // 15 minutes cache
        
        if (!$skills) return [];
        
        // Group by category
        $grouped = [];
        foreach ($skills as $skill) {
            $category = $skill['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $skill;
        }
        
        return $grouped;
    }
    
    /**
     * Get cached experience (optimized for remote database)
     */
    public function getCachedExperience()
    {
        $sql = "SELECT * FROM experience ORDER BY start_date DESC";
        $results = $this->db->cachedQuery($sql, [], 1200); // 20 minutes cache
        return $results ?: [];
    }
    
    /**
     * Get cached education (optimized for remote database)
     */
    public function getCachedEducation()
    {
        $sql = "SELECT * FROM education ORDER BY start_date DESC";
        $results = $this->db->cachedQuery($sql, [], 1200); // 20 minutes cache
        return $results ?: [];
    }

    /**
     * Invalidate cache for specific tables (remote database optimization)
     */
    public function invalidateCache($table)
    {
        $this->db->invalidateCache($table);
    }
}
