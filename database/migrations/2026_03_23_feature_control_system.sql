-- Simple Feature-Level Control System
-- Created: 2026-03-23

-- 1. System Features Table
CREATE TABLE IF NOT EXISTS `system_features` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `feature_key` varchar(50) NOT NULL,
    `feature_name` varchar(100) NOT NULL,
    `is_core` tinyint(1) NOT NULL DEFAULT 0,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_feature_key` (`feature_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Institute Feature Access Table
CREATE TABLE IF NOT EXISTS `institute_feature_access` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint(20) unsigned NOT NULL,
    `feature_id` int(11) NOT NULL,
    `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_tenant_feature` (`tenant_id`, `feature_id`),
    CONSTRAINT `fk_feat_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_feat_system` FOREIGN KEY (`feature_id`) REFERENCES `system_features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Seed Sample Data
INSERT IGNORE INTO `system_features` (`feature_key`, `feature_name`, `is_core`) VALUES
('students', 'Student Management', 1),
('teachers', 'Teacher Management', 1),
('attendance', 'Attendance System', 0),
('exams', 'Examinations', 0),
('homework', 'Homework Management', 0),
('accounting', 'Accounting & Finance', 0),
('inquiry', 'Admissions & Inquiry', 0),
('lms', 'Study Materials (LMS)', 0),
('library', 'Library Management', 0),
('communication', 'SMS & Notifications', 0),
('reports', 'Advanced Reports', 0);

-- 4. Auto-enable core features for existing institutes
INSERT IGNORE INTO `institute_feature_access` (tenant_id, feature_id, is_enabled)
SELECT t.id, f.id, 1 
FROM tenants t 
CROSS JOIN system_features f 
WHERE f.is_core = 1;

-- 5. Auto-enable all for existing (optional, for backward compatibility during dev)
INSERT IGNORE INTO `institute_feature_access` (tenant_id, feature_id, is_enabled)
SELECT t.id, f.id, 1 
FROM tenants t 
CROSS JOIN system_features f
ON DUPLICATE KEY UPDATE is_enabled = 1;
