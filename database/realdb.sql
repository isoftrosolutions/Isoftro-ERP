/*
SQLyog Community v13.3.0 (64 bit)
MySQL - 12.0.2-MariaDB : Database - hamrolabs_db
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`hamrolabs_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `hamrolabs_db`;

/*Table structure for table `academic_calendar` */

DROP TABLE IF EXISTS `academic_calendar`;

CREATE TABLE `academic_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `batch` varchar(100) DEFAULT 'All',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `academic_calendar` */

/*Table structure for table `announcements` */

DROP TABLE IF EXISTS `announcements`;

CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target_audience` enum('all','tenants','admins','trial') NOT NULL DEFAULT 'all',
  `priority` enum('urgent','normal','low') NOT NULL DEFAULT 'normal',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ann_active` (`is_active`),
  KEY `idx_ann_audience` (`target_audience`),
  KEY `idx_ann_dates` (`starts_at`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `announcements` */

/*Table structure for table `api_keys` */

DROP TABLE IF EXISTS `api_keys`;

CREATE TABLE `api_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `api_key` varchar(100) NOT NULL,
  `api_secret` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idx_apikey_tenant` (`tenant_id`),
  KEY `idx_apikey_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `api_keys` */

/*Table structure for table `api_logs` */

DROP TABLE IF EXISTS `api_logs`;

CREATE TABLE `api_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `request_body` text DEFAULT NULL,
  `response_code` int(5) DEFAULT NULL,
  `response_time` int(10) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_api_tenant` (`tenant_id`),
  KEY `idx_api_user` (`user_id`),
  KEY `idx_api_endpoint` (`endpoint`),
  KEY `idx_api_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `api_logs` */

/*Table structure for table `assignment_submissions` */

DROP TABLE IF EXISTS `assignment_submissions`;

CREATE TABLE `assignment_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `assignment_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT 0,
  `marks_awarded` decimal(6,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_submission_assignment_student` (`assignment_id`,`student_id`),
  KEY `fk_as_tenant` (`tenant_id`),
  KEY `fk_as_student` (`student_id`),
  KEY `idx_submissions_graded` (`graded_at`,`tenant_id`),
  CONSTRAINT `fk_as_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `assignment_submissions` */

/*Table structure for table `assignments` */

DROP TABLE IF EXISTS `assignments`;

CREATE TABLE `assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime NOT NULL,
  `max_marks` decimal(6,2) NOT NULL DEFAULT 100.00,
  `attachment_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_assignments_tenant` (`tenant_id`),
  KEY `fk_assignments_batch` (`batch_id`),
  KEY `fk_assignments_teacher` (`teacher_id`),
  CONSTRAINT `fk_assignments_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `assignments` */

/*Table structure for table `attendance` */

DROP TABLE IF EXISTS `attendance`;

CREATE TABLE `attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','leave') NOT NULL DEFAULT 'present',
  `marked_by` bigint(20) unsigned DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_batch_date` (`student_id`,`batch_id`,`attendance_date`),
  KEY `idx_tenant_date` (`tenant_id`,`attendance_date`),
  KEY `idx_batch_date` (`batch_id`,`attendance_date`),
  KEY `idx_student_date` (`student_id`,`attendance_date`),
  KEY `idx_tenant_status` (`tenant_id`,`status`),
  KEY `attendance_course_id_foreign` (`course_id`),
  KEY `attendance_marked_by_foreign` (`marked_by`),
  CONSTRAINT `attendance_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `attendance` */

