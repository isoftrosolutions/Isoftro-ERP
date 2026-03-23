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

/*Table structure for table `acc_accounts` */

DROP TABLE IF EXISTS `acc_accounts`;

CREATE TABLE `acc_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('asset','liability','equity','income','expense') NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_accounts_tenant_id_foreign` (`tenant_id`),
  KEY `acc_accounts_parent_id_foreign` (`parent_id`),
  CONSTRAINT `acc_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `acc_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acc_accounts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `acc_accounts` */

/*Table structure for table `acc_fiscal_years` */

DROP TABLE IF EXISTS `acc_fiscal_years`;

CREATE TABLE `acc_fiscal_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_fiscal_years_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `acc_fiscal_years_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `acc_fiscal_years` */

/*Table structure for table `acc_ledger_postings` */

DROP TABLE IF EXISTS `acc_ledger_postings`;

CREATE TABLE `acc_ledger_postings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acc_ledger_postings_voucher_id_foreign` (`voucher_id`),
  KEY `acc_ledger_postings_account_id_foreign` (`account_id`),
  CONSTRAINT `acc_ledger_postings_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `acc_accounts` (`id`),
  CONSTRAINT `acc_ledger_postings_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acc_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `acc_ledger_postings` */

/*Table structure for table `acc_vouchers` */

DROP TABLE IF EXISTS `acc_vouchers`;

CREATE TABLE `acc_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `voucher_no` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `type` enum('journal','receipt','payment','contra') NOT NULL,
  `narration` text DEFAULT NULL,
  `fiscal_year_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acc_vouchers_voucher_no_unique` (`voucher_no`),
  KEY `acc_vouchers_tenant_id_foreign` (`tenant_id`),
  KEY `acc_vouchers_fiscal_year_id_foreign` (`fiscal_year_id`),
  KEY `acc_vouchers_created_by_foreign` (`created_by`),
  KEY `acc_vouchers_date_index` (`date`),
  KEY `acc_vouchers_status_index` (`status`),
  CONSTRAINT `acc_vouchers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `acc_vouchers_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `acc_fiscal_years` (`id`),
  CONSTRAINT `acc_vouchers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `acc_vouchers` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `attendance` */

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

/*Table structure for table `audit_logs` */

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `audit_logs` */

