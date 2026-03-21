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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `attendance` */

insert  into `attendance`(`id`,`tenant_id`,`student_id`,`batch_id`,`course_id`,`attendance_date`,`status`,`marked_by`,`locked`,`created_at`,`updated_at`) values 
(1,3,11,1,1,'2026-03-16','present',123,0,'2026-03-16 14:09:46','2026-03-16 14:09:46'),
(2,3,12,1,1,'2026-03-16','present',123,0,'2026-03-16 14:09:46','2026-03-16 14:09:46'),
(3,3,11,1,1,'2026-03-17','present',123,0,'2026-03-17 18:28:19','2026-03-17 18:28:19'),
(4,3,12,1,1,'2026-03-17','present',123,0,'2026-03-17 18:28:19','2026-03-17 18:28:19');

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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `audit_logs` */

insert  into `audit_logs`(`id`,`user_id`,`tenant_id`,`action`,`ip_address`,`user_agent`,`description`,`metadata`,`created_at`) values 
(1,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"pdewbrath@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 09:22:33'),
(2,NULL,NULL,'LOGIN_FAILURE','::1','curl/8.18.0',NULL,'{\"email\":\"test\",\"status\":\"failed\",\"reason\":\"User not found\"}','2026-03-11 09:25:53'),
(3,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"pdewbrath@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 09:38:55'),
(4,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"pdewbrath@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 09:58:58'),
(5,1,NULL,'Tenant Created','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','New tenant \'Hamro Loksewa institute\' (hamroloksewa) created with admin \'nepalcyberfirm@gmail.com\'',NULL,'2026-03-11 10:10:41'),
(6,123,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 10:13:20'),
(7,123,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 10:13:24'),
(8,123,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 10:13:30'),
(9,123,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 10:15:12'),
(10,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-11 10:34:31'),
(11,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-12 09:29:01'),
(12,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"pdewbrath@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-13 11:21:39'),
(13,130,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcodingschool@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-13 19:05:25'),
(14,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-14 04:52:40'),
(15,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-14 04:57:26'),
(16,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-14 04:57:33'),
(17,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-14 04:57:48'),
(18,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-14 05:29:11'),
(19,131,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcodingschool@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-14 05:46:35'),
(20,129,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 08:50:22'),
(21,129,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 08:50:29'),
(22,129,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 08:51:33'),
(23,129,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 08:53:42'),
(24,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-14 08:53:55'),
(25,129,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 08:54:47'),
(26,123,3,'LOGIN_FAILURE',NULL,NULL,NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 08:56:44'),
(27,133,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"toonmitra355@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 11:05:27'),
(28,133,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"toonmitra355@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-14 11:09:23'),
(29,133,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"toonmitra355@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-14 11:10:38'),
(30,1,NULL,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"pdewbrath@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-14 14:56:35'),
(31,133,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"toonmitra355@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-14 15:06:44'),
(32,124,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-15 08:03:31'),
(33,124,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-15 08:03:39'),
(34,NULL,NULL,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmanepalcyberfirm@gmail.comil.com\",\"status\":\"failed\",\"reason\":\"User not found\"}','2026-03-16 11:08:54'),
(35,123,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-16 11:09:17'),
(36,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-16 11:09:25'),
(37,123,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-16 11:09:52'),
(38,123,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-16 11:11:24'),
(39,124,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"addamssmith937@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-16 11:52:18'),
(40,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-16 11:56:00'),
(41,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-16 17:48:40'),
(42,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-18 06:58:26'),
(43,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-18 07:22:38'),
(44,NULL,NULL,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmailteacher.com\",\"status\":\"failed\",\"reason\":\"User not found\"}','2026-03-18 07:26:23'),
(45,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-18 07:26:32'),
(46,NULL,NULL,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"teacher\",\"status\":\"failed\",\"reason\":\"User not found\"}','2026-03-18 07:26:45'),
(47,NULL,NULL,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"teachermind59024@gmail.com\",\"status\":\"failed\",\"reason\":\"User not found\"}','2026-03-18 07:28:12'),
(48,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-18 07:28:26'),
(49,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-18 07:32:36'),
(50,125,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-18 07:36:38'),
(51,123,3,'LOGIN_FAILURE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"nepalcyberfirm@gmail.com\",\"status\":\"failed\",\"reason\":\"Invalid password\"}','2026-03-18 07:37:30'),
(52,NULL,NULL,'LOGOUT','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"\",\"status\":\"success\",\"reason\":null}','2026-03-18 07:44:41'),
(53,125,3,'LOGIN_SUCCESS','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',NULL,'{\"email\":\"mind59024@gmail.com\",\"status\":\"success\",\"reason\":null}','2026-03-18 16:49:04');

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
(1,3,1,1,1,'2026-03-15 08:42:19','2026-03-15 08:42:19');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `batches` */

insert  into `batches`(`id`,`tenant_id`,`course_id`,`name`,`shift`,`start_date`,`end_date`,`max_strength`,`room`,`status`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,1,'Morning batch computer course','morning','2026-03-11',NULL,40,'','active','2026-03-11 10:16:33','2026-03-11 10:16:33',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `course_categories` */

insert  into `course_categories`(`id`,`tenant_id`,`name`,`slug`,`description`,`is_active`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,'General Academy','general-academy',NULL,1,'2026-03-13 18:53:56','2026-03-13 18:53:56',NULL),
(2,3,'Skill Development','skill-development',NULL,1,'2026-03-13 18:53:56','2026-03-13 18:53:56',NULL),
(3,3,'Language Class','language-class',NULL,1,'2026-03-13 18:53:56','2026-03-13 18:53:56',NULL),
(4,3,'PSC','psc','',1,'2026-03-13 18:54:15','2026-03-13 18:54:15',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `courses` */

insert  into `courses`(`id`,`tenant_id`,`course_category_id`,`name`,`code`,`description`,`fee`,`duration_weeks`,`seats`,`is_active`,`category`,`status`,`duration_months`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,NULL,'computer Course','101','jedhb\n',8000.00,12,100,1,'general','active',NULL,'2026-03-11 10:15:53','2026-03-12 07:07:24',NULL),
(2,3,1,'Bridge Course','B2082','This is desinged for SEE appread students ',3600.00,12,100,1,'general','active',NULL,'2026-03-18 17:58:19','2026-03-18 17:58:19',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `email_logs` */

insert  into `email_logs`(`id`,`tenant_id`,`student_id`,`campaign_id`,`email`,`subject`,`status`,`sent_via`,`error_message`,`created_at`) values 
(1,3,0,0,'nepalcyberfirm@gmail.com','Password Reset Request - Hamro Loksewa institute','sent','system_smtp',NULL,'2026-03-11 10:11:52'),
(2,3,0,0,'nepalcyberfirm@gmail.com','Password Reset Request - Hamro Loksewa institute','sent','system_smtp',NULL,'2026-03-11 10:12:20'),
(3,3,0,0,'addamssmith937@gmail.com','Welcome to Hamro Loksewa institute - Registration Successful! ?','sent','system_smtp',NULL,'2026-03-11 10:17:06'),
(4,3,0,0,'addamssmith937@gmail.com','Payment Received - Receipt #RCP-000001','sent','system_smtp',NULL,'2026-03-11 10:20:45'),
(5,3,0,0,'addamssmith937@gmail.com','Payment Received - Receipt #RCP-000002','sent','system_smtp',NULL,'2026-03-11 11:16:15'),
(6,3,0,0,'pdewbrath@gmail.com','Welcome to Hamro Loksewa institute - Registration Successful! ?','sent','system_smtp',NULL,'2026-03-11 11:26:27'),
(7,3,0,0,'pdewbrath@gmail.com','Payment Received - Receipt #RCP-000003','sent','system_smtp',NULL,'2026-03-11 11:27:10'),
(8,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000004','sent','system_smtp',NULL,'2026-03-11 11:56:29'),
(9,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000005','sent','system_smtp',NULL,'2026-03-11 11:59:50'),
(10,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000006','sent','system_smtp',NULL,'2026-03-11 12:04:37'),
(11,3,0,0,'addamssmith937@gmail.com','Payment Successful - Receipt #RCP-000007','sent','system_smtp',NULL,'2026-03-11 12:18:29'),
(12,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000008','sent','system_smtp',NULL,'2026-03-11 14:20:23'),
(13,3,0,0,'addamssmith937@gmail.com','Payment Successful - Receipt #RCP-000014','sent','system_smtp',NULL,'2026-03-11 14:22:00'),
(14,3,0,0,'addamssmith937@gmail.com','Payment Successful - Receipt #RCP-000015','sent','system_smtp',NULL,'2026-03-11 14:40:34'),
(15,3,0,0,'addamssmith937@gmail.com','Payment Successful - Receipt #RCP-000016','sent','system_smtp',NULL,'2026-03-11 14:52:17'),
(16,3,0,0,'nepalcodingschool@gmail.com','Welcome to the Team! - Hamro Loksewa institute ?','sent','tenant_smtp',NULL,'2026-03-16 14:08:00'),
(17,3,0,0,'addamssmith937@gmail.com','Registration Successful - Hamro Loksewa institute! ?','sent','tenant_smtp',NULL,'2026-03-18 14:35:09'),
(18,3,0,0,'addamssmith937@gmail.com','Payment Successful - Receipt #RCP-000024','sent','tenant_smtp',NULL,'2026-03-18 14:35:54'),
(19,3,0,0,'addamssmith937@gmail.com','Payment Successful - Receipt #RCP-000025','sent','tenant_smtp',NULL,'2026-03-18 16:59:03'),
(20,3,0,0,'aimsinstitutebirgunj@gmail.com','Registration Successful - Hamro Loksewa institute! ?','sent','tenant_smtp',NULL,'2026-03-18 17:55:35'),
(21,3,0,0,'aimsinstitutebirgunj@gmail.com','Payment Successful - Receipt #RCP-000026','sent','tenant_smtp',NULL,'2026-03-18 17:56:34'),
(22,3,0,0,'mohammadmishal557@gmail.com','Registration Successful - Hamro Loksewa institute! ?','sent','tenant_smtp',NULL,'2026-03-19 13:42:44'),
(23,3,0,0,'mohammadmishal557@gmail.com','Payment Successful - Receipt #RCP-000027','sent','tenant_smtp',NULL,'2026-03-19 13:45:02'),
(24,3,0,0,'nepalcyberfirm@gmail.com','Registration Successful - Hamro Loksewa institute! ?','sent','tenant_smtp',NULL,'2026-03-19 20:29:35'),
(25,3,0,0,'nepalcyberfirm@gmail.com','Registration Successful - Hamro Loksewa institute! ?','sent','tenant_smtp',NULL,'2026-03-19 20:36:21'),
(26,3,0,0,'nepalcyberfirm@gmail.com','Payment Successful - Receipt #RCP-000028','sent','tenant_smtp',NULL,'2026-03-19 21:09:09'),
(27,3,0,0,'nepalcyberfirm@gmail.com','Payment Successful - Receipt #RCP-000029','sent','tenant_smtp',NULL,'2026-03-20 06:03:21'),
(28,3,0,0,'nepalcyberfirm@gmail.com','Payment Successful - Receipt #RCP-000030','sent','tenant_smtp',NULL,'2026-03-20 06:13:07'),
(29,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000031','sent','tenant_smtp',NULL,'2026-03-20 06:15:25'),
(30,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000032','sent','tenant_smtp',NULL,'2026-03-20 06:22:13'),
(31,3,0,0,'nepalcyberfirm@gmail.com','Payment Successful - Receipt #RCP-000033','sent','tenant_smtp',NULL,'2026-03-20 06:53:47'),
(32,3,0,0,'pdewbrath@gmail.com','Registration Successful - Hamro Loksewa institute! ?','sent','tenant_smtp',NULL,'2026-03-20 07:00:42'),
(33,3,0,0,'pdewbrath@gmail.com','Payment Successful - Receipt #RCP-000034','sent','tenant_smtp',NULL,'2026-03-20 07:01:43');

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `enrollments` */

insert  into `enrollments`(`id`,`tenant_id`,`student_id`,`batch_id`,`enrollment_id`,`enrollment_date`,`status`,`created_at`,`updated_at`,`status_changed_at`) values 
(1,3,1,1,'ENR-3-2026-00001','2026-03-11','active','2026-03-11 10:17:01','2026-03-11 10:17:01',NULL),
(2,3,2,1,'ENR-3-2026-00002','2026-03-11','active','2026-03-11 11:26:22','2026-03-11 11:26:22',NULL),
(3,3,4,1,'ENR-3-2026-00004','2026-03-12','active','2026-03-12 07:06:12','2026-03-12 07:06:12',NULL),
(4,3,5,1,'ENR-3-2026-00005','2026-03-12','active','2026-03-12 09:41:44','2026-03-12 09:41:44',NULL),
(5,3,6,1,'ENR-3-2026-00006','2026-03-14','active','2026-03-14 06:00:06','2026-03-14 06:00:06',NULL),
(6,3,7,1,'ENR-3-2026-00007-79','2026-03-14','active','2026-03-14 08:58:00','2026-03-14 08:58:00',NULL),
(7,3,8,1,'ENR-3-2026-00008-19','2026-03-14','active','2026-03-14 09:00:37','2026-03-14 09:00:37',NULL),
(8,3,9,1,'ENR-3-2026-00009-22','2026-03-14','active','2026-03-14 11:03:50','2026-03-14 11:03:50',NULL),
(9,3,10,1,'ENR-3-2026-00010-46','2026-03-14','active','2026-03-14 16:38:20','2026-03-14 16:38:20',NULL),
(10,3,11,1,'ENR-3-2026-00011-30','2026-03-15','active','2026-03-15 06:18:23','2026-03-15 06:18:23',NULL),
(11,3,12,1,'ENR-3-2026-00012-36','2026-03-16','active','2026-03-16 13:41:45','2026-03-16 13:41:45',NULL),
(15,3,15,1,'ENR-3-2026-00015-34','2026-03-18','active','2026-03-18 14:35:04','2026-03-18 14:35:04',NULL),
(16,3,16,1,'ENR-3-2026-00016-49','2026-03-18','active','2026-03-18 17:55:30','2026-03-18 17:55:30',NULL),
(17,3,17,1,'ENR-3-2026-00017-48','2026-03-19','active','2026-03-19 13:42:39','2026-03-19 13:42:39',NULL),
(18,3,18,1,'ENR-3-2026-00018-51','2026-03-19','active','2026-03-19 20:29:28','2026-03-19 20:29:28',NULL),
(19,3,19,1,'ENR-3-2026-00019-38','2026-03-19','active','2026-03-19 20:36:16','2026-03-19 20:36:16',NULL),
(20,3,20,1,'ENR-3-2026-00020-56','2026-03-20','active','2026-03-20 07:00:37','2026-03-20 07:00:37',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `exams` */

insert  into `exams`(`id`,`tenant_id`,`batch_id`,`course_id`,`created_by_user_id`,`title`,`duration_minutes`,`total_marks`,`negative_mark`,`question_mode`,`start_at`,`end_at`,`status`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,1,1,123,'Annual Assessment',180,50.00,0.00,'manual','2026-03-12 00:00:00','2026-03-12 01:00:00','scheduled','2026-03-11 15:00:06','2026-03-15 08:09:08','2026-03-15 08:09:08'),
(2,3,1,1,123,'The knowledge  Test',90,90.00,0.00,'auto','2026-12-20 11:00:00','2026-12-20 11:45:00','scheduled','2026-03-16 17:47:25','2026-03-16 17:47:25',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `expense_categories` */

insert  into `expense_categories`(`id`,`tenant_id`,`parent_id`,`name`,`description`,`color`,`icon`,`is_active`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,NULL,'Staff Salaries & Benefits',NULL,'#4e73df','user-tie',1,'2026-03-18 08:05:17','2026-03-18 08:05:17',NULL),
(2,3,NULL,'Operational Costs',NULL,'#1cc88a','building',1,'2026-03-18 08:05:17','2026-03-18 08:05:17',NULL),
(3,3,NULL,'Educational Materials & Resources',NULL,'#36b9cc','book',1,'2026-03-18 08:05:17','2026-03-18 08:05:17',NULL),
(4,3,NULL,'Marketing & Promotional Expenses',NULL,'#f6c23e','ad',1,'2026-03-18 08:05:17','2026-03-18 08:05:17',NULL),
(5,3,NULL,'Technology & Infrastructure',NULL,'#e74a3b','laptop',1,'2026-03-18 08:05:17','2026-03-18 08:05:17',NULL),
(6,3,NULL,'Miscellaneous',NULL,'#858796','ellipsis-h',1,'2026-03-18 08:05:17','2026-03-18 08:05:17',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `expenses` */

insert  into `expenses`(`id`,`tenant_id`,`expense_category_id`,`amount`,`date_ad`,`date_bs`,`description`,`payment_method`,`receipt_path`,`is_recurring`,`transaction_id`,`bank_name`,`reference_number`,`cheque_number`,`cheque_date`,`cheque_status`,`status`,`approved_by`,`created_by`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,2,15000.00,'2026-03-19','2082-12-05','I have done this for new','esewa',NULL,1,'','','','',NULL,'pending','approved',NULL,123,'2026-03-18 08:14:48','2026-03-18 08:14:48',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_items` */

insert  into `fee_items`(`id`,`tenant_id`,`course_id`,`name`,`type`,`amount`,`installments`,`late_fine_per_day`,`is_active`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,1,'Tuition Fee - computer Cours','admission',8000.00,1,0.00,1,'2026-03-11 10:15:53','2026-03-12 07:07:24',NULL),
(2,3,2,'Tuition Fee - Bridge Course','admission',3600.00,1,0.00,1,'2026-03-18 17:58:19','2026-03-18 17:58:19',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_ledger` */

insert  into `fee_ledger`(`id`,`tenant_id`,`student_id`,`payment_transaction_id`,`fee_record_id`,`entry_date`,`entry_type`,`amount`,`description`,`created_at`,`updated_at`) values 
(1,3,1,1,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000001','2026-03-11 10:20:39','2026-03-11 10:20:39'),
(2,3,1,2,NULL,'2026-03-11','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000002','2026-03-11 11:15:51','2026-03-11 11:15:51'),
(3,3,2,3,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000003','2026-03-11 11:26:56','2026-03-11 11:26:56'),
(4,3,2,4,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000004','2026-03-11 11:56:13','2026-03-11 11:56:13'),
(5,3,2,5,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000005','2026-03-11 11:59:33','2026-03-11 11:59:33'),
(6,3,2,6,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000006','2026-03-11 12:04:18','2026-03-11 12:04:18'),
(7,3,1,7,NULL,'2026-03-11','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000007','2026-03-11 12:18:14','2026-03-11 12:18:14'),
(8,3,2,8,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000008','2026-03-11 14:20:07','2026-03-11 14:20:07'),
(9,3,2,9,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000009','2026-03-11 14:20:23','2026-03-11 14:20:23'),
(10,3,2,10,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000010','2026-03-11 14:20:43','2026-03-11 14:20:43'),
(11,3,1,11,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000014','2026-03-11 14:21:45','2026-03-11 14:21:45'),
(12,3,1,12,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000015','2026-03-11 14:40:12','2026-03-11 14:40:12'),
(13,3,1,13,NULL,'2026-03-11','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000016','2026-03-11 14:52:02','2026-03-11 14:52:02'),
(14,3,5,14,NULL,'2026-03-13','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000001','2026-03-13 11:57:03','2026-03-13 11:57:03'),
(15,3,5,15,NULL,'2026-03-13','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000002','2026-03-13 12:08:54','2026-03-13 12:08:54'),
(16,3,5,16,NULL,'2026-03-13','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000003','2026-03-13 12:20:49','2026-03-13 12:20:49'),
(17,3,6,17,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000004','2026-03-14 07:18:11','2026-03-14 07:18:11'),
(18,3,9,18,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000005','2026-03-14 11:08:22','2026-03-14 11:08:22'),
(19,3,9,19,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000006','2026-03-14 15:02:19','2026-03-14 15:02:19'),
(20,3,9,20,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000007','2026-03-14 15:11:27','2026-03-14 15:11:27'),
(21,3,9,21,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000008','2026-03-14 15:11:44','2026-03-14 15:11:44'),
(22,3,9,22,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000009','2026-03-14 15:12:05','2026-03-14 15:12:05'),
(23,3,9,23,NULL,'2026-03-14','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000017','2026-03-14 18:25:39','2026-03-14 18:25:39'),
(24,3,9,24,NULL,'2026-03-14','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000018','2026-03-14 18:29:49','2026-03-14 18:29:49'),
(25,3,9,25,NULL,'2026-03-14','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000019','2026-03-14 18:30:05','2026-03-14 18:30:05'),
(26,3,9,26,NULL,'2026-03-14','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000020','2026-03-14 18:30:35','2026-03-14 18:30:35'),
(27,3,9,27,NULL,'2026-03-14','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000021','2026-03-14 18:30:51','2026-03-14 18:30:51'),
(28,3,11,28,NULL,'2026-03-15','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000022','2026-03-15 06:18:47','2026-03-15 06:18:47'),
(29,3,12,29,NULL,'2026-03-16','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000023','2026-03-16 13:42:33','2026-03-16 13:42:33'),
(30,3,15,30,NULL,'2026-03-18','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000024','2026-03-18 14:35:32','2026-03-18 14:35:32'),
(31,3,15,31,NULL,'2026-03-18','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000025','2026-03-18 16:58:46','2026-03-18 16:58:46'),
(32,3,16,32,NULL,'2026-03-18','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000026','2026-03-18 17:56:18','2026-03-18 17:56:18'),
(33,3,17,33,NULL,'2026-03-19','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000027','2026-03-19 13:44:41','2026-03-19 13:44:41'),
(34,3,19,34,NULL,'2026-03-19','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000028','2026-03-19 21:08:45','2026-03-19 21:08:45'),
(35,3,19,35,NULL,'2026-03-20','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000029','2026-03-20 06:03:05','2026-03-20 06:03:05'),
(36,3,19,36,NULL,'2026-03-20','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000030','2026-03-20 06:12:49','2026-03-20 06:12:49'),
(37,3,12,37,NULL,'2026-03-20','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000031','2026-03-20 06:15:10','2026-03-20 06:15:10'),
(38,3,12,38,NULL,'2026-03-20','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000032','2026-03-20 06:21:59','2026-03-20 06:21:59'),
(39,3,19,39,NULL,'2026-03-20','credit',1000.00,'Bulk Fee Payment - Receipt #RCP-000033','2026-03-20 06:53:31','2026-03-20 06:53:31'),
(40,3,20,40,NULL,'2026-03-20','credit',500.00,'Bulk Fee Payment - Receipt #RCP-000034','2026-03-20 07:01:25','2026-03-20 07:01:25');

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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_records` */

insert  into `fee_records`(`id`,`tenant_id`,`student_id`,`batch_id`,`fee_item_id`,`installment_no`,`amount_due`,`amount_paid`,`discount_amount`,`due_date`,`paid_date`,`receipt_no`,`receipt_path`,`payment_mode`,`cashier_user_id`,`fine_applied`,`fine_waived`,`notes`,`academic_year`,`status`,`invoice_id`,`created_at`,`updated_at`) values 
(1,3,1,1,1,1,5000.00,5000.00,0.00,'2026-03-11','2026-03-11','RCP-000016',NULL,'cash',123,0.00,0.00,NULL,'2026-2027','paid',NULL,'2026-03-11 10:17:01','2026-03-11 14:52:02'),
(2,3,2,1,1,1,7000.00,7000.00,0.00,'2026-03-11','2026-03-11','RCP-000010',NULL,'cash',123,0.00,0.00,NULL,'2026-2027','paid',NULL,'2026-03-11 11:26:22','2026-03-11 14:20:43'),
(3,3,4,1,1,1,7000.00,0.00,0.00,'2026-03-12',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-12 07:06:12','2026-03-12 07:06:12'),
(4,3,5,1,1,1,8000.00,2000.00,0.00,'2026-03-12','2026-03-13','RCP-000003',NULL,'cash',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-12 09:41:44','2026-03-13 12:20:49'),
(5,3,6,1,1,1,8000.00,1000.00,0.00,'2026-03-14','2026-03-14','RCP-000004',NULL,'bank_transfer',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-14 06:00:06','2026-03-14 07:18:11'),
(6,3,7,1,1,1,8000.00,0.00,0.00,'2026-03-14',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-14 08:58:00','2026-03-14 08:58:00'),
(7,3,8,1,1,1,8000.00,0.00,0.00,'2026-03-14',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-14 09:00:37','2026-03-14 09:00:37'),
(8,3,9,1,1,1,8000.00,8000.00,0.00,'2026-03-14','2026-03-14','RCP-000021',NULL,'cash',123,0.00,0.00,NULL,'2026-2027','paid',NULL,'2026-03-14 11:03:50','2026-03-14 18:30:51'),
(9,3,10,1,1,1,8000.00,0.00,0.00,'2026-03-14',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-14 16:38:20','2026-03-14 16:38:20'),
(10,3,11,1,1,1,8000.00,1000.00,0.00,'2026-03-15','2026-03-15','RCP-000022',NULL,'cash',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-15 06:18:23','2026-03-15 06:18:47'),
(11,3,12,1,1,1,8000.00,3000.00,0.00,'2026-03-16','2026-03-20','RCP-000032',NULL,'cash',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-16 13:41:45','2026-03-20 06:21:59'),
(13,3,15,1,1,1,8000.00,2000.00,0.00,'2026-03-18','2026-03-18','RCP-000025',NULL,'esewa',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-18 14:35:04','2026-03-18 16:58:46'),
(14,3,16,1,1,1,8000.00,1000.00,0.00,'2026-03-18','2026-03-18','RCP-000026',NULL,'esewa',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-18 17:55:30','2026-03-18 17:56:18'),
(15,3,17,1,1,1,8000.00,1000.00,0.00,'2026-03-19','2026-03-19','RCP-000027',NULL,'esewa',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-19 13:42:39','2026-03-19 13:44:41'),
(16,3,18,1,1,1,8000.00,0.00,0.00,'2026-03-19',NULL,NULL,NULL,NULL,NULL,0.00,0.00,NULL,'2026-2027','pending',NULL,'2026-03-19 20:29:28','2026-03-19 20:29:28'),
(17,3,19,1,1,1,8000.00,4000.00,0.00,'2026-03-19','2026-03-20','RCP-000033',NULL,'bank_transfer',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-19 20:36:16','2026-03-20 06:53:31'),
(18,3,20,1,1,1,8000.00,500.00,0.00,'2026-03-20','2026-03-20','RCP-000034',NULL,'esewa',123,0.00,0.00,NULL,'2026-2027','partial',NULL,'2026-03-20 07:00:37','2026-03-20 07:01:25');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fee_settings` */

insert  into `fee_settings`(`id`,`tenant_id`,`invoice_prefix`,`receipt_prefix`,`next_invoice_number`,`next_receipt_number`,`created_at`,`updated_at`) values 
(1,3,'INV','RCP',1,35,'2026-03-13 11:56:09','2026-03-20 07:01:25');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `guardians` */

insert  into `guardians`(`id`,`tenant_id`,`user_id`,`student_id`,`relation`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,127,1,'father','2026-03-12 06:48:00','2026-03-12 06:48:00',NULL),
(2,3,128,2,'father','2026-03-12 06:48:00','2026-03-12 06:48:00',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `homework` */

insert  into `homework`(`id`,`tenant_id`,`course_id`,`batch_id`,`subject_id`,`title`,`description`,`due_date`,`total_marks`,`attachment_path`,`created_by`,`status`,`created_at`,`updated_at`) values 
(1,3,1,1,1,'Chapter Four Memorization','The chapter work all related to this module  should be completed until the holiday .','2026-03-27',100,NULL,123,'published','2026-03-17 15:29:53','2026-03-17 15:29:53');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `homework_submissions` */

insert  into `homework_submissions`(`id`,`homework_id`,`student_id`,`submission_text`,`attachment_path`,`marks_obtained`,`feedback`,`status`,`submitted_at`,`graded_by`,`graded_at`,`created_at`,`updated_at`) values 
(1,1,1,'My work is done',NULL,NULL,NULL,'submitted','2026-03-18 06:55:29',NULL,NULL,'2026-03-18 06:55:29','2026-03-18 06:55:29');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `inquiries` */

insert  into `inquiries`(`id`,`tenant_id`,`inquiry_type`,`full_name`,`phone`,`alt_phone`,`email`,`course_id`,`source`,`status`,`notes`,`appointment_date`,`appointment_time`,`check_in_at`,`check_out_at`,`address`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,'inquiry','Nepal Coding School','9825205484','','nepalcodingschool@gmail.com',1,'facebook','pending','',NULL,NULL,NULL,NULL,'','2026-03-14 08:29:35','2026-03-14 08:29:35',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `job_queue` */

insert  into `job_queue`(`id`,`tenant_id`,`job_type`,`payload`,`status`,`error_message`,`attempts`,`created_at`,`updated_at`) values 
(1,3,'payment_receipt','{\"transaction_id\":12,\"receipt_no\":\"RCP-000015\",\"student_id\":1,\"student_name\":\"Nepal Cyber Firm\",\"student_email\":\"addamssmith937@gmail.com\",\"roll_no\":\"STD-2026-0001\",\"course_name\":\"computer Course\",\"batch_name\":\"Morning batch computer course\",\"amount\":\"1000.00\",\"paid_date\":\"2026-03-11\",\"payment_mode\":\"cash\",\"login_url\":\"http:\\/\\/localhost\\/erp\\/?page=login\"}','pending',NULL,0,'2026-03-11 14:43:23','2026-03-11 14:43:23'),
(2,3,'payment_receipt','{\"transaction_id\":2,\"receipt_no\":\"RCP-000002\",\"student_id\":1,\"student_name\":\"Nepal Cyber Firm\",\"student_email\":\"addamssmith937@gmail.com\",\"roll_no\":\"STD-2026-0001\",\"course_name\":\"computer Course\",\"batch_name\":\"Morning batch computer course\",\"amount\":\"500.00\",\"paid_date\":\"2026-03-11\",\"payment_mode\":\"cash\",\"login_url\":\"http:\\/\\/localhost\\/erp\\/?page=login\"}','pending',NULL,0,'2026-03-13 12:08:59','2026-03-13 12:08:59');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `leave_requests` */

insert  into `leave_requests`(`id`,`tenant_id`,`student_id`,`from_date`,`to_date`,`reason`,`status`,`approved_by`,`approved_at`,`created_at`,`updated_at`) values 
(1,3,9,'2026-03-14','2026-03-28','I am going to thailand','approved',123,'2026-03-14 15:25:44','2026-03-14 15:19:55','2026-03-14 15:25:44');

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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `ledger_entries` */

insert  into `ledger_entries`(`id`,`tenant_id`,`student_id`,`reference_type`,`reference_id`,`amount`,`type`,`description`,`entry_date`,`created_at`,`updated_at`) values 
(1,3,1,'payment',1,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000001','2026-03-11','2026-03-11 10:20:39','2026-03-11 10:20:39'),
(2,3,1,'payment',2,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000002','2026-03-11','2026-03-11 11:15:51','2026-03-11 11:15:51'),
(3,3,2,'payment',3,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000003','2026-03-11','2026-03-11 11:26:56','2026-03-11 11:26:56'),
(4,3,2,'payment',4,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000004','2026-03-11','2026-03-11 11:56:13','2026-03-11 11:56:13'),
(5,3,2,'payment',5,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000005','2026-03-11','2026-03-11 11:59:33','2026-03-11 11:59:33'),
(6,3,2,'payment',6,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000006','2026-03-11','2026-03-11 12:04:18','2026-03-11 12:04:18'),
(7,3,1,'payment',7,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000007','2026-03-11','2026-03-11 12:18:14','2026-03-11 12:18:14'),
(8,3,2,'payment',8,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000008','2026-03-11','2026-03-11 14:20:07','2026-03-11 14:20:07'),
(9,3,2,'payment',9,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000009','2026-03-11','2026-03-11 14:20:23','2026-03-11 14:20:23'),
(10,3,2,'payment',10,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000010','2026-03-11','2026-03-11 14:20:43','2026-03-11 14:20:43'),
(11,3,1,'payment',11,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000014','2026-03-11','2026-03-11 14:21:45','2026-03-11 14:21:45'),
(12,3,1,'payment',12,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000015','2026-03-11','2026-03-11 14:40:12','2026-03-11 14:40:12'),
(13,3,1,'payment',13,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000016','2026-03-11','2026-03-11 14:52:02','2026-03-11 14:52:02'),
(14,3,5,'payment',14,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000001','2026-03-13','2026-03-13 11:57:03','2026-03-13 11:57:03'),
(15,3,5,'payment',15,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000002','2026-03-13','2026-03-13 12:08:54','2026-03-13 12:08:54'),
(16,3,5,'payment',16,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000003','2026-03-13','2026-03-13 12:20:49','2026-03-13 12:20:49'),
(17,3,6,'payment',17,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000004','2026-03-14','2026-03-14 07:18:11','2026-03-14 07:18:11'),
(18,3,9,'payment',18,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000005','2026-03-14','2026-03-14 11:08:22','2026-03-14 11:08:22'),
(19,3,9,'payment',19,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000006','2026-03-14','2026-03-14 15:02:19','2026-03-14 15:02:19'),
(20,3,9,'payment',20,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000007','2026-03-14','2026-03-14 15:11:27','2026-03-14 15:11:27'),
(21,3,9,'payment',21,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000008','2026-03-14','2026-03-14 15:11:44','2026-03-14 15:11:44'),
(22,3,9,'payment',22,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000009','2026-03-14','2026-03-14 15:12:05','2026-03-14 15:12:05'),
(23,3,9,'payment',23,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000017','2026-03-14','2026-03-14 18:25:39','2026-03-14 18:25:39'),
(24,3,9,'payment',24,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000018','2026-03-14','2026-03-14 18:29:49','2026-03-14 18:29:49'),
(25,3,9,'payment',25,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000019','2026-03-14','2026-03-14 18:30:05','2026-03-14 18:30:05'),
(26,3,9,'payment',26,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000020','2026-03-14','2026-03-14 18:30:35','2026-03-14 18:30:35'),
(27,3,9,'payment',27,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000021','2026-03-14','2026-03-14 18:30:51','2026-03-14 18:30:51'),
(28,3,11,'payment',28,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000022','2026-03-15','2026-03-15 06:18:47','2026-03-15 06:18:47'),
(29,3,12,'payment',29,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000023','2026-03-16','2026-03-16 13:42:33','2026-03-16 13:42:33'),
(30,3,15,'payment',30,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000024','2026-03-18','2026-03-18 14:35:32','2026-03-18 14:35:32'),
(31,3,15,'payment',31,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000025','2026-03-18','2026-03-18 16:58:46','2026-03-18 16:58:46'),
(32,3,16,'payment',32,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000026','2026-03-18','2026-03-18 17:56:18','2026-03-18 17:56:18'),
(33,3,17,'payment',33,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000027','2026-03-19','2026-03-19 13:44:41','2026-03-19 13:44:41'),
(34,3,19,'payment',34,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000028','2026-03-19','2026-03-19 21:08:45','2026-03-19 21:08:45'),
(35,3,19,'payment',35,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000029','2026-03-20','2026-03-20 06:03:05','2026-03-20 06:03:05'),
(36,3,19,'payment',36,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000030','2026-03-20','2026-03-20 06:12:49','2026-03-20 06:12:49'),
(37,3,12,'payment',37,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000031','2026-03-20','2026-03-20 06:15:10','2026-03-20 06:15:10'),
(38,3,12,'payment',38,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000032','2026-03-20','2026-03-20 06:21:59','2026-03-20 06:21:59'),
(39,3,19,'payment',39,1000.00,'credit','Bulk Fee Payment - Receipt #RCP-000033','2026-03-20','2026-03-20 06:53:31','2026-03-20 06:53:31'),
(40,3,20,'payment',40,500.00,'credit','Bulk Fee Payment - Receipt #RCP-000034','2026-03-20','2026-03-20 07:01:25','2026-03-20 07:01:25');

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(17,'2026_03_18_075753_create_expenses_table',14);

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `password_resets` */

insert  into `password_resets`(`id`,`tenant_id`,`user_id`,`role`,`email`,`token`,`expires_at`,`created_at`) values 
(7,3,134,'student','infohamrolabs@gmail.com','628219','2026-03-14 17:09:51','2026-03-14 16:39:51');

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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `payment_transactions` */

insert  into `payment_transactions`(`id`,`tenant_id`,`student_id`,`fee_record_id`,`source_type`,`source_id`,`invoice_id`,`amount`,`payment_method`,`transaction_id`,`receipt_number`,`payment_date`,`receipt_path`,`recorded_by`,`notes`,`status`,`created_at`,`updated_at`) values 
(1,3,1,1,'fee_record',1,NULL,1000.00,'cash',NULL,'RCP-000001','2026-03-11','public/uploads/receipts/receipt_RCP-000001.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 10:20:39','2026-03-12 06:32:33'),
(2,3,1,1,'fee_record',1,NULL,500.00,'cash',NULL,'RCP-000002','2026-03-11','public/uploads/receipts/receipt_RCP-000002.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 11:15:51','2026-03-12 06:32:33'),
(3,3,2,2,'fee_record',2,NULL,1000.00,'cash',NULL,'RCP-000003','2026-03-11','public/uploads/receipts/receipt_RCP-000003.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 11:26:56','2026-03-12 06:32:33'),
(4,3,2,2,'fee_record',2,NULL,1000.00,'cash',NULL,'RCP-000004','2026-03-11','public/uploads/receipts/receipt_RCP-000004.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 11:56:13','2026-03-12 06:32:33'),
(5,3,2,2,'fee_record',2,NULL,1000.00,'cash',NULL,'RCP-000005','2026-03-11','public/uploads/receipts/receipt_RCP-000005.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 11:59:33','2026-03-12 06:32:33'),
(6,3,2,2,'fee_record',2,NULL,1000.00,'esewa',NULL,'RCP-000006','2026-03-11','public/uploads/receipts/receipt_RCP-000006.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 12:04:18','2026-03-12 06:32:33'),
(7,3,1,1,'fee_record',1,NULL,500.00,'cash',NULL,'RCP-000007','2026-03-11','public/uploads/receipts/receipt_RCP-000007.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 12:18:14','2026-03-12 06:32:33'),
(8,3,2,2,'fee_record',2,NULL,1000.00,'cash',NULL,'RCP-000008','2026-03-11','public/uploads/receipts/receipt_RCP-000008.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 14:20:07','2026-03-12 06:32:33'),
(9,3,2,2,'fee_record',2,NULL,1000.00,'cash',NULL,'RCP-000009','2026-03-11','public/uploads/receipts/receipt_RCP-000009.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 14:20:23','2026-03-12 06:32:33'),
(10,3,2,2,'fee_record',2,NULL,1000.00,'cash',NULL,'RCP-000010','2026-03-11','public/uploads/receipts/receipt_RCP-000010.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 14:20:43','2026-03-12 06:32:33'),
(11,3,1,1,'fee_record',1,NULL,1000.00,'cash',NULL,'RCP-000014','2026-03-11','public/uploads/receipts/receipt_RCP-000014.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 14:21:45','2026-03-12 06:32:33'),
(12,3,1,1,'fee_record',1,NULL,1000.00,'cash',NULL,'RCP-000015','2026-03-11','public/uploads/receipts/receipt_RCP-000015.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 14:40:12','2026-03-12 06:32:33'),
(13,3,1,1,'fee_record',1,NULL,1000.00,'cash',NULL,'RCP-000016','2026-03-11','public/uploads/receipts/receipt_RCP-000016.pdf',123,' (Bulk Payment Part)','completed','2026-03-11 14:52:02','2026-03-12 06:32:33'),
(14,3,5,4,'fee_record',NULL,NULL,500.00,'cash',NULL,'RCP-000001','2026-03-13','public/uploads/receipts/receipt_RCP-000001.pdf',123,' (Bulk Payment Part)','completed','2026-03-13 11:57:03','2026-03-15 08:15:43'),
(15,3,5,4,'fee_record',NULL,NULL,500.00,'cash',NULL,'RCP-000002','2026-03-13','public/uploads/receipts/receipt_RCP-000002.pdf',123,' (Bulk Payment Part)','completed','2026-03-13 12:08:54','2026-03-13 12:16:49'),
(16,3,5,4,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000003','2026-03-13','public/uploads/receipts/receipt_RCP-000003.pdf',123,' (Bulk Payment Part)','completed','2026-03-13 12:20:49','2026-03-13 12:20:52'),
(17,3,6,5,'fee_record',NULL,NULL,1000.00,'bank_transfer',NULL,'RCP-000004','2026-03-14','public/uploads/receipts/receipt_RCP-000004.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 07:18:11','2026-03-14 07:18:20'),
(18,3,9,8,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000005','2026-03-14','public/uploads/receipts/receipt_RCP-000005.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 11:08:22','2026-03-14 11:08:30'),
(19,3,9,8,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000006','2026-03-14','public/uploads/receipts/receipt_RCP-000006.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 15:02:19','2026-03-14 15:02:29'),
(20,3,9,8,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000007','2026-03-14','public/uploads/receipts/receipt_RCP-000007.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 15:11:27','2026-03-14 15:11:31'),
(21,3,9,8,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000008','2026-03-14','public/uploads/receipts/receipt_RCP-000008.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 15:11:44','2026-03-14 15:11:47'),
(22,3,9,8,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000009','2026-03-14','public/uploads/receipts/receipt_RCP-000009.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 15:12:05','2026-03-14 15:12:08'),
(23,3,9,8,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000017','2026-03-14','public/uploads/receipts/receipt_RCP-000017.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 18:25:39','2026-03-14 18:25:43'),
(24,3,9,8,'fee_record',NULL,NULL,500.00,'cash',NULL,'RCP-000018','2026-03-14','public/uploads/receipts/receipt_RCP-000018.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 18:29:49','2026-03-14 18:29:52'),
(25,3,9,8,'fee_record',NULL,NULL,500.00,'cash',NULL,'RCP-000019','2026-03-14','public/uploads/receipts/receipt_RCP-000019.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 18:30:05','2026-03-14 18:30:08'),
(26,3,9,8,'fee_record',NULL,NULL,500.00,'cash',NULL,'RCP-000020','2026-03-14','public/uploads/receipts/receipt_RCP-000020.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 18:30:35','2026-03-14 18:30:39'),
(27,3,9,8,'fee_record',NULL,NULL,500.00,'cash',NULL,'RCP-000021','2026-03-14','public/uploads/receipts/receipt_RCP-000021.pdf',123,' (Bulk Payment Part)','completed','2026-03-14 18:30:51','2026-03-14 18:30:55'),
(28,3,11,10,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000022','2026-03-15','public/uploads/receipts/receipt_RCP-000022.pdf',123,' (Bulk Payment Part)','completed','2026-03-15 06:18:47','2026-03-15 06:18:57'),
(29,3,12,11,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000023','2026-03-16','public/uploads/receipts/receipt_RCP-000023.pdf',123,' (Bulk Payment Part)','completed','2026-03-16 13:42:33','2026-03-16 13:42:36'),
(30,3,15,13,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000024','2026-03-18','public/uploads/receipts/receipt_RCP-000024.pdf',123,' (Bulk Payment Part)','completed','2026-03-18 14:35:32','2026-03-18 14:35:42'),
(31,3,15,13,'fee_record',NULL,NULL,1000.00,'esewa',NULL,'RCP-000025','2026-03-18','public/uploads/receipts/receipt_RCP-000025.pdf',123,'In Hand Cash  (Bulk Payment Part)','completed','2026-03-18 16:58:46','2026-03-18 16:58:49'),
(32,3,16,14,'fee_record',NULL,NULL,1000.00,'esewa',NULL,'RCP-000026','2026-03-18','public/uploads/receipts/receipt_RCP-000026.pdf',123,' (Bulk Payment Part)','completed','2026-03-18 17:56:18','2026-03-18 17:56:22'),
(33,3,17,15,'fee_record',NULL,NULL,1000.00,'esewa',NULL,'RCP-000027','2026-03-19','public/uploads/receipts/receipt_RCP-000027.pdf',123,' (Bulk Payment Part)','completed','2026-03-19 13:44:41','2026-03-19 13:44:50'),
(34,3,19,17,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000028','2026-03-19','public/uploads/receipts/receipt_RCP-000028.pdf',123,' (Bulk Payment Part)','completed','2026-03-19 21:08:45','2026-03-19 21:08:55'),
(35,3,19,17,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000029','2026-03-20','public/uploads/receipts/receipt_RCP-000029.pdf',123,' (Bulk Payment Part)','completed','2026-03-20 06:03:05','2026-03-20 06:03:09'),
(36,3,19,17,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000030','2026-03-20','public/uploads/receipts/receipt_RCP-000030.pdf',123,' (Bulk Payment Part)','completed','2026-03-20 06:12:49','2026-03-20 06:12:56'),
(37,3,12,11,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000031','2026-03-20','public/uploads/receipts/receipt_RCP-000031.pdf',123,' (Bulk Payment Part)','completed','2026-03-20 06:15:10','2026-03-20 06:15:13'),
(38,3,12,11,'fee_record',NULL,NULL,1000.00,'cash',NULL,'RCP-000032','2026-03-20','public/uploads/receipts/receipt_RCP-000032.pdf',123,' (Bulk Payment Part)','completed','2026-03-20 06:21:59','2026-03-20 06:22:02'),
(39,3,19,17,'fee_record',NULL,NULL,1000.00,'bank_transfer',NULL,'RCP-000033','2026-03-20','public/uploads/receipts/receipt_RCP-000033.pdf',123,' (Bulk Payment Part)','completed','2026-03-20 06:53:31','2026-03-20 06:53:34'),
(40,3,20,18,'fee_record',NULL,NULL,500.00,'esewa',NULL,'RCP-000034','2026-03-20','public/uploads/receipts/receipt_RCP-000034.pdf',123,' (Bulk Payment Part)','completed','2026-03-20 07:01:25','2026-03-20 07:01:28');

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `refresh_tokens` */

insert  into `refresh_tokens`(`id`,`user_id`,`token`,`expires_at`,`invalidated`,`created_at`) values 
(1,125,'bb5a84e58fb05906842ffeedf0ef4ce4455231fe86d2fb20d115d52df73a978c','2026-04-10 10:34:31',0,'2026-03-11 10:34:31'),
(2,1,'42d497f1f4e239d57860c647d229aff29042d4d17bd317fe4173ad80ed3b9c66','2026-04-12 11:21:39',0,'2026-03-13 11:21:39'),
(3,130,'2fd9ec96b4f459fb0859bf20260de0d8ab611c044b88e4944df36382e9281308','2026-04-12 19:05:25',0,'2026-03-13 19:05:25'),
(4,131,'6d3f2745440a7b295f6261585277446c96bd0971b4e2ad8e126ed7ac0236d2d4','2026-04-13 05:46:35',0,'2026-03-14 05:46:35'),
(5,133,'0e486363c3a8eef13739386af0a530ad9096d96c2ba43f3894f62bf82f9842d9','2026-04-13 11:10:38',0,'2026-03-14 11:10:38'),
(6,1,'b599d1a11a6f55cb7a8c5c1c4a8e989083e38e7437ba4dbc68688d8e65d025ef','2026-04-13 14:56:35',0,'2026-03-14 14:56:35'),
(7,133,'18ce5afda654ed4211949e32932cd368eec41383788025e14fd26386b2c53e1e','2026-04-13 15:06:44',0,'2026-03-14 15:06:44'),
(8,124,'75b64aaadc486653bde9e0110cc1880148e695d5717eafbe0ab54c6eae620269','2026-04-14 08:03:39',0,'2026-03-15 08:03:39'),
(9,123,'85bc8731c3aff877493ad730c68286ff4beb1518fe03dca033735e5125b75062','2026-04-15 11:11:24',0,'2026-03-16 11:11:24'),
(10,124,'c0c3e30519b8a882564ea1060c9f9d95332c06aded30392da7ba245b748eb9bc','2026-04-15 11:52:18',0,'2026-03-16 11:52:18'),
(11,125,'99b374a21e6950e6d57c7988a03169533e7fc0f8ecac398d2784d23d5c6e855f','2026-04-15 11:56:00',0,'2026-03-16 11:56:00'),
(12,125,'70a43037d3198a0438a161cddbd8d055a20d11d6c5d0ac4385fffee717d795e8','2026-04-15 17:48:40',0,'2026-03-16 17:48:40'),
(13,125,'f7d2d8a08ea86f84fecffac8511345db201c30f434a98de5ea5e02b781c478a6','2026-04-17 06:58:26',0,'2026-03-18 06:58:26'),
(14,125,'8c392bd8e6b9af0ef562a5715f7a83d6d940aa84a570889d866ac7ab4fe5a387','2026-04-17 07:22:38',0,'2026-03-18 07:22:38'),
(15,125,'ba3d1979b4df3ebb36afebb1e81a4ea612384613dd07ee7b3a359b46488c24e7','2026-04-17 07:28:26',0,'2026-03-18 07:28:26'),
(16,125,'5be8712b1a3d5c7e000e7274160bcb45dc6441371819f5d2fbe410957ff8182d','2026-04-17 07:32:36',0,'2026-03-18 07:32:36'),
(17,125,'9185ad25a307d896c6431b7169a3cfa95685229a1104aa9b2ec45ef16edd9eee','2026-04-17 16:49:04',0,'2026-03-18 16:49:04');

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `remember_tokens` */

insert  into `remember_tokens`(`id`,`user_id`,`token`,`expires_at`,`created_at`) values 
(1,1,'54cf949d68abe6552f20bc8d3f6fd0028d68301d09bcef98cb99ded660b45fc2','2026-04-10 09:58:58','2026-03-11 09:58:58'),
(2,123,'518f8af50aec460d323c17fc0405fbec86070038ce894e49d1c12ab67e6ed1ea','2026-04-10 10:13:24','2026-03-11 10:13:24'),
(3,123,'3bd3884b30efc55ff6cf0d5f5088d03753da7724ca9e34a8111cb70775efbfb1','2026-04-10 10:13:30','2026-03-11 10:13:30'),
(4,123,'cd414f9f7ff2aa82b93be8eaafcf162a2c6f25f0c53d603851b5e74bbd0b2fda','2026-04-10 10:15:12','2026-03-11 10:15:12'),
(5,131,'9fd844b5cb983e3dfe93d2d6905e6cb999633d0e24ec3e7648cdbe0f3dfc29b2','2026-04-13 05:46:35','2026-03-14 05:46:35'),
(6,124,'2494cdec7d0b46ffd4edfe498381756006671299c1d5d2704a5348ff3ed4a00a','2026-04-15 11:52:18','2026-03-16 11:52:18');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `rooms` */

insert  into `rooms`(`id`,`tenant_id`,`name`,`code`,`capacity`,`description`,`is_active`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,'Computer Lab','Com-101',40,'The room here is taken for computer practical ',1,'2026-03-16 11:51:10','2026-03-16 11:51:10',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `student_fee_summary` */

insert  into `student_fee_summary`(`id`,`tenant_id`,`student_id`,`enrollment_id`,`total_fee`,`paid_amount`,`due_amount`,`fee_status`,`created_at`,`updated_at`) values 
(6,3,1,1,5000.00,5000.00,0.00,'paid','2026-03-14 07:37:03','2026-03-14 07:37:03'),
(7,3,2,2,7000.00,7000.00,0.00,'paid','2026-03-14 07:37:03','2026-03-14 07:37:03'),
(8,3,4,3,7000.00,0.00,7000.00,'unpaid','2026-03-14 07:37:03','2026-03-14 07:37:03'),
(9,3,5,4,8000.00,2000.00,6000.00,'partial','2026-03-14 07:37:03','2026-03-14 07:37:03'),
(10,3,6,5,8000.00,1000.00,7000.00,'partial','2026-03-14 07:37:03','2026-03-14 07:37:03'),
(11,3,7,6,8000.00,0.00,8000.00,'unpaid','2026-03-14 08:58:00','2026-03-14 08:58:00'),
(12,3,8,7,8000.00,0.00,8000.00,'unpaid','2026-03-14 09:00:37','2026-03-14 09:00:37'),
(13,3,9,8,8000.00,8000.00,0.00,'paid','2026-03-14 11:03:50','2026-03-14 18:30:51'),
(14,3,10,9,8000.00,0.00,8000.00,'unpaid','2026-03-14 16:38:20','2026-03-14 16:38:20'),
(15,3,11,10,8000.00,1000.00,7000.00,'partial','2026-03-15 06:18:23','2026-03-15 06:18:47'),
(16,3,12,11,8000.00,3000.00,5000.00,'partial','2026-03-16 13:41:45','2026-03-20 06:21:59'),
(18,3,15,15,8000.00,2000.00,6000.00,'partial','2026-03-18 14:35:04','2026-03-18 16:58:46'),
(19,3,16,16,8000.00,1000.00,7000.00,'partial','2026-03-18 17:55:30','2026-03-18 17:56:18'),
(20,3,17,17,8000.00,1000.00,7000.00,'partial','2026-03-19 13:42:39','2026-03-19 13:44:41'),
(21,3,18,18,8000.00,0.00,8000.00,'unpaid','2026-03-19 20:29:28','2026-03-19 20:29:28'),
(22,3,19,19,8000.00,4000.00,4000.00,'partial','2026-03-19 20:36:16','2026-03-20 06:53:31'),
(23,3,20,20,8000.00,500.00,7500.00,'partial','2026-03-20 07:00:37','2026-03-20 07:01:25');

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
  `status` enum('active','inactive','on-leave','graduated','alumni','dropped') NOT NULL DEFAULT 'active',
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `students` */

insert  into `students`(`id`,`tenant_id`,`user_id`,`roll_no`,`dob_bs`,`dob_ad`,`gender`,`blood_group`,`citizenship_no`,`national_id`,`father_name`,`mother_name`,`husband_name`,`guardian_name`,`guardian_relation`,`permanent_address`,`temporary_address`,`academic_qualifications`,`admission_date`,`photo_url`,`identity_doc_url`,`status`,`id_card_status`,`id_card_issued_at`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,124,'STD-2026-0001','2063-09-05',NULL,'female','A+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-11 10:17:01','2026-03-15 08:17:27','2026-03-11 15:39:37'),
(2,3,126,'STD-2026-0002','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-11 11:26:22','2026-03-11 14:21:34','2026-03-11 14:21:34'),
(4,3,123,'STD-2026-0003','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-12 07:06:12','2026-03-14 08:56:59','2026-03-14 08:56:59'),
(5,3,129,'STD-2026-0005','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-12 09:41:44','2026-03-14 08:56:35','2026-03-14 08:56:35'),
(6,3,129,'STD-2026-0006','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Birgunj\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-14 06:00:06','2026-03-14 08:56:31','2026-03-14 08:56:31'),
(7,3,132,'STD-2026-0007','2063-09-05',NULL,'female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-14 08:58:00','2026-03-14 08:59:52','2026-03-14 08:59:52'),
(8,3,131,'STD-2026-0008','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-14 09:00:37','2026-03-14 09:01:44','2026-03-14 09:01:44'),
(9,3,133,'STD-2026-0009','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-14 11:03:50','2026-03-15 07:07:33','2026-03-15 07:07:33'),
(10,3,134,'STD-2026-0010','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-14 16:38:20','2026-03-14 18:11:43','2026-03-14 18:11:43'),
(11,3,135,'STD-2026-0011','2052-09-17',NULL,'female',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-15 06:18:23','2026-03-18 14:34:16','2026-03-18 14:34:16'),
(12,3,136,'STD-2026-0012','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-16 13:41:45','2026-03-20 06:54:44','2026-03-20 06:54:44'),
(13,3,137,'STD-2026-0013','2060-01-01',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-16',NULL,NULL,'active','none',NULL,'2026-03-16 13:51:41','2026-03-16 17:45:43','2026-03-16 17:45:43'),
(15,3,140,'STD-2026-0014','2063-09-05',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-18 14:35:04','2026-03-20 06:54:44','2026-03-20 06:54:44'),
(16,3,141,'STD-2026-0016','2052-09-17',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-18 17:55:30','2026-03-20 06:54:44','2026-03-20 06:54:44'),
(17,3,142,'STD-2026-0017','2063-09-12',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-19 13:42:39','2026-03-20 06:54:44','2026-03-20 06:54:44'),
(18,3,143,'STD-2026-0018','2063-09-12',NULL,'male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-19 20:29:28','2026-03-19 20:35:11','2026-03-19 20:35:11'),
(19,3,144,'STD-2026-0019','2063-09-05','2006-12-20','male','','','','','','','','','{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,'[{\"level\":\"+2\",\"school\":\"A Plus academy\",\"year\":\"2079\",\"percentage\":\"B+\"}]',NULL,NULL,NULL,'active','none',NULL,'2026-03-19 20:36:16','2026-03-20 06:54:44','2026-03-20 06:54:44'),
(20,3,145,'STD-2026-0020','2063-09-12','2006-12-27','male',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"address\":\"Hamro Labs ,No. 13, Radhemai, Birgunj Metropolitan City Parsa District, Madhesh Province, Nepal Postal Code: 44300\"}',NULL,NULL,NULL,NULL,NULL,'active','none',NULL,'2026-03-20 07:00:37','2026-03-20 07:00:37',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `study_materials` */

insert  into `study_materials`(`id`,`tenant_id`,`category_id`,`is_qbank`,`title`,`description`,`file_name`,`file_path`,`file_type`,`file_size`,`file_extension`,`external_url`,`content_type`,`access_type`,`visibility`,`course_id`,`batch_id`,`subject_id`,`tags`,`download_count`,`view_count`,`status`,`is_featured`,`sort_order`,`published_at`,`expires_at`,`created_by`,`updated_by`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,NULL,1,'gdrhhrrhd','','2024020706362365c324e776b76.pdf','C:\\Apache24\\htdocs\\erp\\public\\uploads\\study_materials\\3\\978aa956cd61eae1a05f653cb2702632.pdf','application/pdf',291329,'pdf',NULL,'file','public','all',NULL,NULL,NULL,NULL,0,0,'active',0,0,'2026-03-11 16:08:42',NULL,123,NULL,'2026-03-11 16:08:43','2026-03-11 16:08:43',NULL);

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
(1,3,'Computer basics ','101','Computer basic\n','active','2026-03-15 08:42:01','2026-03-15 08:42:01',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `teachers` */

insert  into `teachers`(`id`,`tenant_id`,`user_id`,`employee_id`,`full_name`,`phone`,`email`,`qualification`,`specialization`,`joined_date`,`monthly_salary`,`leave_balance`,`status`,`created_at`,`updated_at`,`deleted_at`) values 
(1,3,125,'101','Nepal Cyber Firm','9811144402','mind59024@gmail.com',NULL,'GK ,IQ','2026-03-11',0.00,0,'active','2026-03-11 10:33:37','2026-03-11 10:33:37',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `tenant_email_settings` */

insert  into `tenant_email_settings`(`id`,`tenant_id`,`sender_name`,`reply_to_email`,`from_name`,`from_email`,`smtp_host`,`smtp_port`,`smtp_encryption`,`smtp_username`,`smtp_password`,`is_active`,`created_at`,`updated_at`) values 
(1,3,'Hamro Loksewa Institute','nepalcyberfirm@gmail.com',NULL,NULL,'smtp.gmail.com',587,'tls','nepalcyberfirm@gmail.com','4C+YRsuVxa0OoSRG7h09GHpJb00rb2VmU1QreitPRUk3dTM0eTNmaGZPcElPVmxPdEhGTzhZNFdwS1E9',1,'2026-03-14 15:01:24','2026-03-14 15:01:24');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tenants` */

insert  into `tenants`(`id`,`name`,`nepali_name`,`subdomain`,`brand_color`,`tagline`,`logo_path`,`phone`,`email`,`pan_number`,`website`,`address`,`institute_type`,`province`,`pan_no`,`plan`,`status`,`created_by`,`student_limit`,`sms_credits`,`trial_ends_at`,`created_at`,`updated_at`,`deleted_at`,`settings`) values 
(3,'Hamro Loksewa institute','हाम्रो लोकसेवा ईस्टिट्युट','hamroloksewa','#009e7e','Love you','/public/uploads/logos/tenant_3_1773207024.png','+9779811144402','nepalcyberfirm@gmail.com',NULL,'','Birgunj-13,Radhemai',NULL,NULL,'','growth','active',1,100,500,NULL,'2026-03-11 10:10:40','2026-03-12 06:33:51',NULL,'{\"finance\":{\"invoice_prefix\":\"INV\",\"receipt_prefix\":\"RCP\",\"next_invoice_number\":1,\"next_receipt_number\":17,\"auto_generate_invoice\":1,\"send_invoice_email\":1,\"apply_late_fine\":1,\"late_fine_grace_days\":5,\"currency\":\"NPR\"}}');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `timetable_slots` */

insert  into `timetable_slots`(`id`,`tenant_id`,`batch_id`,`teacher_id`,`subject_id`,`room_id`,`day_of_week`,`start_time`,`end_time`,`room`,`online_link`,`class_type`,`created_at`,`updated_at`) values 
(1,3,1,1,1,1,2,'00:00:00','01:00:00',NULL,'','online','2026-03-16 11:51:49','2026-03-16 11:51:49'),
(2,3,1,1,1,1,3,'09:00:00','10:00:00',NULL,'','offline','2026-03-16 11:53:50','2026-03-16 11:57:21'),
(3,3,1,1,1,1,3,'13:00:00','14:00:00',NULL,'','lab','2026-03-16 11:55:02','2026-03-16 11:55:02'),
(4,3,1,1,1,1,4,'00:15:00','01:00:00',NULL,'','offline','2026-03-18 06:59:57','2026-03-18 06:59:57');

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
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`tenant_id`,`role`,`email`,`password_hash`,`phone`,`status`,`monthly_salary`,`last_login_at`,`two_fa_enabled`,`created_at`,`updated_at`,`deleted_at`,`locked_until`,`avatar`,`name`,`two_factor_enabled`,`two_factor_secret`) values 
(1,NULL,'superadmin','pdewbrath@gmail.com','$2y$12$04j0nPzxsEZUNJscHMmGrOuq89J0fPAkfXZifa4xJY4xSYuwN3I0C',NULL,'active',0.00,'2026-03-16 19:04:32',0,'2026-02-21 14:02:24','2026-03-16 19:04:32',NULL,NULL,NULL,NULL,0,NULL),
(2,NULL,'superadmin','super2@hamrolabs.com','$2y$12$R.v07Zun1pS.k.v7F.v/Oun8n9Z6E6Z6E6Z6E6Z6E6Z6E6Z6E6Z6',NULL,'active',0.00,NULL,0,'2026-02-21 14:02:24','2026-02-23 14:01:23',NULL,NULL,NULL,NULL,0,NULL),
(123,3,'instituteadmin','nepalcyberfirm@gmail.com','$2y$12$ijvOlLOpxwoe0Gt4IIIsz.hES8sqcDhyOge6yFj2m2leXicr5D3Um','9811144402','active',0.00,'2026-03-19 21:06:32',0,'2026-03-11 10:10:41','2026-03-19 21:06:32','2026-03-14 08:56:59',NULL,NULL,'Devbarat Prasad Patel',0,NULL),
(124,3,'student','addamssmith937@gmail.com','$2y$12$BO.V.geG3x1UJs3tUCTvseGqOLqPWkA8FHOrCiHAmgZrj.mtEvf3q','9811144402','active',0.00,'2026-03-19 14:15:17',0,'2026-03-11 10:17:01','2026-03-19 14:15:17','2026-03-11 15:39:37',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(125,3,'teacher','mind59024@gmail.com','$2y$12$vued2T9rC/G9OUhRdDbD9uaIcqPu8hClzV/sqK5616yKTcafacHgm','9811144402','active',50000.00,'2026-03-18 16:49:04',0,'2026-03-11 10:33:37','2026-03-18 16:49:04',NULL,NULL,NULL,'Nepal Cyber Firm',0,NULL),
(126,3,'student','pdewbrath@gmail.com','$2y$12$K9UnYPenNDSSG6Skseupo.idsE0RhObW8JLn2MXoONNz0Gr5OPbTG','9811144402','active',0.00,NULL,0,'2026-03-11 11:26:22','2026-03-11 14:21:34','2026-03-11 14:21:34',NULL,NULL,'Devbart  ji',0,NULL),
(127,3,'guardian','guardian_e99d0166@temporary.hamrolabs.com','LEGACY_MIGRATED',NULL,'active',0.00,NULL,0,'2026-03-12 06:48:00','2026-03-12 06:48:00',NULL,NULL,NULL,'nepalcyberfirm@gmail.com',0,NULL),
(128,3,'guardian','guardian_413b6e7c@temporary.hamrolabs.com','LEGACY_MIGRATED',NULL,'active',0.00,NULL,0,'2026-03-12 06:48:00','2026-03-12 06:48:00',NULL,NULL,NULL,'nepalcyberfirm@gmail.com',0,NULL),
(129,3,'student','addamssmith937@gmail.com','$2y$12$CO48lleqK69JfwF86WTYi.v1G0Fnai2vMJ1bYdQh7meGUvJTlv9pm','9855044403','active',0.00,NULL,0,'2026-03-12 09:41:44','2026-03-14 08:56:35','2026-03-14 08:56:35',NULL,NULL,'Devbarat Patel',0,NULL),
(130,3,'frontdesk','nepalcodingschool@gmail.com','$2y$12$C1gIjTlpi2uii8wMmav/Guav7w7jY3qA8KQ.8i/B1LafoRgeCvSXi','9811144402','active',5000.00,'2026-03-13 19:05:25',0,'2026-03-13 18:38:47','2026-03-14 05:44:51','2026-03-14 05:44:51',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(131,3,'frontdesk','nepalcodingschool@gmail.com','$2y$12$lrCEH8gQvAfJL5QFIMoJzeJDei6mDsOGtxjDAwZKfWAbOyOofBIzK','9811144402','active',15000.00,'2026-03-18 16:01:13',0,'2026-03-14 05:45:16','2026-03-18 16:01:13','2026-03-14 09:01:44',NULL,NULL,'Ramesh Kumar Sahani ',0,NULL),
(132,3,'student','pdewbrath@gmail.com','$2y$12$ILHbVtC.kSE3DyXm4.TQKeLWPVrh/m78GOCilyrwWM6pm6LIAr3QW','9855044403','active',0.00,NULL,0,'2026-03-14 08:58:00','2026-03-14 08:59:52','2026-03-14 08:59:52',NULL,NULL,'Archana pal',0,NULL),
(133,3,'student','toonmitra355@gmail.com','$2y$12$HLd54CKB6g/dTNnGIsSgEO0sMkVkns1N/l6WCpyp.shYjxzZ0EiJa','9845012350','active',0.00,'2026-03-14 15:06:44',0,'2026-03-14 11:03:50','2026-03-15 07:07:33','2026-03-15 07:07:33',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(134,3,'student','infohamrolabs@gmail.com','$2y$12$VmzR702PrJMIuKsAAevQE.ZOINHgz3WdrqZrg6MrnzKWOP74h.Tki','9855044403','active',0.00,NULL,0,'2026-03-14 16:38:20','2026-03-14 18:11:43','2026-03-14 18:11:43',NULL,NULL,'Devbarat Prasad Patel',0,NULL),
(135,3,'student','addamssmith937@gmail.com','$2y$12$kcNgbcyiziTufcgqYinqXuUpiip7pjmIGqURupI/46.qGWXGu4BQ.','9845012350','active',0.00,NULL,0,'2026-03-15 06:18:23','2026-03-18 14:34:16','2026-03-18 14:34:16',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(136,3,'student','pdewbrath@gmail.com','$2y$12$FKDsy.Kz9kCYeH17cl0inuc0wwc2XrZsjBtxx2uJOp0yhTTTEkrla','9845012350','active',0.00,NULL,0,'2026-03-16 13:41:45','2026-03-20 06:54:44','2026-03-20 06:54:44',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(137,3,'student','test_std_1773648401@example.com','$2y$12$v2x2ovKiFtITq6kbZM/dIeeAxt5aKzw.GP.SmP0YU4Yt/nA/7as2G','9845951232','active',0.00,NULL,0,'2026-03-16 13:51:41','2026-03-16 17:45:43','2026-03-16 17:45:43',NULL,NULL,'Automated Test Student 1773648401',0,NULL),
(139,3,'frontdesk','nepalcodingschool@gmail.com','$2y$12$lbMaiy8CiqssMbaibmnE5eCLH4SQpR0YL1BdTlgL2YBds0NieRZwm','9833344402','active',15000.00,NULL,0,'2026-03-16 14:07:54','2026-03-16 17:54:02',NULL,NULL,NULL,'Ram Kumar yadav ',0,NULL),
(140,3,'student','addamssmith937@gmail.com','$2y$12$1G0OvnLiTCU9pHddB7TIZO7DcB5L3biZoPn3xZkQ04sd279cMaqvm','9845012350','active',0.00,NULL,0,'2026-03-18 14:35:04','2026-03-20 06:54:44','2026-03-20 06:54:44',NULL,NULL,'Devbarat Prasad Patel',0,NULL),
(141,3,'student','aimsinstitutebirgunj@gmail.com','$2y$12$9LLAKPFUfkxBolSjBFIl5exOZ0VjtViz1Uslw2pWtZCVHrW9RYGsi','9825205484','active',0.00,NULL,0,'2026-03-18 17:55:30','2026-03-20 06:54:44','2026-03-20 06:54:44',NULL,NULL,'Ramesh Kuamar',0,NULL),
(142,3,'student','mohammadmishal557@gmail.com','$2y$12$NdY7gQR8quv76.CGoG8T4eoBILaep119/6PKy8uJnfSiAjval6ZDq','9855044403','active',0.00,NULL,0,'2026-03-19 13:42:38','2026-03-20 06:54:44','2026-03-20 06:54:44',NULL,NULL,'Mishal MD.',0,NULL),
(143,3,'student','nepalcyberfirm@gmail.com','$2y$12$sn3n0YJl2lbhZhdXv.vGZOf3vP3ovLcwEXsbjuYKAi17m0BGBApEW','9825205484','active',0.00,NULL,0,'2026-03-19 20:29:28','2026-03-19 20:35:11','2026-03-19 20:35:11',NULL,NULL,'Nepal Cyber Firm',0,NULL),
(144,3,'student','nepalcyberfirm@gmail.com','$2y$12$3H4CunWpH7g9QY5R8vkwTe2.YxwlnnTfMEGcG922rEZ2R/RxyKaIq','9825205184','active',0.00,NULL,0,'2026-03-19 20:36:16','2026-03-20 06:54:44','2026-03-20 06:54:44',NULL,NULL,'Dev',0,NULL),
(145,3,'student','pdewbrath@gmail.com','$2y$12$JHzcqSjs2hB3ZSeIHNU6w.bcRnzC7wOKws34Bj5GleVNNq7h1VrAC','9845012350','active',0.00,NULL,0,'2026-03-20 07:00:37','2026-03-20 07:00:37',NULL,NULL,NULL,'Devbarat Prasad Patel',0,NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