insert  into `attendance`(`id`,`tenant_id`,`student_id`,`batch_id`,`course_id`,`attendance_date`,`status`,`marked_by`,`locked`,`created_at`,`updated_at`) values 
(1,5,62,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(2,5,53,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(3,5,54,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(4,5,55,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(5,5,67,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(6,5,68,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(7,5,69,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(8,5,70,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(9,5,71,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(10,5,73,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(11,5,76,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(12,5,77,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(13,5,78,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(14,5,66,4,4,'2026-03-07','present',38,0,'2026-03-07 17:50:53','2026-03-07 17:50:53'),
(15,5,62,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(16,5,53,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(17,5,54,4,4,'2026-03-08','absent',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(18,5,55,4,4,'2026-03-08','absent',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(19,5,67,4,4,'2026-03-08','absent',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(20,5,68,4,4,'2026-03-08','absent',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(21,5,69,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(22,5,70,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(23,5,71,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(24,5,73,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(25,5,76,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(26,5,77,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(27,5,78,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44'),
(28,5,66,4,4,'2026-03-08','present',38,0,'2026-03-08 07:49:17','2026-03-08 07:49:44');

/*Table structure for table `attendance_audit_logs` */

DROP TABLE IF EXISTS `attendance_audit_logs`;

CREATE TABLE `attendance_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `attendance_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_audit_date` (`tenant_id`,`created_at`),
  KEY `idx_attendance_audit` (`attendance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `attendance_audit_logs` */

/*Table structure for table `attendance_settings` */

DROP TABLE IF EXISTS `attendance_settings`;

CREATE TABLE `attendance_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `lock_period_hours` int(11) NOT NULL DEFAULT 24,
  `exclude_leave_from_total` tinyint(1) NOT NULL DEFAULT 1,
  `allow_frontdesk_edit` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_settings_tenant_id_unique` (`tenant_id`),
  CONSTRAINT `attendance_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `attendance_settings` */

/*Table structure for table `audit_logs` */

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_tenant_user` (`tenant_id`,`user_id`),
  KEY `idx_audit_table_record` (`table_name`,`record_id`),
  KEY `idx_audit_logs_date` (`tenant_id`,`created_at` DESC),
  KEY `idx_audit_logs_action` (`action`,`tenant_id`,`created_at` DESC),
  KEY `idx_audit_logs_created` (`created_at`,`tenant_id`,`user_id`,`action`),
  CONSTRAINT `fk_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `audit_logs` */

insert  into `audit_logs`(`id`,`tenant_id`,`user_id`,`ip_address`,`user_agent`,`action`,`table_name`,`record_id`,`changes`,`description`,`created_at`) values 
(1,NULL,1,NULL,NULL,'Tenant Created',NULL,NULL,NULL,'New tenant \'Verification Test Inst\' (test-1771836289) created with admin \'test-1771836289@test.com\'','2026-02-23 14:29:51'),
(2,NULL,1,NULL,NULL,'Tenant Updated',NULL,NULL,NULL,'Tenant \'Updated Test Inst\' (test-1771836289) updated.','2026-02-23 14:29:51'),
(3,NULL,1,NULL,NULL,'Tenant Deleted',NULL,NULL,NULL,'Tenant \'Updated Test Inst\' (ID: 4) was soft-deleted and users suspended.','2026-02-23 14:29:52'),
(4,NULL,1,NULL,NULL,'Tenant Deleted',NULL,NULL,NULL,'Tenant \'Global Academic Center\' (ID: 1) was soft-deleted and users suspended.','2026-02-23 14:47:44'),
(5,NULL,1,NULL,NULL,'Tenant Deleted',NULL,NULL,NULL,'Tenant \'Sagarmatha Public School\' (ID: 2) was soft-deleted and users suspended.','2026-02-23 14:47:49'),
(6,NULL,1,NULL,NULL,'Tenant Deleted',NULL,NULL,NULL,'Tenant \'Everest Nursing College\' (ID: 3) was soft-deleted and users suspended.','2026-02-23 14:47:54'),
(7,NULL,1,NULL,NULL,'Tenant Created',NULL,NULL,NULL,'New tenant \'Nepal Cyber Firm\' (Brightfuture) created with admin \'nepalcyberfirm@gmail.com\'','2026-02-23 14:53:01'),
(8,NULL,1,'system',NULL,'CREATE','students',38,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":\\\"25\\\",\\\"roll_no\\\":\\\"000005\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"citizenship_no\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"photo_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"admission_date\\\":\\\"2026-02-28\\\"}\"}','Audited CREATE on students','2026-02-28 11:42:20'),
(10,NULL,1,'system',NULL,'CREATE','students',39,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":\\\"26\\\",\\\"roll_no\\\":\\\"000006\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"citizenship_no\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"photo_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"admission_date\\\":\\\"2026-02-28\\\"}\"}','Audited CREATE on students','2026-02-28 11:49:20'),
(11,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',2,'{\"old\":\"{\\\"id\\\":2,\\\"tenant_id\\\":5,\\\"student_id\\\":33,\\\"batch_id\\\":4,\\\"fee_item_id\\\":1,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:24:19\\\",\\\"updated_at\\\":\\\"2026-02-28 11:24:19\\\"}\",\"new\":\"{\\\"id\\\":2,\\\"tenant_id\\\":5,\\\"student_id\\\":33,\\\"batch_id\\\":4,\\\"fee_item_id\\\":1,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"7000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000001\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:24:19\\\",\\\"updated_at\\\":\\\"2026-02-28 11:50:49\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 11:50:49'),
(12,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',1,'{\"old\":null,\"new\":\"{\\\"id\\\":1,\\\"tenant_id\\\":5,\\\"student_id\\\":33,\\\"fee_record_id\\\":2,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"5000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000001\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 11:50:49'),
(13,NULL,1,'system',NULL,'CREATE','students',40,'{\"old\":null,\"new\":\"{\\\"id\\\":40,\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":28,\\\"roll_no\\\":\\\"STD-0040\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:00:58\\\",\\\"updated_at\\\":\\\"2026-02-28 12:00:58\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:00:58'),
(14,NULL,1,'system',NULL,'CREATE','students',41,'{\"old\":null,\"new\":\"{\\\"id\\\":41,\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":29,\\\"roll_no\\\":\\\"STD-0041\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:01:22\\\",\\\"updated_at\\\":\\\"2026-02-28 12:01:22\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:01:22'),
(15,NULL,1,'system',NULL,'CREATE','students',42,'{\"old\":null,\"new\":\"{\\\"id\\\":42,\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":30,\\\"roll_no\\\":\\\"STD-0042\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:02:20\\\",\\\"updated_at\\\":\\\"2026-02-28 12:02:20\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:02:20'),
(16,NULL,1,'system',NULL,'CREATE','students',43,'{\"old\":null,\"new\":\"{\\\"id\\\":43,\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":31,\\\"roll_no\\\":\\\"STD-0043\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:03:30\\\",\\\"updated_at\\\":\\\"2026-02-28 12:03:30\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:03:30'),
(17,NULL,1,'system',NULL,'CREATE','students',44,'{\"old\":null,\"new\":\"{\\\"id\\\":44,\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":32,\\\"roll_no\\\":\\\"STD-0044\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:04:25\\\",\\\"updated_at\\\":\\\"2026-02-28 12:04:25\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:04:25'),
(18,NULL,1,'system',NULL,'CREATE','students',45,'{\"old\":null,\"new\":\"{\\\"id\\\":45,\\\"tenant_id\\\":5,\\\"user_id\\\":79,\\\"batch_id\\\":33,\\\"roll_no\\\":\\\"STD-0045\\\",\\\"full_name\\\":\\\"Test Student 1\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:05:46\\\",\\\"updated_at\\\":\\\"2026-02-28 12:05:46\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:05:46'),
(19,NULL,1,'system',NULL,'CREATE','students',46,'{\"old\":null,\"new\":\"{\\\"id\\\":46,\\\"tenant_id\\\":5,\\\"user_id\\\":80,\\\"batch_id\\\":33,\\\"roll_no\\\":\\\"STD-0046\\\",\\\"full_name\\\":\\\"Test Student 2\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 12:05:50'),
(20,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',6,'{\"old\":\"{\\\"id\\\":6,\\\"tenant_id\\\":5,\\\"student_id\\\":43,\\\"batch_id\\\":31,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:03:30\\\",\\\"updated_at\\\":\\\"2026-02-28 12:03:30\\\"}\",\"new\":\"{\\\"id\\\":6,\\\"tenant_id\\\":5,\\\"student_id\\\":43,\\\"batch_id\\\":31,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"10000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000002\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:03:30\\\",\\\"updated_at\\\":\\\"2026-02-28 12:06:56\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 12:06:57'),
(21,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',2,'{\"old\":null,\"new\":\"{\\\"id\\\":2,\\\"tenant_id\\\":5,\\\"student_id\\\":43,\\\"fee_record_id\\\":6,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"10000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000002\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 12:06:57'),
(22,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',3,'{\"old\":\"{\\\"id\\\":3,\\\"tenant_id\\\":5,\\\"student_id\\\":37,\\\"batch_id\\\":24,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:41:48\\\",\\\"updated_at\\\":\\\"2026-02-28 11:41:48\\\"}\",\"new\":\"{\\\"id\\\":3,\\\"tenant_id\\\":5,\\\"student_id\\\":37,\\\"batch_id\\\":24,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"10000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000003\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:41:48\\\",\\\"updated_at\\\":\\\"2026-02-28 12:07:34\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 12:07:34'),
(23,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',3,'{\"old\":null,\"new\":\"{\\\"id\\\":3,\\\"tenant_id\\\":5,\\\"student_id\\\":37,\\\"fee_record_id\\\":3,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"10000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000003\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 12:07:34'),
(24,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',13,'{\"old\":\"{\\\"id\\\":13,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"batch_id\\\":33,\\\"fee_item_id\\\":5,\\\"installment_no\\\":4,\\\"amount_due\\\":\\\"2000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-05-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 12:05:50\\\"}\",\"new\":\"{\\\"id\\\":13,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"batch_id\\\":33,\\\"fee_item_id\\\":5,\\\"installment_no\\\":4,\\\"amount_due\\\":\\\"2000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-05-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000004\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 12:26:43\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 12:26:43'),
(25,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',4,'{\"old\":null,\"new\":\"{\\\"id\\\":4,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"fee_record_id\\\":13,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"2000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000004\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 12:26:43'),
(26,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',7,'{\"old\":\"{\\\"id\\\":7,\\\"tenant_id\\\":5,\\\"student_id\\\":44,\\\"batch_id\\\":32,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:04:25\\\",\\\"updated_at\\\":\\\"2026-02-28 12:04:25\\\"}\",\"new\":\"{\\\"id\\\":7,\\\"tenant_id\\\":5,\\\"student_id\\\":44,\\\"batch_id\\\":32,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"10000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000005\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:04:25\\\",\\\"updated_at\\\":\\\"2026-02-28 12:34:47\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 12:34:47'),
(27,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',5,'{\"old\":null,\"new\":\"{\\\"id\\\":5,\\\"tenant_id\\\":5,\\\"student_id\\\":44,\\\"fee_record_id\\\":7,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"10000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000005\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 12:34:47'),
(28,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',9,'{\"old\":\"{\\\"id\\\":9,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"batch_id\\\":33,\\\"fee_item_id\\\":4,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"2000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 12:05:50\\\"}\",\"new\":\"{\\\"id\\\":9,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"batch_id\\\":33,\\\"fee_item_id\\\":4,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"2000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000006\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 12:49:32\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 12:49:32'),
(29,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',6,'{\"old\":null,\"new\":\"{\\\"id\\\":6,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"fee_record_id\\\":9,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"2000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000006\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 12:49:32'),
(30,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',5,'{\"old\":\"{\\\"id\\\":5,\\\"tenant_id\\\":5,\\\"student_id\\\":39,\\\"batch_id\\\":26,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:49:20\\\",\\\"updated_at\\\":\\\"2026-02-28 11:49:20\\\"}\",\"new\":\"{\\\"id\\\":5,\\\"tenant_id\\\":5,\\\"student_id\\\":39,\\\"batch_id\\\":26,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"10000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000007\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:49:20\\\",\\\"updated_at\\\":\\\"2026-02-28 12:53:17\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 12:53:17'),
(31,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',7,'{\"old\":null,\"new\":\"{\\\"id\\\":7,\\\"tenant_id\\\":5,\\\"student_id\\\":39,\\\"fee_record_id\\\":5,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"10000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000007\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 12:53:17'),
(32,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',4,'{\"old\":\"{\\\"id\\\":4,\\\"tenant_id\\\":5,\\\"student_id\\\":38,\\\"batch_id\\\":25,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:42:20\\\",\\\"updated_at\\\":\\\"2026-02-28 11:42:20\\\"}\",\"new\":\"{\\\"id\\\":4,\\\"tenant_id\\\":5,\\\"student_id\\\":38,\\\"batch_id\\\":25,\\\"fee_item_id\\\":3,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"10000.00\\\",\\\"amount_paid\\\":\\\"10000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000008\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 11:42:20\\\",\\\"updated_at\\\":\\\"2026-02-28 13:31:03\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 13:31:03'),
(33,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',8,'{\"old\":null,\"new\":\"{\\\"id\\\":8,\\\"tenant_id\\\":5,\\\"student_id\\\":38,\\\"fee_record_id\\\":4,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"10000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000008\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 13:31:03'),
(34,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',10,'{\"old\":\"{\\\"id\\\":10,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"batch_id\\\":33,\\\"fee_item_id\\\":5,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"2000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 12:05:50\\\"}\",\"new\":\"{\\\"id\\\":10,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"batch_id\\\":33,\\\"fee_item_id\\\":5,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"2000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000009\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 12:05:50\\\",\\\"updated_at\\\":\\\"2026-02-28 13:51:48\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 13:51:48'),
(35,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',9,'{\"old\":null,\"new\":\"{\\\"id\\\":9,\\\"tenant_id\\\":5,\\\"student_id\\\":46,\\\"fee_record_id\\\":10,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"2000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000009\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 13:51:48'),
(36,5,38,'::1',NULL,'CREATE','students',47,'{\"old\":null,\"new\":\"{\\\"id\\\":47,\\\"tenant_id\\\":5,\\\"user_id\\\":77,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0034\\\",\\\"full_name\\\":\\\"Priyanka Shah\\\",\\\"dob_ad\\\":\\\"2008-12-29\\\",\\\"dob_bs\\\":\\\"\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"A+\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":\\\"xyz\\\",\\\"mother_name\\\":\\\"abc\\\",\\\"husband_name\\\":\\\"\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Madhesh Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Parsa\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":\\\"http:\\\\\\/\\\\\\/localhost\\\\\\/erp\\\\\\/public\\\\\\/uploads\\\\\\/students\\\\\\/std_1772268305_69a2ab1136fc2.png\\\",\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 14:30:05\\\",\\\"updated_at\\\":\\\"2026-02-28 14:30:05\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 14:30:05'),
(37,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',14,'{\"old\":\"{\\\"id\\\":14,\\\"tenant_id\\\":5,\\\"student_id\\\":47,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:30:05\\\",\\\"updated_at\\\":\\\"2026-02-28 14:30:05\\\"}\",\"new\":\"{\\\"id\\\":14,\\\"tenant_id\\\":5,\\\"student_id\\\":47,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"7000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000010\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:30:05\\\",\\\"updated_at\\\":\\\"2026-02-28 14:36:00\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 14:36:00'),
(38,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',10,'{\"old\":null,\"new\":\"{\\\"id\\\":10,\\\"tenant_id\\\":5,\\\"student_id\\\":47,\\\"fee_record_id\\\":14,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"7000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000010\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 14:36:00'),
(39,5,38,'::1',NULL,'CREATE','students',48,'{\"old\":null,\"new\":\"{\\\"id\\\":48,\\\"tenant_id\\\":5,\\\"user_id\\\":81,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0048\\\",\\\"full_name\\\":\\\"Devbarat Prasad Patel\\\",\\\"dob_ad\\\":\\\"2006-12-06\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":\\\"xyz\\\",\\\"mother_name\\\":\\\"abc\\\",\\\"husband_name\\\":\\\"\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Madhesh Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Parsa\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bahudramai\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":\\\"http:\\\\\\/\\\\\\/localhost\\\\\\/erp\\\\\\/public\\\\\\/uploads\\\\\\/students\\\\\\/std_1772268847_69a2ad2f7ce15.png\\\",\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 14:39:08\\\",\\\"updated_at\\\":\\\"2026-02-28 14:39:08\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 14:39:08'),
(40,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',15,'{\"old\":\"{\\\"id\\\":15,\\\"tenant_id\\\":5,\\\"student_id\\\":48,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:39:08\\\",\\\"updated_at\\\":\\\"2026-02-28 14:39:08\\\"}\",\"new\":\"{\\\"id\\\":15,\\\"tenant_id\\\":5,\\\"student_id\\\":48,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"7000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-02-28\\\",\\\"receipt_no\\\":\\\"RCP-000011\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:39:08\\\",\\\"updated_at\\\":\\\"2026-02-28 14:39:41\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-02-28 14:39:41'),
(41,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',11,'{\"old\":null,\"new\":\"{\\\"id\\\":11,\\\"tenant_id\\\":5,\\\"student_id\\\":48,\\\"fee_record_id\\\":15,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"7000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000011\\\",\\\"payment_date\\\":\\\"2026-02-28\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-02-28 14:39:41'),
(42,5,38,'::1',NULL,'CREATE','students',49,'{\"old\":null,\"new\":\"{\\\"id\\\":49,\\\"tenant_id\\\":5,\\\"user_id\\\":82,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0049\\\",\\\"full_name\\\":\\\"Master Mind Facts\\\",\\\"dob_ad\\\":\\\"2026-02-28\\\",\\\"dob_bs\\\":\\\"\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":\\\"\\\",\\\"mother_name\\\":\\\"\\\",\\\"husband_name\\\":\\\"\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-02-28\\\",\\\"photo_url\\\":\\\"http:\\\\\\/\\\\\\/localhost\\\\\\/erp\\\\\\/public\\\\\\/uploads\\\\\\/students\\\\\\/std_1772268990_69a2adbe68d37.png\\\",\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-02-28 14:41:31'),
(43,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-02-28 14:41:31\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000012\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:29:50\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 06:29:50'),
(44,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',12,'{\"old\":null,\"new\":\"{\\\"id\\\":12,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000012\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 06:29:50'),
(45,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000012\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:29:50\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000013\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:33:58\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 06:33:58'),
(46,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',13,'{\"old\":null,\"new\":\"{\\\"id\\\":13,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000013\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 06:33:58'),
(47,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000013\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:33:58\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000014\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:39:11\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 06:39:11'),
(48,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',14,'{\"old\":null,\"new\":\"{\\\"id\\\":14,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000014\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 06:39:11'),
(49,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000014\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:39:11\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000015\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:42:23\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 06:42:23'),
(50,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',15,'{\"old\":null,\"new\":\"{\\\"id\\\":15,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000015\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 06:42:23'),
(51,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000015\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:42:23\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000016\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"esewa\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:46:13\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 06:46:13'),
(52,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',16,'{\"old\":null,\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"esewa\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000016\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 06:46:13'),
(53,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000016\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"esewa\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:46:13\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000017\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:48:05\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 06:48:05'),
(54,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',17,'{\"old\":null,\"new\":\"{\\\"id\\\":17,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"500.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000017\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 06:48:05'),
(55,5,38,'::1',NULL,'CREATE','students',50,'{\"old\":null,\"new\":\"{\\\"id\\\":50,\\\"tenant_id\\\":5,\\\"user_id\\\":83,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0050\\\",\\\"full_name\\\":\\\"Priyanka Shah\\\",\\\"dob_ad\\\":\\\"2007-02-06\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"female\\\",\\\"blood_group\\\":\\\"B+\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":\\\"10101\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"xyz\\\",\\\"mother_name\\\":\\\"abc\\\",\\\"husband_name\\\":\\\"10\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Madhesh Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Parsa\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bahudramai\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-01\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-03-01 07:07:28\\\",\\\"updated_at\\\":\\\"2026-03-01 07:07:28\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-01 07:07:28'),
(56,5,38,'::1',NULL,'CREATE','students',51,'{\"old\":null,\"new\":\"{\\\"id\\\":51,\\\"tenant_id\\\":5,\\\"user_id\\\":84,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0051\\\",\\\"full_name\\\":\\\"Master Mind Facts\\\",\\\"dob_ad\\\":\\\"2002-07-10\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"AB+\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":\\\"10101\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"xyz\\\",\\\"mother_name\\\":\\\"abc\\\",\\\"husband_name\\\":\\\"10\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Madhesh Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Parsa\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bahudramai\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"07\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-01\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-03-01 07:10:52\\\",\\\"updated_at\\\":\\\"2026-03-01 07:10:52\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-01 07:10:52'),
(57,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',18,'{\"old\":\"{\\\"id\\\":18,\\\"tenant_id\\\":5,\\\"student_id\\\":51,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:10:52\\\",\\\"updated_at\\\":\\\"2026-03-01 07:10:52\\\"}\",\"new\":\"{\\\"id\\\":18,\\\"tenant_id\\\":5,\\\"student_id\\\":51,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000018\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:10:52\\\",\\\"updated_at\\\":\\\"2026-03-01 07:13:20\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:13:20'),
(58,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',18,'{\"old\":null,\"new\":\"{\\\"id\\\":18,\\\"tenant_id\\\":5,\\\"student_id\\\":51,\\\"fee_record_id\\\":18,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"6000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000018\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:13:20'),
(59,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000017\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 06:48:05\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5750.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000019\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 07:17:03\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:17:03'),
(60,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',19,'{\"old\":null,\"new\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"250.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000019\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:17:03'),
(61,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5750.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000019\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 07:17:03\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000020\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 07:19:36\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:19:36'),
(62,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',20,'{\"old\":null,\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"250.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000020\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:19:36'),
(63,5,38,'::1',NULL,'CREATE','students',52,'{\"old\":null,\"new\":\"{\\\"id\\\":52,\\\"tenant_id\\\":5,\\\"user_id\\\":82,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0052\\\",\\\"full_name\\\":\\\"Addam smithe \\\",\\\"dob_ad\\\":\\\"2006-12-20\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"B-\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nagendra P\\\",\\\"mother_name\\\":\\\"\\\",\\\"husband_name\\\":\\\"\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Koshi Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bhojpur\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bahudramai\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"07\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-01\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-01 07:25:21'),
(64,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',19,'{\"old\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:25:21\\\"}\",\"new\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000021\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:25:56\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:25:56'),
(65,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',21,'{\"old\":null,\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"fee_record_id\\\":19,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"2000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000021\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:25:56'),
(66,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',19,'{\"old\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000021\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:25:56\\\"}\",\"new\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000022\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:36:08\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:36:08'),
(67,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',22,'{\"old\":null,\"new\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"fee_record_id\\\":19,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000022\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:36:08'),
(68,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',19,'{\"old\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000022\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:36:08\\\"}\",\"new\":\"{\\\"id\\\":19,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3250.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000023\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 07:25:21\\\",\\\"updated_at\\\":\\\"2026-03-01 07:46:41\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:46:41'),
(69,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',23,'{\"old\":null,\"new\":\"{\\\"id\\\":23,\\\"tenant_id\\\":5,\\\"student_id\\\":52,\\\"fee_record_id\\\":19,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"250.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000023\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:46:41'),
(70,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',16,'{\"old\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000020\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 07:19:36\\\"}\",\"new\":\"{\\\"id\\\":16,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-02-28\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000024\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-02-28 14:41:31\\\",\\\"updated_at\\\":\\\"2026-03-01 07:49:31\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 07:49:31'),
(71,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',24,'{\"old\":null,\"new\":\"{\\\"id\\\":24,\\\"tenant_id\\\":5,\\\"student_id\\\":49,\\\"fee_record_id\\\":16,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"500.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000024\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 07:49:31'),
(72,5,38,'::1',NULL,'CREATE','students',53,'{\"old\":null,\"new\":\"{\\\"id\\\":53,\\\"tenant_id\\\":5,\\\"user_id\\\":86,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0001\\\",\\\"full_name\\\":\\\"Devbarat Prasad Patel\\\",\\\"dob_ad\\\":\\\"2006-12-20\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"B+\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":\\\"34-01-80-1297\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nagendra Prasad Patel\\\",\\\"mother_name\\\":\\\"Sanju Devi\\\",\\\"husband_name\\\":\\\"\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Madhesh Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Parsa\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bahudramai\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"07\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-01\\\",\\\"photo_url\\\":\\\"http:\\\\\\/\\\\\\/localhost\\\\\\/erp\\\\\\/public\\\\\\/uploads\\\\\\/students\\\\\\/std_1772336418_69a3b522a0303.jpg\\\",\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-01 09:25:19'),
(73,NULL,1,NULL,NULL,'Tenant Updated',NULL,NULL,NULL,'Tenant \'Sucess Institute Birgunj\' (Brightfuture) updated.','2026-03-01 09:34:34'),
(74,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 09:25:19\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000025\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:06:59\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 10:06:59'),
(75,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',25,'{\"old\":null,\"new\":\"{\\\"id\\\":25,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000025\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 10:06:59'),
(76,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000025\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:06:59\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000026\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:07:38\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 10:07:38'),
(77,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',26,'{\"old\":null,\"new\":\"{\\\"id\\\":26,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000026\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 10:07:38'),
(78,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000026\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:07:38\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000027\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:09:56\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 10:09:56'),
(79,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',27,'{\"old\":null,\"new\":\"{\\\"id\\\":27,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000027\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 10:09:56'),
(80,5,38,'::1',NULL,'CREATE','students',54,'{\"old\":null,\"new\":\"{\\\"id\\\":54,\\\"tenant_id\\\":5,\\\"user_id\\\":87,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0054\\\",\\\"full_name\\\":\\\"Devbarat Patel\\\",\\\"dob_ad\\\":\\\"2026-03-01\\\",\\\"dob_bs\\\":null,\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":null,\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":null,\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"[]\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-01\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-01 11:01:18'),
(81,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:01:18\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000028\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:04:41\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 11:04:41'),
(82,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',28,'{\"old\":null,\"new\":\"{\\\"id\\\":28,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000028\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 11:04:41'),
(83,5,38,'::1',NULL,'CREATE','students',55,'{\"old\":null,\"new\":\"{\\\"id\\\":55,\\\"tenant_id\\\":5,\\\"user_id\\\":88,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-0055\\\",\\\"full_name\\\":\\\"Amiri Sah \\\",\\\"dob_ad\\\":\\\"1996-01-01\\\",\\\"dob_bs\\\":\\\"2052-09-17\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"AB+\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nagendra Prasad Patel\\\",\\\"mother_name\\\":\\\"Sanju Devi\\\",\\\"husband_name\\\":\\\"\\\",\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"\\\\\\\"{\\\\\\\\\\\\\\\"province\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Madhesh Province\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"district\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Parsa\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"municipality\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"Bahudramai\\\\\\\\\\\\\\\",\\\\\\\\\\\\\\\"ward\\\\\\\\\\\\\\\":\\\\\\\\\\\\\\\"07\\\\\\\\\\\\\\\"}\\\\\\\"\\\",\\\"temporary_address\\\":\\\"[]\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-01\\\",\\\"photo_url\\\":\\\"http:\\\\\\/\\\\\\/localhost\\\\\\/erp\\\\\\/public\\\\\\/uploads\\\\\\/students\\\\\\/std_1772344524_69a3d4cc64b05.jpg\\\",\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-01 11:40:25'),
(84,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',22,'{\"old\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 11:40:25\\\"}\",\"new\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000029\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 11:41:06\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 11:41:06'),
(85,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',29,'{\"old\":null,\"new\":\"{\\\"id\\\":29,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"fee_record_id\\\":22,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000029\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 11:41:06'),
(86,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000028\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:04:41\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000030\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:57:17\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 11:57:17'),
(87,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',30,'{\"old\":null,\"new\":\"{\\\"id\\\":30,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000030\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 11:57:17'),
(88,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000030\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:57:17\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000031\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"esewa\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:59:08\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 11:59:08'),
(89,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',31,'{\"old\":null,\"new\":\"{\\\"id\\\":31,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"500.00\\\",\\\"payment_method\\\":\\\"esewa\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000031\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"In Hand Cash  (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 11:59:08'),
(90,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',22,'{\"old\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000029\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 11:41:06\\\"}\",\"new\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000032\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 12:08:18\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 12:08:18'),
(91,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',32,'{\"old\":null,\"new\":\"{\\\"id\\\":32,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"fee_record_id\\\":22,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"500.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000032\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 12:08:18'),
(92,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',22,'{\"old\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"1500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000032\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 12:08:18\\\"}\",\"new\":\"{\\\"id\\\":22,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"7000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000033\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:40:25\\\",\\\"updated_at\\\":\\\"2026-03-01 12:15:59\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 12:15:59'),
(93,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',33,'{\"old\":null,\"new\":\"{\\\"id\\\":33,\\\"tenant_id\\\":5,\\\"student_id\\\":55,\\\"fee_record_id\\\":22,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"5500.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000033\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\"\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 12:15:59'),
(94,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000031\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"esewa\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 11:59:08\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2545.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000034\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:31:06\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 14:31:06'),
(95,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',34,'{\"old\":null,\"new\":\"{\\\"id\\\":34,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"45.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000034\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 14:31:06'),
(96,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2545.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000034\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:31:06\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2590.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000035\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:31:44\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 14:31:44'),
(97,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',35,'{\"old\":null,\"new\":\"{\\\"id\\\":35,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"45.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000035\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 14:31:44'),
(98,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2590.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000035\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:31:44\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2990.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000036\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:34:58\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 14:34:58'),
(99,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',36,'{\"old\":null,\"new\":\"{\\\"id\\\":36,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"400.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000036\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 14:34:58'),
(100,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"2990.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000036\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:34:58\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3990.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000037\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"esewa\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:40:08\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 14:40:08'),
(101,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',37,'{\"old\":null,\"new\":\"{\\\"id\\\":37,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"1000.00\\\",\\\"payment_method\\\":\\\"esewa\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000037\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 14:40:08'),
(102,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3990.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000037\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"esewa\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 14:40:08\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4290.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000038\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 18:54:05\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-01 18:54:05'),
(103,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',38,'{\"old\":null,\"new\":\"{\\\"id\\\":38,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"300.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000038\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-01 18:54:05'),
(104,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4290.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000038\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-01 18:54:05\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4390.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000039\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-02 04:58:40\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-02 04:58:40'),
(105,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',39,'{\"old\":null,\"new\":\"{\\\"id\\\":39,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"100.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000039\\\",\\\"payment_date\\\":\\\"2026-03-01\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":null,\\\"updated_at\\\":null}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-02 04:58:40'),
(106,NULL,1,'system',NULL,'PAYMENT_RECORDED','fee_records',26,'{\"old\":\"{\\\"id\\\":26,\\\"tenant_id\\\":5,\\\"student_id\\\":62,\\\"batch_id\\\":4,\\\"fee_item_id\\\":11,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"5000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-02\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-02 19:03:51\\\",\\\"updated_at\\\":\\\"2026-03-02 19:03:51\\\"}\",\"new\":\"{\\\"id\\\":26,\\\"tenant_id\\\":5,\\\"student_id\\\":62,\\\"batch_id\\\":4,\\\"fee_item_id\\\":11,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"5000.00\\\",\\\"amount_paid\\\":\\\"5000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-02\\\",\\\"paid_date\\\":\\\"2026-03-02\\\",\\\"receipt_no\\\":\\\"RCP-000043\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":1,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026\\\",\\\"status\\\":\\\"paid\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-02 19:03:51\\\",\\\"updated_at\\\":\\\"2026-03-02 19:03:51\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-02 19:03:51'),
(107,NULL,1,'system',NULL,'TRANSACTION_CREATED','payment_transactions',40,'{\"old\":null,\"new\":\"{\\\"id\\\":40,\\\"tenant_id\\\":5,\\\"student_id\\\":62,\\\"fee_record_id\\\":26,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"5000.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000043\\\",\\\"payment_date\\\":\\\"2026-03-02\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":1,\\\"notes\\\":\\\"Test Payment (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":\\\"2026-03-02 19:03:51\\\",\\\"updated_at\\\":\\\"2026-03-02 19:03:51\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-02 19:03:51'),
(108,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4390.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000039\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-02 04:58:40\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-04\\\",\\\"receipt_no\\\":\\\"RCP-000044\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-04 08:45:37\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-04 08:45:37'),
(109,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',41,'{\"old\":null,\"new\":\"{\\\"id\\\":41,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"610.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000044\\\",\\\"payment_date\\\":\\\"2026-03-04\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":\\\"2026-03-04 08:45:37\\\",\\\"updated_at\\\":\\\"2026-03-04 08:45:37\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-04 08:45:37'),
(110,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-04\\\",\\\"receipt_no\\\":\\\"RCP-000044\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-04 08:45:37\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5610.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-04\\\",\\\"receipt_no\\\":\\\"RCP-000045\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-04 08:46:27\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-04 08:46:27'),
(111,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',42,'{\"old\":null,\"new\":\"{\\\"id\\\":42,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"610.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000045\\\",\\\"payment_date\\\":\\\"2026-03-04\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":\\\"2026-03-04 08:46:27\\\",\\\"updated_at\\\":\\\"2026-03-04 08:46:27\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-04 08:46:27'),
(113,5,38,'::1',NULL,'CREATE','students',65,'{\"old\":null,\"new\":\"{\\\"id\\\":65,\\\"tenant_id\\\":5,\\\"user_id\\\":91,\\\"batch_id\\\":34,\\\"roll_no\\\":\\\"STD-0063\\\",\\\"full_name\\\":\\\"Nepal Cyber Firm\\\",\\\"dob_ad\\\":\\\"2010-11-17\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"\\\",\\\"phone\\\":null,\\\"email\\\":null,\\\"citizenship_no\\\":\\\"nepalcyberfirm@gmail.com\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nepal Cyber Firm\\\",\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\\\\\\\"}\\\",\\\"temporary_address\\\":\\\"{}\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-04\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"id_card_status\\\":\\\"none\\\",\\\"id_card_issued_at\\\":null,\\\"created_at\\\":\\\"2026-03-04 09:20:48\\\",\\\"updated_at\\\":\\\"2026-03-04 09:20:48\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-04 09:20:48'),
(114,5,38,'::1',NULL,'CREATE','students',72,'{\"old\":null,\"new\":\"{\\\"id\\\":72,\\\"tenant_id\\\":5,\\\"user_id\\\":105,\\\"batch_id\\\":34,\\\"roll_no\\\":\\\"STD-2026-0072\\\",\\\"full_name\\\":\\\"Badri Patel\\\",\\\"dob_ad\\\":\\\"2008-01-30\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"AB+\\\",\\\"phone\\\":\\\"9825205184\\\",\\\"email\\\":\\\"nepalcodingschool@gmail.com\\\",\\\"citizenship_no\\\":null,\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nepal Cyber Firm\\\",\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\\\\\\\"}\\\",\\\"temporary_address\\\":\\\"{}\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-05\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"id_card_status\\\":\\\"none\\\",\\\"id_card_issued_at\\\":null,\\\"created_at\\\":\\\"2026-03-05 17:24:40\\\",\\\"updated_at\\\":\\\"2026-03-05 17:24:40\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-05 17:24:40'),
(115,5,85,'::1',NULL,'CREATE','students',77,'{\"old\":null,\"new\":\"{\\\"id\\\":77,\\\"tenant_id\\\":5,\\\"user_id\\\":110,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-2026-0077\\\",\\\"full_name\\\":\\\"Devbarat Prasad Patel\\\",\\\"dob_ad\\\":\\\"2014-12-29\\\",\\\"dob_bs\\\":\\\"2071-09-14\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"\\\",\\\"phone\\\":\\\"9811144402\\\",\\\"email\\\":\\\"sanojpatel845484@gmail.com\\\",\\\"citizenship_no\\\":\\\"mind59024@gmail.com\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"Devbarat Prasad Patel\\\",\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Bahudramai-07, Phulkaul, Parsa\\\\\\\"}\\\",\\\"temporary_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Birgunj-13, Radhemai , Parsa\\\\\\\"}\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-05\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"id_card_status\\\":\\\"none\\\",\\\"id_card_issued_at\\\":null,\\\"created_at\\\":\\\"2026-03-05 17:38:20\\\",\\\"updated_at\\\":\\\"2026-03-05 17:38:20\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-05 17:38:20'),
(116,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',28,'{\"old\":\"{\\\"id\\\":28,\\\"tenant_id\\\":5,\\\"student_id\\\":72,\\\"batch_id\\\":34,\\\"fee_item_id\\\":12,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"8000.00\\\",\\\"amount_paid\\\":\\\"0.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-05\\\",\\\"paid_date\\\":null,\\\"receipt_no\\\":null,\\\"receipt_path\\\":null,\\\"payment_mode\\\":null,\\\"cashier_user_id\\\":null,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"pending\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-05 17:24:40\\\",\\\"updated_at\\\":\\\"2026-03-05 17:24:40\\\"}\",\"new\":\"{\\\"id\\\":28,\\\"tenant_id\\\":5,\\\"student_id\\\":72,\\\"batch_id\\\":34,\\\"fee_item_id\\\":12,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"8000.00\\\",\\\"amount_paid\\\":\\\"5390.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-05\\\",\\\"paid_date\\\":\\\"2026-03-05\\\",\\\"receipt_no\\\":\\\"RCP-000046\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-05 17:24:40\\\",\\\"updated_at\\\":\\\"2026-03-05 18:08:36\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-05 18:08:36'),
(117,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',43,'{\"old\":null,\"new\":\"{\\\"id\\\":43,\\\"tenant_id\\\":5,\\\"student_id\\\":72,\\\"fee_record_id\\\":28,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"5390.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000046\\\",\\\"payment_date\\\":\\\"2026-03-05\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":\\\"2026-03-05 18:08:36\\\",\\\"updated_at\\\":\\\"2026-03-05 18:08:36\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-05 18:08:36'),
(118,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5610.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-04\\\",\\\"receipt_no\\\":\\\"RCP-000045\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-04 08:46:27\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-05\\\",\\\"receipt_no\\\":\\\"RCP-000047\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:15\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-05 18:12:15'),
(119,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',44,'{\"old\":null,\"new\":\"{\\\"id\\\":44,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"390.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000047\\\",\\\"payment_date\\\":\\\"2026-03-05\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":\\\"2026-03-05 18:12:15\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:15\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-05 18:12:15'),
(120,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-05\\\",\\\"receipt_no\\\":\\\"RCP-000047\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:15\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6390.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-05\\\",\\\"receipt_no\\\":\\\"RCP-000048\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:55\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-05 18:12:55'),
(121,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',45,'{\"old\":null,\"new\":\"{\\\"id\\\":45,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"invoice_id\\\":null,\\\"amount\\\":\\\"390.00\\\",\\\"payment_method\\\":\\\"cash\\\",\\\"transaction_id\\\":null,\\\"receipt_number\\\":\\\"RCP-000048\\\",\\\"payment_date\\\":\\\"2026-03-05\\\",\\\"receipt_path\\\":null,\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\",\\\"created_at\\\":\\\"2026-03-05 18:12:55\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:55\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-05 18:12:55'),
(122,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"3000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000027\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:09:56\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":4000,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-01\\\",\\\"receipt_no\\\":\\\"RCP-000027\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-01 10:09:56\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 14:14:11'),
(123,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',46,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":1000,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000049\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 14:14:11'),
(124,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6390.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-05\\\",\\\"receipt_no\\\":\\\"RCP-000048\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:55\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6490,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-05\\\",\\\"receipt_no\\\":\\\"RCP-000048\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-05 18:12:55\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 16:10:07'),
(125,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',47,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000050\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 16:10:07'),
(126,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6490.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000050\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:10:07\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6500,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000050\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:10:07\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 16:28:09'),
(127,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',48,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"amount\\\":10,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000051\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 16:28:09'),
(128,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6500.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000051\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:28:09\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6600,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000051\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:28:09\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 16:39:25'),
(129,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',49,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000052\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 16:39:25'),
(130,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6600.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000052\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:39:25\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6700,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000052\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:39:25\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 16:41:48'),
(131,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',50,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000053\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 16:41:48'),
(132,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6700.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000053\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:41:48\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6800,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000053\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 16:41:48\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 17:01:48'),
(133,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',51,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000054\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 17:01:48'),
(134,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',21,'{\"old\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6800.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000054\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 17:01:48\\\"}\",\"new\":\"{\\\"id\\\":21,\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6900,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000054\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 11:01:18\\\",\\\"updated_at\\\":\\\"2026-03-06 17:01:48\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 17:11:45'),
(135,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',52,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":54,\\\"fee_record_id\\\":21,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000055\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 17:11:45'),
(136,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000049\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 14:14:11\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":4100,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000049\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 14:14:11\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 17:28:10'),
(137,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',53,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000056\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 17:28:10'),
(138,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"4100.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000056\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 17:28:10\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":5000,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000056\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 17:28:10\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 17:34:10'),
(139,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',54,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":900,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000057\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 17:34:10'),
(140,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000057\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 17:34:10\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":5100,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000057\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 17:34:10\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 19:20:32'),
(141,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',55,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000058\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 19:20:32'),
(142,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5100.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000058\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:20:32\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":5200,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000058\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:20:32\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 19:34:33'),
(143,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',56,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000059\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 19:34:33'),
(144,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5200.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000059\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:34:33\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":5300,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000059\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:34:33\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 19:38:57'),
(145,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',57,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000060\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 19:38:57'),
(146,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5300.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000060\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:38:57\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":5400,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000060\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:38:57\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 19:44:10'),
(147,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',58,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000061\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 19:44:10'),
(148,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"5400.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000061\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:44:10\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6000,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000061\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:44:10\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 19:45:11'),
(149,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',59,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":600,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000062\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 19:45:11'),
(150,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6000.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000062\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:45:11\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6100,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000062\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:45:11\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 19:49:56'),
(151,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',60,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000063\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 19:49:56'),
(152,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6100.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000063\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:49:56\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6200,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000063\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 19:49:56\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 20:56:17'),
(153,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',61,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000064\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 20:56:17'),
(154,5,38,'::1',NULL,'PAYMENT_RECORDED','fee_records',20,'{\"old\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":\\\"6200.00\\\",\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000064\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 20:56:17\\\"}\",\"new\":\"{\\\"id\\\":20,\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"batch_id\\\":4,\\\"fee_item_id\\\":2,\\\"installment_no\\\":1,\\\"amount_due\\\":\\\"7000.00\\\",\\\"amount_paid\\\":6300,\\\"discount_amount\\\":\\\"0.00\\\",\\\"due_date\\\":\\\"2026-03-01\\\",\\\"paid_date\\\":\\\"2026-03-06\\\",\\\"receipt_no\\\":\\\"RCP-000064\\\",\\\"receipt_path\\\":null,\\\"payment_mode\\\":\\\"cash\\\",\\\"cashier_user_id\\\":38,\\\"fine_applied\\\":\\\"0.00\\\",\\\"fine_waived\\\":\\\"0.00\\\",\\\"notes\\\":null,\\\"academic_year\\\":\\\"2026-2027\\\",\\\"status\\\":\\\"partial\\\",\\\"invoice_id\\\":null,\\\"created_at\\\":\\\"2026-03-01 09:25:19\\\",\\\"updated_at\\\":\\\"2026-03-06 20:56:17\\\"}\"}','Audited PAYMENT_RECORDED on fee_records','2026-03-06 20:57:53'),
(155,5,38,'::1',NULL,'TRANSACTION_CREATED','payment_transactions',62,'{\"old\":null,\"new\":\"{\\\"tenant_id\\\":5,\\\"student_id\\\":53,\\\"fee_record_id\\\":20,\\\"amount\\\":100,\\\"payment_method\\\":\\\"cash\\\",\\\"receipt_number\\\":\\\"RCP-000065\\\",\\\"payment_date\\\":\\\"2026-03-06\\\",\\\"recorded_by\\\":38,\\\"notes\\\":\\\" (Bulk Payment Part)\\\",\\\"status\\\":\\\"completed\\\"}\"}','Audited TRANSACTION_CREATED on payment_transactions','2026-03-06 20:57:53'),
(156,5,38,'::1',NULL,'CREATE','students',78,'{\"old\":null,\"new\":\"{\\\"id\\\":78,\\\"tenant_id\\\":5,\\\"user_id\\\":111,\\\"batch_id\\\":4,\\\"roll_no\\\":\\\"STD-2026-0078\\\",\\\"full_name\\\":\\\"Nepal Cyber Firm\\\",\\\"dob_ad\\\":\\\"2003-06-10\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"B+\\\",\\\"phone\\\":\\\"9845012350\\\",\\\"email\\\":\\\"nepalcodingschool@gmail.com\\\",\\\"citizenship_no\\\":\\\"nepalcyberfirm@gmail.com\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nepal Cyber Firm\\\",\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\\\\\\\"}\\\",\\\"temporary_address\\\":\\\"{}\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-07\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"id_card_status\\\":\\\"none\\\",\\\"id_card_issued_at\\\":null,\\\"created_at\\\":\\\"2026-03-07 06:01:17\\\",\\\"updated_at\\\":\\\"2026-03-07 06:01:17\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-07 06:01:23'),
(157,5,38,'::1',NULL,'CREATE','students',79,'{\"old\":null,\"new\":\"{\\\"id\\\":79,\\\"tenant_id\\\":5,\\\"user_id\\\":112,\\\"batch_id\\\":34,\\\"roll_no\\\":\\\"STD-2026-0079\\\",\\\"full_name\\\":\\\"News Gunj Medai\\\",\\\"dob_ad\\\":\\\"1998-03-12\\\",\\\"dob_bs\\\":\\\"2052-09-17\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"A+\\\",\\\"phone\\\":\\\"9845012350\\\",\\\"email\\\":\\\"medianewsgunj@gmail.com\\\",\\\"citizenship_no\\\":\\\"nepalcyberfirm@gmail.com\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nepal Cyber Firm\\\",\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\\\\\\\"}\\\",\\\"temporary_address\\\":\\\"{}\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-08\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"id_card_status\\\":\\\"none\\\",\\\"id_card_issued_at\\\":null,\\\"created_at\\\":\\\"2026-03-08 12:21:43\\\",\\\"updated_at\\\":\\\"2026-03-08 12:21:43\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-08 12:21:47'),
(158,5,38,'::1',NULL,'CREATE','students',80,'{\"old\":null,\"new\":\"{\\\"id\\\":80,\\\"tenant_id\\\":5,\\\"user_id\\\":113,\\\"batch_id\\\":34,\\\"roll_no\\\":\\\"STD-2026-0080\\\",\\\"full_name\\\":\\\"Nepal Cyber Firm\\\",\\\"dob_ad\\\":\\\"2003-11-12\\\",\\\"dob_bs\\\":\\\"2063-09-05\\\",\\\"gender\\\":\\\"male\\\",\\\"blood_group\\\":\\\"\\\",\\\"phone\\\":\\\"9833344402\\\",\\\"email\\\":\\\"medianewsgunj@gmail.com\\\",\\\"citizenship_no\\\":\\\"9852505484\\\",\\\"national_id\\\":null,\\\"father_name\\\":\\\"Nepal Cyber Firm\\\",\\\"mother_name\\\":null,\\\"husband_name\\\":null,\\\"guardian_name\\\":null,\\\"guardian_relation\\\":null,\\\"permanent_address\\\":\\\"{\\\\\\\"address\\\\\\\":\\\\\\\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\\\\\\\"}\\\",\\\"temporary_address\\\":\\\"{}\\\",\\\"academic_qualifications\\\":\\\"[]\\\",\\\"admission_date\\\":\\\"2026-03-08\\\",\\\"photo_url\\\":null,\\\"identity_doc_url\\\":null,\\\"status\\\":\\\"active\\\",\\\"registration_mode\\\":\\\"full\\\",\\\"registration_status\\\":\\\"fully_registered\\\",\\\"id_card_status\\\":\\\"none\\\",\\\"id_card_issued_at\\\":null,\\\"created_at\\\":\\\"2026-03-08 12:35:06\\\",\\\"updated_at\\\":\\\"2026-03-08 12:35:06\\\",\\\"deleted_at\\\":null}\"}','Audited CREATE on students','2026-03-08 12:35:10'),
(159,NULL,1,NULL,NULL,'Tenant Created',NULL,NULL,NULL,'New tenant \'Hamro Loksewa institute\' (hamroloksewa) created with admin \'toonmitra355@gmail.com\'','2026-03-08 13:05:44');

/*Table structure for table `batch_subject_allocations` */

DROP TABLE IF EXISTS `batch_subject_allocations`;

CREATE TABLE `batch_subject_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch_subject` (`batch_id`,`subject_id`),
  KEY `fk_bsa_tenant` (`tenant_id`),
  KEY `fk_bsa_teacher` (`teacher_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `batch_subject_allocations` */

insert  into `batch_subject_allocations`(`id`,`tenant_id`,`batch_id`,`teacher_id`,`subject_id`,`created_at`,`updated_at`) values 
(1,5,4,9,1,'2026-02-27 08:18:17','2026-03-03 19:35:35');

/*Table structure for table `batches` */

DROP TABLE IF EXISTS `batches`;

CREATE TABLE `batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `shift` enum('morning','day','evening') NOT NULL DEFAULT 'morning',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `max_strength` int(10) unsigned NOT NULL DEFAULT 40,
  `room` varchar(50) DEFAULT NULL,
  `status` enum('upcoming','active','completed') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_batches_tenant` (`tenant_id`),
  KEY `fk_batches_course` (`course_id`),
  KEY `idx_batches_status` (`status`),
  CONSTRAINT `fk_batches_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_batches_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `batches` */

insert  into `batches`(`id`,`tenant_id`,`course_id`,`name`,`shift`,`start_date`,`end_date`,`max_strength`,`room`,`status`,`created_at`,`updated_at`,`deleted_at`) values 
(4,5,4,'Kharidar First Papper 2082','morning','2026-02-03','2026-02-27',40,'101','active','2026-02-24 07:29:25','2026-02-24 07:29:25',NULL),
(34,5,38,'Evening Batch Nasu','morning','2004-06-25','2010-07-03',40,'','active','2026-03-03 21:44:48','2026-03-03 21:44:48',NULL);

/*Table structure for table `courses` */

DROP TABLE IF EXISTS `courses`;

CREATE TABLE `courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `fee` decimal(15,2) DEFAULT 0.00,
  `duration_weeks` int(10) unsigned DEFAULT NULL,
  `seats` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `category` enum('loksewa','health','banking','tsc','general','engineering') NOT NULL DEFAULT 'general',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `duration_months` smallint(5) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_courses_tenant` (`tenant_id`),
  CONSTRAINT `fk_courses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `courses` */

insert  into `courses`(`id`,`tenant_id`,`name`,`code`,`description`,`fee`,`duration_weeks`,`seats`,`is_active`,`category`,`status`,`duration_months`,`created_at`,`updated_at`,`deleted_at`) values 
(4,5,'Kharidar First Papper ','KH-01','abc',7000.00,12,100,1,'loksewa','active',NULL,'2026-02-24 07:25:28','2026-02-27 10:27:03',NULL),
(5,5,'Kharidar Second Papper ','KH-02','abc',12500.00,12,100,1,'loksewa','active',NULL,'2026-02-26 14:44:57','2026-02-27 06:55:30','2026-02-27 06:55:30'),
(6,5,'Kharidar Third papper','KH-03','Kharidar all pappers',7000.00,14,99,1,'loksewa','active',NULL,'2026-02-26 15:01:15','2026-02-27 06:55:35','2026-02-27 06:55:35'),
(38,5,'Nayab Shhuba ','NY101',' ej\n',8000.00,12,99,1,'loksewa','active',NULL,'2026-03-03 21:43:49','2026-03-03 21:43:49',NULL);

/*Table structure for table `dashboard_checklists` */

DROP TABLE IF EXISTS `dashboard_checklists`;

CREATE TABLE `dashboard_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `checklist_date` date NOT NULL,
  `step_key` varchar(100) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_checklist_step` (`tenant_id`,`user_id`,`checklist_date`,`step_key`),
  KEY `dashboard_checklists_tenant_id_checklist_date_index` (`tenant_id`,`checklist_date`),
  KEY `dashboard_checklists_user_id_foreign` (`user_id`),
  CONSTRAINT `dashboard_checklists_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dashboard_checklists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `dashboard_checklists` */

/*Table structure for table `dashboard_targets` */

DROP TABLE IF EXISTS `dashboard_targets`;

CREATE TABLE `dashboard_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `fee_collection_target` decimal(15,2) DEFAULT 0.00,
  `enrollment_target` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_target_month` (`tenant_id`,`year`,`month`),
  KEY `idx_tenant_month` (`tenant_id`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `dashboard_targets` */

/*Table structure for table `email_logs` */

DROP TABLE IF EXISTS `email_logs`;

CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'failed',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_email_logs_tenant` (`tenant_id`),
  KEY `fk_email_logs_student` (`student_id`),
  CONSTRAINT `fk_email_logs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_email_logs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `email_logs` */

insert  into `email_logs`(`id`,`tenant_id`,`student_id`,`email`,`subject`,`status`,`error_message`,`created_at`) values 
(1,5,NULL,'autotest1772153475@example.com','Welcome Credentials','sent',NULL,'2026-02-27 06:36:21'),
(2,5,NULL,'autotest1772153527@example.com','Welcome Credentials','sent',NULL,'2026-02-27 06:37:12'),
(3,5,NULL,'nepalcyberfirm@gmail.com','Welcome Credentials','sent',NULL,'2026-02-27 06:37:13'),
(4,5,NULL,'autotest1772153613@example.com','Welcome Credentials','sent',NULL,'2026-02-27 06:38:38'),
(5,5,NULL,'autotest1772153892@example.com','Welcome Credentials','sent',NULL,'2026-02-27 06:43:18'),
(6,5,NULL,'autotest1772153927@example.com','Welcome Credentials','sent',NULL,'2026-02-27 06:43:52'),
(7,5,NULL,'mind59024@gmail.com','Welcome Credentials','sent',NULL,'2026-02-27 07:09:42'),
(8,5,NULL,'medianewsgunj@gmail.com','Welcome Credentials','sent',NULL,'2026-02-27 15:05:07'),
(9,5,NULL,'addamssmith937@gmail.com','Welcome Credentials','sent',NULL,'2026-02-28 07:42:35'),
(10,5,NULL,'test1@example.com','Welcome Credentials','sent',NULL,'2026-02-28 11:42:25'),
(11,5,NULL,'test1@example.com','Welcome Credentials','sent',NULL,'2026-02-28 11:49:25'),
(12,5,NULL,'test1@example.com','Welcome Credentials','sent',NULL,'2026-02-28 12:03:35'),
(13,5,NULL,'test1@example.com','Welcome Credentials','sent',NULL,'2026-02-28 12:05:50'),
(14,5,NULL,'test2@example.com','Welcome Credentials','sent',NULL,'2026-02-28 12:05:55'),
(15,5,NULL,'mind59024@gmail.com','Welcome Credentials','sent',NULL,'2026-02-28 14:30:11'),
(16,5,NULL,'mind59024@gmail.com','Welcome Credentials','sent',NULL,'2026-02-28 14:39:12'),
(17,5,NULL,'addamssmith937@gmail.com','Welcome Credentials','sent',NULL,'2026-02-28 14:41:36'),
(18,5,NULL,'nepalcodingschool@gmail.com','Welcome Credentials','sent',NULL,'2026-03-01 07:07:33'),
(19,5,NULL,'nepalcodingschool@gmail.com','Welcome Credentials','sent',NULL,'2026-03-01 07:10:57'),
(20,5,NULL,'addamssmith937@gmail.com','Welcome Credentials','sent',NULL,'2026-03-01 07:25:25'),
(21,5,53,'addamssmith937@gmail.com','Welcome Credentials','sent',NULL,'2026-03-01 09:25:24'),
(22,5,54,'dhirendraparshad65@gmail.com','Welcome Credentials','sent',NULL,'2026-03-01 11:01:24'),
(23,5,55,'amirisah1@gmail.com','Welcome Credentials','sent',NULL,'2026-03-01 11:40:30'),
(24,5,65,'nepalcyberfirm@gmail.com','Welcome Credentials','sent',NULL,'2026-03-04 09:20:53'),
(25,5,72,'nepalcodingschool@gmail.com','Welcome Credentials','sent',NULL,'2026-03-05 17:24:56'),
(26,5,76,'test_student_1772711133@example.com','Welcome Credentials','failed','Failed to send welcome email','2026-03-05 17:30:33'),
(27,5,77,'sanojpatel845484@gmail.com','Welcome Credentials','sent',NULL,'2026-03-05 17:38:30');

/*Table structure for table `email_settings` */

DROP TABLE IF EXISTS `email_settings`;

CREATE TABLE `email_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `reply_to_email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email_settings_tenant` (`tenant_id`),
  CONSTRAINT `fk_email_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `email_settings` */

/*Table structure for table `email_templates` */

DROP TABLE IF EXISTS `email_templates`;

CREATE TABLE `email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `template_key` varchar(50) NOT NULL COMMENT 'e.g., welcome_email, payment_success, fee_reminder, etc.',
  `template_name` varchar(100) NOT NULL COMMENT 'Human readable name',
  `subject` varchar(255) NOT NULL,
  `body_content` text NOT NULL COMMENT 'HTML content with {{placeholders}}',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template_key_tenant` (`tenant_id`,`template_key`),
  CONSTRAINT `fk_email_templates_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `email_templates` */

insert  into `email_templates`(`id`,`tenant_id`,`template_key`,`template_name`,`subject`,`body_content`,`is_active`,`created_at`,`updated_at`) values 
(1,5,'welcome_student','Welcome Student','Welcome to {{institute_name}}','<p>Welcome to  <strong> {{institute_name}</strong>}!</p><p>You are Successfully Enrolled</p><p>Dear   {{student_name}},</p><p> COURSE:<em> {{course_name}}</em></p><p><br></p><p>THANK YOU</p>',1,'2026-03-01 06:55:29','2026-03-01 07:24:07'),
(2,5,'payment_success','Payment Successful','Payment Receipt - {{institute_name}}','<p>Hi , Dear Student Your  Payment for {{course_name}} of {{amount}} has been received sucessfully .</p><p><br></p><p>Thank You ,</p><p>{{institute_name}}</p>',1,'2026-03-01 06:55:29','2026-03-01 07:19:21'),
(3,5,'fee_reminder','Fee Reminder','Upcoming Fee Reminder - {{institute_name}}','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>This is a gentle reminder that your {{fee_type}} of <strong>{{amount}}</strong> is due on <strong>{{due_date}}</strong>.</p><p>Please ensure timely payment to avoid late fines.</p><br><p>Best regards,<br>Accounts Team, {{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(4,5,'exam_schedule','Exam Schedule Published','New Exam Schedule: {{exam_name}}','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>The schedule for <strong>{{exam_name}}</strong> has been published. The exam will start on <strong>{{start_date}}</strong>.</p><p>Please log in to the portal to view the full timetable.</p><br><p>Best of luck,<br>{{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(5,5,'exam_result','Exam Result Published','Results for {{exam_name}}','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>The results for <strong>{{exam_name}}</strong> have been published.</p><p>Please log in to your student portal to view your grades and feedback.</p><br><p>Best regards,<br>{{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(6,5,'course_enrollment','Course Enrollment','Enrolled in {{course_name}}','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>You have been successfully enrolled in <strong>{{course_name}}</strong>.</p><p>Your classes in batch <strong>{{batch_name}}</strong> start on {{start_date}}.</p><br><p>Best regards,<br>{{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(7,5,'attendance_warning','Attendance Warning','Low Attendance Alert','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>This is a notification regarding your attendance in <strong>{{course_name}}</strong>.</p><p>Your current attendance is <strong>{{attendance_percentage}}%</strong>, which is below the required threshold.</p><p>Please ensure you attend the remaining classes regularly.</p><br><p>Best regards,<br>{{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(8,5,'assignment_new','New Assignment','New Assignment: {{assignment_title}}','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>A new assignment <strong>\\\"{{assignment_title}}\\\"</strong> has been posted in {{course_name}}.</p><p><strong>Due Date:</strong> {{due_date}}</p><p>Log in to the portal to view the details and submit your work.</p><br><p>Best regards,<br>{{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(9,5,'general_announcement','General Announcement','Important Announcement from {{institute_name}}','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><h3>{{announcement_title}}</h3><p>{{announcement_content}}</p><br><p>Best regards,<br>{{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29'),
(10,5,'account_suspension','Account Suspended','Account Suspension Notice','<div style=\\\"font-family:sans-serif;color:#333;\\\"><p>Dear {{student_name}},</p><p>Your student account at {{institute_name}} has been temporarily suspended.</p><p>Reason: {{suspension_reason}}</p><p>Please contact the administration office immediately to resolve this issue.</p><br><p>Best regards,<br>Admin Office, {{institute_name}}</p></div>',1,'2026-03-01 06:55:29','2026-03-01 06:55:29');

/*Table structure for table `enrollments` */

DROP TABLE IF EXISTS `enrollments`;

CREATE TABLE `enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` varchar(50) DEFAULT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('active','completed','dropped','transferred') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_changed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when enrollment status last changed (completed/dropped/transferred)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_enrollment` (`tenant_id`,`student_id`,`batch_id`),
  KEY `fk_enr_tenant` (`tenant_id`),
  KEY `fk_enr_student` (`student_id`),
  KEY `fk_enr_batch` (`batch_id`),
  CONSTRAINT `fk_enr_batch_ref` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enr_student_ref` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enr_tenant_ref` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `enrollments` */

insert  into `enrollments`(`id`,`tenant_id`,`student_id`,`batch_id`,`enrollment_id`,`enrollment_date`,`status`,`created_at`,`updated_at`,`status_changed_at`) values 
(30,5,53,4,NULL,'2026-03-01','active','2026-03-01 09:25:19','2026-03-01 09:25:19',NULL),
(31,5,54,4,NULL,'2026-03-01','active','2026-03-01 11:01:18','2026-03-01 11:01:18',NULL),
(32,5,55,4,NULL,'2026-03-01','active','2026-03-01 11:40:25','2026-03-01 11:40:25',NULL),
(33,5,65,34,'ENR-5-2026-00065','2026-03-04','active','2026-03-04 09:20:48','2026-03-04 09:20:48',NULL),
(37,5,72,34,'ENR-5-2026-00072','2026-03-05','active','2026-03-05 17:24:40','2026-03-05 17:24:40',NULL),
(41,5,76,4,'ENR-5-2026-00076','2026-03-05','active','2026-03-05 17:30:33','2026-03-05 17:30:33',NULL),
(42,5,77,4,'ENR-5-2026-00077','2026-03-05','active','2026-03-05 17:38:20','2026-03-05 17:38:20',NULL),
(43,5,78,4,'ENR-5-2026-00078','2026-03-07','active','2026-03-07 06:01:23','2026-03-07 06:01:23',NULL),
(44,5,79,34,'ENR-5-2026-00079','2026-03-08','active','2026-03-08 12:21:47','2026-03-08 12:21:47',NULL),
(45,5,80,34,'ENR-5-2026-00080','2026-03-08','active','2026-03-08 12:35:10','2026-03-08 12:35:10',NULL);

/*Table structure for table `exam_attempts` */

DROP TABLE IF EXISTS `exam_attempts`;

CREATE TABLE `exam_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `exam_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `start_time` datetime NOT NULL,
  `submit_time` datetime DEFAULT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `score` decimal(6,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `rank` int(10) unsigned DEFAULT NULL,
  `time_taken_secs` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_exam_attempts_tenant_exam` (`tenant_id`,`exam_id`),
  KEY `fk_ea_exam` (`exam_id`),
  KEY `fk_ea_student` (`student_id`),
  CONSTRAINT `fk_ea_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ea_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ea_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `exam_attempts` */

/*Table structure for table `exam_questions` */

DROP TABLE IF EXISTS `exam_questions`;

CREATE TABLE `exam_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `exam_id` bigint(20) unsigned NOT NULL,
  `question_id` bigint(20) unsigned NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_question` (`exam_id`,`question_id`),
  KEY `fk_eq_tenant` (`tenant_id`),
  KEY `fk_eq_question` (`question_id`),
  CONSTRAINT `fk_eq_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eq_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eq_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `exam_questions` */

/*Table structure for table `exams` */

DROP TABLE IF EXISTS `exams`;

CREATE TABLE `exams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `created_by_user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration_minutes` smallint(5) unsigned NOT NULL,
  `total_marks` decimal(6,2) NOT NULL,
  `negative_mark` decimal(4,2) NOT NULL DEFAULT 0.00,
  `question_mode` enum('manual','auto') NOT NULL DEFAULT 'manual',
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `status` enum('draft','scheduled','active','completed','cancelled') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_exams_batch` (`batch_id`),
  KEY `fk_exams_course` (`course_id`),
  KEY `fk_exams_creator` (`created_by_user_id`),
  KEY `idx_tenant_start_at` (`tenant_id`,`start_at`),
  CONSTRAINT `fk_exams_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exams_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exams_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_exams_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `exams` */

/*Table structure for table `failed_logins` */

DROP TABLE IF EXISTS `failed_logins`;

CREATE TABLE `failed_logins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_failed_user` (`user_id`),
  KEY `idx_failed_time` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `failed_logins` */

insert  into `failed_logins`(`id`,`user_id`,`ip_address`,`attempted_at`) values 
(5,42,'::1','2026-02-25 07:58:44'),
(6,42,'::1','2026-02-25 07:59:00'),
(7,42,'::1','2026-02-25 08:00:08'),
(16,40,'::1','2026-02-26 17:53:58'),
(18,40,'::1','2026-02-28 13:25:19'),
(19,40,'::1','2026-02-28 17:02:10'),
(20,40,'::1','2026-03-01 06:19:40');

/*Table structure for table `fee_items` */

DROP TABLE IF EXISTS `fee_items`;

CREATE TABLE `fee_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('admission','monthly','exam','material','fine','other') NOT NULL DEFAULT 'monthly',
  `amount` decimal(10,2) NOT NULL,
  `installments` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `late_fine_per_day` decimal(8,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_fee_items_tenant` (`tenant_id`),
  KEY `fk_fee_items_course` (`course_id`),
  CONSTRAINT `fk_fee_items_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_items_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_items` */

insert  into `fee_items`(`id`,`tenant_id`,`course_id`,`name`,`type`,`amount`,`installments`,`late_fine_per_day`,`is_active`,`created_at`,`updated_at`,`deleted_at`) values 
(1,5,6,'Tuition Fee - Kharidar Third papper','monthly',7000.00,1,0.00,1,'2026-02-26 15:01:15','2026-03-04 08:49:04','2026-02-27 06:55:35'),
(2,5,4,'Tuition Fee - Kharidar First Papper ','monthly',7000.00,1,0.00,1,'2026-02-27 06:44:18','2026-03-04 08:49:04',NULL),
(11,5,4,'Term Fee','other',5000.00,1,0.00,1,'2026-03-02 19:03:51','2026-03-02 19:03:51',NULL),
(12,5,38,'Tuition Fee - Nayab Shhuba ','monthly',8000.00,1,0.00,1,'2026-03-03 21:43:49','2026-03-04 08:49:04',NULL);

/*Table structure for table `fee_ledger` */

DROP TABLE IF EXISTS `fee_ledger`;

CREATE TABLE `fee_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `payment_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `fee_record_id` bigint(20) unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `entry_type` enum('debit','credit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_student` (`tenant_id`,`student_id`),
  KEY `idx_entry_date` (`entry_date`),
  CONSTRAINT `fk_fee_ledger_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_ledger` */

insert  into `fee_ledger`(`id`,`tenant_id`,`student_id`,`payment_transaction_id`,`fee_record_id`,`entry_date`,`entry_type`,`amount`,`description`,`created_at`,`updated_at`) values 
(1,5,53,55,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000058','2026-03-06 19:20:32','2026-03-06 19:20:32'),
(2,5,53,56,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000059','2026-03-06 19:34:33','2026-03-06 19:34:33'),
(3,5,53,57,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000060','2026-03-06 19:38:57','2026-03-06 19:38:57'),
(4,5,53,58,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000061','2026-03-06 19:44:10','2026-03-06 19:44:10'),
(5,5,53,59,NULL,'2026-03-06','credit',600.00,'Bulk Fee Payment - Receipt #RCP-000062','2026-03-06 19:45:11','2026-03-06 19:45:11'),
(6,5,53,60,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000063','2026-03-06 19:49:56','2026-03-06 19:49:56'),
(7,5,53,61,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000064','2026-03-06 20:56:17','2026-03-06 20:56:17'),
(8,5,53,62,NULL,'2026-03-06','credit',100.00,'Bulk Fee Payment - Receipt #RCP-000065','2026-03-06 20:57:53','2026-03-06 20:57:53'),
(9,5,53,16,NULL,'2026-03-06','credit',50.00,'Bulk Fee Payment - Receipt #RCP-000066','2026-03-06 21:21:25','2026-03-06 21:21:25'),
(10,5,53,17,NULL,'2026-03-06','credit',50.00,'Bulk Fee Payment - Receipt #RCP-000067','2026-03-07 04:32:01','2026-03-07 04:32:01'),
(11,5,80,18,NULL,'2026-03-08','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000068','2026-03-08 15:08:51','2026-03-08 15:08:51'),
(12,5,78,19,NULL,'2026-03-08','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000069','2026-03-08 15:26:28','2026-03-08 15:26:28');

/*Table structure for table `fee_records` */

DROP TABLE IF EXISTS `fee_records`;

CREATE TABLE `fee_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `fee_item_id` bigint(20) unsigned NOT NULL,
  `installment_no` tinyint(3) unsigned NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `payment_mode` enum('cash','bank_transfer','cheque','esewa','khalti') DEFAULT NULL,
  `cashier_user_id` bigint(20) unsigned DEFAULT NULL,
  `fine_applied` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fine_waived` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('pending','paid','partial','overdue','cancelled') DEFAULT 'pending',
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_fee_records_receipt_no` (`receipt_no`),
  KEY `idx_fee_records_tenant_student` (`tenant_id`,`student_id`),
  KEY `idx_fee_records_tenant_due_date` (`tenant_id`,`due_date`),
  KEY `fk_fee_records_student` (`student_id`),
  KEY `fk_fee_records_cashier` (`cashier_user_id`),
  KEY `idx_fee_records_status` (`status`),
  KEY `idx_fee_records_batch` (`batch_id`),
  KEY `idx_fee_records_fee_item_inst` (`fee_item_id`,`installment_no`),
  KEY `idx_tenant_paid_date` (`tenant_id`,`paid_date`),
  KEY `idx_tenant_due_date` (`tenant_id`,`due_date`),
  KEY `idx_fee_records_status_simple` (`status`),
  KEY `idx_fee_records_created_at` (`created_at`),
  KEY `idx_fee_records_status_tenant` (`status`,`tenant_id`),
  KEY `idx_fee_records_batch_tenant` (`batch_id`,`tenant_id`,`status`),
  CONSTRAINT `fk_fee_records_cashier` FOREIGN KEY (`cashier_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fee_records_fee_item` FOREIGN KEY (`fee_item_id`) REFERENCES `fee_items` (`id`),
  CONSTRAINT `fk_fee_records_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_records_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_records` */

insert  into `fee_records`(`id`,`tenant_id`,`student_id`,`batch_id`,`fee_item_id`,`installment_no`,`amount_due`,`amount_paid`,`discount_amount`,`due_date`,`paid_date`,`receipt_no`,`receipt_path`,`payment_mode`,`cashier_user_id`,`fine_applied`,`fine_waived`,`notes`,`academic_year`,`status`,`invoice_id`,`created_at`,`updated_at`) values 
(20,5,53,4,2,1,7000.00,6400.00,0.00,'2026-03-01','2026-03-06','RCP-000067',NULL,'cash',38,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-01 09:25:19','2026-03-07 04:32:01'),
(21,5,54,4,2,1,7000.00,6900.00,0.00,'2026-03-01','2026-03-06','RCP-000055',NULL,'cash',38,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-01 11:01:18','2026-03-06 17:11:45'),
(22,5,55,4,2,1,7000.00,7000.00,0.00,'2026-03-01','2026-03-01','RCP-000033',NULL,'cash',38,0.00,0.00,NULL,'2026-2027','paid',NULL,'2026-03-01 11:40:25','2026-03-01 12:15:59'),
(26,5,62,4,11,1,5000.00,5000.00,0.00,'2026-03-02','2026-03-02','RCP-000043',NULL,'cash',1,0.00,0.00,NULL,'2026-2027','paid',NULL,'2026-03-02 19:03:51','2026-03-04 08:49:04'),
(27,5,65,34,12,1,8000.00,0.00,0.00,'2026-03-04',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-04 09:20:48','2026-03-04 09:20:48'),
(28,5,72,34,12,1,8000.00,5390.00,0.00,'2026-03-05','2026-03-05','RCP-000046',NULL,'cash',38,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-05 17:24:40','2026-03-05 18:08:36'),
(29,5,76,4,2,1,7000.00,0.00,0.00,'2026-03-05',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-05 17:30:33','2026-03-05 17:30:33'),
(30,5,76,4,11,1,5000.00,0.00,0.00,'2026-03-05',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-05 17:30:33','2026-03-05 17:30:33'),
(31,5,77,4,2,1,7000.00,0.00,0.00,'2026-03-05',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-05 17:38:20','2026-03-05 17:38:20'),
(32,5,77,4,11,1,5000.00,0.00,0.00,'2026-03-05',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-05 17:38:20','2026-03-05 17:38:20'),
(33,5,78,4,2,1,7000.00,1000.00,0.00,'2026-03-07','2026-03-08','RCP-000069',NULL,'cash',38,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-07 06:01:23','2026-03-08 15:26:28'),
(34,5,78,4,11,1,5000.00,0.00,0.00,'2026-03-07',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-07 06:01:23','2026-03-07 06:01:23'),
(35,5,79,34,12,1,8000.00,0.00,0.00,'2026-03-08',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-08 12:21:47','2026-03-08 12:21:47'),
(36,5,80,34,12,1,8000.00,1000.00,0.00,'2026-03-08','2026-03-08','RCP-000068',NULL,'cash',38,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-08 12:35:10','2026-03-08 15:08:51');

/*Table structure for table `fee_settings` */

DROP TABLE IF EXISTS `fee_settings`;

CREATE TABLE `fee_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `invoice_prefix` varchar(10) NOT NULL DEFAULT 'INV',
  `receipt_prefix` varchar(10) NOT NULL DEFAULT 'RCP',
  `next_invoice_number` int(11) NOT NULL DEFAULT 1,
  `next_receipt_number` int(11) NOT NULL DEFAULT 1,
  `auto_generate_invoice` tinyint(1) NOT NULL DEFAULT 1,
  `send_invoice_email` tinyint(1) NOT NULL DEFAULT 1,
  `apply_late_fine` tinyint(1) NOT NULL DEFAULT 1,
  `late_fine_grace_days` int(11) NOT NULL DEFAULT 5,
  `currency` varchar(3) NOT NULL DEFAULT 'NPR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_settings_tenant_id_unique` (`tenant_id`),
  CONSTRAINT `fee_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_settings` */

insert  into `fee_settings`(`id`,`tenant_id`,`invoice_prefix`,`receipt_prefix`,`next_invoice_number`,`next_receipt_number`,`auto_generate_invoice`,`send_invoice_email`,`apply_late_fine`,`late_fine_grace_days`,`currency`,`created_at`,`updated_at`) values 
(2,5,'INV','RCP',1,70,1,1,1,5,'NPR',NULL,NULL);

/*Table structure for table `feedbacks` */

DROP TABLE IF EXISTS `feedbacks`;

CREATE TABLE `feedbacks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `module` varchar(255) DEFAULT NULL,
  `page` varchar(255) DEFAULT NULL,
  `problem` text NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `feedbacks` */

/*Table structure for table `guardians` */

DROP TABLE IF EXISTS `guardians`;

CREATE TABLE `guardians` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `relation` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guardian_user_student` (`user_id`,`student_id`),
  KEY `fk_guardians_tenant` (`tenant_id`),
  KEY `fk_guardians_student` (`student_id`),
  CONSTRAINT `fk_guardians_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_guardians_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_guardians_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `guardians` */

/*Table structure for table `homework` */

DROP TABLE IF EXISTS `homework`;

CREATE TABLE `homework` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `total_marks` int(11) DEFAULT 100,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('draft','published','closed') DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `course_id` (`course_id`),
  KEY `batch_id` (`batch_id`),
  KEY `subject_id` (`subject_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `homework` */

insert  into `homework`(`id`,`tenant_id`,`course_id`,`batch_id`,`subject_id`,`title`,`description`,`due_date`,`total_marks`,`attachment_path`,`created_by`,`status`,`created_at`,`updated_at`) values 
(1,1,1,1,1,'Test PHP','','2026-03-10',100,NULL,1,'published','2026-03-08 09:37:46','2026-03-08 09:37:46'),
(2,5,4,4,1,'chapter   five','nioi','2026-04-09',100,NULL,38,'published','2026-03-08 09:46:00','2026-03-08 09:46:00');

/*Table structure for table `homework_submissions` */

DROP TABLE IF EXISTS `homework_submissions`;

CREATE TABLE `homework_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `homework_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `submission_text` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `marks_obtained` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('pending','submitted','graded','late') DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `hw_student_unique` (`homework_id`,`student_id`),
  KEY `homework_id` (`homework_id`),
  KEY `student_id` (`student_id`),
  KEY `graded_by` (`graded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `homework_submissions` */

/*Table structure for table `impersonation_logs` */

DROP TABLE IF EXISTS `impersonation_logs`;

CREATE TABLE `impersonation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `super_admin_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `target_user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_imperson_superadmin` (`super_admin_id`),
  KEY `idx_imperson_tenant` (`tenant_id`),
  KEY `idx_imperson_dates` (`started_at`,`ended_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `impersonation_logs` */

/*Table structure for table `inquiries` */

DROP TABLE IF EXISTS `inquiries`;

CREATE TABLE `inquiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `inquiry_type` enum('inquiry','visitor','appointment','call_log','complaint') NOT NULL DEFAULT 'inquiry',
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `alt_phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(100) DEFAULT 'walk_in',
  `status` enum('pending','contacted','admitted','closed','follow_up','converted','open','in_progress','resolved') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `check_in_at` timestamp NULL DEFAULT NULL,
  `check_out_at` timestamp NULL DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `status` (`status`),
  KEY `idx_tenant_created_at` (`tenant_id`,`created_at`),
  KEY `idx_inquiries_deleted_tenant` (`tenant_id`,`deleted_at`),
  KEY `idx_inquiries_deleted` (`deleted_at`),
  KEY `idx_type_tenant_date` (`tenant_id`,`inquiry_type`,`created_at`),
  KEY `idx_inquiries_status_created` (`status`,`created_at`,`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `inquiries` */

insert  into `inquiries`(`id`,`tenant_id`,`inquiry_type`,`full_name`,`phone`,`alt_phone`,`email`,`course_id`,`source`,`status`,`notes`,`appointment_date`,`appointment_time`,`check_in_at`,`check_out_at`,`address`,`created_at`,`updated_at`,`deleted_at`) values 
(14,5,'inquiry','Dashboard Test Inquiry','9800000000',NULL,'test@example.com',NULL,'walk_in','pending',NULL,NULL,NULL,NULL,NULL,NULL,'2026-02-27 10:31:14','2026-03-02 18:09:10',NULL);

/*Table structure for table `inquiry_followups` */

DROP TABLE IF EXISTS `inquiry_followups`;

CREATE TABLE `inquiry_followups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inquiry_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'Staff who followed up',
  `remarks` text NOT NULL,
  `next_followup_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inquiry_id` (`inquiry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `inquiry_followups` */

/*Table structure for table `invoice_items` */

DROP TABLE IF EXISTS `invoice_items`;

CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `fee_record_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_fee_record_id_foreign` (`fee_record_id`),
  CONSTRAINT `invoice_items_fee_record_id_foreign` FOREIGN KEY (`fee_record_id`) REFERENCES `fee_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invoice_items` */

/*Table structure for table `invoices` */

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `fee_record_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NPR',
  `status` enum('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_invoice_tenant` (`tenant_id`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_invoice_status` (`status`),
  KEY `idx_invoice_dates` (`issue_date`,`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invoices` */

/*Table structure for table `job_queue` */

DROP TABLE IF EXISTS `job_queue`;

CREATE TABLE `job_queue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `job_type` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_tenant` (`status`,`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `job_queue` */

insert  into `job_queue`(`id`,`tenant_id`,`job_type`,`payload`,`status`,`attempts`,`error_message`,`created_at`,`updated_at`) values 
(1,5,'payment_receipt','{\"transaction_ids\":[\"46\"],\"receipt_no\":\"RCP-000049\",\"student_id\":53}','completed',1,NULL,'2026-03-06 14:14:11','2026-03-08 15:25:42'),
(2,5,'payment_receipt','{\"transaction_ids\":[\"47\"],\"receipt_no\":\"RCP-000050\",\"student_id\":54}','completed',1,NULL,'2026-03-06 16:10:07','2026-03-08 15:25:42'),
(3,5,'payment_receipt','{\"transaction_ids\":[\"48\"],\"receipt_no\":\"RCP-000051\",\"student_id\":54}','completed',1,NULL,'2026-03-06 16:28:09','2026-03-08 15:25:42'),
(4,5,'payment_receipt','{\"transaction_ids\":[\"49\"],\"receipt_no\":\"RCP-000052\",\"student_id\":54}','completed',1,NULL,'2026-03-06 16:39:25','2026-03-08 15:25:42'),
(5,5,'payment_receipt','{\"transaction_ids\":[\"50\"],\"receipt_no\":\"RCP-000053\",\"student_id\":54}','completed',1,NULL,'2026-03-06 16:41:48','2026-03-08 15:25:42'),
(6,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":54,\"recipient_email\":\"dhirendraparshad65@gmail.com\",\"recipient_name\":\"Devbarat Patel\",\"receipt_no\":\"RCP-000053\"}','completed',1,NULL,'2026-03-06 16:42:56','2026-03-06 19:40:34'),
(7,5,'payment_receipt','{\"transaction_ids\":[\"51\"],\"receipt_no\":\"RCP-000054\",\"student_id\":54}','completed',1,NULL,'2026-03-06 17:01:48','2026-03-08 15:25:42'),
(8,5,'payment_receipt','{\"transaction_ids\":[\"52\"],\"receipt_no\":\"RCP-000055\",\"student_id\":54}','completed',1,NULL,'2026-03-06 17:11:45','2026-03-08 15:25:42'),
(9,5,'payment_receipt','{\"transaction_ids\":[\"53\"],\"receipt_no\":\"RCP-000056\",\"student_id\":53}','completed',1,NULL,'2026-03-06 17:28:10','2026-03-08 15:25:42'),
(10,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":53,\"recipient_email\":\"addamssmith937@gmail.com\",\"recipient_name\":\"Devbarat Prasad Patel\",\"receipt_no\":\"RCP-000056\"}','completed',1,NULL,'2026-03-06 17:28:26','2026-03-06 19:40:34'),
(11,5,'payment_receipt','{\"transaction_ids\":[\"54\"],\"receipt_no\":\"RCP-000057\",\"student_id\":53}','completed',1,NULL,'2026-03-06 17:34:10','2026-03-08 15:25:42'),
(12,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":53,\"recipient_email\":\"addamssmith937@gmail.com\",\"recipient_name\":\"Devbarat Prasad Patel\",\"receipt_no\":\"RCP-000057\"}','completed',1,NULL,'2026-03-06 17:34:15','2026-03-06 19:40:34'),
(13,5,'payment_receipt','{\"transaction_ids\":[\"55\"],\"receipt_no\":\"RCP-000058\",\"student_id\":53}','completed',1,NULL,'2026-03-06 19:20:32','2026-03-08 15:25:42'),
(14,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":53,\"recipient_email\":\"addamssmith937@gmail.com\",\"recipient_name\":\"Devbarat Prasad Patel\",\"receipt_no\":\"RCP-000058\"}','completed',1,NULL,'2026-03-06 19:20:36','2026-03-06 19:40:34'),
(15,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":53,\"recipient_email\":\"addamssmith937@gmail.com\",\"recipient_name\":\"Devbarat Prasad Patel\",\"receipt_no\":\"RCP-000058\"}','completed',1,NULL,'2026-03-06 19:20:47','2026-03-06 19:40:34'),
(16,5,'payment_receipt','{\"transaction_ids\":[\"56\"],\"receipt_no\":\"RCP-000059\",\"student_id\":53}','completed',1,NULL,'2026-03-06 19:34:33','2026-03-08 15:25:42'),
(17,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":53,\"recipient_email\":\"addamssmith937@gmail.com\",\"recipient_name\":\"Devbarat Prasad Patel\",\"receipt_no\":\"RCP-000059\"}','completed',1,NULL,'2026-03-06 19:34:38','2026-03-06 19:40:34'),
(18,5,'payment_receipt','{\"transaction_ids\":[\"57\"],\"receipt_no\":\"RCP-000060\",\"student_id\":53}','completed',1,NULL,'2026-03-06 19:38:57','2026-03-08 15:25:42'),
(19,NULL,'send_email_receipt','{\"tenant_id\":5,\"student_id\":53,\"recipient_email\":\"addamssmith937@gmail.com\",\"recipient_name\":\"Devbarat Prasad Patel\",\"receipt_no\":\"RCP-000060\"}','completed',1,NULL,'2026-03-06 19:39:19','2026-03-06 19:40:34'),
(20,5,'payment_receipt','{\"transaction_id\":\"63\",\"receipt_no\":\"RCP-000066\",\"student_id\":53}','completed',1,NULL,'2026-03-06 21:21:25','2026-03-08 15:26:05'),
(21,5,'payment_receipt','{\"transaction_id\":\"64\",\"receipt_no\":\"RCP-000067\",\"student_id\":53}','completed',1,NULL,'2026-03-07 04:32:01','2026-03-08 15:26:28'),
(22,5,'password_reset','{\"email\":\"nepalcodingschool@gmail.com\",\"student_name\":\"Niki Yadav\",\"reset_token\":\"424648\",\"template_key\":\"password_reset_request\"}','completed',1,NULL,'2026-03-07 05:41:27','2026-03-08 15:14:32'),
(23,5,'password_reset','{\"email\":\"nepalcyberfirm@gmail.com\",\"student_name\":\"Priyanka Kumari Sah\",\"reset_token\":\"270129\",\"template_key\":\"password_reset_request\"}','completed',2,NULL,'2026-03-07 05:41:57','2026-03-08 15:14:39'),
(24,5,'password_reset','{\"email\":\"nepalcyberfirm@gmail.com\",\"student_name\":\"Priyanka Kumari Sah\",\"reset_token\":\"711913\",\"template_key\":\"password_reset_request\"}','completed',2,NULL,'2026-03-07 05:48:48','2026-03-08 15:14:44'),
(25,5,'password_reset','{\"email\":\"nepalcyberfirm@gmail.com\",\"student_name\":\"Priyanka Kumari Sah\",\"reset_token\":\"289207\",\"template_key\":\"password_reset_request\"}','completed',2,NULL,'2026-03-07 05:55:22','2026-03-08 15:14:49'),
(26,5,'password_reset','{\"email\":\"nepalcyberfirm@gmail.com\",\"student_name\":\"Priyanka Kumari Sah\",\"reset_token\":\"731045\",\"template_key\":\"password_reset_request\"}','completed',2,NULL,'2026-03-07 05:56:00','2026-03-08 15:14:54'),
(27,5,'student_welcome','{\"student_id\":78,\"full_name\":\"Nepal Cyber Firm\",\"email\":\"nepalcodingschool@gmail.com\",\"plain_password\":\"Nepal@123\",\"course_name\":\"Kharidar First Papper \",\"batch_name\":\"Kharidar First Papper 2082\",\"roll_no\":\"N\\/A\",\"admission_date\":\"2026-03-07\",\"login_url\":\"http:\\/\\/localhost\\/erp\\/login\"}','completed',2,NULL,'2026-03-07 06:01:23','2026-03-08 15:14:59'),
(28,5,'payment_receipt','{\"transaction_id\":64,\"receipt_no\":\"RCP-000067\",\"student_id\":53}','completed',1,NULL,'2026-03-08 12:52:26','2026-03-08 15:26:50'),
(29,5,'payment_receipt','{\"transaction_id\":\"65\",\"receipt_no\":\"RCP-000068\",\"student_id\":80}','completed',1,NULL,'2026-03-08 15:08:51','2026-03-08 15:27:12'),
(30,5,'payment_receipt','{\"transaction_id\":65,\"receipt_no\":\"RCP-000068\",\"student_id\":80}','completed',1,NULL,'2026-03-08 15:08:56','2026-03-08 15:27:34'),
(31,5,'payment_receipt','{\"transaction_id\":\"66\",\"receipt_no\":\"RCP-000069\",\"student_id\":78}','pending',0,NULL,'2026-03-08 15:26:28','2026-03-08 15:26:28'),
(32,5,'payment_receipt','{\"transaction_id\":66,\"receipt_no\":\"RCP-000069\",\"student_id\":78}','pending',0,NULL,'2026-03-08 15:26:34','2026-03-08 15:26:34');

/*Table structure for table `leave_requests` */

DROP TABLE IF EXISTS `leave_requests`;

CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_leave_status` (`tenant_id`,`status`),
  KEY `idx_student_leave_dates` (`student_id`,`from_date`,`to_date`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `leave_requests` */

/*Table structure for table `ledger_entries` */

DROP TABLE IF EXISTS `ledger_entries`;

CREATE TABLE `ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `reference_type` varchar(255) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('debit','credit') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ledger_entries_tenant_id_entry_date_index` (`tenant_id`,`entry_date`),
  KEY `ledger_entries_tenant_id_student_id_index` (`tenant_id`,`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `ledger_entries` */

insert  into `ledger_entries`(`id`,`tenant_id`,`student_id`,`reference_type`,`reference_id`,`amount`,`type`,`description`,`entry_date`,`created_at`,`updated_at`) values 
(1,5,54,'payment',48,10.00,'credit','Bulk Fee Payment - Receipt #RCP-000051','2026-03-06','2026-03-06 16:28:09','2026-03-06 16:28:09'),
(2,5,54,'payment',49,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000052','2026-03-06','2026-03-06 16:39:25','2026-03-06 16:39:25'),
(3,5,54,'payment',50,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000053','2026-03-06','2026-03-06 16:41:48','2026-03-06 16:41:48'),
(4,5,54,'payment',51,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000054','2026-03-06','2026-03-06 17:01:48','2026-03-06 17:01:48'),
(5,5,54,'payment',52,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000055','2026-03-06','2026-03-06 17:11:45','2026-03-06 17:11:45'),
(6,5,53,'payment',53,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000056','2026-03-06','2026-03-06 17:28:10','2026-03-06 17:28:10'),
(7,5,53,'payment',54,900.00,'credit','Bulk Fee Payment - Receipt #RCP-000057','2026-03-06','2026-03-06 17:34:10','2026-03-06 17:34:10'),
(8,5,53,'payment',55,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000058','2026-03-06','2026-03-06 19:20:32','2026-03-06 19:20:32'),
(9,5,53,'payment',56,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000059','2026-03-06','2026-03-06 19:34:33','2026-03-06 19:34:33'),
(10,5,53,'payment',57,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000060','2026-03-06','2026-03-06 19:38:57','2026-03-06 19:38:57'),
(11,5,53,'payment',58,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000061','2026-03-06','2026-03-06 19:44:10','2026-03-06 19:44:10'),
(12,5,53,'payment',59,600.00,'credit','Bulk Fee Payment - Receipt #RCP-000062','2026-03-06','2026-03-06 19:45:11','2026-03-06 19:45:11'),
(13,5,53,'payment',60,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000063','2026-03-06','2026-03-06 19:49:56','2026-03-06 19:49:56'),
(14,5,53,'payment',61,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000064','2026-03-06','2026-03-06 20:56:17','2026-03-06 20:56:17'),
(15,5,53,'payment',62,100.00,'credit','Bulk Fee Payment - Receipt #RCP-000065','2026-03-06','2026-03-06 20:57:53','2026-03-06 20:57:53'),
(16,5,53,'payment',63,50.00,'credit','Bulk Fee Payment - Receipt #RCP-000066','2026-03-06','2026-03-06 21:21:25','2026-03-06 21:21:25'),
(17,5,53,'payment',64,50.00,'credit','Bulk Fee Payment - Receipt #RCP-000067','2026-03-06','2026-03-07 04:32:01','2026-03-07 04:32:01'),
(18,5,80,'payment',65,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000068','2026-03-08','2026-03-08 15:08:51','2026-03-08 15:08:51'),
(19,5,78,'payment',66,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000069','2026-03-08','2026-03-08 15:26:28','2026-03-08 15:26:28');

/*Table structure for table `library_books` */

DROP TABLE IF EXISTS `library_books`;

CREATE TABLE `library_books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `accession_no` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `subject` varchar(150) DEFAULT NULL,
  `total_copies` smallint(5) unsigned NOT NULL DEFAULT 1,
  `available_copies` smallint(5) unsigned NOT NULL DEFAULT 1,
  `shelf_location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_accession_tenant` (`accession_no`,`tenant_id`),
  KEY `fk_lb_tenant` (`tenant_id`),
  CONSTRAINT `fk_lb_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `library_books` */

/*Table structure for table `library_issues` */

DROP TABLE IF EXISTS `library_issues`;

CREATE TABLE `library_issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `book_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `issued_by` bigint(20) unsigned NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `condition_on_issue` varchar(100) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `condition_on_return` varchar(100) DEFAULT NULL,
  `fine_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_li_tenant` (`tenant_id`),
  KEY `fk_li_book` (`book_id`),
  KEY `fk_li_student` (`student_id`),
  KEY `fk_li_issuer` (`issued_by`),
  CONSTRAINT `fk_li_book` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_li_issuer` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_li_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_li_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `library_issues` */

/*Table structure for table `login_attempts` */

DROP TABLE IF EXISTS `login_attempts`;

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `login_attempts` */

insert  into `login_attempts`(`id`,`email`,`ip_address`,`status`,`created_at`) values 
(1,NULL,NULL,'success','2026-02-23 04:36:53'),
(2,NULL,NULL,'success','2026-02-22 05:36:53'),
(3,NULL,NULL,'success','2026-02-22 05:36:53'),
(4,NULL,NULL,'success','2026-02-23 01:36:53'),
(5,NULL,NULL,'success','2026-02-21 23:36:53'),
(6,NULL,NULL,'failed','2026-02-23 14:36:53'),
(7,NULL,NULL,'success','2026-02-22 20:36:53'),
(8,NULL,NULL,'success','2026-02-22 17:36:53'),
(9,NULL,NULL,'failed','2026-02-21 21:36:53'),
(10,NULL,NULL,'success','2026-02-23 07:36:53'),
(11,NULL,NULL,'success','2026-02-23 03:36:53'),
(12,NULL,NULL,'success','2026-02-21 16:36:53'),
(13,NULL,NULL,'failed','2026-02-22 08:36:53'),
(14,NULL,NULL,'success','2026-02-22 14:36:53'),
(15,NULL,NULL,'success','2026-02-23 04:36:53'),
(16,NULL,NULL,'success','2026-02-21 23:36:53'),
(17,NULL,NULL,'success','2026-02-21 15:36:53'),
(18,NULL,NULL,'failed','2026-02-21 17:36:53'),
(19,NULL,NULL,'success','2026-02-23 04:36:53'),
(20,NULL,NULL,'success','2026-02-23 10:36:53'),
(21,NULL,NULL,'success','2026-02-23 13:36:53'),
(22,NULL,NULL,'success','2026-02-22 07:36:53'),
(23,NULL,NULL,'failed','2026-02-22 23:36:53'),
(24,NULL,NULL,'success','2026-02-22 09:36:53'),
(25,NULL,NULL,'failed','2026-02-22 12:36:53'),
(26,NULL,NULL,'failed','2026-02-21 18:36:53'),
(27,NULL,NULL,'failed','2026-02-22 10:36:53'),
(28,NULL,NULL,'failed','2026-02-22 05:36:53'),
(29,NULL,NULL,'success','2026-02-22 18:36:53'),
(30,NULL,NULL,'success','2026-02-23 09:36:53'),
(31,NULL,NULL,'success','2026-02-21 21:36:53'),
(32,NULL,NULL,'success','2026-02-21 21:36:53'),
(33,NULL,NULL,'failed','2026-02-22 10:36:53'),
(34,NULL,NULL,'success','2026-02-22 02:36:53'),
(35,NULL,NULL,'success','2026-02-21 23:36:53'),
(36,NULL,NULL,'success','2026-02-23 12:36:53'),
(37,NULL,NULL,'success','2026-02-21 18:36:53'),
(38,NULL,NULL,'success','2026-02-22 00:36:53'),
(39,NULL,NULL,'success','2026-02-22 20:36:53'),
(40,NULL,NULL,'success','2026-02-22 20:36:53'),
(41,NULL,NULL,'failed','2026-02-23 10:36:53'),
(42,NULL,NULL,'failed','2026-02-22 23:36:53'),
(43,NULL,NULL,'success','2026-02-22 09:36:53'),
(44,NULL,NULL,'success','2026-02-22 20:36:53'),
(45,NULL,NULL,'failed','2026-02-23 06:36:53'),
(46,NULL,NULL,'success','2026-02-22 03:36:53'),
(47,NULL,NULL,'success','2026-02-22 16:36:53'),
(48,NULL,NULL,'success','2026-02-22 06:36:53'),
(49,NULL,NULL,'success','2026-02-21 21:36:53'),
(50,NULL,NULL,'success','2026-02-21 16:36:53');

/*Table structure for table `mail_logs` */

DROP TABLE IF EXISTS `mail_logs`;

CREATE TABLE `mail_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT 0,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `mail_logs` */

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'2026_02_22_202142_create_notify_sup_admin_table',1),
(2,'2026_02_23_000001_create_dashboard_missing_tables',2),
(3,'2026_02_24_190000_create_tenant_email_settings_table',3),
(4,'2026_02_26_210000_create_attendance_tables',4),
(5,'2026_02_27_000000_fee_module_enhancements',4),
(6,'2026_02_27_031000_create_workflow_checklists_table',4),
(7,'2026_02_27_091419_add_discount_amount_to_fee_records',5),
(8,'2026_02_27_094500_create_dashboard_checklists_and_indexes',6),
(9,'2026_03_05_175529_add_indexes_for_caching',7),
(10,'2026_03_06_161943_create_ledger_entries_table',7),
(11,'2026_03_08_082236_create_feedbacks_table',8);

/*Table structure for table `monthly_targets` */

DROP TABLE IF EXISTS `monthly_targets`;

CREATE TABLE `monthly_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `fee_collection_target` decimal(12,2) NOT NULL DEFAULT 0.00,
  `enrollment_target` decimal(10,0) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant_target` (`tenant_id`,`year`,`month`),
  KEY `idx_tenant_year_month` (`tenant_id`,`year`,`month`),
  CONSTRAINT `monthly_targets_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `monthly_targets` */

/*Table structure for table `notice_reads` */

DROP TABLE IF EXISTS `notice_reads`;

CREATE TABLE `notice_reads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notice_id` bigint(20) unsigned NOT NULL,
  `student_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notice_id` (`notice_id`),
  KEY `student_id` (`student_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notice_reads_ibfk_1` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `notice_reads` */

/*Table structure for table `notices` */

DROP TABLE IF EXISTS `notices`;

CREATE TABLE `notices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `notice_type` enum('announcement','event','holiday','fee','exam','admission','other') DEFAULT 'announcement',
  `category` enum('general','academic','finance','system') DEFAULT 'general',
  `priority` enum('low','normal','high','critical') DEFAULT 'normal',
  `target_type` enum('all','batch','student','staff','role') DEFAULT 'all',
  `target_id` int(10) unsigned DEFAULT NULL COMMENT 'ID of batch/student if targeted',
  `display_from` datetime DEFAULT current_timestamp(),
  `display_until` datetime DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('active','draft','expired','deleted') DEFAULT 'active',
  `created_by` int(10) unsigned NOT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `status` (`status`),
  KEY `target_type` (`target_type`,`target_id`),
  KEY `display_from` (`display_from`,`display_until`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `notices` */

insert  into `notices`(`id`,`tenant_id`,`title`,`content`,`notice_type`,`category`,`priority`,`target_type`,`target_id`,`display_from`,`display_until`,`attachment_path`,`status`,`created_by`,`updated_by`,`created_at`,`updated_at`,`deleted_at`) values 
(1,1,'Welcome to Dashboard V2','We have upgraded our institute dashboard for better performance.','announcement','general','high','all',NULL,'2026-03-03 13:40:23',NULL,NULL,'active',1,NULL,'2026-03-03 13:40:23','2026-03-03 13:40:23',NULL),
(2,1,'Upcoming Exam Schedule','Final exams start from next Monday. Please check timetable.','exam','general','high','all',NULL,'2026-03-03 13:40:23',NULL,NULL,'active',1,NULL,'2026-03-03 13:40:23','2026-03-03 13:40:23',NULL);

/*Table structure for table `notification_automation_rules` */

DROP TABLE IF EXISTS `notification_automation_rules`;

CREATE TABLE `notification_automation_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `trigger_type` enum('absent','fee_due') NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `message_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_trigger` (`tenant_id`,`trigger_type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `notification_automation_rules` */

insert  into `notification_automation_rules`(`id`,`tenant_id`,`name`,`trigger_type`,`conditions`,`message_template`,`is_active`,`created_at`,`updated_at`) values 
(1,1,'Test Absence Rule','absent','[]','TEST: {student_name} is absent on {date}.',1,'2026-03-08 08:41:30','2026-03-08 08:41:30');

/*Table structure for table `notifications` */

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  KEY `idx_notifications_created_at` (`created_at`),
  KEY `idx_notifications_tenant_id` (`tenant_id`),
  CONSTRAINT `fk_notif_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `notifications` */

/*Table structure for table `notify_sup_admin` */

DROP TABLE IF EXISTS `notify_sup_admin`;

CREATE TABLE `notify_sup_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `notify_sup_admin` */

insert  into `notify_sup_admin`(`id`,`type`,`title`,`message`,`link`,`is_read`,`created_at`,`updated_at`) values 
(1,'security','Suspicious Login Attempt','Multiple failed logins detected from IP 192.168.1.104.','/dash/super-admin/logs?type=security',0,'2026-02-22 20:09:49','2026-02-22 20:24:49'),
(2,'signup','New Institute Registered','Bright Future Academy has signed up for the Professional plan.','/dash/super-admin/tenant-management',0,'2026-02-22 18:24:49','2026-02-22 20:24:49'),
(3,'alert','High Server Memory Usage','Redis memory hit 85% capacity.','/dash/super-admin/index',0,'2026-02-22 15:24:49','2026-02-22 20:24:49'),
(4,'payment','Payment Received','Received NPR 15,000 from Mount Everest College.','/dash/super-admin/revenue-analytics',1,'2026-02-21 20:24:49','2026-02-22 20:24:49');

/*Table structure for table `password_resets` */

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pwd_reset_email` (`email`),
  KEY `idx_pwd_reset_token` (`token`),
  KEY `fk_pwd_reset_tenant` (`tenant_id`),
  KEY `fk_pwd_reset_user` (`user_id`),
  CONSTRAINT `fk_pwd_reset_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pwd_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `password_resets` */

insert  into `password_resets`(`id`,`tenant_id`,`user_id`,`role`,`email`,`token`,`created_at`,`expires_at`) values 
(9,5,38,'instituteadmin','nepalcyberfirm@gmail.com','815275','2026-03-01 08:35:00','2026-03-01 09:05:00'),
(10,5,38,'instituteadmin','nepalcyberfirm@gmail.com','76a8ff67c96d67c099ad8ff6cbb8d53701b94ae6bff9d96ddbb2b84739e356e0','2026-03-01 08:36:36','2026-03-01 08:57:21'),
(12,5,92,'student','nepalcodingschool@gmail.com','424648','2026-03-07 05:41:25','2026-03-07 06:11:25'),
(13,5,38,'instituteadmin','nepalcyberfirm@gmail.com','270129','2026-03-07 05:41:57','2026-03-07 06:11:57'),
(14,5,38,'instituteadmin','nepalcyberfirm@gmail.com','711913','2026-03-07 05:48:47','2026-03-07 06:18:47'),
(15,5,38,'instituteadmin','nepalcyberfirm@gmail.com','289207','2026-03-07 05:55:21','2026-03-07 06:25:21'),
(16,5,38,'instituteadmin','nepalcyberfirm@gmail.com','731045','2026-03-07 05:56:00','2026-03-07 06:26:00'),
(17,5,38,'instituteadmin','nepalcyberfirm@gmail.com','97387d8f15b995fa62d176e7e970edc2f7d6cebe06be7939c1a2fc50c6f469b5','2026-03-07 06:02:46','2026-03-07 06:25:08'),
(18,5,92,'student','nepalcodingschool@gmail.com','e7d9fec3817b6d10da256080c8da1a4318e43814f0cb915e6e55edc47ac23a12','2026-03-07 06:08:24','2026-03-07 06:28:45');

/*Table structure for table `payment_receipts` */

DROP TABLE IF EXISTS `payment_receipts`;

CREATE TABLE `payment_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `pdf_path` varchar(255) NOT NULL,
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `payment_receipts` */

/*Table structure for table `payment_transactions` */

DROP TABLE IF EXISTS `payment_transactions`;

CREATE TABLE `payment_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `fee_record_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','cheque','esewa','khalti','card') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pt_tenant` (`tenant_id`),
  KEY `idx_pt_student` (`student_id`),
  KEY `idx_pt_receipt` (`receipt_number`),
  KEY `idx_pt_date` (`payment_date`),
  KEY `idx_pt_status` (`status`),
  KEY `payment_transactions_fee_record_id_foreign` (`fee_record_id`),
  KEY `payment_transactions_recorded_by_foreign` (`recorded_by`),
  KEY `idx_pt_date_method_tenant` (`tenant_id`,`payment_date` DESC,`payment_method`),
  CONSTRAINT `payment_transactions_fee_record_id_foreign` FOREIGN KEY (`fee_record_id`) REFERENCES `fee_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_transactions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `payment_transactions` */

insert  into `payment_transactions`(`id`,`tenant_id`,`student_id`,`fee_record_id`,`invoice_id`,`amount`,`payment_method`,`transaction_id`,`receipt_number`,`payment_date`,`receipt_path`,`recorded_by`,`notes`,`status`,`created_at`,`updated_at`) values 
(25,5,53,20,NULL,1000.00,'cash',NULL,'RCP-000025','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(26,5,53,20,NULL,1000.00,'cash',NULL,'RCP-000026','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(27,5,53,20,NULL,1000.00,'cash',NULL,'RCP-000027','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(28,5,54,21,NULL,1000.00,'cash',NULL,'RCP-000028','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(29,5,55,22,NULL,1000.00,'cash',NULL,'RCP-000029','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(30,5,54,21,NULL,1000.00,'cash',NULL,'RCP-000030','2026-03-01',NULL,38,'','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(31,5,54,21,NULL,500.00,'esewa',NULL,'RCP-000031','2026-03-01',NULL,38,'In Hand Cash  (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(32,5,55,22,NULL,500.00,'cash',NULL,'RCP-000032','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(33,5,55,22,NULL,5500.00,'cash',NULL,'RCP-000033','2026-03-01',NULL,38,'','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(34,5,54,21,NULL,45.00,'cash',NULL,'RCP-000034','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(35,5,54,21,NULL,45.00,'cash',NULL,'RCP-000035','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(36,5,54,21,NULL,400.00,'cash',NULL,'RCP-000036','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(37,5,54,21,NULL,1000.00,'esewa',NULL,'RCP-000037','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(38,5,54,21,NULL,300.00,'cash',NULL,'RCP-000038','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(39,5,54,21,NULL,100.00,'cash',NULL,'RCP-000039','2026-03-01',NULL,38,' (Bulk Payment Part)','completed','2026-03-02 18:09:10','2026-03-02 18:09:10'),
(40,5,62,26,NULL,5000.00,'cash',NULL,'RCP-000043','2026-03-02',NULL,1,'Test Payment (Bulk Payment Part)','completed','2026-03-02 19:03:51','2026-03-02 19:03:51'),
(41,5,54,21,NULL,610.00,'cash',NULL,'RCP-000044','2026-03-04',NULL,38,' (Bulk Payment Part)','completed','2026-03-04 08:45:37','2026-03-04 08:45:37'),
(42,5,54,21,NULL,610.00,'cash',NULL,'RCP-000045','2026-03-04',NULL,38,' (Bulk Payment Part)','completed','2026-03-04 08:46:27','2026-03-04 08:46:27'),
(43,5,72,28,NULL,5390.00,'cash',NULL,'RCP-000046','2026-03-05',NULL,38,' (Bulk Payment Part)','completed','2026-03-05 18:08:36','2026-03-05 18:08:36'),
(44,5,54,21,NULL,390.00,'cash',NULL,'RCP-000047','2026-03-05',NULL,38,' (Bulk Payment Part)','completed','2026-03-05 18:12:15','2026-03-05 18:12:15'),
(45,5,54,21,NULL,390.00,'cash',NULL,'RCP-000048','2026-03-05',NULL,38,' (Bulk Payment Part)','completed','2026-03-05 18:12:55','2026-03-05 18:12:55'),
(46,5,53,20,NULL,1000.00,'cash',NULL,'RCP-000049','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 14:14:11','2026-03-06 14:14:11'),
(47,5,54,21,NULL,100.00,'cash',NULL,'RCP-000050','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 16:10:07','2026-03-06 16:10:07'),
(48,5,54,21,NULL,10.00,'cash',NULL,'RCP-000051','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 16:28:09','2026-03-06 16:28:09'),
(49,5,54,21,NULL,100.00,'cash',NULL,'RCP-000052','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 16:39:25','2026-03-06 16:39:25'),
(50,5,54,21,NULL,100.00,'cash',NULL,'RCP-000053','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 16:41:48','2026-03-06 16:41:48'),
(51,5,54,21,NULL,100.00,'cash',NULL,'RCP-000054','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 17:01:48','2026-03-06 17:01:48'),
(52,5,54,21,NULL,100.00,'cash',NULL,'RCP-000055','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 17:11:45','2026-03-06 17:11:45'),
(53,5,53,20,NULL,100.00,'cash',NULL,'RCP-000056','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 17:28:10','2026-03-06 17:28:10'),
(54,5,53,20,NULL,900.00,'cash',NULL,'RCP-000057','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 17:34:10','2026-03-06 17:34:10'),
(55,5,53,20,NULL,100.00,'cash',NULL,'RCP-000058','2026-03-06','public/uploads/receipts/receipt_RCP-000058.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 19:20:32','2026-03-06 19:21:51'),
(56,5,53,20,NULL,100.00,'cash',NULL,'RCP-000059','2026-03-06',NULL,38,' (Bulk Payment Part)','completed','2026-03-06 19:34:33','2026-03-06 19:34:33'),
(57,5,53,20,NULL,100.00,'cash',NULL,'RCP-000060','2026-03-06','public/uploads/receipts/receipt_RCP-000060.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 19:38:57','2026-03-06 19:39:11'),
(58,5,53,20,NULL,100.00,'cash',NULL,'RCP-000061','2026-03-06','public/uploads/receipts/receipt_RCP-000061.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 19:44:10','2026-03-06 19:44:13'),
(59,5,53,20,NULL,600.00,'cash',NULL,'RCP-000062','2026-03-06','public/uploads/receipts/receipt_RCP-000062.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 19:45:11','2026-03-06 19:45:14'),
(60,5,53,20,NULL,100.00,'cash',NULL,'RCP-000063','2026-03-06','public/uploads/receipts/receipt_RCP-000063.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 19:49:56','2026-03-06 19:50:00'),
(61,5,53,20,NULL,100.00,'cash',NULL,'RCP-000064','2026-03-06','public/uploads/receipts/receipt_RCP-000064.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 20:56:17','2026-03-06 20:56:20'),
(62,5,53,20,NULL,100.00,'cash',NULL,'RCP-000065','2026-03-06','public/uploads/receipts/receipt_RCP-000065.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 20:57:53','2026-03-06 20:57:56'),
(63,5,53,20,NULL,50.00,'cash',NULL,'RCP-000066','2026-03-06','uploads/receipts/receipt_RCP-000066.pdf',38,' (Bulk Payment Part)','completed','2026-03-06 21:21:25','2026-03-08 15:12:42'),
(64,5,53,20,NULL,50.00,'cash',NULL,'RCP-000067','2026-03-06','uploads/receipts/receipt_RCP-000067.pdf',38,' (Bulk Payment Part)','completed','2026-03-07 04:32:01','2026-03-08 15:14:07'),
(65,5,80,36,NULL,1000.00,'cash',NULL,'RCP-000068','2026-03-08','uploads/receipts/receipt_RCP-000068.pdf',38,' (Bulk Payment Part)','completed','2026-03-08 15:08:51','2026-03-08 15:15:20'),
(66,5,78,33,NULL,1000.00,'cash',NULL,'RCP-000069','2026-03-08','public/uploads/receipts/receipt_RCP-000069.pdf',38,' (Bulk Payment Part)','completed','2026-03-08 15:26:28','2026-03-08 15:28:02');

/*Table structure for table `payments` */

DROP TABLE IF EXISTS `payments`;

CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned DEFAULT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `fee_record_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'NPR',
  `payment_method` enum('esewa','khalti','bank_transfer','cash','cheque','card') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_date` date NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pay_tenant` (`tenant_id`),
  KEY `idx_pay_subscription` (`subscription_id`),
  KEY `idx_pay_status` (`status`),
  KEY `idx_pay_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `payments` */

/*Table structure for table `plan_features` */

DROP TABLE IF EXISTS `plan_features`;

CREATE TABLE `plan_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feature_key` varchar(100) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_global` tinyint(1) NOT NULL DEFAULT 0,
  `plans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`plans`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_key` (`feature_key`),
  KEY `idx_feature_key` (`feature_key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `plan_features` */

insert  into `plan_features`(`id`,`feature_key`,`feature_name`,`description`,`is_enabled`,`is_global`,`plans`,`created_at`,`updated_at`) values 
(1,'sms','SMS Notifications','Send SMS notifications to students/parents',1,1,NULL,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(2,'attendance','Attendance Management','Track student attendance',1,1,NULL,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(3,'assignments','Assignments','Create and manage assignments',1,1,NULL,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(4,'timetable','Timetable','Class scheduling and timetable',1,1,NULL,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(5,'fees','Fee Management','Manage fees and payments',1,1,NULL,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(6,'exam','Examination','Exam schedules and results',1,1,NULL,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(7,'library','Library Management','Book inventory and issue tracking',1,0,'[\"growth\",\"professional\",\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31'),
(8,'transport','Transport Management','Bus route and student tracking',1,0,'[\"professional\",\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31'),
(9,'hostel','Hostel Management','Hostel rooms and allotments',1,0,'[\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31'),
(10,'custom_reports','Custom Reports','Generate custom reports',1,0,'[\"professional\",\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31'),
(11,'api_access','API Access','REST API access for integrations',1,0,'[\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31'),
(12,'white_label','White Label','Custom branding and domain',1,0,'[\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31'),
(13,'priority_support','Priority Support','24/7 priority support',1,0,'[\"enterprise\"]','2026-02-22 10:11:31','2026-02-22 10:11:31');

/*Table structure for table `platform_payments` */

DROP TABLE IF EXISTS `platform_payments`;

CREATE TABLE `platform_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pg','py','pr') DEFAULT 'pg' COMMENT 'pg=paid, py=pending, pr=overdue',
  `payment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `platform_payments` */

/*Table structure for table `platform_settings` */

DROP TABLE IF EXISTS `platform_settings`;

CREATE TABLE `platform_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `platform_settings` */

insert  into `platform_settings`(`id`,`setting_key`,`setting_value`,`setting_type`,`description`,`is_public`,`updated_at`) values 
(1,'platform_name','Hamro Labs ERP','string','Platform display name',1,'2026-02-22 10:11:31'),
(2,'platform_logo','/logo.png','string','Platform logo URL',1,'2026-02-22 10:11:31'),
(3,'platform_primary_color','#009E7E','string','Primary brand color',1,'2026-02-22 10:11:31'),
(4,'maintenance_mode','0','boolean','Enable platform maintenance mode',0,'2026-02-22 10:11:31'),
(5,'sms_credit_warning_threshold','20','number','Percentage threshold for SMS credit warnings',0,'2026-02-22 10:11:31'),
(6,'default_student_limit','100','number','Default student limit for new tenants',0,'2026-02-22 10:11:31'),
(7,'default_sms_credits','500','number','Default SMS credits for new tenants',0,'2026-02-22 10:11:31'),
(8,'support_email','support@hamrolabs.com','string','Platform support email',1,'2026-02-22 10:11:31'),
(9,'pricing_starter','1500','number','Starter plan monthly price (NPR)',0,'2026-02-22 10:11:31'),
(10,'pricing_growth','3500','number','Growth plan monthly price (NPR)',0,'2026-02-22 10:11:31'),
(11,'pricing_professional','12000','number','Professional plan monthly price (NPR)',0,'2026-02-22 10:11:31'),
(12,'pricing_enterprise','25000','number','Enterprise plan monthly price (NPR)',0,'2026-02-22 10:11:31');

/*Table structure for table `questions` */

DROP TABLE IF EXISTS `questions`;

CREATE TABLE `questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(150) NOT NULL,
  `topic` varchar(150) DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `question_text` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_option` tinyint(3) unsigned NOT NULL,
  `explanation` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `status` enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_questions_tenant` (`tenant_id`),
  KEY `fk_questions_teacher` (`teacher_id`),
  KEY `fk_questions_approver` (`approved_by`),
  CONSTRAINT `fk_questions_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_questions_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_questions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `questions` */

/*Table structure for table `remember_tokens` */

DROP TABLE IF EXISTS `remember_tokens`;

CREATE TABLE `remember_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_remember_user` (`user_id`),
  KEY `idx_remember_token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `remember_tokens` */

insert  into `remember_tokens`(`id`,`user_id`,`token`,`expires_at`,`created_at`) values 
(1,1,'36e6e9c5c6edea85f25e50231da52b7f83f0d70139153b9dd7b545546016d336','2026-03-25 11:17:54','2026-02-23 11:17:54'),
(2,31,'8c0bbfef01084961c1eb8dc3788b7d9cad2bdced609918e5a9dea3a14bad5791','2026-03-25 14:07:13','2026-02-23 14:07:13'),
(3,31,'252dcc0d0347a88ab48050327da7942766636a0ec844cc7e92f46f01d7bce013','2026-03-25 14:11:45','2026-02-23 14:11:45'),
(4,40,'92d0d349ce749bb82d17035ffe1a8333d062f0d1418094d2d28103a6946342e0','2026-03-25 15:51:19','2026-02-23 15:51:19'),
(5,42,'ba5946b3b106039bf91de8597ac161c5bb678881095c726778f8b591c3f97777','2026-03-25 16:04:13','2026-02-23 16:04:13'),
(6,56,'3ca0fadfa028c16799bf3ceeb52754539d17d9ea1c1e3b74fbcf02eab4d2bfe7','2026-03-27 10:22:59','2026-02-25 10:22:59'),
(7,38,'3775e4bcfc64c2a01380a6c52ebd2a529091dcbd8561004af5b83887c44effe2','2026-03-27 18:44:18','2026-02-25 18:44:18'),
(8,50,'fa2b94960361c1b2e1abe81fe70a6a014b2323249b1972a3d398480890eb3a9f','2026-03-28 16:34:35','2026-02-26 16:34:35'),
(9,38,'1681f2bd773fc21639890ba900efafd7fb38c5732181a4ad6213a03ec085b58a','2026-03-29 04:10:57','2026-02-27 04:10:57'),
(10,38,'9199a7f344825ed2cc98baa858a7ae31ac8623496ccb5b18fc0ad36c98bfc534','2026-03-29 04:27:01','2026-02-27 04:27:01'),
(11,38,'c1271a59122063b77b44297df519cfe73754bd712eb1352e29536e45bbafefd2','2026-03-29 07:30:16','2026-02-27 07:30:16'),
(12,38,'7975dee4b14f116ca5e44eadb20af24c3becfba7225ee9cd7b0e9d6f2210a41c','2026-03-29 08:54:34','2026-02-27 08:54:34'),
(13,38,'98814e6f470f7f82e7f65e1688cbc693ae8ae260f23ec5991cd156ae893a8f54','2026-03-29 13:02:27','2026-02-27 13:02:27'),
(14,38,'a0aa01bdf9d148ed8899b18fb351a47033226665393e5bdb9ba5a154319aecc8','2026-03-29 13:33:04','2026-02-27 13:33:04'),
(15,38,'b1f81b08d46e37b360c191e3970ebccb0d928a754abc36af43e699f295f6e823','2026-03-29 16:24:10','2026-02-27 16:24:10'),
(16,38,'51e15ec4dc83bb9914cdcd0a9c21df42701d70187bcc8986b5d25b48fc499a24','2026-03-29 17:19:20','2026-02-27 17:19:20'),
(17,38,'943141ae4001dfb4528667cc4f5c8995825ab988b34ed697ef383405c07de33f','2026-03-31 11:35:46','2026-03-01 11:35:46'),
(18,85,'5127af2a9b0178677d151bcae3e0fad05284e57b3ceba963d5a124d3924e70a6','2026-04-01 17:55:50','2026-03-02 17:55:50'),
(19,85,'21ccbd11d08d05f89f9313a3f79e1f6b578f89d5a5115c319d3698a8af18de82','2026-04-02 06:10:11','2026-03-03 06:10:11'),
(20,85,'587e7ca1f5e8fbcdd088430abb3127b32043f634c4b2b15850a5f6a2578688e1','2026-04-04 17:33:27','2026-03-05 17:33:27'),
(21,1,'8519b08f0d1f36fafcbbda9c05c9819388eb44cb0e0c228c90142b84b8003f78','2026-04-07 13:19:08','2026-03-08 13:19:08');

/*Table structure for table `sms_logs` */

DROP TABLE IF EXISTS `sms_logs`;

CREATE TABLE `sms_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `template_key` varchar(100) DEFAULT NULL,
  `gateway` enum('sparrow','aakash') NOT NULL,
  `status` enum('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued',
  `gateway_msg_id` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sms_tenant_status` (`tenant_id`,`status`),
  CONSTRAINT `fk_sms_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sms_logs` */

/*Table structure for table `sms_templates` */

DROP TABLE IF EXISTS `sms_templates`;

CREATE TABLE `sms_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_sms_slug` (`slug`),
  KEY `idx_sms_default` (`is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sms_templates` */

insert  into `sms_templates`(`id`,`name`,`slug`,`content`,`variables`,`is_default`,`is_active`,`created_at`,`updated_at`) values 
(1,'Welcome Message','welcome','Welcome to {{institute_name}}! Your account has been created. Login with: {{username}} / {{password}}',NULL,1,1,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(2,'Fee Reminder','fee_reminder','Dear {{parent_name}}, kindly pay {{fee_amount}} for {{student_name}} before {{due_date}}. - {{institute_name}}',NULL,0,1,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(3,'Attendance Alert','attendance_alert','Alert: {{student_name}} was absent on {{date}} for {{subject}}. - {{institute_name}}',NULL,0,1,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(4,'Exam Notification','exam_notification','Exam schedule: {{exam_name}} on {{exam_date}} at {{exam_time}}. Room: {{room}}. - {{institute_name}}',NULL,0,1,'2026-02-22 10:11:31','2026-02-22 10:11:31'),
(5,'Result Published','result_published','{{student_name}}, your {{exam_name}} results are now available. Grade: {{grade}}. - {{institute_name}}',NULL,0,1,'2026-02-22 10:11:31','2026-02-22 10:11:31');

/*Table structure for table `staff_attendance` */

DROP TABLE IF EXISTS `staff_attendance`;

CREATE TABLE `staff_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `marked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_staff_attendance` (`teacher_id`,`date`),
  KEY `staff_attendance_tenant_id_date_index` (`tenant_id`,`date`),
  KEY `staff_attendance_teacher_id_date_index` (`teacher_id`,`date`),
  CONSTRAINT `staff_attendance_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_attendance_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `staff_attendance` */

/*Table structure for table `staff_salaries` */

DROP TABLE IF EXISTS `staff_salaries`;

CREATE TABLE `staff_salaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `month` tinyint(2) NOT NULL,
  `year` smallint(4) NOT NULL,
  `payment_date` date NOT NULL,
  `status` enum('paid','pending') NOT NULL DEFAULT 'paid',
  `payment_method` enum('cash','bank_transfer','cheque','esewa','khalti') DEFAULT 'cash',
  `transaction_id` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ss_tenant` (`tenant_id`),
  KEY `idx_ss_user` (`user_id`),
  CONSTRAINT `fk_ss_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ss_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `staff_salaries` */

/*Table structure for table `student_fee_summary` */

DROP TABLE IF EXISTS `student_fee_summary`;

CREATE TABLE `student_fee_summary` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `total_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fee_status` enum('paid','unpaid','partial','overdue','no_fees') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fee_summary_tenant` (`tenant_id`),
  KEY `fk_fee_summary_student` (`student_id`),
  KEY `fk_fee_summary_enrollment` (`enrollment_id`),
  KEY `idx_student_fee_summary_lookup` (`student_id`,`tenant_id`,`fee_status`),
  CONSTRAINT `fk_fee_summary_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_summary_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_summary_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `student_fee_summary` */

insert  into `student_fee_summary`(`id`,`tenant_id`,`student_id`,`enrollment_id`,`total_fee`,`paid_amount`,`due_amount`,`fee_status`,`created_at`,`updated_at`) values 
(26,5,53,30,7000.00,6400.00,600.00,'partial','2026-03-01 09:25:19','2026-03-07 04:32:01'),
(27,5,54,31,7000.00,6900.00,100.00,'paid','2026-03-01 11:01:18','2026-03-06 17:11:45'),
(28,5,55,32,7000.00,7000.00,0.00,'paid','2026-03-01 11:40:25','2026-03-01 12:15:59'),
(29,5,65,33,8000.00,0.00,8000.00,'unpaid','2026-03-04 09:20:48','2026-03-04 09:20:48'),
(30,5,72,37,8000.00,5390.00,2610.00,'paid','2026-03-05 17:24:40','2026-03-05 18:08:36'),
(31,5,76,41,7000.00,0.00,7000.00,'unpaid','2026-03-05 17:30:33','2026-03-05 17:30:33'),
(32,5,77,42,7000.00,0.00,7000.00,'unpaid','2026-03-05 17:38:20','2026-03-05 17:38:20'),
(33,5,78,43,7000.00,1000.00,6000.00,'partial','2026-03-07 06:01:23','2026-03-08 15:26:28'),
(34,5,79,44,8000.00,0.00,8000.00,'unpaid','2026-03-08 12:21:47','2026-03-08 12:21:47'),
(35,5,80,45,8000.00,1000.00,7000.00,'partial','2026-03-08 12:35:10','2026-03-08 15:08:51');

/*Table structure for table `student_invoices` */

DROP TABLE IF EXISTS `student_invoices`;

CREATE TABLE `student_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_invoices_invoice_number_unique` (`invoice_number`),
  KEY `idx_si_tenant` (`tenant_id`),
  KEY `idx_si_student` (`student_id`),
  KEY `idx_si_invoice_number` (`invoice_number`),
  KEY `idx_si_status` (`status`),
  KEY `idx_si_dates` (`invoice_date`,`due_date`),
  KEY `student_invoices_batch_id_foreign` (`batch_id`),
  CONSTRAINT `student_invoices_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_invoices_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `student_invoices` */

/*Table structure for table `student_payments` */

DROP TABLE IF EXISTS `student_payments`;

CREATE TABLE `student_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `enrollment_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('cash','bank_transfer','cheque','esewa','khalti','card') NOT NULL DEFAULT 'cash',
  `reference` varchar(255) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `collected_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_student_payments_tenant` (`tenant_id`),
  KEY `fk_student_payments_student` (`student_id`),
  KEY `fk_student_payments_enrollment` (`enrollment_id`),
  KEY `fk_student_payments_user` (`collected_by`),
  CONSTRAINT `fk_student_payments_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_payments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_payments_user` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `student_payments` */

/*Table structure for table `students` */

DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `roll_no` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `dob_ad` date DEFAULT NULL COMMENT 'Date of birth (AD) — nullable for quick registration',
  `dob_bs` varchar(20) DEFAULT NULL COMMENT 'Date of birth (BS) — nullable for quick registration',
  `gender` enum('male','female','other') DEFAULT NULL COMMENT 'Gender — nullable for quick registration',
  `blood_group` varchar(5) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `citizenship_no` varchar(255) DEFAULT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `husband_name` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `permanent_address` longtext DEFAULT NULL COMMENT 'Permanent address JSON — nullable for quick registration',
  `temporary_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`temporary_address`)),
  `academic_qualifications` longtext DEFAULT NULL COMMENT 'Academic qualifications JSON — nullable for quick registration',
  `admission_date` date DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `identity_doc_url` varchar(255) DEFAULT NULL,
  `status` enum('active','alumni','dropped') NOT NULL DEFAULT 'active',
  `registration_mode` enum('quick','full') NOT NULL DEFAULT 'full' COMMENT 'quick=Quick Registration; full=Complete Profile',
  `registration_status` enum('quick_registered','fully_registered') NOT NULL DEFAULT 'fully_registered' COMMENT 'Registration completion status',
  `id_card_status` enum('none','requested','processing','issued') NOT NULL DEFAULT 'none',
  `id_card_issued_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_students_roll_no` (`roll_no`,`tenant_id`),
  KEY `idx_students_status` (`status`),
  KEY `idx_students_deleted_at` (`deleted_at`),
  KEY `idx_students_id_card` (`tenant_id`,`id_card_status`),
  KEY `idx_students_tenant_status` (`tenant_id`,`status`,`deleted_at`),
  KEY `idx_students_user_id_lookup` (`user_id`,`tenant_id`),
  KEY `idx_students_batch` (`batch_id`,`tenant_id`),
  CONSTRAINT `fk_students_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `fk_students_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `students` */

insert  into `students`(`id`,`tenant_id`,`user_id`,`batch_id`,`roll_no`,`full_name`,`dob_ad`,`dob_bs`,`gender`,`blood_group`,`phone`,`email`,`citizenship_no`,`national_id`,`father_name`,`mother_name`,`husband_name`,`guardian_name`,`guardian_relation`,`permanent_address`,`temporary_address`,`academic_qualifications`,`admission_date`,`photo_url`,`identity_doc_url`,`status`,`registration_mode`,`registration_status`,`id_card_status`,`id_card_issued_at`,`created_at`,`updated_at`,`deleted_at`) values 
(53,5,86,4,'STD-0001','Devbarat Prasad Patel','2006-12-20','2063-09-05','male','B+','9811144402','addamssmith937@gmail.com','HdTz7nalwuzkoEcw5Qn5Hjc3QzkxWEtpbmFyL2Z2QjNpYm55RFE9PQ==','','Nagendra Prasad Patel','Sanju Devi','','','','{\"province\":\"Madhesh Province\",\"district\":\"Parsa\",\"municipality\":\"Bahudramai\",\"ward\":\"07\"}','[]','[]','2026-03-01','http://localhost/erp/public/uploads/students/std_1772336418_69a3b522a0303.jpg',NULL,'active','full','fully_registered','none',NULL,'2026-03-01 09:25:19','2026-03-01 10:09:32',NULL),
(54,5,87,4,'STD-0054','Devbarat Patel','2026-03-01','2082-11-17','male','','9510408252','dhirendraparshad65@gmail.com','','','','','','','','[]','[]','[]','2026-03-01',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-01 11:01:18','2026-03-01 11:04:07',NULL),
(55,5,88,4,'STD-0055','Amiri Sah ','1996-01-01','2052-09-17','male','AB+','98250350412','amirisah1@gmail.com','','','Nagendra Prasad Patel','Sanju Devi','','','','{\"province\":\"Madhesh Province\",\"district\":\"Parsa\",\"municipality\":\"Bahudramai\",\"ward\":\"\"}','{\"province\":\"Madhesh Province\",\"district\":\"Parsa\",\"municipality\":\"Bahudramai \",\"ward\":\"\"}','[{\"level\":\"+2\",\"school\":\"A Plus academy\",\"year\":\"2079\",\"percentage\":\"B+\"}]','2026-03-01','http://localhost/erp/public/uploads/students/std_1772344524_69a3d4cc64b05.jpg',NULL,'active','full','fully_registered','none',NULL,'2026-03-01 11:40:25','2026-03-01 12:02:26',NULL),
(62,5,NULL,4,'ROLL-TEST-001','Test Fee Student',NULL,NULL,NULL,NULL,NULL,'testfee@example.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-02 19:03:51','2026-03-02 19:03:51',NULL),
(65,5,91,34,'STD-0063','Nepal Cyber Firm','2010-11-17','2063-09-05','male','',NULL,NULL,'c1hiEwpZgMLF1S6LfTT2WWJVaTJ4MEQ4ZU9HZFhVbVhrZ3Z0bDVSQjJPZHA3cjdkS0xBUk1LK2hhOG89',NULL,'Nepal Cyber Firm',NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}','{}','[]','2026-03-04',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-04 09:20:48','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(66,5,92,4,'STU-2026-0006','Niki Yadav','2009-09-19','','female','','9811144402','nepalcodingschool@gmail.com','1025151',NULL,'Master Mind Facts',NULL,NULL,NULL,NULL,'{\"address\":\"Birgunj-14,Parsa\"}','{\"address\":\"Birgunj-13, Radhemai , Parsa\"}','','2026-03-04',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-04 09:24:20','2026-03-05 07:13:38','2026-03-05 07:13:38'),
(67,5,100,4,'STD-2026-0067','Test Student Flow','2005-01-01',NULL,'male',NULL,NULL,NULL,NULL,NULL,'Test Father',NULL,NULL,NULL,NULL,'{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:14:24','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(68,5,101,4,'STD-2026-0068','Test Student Flow','2005-01-01',NULL,'male',NULL,NULL,NULL,NULL,NULL,'Test Father',NULL,NULL,NULL,NULL,'{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:15:05','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(69,5,102,4,'STD-2026-0069','Test Student Flow','2005-01-01',NULL,'male',NULL,NULL,NULL,NULL,NULL,'Test Father',NULL,NULL,NULL,NULL,'{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:16:15','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(70,5,103,4,'STD-2026-0070','Test Student Flow','2005-01-01',NULL,'male',NULL,'9800000000','test_student_1772710681@example.com',NULL,NULL,'Test Father','Test Mother',NULL,'Test Guardian','Uncle','{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:23:01','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(71,5,104,4,'STD-2026-0071','Test Student Flow','2005-01-01',NULL,'male',NULL,'9800000000','test_student_1772710748@example.com',NULL,NULL,'Test Father','Test Mother',NULL,'Test Guardian','Uncle','{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:24:08','2026-03-06 14:13:08','2026-03-06 14:13:08'),
(72,5,105,34,'STD-2026-0072','Badri Patel','2008-01-30','2063-09-05','male','AB+','9825205184','nepalcodingschool@gmail.com',NULL,NULL,'Nepal Cyber Firm',NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:24:40','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(73,5,106,4,'STD-2026-0073','Test Student Flow','2005-01-01',NULL,'male',NULL,'9800000000','test_student_1772710872@example.com',NULL,NULL,'Test Father','Test Mother',NULL,'Test Guardian','Uncle','{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:26:12','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(76,5,109,4,'STD-2026-0074','Test Student Flow','2005-01-01',NULL,'male',NULL,'9800000000','test_student_1772711133@example.com',NULL,NULL,'Test Father','Test Mother',NULL,'Test Guardian','Uncle','{\"address\":\"Test Address\"}','{}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:30:33','2026-03-06 14:13:27','2026-03-06 14:13:27'),
(77,5,110,4,'STD-2026-0077','Devbarat Prasad Patel','2014-12-29','2071-09-14','male','','9811144402','sanojpatel845484@gmail.com','FFc+KFEroROJE2o1pU4ywWFGc2crblAzeGM0TUhwTzA3ZzJLd2MvUEkwMU1hajc0d3p0QitiNzBsTkU9',NULL,'Devbarat Prasad Patel',NULL,NULL,NULL,NULL,'{\"address\":\"Bahudramai-07, Phulkaul, Parsa\"}','{\"address\":\"Birgunj-13, Radhemai , Parsa\"}','[]','2026-03-05',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-05 17:38:20','2026-03-06 16:41:16','2026-03-06 16:41:16'),
(78,5,111,4,'STD-2026-0078','Nepal Cyber Firm','2003-06-10','2063-09-05','male','B+','9845012350','nepalcodingschool@gmail.com','vZJh2FfXZfEnpNYBU0FCEUNFdVlvUllKcVNSa0RQRWE4OWVtT0FGNE1PMkRvSmVpZ2MzYnJFM2tOcFk9',NULL,'Nepal Cyber Firm',NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}','{}','[]','2026-03-07',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-07 06:01:17','2026-03-07 06:01:17',NULL),
(79,5,112,34,'STD-2026-0079','News Gunj Medai','1998-03-12','2052-09-17','male','A+','9845012350','medianewsgunj@gmail.com','ym4EkEJeeyqqC1+k1Y+zOFo0ajBhQkFvKzdQeHNZSWhGZ2JtaHJ3bFlCVThqVXpJUFFuZ0hEU0hDU1E9',NULL,'Nepal Cyber Firm',NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}','{}','[]','2026-03-08',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-08 12:21:43','2026-03-08 12:33:36','2026-03-08 12:33:36'),
(80,5,113,34,'STD-2026-0080','Nepal Cyber Firm','2003-11-12','2063-09-05','male','','9833344402','medianewsgunj@gmail.com','uxaErPUi+/k/cSPIC7J8blRnS3VkN2tDLzlzMDhqR0xpYW9YMmc9PQ==',NULL,'Nepal Cyber Firm',NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}','{}','[]','2026-03-08',NULL,NULL,'active','full','fully_registered','none',NULL,'2026-03-08 12:35:06','2026-03-08 12:35:06',NULL);

/*Table structure for table `study_material_access_logs` */

DROP TABLE IF EXISTS `study_material_access_logs`;

CREATE TABLE `study_material_access_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_type` enum('student','teacher','admin') NOT NULL,
  `action` enum('view','download') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `material_id` (`material_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_material_access_logs` */

/*Table structure for table `study_material_categories` */

DROP TABLE IF EXISTS `study_material_categories`;

CREATE TABLE `study_material_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fa-folder',
  `color` varchar(20) DEFAULT '#00B894',
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `parent_id` (`parent_id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_material_categories` */

insert  into `study_material_categories`(`id`,`tenant_id`,`name`,`description`,`icon`,`color`,`parent_id`,`sort_order`,`status`,`created_by`,`created_at`,`updated_at`,`deleted_at`) values 
(1,1,'Notes','Class notes and lecture materials','fa-file-lines','#00B894',NULL,1,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(2,1,'PDF Documents','PDF files and documents','fa-file-pdf','#E74C3C',NULL,2,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(3,1,'Video Lectures','Recorded video lectures and tutorials','fa-video','#9B59B6',NULL,3,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(4,1,'Assignments','Practice assignments and homework','fa-pen-to-square','#F39C12',NULL,4,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(5,1,'Previous Questions','Past exam papers and questions','fa-clipboard-question','#3498DB',NULL,5,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(6,1,'Reference Books','Recommended reference materials','fa-book','#1ABC9C',NULL,6,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(7,1,'Important Links','Useful external resources','fa-link','#E67E22',NULL,7,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL),
(8,1,'Syllabus','Course syllabus and curriculum','fa-list-check','#34495E',NULL,8,'active',NULL,'2026-03-07 05:11:21','2026-03-07 05:11:21',NULL);

/*Table structure for table `study_material_favorites` */

DROP TABLE IF EXISTS `study_material_favorites`;

CREATE TABLE `study_material_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`tenant_id`,`material_id`,`student_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `student_id` (`student_id`),
  KEY `material_id` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_material_favorites` */

/*Table structure for table `study_material_feedback` */

DROP TABLE IF EXISTS `study_material_feedback`;

CREATE TABLE `study_material_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_feedback` (`tenant_id`,`material_id`,`student_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `material_id` (`material_id`),
  KEY `student_id` (`student_id`),
  KEY `rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_material_feedback` */

/*Table structure for table `study_material_permissions` */

DROP TABLE IF EXISTS `study_material_permissions`;

CREATE TABLE `study_material_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `entity_type` enum('batch','student') NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_download` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`material_id`,`entity_type`,`entity_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `material_id` (`material_id`),
  KEY `entity_type` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_material_permissions` */

/*Table structure for table `study_materials` */

DROP TABLE IF EXISTS `study_materials`;

CREATE TABLE `study_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(500) DEFAULT NULL,
  `file_path` varchar(1000) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT 0,
  `file_extension` varchar(20) DEFAULT NULL,
  `external_url` varchar(1000) DEFAULT NULL,
  `content_type` enum('file','link','video','document','image') DEFAULT 'file',
  `access_type` enum('public','batch','student','private') DEFAULT 'public',
  `visibility` enum('all','specific_batches','specific_students') DEFAULT 'all',
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `download_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `category_id` (`category_id`),
  KEY `course_id` (`course_id`),
  KEY `batch_id` (`batch_id`),
  KEY `subject_id` (`subject_id`),
  KEY `status` (`status`),
  KEY `content_type` (`content_type`),
  KEY `access_type` (`access_type`),
  KEY `is_featured` (`is_featured`),
  KEY `sort_order` (`sort_order`),
  KEY `published_at` (`published_at`),
  FULLTEXT KEY `ft_title_desc` (`title`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_materials` */

/*Table structure for table `subjects` */

DROP TABLE IF EXISTS `subjects`;

CREATE TABLE `subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `subjects` */

insert  into `subjects`(`id`,`tenant_id`,`name`,`code`,`description`,`status`,`created_at`,`updated_at`,`deleted_at`) values 
(1,5,'General Knowledege','GK101','For All students','active','2026-02-27 07:37:12','2026-02-27 07:37:12',NULL);

/*Table structure for table `subscriptions` */

DROP TABLE IF EXISTS `subscriptions`;

CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `plan` enum('starter','growth','professional','enterprise') NOT NULL DEFAULT 'starter',
  `amount` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','cancelled','expired','past_due','trial') NOT NULL DEFAULT 'active',
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sub_tenant` (`tenant_id`),
  KEY `idx_sub_status` (`status`),
  KEY `idx_sub_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `subscriptions` */

/*Table structure for table `support_tickets` */

DROP TABLE IF EXISTS `support_tickets`;

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','normal','high','critical') DEFAULT 'normal',
  `status` enum('open','pending','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `support_tickets` */

insert  into `support_tickets`(`id`,`tenant_id`,`user_id`,`subject`,`description`,`priority`,`status`,`created_at`,`updated_at`) values 
(1,NULL,NULL,'Mock Ticket 1',NULL,'high','closed','2026-02-22 15:36:53','2026-02-23 15:36:53'),
(2,NULL,NULL,'Mock Ticket 2',NULL,'high','pending','2026-02-23 03:36:53','2026-02-23 15:36:53'),
(3,NULL,NULL,'Mock Ticket 3',NULL,'critical','closed','2026-02-19 22:36:53','2026-02-23 15:36:53'),
(4,NULL,NULL,'Mock Ticket 4',NULL,'critical','pending','2026-02-21 18:36:53','2026-02-23 15:36:53'),
(5,NULL,NULL,'Mock Ticket 5',NULL,'low','open','2026-02-20 02:36:53','2026-02-23 15:36:53'),
(6,NULL,NULL,'Mock Ticket 6',NULL,'high','open','2026-02-22 20:36:53','2026-02-23 15:36:53'),
(7,NULL,NULL,'Mock Ticket 7',NULL,'critical','resolved','2026-02-21 06:36:53','2026-02-23 15:36:53'),
(8,NULL,NULL,'Mock Ticket 8',NULL,'normal','pending','2026-02-20 14:36:53','2026-02-23 15:36:53'),
(9,NULL,NULL,'Mock Ticket 9',NULL,'critical','resolved','2026-02-22 02:36:53','2026-02-23 15:36:53'),
(10,NULL,NULL,'Mock Ticket 10',NULL,'high','open','2026-02-20 03:36:53','2026-02-23 15:36:53'),
(11,NULL,NULL,'Mock Ticket 11',NULL,'high','closed','2026-02-22 05:36:53','2026-02-23 15:36:53'),
(12,NULL,NULL,'Mock Ticket 12',NULL,'low','pending','2026-02-22 14:36:53','2026-02-23 15:36:53'),
(13,NULL,NULL,'Mock Ticket 13',NULL,'low','closed','2026-02-21 11:36:53','2026-02-23 15:36:53'),
(14,NULL,NULL,'Mock Ticket 14',NULL,'normal','open','2026-02-21 09:36:53','2026-02-23 15:36:53'),
(15,NULL,NULL,'Mock Ticket 15',NULL,'low','closed','2026-02-21 23:36:53','2026-02-23 15:36:53'),
(16,NULL,NULL,'Mock Ticket 16',NULL,'critical','pending','2026-02-21 19:36:53','2026-02-23 15:36:53'),
(17,NULL,NULL,'Mock Ticket 17',NULL,'high','resolved','2026-02-22 14:36:53','2026-02-23 15:36:53'),
(18,NULL,NULL,'Mock Ticket 18',NULL,'low','open','2026-02-19 19:36:53','2026-02-23 15:36:53'),
(19,NULL,NULL,'Mock Ticket 19',NULL,'high','resolved','2026-02-22 14:36:53','2026-02-23 15:36:53'),
(20,NULL,NULL,'Mock Ticket 20',NULL,'high','open','2026-02-21 06:36:53','2026-02-23 15:36:53'),
(21,NULL,NULL,'Mock Ticket 21',NULL,'low','resolved','2026-02-19 18:36:53','2026-02-23 15:36:53'),
(22,NULL,NULL,'Mock Ticket 22',NULL,'high','closed','2026-02-20 01:36:53','2026-02-23 15:36:53'),
(23,NULL,NULL,'Mock Ticket 23',NULL,'high','pending','2026-02-23 09:36:53','2026-02-23 15:36:53'),
(24,NULL,NULL,'Mock Ticket 24',NULL,'low','closed','2026-02-23 07:36:53','2026-02-23 15:36:53'),
(25,NULL,NULL,'Mock Ticket 25',NULL,'critical','open','2026-02-21 01:36:53','2026-02-23 15:36:53'),
(26,NULL,NULL,'Mock Ticket 26',NULL,'critical','closed','2026-02-23 02:36:53','2026-02-23 15:36:53'),
(27,NULL,NULL,'Mock Ticket 27',NULL,'critical','closed','2026-02-22 04:36:53','2026-02-23 15:36:53'),
(28,NULL,NULL,'Mock Ticket 28',NULL,'low','pending','2026-02-19 16:36:53','2026-02-23 15:36:53'),
(29,NULL,NULL,'Mock Ticket 29',NULL,'low','open','2026-02-19 13:36:53','2026-02-23 15:36:53'),
(30,NULL,NULL,'Mock Ticket 30',NULL,'low','pending','2026-02-23 14:36:53','2026-02-23 15:36:53');

/*Table structure for table `teachers` */

DROP TABLE IF EXISTS `teachers`;

CREATE TABLE `teachers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `monthly_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `leave_balance` smallint(5) unsigned NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_teachers_tenant` (`tenant_id`),
  KEY `fk_teachers_user` (`user_id`),
  CONSTRAINT `fk_teachers_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `teachers` */

insert  into `teachers`(`id`,`tenant_id`,`user_id`,`employee_id`,`full_name`,`phone`,`email`,`qualification`,`specialization`,`joined_date`,`monthly_salary`,`leave_balance`,`status`,`created_at`,`updated_at`,`deleted_at`) values 
(9,5,89,NULL,'Shyam Sir ','9811144402','loginteacher@gmail.com',NULL,'GK ,IQ','2026-03-03',0.00,0,'active','2026-03-03 19:28:18','2026-03-03 19:28:18',NULL);

/*Table structure for table `tenant_payments` */

DROP TABLE IF EXISTS `tenant_payments`;

CREATE TABLE `tenant_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `plan` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `billing_cycle` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('paid','pending','overdue','failed') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_payments` (`tenant_id`,`status`),
  CONSTRAINT `fk_tenant_payment` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tenant_payments` */

/*Table structure for table `tenants` */

DROP TABLE IF EXISTS `tenants`;

CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nepali_name` varchar(255) DEFAULT NULL,
  `subdomain` varchar(255) NOT NULL,
  `brand_color` varchar(20) DEFAULT NULL,
  `tagline` varchar(500) DEFAULT NULL,
  `logo_path` varchar(500) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `pan_no` varchar(100) DEFAULT NULL,
  `plan` enum('starter','growth','professional','enterprise') NOT NULL DEFAULT 'starter',
  `status` enum('active','suspended','trial') NOT NULL DEFAULT 'trial',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `student_limit` int(10) unsigned NOT NULL DEFAULT 100,
  `sms_credits` int(10) unsigned NOT NULL DEFAULT 500,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenants_subdomain` (`subdomain`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tenants` */

insert  into `tenants`(`id`,`name`,`nepali_name`,`subdomain`,`brand_color`,`tagline`,`logo_path`,`phone`,`email`,`website`,`address`,`province`,`pan_no`,`plan`,`status`,`created_by`,`student_limit`,`sms_credits`,`trial_ends_at`,`created_at`,`updated_at`,`deleted_at`,`settings`) values 
(5,'Sucess Institute Birgunj','A B  C','Brightfuture','#2ab775','Prepare with confidence','/public/uploads/logos/tenant_5_1772191191.png','9811144402','hamroloksewa@gmail.com','','',NULL,'','growth','trial',NULL,100,500,NULL,'2026-02-23 14:53:01','2026-03-01 09:34:34',NULL,NULL),
(6,'Hamro Loksewa institute','हाम्रो लोकसेवा ईस्टिट्युट','hamroloksewa','#009e7e','Education evolved.',NULL,'+9779811144402','pdewbrath@gmail.com',NULL,'Birgunj-13,Radhemai',NULL,NULL,'starter','trial',NULL,100,500,NULL,'2026-03-08 13:05:44','2026-03-08 13:05:44',NULL,NULL);

/*Table structure for table `timetable_slots` */

DROP TABLE IF EXISTS `timetable_slots`;

CREATE TABLE `timetable_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL COMMENT '1=Sunday … 7=Saturday',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `online_link` varchar(500) DEFAULT NULL,
  `class_type` enum('offline','online','lab') DEFAULT 'offline',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_timetable_tenant_batch_day` (`tenant_id`,`batch_id`,`day_of_week`),
  KEY `fk_tt_batch` (`batch_id`),
  KEY `fk_tt_teacher` (`teacher_id`),
  CONSTRAINT `fk_tt_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `timetable_slots` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `role` enum('superadmin','instituteadmin','teacher','student','guardian','frontdesk') NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `monthly_salary` decimal(10,2) DEFAULT 0.00,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `two_fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_users_tenant` (`tenant_id`),
  CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`tenant_id`,`role`,`email`,`password_hash`,`phone`,`status`,`monthly_salary`,`last_login_at`,`two_fa_enabled`,`created_at`,`updated_at`,`deleted_at`,`locked_until`,`avatar`,`name`,`two_factor_enabled`,`two_factor_secret`) values 
(1,NULL,'superadmin','pdewbrath@gmail.com','$2y$12$04j0nPzxsEZUNJscHMmGrOuq89J0fPAkfXZifa4xJY4xSYuwN3I0C',NULL,'active',0.00,'2026-03-08 13:29:29',0,'2026-02-21 14:02:24','2026-03-08 13:29:29',NULL,NULL,NULL,NULL,0,NULL),
(2,NULL,'superadmin','super2@hamrolabs.com','$2y$12$R.v07Zun1pS.k.v7F.v/Oun8n9Z6E6Z6E6Z6E6Z6E6Z6E6Z6E6Z6',NULL,'active',0.00,NULL,0,'2026-02-21 14:02:24','2026-02-23 14:01:23',NULL,NULL,NULL,NULL,0,NULL),
(38,5,'instituteadmin','nepalcyberfirm@gmail.com','$2y$12$07je2KcDgDw5a51L62PN5.fMg6Zwr/Plg5B6WQbwEGAsNy.S1Geq.','9811144402','active',0.00,'2026-03-08 15:07:10',0,'2026-02-23 14:53:01','2026-03-08 15:07:10','2026-02-26 14:43:43',NULL,NULL,'Priyanka Kumari Sah',0,NULL),
(85,5,'frontdesk','mind59024@gmail.com','$2y$12$obmUkLgcAOUF1olo5qG2F.GyVet.qzT63RY72MipLlbL7gXOOh1Qi','9811144402','active',0.00,'2026-03-08 10:26:42',0,'2026-03-01 09:17:00','2026-03-08 10:26:42',NULL,NULL,NULL,'Nepal Cyber Firm',0,NULL),
(86,5,'student','addamssmith937@gmail.com','$2y$12$orK6zuY./9/e6fncFnnLS.yoNZlb1/RgnRHEQ.Pu1JZEIwS0FI16S','9811144402','active',0.00,'2026-03-01 09:27:18',0,'2026-03-01 09:25:19','2026-03-01 09:27:18',NULL,NULL,NULL,'Devbarat Prasad Patel',0,NULL),
(87,5,'student','dhirendraparshad65@gmail.com','$2y$12$NTM7pJnoWv0xl1i3phEAyOLjw06gMv/dZsI6nbSWjVtnamIOR.nbi','9510408252','active',0.00,'2026-03-01 18:11:54',0,'2026-03-01 11:01:18','2026-03-01 18:11:54',NULL,NULL,NULL,'Devbarat Patel',0,NULL),
(88,5,'student','amirisah1@gmail.com','$2y$12$rDWdDv4ZQKMLrnEgj/NxuuEpWGx6XOIWoY5nxCz.ItM9NyVX3AD7a','98250350412','active',0.00,NULL,0,'2026-03-01 11:40:25','2026-03-01 12:02:26',NULL,NULL,NULL,'Amiri Sah ',0,NULL),
(89,5,'teacher','loginteacher@gmail.com','$2y$12$8OiEhOjucIVBvv8hn0E.lezmeUHu5ElAHeHMQK9T.zGdxtrfT6dzm','9811144402','active',0.00,NULL,0,'2026-03-03 19:28:18','2026-03-03 19:28:18',NULL,NULL,NULL,'Shyam Sir ',0,NULL),
(91,5,'student','nepalcyberfirm@gmail.com','$2y$12$9/IF/g1MdlW0zf/U4H/3lOa0QT5RtOxMD0PfI3mhKz2Ng3eNAtQ6i','9845012350','active',0.00,NULL,0,'2026-03-04 09:20:48','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(92,5,'student','nepalcodingschool@gmail.com','$2y$12$k2wfNkXANIj9yQ16MzakletVuBF5Rv.EN4fNTsVQC3t2sirZMkLHW','9811144402','active',0.00,NULL,0,'2026-03-04 09:24:20','2026-03-05 07:13:38','2026-03-05 07:13:38',NULL,NULL,'Niki Yadav',0,NULL),
(98,5,'student','test_student_1772710088@example.com','$2y$12$KEpY0KR3ydevPbSa37RdBOWs4up/6jDHV7XdytsSf8M7kIfREUQsy',NULL,'active',0.00,NULL,0,'2026-03-05 17:13:08','2026-03-05 17:13:08',NULL,NULL,NULL,'Test Student Flow',0,NULL),
(99,5,'student','test_student_1772710116@example.com','$2y$12$2sYElOaGLUHLA.HSJWTgPeXkD3C3HN7B6aVQ31DjiklFSGMC24Ytu',NULL,'active',0.00,NULL,0,'2026-03-05 17:13:36','2026-03-05 17:13:36',NULL,NULL,NULL,'Test Student Flow',0,NULL),
(100,5,'student','test_student_1772710164@example.com','$2y$12$vTfeoFtzNUh9nMiCAnvOHeUtpXVfM7cwVREtSWUk7aQAWLixtJXAe',NULL,'active',0.00,NULL,0,'2026-03-05 17:14:24','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Test Student Flow',0,NULL),
(101,5,'student','test_student_1772710205@example.com','$2y$12$rMPLWVnO2G71AVmEVYiSyeh0T9eDtlq3nD.JVM/BP.X52CQMHq5dC',NULL,'active',0.00,NULL,0,'2026-03-05 17:15:05','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Test Student Flow',0,NULL),
(102,5,'student','test_student_1772710275@example.com','$2y$12$6ynAgQ1MaE7dXOrn8acXVufT8S118EqRaH3hqsZqR5xgM4GFBYWcu',NULL,'active',0.00,NULL,0,'2026-03-05 17:16:15','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Test Student Flow',0,NULL),
(103,5,'student','test_student_1772710681@example.com','$2y$12$r9MStrgTume/QDjUMAxmk.pg8HKHGjmkDtrhtec6UYTo2JAX3GkSy','9800000000','active',0.00,NULL,0,'2026-03-05 17:23:01','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Test Student Flow',0,NULL),
(104,5,'student','test_student_1772710748@example.com','$2y$12$lLaNGleU8XK3M61pn2Yayejeeg95cYQaI/O6id15YHseBNosoyy2y','9800000000','active',0.00,NULL,0,'2026-03-05 17:24:08','2026-03-06 14:13:08','2026-03-06 14:13:08',NULL,NULL,'Test Student Flow',0,NULL),
(105,5,'student','nepalcodingschool@gmail.com','$2y$12$TEgYjYQ./5aRv8tinvTl8eMOU/Rbsg1aYt2Y/OeAvjk5h4HjXVtb2','9825205184','active',0.00,NULL,0,'2026-03-05 17:24:40','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Badri Patel',0,NULL),
(106,5,'student','test_student_1772710872@example.com','$2y$12$IS6T4h7pUmuY8S3qDZhmaucYYxzenLpvtY/MLQejxYWcqMlpv9bQC','9800000000','active',0.00,NULL,0,'2026-03-05 17:26:12','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Test Student Flow',0,NULL),
(109,5,'student','test_student_1772711133@example.com','$2y$12$6piIszXRMZ/fRa7l8vpPwueLUZpMsu4DYa7oG2ls8rhr9HdKtSMWC','9800000000','active',0.00,NULL,0,'2026-03-05 17:30:33','2026-03-06 14:13:27','2026-03-06 14:13:27',NULL,NULL,'Test Student Flow',0,NULL),
(110,5,'student','sanojpatel845484@gmail.com','$2y$12$tHbuHBXmAg6viPjVgGsz0.mcdCr0hJeJ7Jd.CLBqfsvlYe9YTVZDC','9811144402','active',0.00,NULL,0,'2026-03-05 17:38:20','2026-03-06 16:41:16','2026-03-06 16:41:16',NULL,NULL,'Devbarat Prasad Patel',0,NULL),
(111,5,'student','nepalcodingschool@gmail.com','$2y$12$/WqdOyo0ouv/D5NuzAM9Tuuj2Xvjps2PeCrty.ClU2qyoCmGbfbYK','9845012350','active',0.00,NULL,0,'2026-03-07 06:01:17','2026-03-07 06:01:17',NULL,NULL,NULL,'Nepal Cyber Firm',0,NULL),
(112,5,'student','medianewsgunj@gmail.com','$2y$12$4mwd5xneYYtcmt5P5GtG4OIx3jaoceEy3DeG6lwbS34oVIHRCOjkK','9845012350','active',0.00,NULL,0,'2026-03-08 12:21:43','2026-03-08 12:33:36','2026-03-08 12:33:36',NULL,NULL,'News Gunj Medai',0,NULL),
(113,5,'student','medianewsgunj@gmail.com','$2y$12$n3AlVYoKtD3qiY56zMsTA.dMctRUvBq2yfPQKjqcwzkJtRlCHwz4u','9833344402','active',0.00,NULL,0,'2026-03-08 12:35:06','2026-03-08 12:35:06',NULL,NULL,NULL,'Nepal Cyber Firm',0,NULL),
(114,6,'instituteadmin','toonmitra355@gmail.com','$2y$12$fmr0kDR.bbm.B2UvIuPjLuNe7eO0p73ts4OIAZKojS5zUJqIucVm.','9811144402','active',0.00,'2026-03-08 13:08:29',0,'2026-03-08 13:05:44','2026-03-08 13:08:29',NULL,NULL,NULL,'Devbarat Prasad Patel',0,NULL);

/*Table structure for table `workflow_checklists` */

DROP TABLE IF EXISTS `workflow_checklists`;

CREATE TABLE `workflow_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `task_key` varchar(100) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `checklist_date` date NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_workflow_item` (`tenant_id`,`user_id`,`task_key`,`checklist_date`),
  KEY `idx_workflow_lookup` (`tenant_id`,`user_id`,`checklist_date`),
  KEY `idx_workflow_pending` (`tenant_id`,`checklist_date`,`is_completed`),
  KEY `idx_workflow_date` (`checklist_date`),
  KEY `workflow_checklists_user_id_foreign` (`user_id`),
  CONSTRAINT `workflow_checklists_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_checklists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `workflow_checklists` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