insert  into `audit_logs`(`id`,`user_id`,`tenant_id`,`action`,`ip_address`,`user_agent`,`description`,`metadata`,`created_at`) values 
(1,NULL,NULL,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"failed\",\"reason\":\"User not found\"}','2026-03-23 08:31:42'),
(2,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"pdewbrath@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-23 08:37:56'),
(3,1,NULL,'Tenant Created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','New tenant \'Sucess Institute Birgunj\' (sic) created with admin \'mind59024@gmail.com\'',NULL,'2026-03-23 08:39:21'),
(4,150,1,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-23 08:40:08');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `batch_subject_allocations` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `batches` */

/*Table structure for table `communication_logs` */

DROP TABLE IF EXISTS `communication_logs`;

CREATE TABLE `communication_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `type` enum('sms','email','whatsapp') NOT NULL,
  `sender_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_type` enum('student','staff','teacher','other') DEFAULT 'other',
  `recipient_contact` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed','delivered') NOT NULL DEFAULT 'pending',
  `provider_response` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_comm_logs` (`tenant_id`),
  KEY `idx_comm_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `communication_logs` */

/*Table structure for table `course_categories` */

DROP TABLE IF EXISTS `course_categories`;

CREATE TABLE `course_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_categories_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  CONSTRAINT `course_categories_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `course_categories` */

/*Table structure for table `courses` */

DROP TABLE IF EXISTS `courses`;

CREATE TABLE `courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `course_category_id` bigint(20) unsigned DEFAULT NULL,
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
  KEY `courses_course_category_id_foreign` (`course_category_id`),
  CONSTRAINT `courses_course_category_id_foreign` FOREIGN KEY (`course_category_id`) REFERENCES `course_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_courses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `courses` */

/*Table structure for table `email_logs` */

DROP TABLE IF EXISTS `email_logs`;

CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` int(11) DEFAULT 0,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `sent_via` enum('tenant_smtp','system_smtp') DEFAULT 'system_smtp',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `status` (`status`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `email_logs` */

insert  into `email_logs`(`id`,`tenant_id`,`student_id`,`campaign_id`,`email`,`subject`,`status`,`sent_via`,`error_message`,`created_at`) values 
(1,1,0,0,'mind59024@gmail.com','Welcome to Isoftro ERP! Your Institute Portal is Ready','sent','system_smtp',NULL,'2026-03-23 08:39:26');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `email_templates` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `enrollments` */

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

/*Table structure for table `expense_categories` */

DROP TABLE IF EXISTS `expense_categories`;

CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_categories_parent_id_foreign` (`parent_id`),
  KEY `expense_categories_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  CONSTRAINT `expense_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_categories_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `expense_categories` */

/*Table structure for table `expenses` */

DROP TABLE IF EXISTS `expenses`;

CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date_ad` date NOT NULL,
  `date_bs` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','esewa','khalti','cheque') NOT NULL DEFAULT 'cash',
  `receipt_path` varchar(255) DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `transaction_id` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `cheque_number` varchar(255) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `cheque_status` enum('pending','cleared','bounced') NOT NULL DEFAULT 'pending',
  `status` enum('draft','pending','approved','rejected','archived') NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_approved_by_foreign` (`approved_by`),
  KEY `expenses_created_by_foreign` (`created_by`),
  KEY `expenses_tenant_id_status_date_ad_index` (`tenant_id`,`status`,`date_ad`),
  KEY `expenses_expense_category_id_index` (`expense_category_id`),
  CONSTRAINT `expenses_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `expenses` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `failed_logins` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_items` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_ledger` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_records` */

/*Table structure for table `fee_settings` */

DROP TABLE IF EXISTS `fee_settings`;

CREATE TABLE `fee_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `invoice_prefix` varchar(20) DEFAULT 'INV',
  `receipt_prefix` varchar(20) DEFAULT 'RCP',
  `next_invoice_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `next_receipt_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant` (`tenant_id`),
  CONSTRAINT `fk_fee_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_settings` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `homework` */

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

/*Table structure for table `impersonation_tokens` */

DROP TABLE IF EXISTS `impersonation_tokens`;

CREATE TABLE `impersonation_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `impersonation_tokens` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `inquiries` */

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

/*Table structure for table `institute_feature_access` */

DROP TABLE IF EXISTS `institute_feature_access`;

CREATE TABLE `institute_feature_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `feature_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tenant_feature` (`tenant_id`,`feature_id`),
  KEY `fk_feat_system` (`feature_id`),
  CONSTRAINT `fk_feat_system` FOREIGN KEY (`feature_id`) REFERENCES `system_features` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_feat_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `institute_feature_access` */

insert  into `institute_feature_access`(`id`,`tenant_id`,`feature_id`,`is_enabled`) values 
(1,1,1,1),
(2,1,2,1),
(4,1,6,1),
(5,1,3,1),
(6,1,10,1),
(7,1,4,1),
(8,1,5,1),
(9,1,7,1),
(10,1,9,1),
(11,1,8,1),
(12,1,11,1);

/*Table structure for table `invoices` */

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned DEFAULT NULL,
  `fee_record_id` bigint(20) unsigned DEFAULT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `invoice_type` enum('student','subscription','other') DEFAULT 'student',
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
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `job_type` varchar(100) NOT NULL,
  `payload` longtext NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `job_type` (`job_type`),
  KEY `tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `job_queue` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `ledger_entries` */

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

/*Table structure for table `message_templates` */

DROP TABLE IF EXISTS `message_templates`;

CREATE TABLE `message_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('sms','email','whatsapp') NOT NULL DEFAULT 'sms',
  `subject` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_templates` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `message_templates` */

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(11,'2026_03_08_082236_create_feedbacks_table',8),
(12,'2026_03_13_181146_add_institute_type_to_tenants_table',9),
(13,'2026_03_13_181931_change_institute_type_to_enum_in_tenants_table',10),
(14,'2026_03_13_184427_create_course_categories_table',11),
(15,'2026_03_15_081400_create_rooms_table',12),
(16,'2026_03_18_075735_create_expense_categories_table',13),
(17,'2026_03_18_075753_create_expenses_table',14),
(19,'2026_03_21_192635_create_accounting_tables',15),
(20,'2026_03_22_000000_add_indexes_to_accounting_tables',16);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `notices` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `notification_automation_rules` */

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

/*Table structure for table `online_class_attendance` */

DROP TABLE IF EXISTS `online_class_attendance`;

CREATE TABLE `online_class_attendance` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `online_class_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `left_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 0,
  `status` enum('present','absent','partial') NOT NULL DEFAULT 'absent',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_class_attendance` (`online_class_id`),
  KEY `idx_student_online_attendance` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `online_class_attendance` */

/*Table structure for table `online_classes` */

DROP TABLE IF EXISTS `online_classes`;

CREATE TABLE `online_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 40,
  `meeting_provider` enum('zoom','google_meet','jitsi','internal') NOT NULL DEFAULT 'zoom',
  `meeting_id` varchar(100) DEFAULT NULL,
  `meeting_password` varchar(100) DEFAULT NULL,
  `join_url` text DEFAULT NULL,
  `start_url` text DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','canceled') NOT NULL DEFAULT 'scheduled',
  `recorded_url` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_classes` (`tenant_id`),
  KEY `idx_batch_classes` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `online_classes` */

/*Table structure for table `otp_codes` */

DROP TABLE IF EXISTS `otp_codes`;

CREATE TABLE `otp_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `code` varchar(10) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_otp_user` (`user_id`),
  KEY `idx_otp_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `otp_codes` */

/*Table structure for table `password_resets` */

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `email` varchar(191) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `password_resets` */

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
  `source_type` varchar(50) DEFAULT 'fee_record',
  `source_id` bigint(20) unsigned DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `payment_transactions` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `plan_features` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `platform_settings` */

/*Table structure for table `refresh_tokens` */

DROP TABLE IF EXISTS `refresh_tokens`;

CREATE TABLE `refresh_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `invalidated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_refresh_user` (`user_id`),
  KEY `idx_refresh_token` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `refresh_tokens` */

insert  into `refresh_tokens`(`id`,`user_id`,`token`,`expires_at`,`invalidated`,`created_at`) values 
(1,1,'04663697aee6c9c44e2a077c5b7a6e6a0f8387bbb7a3805e7a568a4dcb28cb15','2026-04-22 08:37:56',0,'2026-03-23 08:37:56'),
(2,150,'4b20d031f7c0cdda824e65b224ea08fa42740db117679087bfbdff4e213b89a1','2026-04-22 08:40:08',0,'2026-03-23 08:40:08');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `remember_tokens` */

/*Table structure for table `rooms` */

DROP TABLE IF EXISTS `rooms`;

CREATE TABLE `rooms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rooms_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  CONSTRAINT `rooms_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `rooms` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sms_templates` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `student_fee_summary` */

/*Table structure for table `students` */

DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `roll_no` varchar(50) NOT NULL,
  `dob_bs` varchar(20) DEFAULT NULL COMMENT 'Date of birth (BS) — nullable for quick registration',
  `dob_ad` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL COMMENT 'Gender — nullable for quick registration',
  `blood_group` varchar(5) DEFAULT NULL,
  `citizenship_no` varchar(255) DEFAULT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `husband_name` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `permanent_address` longtext DEFAULT NULL,
  `temporary_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`temporary_address`)),
  `academic_qualifications` longtext DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `identity_doc_url` varchar(255) DEFAULT NULL,
  `status` enum('active','alumni','dropped') DEFAULT 'active',
  `is_active` tinyint(1) DEFAULT 1,
  `alumni_year` int(11) DEFAULT NULL,
  `completion_status` varchar(255) DEFAULT NULL,
  `alumni_remarks` text DEFAULT NULL,
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
  KEY `idx_students_batch` (`tenant_id`),
  CONSTRAINT `fk_students_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `check_stu_permanent_addr` CHECK (json_valid(`permanent_address`)),
  CONSTRAINT `check_stu_qualifications` CHECK (json_valid(`academic_qualifications`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `students` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_material_categories` */

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
  `is_qbank` tinyint(1) DEFAULT 0,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `subjects` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `support_tickets` */

/*Table structure for table `system_features` */

DROP TABLE IF EXISTS `system_features`;

CREATE TABLE `system_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feature_key` varchar(50) NOT NULL,
  `feature_name` varchar(100) NOT NULL,
  `is_core` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_feature_key` (`feature_key`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `system_features` */

insert  into `system_features`(`id`,`feature_key`,`feature_name`,`is_core`,`status`) values 
(1,'students','Student Management',1,'active'),
(2,'teachers','Teacher Management',1,'active'),
(3,'attendance','Attendance System',0,'active'),
(4,'exams','Examinations',0,'active'),
(5,'homework','Homework Management',0,'active'),
(6,'accounting','Accounting & Finance',0,'active'),
(7,'inquiry','Admissions & Inquiry',0,'active'),
(8,'lms','Study Materials (LMS)',0,'active'),
(9,'library','Library Management',0,'active'),
(10,'communication','SMS & Notifications',0,'active'),
(11,'reports','Advanced Reports',0,'active');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `teachers` */

/*Table structure for table `tenant_email_settings` */

DROP TABLE IF EXISTS `tenant_email_settings`;

CREATE TABLE `tenant_email_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `reply_to_email` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `smtp_encryption` enum('tls','ssl','none') DEFAULT 'tls',
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `tenant_email_settings` */

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `pan_number` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `institute_type` enum('loksewa preparation','computer training','bridge course','tuition','other') DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tenants` */

insert  into `tenants`(`id`,`name`,`nepali_name`,`subdomain`,`brand_color`,`tagline`,`logo_path`,`phone`,`email`,`pan_number`,`website`,`address`,`institute_type`,`province`,`pan_no`,`plan`,`status`,`created_by`,`student_limit`,`sms_credits`,`trial_ends_at`,`created_at`,`updated_at`,`deleted_at`,`settings`) values 
(1,'Sucess Institute Birgunj','Hamro Loksewa institute','sic','#009e7e','The Holy Shit','/public/uploads/logos/logo_1_1774234460.png','+9779811144402','pdewbrath@gmail.com','10224454',NULL,'Birgunj-13,Radhemai','loksewa preparation',NULL,NULL,'growth','active',1,100,500,NULL,'2026-03-23 08:39:20','2026-03-23 08:39:20',NULL,NULL);

/*Table structure for table `timetable_slots` */

DROP TABLE IF EXISTS `timetable_slots`;

CREATE TABLE `timetable_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned NOT NULL,
  `teacher_id` bigint(20) unsigned NOT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `room_id` bigint(20) unsigned DEFAULT NULL,
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
  KEY `timetable_slots_room_id_foreign` (`room_id`),
  CONSTRAINT `fk_tt_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_slots_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`tenant_id`,`role`,`email`,`password_hash`,`phone`,`status`,`monthly_salary`,`last_login_at`,`two_fa_enabled`,`created_at`,`updated_at`,`deleted_at`,`locked_until`,`avatar`,`name`,`two_factor_enabled`,`two_factor_secret`) values 
(1,NULL,'superadmin','pdewbrath@gmail.com','$2y$12$04j0nPzxsEZUNJscHMmGrOuq89J0fPAkfXZifa4xJY4xSYuwN3I0C',NULL,'active',0.00,'2026-03-23 08:37:54',0,'2026-02-21 14:02:24','2026-03-23 08:37:54',NULL,NULL,NULL,NULL,0,NULL),
(2,NULL,'superadmin','super2@hamrolabs.com','$2y$12$R.v07Zun1pS.k.v7F.v/Oun8n9Z6E6Z6E6Z6E6Z6E6Z6E6Z6E6Z6',NULL,'active',0.00,NULL,0,'2026-02-21 14:02:24','2026-02-23 14:01:23',NULL,NULL,NULL,NULL,0,NULL),
(149,4,'instituteadmin','succes@gmail.com','$2y$12$RAUkvwtQYU79bKe13YaTVeXxE0gQPQvPGG9onm1Mm/8JOmSAHAkli','','active',0.00,NULL,0,'2026-03-23 08:25:37','2026-03-23 08:25:37',NULL,NULL,NULL,'Devbarat Prasad Patel',0,NULL),
(150,1,'instituteadmin','mind59024@gmail.com','$2y$12$t.6dtVyS6w.fPTAALFQ0yei27arVvd3qTZ01ubrTyYdjg9ix2gH2K','','active',0.00,'2026-03-23 08:40:08',0,'2026-03-23 08:39:21','2026-03-23 08:40:08',NULL,NULL,NULL,'Devbarat Prasad Patel',0,NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
