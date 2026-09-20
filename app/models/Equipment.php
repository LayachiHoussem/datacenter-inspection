<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class Equipment {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array {
        return $this->filter();
    }

    public function filter(array $filters = []): array {
        if (!$this->db) return [];
        $sql = "SELECT e.*, c.name as category_name 
                FROM equipment e 
                LEFT JOIN categories c ON e.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= " AND e.category_id = :category_id";
            $params['category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['name'])) {
            $sql .= " AND (e.name LIKE :name_search OR e.serial_number LIKE :serial_search)";
            $searchTerm = '%' . trim($filters['name']) . '%';
            $params['name_search'] = $searchTerm;
            $params['serial_search'] = $searchTerm;
        }

        $sql .= " ORDER BY e.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT e.*, c.name as category_name FROM equipment e LEFT JOIN categories c ON e.category_id = c.id WHERE e.id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySerialNumber(string $serialNumber, ?int $excludeId = null): ?array {
        if (!$this->db) return null;
        $sql = "SELECT * FROM equipment WHERE serial_number = :serial_number";
        $params = ['serial_number' => trim($serialNumber)];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCategory(int $categoryId): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM equipment WHERE category_id = :category_id ORDER BY name ASC");
        $stmt->execute(['category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        try {
            $sql = "INSERT INTO equipment (name, serial_number, category_id, room_location, status) 
                    VALUES (:name, :serial_number, :category_id, :room_location, :status)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'serial_number' => $data['serial_number'],
                'category_id' => $data['category_id'],
                'room_location' => $data['room_location'],
                'status' => $data['status'] ?? EQUIPMENT_ACTIVE
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Equipment create error: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool {
        if (!$this->db) return false;
        try {
            $sql = "UPDATE equipment 
                    SET name = :name, serial_number = :serial_number, category_id = :category_id, room_location = :room_location, status = :status 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'serial_number' => $data['serial_number'],
                'category_id' => $data['category_id'],
                'room_location' => $data['room_location'],
                'status' => $data['status']
            ]);
        } catch (PDOException $e) {
            error_log("Equipment update error: " . $e->getMessage());
            return false;
        }
    }

    public function updateLastInspected(int $id): bool {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare("UPDATE equipment SET last_inspected_at = NOW() WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Equipment updateLastInspected error: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare("DELETE FROM equipment WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Equipment delete error: " . $e->getMessage());
            return false;
        }
    }
}
