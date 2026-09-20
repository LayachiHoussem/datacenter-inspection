<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class ReportPhoto {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByInspection(int $inspectionId): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM report_photos WHERE inspection_id = :inspection_id ORDER BY uploaded_at DESC");
        $stmt->execute(['inspection_id' => $inspectionId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int|bool {
        if (!$this->db) return false;
        $sql = "INSERT INTO report_photos (inspection_id, item_id, file_path, caption) 
                VALUES (:inspection_id, :item_id, :file_path, :caption)";
        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute([
                'inspection_id' => $data['inspection_id'],
                'item_id' => $data['item_id'] ?? null,
                'file_path' => $data['file_path'],
                'caption' => $data['caption'] ?? null
            ]);
            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            // If foreign key constraint failed on item_id, fallback to inserting with item_id as null
            if (!empty($data['item_id'])) {
                try {
                    $stmt->execute([
                        'inspection_id' => $data['inspection_id'],
                        'item_id' => null,
                        'file_path' => $data['file_path'],
                        'caption' => $data['caption'] ?? null
                    ]);
                    return (int) $this->db->lastInsertId();
                } catch (\PDOException $fallbackEx) {
                    error_log("ReportPhoto insert fallback error: " . $fallbackEx->getMessage());
                    return false;
                }
            }
            error_log("ReportPhoto insert error: " . $e->getMessage());
            return false;
        }
    }
}
