-- Seed initial data for Datacenter Inspection System

-- Default Passwords:
-- Admin: admin@datacenter.local / admin123  (bcrypt hash below)
-- Inspector: inspector@datacenter.local / password123

INSERT INTO users (id, name, email, password, role, status, avatar) VALUES
(1, 'System Administrator', 'admin@datacenter.local', '$2y$10$8K1p/a0dL.d5.uY7lKzJc.g4VnO9qK5p4h5f6g7h8i9j0k1l2m3n', 'admin', 'active', NULL),
(2, 'Datacenter Inspector', 'inspector@datacenter.local', '$2y$10$8K1p/a0dL.d5.uY7lKzJc.g4VnO9qK5p4h5f6g7h8i9j0k1l2m3n', 'inspector', 'active', NULL),
(3, 'Facility Manager', 'manager@datacenter.local', '$2y$10$8K1p/a0dL.d5.uY7lKzJc.g4VnO9qK5p4h5f6g7h8i9j0k1l2m3n', 'manager', 'active', NULL);

-- Categories
INSERT INTO categories (id, name, slug, description, icon) VALUES
(1, 'HVAC & Environmental Cooling', 'hvac-cooling', 'Computer Room Air Conditioning (CRAC/CRAH), airflow, ambient temp & humidity controls.', 'fa-snowflake'),
(2, 'UPS & Power Distribution', 'ups-power', 'Uninterruptible Power Supply banks, PDU units, backup generators, and automatic transfer switches.', 'fa-bolt'),
(3, 'Fire Suppression & Safety', 'fire-suppression', 'FM-200 / Novec 1230 gas systems, smoke detection, emergency power-off (EPO) buttons.', 'fa-fire-extinguisher'),
(4, 'Server Racks & Cabling', 'rack-cabling', 'Structured fiber cabling, patch panels, cabinet locks, cable management, rack grounding.', 'fa-server'),
(5, 'Physical Security & Access', 'security-access', 'Biometric scanners, CCTV camera coverage, door sensors, visitor logging compliance.', 'fa-shield-halved');

-- Equipment Inventory
INSERT INTO equipment (id, name, serial_number, category_id, room_location, status, last_inspected_at) VALUES
(1, 'Schneider CRAC Unit Alpha', 'SN-CRAC-2024-001', 1, 'Data Hall A - Zone 1', 'active', '2026-08-25 10:00:00'),
(2, 'APC Symmetra PX 160kW UPS', 'SN-UPS-2023-089', 2, 'Power Room West', 'active', '2026-08-26 14:30:00'),
(3, 'VESDA Laser Smoke Detector Unit 1', 'SN-FIRE-2022-104', 3, 'Data Hall A - Overhead', 'active', '2026-08-20 09:15:00'),
(4, 'Rack Cluster A01-A10 PDU Pair', 'SN-PDU-2024-552', 4, 'Data Hall A - Row 1', 'active', '2026-08-27 11:45:00'),
(5, 'Caterpillar 1500kVA Diesel Generator', 'SN-GEN-2021-002', 2, 'External Utility Yard', 'maintenance', '2026-08-15 08:00:00');

-- Checklist Template Items
INSERT INTO inspection_items (id, category_id, title, description, is_critical) VALUES
-- HVAC items
(1, 1, 'Check CRAC Return Temperature', 'Ensure return air temperature stays within 20°C - 24°C bounds.', 1),
(2, 1, 'Inspect Air Filter Cleanliness', 'Verify no dust accumulation on intake micro-filters.', 0),
(3, 1, 'Verify Condensate Drain & Pump', 'Check for water leaks or blockages in the condensate line.', 1),

-- UPS items
(4, 2, 'UPS Battery Bank Voltage Test', 'Confirm floating voltage per cell is within spec (13.5V-13.8V).', 1),
(5, 2, 'PDU Phase Load Balance Check', 'Ensure load variance across L1, L2, L3 does not exceed 15%.', 0),
(6, 2, 'Emergency Generator Fuel Level', 'Check diesel tank volume is > 85% capacity.', 1),

-- Fire Suppression items
(7, 3, 'FM-200 Cylinder Pressure Gauge', 'Ensure agent bottle pressure is inside green zone (360 PSI).', 1),
(8, 3, 'VESDA Air Sampling Aspirator', 'Verify airflow indicator LED is steady green with no fault codes.', 1),

-- Rack & Cabling items
(9, 4, 'Rack Grounding & Bonding Wire', 'Check copper grounding braid connection to main ground busbar.', 1),
(10, 4, 'Cable Strain Relief & Bend Radius', 'Ensure optical fiber cables observe minimum 30mm bend radius.', 0),

-- Security items
(11, 5, 'Biometric Access Control Keypad', 'Test fingerprint scanner response time and audit log timestamping.', 0),
(12, 5, 'CCTV Blind Spot Inspection', 'Verify camera field of view covers all aisle entry points.', 0);

-- Sample Completed Inspection
INSERT INTO inspections (id, title, reference_code, user_id, category_id, equipment_id, status, notes, started_at, completed_at) VALUES
(1, 'Weekly HVAC & CRAC Unit Maintenance Check', 'INSP-2026-08-001', 2, 1, 1, 'completed', 'Unit running smooth. Filter replaced on CRAC Alpha.', '2026-08-25 09:30:00', '2026-08-25 10:00:00'),
(2, 'Monthly UPS Battery & PDU Thermal Sweep', 'INSP-2026-08-002', 2, 2, 2, 'completed', 'Minor thermal variance detected on PDU L3 phase wire.', '2026-08-26 14:00:00', '2026-08-26 14:30:00'),
(3, 'Routine Fire Suppression Bottle Inspection', 'INSP-2026-08-003', 2, 3, 3, 'in_progress', 'Inspecting overhead VESDA aspirator sensors.', '2026-08-28 08:00:00', NULL);

-- Sample Results
INSERT INTO inspection_results (id, inspection_id, item_id, result_status, notes) VALUES
(1, 1, 1, 'pass', 'Return temperature stable at 21.8°C.'),
(2, 1, 2, 'pass', 'Filter replaced with new MERV 13 rating unit.'),
(3, 1, 3, 'pass', 'Drain pump functioning normally.'),
(4, 2, 4, 'pass', 'Battery bank voltage reads 13.62V per cell.'),
(5, 2, 5, 'warning', 'L3 phase shows 12% higher current draw than L1.'),
(6, 2, 6, 'pass', 'Fuel tank level at 92%.');

-- Sample Reports
INSERT INTO reports (id, inspection_id, generated_by, summary, score, file_path) VALUES
(1, 1, 2, 'All CRAC cooling parameters passed compliance threshold with 100% operational health.', 100.00, 'reports/pdf/INSP-2026-08-001.pdf'),
(2, 2, 2, 'UPS battery bank healthy. Recommended balancing phase loads on PDU cabinet A01.', 91.50, 'reports/pdf/INSP-2026-08-002.pdf');

-- Audit Logs
INSERT INTO audit_logs (id, user_id, action, entity_type, entity_id, details, ip_address) VALUES
(1, 1, 'User Login', 'User', 1, 'User admin logged in successfully', '127.0.0.1'),
(2, 2, 'Completed Inspection', 'Inspection', 1, 'Inspection INSP-2026-08-001 submitted with status completed', '127.0.0.1'),
(3, 2, 'Generated Report', 'Report', 1, 'Report generated for inspection INSP-2026-08-001', '127.0.0.1');
