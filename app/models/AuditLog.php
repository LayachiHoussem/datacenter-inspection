<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class AuditLog {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log(string $action, string $entityType, ?int $entityId = null, ?string $details = null): bool {
        if (!$this->db) return false;
        $user = auth_user();
        $userId = $user ? $user['id'] : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => $ip
        ]);
    }

    public function getRecent(int $limit = 50): array {
        if (!$this->db) return [];
        $sql = "SELECT a.*, u.name as user_name 
                FROM audit_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
