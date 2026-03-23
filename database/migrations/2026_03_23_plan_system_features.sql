-- Plan System Features Link Table
CREATE TABLE IF NOT EXISTS `plan_system_features` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `plan_id` int(11) NOT NULL,
    `feature_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_plan_feature` (`plan_id`, `feature_id`),
    CONSTRAINT `fk_psf_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_psf_feature` FOREIGN KEY (`feature_id`) REFERENCES `system_features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed some default linkings based on common sense
-- Starter: students, teachers, inquiry
INSERT IGNORE INTO `plan_system_features` (plan_id, feature_id)
SELECT p.id, f.id FROM subscription_plans p, system_features f 
WHERE p.slug = 'starter' AND f.feature_key IN ('students', 'teachers', 'inquiry', 'attendance');

-- Growth: adds exams, homework, lms
INSERT IGNORE INTO `plan_system_features` (plan_id, feature_id)
SELECT p.id, f.id FROM subscription_plans p, system_features f 
WHERE p.slug = 'growth' AND f.feature_key IN ('students', 'teachers', 'inquiry', 'attendance', 'exams', 'homework', 'lms');

-- Professional: adds all
INSERT IGNORE INTO `plan_system_features` (plan_id, feature_id)
SELECT p.id, f.id FROM subscription_plans p, system_features f 
WHERE p.slug = 'professional';
