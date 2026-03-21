-- Module-Based Access Control (Feature Gating)
-- Created: 2026-03-21

-- 1. Modules Table (System Level)
CREATE TABLE IF NOT EXISTS `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `is_core` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_module_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Institute Modules Mapping (Institute Level Override)
CREATE TABLE IF NOT EXISTS `institute_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `module_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_module` (`tenant_id`, `module_id`),
  CONSTRAINT `fk_inst_mod_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inst_mod_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Seed Initial Modules
INSERT IGNORE INTO `modules` (`name`, `label`, `is_core`) VALUES
('dashboard', 'Dashboard', 1),
('academic', 'Academic Management', 1),
('attendance', 'Attendance System', 0),
('exams', 'Examinations & Results', 0),
('admissions', 'Admissions & Inquiries', 0),
('finance', 'Finance & Fees', 0),
('staff', 'Staff Management', 1),
('lms', 'Learning Management (Study Materials)', 0),
('communication', 'SMS & Notifications', 0),
('library', 'Library Management', 0),
('reports', 'Advanced Reports', 0),
('system', 'System Settings', 1);

-- 4. Enable all modules for existing institutes (backward compatibility)
INSERT IGNORE INTO `institute_modules` (tenant_id, module_id, is_enabled)
SELECT t.id, m.id, 1 
FROM tenants t 
CROSS JOIN modules m;
