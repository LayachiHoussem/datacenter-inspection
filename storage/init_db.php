<?php
/**
 * Datacenter Inspection System - Database Initialization Helper
 */

require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/config/database.php';

echo "Initializing Database...\n";

// Test MySQL connection first
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: 3306;
$dbName = getenv('DB_NAME') ?: 'datacenter_inspection';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$driver = 'sqlite';
$pdo = null;

try {
    // Try connecting to MySQL
    $rawPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $rawPdo->exec("USE `{$dbName}`;");
    $pdo = $rawPdo;
    $driver = 'mysql';
    echo "Connected to MySQL successfully!\n";
} catch (\Exception $e) {
    echo "MySQL connection not active ({$e->getMessage()}). Using SQLite fallback...\n";
    $sqlitePath = __DIR__ . '/database.sqlite';
    $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $driver = 'sqlite';
    putenv("DB_DRIVER=sqlite");
    putenv("DB_SQLITE_PATH={$sqlitePath}");
}

if ($driver === 'mysql') {
    $schemaSql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $seedSql = file_get_contents(__DIR__ . '/../database/seed.sql');

    $pdo->exec($schemaSql);
    echo "MySQL Schema imported successfully.\n";
    $pdo->exec($seedSql);
    echo "MySQL Seed data imported successfully.\n";
} else {
    // SQLite DDL Creation
    $sqliteSchema = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'inspector',
        status TEXT NOT NULL DEFAULT 'active',
        avatar TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT NULL,
        icon TEXT DEFAULT 'fa-list-check',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS equipment (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        serial_number TEXT NOT NULL UNIQUE,
        category_id INTEGER NOT NULL,
        room_location TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'active',
        last_inspected_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS inspections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        reference_code TEXT NOT NULL UNIQUE,
        user_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        equipment_id INTEGER NULL,
        status TEXT NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        started_at DATETIME NULL,
        completed_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS inspection_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT NULL,
        is_critical INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS inspection_results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inspection_id INTEGER NOT NULL,
        equipment_id INTEGER NULL,
        item_id INTEGER NULL,
        result_status TEXT NOT NULL DEFAULT 'pass',
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS reports (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inspection_id INTEGER NOT NULL,
        generated_by INTEGER NOT NULL,
        summary TEXT NULL,
        score REAL DEFAULT 100.00,
        file_path TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS report_photos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        inspection_id INTEGER NOT NULL,
        item_id INTEGER NULL,
        file_path TEXT NOT NULL,
        caption TEXT NULL,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        action TEXT NOT NULL,
        entity_type TEXT NOT NULL,
        entity_id INTEGER NULL,
        details TEXT NULL,
        ip_address TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS maintenance_plans (
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
    );
    ";

    $pdo->exec($sqliteSchema);
    echo "SQLite Schema created successfully.\n";

    // Check if seeded
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
        $inspectorPass = password_hash('password123', PASSWORD_BCRYPT);

        $pdo->exec("INSERT INTO users (id, name, email, password, role, status) VALUES
            (1, 'System Administrator', 'admin@datacenter.local', '{$adminPass}', 'admin', 'active'),
            (2, 'Datacenter Inspector', 'inspector@datacenter.local', '{$inspectorPass}', 'inspector', 'active'),
            (3, 'Facility Manager', 'manager@datacenter.local', '{$inspectorPass}', 'manager', 'active');");

        $pdo->exec("INSERT INTO categories (id, name, slug, description, icon) VALUES
            (1, 'HVAC & Environmental Cooling', 'hvac-cooling', 'CRAC/CRAH units, airflow, temp & humidity controls.', 'fa-snowflake'),
            (2, 'UPS & Power Distribution', 'ups-power', 'UPS banks, PDU units, generators, and transfer switches.', 'fa-bolt'),
            (3, 'Fire Suppression & Safety', 'fire-suppression', 'FM-200 gas systems, smoke detection, emergency power-off.', 'fa-fire-extinguisher'),
            (4, 'Server Racks & Cabling', 'rack-cabling', 'Structured fiber cabling, patch panels, cabinet locks.', 'fa-server'),
            (5, 'Physical Security & Access', 'security-access', 'Biometric scanners, CCTV coverage, door sensors.', 'fa-shield-halved');");

        $pdo->exec("INSERT INTO equipment (id, name, serial_number, category_id, room_location, status, last_inspected_at) VALUES
            (1, 'Schneider CRAC Unit Alpha', 'SN-CRAC-2024-001', 1, 'Data Hall A - Zone 1', 'active', '2026-08-25 10:00:00'),
            (2, 'APC Symmetra PX 160kW UPS', 'SN-UPS-2023-089', 2, 'Power Room West', 'active', '2026-08-26 14:30:00'),
            (3, 'VESDA Laser Smoke Detector Unit 1', 'SN-FIRE-2022-104', 3, 'Data Hall A - Overhead', 'active', '2026-08-20 09:15:00'),
            (4, 'Rack Cluster A01-A10 PDU Pair', 'SN-PDU-2024-552', 4, 'Data Hall A - Row 1', 'active', '2026-08-27 11:45:00'),
            (5, 'Caterpillar 1500kVA Diesel Generator', 'SN-GEN-2021-002', 2, 'External Utility Yard', 'maintenance', '2026-08-15 08:00:00');");

        $pdo->exec("INSERT INTO inspection_items (id, category_id, title, description, is_critical) VALUES
            (1, 1, 'Check CRAC Return Temperature', 'Ensure return air temperature stays within 20°C - 24°C bounds.', 1),
            (2, 1, 'Inspect Air Filter Cleanliness', 'Verify no dust accumulation on intake micro-filters.', 0),
            (3, 1, 'Verify Condensate Drain & Pump', 'Check for water leaks or blockages in the condensate line.', 1),
            (4, 2, 'UPS Battery Bank Voltage Test', 'Confirm floating voltage per cell is within spec (13.5V-13.8V).', 1),
            (5, 2, 'PDU Phase Load Balance Check', 'Ensure load variance across L1, L2, L3 does not exceed 15%.', 0),
            (6, 2, 'Emergency Generator Fuel Level', 'Check diesel tank volume is > 85% capacity.', 1),
            (7, 3, 'FM-200 Cylinder Pressure Gauge', 'Ensure agent bottle pressure is inside green zone (360 PSI).', 1),
            (8, 3, 'VESDA Air Sampling Aspirator', 'Verify airflow indicator LED is steady green with no fault codes.', 1),
            (9, 4, 'Rack Grounding & Bonding Wire', 'Check copper grounding braid connection to main ground busbar.', 1),
            (10, 4, 'Cable Strain Relief & Bend Radius', 'Ensure optical fiber cables observe minimum 30mm bend radius.', 0),
            (11, 5, 'Biometric Access Control Keypad', 'Test fingerprint scanner response time and audit log timestamping.', 0),
            (12, 5, 'CCTV Blind Spot Inspection', 'Verify camera field of view covers all aisle entry points.', 0);");

        $pdo->exec("INSERT INTO inspections (id, title, reference_code, user_id, category_id, equipment_id, status, notes, started_at, completed_at) VALUES
            (1, 'Weekly HVAC & CRAC Unit Maintenance Check', 'INSP-2026-08-001', 2, 1, 1, 'completed', 'Unit running smooth. Filter replaced on CRAC Alpha.', '2026-08-25 09:30:00', '2026-08-25 10:00:00'),
            (2, 'Monthly UPS Battery & PDU Thermal Sweep', 'INSP-2026-08-002', 2, 2, 2, 'completed', 'Minor thermal variance detected on PDU L3 phase wire.', '2026-08-26 14:00:00', '2026-08-26 14:30:00'),
            (3, 'Routine Fire Suppression Bottle Inspection', 'INSP-2026-08-003', 2, 3, 3, 'in_progress', 'Inspecting overhead VESDA aspirator sensors.', '2026-08-28 08:00:00', NULL);");

        $pdo->exec("INSERT INTO inspection_results (id, inspection_id, item_id, result_status, notes) VALUES
            (1, 1, 1, 'pass', 'Return temperature stable at 21.8°C.'),
            (2, 1, 2, 'pass', 'Filter replaced with new MERV 13 rating unit.'),
            (3, 1, 3, 'pass', 'Drain pump functioning normally.'),
            (4, 2, 4, 'pass', 'Battery bank voltage reads 13.62V per cell.'),
            (5, 2, 5, 'warning', 'L3 phase shows 12% higher current draw than L1.'),
            (6, 2, 6, 'pass', 'Fuel tank level at 92%.');");

        $pdo->exec("INSERT INTO reports (id, inspection_id, generated_by, summary, score, file_path) VALUES
            (1, 1, 2, 'All CRAC cooling parameters passed compliance threshold with 100% operational health.', 100.00, 'reports/pdf/INSP-2026-08-001.pdf'),
            (2, 2, 2, 'UPS battery bank healthy. Recommended balancing phase loads on PDU cabinet A01.', 91.50, 'reports/pdf/INSP-2026-08-002.pdf');");

        $pdo->exec("INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, details, ip_address) VALUES
            (1, 1, 'User Login', 'User', 1, 'User admin logged in successfully', '127.0.0.1'),
            (2, 2, 'Completed Inspection', 'Inspection', 1, 'Inspection INSP-2026-08-001 submitted with status completed', '127.0.0.1'),
            (3, 2, 'Generated Report', 'Report', 1, 'Report generated for inspection INSP-2026-08-001', '127.0.0.1');");

        echo "SQLite Seed data inserted successfully!\n";
    }
}

echo "Database initialization finished successfully!\n";
