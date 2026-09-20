<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Report {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(): array {
        if (!$this->db) return [];
        $sql = "SELECT r.*, i.reference_code, i.title as inspection_title, i.status as inspection_status, u.name as generated_by_name 
                FROM reports r 
                LEFT JOIN inspections i ON r.inspection_id = i.id 
                LEFT JOIN users u ON r.generated_by = u.id 
                ORDER BY r.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array {
        if (!$this->db) return null;
        $sql = "SELECT r.*, i.reference_code, i.title as inspection_title, i.status as inspection_status, i.category_id, u.name as generated_by_name 
                FROM reports r 
                LEFT JOIN inspections i ON r.inspection_id = i.id 
                LEFT JOIN users u ON r.generated_by = u.id 
                WHERE r.id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByInspection(int $inspectionId): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM reports WHERE inspection_id = :inspection_id LIMIT 1");
        $stmt->execute(['inspection_id' => $inspectionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByInspection(int $inspectionId): ?array {
        return $this->findByInspection($inspectionId);
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        $sql = "INSERT INTO reports (inspection_id, generated_by, summary, score, file_path) 
                VALUES (:inspection_id, :generated_by, :summary, :score, :file_path)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'inspection_id' => $data['inspection_id'],
            'generated_by' => $data['generated_by'],
            'summary' => $data['summary'] ?? null,
            'score' => $data['score'] ?? 100.00,
            'file_path' => $data['file_path'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (!$this->db) return false;
        $sql = "UPDATE reports SET 
                generated_by = :generated_by, 
                summary = :summary, 
                score = :score, 
                file_path = :file_path 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'generated_by' => $data['generated_by'],
            'summary' => $data['summary'] ?? null,
            'score' => $data['score'] ?? 100.00,
            'file_path' => $data['file_path'] ?? null
        ]);
    }
}
