<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class InspectionResult {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByInspection(int $inspectionId): array {
        if (!$this->db) return [];
        $sql = "SELECT r.*, 
                       r.result_status as status,
                       COALESCE(eq.name, item.title) as item_title, 
                       COALESCE(eq.room_location, item.description) as item_description, 
                       c.name as category_name,
                       COALESCE(item.is_critical, 0) as is_critical 
                FROM inspection_results r 
                LEFT JOIN equipment eq ON r.equipment_id = eq.id 
                LEFT JOIN inspection_items item ON r.item_id = item.id 
                LEFT JOIN categories c ON eq.category_id = c.id
                WHERE r.inspection_id = :inspection_id 
                ORDER BY r.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['inspection_id' => $inspectionId]);
        return $stmt->fetchAll();
    }

    public function saveResults(int $inspectionId, array $results): bool {
        if (!$this->db) return false;
        
        $this->db->beginTransaction();
        try {
            // Clear existing results for fresh save
            $stmtDel = $this->db->prepare("DELETE FROM inspection_results WHERE inspection_id = :inspection_id");
            $stmtDel->execute(['inspection_id' => $inspectionId]);

            $stmtIns = $this->db->prepare("INSERT INTO inspection_results (inspection_id, equipment_id, item_id, result_status, notes) VALUES (:inspection_id, :equipment_id, :item_id, :result_status, :notes)");
            
            foreach ($results as $id => $itemData) {
                $equipmentId = $itemData['equipment_id'] ?? $id;
                $itemId = $itemData['item_id'] ?? null;

                $stmtIns->execute([
                    'inspection_id' => $inspectionId,
                    'equipment_id' => $equipmentId,
                    'item_id' => $itemId,
                    'result_status' => $itemData['status'] ?? RESULT_PASS,
                    'notes' => $itemData['notes'] ?? null
                ]);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Save Inspection Results Error: " . $e->getMessage());
            return false;
        }
    }
}
