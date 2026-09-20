<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class InspectionItem {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByCategory(int $categoryId): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM inspection_items WHERE category_id = :category_id ORDER BY id ASC");
        $stmt->execute(['category_id' => $categoryId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM inspection_items WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        $sql = "INSERT INTO inspection_items (category_id, title, description, is_critical) 
                VALUES (:category_id, :title, :description, :is_critical)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_critical' => $data['is_critical'] ?? 0
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (!$this->db) return false;
        $sql = "UPDATE inspection_items SET title = :title, description = :description, is_critical = :is_critical WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_critical' => $data['is_critical'] ?? 0
        ]);
    }

    public function delete(int $id): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM inspection_items WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
