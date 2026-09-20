<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Inspection {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array {
        if (!$this->db) return [];
        $sql = "SELECT i.*, u.name as inspector_name, c.name as category_name, e.name as equipment_name 
                FROM inspections i 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN categories c ON i.category_id = c.id 
                LEFT JOIN equipment e ON i.equipment_id = e.id 
                ORDER BY i.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $sql = "SELECT i.*, u.name as inspector_name, u.email as inspector_email, c.name as category_name, e.name as equipment_name, e.serial_number 
                FROM inspections i 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN categories c ON i.category_id = c.id 
                LEFT JOIN equipment e ON i.equipment_id = e.id 
                WHERE i.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        $refCode = 'INSP-' . date('Y-m-d') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $status = $data['status'] ?? STATUS_PENDING;
        $isCompleted = ($status === STATUS_COMPLETED || $status === 'completed');
        $startedAt = $data['started_at'] ?? date('Y-m-d H:i:s');
        $completedAt = $data['completed_at'] ?? ($isCompleted ? date('Y-m-d H:i:s') : null);

        $sql = "INSERT INTO inspections (title, reference_code, user_id, category_id, equipment_id, status, notes, started_at, completed_at) 
                VALUES (:title, :reference_code, :user_id, :category_id, :equipment_id, :status, :notes, :started_at, :completed_at)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'reference_code' => $refCode,
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'equipment_id' => $data['equipment_id'] ?? null,
            'status' => $status,
            'notes' => $data['notes'] ?? null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $notes = null): bool {
        if (!$this->db) return false;
        $isCompleted = ($status === STATUS_COMPLETED || $status === 'completed');
        $completedAtClause = $isCompleted ? ", completed_at = COALESCE(completed_at, NOW())" : ", completed_at = NULL";
        $sql = "UPDATE inspections SET status = :status, notes = COALESCE(:notes, notes) {$completedAtClause} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'notes' => $notes
        ]);
    }

    public function update(int $id, array $data): bool {
        if (!$this->db) return false;
        $fields = [];
        $params = ['id' => $id];
        foreach (['title', 'notes', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        if (isset($data['status'])) {
            if ($data['status'] === STATUS_COMPLETED || $data['status'] === 'completed') {
                $fields[] = "completed_at = COALESCE(completed_at, NOW())";
            } else {
                $fields[] = "completed_at = NULL";
            }
        }
        if (empty($fields)) return true;
        $sql = "UPDATE inspections SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM inspections WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
