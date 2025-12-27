-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: northland_schools_kano
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_sessions`
--

DROP TABLE IF EXISTS `academic_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_sessions`
--

LOCK TABLES `academic_sessions` WRITE;
/*!40000 ALTER TABLE `academic_sessions` DISABLE KEYS */;
INSERT INTO `academic_sessions` VALUES (1,'2023/2024','2023-09-01','2024-08-31',0,'2025-10-23 11:37:21'),(2,'2024/2025','2024-09-01','2025-08-31',1,'2025-12-22 14:25:54');
/*!40000 ALTER TABLE `academic_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'Abubakar Muhammad','New Student','Added student: ABUBAKAR AHMAD (STU7622)','fas fa-user-plus','bg-nsklightblue','2025-11-02 15:34:59'),(2,'Abubakar Muhammad','Teacher Updated','Updated profile for: Aisha Bello (TCH001)','fas fa-user-edit','bg-nskgold','2025-11-02 15:47:30'),(3,'Abubakar Muhammad','Teacher Updated','Updated profile for: Aisha Bello (TCH001)','fas fa-user-edit','bg-nskgold','2025-11-02 15:49:05'),(4,'Abubakar Muhammad','Update Student','Updated student: ABUBAKAR AHMAD (STU4260)','fas fa-user-edit','bg-nskgold','2025-11-02 16:40:28'),(5,'Admin','Update Assignment','Updated subject assignment: English Language to JSS 3 (Changes: class from JSS 1 to JSS 3, teacher from ABUBAKAR AHMAD to Aisha Bello)','fas fa-edit','bg-nskgold','2025-11-02 17:14:40'),(6,'Admin','Update Assignment','Updated subject assignment: Islamic Religious Studies to JSS 3 (Changes: class from JSS 1 to JSS 3, teacher from ABUBAKAR AHMAD to Aisha Bello)','fas fa-edit','bg-nskgold','2025-11-02 17:15:24');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_profiles`
--

DROP TABLE IF EXISTS `admin_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `admin_level` enum('Super Admin','Admin','Sub-Admin') DEFAULT NULL,
  `department_access` varchar(255) DEFAULT NULL,
  `special_permissions` text,
  `admin_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_profiles`
--

