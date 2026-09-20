<?php
namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;

class MaintenancePlan {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    /**
     * Self-healing table creation for MySQL and SQLite
     */
    public function ensureTableExists(): void {
        if (!$this->db) return;

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $sql = "CREATE TABLE IF NOT EXISTS maintenance_plans (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    plan_code TEXT NOT NULL UNIQUE,
                    title TEXT NOT NULL,
                    description TEXT NULL,
                    checklist_scope TEXT NULL,
                    equipment_id INTEGER NULL,
                    category_id INTEGER NULL,
                    assigned_to INTEGER NULL,
                    created_by INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'scheduled',
                    priority TEXT NOT NULL DEFAULT 'medium',
                    recurrence_type TEXT NOT NULL DEFAULT 'one_time',
                    custom_interval_value INTEGER NULL,
                    custom_interval_unit TEXT NULL,
                    scheduled_date DATE NOT NULL,
                    scheduled_time TIME NULL DEFAULT '09:00:00',
                    estimated_duration_minutes INTEGER DEFAULT 60,
                    end_date DATE NULL,
                    last_executed_at DATETIME NULL,
                    next_due_date DATE NULL,
                    notify_email INTEGER DEFAULT 1,
                    recipient_emails TEXT NULL,
                    completion_notes TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );";
                $this->db->exec($sql);
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS maintenance_plans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    plan_code VARCHAR(50) NOT NULL UNIQUE,
                    title VARCHAR(200) NOT NULL,
                    description TEXT NULL,
                    checklist_scope TEXT NULL,
                    equipment_id INT NULL,
                    category_id INT NULL,
                    assigned_to INT NULL,
                    created_by INT NOT NULL,
                    status ENUM('scheduled', 'in_progress', 'completed', 'overdue', 'cancelled') NOT NULL DEFAULT 'scheduled',
                    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
                    recurrence_type ENUM('one_time', 'daily', 'weekly', 'bi_weekly', 'monthly', 'quarterly', 'semi_annually', 'annually', 'custom') NOT NULL DEFAULT 'one_time',
                    custom_interval_value INT NULL,
                    custom_interval_unit ENUM('days', 'weeks', 'months', 'years') NULL,
                    scheduled_date DATE NOT NULL,
                    scheduled_time TIME NULL DEFAULT '09:00:00',
                    estimated_duration_minutes INT DEFAULT 60,
                    end_date DATE NULL,
                    last_executed_at DATETIME NULL,
                    next_due_date DATE NULL,
                    notify_email TINYINT(1) DEFAULT 1,
                    recipient_emails TEXT NULL,
                    completion_notes TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_plan_code (plan_code),
                    INDEX idx_status (status),
                    INDEX idx_scheduled_date (scheduled_date),
                    INDEX idx_next_due_date (next_due_date),
                    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                $this->db->exec($sql);
            }

            // Create pivot table for multiple equipment support
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("CREATE TABLE IF NOT EXISTS maintenance_plan_equipment (
                    plan_id INTEGER NOT NULL,
                    equipment_id INTEGER NOT NULL,
                    PRIMARY KEY (plan_id, equipment_id),
                    FOREIGN KEY (plan_id) REFERENCES maintenance_plans(id) ON DELETE CASCADE,
                    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
                );");
            } else {
                $this->db->exec("CREATE TABLE IF NOT EXISTS maintenance_plan_equipment (
                    plan_id INT NOT NULL,
                    equipment_id INT NOT NULL,
                    PRIMARY KEY (plan_id, equipment_id),
                    FOREIGN KEY (plan_id) REFERENCES maintenance_plans(id) ON DELETE CASCADE,
                    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            }

            // Ensure attachments table exists
            (new MaintenanceAttachment())->ensureTableExists();

            // Seed initial sample datacenter preventive maintenance plans only once upon table creation
            $this->db->exec("CREATE TABLE IF NOT EXISTS app_seed_status (
                seed_key VARCHAR(100) PRIMARY KEY,
                seeded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );");

            $seedCheck = $this->db->query("SELECT 1 FROM app_seed_status WHERE seed_key = 'maintenance_plans_seeded'");
            $alreadySeeded = $seedCheck && (bool)$seedCheck->fetchColumn();

            if (!$alreadySeeded) {
                $countStmt = $this->db->query("SELECT COUNT(*) FROM maintenance_plans");
                if ($countStmt && (int)$countStmt->fetchColumn() === 0) {
                    $this->seedInitialPlans();
                }
                // Record that initial seeding was performed so deleting plans never re-seeds them
                $this->db->exec("INSERT INTO app_seed_status (seed_key) VALUES ('maintenance_plans_seeded')");
            }
        } catch (Exception $e) {
            error_log("MaintenancePlan ensureTableExists error: " . $e->getMessage());
        }
    }

    /**
     * Seed starter datacenter maintenance plans
     */
    private function seedInitialPlans(): void {
        try {
            $userStmt = $this->db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
            $userId = $userStmt ? (int)$userStmt->fetchColumn() : 1;

            $eqStmt = $this->db->query("SELECT id, category_id FROM equipment ORDER BY id ASC LIMIT 5");
            $equipments = $eqStmt ? $eqStmt->fetchAll() : [];

            $today = date('Y-m-d');
            $samplePlans = [
                [
                    'plan_code' => 'PMP-' . date('Ym') . '-CRAC01',
                    'title' => 'CRAC Air Filters & Blower Belt Servicing',
                    'description' => 'Replace air intake filtration media (MERV 13), inspect centrifugal belt tension and calibrate hot-aisle supply sensors.',
                    'checklist_scope' => "- Verify intake air temperature differential\n- Replace primary MERV 13 pre-filters\n- Inspect fan motor pulley alignment and bearing lubrication\n- Check refrigerant pressure gauges and condensate drain pump",
                    'equipment_id' => $equipments[0]['id'] ?? null,
                    'category_id' => $equipments[0]['category_id'] ?? 1,
                    'assigned_to' => $userId,
                    'created_by' => $userId,
                    'status' => 'scheduled',
                    'priority' => 'high',
                    'recurrence_type' => 'quarterly',
                    'scheduled_date' => date('Y-m-d', strtotime('+3 days')),
                    'scheduled_time' => '08:30:00',
                    'estimated_duration_minutes' => 90,
                    'next_due_date' => date('Y-m-d', strtotime('+3 months +3 days')),
                ],
                [
                    'plan_code' => 'PMP-' . date('Ym') . '-UPS01',
                    'title' => 'Modular UPS Battery Impedance & Thermal Scan',
                    'description' => 'Comprehensive cell conductance measurement, terminal torque check and infrared thermographic inspection on battery string A & B.',
                    'checklist_scope' => "- Conduct IR thermography on busbars and battery poles\n- Measure individual cell internal resistance / impedance\n- Confirm ambient battery room temperature is steady at 20-22°C\n- Validate DC ripple voltage and inverter synchronization",
                    'equipment_id' => $equipments[1]['id'] ?? null,
                    'category_id' => $equipments[1]['category_id'] ?? 2,
                    'assigned_to' => $userId,
                    'created_by' => $userId,
                    'status' => 'in_progress',
                    'priority' => 'critical',
                    'recurrence_type' => 'monthly',
                    'scheduled_date' => $today,
                    'scheduled_time' => '10:00:00',
                    'estimated_duration_minutes' => 120,
                    'next_due_date' => date('Y-m-d', strtotime('+1 month')),
                ],
                [
                    'plan_code' => 'PMP-' . date('Ym') . '-GEN01',
                    'title' => 'Diesel Generator No-Load Test & Fuel Polishing',
                    'description' => 'Run 30-minute diesel standby generator diagnostic test, verify automatic transfer switch (ATS) telemetry, and inspect day-tank fuel quality.',
                    'checklist_scope' => "- Check starting lead-acid battery voltage and charger\n- Verify engine coolant level and block heater temperature\n- Test emergency stop circuit and ATS communication\n- Record oil pressure and exhaust gas temperature under idle",
                    'equipment_id' => $equipments[2]['id'] ?? null,
                    'category_id' => $equipments[2]['category_id'] ?? 2,
                    'assigned_to' => $userId,
                    'created_by' => $userId,
                    'status' => 'scheduled',
                    'priority' => 'medium',
                    'recurrence_type' => 'bi_weekly',
                    'scheduled_date' => date('Y-m-d', strtotime('+7 days')),
                    'scheduled_time' => '14:00:00',
                    'estimated_duration_minutes' => 60,
                    'next_due_date' => date('Y-m-d', strtotime('+21 days')),
                ],
                [
                    'plan_code' => 'PMP-' . date('Ym') . '-FIRE01',
                    'title' => 'FM-200 / Clean Agent Cylinder Weight Verification',
                    'description' => 'Check clean agent fire suppression storage cylinders pressure gauges, mechanical actuators and VESDA aspiration smoke detectors.',
                    'checklist_scope' => "- Inspect liquid level gauges and cylinder pressure\n- Blow down VESDA sampling pipes and clean laser chamber filter\n- Confirm abort switches and horn/strobe visual signals\n- Verify EPO (Emergency Power Off) isolation interlocks",
                    'equipment_id' => $equipments[3]['id'] ?? null,
                    'category_id' => $equipments[3]['category_id'] ?? 3,
                    'assigned_to' => $userId,
                    'created_by' => $userId,
                    'status' => 'completed',
                    'priority' => 'high',
                    'recurrence_type' => 'semi_annually',
                    'scheduled_date' => date('Y-m-d', strtotime('-10 days')),
                    'scheduled_time' => '09:00:00',
                    'estimated_duration_minutes' => 120,
                    'last_executed_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'next_due_date' => date('Y-m-d', strtotime('+170 days')),
                    'completion_notes' => 'All 6 FM-200 cylinder pressure dials tested within compliant green zone (360 psi). Smoke aspiration filter cleaned.'
                ]
            ];

            foreach ($samplePlans as $p) {
                $sql = "INSERT INTO maintenance_plans (
                    plan_code, title, description, checklist_scope, equipment_id, category_id,
                    assigned_to, created_by, status, priority, recurrence_type, scheduled_date,
                    scheduled_time, estimated_duration_minutes, last_executed_at, next_due_date, completion_notes
                ) VALUES (
                    :plan_code, :title, :description, :checklist_scope, :equipment_id, :category_id,
                    :assigned_to, :created_by, :status, :priority, :recurrence_type, :scheduled_date,
                    :scheduled_time, :estimated_duration_minutes, :last_executed_at, :next_due_date, :completion_notes
                )";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'plan_code' => $p['plan_code'],
                    'title' => $p['title'],
                    'description' => $p['description'],
                    'checklist_scope' => $p['checklist_scope'],
                    'equipment_id' => $p['equipment_id'],
                    'category_id' => $p['category_id'],
                    'assigned_to' => $p['assigned_to'],
                    'created_by' => $p['created_by'],
                    'status' => $p['status'],
                    'priority' => $p['priority'],
                    'recurrence_type' => $p['recurrence_type'],
                    'scheduled_date' => $p['scheduled_date'],
                    'scheduled_time' => $p['scheduled_time'],
                    'estimated_duration_minutes' => $p['estimated_duration_minutes'],
                    'last_executed_at' => $p['last_executed_at'] ?? null,
                    'next_due_date' => $p['next_due_date'] ?? null,
                    'completion_notes' => $p['completion_notes'] ?? null,
                ]);
            }
        } catch (Exception $e) {
            error_log("Seed initial maintenance plans failed: " . $e->getMessage());
        }
    }

    /**
     * Compute the next due date based on dynamic period / recurrence settings
     */
    public static function calculateNextDueDate(
        string $baseDate,
        string $recurrenceType,
        ?int $customValue = null,
        ?string $customUnit = null
    ): ?string {
        $timestamp = strtotime($baseDate);
        if (!$timestamp) {
            $timestamp = time();
        }

        switch ($recurrenceType) {
            case 'daily':
                return date('Y-m-d', strtotime('+1 day', $timestamp));
            case 'weekly':
                return date('Y-m-d', strtotime('+1 week', $timestamp));
            case 'bi_weekly':
                return date('Y-m-d', strtotime('+2 weeks', $timestamp));
            case 'monthly':
                return date('Y-m-d', strtotime('+1 month', $timestamp));
            case 'quarterly':
                return date('Y-m-d', strtotime('+3 months', $timestamp));
            case 'semi_annually':
                return date('Y-m-d', strtotime('+6 months', $timestamp));
            case 'annually':
                return date('Y-m-d', strtotime('+1 year', $timestamp));
            case 'custom':
                $val = max(1, (int)$customValue);
                $unit = strtolower(trim($customUnit ?? 'days'));
                if (!in_array($unit, ['days', 'weeks', 'months', 'years'])) {
                    $unit = 'days';
                }
                return date('Y-m-d', strtotime("+{$val} {$unit}", $timestamp));
            case 'one_time':
            default:
                return null;
        }
    }

    /**
     * Friendly label for recurrence type
     */
    public static function getRecurrenceLabel(
        string $recurrenceType,
        ?int $customValue = null,
        ?string $customUnit = null
    ): string {
        switch ($recurrenceType) {
            case 'one_time':
                return 'One-Time Plan';
            case 'daily':
                return 'Every Day (Daily)';
            case 'weekly':
                return 'Every Week (Weekly)';
            case 'bi_weekly':
                return 'Every 2 Weeks (Bi-Weekly)';
            case 'monthly':
                return 'Every Month (Monthly)';
            case 'quarterly':
                return 'Every 3 Months (Quarterly)';
            case 'semi_annually':
                return 'Every 6 Months (Semi-Annually)';
            case 'annually':
                return 'Every Year (Annually)';
            case 'custom':
                $val = max(1, (int)$customValue);
                $unit = ucfirst(strtolower(trim($customUnit ?? 'days')));
                return "Custom: Every {$val} {$unit}";
            default:
                return ucfirst(str_replace('_', ' ', $recurrenceType));
        }
    }

    /**
     * Get all maintenance plans with optional filters
     */
    public function all(array $filters = []): array {
        if (!$this->db) return [];

        $sql = "SELECT p.*,
                       e.name as equipment_name, e.serial_number, e.room_location,
                       c.name as category_name, c.icon as category_icon,
                       u.name as assigned_name, u.email as assigned_email,
                       cu.name as creator_name
                FROM maintenance_plans p
                LEFT JOIN equipment e ON p.equipment_id = e.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.assigned_to = u.id
                LEFT JOIN users cu ON p.created_by = cu.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $today = date('Y-m-d');
                $sql .= " AND (p.status = 'overdue' OR (p.status = 'scheduled' AND p.scheduled_date < :today_overdue))";
                $params['today_overdue'] = $today;
            } else {
                $sql .= " AND p.status = :status";
                $params['status'] = $filters['status'];
            }
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND p.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['equipment_id'])) {
            $sql .= " AND (p.equipment_id = :eq_id OR EXISTS (SELECT 1 FROM maintenance_plan_equipment mpe WHERE mpe.plan_id = p.id AND mpe.equipment_id = :eq_id_mpe))";
            $params['eq_id'] = (int)$filters['equipment_id'];
            $params['eq_id_mpe'] = (int)$filters['equipment_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND p.assigned_to = :assigned_to";
            $params['assigned_to'] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                p.title LIKE :s_title 
                OR p.plan_code LIKE :s_code 
                OR p.description LIKE :s_desc 
                OR p.checklist_scope LIKE :s_scope
                OR e.name LIKE :s_eq_name 
                OR e.serial_number LIKE :s_eq_sn 
                OR e.room_location LIKE :s_eq_room 
                OR c.name LIKE :s_cat_name 
                OR u.name LIKE :s_user_name 
                OR u.email LIKE :s_user_email
                OR EXISTS (
                    SELECT 1 FROM maintenance_plan_equipment mpe2 
                    JOIN equipment e2 ON mpe2.equipment_id = e2.id 
                    WHERE mpe2.plan_id = p.id 
                    AND (e2.name LIKE :s_mpe_name OR e2.serial_number LIKE :s_mpe_sn OR e2.room_location LIKE :s_mpe_room)
                )
            )";
            $params['s_title'] = $searchTerm;
            $params['s_code'] = $searchTerm;
            $params['s_desc'] = $searchTerm;
            $params['s_scope'] = $searchTerm;
            $params['s_eq_name'] = $searchTerm;
            $params['s_eq_sn'] = $searchTerm;
            $params['s_eq_room'] = $searchTerm;
            $params['s_cat_name'] = $searchTerm;
            $params['s_user_name'] = $searchTerm;
            $params['s_user_email'] = $searchTerm;
            $params['s_mpe_name'] = $searchTerm;
            $params['s_mpe_sn'] = $searchTerm;
            $params['s_mpe_room'] = $searchTerm;
        }

        $sql .= " ORDER BY 
                    CASE p.status
                        WHEN 'overdue' THEN 1
                        WHEN 'in_progress' THEN 2
                        WHEN 'scheduled' THEN 3
                        WHEN 'completed' THEN 4
                        ELSE 5
                    END,
                    p.scheduled_date ASC, p.scheduled_time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Dynamically evaluate overdue status for display
        $today = date('Y-m-d');
        foreach ($rows as &$row) {
            $row['is_overdue'] = false;
            if ($row['status'] === 'scheduled' && $row['scheduled_date'] < $today) {
                $row['is_overdue'] = true;
            }
            $row['recurrence_label'] = self::getRecurrenceLabel(
                $row['recurrence_type'],
                $row['custom_interval_value'],
                $row['custom_interval_unit']
            );
            $row['equipments'] = $this->getEquipmentsByPlan((int)$row['id']);
            $row['equipment_count'] = count($row['equipments']);
        }

        return $rows;
    }

    /**
     * Sync targeted equipments for a plan
     */
    public function syncEquipments(int $planId, array $equipmentIds): void {
        if (!$this->db) return;

        // Clean existing associations
        $del = $this->db->prepare("DELETE FROM maintenance_plan_equipment WHERE plan_id = ?");
        $del->execute([$planId]);

        $validIds = [];
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertSql = ($driver === 'sqlite')
            ? "INSERT OR IGNORE INTO maintenance_plan_equipment (plan_id, equipment_id) VALUES (?, ?)"
            : "INSERT IGNORE INTO maintenance_plan_equipment (plan_id, equipment_id) VALUES (?, ?)";

        $ins = $this->db->prepare($insertSql);

        foreach ($equipmentIds as $eqId) {
            $eqId = (int)$eqId;
            if ($eqId <= 0) continue;

            $chk = $this->db->prepare("SELECT id FROM equipment WHERE id = ?");
            $chk->execute([$eqId]);
            if ($chk->fetchColumn()) {
                $ins->execute([$planId, $eqId]);
                $validIds[] = $eqId;
            }
        }

        // Keep primary equipment_id column in sync for backward compatibility
        $primaryId = !empty($validIds[0]) ? $validIds[0] : null;
        $up = $this->db->prepare("UPDATE maintenance_plans SET equipment_id = ? WHERE id = ?");
        $up->execute([$primaryId, $planId]);
    }

    /**
     * Get all targeted equipments for a plan
     */
    public function getEquipmentsByPlan(int $planId): array {
        if (!$this->db) return [];

        $sql = "SELECT e.*, c.name as category_name, c.icon as category_icon
                FROM maintenance_plan_equipment mpe
                JOIN equipment e ON mpe.equipment_id = e.id
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE mpe.plan_id = :plan_id
                ORDER BY e.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['plan_id' => $planId]);
        $rows = $stmt->fetchAll();

        // Fallback to legacy single equipment_id if none in pivot table
        if (empty($rows)) {
            $sql2 = "SELECT e.*, c.name as category_name, c.icon as category_icon
                     FROM maintenance_plans p
                     JOIN equipment e ON p.equipment_id = e.id
                     LEFT JOIN categories c ON e.category_id = c.id
                     WHERE p.id = :plan_id";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->execute(['plan_id' => $planId]);
            $rows = $stmt2->fetchAll();
        }

        return $rows;
    }

    /**
     * Find single maintenance plan by ID
     */
    public function findById(int $id): ?array {
        if (!$this->db) return null;

        $sql = "SELECT p.*,
                       e.name as equipment_name, e.serial_number, e.room_location, e.status as equipment_status,
                       c.name as category_name, c.icon as category_icon,
                       u.name as assigned_name, u.email as assigned_email,
                       cu.name as creator_name
                FROM maintenance_plans p
                LEFT JOIN equipment e ON p.equipment_id = e.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.assigned_to = u.id
                LEFT JOIN users cu ON p.created_by = cu.id
                WHERE p.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            $row['is_overdue'] = ($row['status'] === 'scheduled' && $row['scheduled_date'] < date('Y-m-d'));
            $row['recurrence_label'] = self::getRecurrenceLabel(
                $row['recurrence_type'],
                $row['custom_interval_value'],
                $row['custom_interval_unit']
            );
            $row['equipments'] = $this->getEquipmentsByPlan($id);
            $attModel = new MaintenanceAttachment();
            $row['attachments'] = $attModel->getByPlan($id);
            return $row;
        }

        return null;
    }

    /**
     * Create a new preventive maintenance plan
     */
    public function create(array $data): int|bool {
        if (!$this->db) return false;

        $planCode = 'PMP-' . date('Ym') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $recurrence = $data['recurrence_type'] ?? 'one_time';
        $customVal = !empty($data['custom_interval_value']) ? (int)$data['custom_interval_value'] : null;
        $customUnit = !empty($data['custom_interval_unit']) ? $data['custom_interval_unit'] : null;
        $scheduledDate = $data['scheduled_date'] ?? date('Y-m-d');
        
        $nextDue = self::calculateNextDueDate($scheduledDate, $recurrence, $customVal, $customUnit);

        // Sanitize foreign keys to prevent constraint violations
        $equipmentId = !empty($data['equipment_id']) ? (int)$data['equipment_id'] : null;
        if ($equipmentId) {
            $chk = $this->db->prepare("SELECT id FROM equipment WHERE id = ?");
            $chk->execute([$equipmentId]);
            if (!$chk->fetchColumn()) $equipmentId = null;
        }

        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        if ($categoryId) {
            $chk = $this->db->prepare("SELECT id FROM categories WHERE id = ?");
            $chk->execute([$categoryId]);
            if (!$chk->fetchColumn()) $categoryId = null;
        }

        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        if ($assignedTo) {
            $chk = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $chk->execute([$assignedTo]);
            if (!$chk->fetchColumn()) $assignedTo = null;
        }

        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : 1;
        $chkUser = $this->db->prepare("SELECT id FROM users WHERE id = ?");
        $chkUser->execute([$createdBy]);
        if (!$chkUser->fetchColumn()) {
            $firstUser = $this->db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
            $createdBy = $firstUser ?: 1;
        }

        $sql = "INSERT INTO maintenance_plans (
                    plan_code, title, description, checklist_scope, equipment_id, category_id,
                    assigned_to, created_by, status, priority, recurrence_type, custom_interval_value,
                    custom_interval_unit, scheduled_date, scheduled_time, estimated_duration_minutes,
                    end_date, next_due_date, notify_email, recipient_emails
                ) VALUES (
                    :plan_code, :title, :description, :checklist_scope, :equipment_id, :category_id,
                    :assigned_to, :created_by, :status, :priority, :recurrence_type, :custom_interval_value,
                    :custom_interval_unit, :scheduled_date, :scheduled_time, :estimated_duration_minutes,
                    :end_date, :next_due_date, :notify_email, :recipient_emails
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'plan_code' => $planCode,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'checklist_scope' => $data['checklist_scope'] ?? null,
            'equipment_id' => $equipmentId,
            'category_id' => $categoryId,
            'assigned_to' => $assignedTo,
            'created_by' => $createdBy,
            'status' => $data['status'] ?? 'scheduled',
            'priority' => $data['priority'] ?? 'medium',
            'recurrence_type' => $recurrence,
            'custom_interval_value' => $customVal,
            'custom_interval_unit' => $customUnit,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $data['scheduled_time'] ?? '09:00:00',
            'estimated_duration_minutes' => !empty($data['estimated_duration_minutes']) ? (int)$data['estimated_duration_minutes'] : 60,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'next_due_date' => $nextDue,
            'notify_email' => isset($data['notify_email']) ? (int)$data['notify_email'] : 1,
            'recipient_emails' => $data['recipient_emails'] ?? null,
        ]);

        $planId = (int)$this->db->lastInsertId();
        if ($planId) {
            $eqIds = !empty($data['equipment_ids']) && is_array($data['equipment_ids'])
                ? $data['equipment_ids']
                : (!empty($equipmentId) ? [$equipmentId] : []);
            if (!empty($eqIds)) {
                $this->syncEquipments($planId, $eqIds);
            }
        }

        return $planId;
    }

    /**
     * Update an existing maintenance plan
     */
    public function update(int $id, array $data): bool {
        if (!$this->db) return false;

        $recurrence = $data['recurrence_type'] ?? 'one_time';
        $customVal = !empty($data['custom_interval_value']) ? (int)$data['custom_interval_value'] : null;
        $customUnit = !empty($data['custom_interval_unit']) ? $data['custom_interval_unit'] : null;
        $scheduledDate = $data['scheduled_date'] ?? date('Y-m-d');
        
        $nextDue = self::calculateNextDueDate($scheduledDate, $recurrence, $customVal, $customUnit);

        // Sanitize foreign keys
        $equipmentId = !empty($data['equipment_id']) ? (int)$data['equipment_id'] : null;
        if ($equipmentId) {
            $chk = $this->db->prepare("SELECT id FROM equipment WHERE id = ?");
            $chk->execute([$equipmentId]);
            if (!$chk->fetchColumn()) $equipmentId = null;
        }

        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        if ($categoryId) {
            $chk = $this->db->prepare("SELECT id FROM categories WHERE id = ?");
            $chk->execute([$categoryId]);
            if (!$chk->fetchColumn()) $categoryId = null;
        }

        $assignedTo = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
        if ($assignedTo) {
            $chk = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $chk->execute([$assignedTo]);
            if (!$chk->fetchColumn()) $assignedTo = null;
        }

        $sql = "UPDATE maintenance_plans SET
                    title = :title,
                    description = :description,
                    checklist_scope = :checklist_scope,
                    equipment_id = :equipment_id,
                    category_id = :category_id,
                    assigned_to = :assigned_to,
                    status = :status,
                    priority = :priority,
                    recurrence_type = :recurrence_type,
                    custom_interval_value = :custom_interval_value,
                    custom_interval_unit = :custom_interval_unit,
                    scheduled_date = :scheduled_date,
                    scheduled_time = :scheduled_time,
                    estimated_duration_minutes = :estimated_duration_minutes,
                    end_date = :end_date,
                    next_due_date = :next_due_date,
                    notify_email = :notify_email,
                    recipient_emails = :recipient_emails
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'checklist_scope' => $data['checklist_scope'] ?? null,
            'equipment_id' => $equipmentId,
            'category_id' => $categoryId,
            'assigned_to' => $assignedTo,
            'status' => $data['status'] ?? 'scheduled',
            'priority' => $data['priority'] ?? 'medium',
            'recurrence_type' => $recurrence,
            'custom_interval_value' => $customVal,
            'custom_interval_unit' => $customUnit,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $data['scheduled_time'] ?? '09:00:00',
            'estimated_duration_minutes' => !empty($data['estimated_duration_minutes']) ? (int)$data['estimated_duration_minutes'] : 60,
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'next_due_date' => $nextDue,
            'notify_email' => isset($data['notify_email']) ? (int)$data['notify_email'] : 1,
            'recipient_emails' => $data['recipient_emails'] ?? null,
        ]);

        if ($success) {
            if (isset($data['equipment_ids']) && is_array($data['equipment_ids'])) {
                $this->syncEquipments($id, $data['equipment_ids']);
            } elseif (!empty($equipmentId)) {
                $this->syncEquipments($id, [$equipmentId]);
            }
        }

        return $success;
    }

    /**
     * Update status (e.g. from Kanban drag-and-drop or modal)
     */
    public function updateStatus(int $id, string $status, ?string $notes = null): bool {
        if (!$this->db) return false;

        $cleanNotes = (!empty($notes) && trim($notes) !== '') ? trim($notes) : null;

        if ($status === 'completed') {
            return $this->markComplete($id, $cleanNotes ?: 'Completed from Kanban / quick status change.');
        }

        $sql = "UPDATE maintenance_plans 
                SET status = :status, 
                    completion_notes = COALESCE(:notes, completion_notes)
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'notes' => $cleanNotes
        ]);
    }

    /**
     * Mark plan as complete and advance next due date for recurring schedules
     */
    public function markComplete(int $id, ?string $completionNotes = null): bool {
        $plan = $this->findById($id);
        if (!$plan) return false;

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // If it's a recurring plan, calculate the subsequent due date
        $recurrence = $plan['recurrence_type'] ?? 'one_time';
        $nextDue = null;
        if ($recurrence !== 'one_time') {
            $base = $plan['next_due_date'] ?: $today;
            if ($base < $today) {
                $base = $today;
            }
            $nextDue = self::calculateNextDueDate(
                $base,
                $recurrence,
                $plan['custom_interval_value'],
                $plan['custom_interval_unit']
            );
        }

        $sql = "UPDATE maintenance_plans SET
                    status = 'completed',
                    last_executed_at = :now,
                    next_due_date = :next_due_date,
                    completion_notes = :notes
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'now' => $now,
            'next_due_date' => $nextDue,
            'notes' => $completionNotes ?: 'Completed as scheduled.'
        ]);
    }

    /**
     * Delete a plan
     */
    public function delete(int $id): bool {
        if (!$this->db) return false;

        // Clean up attached physical files and database records
        (new MaintenanceAttachment())->deleteByPlan($id);

        // Clean up equipment pivot associations
        $delMpe = $this->db->prepare("DELETE FROM maintenance_plan_equipment WHERE plan_id = ?");
        $delMpe->execute([$id]);

        $stmt = $this->db->prepare("DELETE FROM maintenance_plans WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * High-level KPI metric counts
     */
    public function getStats(): array {
        if (!$this->db) {
            return ['total' => 0, 'scheduled' => 0, 'in_progress' => 0, 'overdue' => 0, 'completed' => 0];
        }

        $all = $this->all();
        $total = count($all);
        $scheduled = 0;
        $inProgress = 0;
        $overdue = 0;
        $completed = 0;

        foreach ($all as $p) {
            if ($p['status'] === 'completed') {
                $completed++;
            } elseif ($p['status'] === 'in_progress') {
                $inProgress++;
            } elseif ($p['status'] === 'overdue' || !empty($p['is_overdue'])) {
                $overdue++;
            } else {
                $scheduled++;
            }
        }

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'completed' => $completed
        ];
    }

    /**
     * Return formatted events for Calendar view
     */
    public function getCalendarEvents(?string $start = null, ?string $end = null): array {
        $plans = $this->all();
        $events = [];

        foreach ($plans as $p) {
            $date = $p['scheduled_date'];
            if ($start && $date < $start) continue;
            if ($end && $date > $end) continue;

            $status = $p['status'];
            if ($p['is_overdue']) {
                $status = 'overdue';
            }

            // Map priority to visual accent color
            $priorityColors = [
                'low' => '#64748b',
                'medium' => '#3b82f6',
                'high' => '#f97316',
                'critical' => '#ef4444'
            ];
            $color = $priorityColors[$p['priority']] ?? '#3b82f6';

            $events[] = [
                'id' => $p['id'],
                'code' => $p['plan_code'],
                'title' => $p['title'],
                'date' => $date,
                'time' => substr($p['scheduled_time'] ?? '09:00', 0, 5),
                'equipment' => $p['equipment_name'] ?? 'Facility General',
                'room' => $p['room_location'] ?? '',
                'category' => $p['category_name'] ?? '',
                'assigned' => $p['assigned_name'] ?? 'Unassigned',
                'status' => $status,
                'priority' => $p['priority'],
                'recurrence' => $p['recurrence_label'],
                'color' => $color,
                'duration' => $p['estimated_duration_minutes'] . 'm'
            ];
        }

        return $events;
    }
}
