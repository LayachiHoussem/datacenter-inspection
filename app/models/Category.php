<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Category {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT c.*, COUNT(e.id) as equipment_count 
                                  FROM categories c 
                                  LEFT JOIN equipment e ON e.category_id = c.id 
                                  GROUP BY c.id ORDER BY c.name ASC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
            $sql = "INSERT INTO categories (name, slug, description, icon) VALUES (:name, :slug, :description, :icon)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? 'fa-list-check'
            ]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Category create error: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool {
        if (!$this->db) return false;
        try {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name'])));
            $sql = "UPDATE categories SET name = :name, slug = :slug, description = :description, icon = :icon WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? 'fa-list-check'
            ]);
        } catch (\PDOException $e) {
            error_log("Category update error: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
