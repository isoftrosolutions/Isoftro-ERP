-- Dynamic Pricing and Features Tables
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `student_limit` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `badge_text` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `css_class` varchar(100) DEFAULT NULL,
  `icon_emoji` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_plan_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plan_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `feature_text` varchar(255) NOT NULL,
  `is_included` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_plan_features_plan` (`plan_id`),
  CONSTRAINT `fk_plan_features_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default plans
INSERT INTO `subscription_plans` (`slug`, `name`, `price_monthly`, `student_limit`, `description`, `badge_text`, `is_featured`, `css_class`, `icon_emoji`, `sort_order`) VALUES
('starter', 'Starter Plan', 5000.00, 300, 'Good for institutes that want digital fee & student management.', NULL, 0, 'starter', '🌱', 1),
('growth', 'Growth Plan', 8000.00, 800, 'Complete institute management system', 'Most Popular', 1, 'growth', '🚀', 2),
('professional', 'Professional Plan', 12000.00, 0, 'Enterprise level institute ERP', NULL, 0, 'pro', '⭐', 3);

-- Insert features for Starter
INSERT INTO `plan_features` (`plan_id`, `feature_text`, `sort_order`) VALUES
(1, 'Up to 300 students', 1),
(1, 'Student admission management', 2),
(1, 'Batch management', 3),
(1, 'Fees collection system', 4),
(1, 'Payment receipts', 5),
(1, 'Basic attendance tracking', 6),
(1, 'Notice & announcements', 7),
(1, 'Basic reporting dashboard', 8),
(1, 'Email support', 9);

-- Insert features for Growth
INSERT INTO `plan_features` (`plan_id`, `feature_text`, `sort_order`) VALUES
(2, 'Up to 800 students', 1),
(2, 'Study materials module', 2),
(2, 'Homework / assignment system', 3),
(2, 'Advanced attendance reports', 4),
(2, 'Payment history analytics', 5),
(2, 'Role based access (admin / front desk / teacher)', 6),
(2, 'Priority support', 7);

-- Insert features for Professional
INSERT INTO `plan_features` (`plan_id`, `feature_text`, `sort_order`) VALUES
(3, 'Unlimited students', 1),
(3, 'Multi-branch support', 2),
(3, 'SMS integration', 3),
(3, 'Advanced analytics dashboard', 4),
(3, 'API integrations', 5),
(3, 'Custom branding', 6),
(3, 'Dedicated relationship manager', 7);