LOCK TABLES `admin_profiles` WRITE;
/*!40000 ALTER TABLE `admin_profiles` DISABLE KEYS */;
INSERT INTO `admin_profiles` VALUES (1,9,NULL,NULL,NULL,'ADM009','2025-10-28 08:52:04');
/*!40000 ALTER TABLE `admin_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `due_date` date NOT NULL,
  `total_points` int DEFAULT '100',
  `type` enum('Quiz','Homework','Project','Exam','Worksheet') DEFAULT 'Homework',
  `allow_late_submission` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text,
  `recorded_by` int NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_attendance` (`student_id`,`attendance_date`),
  KEY `recorded_by` (`recorded_by`),
  KEY `academic_session_id` (`academic_session_id`),
  KEY `term_id` (`term_id`),
  KEY `idx_attendance_student_date` (`student_id`,`attendance_date`),
  KEY `idx_attendance_class_date` (`class_id`,`attendance_date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_audit_log`
--

DROP TABLE IF EXISTS `auth_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `event_type` enum('login','logout','registration','password_reset','failed_login','account_locked') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_auth_audit_user` (`user_id`),
  KEY `idx_auth_audit_event` (`event_type`),
  KEY `idx_auth_audit_created` (`created_at`),
  CONSTRAINT `auth_audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_audit_log`
--

LOCK TABLES `auth_audit_log` WRITE;
/*!40000 ALTER TABLE `auth_audit_log` DISABLE KEYS */;
INSERT INTO `auth_audit_log` VALUES (1,NULL,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:40:25\"}','2025-10-27 16:40:25'),(2,NULL,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:40:35\"}','2025-10-27 16:40:35'),(3,NULL,'registration','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:41:54\"}','2025-10-27 16:41:54'),(4,NULL,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:42:08\"}','2025-10-27 16:42:08'),(5,7,'registration','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:43:25\"}','2025-10-27 16:43:25'),(6,7,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:43:36\"}','2025-10-27 16:43:36'),(7,8,'registration','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:51:49\"}','2025-10-27 16:51:49'),(8,8,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 16:52:09\"}','2025-10-27 16:52:09'),(9,7,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 18:15:08\"}','2025-10-27 18:15:08'),(10,7,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-27 18:22:43\"}','2025-10-27 18:22:43'),(11,9,'registration','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:52:04\"}','2025-10-28 08:52:04'),(12,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:52:21\"}','2025-10-28 08:52:21'),(13,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:53:01\"}','2025-10-28 08:53:01'),(14,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:53:31\"}','2025-10-28 08:53:31'),(15,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:53:54\"}','2025-10-28 08:53:54'),(16,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:54:09\"}','2025-10-28 08:54:09'),(17,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:54:37\"}','2025-10-28 08:54:37'),(18,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 08:55:25\"}','2025-10-28 08:55:25'),(19,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 09:00:31\"}','2025-10-28 09:00:31'),(20,9,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','{\"timestamp\":\"2025-10-28 09:00:50\"}','2025-10-28 09:00:50'),(21,38,'registration','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-22 14:07:23\"}','2025-12-22 14:07:23'),(22,38,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-22 14:08:07\"}','2025-12-22 14:08:07'),(23,38,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-22 14:08:37\"}','2025-12-22 14:08:37'),(24,38,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-22 14:09:41\"}','2025-12-22 14:09:41'),(25,38,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-22 14:09:42\"}','2025-12-22 14:09:42'),(26,39,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:17:54\"}','2025-12-24 15:17:54'),(27,38,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:21:45\"}','2025-12-24 15:21:45'),(28,39,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:32:43\"}','2025-12-24 15:32:43'),(29,1,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:37:03\",\"reason\":\"Incorrect password\"}','2025-12-24 15:37:03'),(30,1,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:37:14\",\"reason\":\"Incorrect password\"}','2025-12-24 15:37:14'),(31,1,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:37:19\",\"reason\":\"Incorrect password\"}','2025-12-24 15:37:19'),(32,2,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:38:13\",\"reason\":\"Incorrect password\"}','2025-12-24 15:38:13'),(33,2,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:39:32\",\"reason\":\"Incorrect password\"}','2025-12-24 15:39:32'),(34,2,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:39:41\",\"reason\":\"Incorrect password\"}','2025-12-24 15:39:41'),(35,NULL,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:40:33\",\"reason\":\"User not found\"}','2025-12-24 15:40:33'),(36,NULL,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:40:51\",\"reason\":\"User not found\"}','2025-12-24 15:40:51'),(37,1,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:42:32\",\"reason\":\"Incorrect password\"}','2025-12-24 15:42:32'),(38,1,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:42:42\",\"reason\":\"Incorrect password\"}','2025-12-24 15:42:42'),(39,NULL,'failed_login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:43:19\",\"reason\":\"User not found\"}','2025-12-24 15:43:19'),(40,1,'login','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','{\"timestamp\":\"2025-12-24 15:44:28\"}','2025-12-24 15:44:28'),(41,38,'login','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','{\"timestamp\":\"2025-12-24 16:29:07\"}','2025-12-24 16:29:07'),(42,1,'failed_login','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','{\"timestamp\":\"2025-12-24 16:30:31\",\"reason\":\"Incorrect password\"}','2025-12-24 16:30:31'),(43,1,'login','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','{\"timestamp\":\"2025-12-24 16:30:38\"}','2025-12-24 16:30:38'),(44,39,'login','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','{\"timestamp\":\"2025-12-24 16:51:28\"}','2025-12-24 16:51:28'),(45,38,'login','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','{\"timestamp\":\"2025-12-24 17:23:34\"}','2025-12-24 17:23:34');
/*!40000 ALTER TABLE `auth_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_borrowing`
--

DROP TABLE IF EXISTS `book_borrowing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_borrowing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` int NOT NULL,
  `student_id` int NOT NULL,
  `borrowed_date` date NOT NULL,
  `due_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `fine_amount` decimal(8,2) DEFAULT '0.00',
  `status` enum('Borrowed','Returned','Overdue') DEFAULT 'Borrowed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `idx_book_borrowing_student_status` (`student_id`,`status`),
  KEY `idx_book_borrowing_due_date` (`due_date`),
  CONSTRAINT `book_borrowing_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `book_borrowing_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_borrowing`
--

LOCK TABLES `book_borrowing` WRITE;
/*!40000 ALTER TABLE `book_borrowing` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_borrowing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `class_subjects`
--

DROP TABLE IF EXISTS `class_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `is_compulsory` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_subjects`
--

LOCK TABLES `class_subjects` WRITE;
/*!40000 ALTER TABLE `class_subjects` DISABLE KEYS */;
INSERT INTO `class_subjects` VALUES (2,5,1,1,1,'2025-11-02 11:53:04'),(3,5,2,2,1,'2025-11-02 11:53:04'),(4,5,11,1,0,'2025-11-02 11:53:04'),(5,12,1,1,1,'2025-11-02 11:53:04'),(6,10,2,2,1,'2025-11-02 11:53:04'),(8,12,9,1,1,'2025-11-02 11:53:04'),(9,13,1,3,1,'2025-11-02 11:53:04'),(10,13,2,2,1,'2025-11-02 11:53:04'),(11,13,4,2,1,'2025-11-02 11:53:04'),(12,13,5,2,1,'2025-11-02 11:53:04'),(13,13,7,1,0,'2025-11-02 11:53:04'),(14,11,3,1,1,'2025-11-02 14:50:53'),(15,4,11,1,1,'2025-11-02 14:51:40');
/*!40000 ALTER TABLE `class_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_teacher_id` int DEFAULT NULL,
  `capacity` int DEFAULT '40',
  `class_level` varchar(255) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_code` (`class_code`),
  KEY `class_teacher_id` (`class_teacher_id`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'Garden (Age 2-3)','GARDEN',NULL,20,'Early Childhood','2025-10-28 09:41:24'),(2,'Pre-Nursery (Age 3-4)','PRE-NUR',NULL,20,'Early Childhood','2025-10-28 09:41:24'),(3,'Nursery 1 (Age 4-5)','NUR1',NULL,25,'Early Childhood','2025-10-28 09:41:24'),(4,'Nursery 2 (Age 5-6)','NUR2',NULL,25,'Early Childhood','2025-10-28 09:41:24'),(5,'Primary 1','P1',NULL,30,'Primary','2025-10-28 09:41:24'),(6,'Primary 2','P2',NULL,30,'Primary','2025-10-28 09:41:24'),(7,'Primary 3','P3',NULL,30,'Primary','2025-10-28 09:41:24'),(8,'Primary 4','P4',NULL,30,'Primary','2025-10-28 09:41:24'),(9,'Primary 5','P5',NULL,30,'Primary','2025-10-28 09:41:24'),(10,'JSS 1','JSS1',NULL,35,'Secondary','2025-10-28 09:41:24'),(11,'JSS 2','JSS2',NULL,35,'Secondary','2025-10-28 09:41:24'),(12,'JSS 3','JSS3',NULL,35,'Secondary','2025-10-28 09:41:24'),(13,'SS 1','SS1',NULL,30,'Secondary','2025-10-28 09:41:24'),(14,'SS 2','SS2',NULL,30,'Secondary','2025-10-28 09:41:24'),(15,'SS 3','SS3',NULL,30,'Secondary','2025-10-28 09:41:24');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_title` varchar(255) NOT NULL,
  `event_description` text,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `target_audience` enum('All','Students','Teachers','Parents','Staff') DEFAULT 'All',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_types`
--

DROP TABLE IF EXISTS `exam_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_name` varchar(100) NOT NULL,
  `exam_code` varchar(20) NOT NULL,
  `weightage` decimal(5,2) DEFAULT '100.00',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_code` (`exam_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_types`
--

LOCK TABLES `exam_types` WRITE;
/*!40000 ALTER TABLE `exam_types` DISABLE KEYS */;
INSERT INTO `exam_types` VALUES (1,'First Term Examination','FTE',30.00,'First term comprehensive examination','2025-10-23 11:37:21'),(2,'Second Term Examination','STE',30.00,'Second term comprehensive examination','2025-10-23 11:37:21'),(3,'Third Term Examination','TTE',40.00,'Third term comprehensive examination','2025-10-23 11:37:21');
/*!40000 ALTER TABLE `exam_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_type_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `exam_date` date NOT NULL,
  `total_marks` decimal(6,2) NOT NULL,
  `passing_marks` decimal(6,2) NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_type_id` (`exam_type_id`),
  KEY `class_id` (`class_id`),
  KEY `subject_id` (`subject_id`),
  KEY `academic_session_id` (`academic_session_id`),
  KEY `term_id` (`term_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `category` enum('salary','utilities','maintenance','supplies','other') DEFAULT 'other',
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'Test Expense','other',100.00,'2025-12-22','pending',NULL,1,'2025-12-22 13:41:45');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structure`
--

DROP TABLE IF EXISTS `fee_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `academic_session_id` (`academic_session_id`),
  KEY `term_id` (`term_id`),
  CONSTRAINT `fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structure_ibfk_2` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structure_ibfk_3` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure`
--

LOCK TABLES `fee_structure` WRITE;
/*!40000 ALTER TABLE `fee_structure` DISABLE KEYS */;
INSERT INTO `fee_structure` VALUES (1,10,'Tuition',45000.00,2,4,NULL,0,'2025-12-22 14:25:54'),(2,10,'Development Levy',5000.00,2,4,NULL,0,'2025-12-22 14:25:54'),(3,10,'Tuition',45000.00,2,5,NULL,1,'2025-12-22 14:25:54'),(4,13,'Tuition',55000.00,2,4,NULL,1,'2025-12-22 14:25:54'),(5,13,'Development Levy',5000.00,2,4,NULL,1,'2025-12-22 14:25:54'),(6,9,'tution fee',5000.00,2,5,'2025-12-31',1,'2025-12-23 21:40:17');
/*!40000 ALTER TABLE `fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generated_reports`
--

DROP TABLE IF EXISTS `generated_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `generated_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `generated_by` varchar(255) NOT NULL,
  `generated_date` datetime NOT NULL,
  `download_count` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generated_reports`
--

LOCK TABLES `generated_reports` WRITE;
/*!40000 ALTER TABLE `generated_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `generated_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int NOT NULL,
  `min_quantity` int DEFAULT '5',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_books`
--

DROP TABLE IF EXISTS `library_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_books`
--

LOCK TABLES `library_books` WRITE;
/*!40000 ALTER TABLE `library_books` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notices`
--

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('All','Students','Teachers','Parents','Staff') DEFAULT 'All',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `publish_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notices`
--

LOCK TABLES `notices` WRITE;
/*!40000 ALTER TABLE `notices` DISABLE KEYS */;
/*!40000 ALTER TABLE `notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `fee_structure_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','POS','Online') DEFAULT 'Cash',
  `transaction_id` varchar(100) DEFAULT NULL,
  `received_by` int NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fee_structure_id` (`fee_structure_id`),
  KEY `received_by` (`received_by`),
  KEY `academic_session_id` (`academic_session_id`),
  KEY `term_id` (`term_id`),
  KEY `idx_payments_student_date` (`student_id`,`payment_date`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,304,3,10000.00,'2025-12-23','Cash',NULL,1,2,1,'','2025-12-23 12:17:35'),(2,304,1,5000.00,'2025-12-23','Cash',NULL,1,2,3,'','2025-12-23 21:12:23'),(3,303,6,5000.00,'2025-12-23','Cash',NULL,1,2,3,'aaa','2025-12-23 21:41:00'),(5,303,6,5000.00,'2025-12-23','Cash',NULL,1,2,3,'12346','2025-12-23 22:50:21');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_schedules`
--

DROP TABLE IF EXISTS `report_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_schedules`
--

LOCK TABLES `report_schedules` WRITE;
/*!40000 ALTER TABLE `report_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `exam_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `marks_obtained` decimal(6,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_exam_subject` (`student_id`,`exam_id`,`subject_id`),
  KEY `exam_id` (`exam_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_results_student_exam` (`student_id`,`exam_id`),
  CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `results_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results`
--

LOCK TABLES `results` WRITE;
/*!40000 ALTER TABLE `results` DISABLE KEYS */;
/*!40000 ALTER TABLE `results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `principal_id` int DEFAULT NULL,
  `established_year` year DEFAULT NULL,
  `motto` text,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_code` (`school_code`),
  KEY `principal_id` (`principal_id`),
  CONSTRAINT `schools_ibfk_1` FOREIGN KEY (`principal_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schools`
--

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
INSERT INTO `schools` VALUES (1,'NLSK001','Northland Schools Kano','No. 123 Education Road, Kano State, Nigeria','08001234567','info@northland.edu.ng',2,1995,'Excellence in Education',NULL,'2025-10-23 11:37:21');
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'school_name','Northland Schools Kano','Official school name','2025-11-04 09:53:03'),(2,'school_address','No. 123 Education Road, Kano State, Nigeria','School physical address','2025-10-23 11:37:21'),(3,'school_phone','08001234567','School contact phone','2025-10-23 11:37:21'),(4,'school_email','info@northland.edu.ng','School email address','2025-11-04 23:09:56'),(5,'attendance_threshold','75','Minimum attendance percentage required','2025-10-23 11:37:21'),(6,'late_threshold_minutes','15','Minutes after which student is marked late','2025-10-23 11:37:21');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract') DEFAULT NULL,
  `supervisor` varchar(100) DEFAULT NULL,
  `staff_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_profiles`
--

LOCK TABLES `staff_profiles` WRITE;
/*!40000 ALTER TABLE `staff_profiles` DISABLE KEYS */;
INSERT INTO `staff_profiles` VALUES (1,8,'Security','IT Staff','Full-time','Assodeeq','STF008','2025-10-27 16:51:49'),(2,38,'Bursary','Accountant','Full-time',NULL,'ACT038','2025-12-22 14:07:23'),(3,39,'Bursary','Accountant','Full-time',NULL,'STF039','2025-12-24 14:34:48');
/*!40000 ALTER TABLE `staff_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `class_level` varchar(50) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `medical_info` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
-- Updated: 2025-12-27 - Added status and graduation_date columns for graduation system
-- - status: enum('active','graduated','transferred','withdrawn') - Tracks student enrollment status
-- - graduation_date: date - Records when student graduated (NULL for non-graduated students)
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `class_id` int NOT NULL,
  `status` enum('active','graduated','transferred','withdrawn') NOT NULL DEFAULT 'active',
  `graduation_date` date DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `admission_date` date NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Nigerian',
  `state_of_origin` varchar(50) DEFAULT NULL,
  `lga` varchar(50) DEFAULT NULL,
  `medical_conditions` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `admission_number` (`admission_number`),
  KEY `idx_students_class_id` (`class_id`),
  KEY `idx_students_parent_id` (`parent_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `students_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (302,20,'STU5888','ADM8694',6,NULL,'2025-10-29','Christianity','Nigerian','Kano','tarauni','Null','ABUBAKAR SAAD AHMAD','09090909090','2025-10-29 10:17:44'),(303,21,'STU6153','ADM9777',9,NULL,'2025-10-29','Christianity','Nigerian','Kano','tarauni','','','','2025-10-29 10:53:39'),(304,23,'STU2801','ADM3568',10,NULL,'2025-10-29','Islam','Nigerian','Kano','kumbotso','cough','','','2025-10-29 10:57:32'),(305,24,'STU4260','ADM4588',1,NULL,'2025-10-29','Islam','Nigerian','Kano','tarauni','','','','2025-10-29 11:11:54'),(307,26,'STU5519','ADM3759',10,NULL,'2025-10-31','Christianity','Nigerian','Kano','kumbotso','broke','Auta','09090909090','2025-10-31 14:47:42'),(308,27,'STU3728','ADM9072',15,NULL,'2025-11-02','Christianity','Nigerian','Kebbi','doguwa','','Odugo','09088777776','2025-11-02 10:35:49'),(309,32,'STU2594','ADM2957',1,NULL,'2025-11-02','Islam','Nigerian','Kano','','','','','2025-11-02 15:27:07'),(310,33,'STU6053','ADM8671',1,NULL,'2025-11-02','Islam','Nigerian','Kano','','','','','2025-11-02 15:30:59'),(311,34,'STU7622','ADM3901',2,NULL,'2025-11-02','Islam','Nigerian','Kano','','','','','2025-11-02 15:34:59'),(312,35,'STU005','ADM005',10,NULL,'2023-09-01',NULL,'Nigerian',NULL,NULL,NULL,NULL,NULL,'2025-11-04 23:33:11'),(313,36,'STU006','ADM006',11,NULL,'2023-09-01',NULL,'Nigerian',NULL,NULL,NULL,NULL,NULL,'2025-11-04 23:33:11'),(314,37,'STU007','ADM007',13,NULL,'2023-09-01',NULL,'Nigerian',NULL,NULL,NULL,NULL,NULL,'2025-11-04 23:33:11');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text,
  `category` enum('Core','Elective','Extra-curricular') DEFAULT 'Core',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'ENG','English Language',NULL,'Core',1,'2025-10-23 11:37:21'),(2,'MAT','Mathematics',NULL,'Core',1,'2025-10-23 11:37:21'),(3,'BIO','Biology',NULL,'Core',1,'2025-10-23 11:37:21'),(4,'CHEM','Chemistry',NULL,'Core',1,'2025-10-23 11:37:21'),(5,'PHY','Physics',NULL,'Core',1,'2025-10-23 11:37:21'),(6,'GEO','Geography',NULL,'Core',1,'2025-10-23 11:37:21'),(7,'HIS','History',NULL,'Core',1,'2025-10-23 11:37:21'),(8,'CRS','Christian Religious Studies',NULL,'Core',1,'2025-10-23 11:37:21'),(9,'IRS','Islamic Religious Studies',NULL,'Core',1,'2025-10-23 11:37:21'),(10,'FRN','French',NULL,'Elective',1,'2025-10-23 11:37:21'),(11,'MUS','Music',NULL,'Extra-curricular',1,'2025-10-23 11:37:21');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_profiles`
--

DROP TABLE IF EXISTS `teacher_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `subject_specialization` varchar(255) DEFAULT NULL,
  `years_experience` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract') DEFAULT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `teacher_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_profiles`
--

LOCK TABLES `teacher_profiles` WRITE;
/*!40000 ALTER TABLE `teacher_profiles` DISABLE KEYS */;
INSERT INTO `teacher_profiles` VALUES (1,7,'Degree','Math','3-5 years','Arts','Part-time','TCH007','2025-10-27 16:43:25'),(2,28,'Degree','English Language, Islamic Religious Studies',NULL,'Arts','Part-time','TCH2150','2025-11-02 10:45:24');
/*!40000 ALTER TABLE `teacher_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `employment_date` date DEFAULT NULL,
  `salary_grade` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `is_class_teacher` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,3,'TCH001','<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string ...',NULL,'2020-09-01',NULL,NULL,NULL,0,'2025-10-23 11:37:21'),(2,7,'TCH007','Degree','Math','2025-10-27',NULL,NULL,NULL,0,'2025-10-27 16:43:25'),(3,28,'TCH2150','Degree','English Language, Islamic Religious Studies','2025-11-19',NULL,NULL,NULL,0,'2025-11-02 10:45:24');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_session_id` int NOT NULL,
  `term_name` enum('First Term','Second Term','Third Term') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `academic_session_id` (`academic_session_id`),
  CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms`
--

LOCK TABLES `terms` WRITE;
/*!40000 ALTER TABLE `terms` DISABLE KEYS */;
INSERT INTO `terms` VALUES (1,1,'First Term','2023-09-01','2023-12-15',0,'2025-10-23 11:37:21'),(2,1,'Second Term','2024-01-08','2024-04-05',0,'2025-10-23 11:37:21'),(3,1,'Third Term','2024-04-22','2024-08-02',1,'2025-10-23 11:37:21'),(4,2,'First Term','2024-09-09','2024-12-13',1,'2025-12-22 14:25:54'),(5,2,'Second Term','2025-01-06','2025-04-11',0,'2025-12-22 14:25:54'),(6,2,'Third Term','2025-04-28','2025-07-25',0,'2025-12-22 14:25:54');
/*!40000 ALTER TABLE `terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable`
--

DROP TABLE IF EXISTS `timetable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timetable` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `academic_session_id` (`academic_session_id`),
  KEY `term_id` (`term_id`),
  KEY `idx_timetable_class_day` (`class_id`,`day_of_week`),
  CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetable_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable`
--

LOCK TABLES `timetable` WRITE;
/*!40000 ALTER TABLE `timetable` DISABLE KEYS */;
INSERT INTO `timetable` VALUES (1,5,1,1,'Monday','08:00:00','08:45:00','P1-A',1,3,'2025-11-02 11:26:11'),(2,5,2,2,'Monday','08:45:00','09:30:00','P1-A',1,3,'2025-11-02 11:26:11'),(3,5,11,1,'Monday','09:30:00','10:15:00','Hall',1,3,'2025-11-02 11:26:11'),(4,5,1,1,'Tuesday','08:00:00','08:45:00','P1-A',1,3,'2025-11-02 11:26:11'),(5,5,2,2,'Tuesday','08:45:00','09:30:00','P1-A',1,3,'2025-11-02 11:26:11'),(6,5,1,1,'Wednesday','08:00:00','08:45:00','P1-A',1,3,'2025-11-02 11:26:11'),(7,5,11,1,'Wednesday','09:30:00','10:15:00','Hall',1,3,'2025-11-02 11:26:11'),(8,5,2,2,'Thursday','08:00:00','08:45:00','P1-A',1,3,'2025-11-02 11:26:11'),(9,5,1,1,'Friday','08:00:00','08:45:00','P1-A',1,3,'2025-11-02 11:26:11'),(10,10,2,2,'Monday','08:00:00','08:45:00','JSS1-Hall',1,3,'2025-11-02 11:26:11'),(11,10,1,1,'Monday','08:45:00','09:30:00','JSS1-Hall',1,3,'2025-11-02 11:26:11'),(12,10,3,2,'Monday','09:30:00','10:15:00','Lab A',1,3,'2025-11-02 11:26:11'),(13,10,1,1,'Tuesday','08:00:00','08:45:00','JSS1-Hall',1,3,'2025-11-02 11:26:11'),(14,10,2,2,'Tuesday','08:45:00','09:30:00','JSS1-Hall',1,3,'2025-11-02 11:26:11'),(15,10,3,2,'Wednesday','09:30:00','10:15:00','Lab A',1,3,'2025-11-02 11:26:11'),(16,10,1,1,'Thursday','08:45:00','09:30:00','JSS1-Hall',1,3,'2025-11-02 11:26:11'),(17,10,2,2,'Friday','08:00:00','08:45:00','JSS1-Hall',1,3,'2025-11-02 11:26:11'),(18,13,5,2,'Monday','08:00:00','08:45:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(19,13,2,2,'Monday','08:45:00','09:30:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(20,13,4,2,'Monday','11:00:00','11:45:00','Lab B',1,3,'2025-11-02 11:26:11'),(21,13,1,1,'Tuesday','08:45:00','09:30:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(22,13,3,2,'Tuesday','11:00:00','11:45:00','Lab A',1,3,'2025-11-02 11:26:11'),(23,13,5,2,'Wednesday','08:00:00','08:45:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(24,13,1,1,'Wednesday','08:45:00','09:30:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(25,13,2,2,'Thursday','09:30:00','10:15:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(26,13,5,2,'Friday','11:00:00','11:45:00','SS1-Sci',1,3,'2025-11-02 11:26:11'),(27,8,8,3,'Monday','13:00:00','14:15:00','Pri 4',1,3,'2025-11-02 13:14:35'),(28,6,8,2,'Wednesday','22:30:00','12:30:00','Pri 4',1,3,'2025-11-02 19:28:51');
/*!40000 ALTER TABLE `timetable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `permissions` json NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,'admin','[\"users.manage\", \"students.manage\", \"teachers.manage\", \"finance.manage\", \"reports.view\", \"settings.manage\"]','Full system administrator','2025-10-23 11:37:21'),(2,'principal','[\"students.manage\", \"teachers.manage\", \"reports.view\", \"attendance.manage\"]','School principal with management access','2025-10-23 11:37:21'),(3,'teacher','[\"students.view\", \"attendance.record\", \"results.manage\", \"timetable.view\"]','Teaching staff with academic access','2025-10-23 11:37:21'),(4,'student','[\"profile.view\", \"results.view\", \"attendance.view\", \"timetable.view\"]','Student access to personal information','2025-10-23 11:37:21'),(5,'staff','[\"profile.view\", \"inventory.manage\", \"library.manage\"]','Non-teaching staff access','2025-10-23 11:37:21'),(7,'accountant','[\"finance.manage\", \"reports.view\", \"payments.view\", \"expenses.manage\", \"inventory.manage\", \"profile.view\"]','Accountant with financial management access','2025-12-24 16:43:38');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_sessions_token` (`session_token`),
  KEY `idx_user_sessions_user_id` (`user_id`),
  KEY `idx_user_sessions_expires` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (2,7,'48a2be31d13f3040a0be4a05c863dd9c0d86f26a87360c322e2964301f50079e','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-27 16:43:36','2025-10-28 15:43:36','2025-10-27 16:43:36'),(3,8,'8b0d331cfe3cf0df3c4401f763f02ceb01bb4afccdca0e07c9f6a8746c24a836','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-27 16:52:09','2025-10-28 15:52:09','2025-10-27 16:52:09'),(4,7,'881dc37533d99987a4136bf085e155c047d9c08fb143855c043bf6c42bd8fce0','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-27 18:15:08','2025-10-28 17:15:08','2025-10-27 18:15:08'),(5,7,'d7ead6f21e2c54c88767175774a14e65f434190382914d50be70e7ddd0f8e41b','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-27 18:22:43','2025-10-28 17:22:43','2025-10-27 18:22:43'),(6,9,'99bd09f19b4430051266ff8dc93ba4f9b1a7ebd65b961839aa4e2ef7c3f89d57','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:52:21','2025-10-29 07:52:21','2025-10-28 08:52:21'),(7,9,'2088c5dda19eb55d277793501585c2b0e1a8e09c29edb7d4d98a2fe90c569f8e','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:53:01','2025-10-29 07:53:01','2025-10-28 08:53:01'),(8,9,'329912d482ca7f1507abec55d7df3f712ebd30f6ee3af83ac56819f572512437','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:53:31','2025-10-29 07:53:31','2025-10-28 08:53:31'),(9,9,'72df3a10bef7ff42c0ef4d9ead6581da5e2941fb21b6f51a29132568b6fca1f8','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:53:54','2025-10-29 07:53:54','2025-10-28 08:53:54'),(10,9,'0d4d887c0b293d46c6e8f204004334cec09235ce256597dc879ef2df2ef92499','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:54:09','2025-10-29 07:54:09','2025-10-28 08:54:09'),(11,9,'60c6aab8a5dc66b9903ba052dcbe65aa737accca58cacc7603b2e3b6963f2e45','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:54:37','2025-10-29 07:54:37','2025-10-28 08:54:37'),(12,9,'62b6ec296996673f43ec48d9cf2ca7465cfa15f991b22835a05fa640f08f0255','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 08:55:25','2025-10-29 07:55:25','2025-10-28 08:55:25'),(13,9,'a500abc08b4786994e0a83b66645ab82373f830b57e06a334289ced22bcd5280','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 09:00:31','2025-10-29 08:00:31','2025-10-28 09:00:31'),(14,9,'915e94f555d2b0a14b399df5d3d43e0d782c227b798d0f8947e58a3575bc569d','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-28 09:00:50','2025-10-29 08:00:50','2025-10-28 09:00:50'),(15,38,'22c159f841c37b7693f046f7a003f9340f588073350867fced26e93de0947afe','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-22 14:08:07','2025-12-23 13:08:07','2025-12-22 14:08:07'),(16,38,'79a8d198af8fbd4bad6f8911be87d879daaac1a29c1238b9ae2daf135085f832','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-22 14:08:37','2025-12-23 13:08:37','2025-12-22 14:08:37'),(17,38,'33af610be2c4a1acb72114222d245e148972a4251ea9093d6acb202dde590e8c','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-22 14:09:41','2025-12-23 13:09:41','2025-12-22 14:09:41'),(18,38,'8859ef9fc27aaab2f4f029c4233a9b302ae9288c6959bf4f6551458ab2fccce6','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-22 14:09:42','2025-12-23 13:09:42','2025-12-22 14:09:42'),(19,1,'test_token_1766493560','127.0.0.1','Manual Test','2025-12-23 12:39:20','2025-12-23 13:39:20','2025-12-23 12:39:20'),(20,1,'test_token_1766527689','127.0.0.1','Manual Test','2025-12-23 22:08:09','2025-12-23 23:08:09','2025-12-23 22:08:09'),(21,39,'7ef4a2e372e2a563cc9fb1b79cb6e0486359dc00ded7585a2881e8cc2bd230fe','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-24 15:17:54','2025-12-25 14:17:54','2025-12-24 15:17:54'),(22,38,'e2a22efb884a25514d89e404fdbbfe1966c1845fe36edb79c2da64d763bc874c','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-24 15:21:45','2025-12-25 14:21:45','2025-12-24 15:21:45'),(23,39,'50f1ad6f6740763f425570ed7a8d307fe2c0ec1e9a6d88a60ee2b5e827e00ff6','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-24 15:32:43','2025-12-25 14:32:43','2025-12-24 15:32:43'),(24,1,'c3350934731bf655827e1f1a5f034e157cb6d11774d8e4a6ab10f3399154a347','::1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2025-12-24 15:44:28','2025-12-25 14:44:28','2025-12-24 15:44:28'),(25,38,'437253c52ff609259f9db349b3a0f6c0d5257c670bd949133bf96b17424b1ba6','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','2025-12-24 16:29:07','2025-12-25 15:29:07','2025-12-24 16:29:07'),(27,39,'29ebf6a770ec7925c0f2997d8cefbeaa33b4eaf03672a4cf03e2b71ab9e4435d','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','2025-12-24 16:51:28','2025-12-25 15:51:28','2025-12-24 16:51:28'),(28,38,'c0fb5353c655310eecf790b0ceb165204d9d6184b9bad3935429e198f55c2790','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:146.0) Gecko/20100101 Firefox/146.0','2025-12-24 17:23:34','2025-12-25 16:23:34','2025-12-24 17:23:34');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','teacher','student','staff','principal','accountant') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `registration_step` tinyint DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `registration_data` json DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `login_attempts` tinyint DEFAULT '0',
  `account_locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_user_type` (`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','abdul@northland.edu.ng','$2y$10$d6nPKYjAQg3VxcYQolwJM./lsgCzzV7On8zpOghl3NsqnaObunlxG','admin','Auta','Ahmad','08012345678',NULL,NULL,'Male',NULL,1,'2025-12-24 17:30:38',1,1,NULL,NULL,NULL,NULL,'2025-12-24 17:30:38',0,NULL,'2025-10-23 11:37:21','2025-12-24 16:30:38'),(2,'principal','principal@northland.edu.ng','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','principal','Ahmed','Musa','08023456789',NULL,NULL,'Male',NULL,1,NULL,1,1,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-23 11:37:21','2025-10-23 11:37:21'),(3,'teacher1','teacher1@northland.edu.ng','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','teacher','Aisha','Bello','08034567890',NULL,'2025-11-12','Female',NULL,1,NULL,1,1,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-23 11:37:21','2025-11-02 15:47:30'),(5,'staff1','staff1@northland.edu.ng','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','staff','Hassan','Ibrahim','08056789012',NULL,NULL,'Male',NULL,1,NULL,1,1,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-23 11:37:21','2025-10-23 11:37:21'),(7,'hamza.ali','Hamza@user.com','$2y$10$KDvr7q4xw23JEBy8G9PZwOgpelZqwWigJjsmh9NLD3myz5Y1Nrgg.','teacher','Hamza','Ali','09090909090',NULL,NULL,NULL,NULL,1,'2025-10-28 09:51:09',1,0,NULL,NULL,NULL,NULL,'2025-10-28 09:51:09',0,NULL,'2025-10-27 16:43:25','2025-11-02 13:49:42'),(8,'babba.kira','kira@test.com','$2y$10$JEWnpbX34umFxbVuQZ8ZvuJBlkzAZ4csklyraEpxiLURcTZ1jw7L2','staff','babba','kira','09090909087',NULL,NULL,NULL,NULL,0,'2025-10-27 19:14:51',1,0,NULL,NULL,NULL,NULL,'2025-10-27 19:14:51',0,NULL,'2025-10-27 16:51:49','2025-11-02 13:49:56'),(9,'abubakar.ahmad1','admin@test.com','$2y$10$BuS78X5Qnrc.j/3hc5lErO6h0op6A.o2Vofy8lBJUNTqyrWun6CxK','admin','ABUBAKAR','AHMAD','09090909090',NULL,NULL,NULL,NULL,0,'2025-10-28 10:00:50',1,0,NULL,NULL,NULL,NULL,'2025-10-28 10:00:50',0,NULL,'2025-10-28 08:52:04','2025-11-02 13:49:14'),(20,'abubakar.ahmad552','assssssadiq019@gmail.com','$2y$10$jtaD2yxGqoiaqKV.RHFBROjFWXJ2roeY1tvkV4mK/9Ei7C85w99LW','student','ABUBAKAR','AHMAD','09090909090',NULL,'2025-10-10','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-29 10:17:44','2025-11-02 09:59:52'),(21,'hamza.ahmad','dankwarai@gmail.com','$2y$10$Kd46u8UYzq3NBao2p.0CQeEGJHagcP2SpWBJIin5tSjHHsqQinJUm','student','Abubakar','Muhammad','0909090909',NULL,'2025-10-10','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-29 10:53:39','2025-11-02 14:54:53'),(23,'kira.kira','babba@babba.com','$2y$10$Wf7aY5IkOAgWEs4OxZ.NMOLIKZD/y0diLRtPIV72dvnTdLlZOuNxW','student','kira','kira','0909190909',NULL,'2022-02-28','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-29 10:57:32','2025-10-29 10:57:32'),(24,'abubakar.ahmad892','sabeersulaiman15@gmail.com','$2y$10$3DJGCIZG2R4nZOMboiNgYeF7PzZC.ngZR4iNeFMmKDWAY8W3egChK','student','ABUBAKAR','AHMAD','',NULL,'2025-10-16','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-29 11:11:54','2025-11-02 11:06:47'),(26,'sadiq.bello','bro@test.com','$2y$10$FtGfOJY8IG0OIRU3dO2UkO02/vj/hMVRY4RMxcPyQDlMMaD0iU3Ha','student','sadiq','bello','09090909087',NULL,'2025-10-08','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-10-31 14:47:42','2025-10-31 14:47:42'),(27,'chinyere.odugo','chcichi@gmail.com','$2y$10$TsgBFTT4y0yNyFt4CUVLtedHj6js4yKbdjm0jiJ6L/FN7wN71A2iO','student','Chinyere','Odugo','09089897989',NULL,'2002-12-12','Female',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-02 10:35:49','2025-11-02 10:35:49'),(28,'abubakar.ahmad852','mnassolutions007@gmail.com','$2y$10$mWUvLqdsRejoE44fiCdVSuQCYoQbFYtJfZ5zFPJxGtcHwHPt/5m0m','teacher','ABUBAKAR','AHMAD','0909090909',NULL,'2025-11-21','Male',NULL,0,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-02 10:45:24','2025-11-02 13:49:27'),(30,'abubakar.ahmad650','mnasffsolutions007@gmail.com','$2y$10$vs95DUnUDXBgZyT/w9InluL8x1YeX94rbeB/YI5zqiLo9D0Ipe.Ji','principal','ABUBAKAR','AHMAD','0909090909',NULL,NULL,NULL,NULL,0,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-02 14:02:51','2025-11-02 14:07:28'),(32,'abubakar.ahmad','mnassolutions07v7@gmail.com','$2y$10$bGYlILXOVTTVg.g7qG3faecU1YsjQst3P8AuZLjoO56zGGKfSgiBO','student','ABUBAKAR','AHMAD','09090909090',NULL,'2025-11-06','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-02 15:27:07','2025-11-02 15:27:07'),(33,'abubakar.ahmad462','mnassolutions077@gmail.comss','$2y$10$FvuEk7yfhNeYhwODoVGF3emWCMytVnNGJND6KDEz6VIMo.tHrg4vC','student','ABUBAKAR','AHMAD','09090909090',NULL,'2025-11-14','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-02 15:30:59','2025-11-02 15:30:59'),(34,'abubakar.ahmad648','avsaadsad@user.com','$2y$10$OU2reWEdLbhZ8C3KgI6ig.y.XEmTwZisyD9ivS578bIIgmoY9oHbS','student','ABUBAKAR','AHMAD','0909090909',NULL,'2025-11-14','Male',NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-02 15:34:59','2025-11-02 15:34:59'),(35,'obi.peter','obi@test.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','student','Obi','Peter',NULL,NULL,NULL,NULL,NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-04 23:33:11','2025-11-04 23:33:11'),(36,'nkiru.james','nkiru@test.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','student','Nkiru','James',NULL,NULL,NULL,NULL,NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-04 23:33:11','2025-11-04 23:33:11'),(37,'bola.musa','bola@test.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','student','Bola','Musa',NULL,NULL,NULL,NULL,NULL,1,NULL,1,0,NULL,NULL,NULL,NULL,NULL,0,NULL,'2025-11-04 23:33:11','2025-11-04 23:33:11'),(38,'abubakar .haruna','habu@gmail.com','$2y$10$d6nPKYjAQg3VxcYQolwJM./lsgCzzV7On8zpOghl3NsqnaObunlxG','accountant','Abubakar ','Haruna','09020439378',NULL,NULL,NULL,NULL,1,'2025-12-24 18:23:34',1,0,NULL,NULL,NULL,NULL,'2025-12-24 18:23:34',0,NULL,'2025-12-22 14:07:23','2025-12-24 17:23:34'),(39,'accountant','accountant@northland.edu.ng','$2y$10$BqxahNiex.b1NKcqyu24.eCQonwsMGmt407ZWiiqGTEUtzGi0dyei','accountant','Bursary','Accountant',NULL,NULL,NULL,NULL,NULL,1,'2025-12-24 18:23:07',1,1,NULL,NULL,NULL,NULL,'2025-12-24 18:23:07',0,NULL,'2025-12-24 14:34:48','2025-12-24 17:23:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-24 19:03:30
