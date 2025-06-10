<?php

namespace App\Models;

/**
 * Admin Model
 * Handles admin user operations
 */
class AdminModel extends BaseModel
{
    protected $table = 'admin_users';
    
    /**
     * Find admin user by username
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        return $this->db->fetch($sql, ['username' => $username]);
    }
    
    /**
     * Find admin user by email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->db->fetch($sql, ['email' => $email]);
    }
    
    /**
     * Create new admin user
     */
    public function createAdmin($data)
    {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        return $this->create($data);
    }
    
    /**
     * Update admin password
     */
    public function updatePassword($id, $newPassword)
    {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($id, [
            'password_hash' => $passwordHash
        ]);
    }
    
    /**
     * Log admin activity
     */
    public function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null)
    {
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ];
        
        return $this->db->insert('admin_logs', $logData);
    }
    
    /**
     * Get admin activity logs
     */
    public function getActivityLogs($userId = null, $limit = 50)
    {
        $sql = "SELECT al.*, au.username FROM admin_logs al 
                JOIN admin_users au ON al.user_id = au.id";
        
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE al.user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT {$limit}";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get contact messages with filtering and pagination
     * 
     * @param array $filters
     * @return array
     */
    public function getContactMessages(array $filters = []): array
    {
        $sql = "SELECT * FROM contact_messages WHERE 1=1";
        $params = [];
        
        // Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'read') {
                $sql .= " AND is_read = 1";
            } elseif ($filters['status'] === 'unread') {
                $sql .= " AND is_read = 0";
            }
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        // Pagination
        if (isset($filters['limit']) && isset($filters['offset'])) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = (int) $filters['limit'];
            $params['offset'] = (int) $filters['offset'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get contact messages count with filtering
     * 
     * @param array $filters
     * @return int
     */
    public function getContactMessagesCount(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM contact_messages WHERE 1=1";
        $params = [];
        
        // Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'read') {
                $sql .= " AND is_read = 1";
            } elseif ($filters['status'] === 'unread') {
                $sql .= " AND is_read = 0";
            }
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $result = $this->db->fetch($sql, $params);
        return (int) $result['count'];
    }

    /**
     * Update contact message status (read/unread)
     * 
     * @param int $messageId
     * @param bool $isRead
     * @return bool
     */
    public function updateContactMessageStatus(int $messageId, bool $isRead): bool
    {
        $data = [
            'is_read' => $isRead ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('contact_messages', $data, 'id = :id', ['id' => $messageId]) > 0;
    }

    /**
     * Delete contact message
     * 
     * @param int $messageId
     * @return bool
     */
    public function deleteContactMessage(int $messageId): bool
    {
        return $this->db->delete('contact_messages', 'id = :id', ['id' => $messageId]) > 0;
    }

    /**
     * Get unread messages count
     * 
     * @return int
     */
    public function getUnreadMessagesCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
        return (int) $result['count'];
    }

    /**
     * Get total messages count
     * 
     * @return int
     */
    public function getTotalMessagesCount(): int
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM contact_messages");
        return (int) $result['count'];
    }

    /**
     * Get recent messages for dashboard
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentMessages(int $limit = 5): array
    {
        $sql = "SELECT id, name, email, subject, created_at, is_read 
                FROM contact_messages 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }

    /**
     * Log admin action
     * 
     * @param int $adminId
     * @param string $action
     * @param array $details
     * @return int|false
     */
    public function logAdminAction(int $adminId, string $action, array $details = [])
    {
        $data = [
            'admin_id' => $adminId,
            'action' => $action,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('admin_logs', $data);
    }

    /**
     * Get admin action logs with pagination
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAdminLogs(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT al.*, au.username 
                FROM admin_logs al 
                LEFT JOIN admin_users au ON al.admin_id = au.id 
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
}
