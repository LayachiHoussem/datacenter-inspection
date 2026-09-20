<?php
namespace App\Models;

use App\Config\Database;
use App\Services\FileUploadService;
use PDO;
use Exception;

class MaintenanceAttachment {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    /**
     * Self-healing table creation
     */
    public function ensureTableExists(): void {
        if (!$this->db) return;

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $sql = "CREATE TABLE IF NOT EXISTS maintenance_attachments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    plan_id INTEGER NOT NULL,
                    file_name TEXT NOT NULL,
                    original_name TEXT NOT NULL,
                    file_path TEXT NOT NULL,
                    file_size INTEGER DEFAULT 0,
                    file_type TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (plan_id) REFERENCES maintenance_plans(id) ON DELETE CASCADE
                );";
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS maintenance_attachments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    plan_id INT NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    file_size INT DEFAULT 0,
                    file_type VARCHAR(100) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_plan_id (plan_id),
                    FOREIGN KEY (plan_id) REFERENCES maintenance_plans(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            }
            $this->db->exec($sql);
        } catch (Exception $e) {
            error_log("MaintenanceAttachment ensureTableExists error: " . $e->getMessage());
        }
    }

    /**
     * Get all attachments for a specific maintenance plan
     */
    public function getByPlan(int $planId): array {
        if (!$this->db) return [];

        $stmt = $this->db->prepare("SELECT * FROM maintenance_attachments WHERE plan_id = :plan_id ORDER BY created_at DESC");
        $stmt->execute(['plan_id' => $planId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['formatted_size'] = self::formatBytes((int)($row['file_size'] ?? 0));
            $row['icon'] = self::getFileIcon($row['file_name'] ?? '');
            $row['is_image'] = self::isImage($row['file_name'] ?? '');
        }

        return $rows;
    }

    /**
     * Find attachment by ID
     */
    public function findById(int $id): ?array {
        if (!$this->db) return null;

        $stmt = $this->db->prepare("SELECT * FROM maintenance_attachments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            $row['formatted_size'] = self::formatBytes((int)($row['file_size'] ?? 0));
            $row['icon'] = self::getFileIcon($row['file_name'] ?? '');
            $row['is_image'] = self::isImage($row['file_name'] ?? '');
            return $row;
        }

        return null;
    }

    /**
     * Add attachment record
     */
    public function create(int $planId, array $fileData): int|bool {
        if (!$this->db) return false;

        $sql = "INSERT INTO maintenance_attachments (plan_id, file_name, original_name, file_path, file_size, file_type)
                VALUES (:plan_id, :file_name, :original_name, :file_path, :file_size, :file_type)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'plan_id' => $planId,
            'file_name' => $fileData['file_name'],
            'original_name' => $fileData['original_name'] ?? $fileData['file_name'],
            'file_path' => $fileData['file_path'],
            'file_size' => (int)($fileData['file_size'] ?? 0),
            'file_type' => $fileData['file_type'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Delete attachment from database and physically delete file from disk
     */
    public function delete(int $id): bool {
        if (!$this->db) return false;

        $att = $this->findById($id);
        if (!$att) return false;

        // Delete physical file
        if (!empty($att['file_path'])) {
            FileUploadService::deleteFile($att['file_path']);
        }

        $stmt = $this->db->prepare("DELETE FROM maintenance_attachments WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Delete all attachments for a plan (when plan is deleted)
     */
    public function deleteByPlan(int $planId): bool {
        $attachments = $this->getByPlan($planId);
        foreach ($attachments as $att) {
            if (!empty($att['file_path'])) {
                FileUploadService::deleteFile($att['file_path']);
            }
        }

        $stmt = $this->db->prepare("DELETE FROM maintenance_attachments WHERE plan_id = :plan_id");
        return $stmt->execute(['plan_id' => $planId]);
    }

    public static function formatBytes(int $bytes, int $precision = 1): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function getFileIcon(string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'fa-file-pdf',
            'doc', 'docx' => 'fa-file-word',
            'xls', 'xlsx', 'csv' => 'fa-file-excel',
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'fa-file-image',
            'zip', 'rar', 'tar', 'gz' => 'fa-file-zipper',
            'txt' => 'fa-file-lines',
            default => 'fa-file'
        };
    }

    public static function isImage(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }
}
