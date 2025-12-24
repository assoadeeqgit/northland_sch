-- ============================================================================
-- UNIFIED DATABASE MIGRATION SCRIPT
-- Purpose: Merge teacher dashboard and admin dashboard into one database
-- Database: northland_schools_kano
-- Created: 2025-12-07
-- ============================================================================

-- Drop existing database if you want a fresh start (UNCOMMENT IF NEEDED)
-- DROP DATABASE IF EXISTS northland_schools_kano;
-- CREATE DATABASE northland_schools_kano CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- USE northland_schools_kano;

USE northland_schools_kano;

-- Disable foreign key checks for smooth migration
SET FOREIGN_KEY_CHECKS=0;

-- ============================================================================
-- ENHANCED RESULTS TABLE (from teacher dashboard)
-- ============================================================================

-- Drop old results table
DROP TABLE IF EXISTS `results`;

-- Create enhanced results table with all features from both dashboards
CREATE TABLE `results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `class_id` int NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `marks_obtained` decimal(6,2) NOT NULL,
  `total_marks` decimal(6,2) NOT NULL DEFAULT '100.00',
  `percentage` decimal(5,2) GENERATED ALWAYS AS ((`marks_obtained` / `total_marks`) * 100) STORED,
  `grade` varchar(5) DEFAULT NULL,
  `grade_point` decimal(3,2) DEFAULT NULL,
  `position_in_class` int DEFAULT NULL,
  `position_in_subject` int DEFAULT NULL,
  `remarks` text,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_exam_subject` (`student_id`,`exam_id`,`subject_id`),
  KEY `exam_id` (`exam_id`),
  KEY `subject_id` (`subject_id`),
  KEY `academic_session_id` (`academic_session_id`),
  KEY `term_id` (`term_id`),
  KEY `class_id` (`class_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_results_student_exam` (`student_id`,`exam_id`),
  KEY `idx_results_class_subject` (`class_id`,`subject_id`),
  CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_6` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_7` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- ENHANCED ASSIGNMENTS TABLE
-- ============================================================================

-- Ensure assignments table has submission tracking (add columns if they don't exist)
-- Note: These ALTER statements will fail silently if columns already exist
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE table_name = 'assignments'
     AND table_schema = 'northland_schools_kano'
     AND column_name = 'status') > 0,
    'SELECT 1',
    'ALTER TABLE `assignments` ADD COLUMN `status` enum(\'Draft\',\'Published\',\'Closed\') DEFAULT \'Published\''
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE table_name = 'assignments'
     AND table_schema = 'northland_schools_kano'
     AND column_name = 'attachment_path') > 0,
    'SELECT 1',
    'ALTER TABLE `assignments` ADD COLUMN `attachment_path` varchar(255) DEFAULT NULL'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE table_name = 'assignments'
     AND table_schema = 'northland_schools_kano'
     AND column_name = 'updated_at') > 0,
    'SELECT 1',
    'ALTER TABLE `assignments` ADD COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Create assignment submissions table if it doesn't exist
CREATE TABLE IF NOT EXISTS `assignment_submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `submission_text` text,
  `attachment_path` varchar(255) DEFAULT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `feedback` text,
  `graded_by` int DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `status` enum('Submitted','Late','Graded','Returned') DEFAULT 'Submitted',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment_student` (`assignment_id`,`student_id`),
  KEY `student_id` (`student_id`),
  KEY `graded_by` (`graded_by`),
  CONSTRAINT `assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_submissions_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- ENHANCED GENERATED REPORTS TABLE (from teacher dashboard)
-- ============================================================================

-- Ensure generated_reports table exists with all  necessary columns
CREATE TABLE IF NOT EXISTS `generated_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `generated_by` varchar(100) DEFAULT NULL,
  `download_count` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- ENHANCED REPORT SCHEDULES TABLE (from teacher dashboard)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- Re-enable foreign key checks
-- ============================================================================

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

SELECT 'Database migration completed successfully!' AS Status;
SELECT COUNT(*) AS total_tables FROM information_schema.tables WHERE table_schema = 'northland_schools_kano';
