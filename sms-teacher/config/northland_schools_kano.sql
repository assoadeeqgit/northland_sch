-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 15, 2025 at 02:18 PM
-- Server version: 8.0.43-0ubuntu0.24.04.2
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `northland_schools_kano`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` int NOT NULL,
  `session_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, '2023/2024', '2023-09-01', '2024-08-31', 1, '2025-10-23 11:37:21'),
(2, '2024/2025', '2024-09-01', '2025-08-31', 0, '2025-10-23 10:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_name`, `action_type`, `description`, `icon`, `color`, `created_at`) VALUES
(1, 'Admin', 'New Student', 'Added student: SAFIYYA AHMAD (STU6769)', 'fas fa-user-plus', 'bg-nsklightblue', '2025-11-07 10:09:49'),
(2, 'Admin', 'New Student', 'Added student: Hassan Musa (STU1004)', 'fas fa-user-plus', 'bg-nsklightblue', '2025-11-07 10:11:56'),
(3, 'Admin', 'New Teacher', 'Added teacher: Ahmad Adamu (TCH6060)', 'fas fa-chalkboard-teacher', 'bg-nskgreen', '2025-11-07 10:17:47'),
(4, 'Admin', 'Assign Subject', 'Assigned History to JSS 3 (Teacher: Ahmad Adamu, Type: Compulsory)', 'fas fa-book', 'bg-nskgreen', '2025-11-07 10:30:51'),
(5, 'Admin', 'Assign Subject', 'Assigned Music to JSS 3 (Teacher: Aisha Bello, Type: Elective)', 'fas fa-book', 'bg-nskgreen', '2025-11-07 10:31:34'),
(6, 'Auta Ahmad', 'Settings Update', 'Auta Ahmad updated the system preferences.', 'fas fa-cogs', 'bg-nskgold', '2025-11-07 10:52:34'),
(7, 'Auta Ahmad', 'Profile Update', 'Auta Ahmad updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-07 11:13:16'),
(8, 'Auta Ahmad Sani', 'Profile Update', 'Auta Ahmad Sani updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-07 11:25:21'),
(9, 'Admin', 'Report Generated', 'Generated: Attendance Report - Weekly', 'fas fa-file-pdf', 'bg-nskgreen', '2025-11-09 10:56:54'),
(10, 'Admin', 'Report Generated', 'Generated: Academic Report - Weekly', 'fas fa-file-pdf', 'bg-nskgreen', '2025-11-09 11:10:19'),
(11, 'Admin', 'Report Scheduled', 'Scheduled attendance report (daily)', 'fas fa-clock', 'bg-nskgold', '2025-11-09 11:15:36'),
(12, 'Auta Ahmad Sani', 'Profile Update', 'Auta Ahmad Sani updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 12:02:43'),
(13, 'Auta Ahmad Sani', 'Settings Update', 'Auta Ahmad Sani updated the system preferences.', 'fas fa-cogs', 'bg-nskgold', '2025-11-09 12:12:00'),
(14, 'Auta Ahmad Sani', 'Settings Update', 'Auta Ahmad Sani updated the system preferences.', 'fas fa-cogs', 'bg-nskgold', '2025-11-09 12:12:10'),
(15, 'Auta Ahmad Sani', 'Settings Update', 'Auta Ahmad Sani updated the system preferences.', 'fas fa-cogs', 'bg-nskgold', '2025-11-09 12:12:16'),
(16, 'Auta Ahmad Sani', 'Profile Update', 'Auta Ahmad Sani updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 12:20:47'),
(17, 'Auta Ahmad Sani', 'Profile Update', 'Auta Ahmad Sani updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 12:25:22'),
(18, 'Auta Ahmad Sani', 'Profile Update', 'Auta Ahmad Sani updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 12:28:49'),
(19, 'Abdurrahman Alhassan', 'Profile Update', 'Abdurrahman Alhassan updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 12:45:32'),
(20, 'Abdurrahman Alhassan', 'Profile Update', 'Abdurrahman Alhassan updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 13:57:18'),
(21, 'Abdurrahman Musa Alhassan', 'Profile Update', 'Abdurrahman Musa Alhassan updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-09 13:57:24'),
(22, 'Abdurrahman Musa Alhassan', 'Profile Update', 'Abdurrahman Musa Alhassan updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-10 09:00:57'),
(23, 'Abdurrahman Alhassan', 'Profile Update', 'Abdurrahman Alhassan updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-10 09:01:14'),
(24, 'Garba Alhassan', 'Profile Update', 'Garba Alhassan updated their profile information.', 'fas fa-user-edit', 'bg-nskblue', '2025-11-10 09:02:35');

-- --------------------------------------------------------

--
-- Table structure for table `admin_profiles`
--

CREATE TABLE `admin_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `admin_level` enum('Super Admin','Admin','Sub-Admin') DEFAULT NULL,
  `department_access` varchar(255) DEFAULT NULL,
  `special_permissions` text,
  `admin_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `due_date` date NOT NULL,
  `total_points` int DEFAULT '100',
  `type` enum('Quiz','Homework','Project','Exam','Worksheet') DEFAULT 'Homework',
  `allow_late_submission` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `title`, `description`, `due_date`, `total_points`, `type`, `allow_late_submission`, `created_at`) VALUES
(2, 2, 10, 2, 'Simple Equations', 'Complete worksheet 1-10', '2025-11-12', 50, 'Worksheet', 0, '2025-11-05 00:03:23'),
(3, 2, 10, 5, 'Lab Report 1', 'Write report on density', '2025-11-12', 100, 'Project', 0, '2025-11-05 00:03:23'),
(6, 1, 5, 1, 'Simple Words', 'Read and write 10 simple three-letter words', '2025-11-18', 20, 'Homework', 1, '2025-11-11 14:00:00'),
(7, 1, 5, 1, 'Story Time', 'Draw a picture from your favorite story', '2025-11-20', 10, 'Project', 1, '2025-11-11 14:00:00'),
(8, 1, 1, 1, 'Color Recognition', 'Identify primary colors: Red, Blue, Yellow', '2025-11-14', 10, 'Worksheet', 1, '2025-11-11 14:00:00'),
(9, 1, 1, 1, 'Shapes Matching', 'Match basic shapes: Circle, Square, Triangle', '2025-11-16', 10, 'Worksheet', 1, '2025-11-11 14:00:00'),
(10, 1, 1, 1, 'Show and Tell', 'Bring your favorite toy to class', '2025-11-19', 5, 'Project', 1, '2025-11-11 14:00:00'),
(12, 1, 5, 1, 'Simple Words', 'Read and write 10 simple three-letter words', '2025-11-18', 20, 'Homework', 1, '2025-11-11 14:00:00'),
(13, 1, 5, 1, 'Story Time', 'Draw a picture from your favorite story', '2025-11-20', 10, 'Project', 1, '2025-11-11 14:00:00'),
(15, 1, 5, 1, 'Simple Words', 'Read and write 10 simple three-letter words', '2025-11-18', 20, 'Homework', 1, '2025-11-11 14:00:00'),
(16, 1, 5, 1, 'Story Time', 'Draw a picture from your favorite story', '2025-11-20', 10, 'Project', 1, '2025-11-11 14:00:00'),
(17, 1, 1, 1, 'Color Recognition', 'Identify primary colors: Red, Blue, Yellow', '2025-11-14', 10, 'Worksheet', 1, '2025-11-11 14:00:00'),
(18, 1, 1, 1, 'Shapes Matching', 'Match basic shapes: Circle, Square, Triangle', '2025-11-16', 10, 'Worksheet', 1, '2025-11-11 14:00:00'),
(19, 1, 1, 1, 'Show and Tell', 'Bring your favorite toy to class', '2025-11-19', 5, 'Project', 1, '2025-11-11 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text,
  `recorded_by` int NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `class_id`, `attendance_date`, `status`, `remarks`, `recorded_by`, `academic_session_id`, `term_id`, `created_at`) VALUES
(2, 1, 5, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:16:14'),
(3, 10, 5, '2025-11-11', 'Present', 'Happy and active', 1, 1, 3, '2025-11-11 07:30:00'),
(4, 11, 5, '2025-11-11', 'Present', NULL, 1, 1, 3, '2025-11-11 07:30:00'),
(5, 12, 5, '2025-11-11', 'Late', 'Came 15 minutes late', 1, 1, 3, '2025-11-11 07:30:00'),
(6, 13, 5, '2025-11-11', 'Absent', 'Sick - fever', 1, 1, 3, '2025-11-11 07:30:00'),
(7, 14, 5, '2025-11-11', 'Present', NULL, 1, 1, 3, '2025-11-11 07:30:00'),
(24, 18, 1, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:26'),
(25, 19, 1, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:26'),
(26, 16, 1, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:26'),
(27, 15, 1, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:26'),
(28, 4, 1, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:26'),
(29, 17, 1, '2025-11-11', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:26'),
(30, 18, 1, '2025-11-09', 'Absent', NULL, 3, 1, 3, '2025-11-11 16:26:43'),
(31, 19, 1, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:43'),
(32, 16, 1, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:43'),
(33, 15, 1, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:43'),
(34, 4, 1, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:43'),
(35, 17, 1, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:26:43'),
(46, 10, 5, '2025-11-10', 'Present', 'Good participation', 1, 1, 3, '2025-11-10 07:30:00'),
(47, 11, 5, '2025-11-10', 'Late', 'Traffic delay', 1, 1, 3, '2025-11-10 07:30:00'),
(48, 12, 5, '2025-11-10', 'Present', NULL, 1, 1, 3, '2025-11-10 07:30:00'),
(49, 13, 5, '2025-11-10', 'Present', 'Excellent work', 1, 1, 3, '2025-11-10 07:30:00'),
(50, 14, 5, '2025-11-10', 'Absent', 'Doctor appointment', 1, 1, 3, '2025-11-10 07:30:00'),
(51, 10, 5, '2025-11-07', 'Present', NULL, 1, 1, 3, '2025-11-07 07:30:00'),
(52, 11, 5, '2025-11-07', 'Present', 'Brought homework', 1, 1, 3, '2025-11-07 07:30:00'),
(53, 12, 5, '2025-11-07', 'Excused', 'Family event', 1, 1, 3, '2025-11-07 07:30:00'),
(54, 13, 5, '2025-11-07', 'Present', NULL, 1, 1, 3, '2025-11-07 07:30:00'),
(55, 14, 5, '2025-11-07', 'Late', 'Car trouble', 1, 1, 3, '2025-11-07 07:30:00'),
(89, 11, 5, '2025-11-08', 'Absent', NULL, 3, 1, 3, '2025-11-11 16:29:18'),
(90, 13, 5, '2025-11-08', 'Absent', NULL, 3, 1, 3, '2025-11-11 16:29:18'),
(91, 10, 5, '2025-11-08', 'Absent', NULL, 3, 1, 3, '2025-11-11 16:29:18'),
(92, 1, 5, '2025-11-08', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:18'),
(93, 12, 5, '2025-11-08', 'Absent', NULL, 3, 1, 3, '2025-11-11 16:29:18'),
(94, 14, 5, '2025-11-08', 'Absent', NULL, 3, 1, 3, '2025-11-11 16:29:18'),
(95, 11, 5, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:43'),
(96, 13, 5, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:43'),
(97, 10, 5, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:43'),
(98, 1, 5, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:43'),
(99, 12, 5, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:43'),
(100, 14, 5, '2025-11-09', 'Present', NULL, 3, 1, 3, '2025-11-11 16:29:43'),
(101, 18, 1, '2025-11-10', 'Present', NULL, 3, 1, 3, '2025-11-11 16:33:41'),
(102, 19, 1, '2025-11-10', 'Present', NULL, 3, 1, 3, '2025-11-11 16:33:41'),
(103, 16, 1, '2025-11-10', 'Present', NULL, 3, 1, 3, '2025-11-11 16:33:41'),
(104, 15, 1, '2025-11-10', 'Present', NULL, 3, 1, 3, '2025-11-11 16:33:41'),
(105, 4, 1, '2025-11-10', 'Present', NULL, 3, 1, 3, '2025-11-11 16:33:41'),
(106, 17, 1, '2025-11-10', 'Present', NULL, 3, 1, 3, '2025-11-11 16:33:41'),
(107, 11, 5, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:11:50'),
(108, 13, 5, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:11:50'),
(109, 10, 5, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:11:50'),
(110, 1, 5, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:11:50'),
(111, 12, 5, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:11:50'),
(112, 14, 5, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:11:50'),
(113, 18, 1, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:12:05'),
(114, 19, 1, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:12:05'),
(115, 16, 1, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:12:05'),
(116, 15, 1, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:12:05'),
(117, 4, 1, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:12:05'),
(118, 17, 1, '2025-11-12', 'Present', NULL, 3, 1, 3, '2025-11-12 10:12:05');

-- --------------------------------------------------------

--
-- Table structure for table `auth_audit_log`
--

CREATE TABLE `auth_audit_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `event_type` enum('login','logout','registration','password_reset','failed_login','account_locked') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `auth_audit_log`
--

INSERT INTO `auth_audit_log` (`id`, `user_id`, `event_type`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
(1, NULL, 'failed_login', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '{\"timestamp\":\"2025-11-07 10:24:48\"}', '2025-11-07 10:24:48'),
(2, NULL, 'failed_login', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '{\"timestamp\":\"2025-11-07 10:25:12\"}', '2025-11-07 10:25:12'),
(3, 11, 'login', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '{\"timestamp\":\"2025-11-07 10:25:36\"}', '2025-11-07 10:25:36'),
(4, NULL, 'failed_login', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '{\"timestamp\":\"2025-11-07 10:26:58\"}', '2025-11-07 10:26:58'),
(5, 11, 'failed_login', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '{\"timestamp\":\"2025-11-07 10:47:26\",\"reason\":\"Account inactive\"}', '2025-11-07 10:47:26'),
(6, NULL, 'failed_login', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '{\"timestamp\":\"2025-11-07 10:47:37\",\"reason\":\"User not found\"}', '2025-11-07 10:47:37'),
(7, 1, 'failed_login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-09 12:47:00\",\"reason\":\"Incorrect password\"}', '2025-11-09 12:47:00'),
(8, 1, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-09 12:48:20\"}', '2025-11-09 12:48:20'),
(9, 1, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-09 12:50:43\"}', '2025-11-09 12:50:43'),
(10, 2, 'failed_login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-09 13:50:11\",\"reason\":\"Incorrect password\"}', '2025-11-09 13:50:11'),
(11, 2, 'failed_login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-09 13:50:28\",\"reason\":\"Account inactive\"}', '2025-11-09 13:50:28'),
(12, 2, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-09 13:50:43\"}', '2025-11-09 13:50:43'),
(13, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 14:05:30\"}', '2025-11-11 14:05:30'),
(14, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 14:11:08\"}', '2025-11-11 14:11:08'),
(15, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 14:11:55\"}', '2025-11-11 14:11:55'),
(16, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 14:54:48\"}', '2025-11-11 14:54:48'),
(17, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 15:04:39\"}', '2025-11-11 15:04:39'),
(18, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 15:06:04\"}', '2025-11-11 15:06:04'),
(19, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 15:24:48\"}', '2025-11-11 15:24:48'),
(20, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-11 15:25:13\"}', '2025-11-11 15:25:13'),
(21, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-12 10:10:19\"}', '2025-11-12 10:10:19'),
(22, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-12 11:51:15\"}', '2025-11-12 11:51:15'),
(23, 3, 'login', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-11-15 10:56:18\"}', '2025-11-15 10:56:18');

-- --------------------------------------------------------

--
-- Table structure for table `book_borrowing`
--

CREATE TABLE `book_borrowing` (
  `id` int NOT NULL,
  `book_id` int NOT NULL,
  `student_id` int NOT NULL,
  `borrowed_date` date NOT NULL,
  `due_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `fine_amount` decimal(8,2) DEFAULT '0.00',
  `status` enum('Borrowed','Returned','Overdue') DEFAULT 'Borrowed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `class_teacher_id` int DEFAULT NULL,
  `capacity` int DEFAULT '40',
  `class_level` varchar(255) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `class_code`, `class_teacher_id`, `capacity`, `class_level`, `created_at`) VALUES
(1, 'Garden (Age 2-3)', 'GARDEN', 3, 20, 'Early Childhood', '2025-10-28 09:41:24'),
(2, 'Pre-Nursery (Age 3-4)', 'PRE-NUR', 1, 20, 'Early Childhood', '2025-10-28 09:41:24'),
(3, 'Nursery 1 (Age 4-5)', 'NUR1', NULL, 25, 'Early Childhood', '2025-10-28 09:41:24'),
(4, 'Nursery 2 (Age 5-6)', 'NUR2', NULL, 25, 'Early Childhood', '2025-10-28 09:41:24'),
(5, 'Primary 1', 'P1', NULL, 30, 'Primary', '2025-10-28 09:41:24'),
(6, 'Primary 2', 'P2', NULL, 30, 'Primary', '2025-10-28 09:41:24'),
(7, 'Primary 3', 'P3', NULL, 30, 'Primary', '2025-10-28 09:41:24'),
(8, 'Primary 4', 'P4', NULL, 30, 'Primary', '2025-10-28 09:41:24'),
(9, 'Primary 5', 'P5', NULL, 30, 'Primary', '2025-10-28 09:41:24'),
(10, 'JSS 1', 'JSS1', NULL, 35, 'Secondary', '2025-10-28 09:41:24'),
(11, 'JSS 2', 'JSS2', NULL, 35, 'Secondary', '2025-10-28 09:41:24'),
(12, 'JSS 3', 'JSS3', NULL, 35, 'Secondary', '2025-10-28 09:41:24'),
(13, 'SS 1', 'SS1', NULL, 30, 'Secondary', '2025-10-28 09:41:24'),
(14, 'SS 2', 'SS2', NULL, 30, 'Secondary', '2025-10-28 09:41:24'),
(15, 'SS 3', 'SS3', NULL, 30, 'Secondary', '2025-10-28 09:41:24');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `is_compulsory` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `class_subjects`
--

INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `teacher_id`, `is_compulsory`, `created_at`) VALUES
(1, 5, 1, 1, 1, '2025-11-05 00:03:23'),
(2, 5, 2, 2, 1, '2025-11-05 00:03:23'),
(3, 1, 1, 1, 1, '2025-11-05 00:03:23'),
(4, 10, 2, 2, 1, '2025-11-05 00:03:23'),
(5, 10, 5, 2, 1, '2025-11-05 00:03:23'),
(6, 12, 7, 3, 1, '2025-11-07 10:30:51');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_description` text,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `target_audience` enum('All','Students','Teachers','Parents','Staff') DEFAULT 'All',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_title`, `event_description`, `event_date`, `start_time`, `end_time`, `venue`, `target_audience`, `created_by`, `created_at`) VALUES
(1, 'Inter-house Sports Competition', 'Annual sports day with various athletic competitions', '2025-11-25', '08:00:00', '16:00:00', 'School Sports Field', 'All', 1, '2025-11-11 13:00:00'),
(2, 'Parent-Teacher Meeting', 'Quarterly meeting to discuss student progress', '2025-11-18', '14:00:00', '16:00:00', 'School Hall', 'Parents', 1, '2025-11-11 13:00:00'),
(3, 'Science Fair Exhibition', 'Student science projects exhibition', '2025-11-30', '09:00:00', '13:00:00', 'Science Block', 'Students', 2, '2025-11-11 13:00:00'),
(4, 'Staff Development Workshop', 'Training on modern teaching methodologies', '2025-11-20', '10:00:00', '15:00:00', 'Conference Room', 'Teachers', 1, '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int NOT NULL,
  `exam_type_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `exam_date` date NOT NULL,
  `total_marks` decimal(6,2) NOT NULL,
  `passing_marks` decimal(6,2) NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_types`
--

CREATE TABLE `exam_types` (
  `id` int NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_code` varchar(20) NOT NULL,
  `weightage` decimal(5,2) DEFAULT '100.00',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exam_types`
--

INSERT INTO `exam_types` (`id`, `exam_name`, `exam_code`, `weightage`, `description`, `created_at`) VALUES
(1, 'First Term Examination', 'FTE', 30.00, 'First term comprehensive examination', '2025-10-23 11:37:21'),
(2, 'Second Term Examination', 'STE', 30.00, 'Second term comprehensive examination', '2025-10-23 11:37:21'),
(3, 'Third Term Examination', 'TTE', 40.00, 'Third term comprehensive examination', '2025-10-23 11:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `fee_structure`
--

INSERT INTO `fee_structure` (`id`, `class_id`, `fee_type`, `amount`, `academic_session_id`, `term_id`, `due_date`, `is_active`, `created_at`) VALUES
(1, 10, 'Tuition Fee', 25000.00, 1, 3, '2025-11-30', 1, '2025-11-11 13:00:00'),
(2, 10, 'Development Levy', 5000.00, 1, 3, '2025-11-30', 1, '2025-11-11 13:00:00'),
(3, 10, 'Sports Fee', 2000.00, 1, 3, '2025-11-30', 1, '2025-11-11 13:00:00'),
(4, 5, 'Tuition Fee', 18000.00, 1, 3, '2025-11-30', 1, '2025-11-11 13:00:00'),
(5, 5, 'Development Levy', 3000.00, 1, 3, '2025-11-30', 1, '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `generated_reports`
--

CREATE TABLE `generated_reports` (
  `id` int NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `generated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `generated_by` varchar(100) DEFAULT NULL,
  `download_count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `generated_reports`
--

INSERT INTO `generated_reports` (`id`, `report_name`, `report_type`, `generated_date`, `period_start`, `period_end`, `file_path`, `generated_by`, `download_count`) VALUES
(1, 'Attendance Report - monthly', 'attendance', '2025-11-01 09:30:00', '2025-10-01', '2025-10-31', 'report_attendance_20251101_103000.pdf', 'Admin User', 0),
(2, 'Academic Report - term', 'academic', '2025-11-02 13:00:00', '2025-09-01', '2025-11-01', 'report_academic_20251102_140000.pdf', 'Admin User', 0),
(3, 'Financial Report - weekly', 'financial', '2025-11-03 08:00:00', '2025-10-27', '2025-11-02', 'report_financial_20251103_090000.excel', 'Admin User', 0);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int NOT NULL,
  `min_quantity` int DEFAULT '5',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `item_code`, `category`, `quantity`, `min_quantity`, `unit_price`, `supplier`, `storage_location`, `description`, `created_at`) VALUES
(1, 'Exercise Books (80 pages)', 'EXB-80P', 'Stationery', 250, 50, 150.00, 'Stationery Plus Ltd', 'Store Room A', 'Single line exercise books', '2025-11-11 13:00:00'),
(2, 'Blue Pens', 'PEN-BLUE', 'Stationery', 120, 30, 50.00, 'WriteWell Suppliers', 'Store Room A', 'Ballpoint blue pens', '2025-11-11 13:00:00'),
(3, 'Scientific Calculators', 'CALC-SCI', 'Electronics', 25, 5, 2500.00, 'TechGadgets Ltd', 'Store Room B', 'Casio FX-991ES Plus', '2025-11-11 13:00:00'),
(4, 'Whiteboard Markers', 'MRK-BLACK', 'Teaching Aids', 45, 10, 200.00, 'EduSupplies Co.', 'Staff Room', 'Black whiteboard markers', '2025-11-11 13:00:00'),
(5, 'Laboratory Beakers', 'BEAK-250ML', 'Science Lab', 35, 8, 800.00, 'ScienceLab Equipment', 'Chemistry Lab', '250ml glass beakers', '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` int NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `library_books`
--

INSERT INTO `library_books` (`id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`, `shelf_location`, `created_at`) VALUES
(1, '9780141439518', 'Things Fall Apart', 'Chinua Achebe', 'Penguin Books', '1958', 'Literature', 5, 3, 'A1-01', '2025-11-11 13:00:00'),
(2, '9780439136365', 'Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'Bloomsbury', '1997', 'Fiction', 3, 2, 'B2-15', '2025-11-11 13:00:00'),
(3, '9780007354775', 'Mathematics for JSS 1', 'Prof. A. Bello', 'LearnRight Publishers', '2020', 'Textbook', 15, 12, 'C3-08', '2025-11-11 13:00:00'),
(4, '9781234567890', 'Basic Science Concepts', 'Dr. C. Mohammed', 'EduPress', '2021', 'Science', 8, 6, 'C3-12', '2025-11-11 13:00:00'),
(5, '9780987654321', 'English Grammar Guide', 'Mrs. D. Johnson', 'Language Masters', '2019', 'Language', 10, 8, 'A1-25', '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('All','Students','Teachers','Parents','Staff') DEFAULT 'All',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `publish_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `target_audience`, `priority`, `publish_date`, `expiry_date`, `created_by`, `is_active`, `created_at`) VALUES
(1, 'School Resumption Notice', 'All students are expected to resume for the new academic session on September 9th, 2024. Please ensure you have all required materials and complete uniform.', 'Students', 'High', '2025-11-01', '2025-11-30', 1, 1, '2025-11-11 13:00:00'),
(2, 'Fee Payment Reminder', 'This is to remind all parents that the second installment of school fees is due by November 15th, 2024. Please make payments at the bursary department.', 'Parents', 'Medium', '2025-11-05', '2025-11-15', 1, 1, '2025-11-11 13:00:00'),
(3, 'Staff Meeting', 'There will be an important staff meeting on Friday, November 15th at 2:00 PM in the staff room. All teaching staff must attend.', 'Teachers', 'High', '2025-11-10', '2025-11-15', 1, 1, '2025-11-11 13:00:00'),
(4, 'Library Week', 'The school library will be celebrating Library Week from November 18th-22nd. Special activities and reading competitions will be held.', 'Students', 'Medium', '2025-11-08', '2025-11-22', 1, 1, '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `fee_structure_id`, `amount_paid`, `payment_date`, `payment_method`, `transaction_id`, `received_by`, `academic_session_id`, `term_id`, `remarks`, `created_at`) VALUES
(1, 2, 1, 25000.00, '2025-11-05', 'Bank Transfer', 'TXN001234', 5, 1, 3, 'Full payment', '2025-11-11 13:00:00'),
(2, 5, 1, 15000.00, '2025-11-06', 'Cash', NULL, 5, 1, 3, 'First installment', '2025-11-11 13:00:00'),
(3, 6, 1, 25000.00, '2025-11-07', 'POS', 'POS987654', 5, 1, 3, 'Full payment', '2025-11-11 13:00:00'),
(4, 7, 1, 20000.00, '2025-11-08', 'Bank Transfer', 'TXN567890', 5, 1, 3, 'Partial payment', '2025-11-11 13:00:00'),
(5, 1, 4, 18000.00, '2025-11-09', 'Cash', NULL, 5, 1, 3, 'Full payment', '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `report_schedules`
--

CREATE TABLE `report_schedules` (
  `id` int NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `report_schedules`
--

INSERT INTO `report_schedules` (`id`, `report_type`, `frequency`, `recipient_email`, `created_by`, `created_date`) VALUES
(1, 'attendance', 'Weekly', 'principal@northland.edu.ng', 'Admin User', '2025-11-09 11:15:07'),
(2, 'financial', 'Monthly', 'accounts@northland.edu.ng', 'Admin User', '2025-11-09 11:15:07'),
(3, 'attendance', 'daily', 'aqbsadiq019@gmail.com', 'Admin', '2025-11-09 11:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

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
  `percentage` decimal(5,2) GENERATED ALWAYS AS ((marks_obtained / total_marks) * 100) STORED,
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

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int NOT NULL,
  `school_code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `principal_id` int DEFAULT NULL,
  `established_year` year DEFAULT NULL,
  `motto` text,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `school_code`, `name`, `address`, `phone`, `email`, `principal_id`, `established_year`, `motto`, `logo`, `created_at`) VALUES
(1, 'NLSK001', 'Northland Schools Kano', 'No. 123 Education Road, Kano State, Nigeria', '08001234567', 'info@northland.edu.ng', 2, '1995', 'Excellence in Education', NULL, '2025-10-23 11:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `updated_at`) VALUES
(1, 'school_name', 'Northland Schools Kano ', 'Official school name', '2025-11-09 12:12:16'),
(2, 'school_address', 'No. 123-Education Road, Kano State, Nigeria', 'School physical address', '2025-11-07 10:52:34'),
(3, 'school_phone', '08001234567', 'School contact phone', '2025-10-23 11:37:21'),
(4, 'school_email', 'info@northland.edu.ng', 'School email address', '2025-11-04 23:09:56'),
(5, 'attendance_threshold', '75', 'Minimum attendance percentage required', '2025-10-23 11:37:21'),
(6, 'late_threshold_minutes', '15', 'Minutes after which student is marked late', '2025-10-23 11:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract') DEFAULT NULL,
  `supervisor` varchar(100) DEFAULT NULL,
  `staff_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`id`, `user_id`, `department`, `position`, `employment_type`, `supervisor`, `staff_id`, `created_at`) VALUES
(1, 5, 'Accounts', 'Bursar', NULL, NULL, 'STF001', '2025-11-05 00:03:23'),
(2, 11, 'Library', 'Liberian ', NULL, NULL, 'STF6966', '2025-11-07 10:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `class_id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `admission_date` date NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Nigerian',
  `state_of_origin` varchar(50) DEFAULT NULL,
  `lga` varchar(50) DEFAULT NULL,
  `medical_conditions` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_id`, `admission_number`, `class_id`, `parent_id`, `admission_date`, `religion`, `nationality`, `state_of_origin`, `lga`, `medical_conditions`, `emergency_contact_name`, `emergency_contact_phone`, `created_at`) VALUES
(1, 6, 'STU001', 'ADM001', 5, NULL, '2024-01-15', NULL, 'Nigerian', NULL, NULL, NULL, NULL, NULL, '2025-11-05 00:03:23'),
(2, 7, 'STU002', 'ADM002', 10, NULL, '2024-01-15', NULL, 'Nigerian', NULL, NULL, NULL, NULL, NULL, '2025-11-05 00:03:23'),
(3, 8, 'STU6769', 'ADM6676', 12, NULL, '2025-11-07', 'Islam', 'Nigerian', 'Kano', 'Tarauni', '', 'Hajiya Larai', '09004757748', '2025-11-07 10:09:49'),
(4, 9, 'STU1004', 'ADM3731', 1, NULL, '2025-11-07', 'Islam', 'Nigerian', 'Katsina', 'Ajingi', '\r\n', 'Alh Inuwa', '09033776447', '2025-11-07 10:11:56'),
(5, 14, 'STU005', 'ADM005', 10, NULL, '2024-01-15', 'Islam', 'Nigerian', 'Kano', 'Nassarawa', 'Asthma', 'Alhaji Sani', '08051114444', '2025-11-11 13:00:00'),
(6, 15, 'STU006', 'ADM006', 10, NULL, '2024-01-15', 'Islam', 'Nigerian', 'Kano', 'Kano Municipal', NULL, 'Hajiya Mohammed', '08061115555', '2025-11-11 13:00:00'),
(7, 16, 'STU007', 'ADM007', 10, NULL, '2024-01-15', 'Islam', 'Nigerian', 'Kano', 'Gwale', NULL, 'Malam Ali', '08071116666', '2025-11-11 13:00:00'),
(8, 17, 'STU008', 'ADM008', 10, NULL, '2024-01-15', 'Islam', 'Nigerian', 'Kano', 'Fagge', 'None', 'Alhaji Ibrahim', '08081117777', '2025-11-11 13:00:00'),
(9, 18, 'STU009', 'ADM009', 10, NULL, '2024-01-15', 'Islam', 'Nigerian', 'Kano', 'Dala', NULL, 'Malam Adam', '08091118888', '2025-11-11 13:00:00'),
(10, 19, 'STU010', 'ADM010', 5, NULL, '2024-09-01', 'Christian', 'Nigerian', 'Lagos', 'Ikeja', NULL, 'Mrs. Okafor', '08101112222', '2025-11-11 14:00:00'),
(11, 20, 'STU011', 'ADM011', 5, NULL, '2024-09-01', 'Christian', 'Nigerian', 'Oyo', 'Ibadan North', NULL, 'Mr. Adebayo', '08111113333', '2025-11-11 14:00:00'),
(12, 21, 'STU012', 'ADM012', 5, NULL, '2024-09-01', 'Christian', 'Nigerian', 'Rivers', 'Port Harcourt', NULL, 'Mrs. Uwakwe', '08121114444', '2025-11-11 14:00:00'),
(13, 22, 'STU013', 'ADM013', 5, NULL, '2024-09-01', 'Christian', 'Nigerian', 'Enugu', 'Enugu East', NULL, 'Mr. Nwosu', '08131115555', '2025-11-11 14:00:00'),
(14, 23, 'STU014', 'ADM014', 5, NULL, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Kano Municipal', NULL, 'Alhaji Yusuf', '08141116666', '2025-11-11 14:00:00'),
(15, 28, 'STU015', 'ADM015', 1, NULL, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Nassarawa', '', 'Hajiya Mohammed', '08151117777', '2025-11-11 14:00:00'),
(16, 29, 'STU016', 'ADM016', 1, NULL, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Gwale', NULL, 'Alhaji Ibrahim', '08161118888', '2025-11-11 14:00:00'),
(17, 30, 'STU017', 'ADM017', 1, NULL, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Fagge', NULL, 'Malam Umar', '08171119999', '2025-11-11 14:00:00'),
(18, 31, 'STU018', 'ADM018', 1, NULL, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Dala', NULL, 'Mr. Ali', '08181110000', '2025-11-11 14:00:00'),
(19, 32, 'STU019', 'ADM019', 1, NULL, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Kano Municipal', NULL, 'Alhaji Hassan', '08191111111', '2025-11-11 14:00:00'),
(20, 34, 'STU6770', 'ADM6770', 5, NULL, '2025-11-01', 'Islam', 'Nigerian', 'katsina', 'kankia', '', '', '', '2025-11-15 13:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `class_level` varchar(50) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `medical_info` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `date_of_birth`, `gender`, `class_level`, `parent_name`, `parent_phone`, `previous_school`, `medical_info`, `created_at`) VALUES
(1, 6, '2017-05-10', 'Male', 'Primary', 'Sani Yusuf', '08011111111', NULL, NULL, '2025-11-05 00:03:23'),
(2, 7, '2012-03-20', 'Female', 'Secondary', 'Kareem Bello', '08022222222', NULL, NULL, '2025-11-05 00:03:23'),
(3, 14, '2012-08-10', 'Male', 'Secondary', 'Alhaji Sani', '08051114444', 'Prime Primary School', 'Asthma - uses inhaler when needed', '2025-11-11 13:00:00'),
(4, 15, '2013-01-25', 'Female', 'Secondary', 'Hajiya Mohammed', '08061115555', 'Excel Nursery & Primary', NULL, '2025-11-11 13:00:00'),
(5, 16, '2011-11-30', 'Male', 'Secondary', 'Malam Ali', '08071116666', 'Success Primary School', NULL, '2025-11-11 13:00:00'),
(6, 17, '2012-04-18', 'Female', 'Secondary', 'Alhaji Ibrahim', '08081117777', 'Bright Future Academy', 'None', '2025-11-11 13:00:00'),
(7, 18, '2013-06-05', 'Male', 'Secondary', 'Malam Adam', '08091118888', 'Little Scholars Primary', NULL, '2025-11-11 13:00:00'),
(8, 19, '2017-03-15', 'Female', 'Primary', 'Mrs. Okafor', '08101112222', 'Little Stars Academy', NULL, '2025-11-11 14:00:00'),
(9, 20, '2017-07-22', 'Male', 'Primary', 'Mr. Adebayo', '08111113333', 'Bright Beginning School', 'Allergic to peanuts', '2025-11-11 14:00:00'),
(10, 21, '2017-11-08', 'Female', 'Primary', 'Mrs. Uwakwe', '08121114444', 'Sunrise Academy', NULL, '2025-11-11 14:00:00'),
(11, 22, '2017-05-30', 'Male', 'Primary', 'Mr. Nwosu', '08131115555', 'Future Leaders School', NULL, '2025-11-11 14:00:00'),
(12, 23, '2017-09-14', 'Female', 'Primary', 'Alhaji Yusuf', '08141116666', 'Islamic Model School', NULL, '2025-11-11 14:00:00'),
(13, 28, '2022-01-10', 'Female', 'Early Childhood', 'Hajiya Mohammed', '08151117777', NULL, 'First time in school', '2025-11-11 14:00:00'),
(14, 29, '2022-04-25', 'Male', 'Early Childhood', 'Alhaji Ibrahim', '08161118888', NULL, NULL, '2025-11-11 14:00:00'),
(15, 30, '2022-08-15', 'Female', 'Early Childhood', 'Malam Umar', '08171119999', NULL, 'Mild asthma', '2025-11-11 14:00:00'),
(16, 31, '2022-11-30', 'Male', 'Early Childhood', 'Mr. Ali', '08181110000', NULL, NULL, '2025-11-11 14:00:00'),
(17, 32, '2022-06-18', 'Female', 'Early Childhood', 'Alhaji Hassan', '08191111111', NULL, NULL, '2025-11-11 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text,
  `category` enum('Core','Elective','Extra-curricular') DEFAULT 'Core',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `description`, `category`, `is_active`, `created_at`) VALUES
(1, 'ENG', 'English Language', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(2, 'MAT', 'Mathematics', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(3, 'BIO', 'Biology', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(4, 'CHEM', 'Chemistry', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(5, 'PHY', 'Physics', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(6, 'GEO', 'Geography', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(7, 'HIS', 'History', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(8, 'CRS', 'Christian Religious Studies', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(9, 'IRS', 'Islamic Religious Studies', NULL, 'Core', 1, '2025-10-23 11:37:21'),
(10, 'FRN', 'French', NULL, 'Elective', 1, '2025-10-23 11:37:21'),
(11, 'MUS', 'Music', NULL, 'Extra-curricular', 1, '2025-10-23 11:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `employment_date` date DEFAULT NULL,
  `salary_grade` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(20) DEFAULT NULL,
  `is_class_teacher` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `teacher_id`, `qualification`, `specialization`, `employment_date`, `salary_grade`, `bank_name`, `account_number`, `is_class_teacher`, `created_at`) VALUES
(1, 3, 'TCH001', 'B.Sc Education', NULL, '2020-09-01', NULL, NULL, NULL, 0, '2025-11-05 00:03:23'),
(2, 4, 'TCH002', 'M.Sc Physics', NULL, '2019-01-05', NULL, NULL, NULL, 0, '2025-11-05 00:03:23'),
(3, 10, 'TCH6060', 'B.Sc Medical Laboratory Science', NULL, '2025-11-07', NULL, NULL, NULL, 0, '2025-11-07 10:17:47'),
(4, 12, 'TCH004', 'B.Sc Mathematics', 'Mathematics, Physics', '2022-02-15', 'CONUAS 7', 'First Bank', '3012345678', 1, '2025-11-11 13:00:00'),
(5, 13, 'TCH005', 'B.A Education', 'English, Literature', '2021-09-01', 'CONUAS 6', 'GT Bank', '4012345678', 1, '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_profiles`
--

CREATE TABLE `teacher_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `subject_specialization` varchar(255) DEFAULT NULL,
  `years_experience` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract') DEFAULT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `teacher_profiles`
--

INSERT INTO `teacher_profiles` (`id`, `user_id`, `qualification`, `subject_specialization`, `years_experience`, `department`, `employment_type`, `teacher_id`, `created_at`) VALUES
(1, 3, NULL, 'English Language, History', '3-5 years', 'Arts', 'Full-time', 'TCH001', '2025-11-05 00:03:23'),
(2, 4, NULL, 'Physics, Mathematics', '6-10 years', 'Science', 'Full-time', 'TCH002', '2025-11-05 00:03:23'),
(3, 10, 'B.Sc Medical Laboratory Science', 'Biology, Chemistry', NULL, 'Science', 'Full-time', 'TCH6060', '2025-11-07 10:17:47'),
(4, 12, 'B.Sc Mathematics', 'Mathematics, Physics', '5-7 years', 'Science', 'Full-time', 'TCH004', '2025-11-11 13:00:00'),
(5, 13, 'B.A Education', 'English, Literature', '3-5 years', 'Arts', 'Full-time', 'TCH005', '2025-11-11 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` int NOT NULL,
  `academic_session_id` int NOT NULL,
  `term_name` enum('First Term','Second Term','Third Term') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `academic_session_id`, `term_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, 1, 'First Term', '2023-09-01', '2023-12-15', 0, '2025-10-23 11:37:21'),
(2, 1, 'Second Term', '2024-01-08', '2024-04-05', 0, '2025-10-23 11:37:21'),
(3, 1, 'Third Term', '2024-04-22', '2024-08-02', 1, '2025-10-23 11:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `academic_session_id` int NOT NULL,
  `term_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`, `created_at`) VALUES
(1, 5, 1, 1, 'Monday', '08:00:00', '08:45:00', 'P1-A', 1, 3, '2025-11-05 00:03:23'),
(2, 5, 2, 2, 'Monday', '08:45:00', '09:30:00', 'P1-A', 1, 3, '2025-11-05 00:03:23'),
(3, 5, 1, 1, 'Tuesday', '08:00:00', '08:45:00', 'P1-A', 1, 3, '2025-11-05 00:03:23'),
(4, 5, 2, 2, 'Tuesday', '08:45:00', '09:30:00', 'P1-A', 1, 3, '2025-11-05 00:03:23'),
(5, 10, 2, 2, 'Monday', '08:00:00', '08:45:00', 'JSS1-A', 1, 3, '2025-11-05 00:03:23'),
(6, 10, 1, 1, 'Monday', '08:45:00', '09:30:00', 'JSS1-A', 1, 3, '2025-11-05 00:03:23'),
(7, 10, 5, 2, 'Tuesday', '08:00:00', '08:45:00', 'Lab A', 1, 3, '2025-11-05 00:03:23'),
(8, 10, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'JSS1-A', 1, 3, '2025-11-05 00:03:23'),
(9, 10, 1, 3, 'Monday', '09:30:00', '10:15:00', 'JSS1-A', 1, 3, '2025-11-11 13:00:00'),
(10, 10, 2, 4, 'Monday', '10:15:00', '11:00:00', 'JSS1-A', 1, 3, '2025-11-11 13:00:00'),
(11, 10, 3, 2, 'Monday', '11:15:00', '12:00:00', 'Lab A', 1, 3, '2025-11-11 13:00:00'),
(12, 10, 9, 3, 'Tuesday', '08:00:00', '08:45:00', 'JSS1-A', 1, 3, '2025-11-11 13:00:00'),
(13, 10, 1, 3, 'Wednesday', '09:30:00', '10:15:00', 'JSS1-A', 1, 3, '2025-11-11 13:00:00'),
(14, 10, 2, 4, 'Thursday', '10:15:00', '11:00:00', 'JSS1-A', 1, 3, '2025-11-11 13:00:00'),
(15, 10, 3, 2, 'Friday', '11:15:00', '12:00:00', 'Lab A', 1, 3, '2025-11-11 13:00:00'),
(16, 5, 1, 1, 'Monday', '08:00:00', '08:30:00', 'P1-A', 1, 3, '2025-11-11 14:00:00'),
(17, 5, 1, 1, 'Wednesday', '08:00:00', '08:30:00', 'P1-A', 1, 3, '2025-11-11 14:00:00'),
(18, 5, 1, 1, 'Friday', '08:00:00', '08:30:00', 'P1-A', 1, 3, '2025-11-11 14:00:00'),
(19, 1, 1, 1, 'Tuesday', '09:00:00', '09:45:00', 'GARDEN-ROOM', 1, 3, '2025-11-11 14:00:00'),
(20, 1, 1, 1, 'Thursday', '09:00:00', '09:45:00', 'GARDEN-ROOM', 1, 3, '2025-11-11 14:00:00'),
(21, 5, 1, 1, 'Monday', '08:00:00', '08:30:00', 'P1-A', 1, 3, '2025-11-11 14:00:00'),
(22, 5, 1, 1, 'Wednesday', '08:00:00', '08:30:00', 'P1-A', 1, 3, '2025-11-11 14:00:00'),
(23, 5, 1, 1, 'Friday', '08:00:00', '08:30:00', 'P1-A', 1, 3, '2025-11-11 14:00:00'),
(24, 1, 1, 1, 'Tuesday', '09:00:00', '09:45:00', 'GARDEN-ROOM', 1, 3, '2025-11-11 14:00:00'),
(25, 1, 1, 1, 'Thursday', '09:00:00', '09:45:00', 'GARDEN-ROOM', 1, 3, '2025-11-11 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','teacher','student','staff','principal') NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `user_type`, `first_name`, `last_name`, `phone`, `address`, `date_of_birth`, `gender`, `profile_picture`, `is_active`, `last_login`, `registration_step`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expiry`, `registration_data`, `last_activity`, `login_attempts`, `account_locked_until`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'abdul@notherland.edu.ng', '$2y$10$et.jlLFy9QsDvRizhFXth.oZPFuTEd7YvDrVbumCNsjv6zGKLnefK', 'admin', 'Garba', 'Alhassan', '08012345678', NULL, NULL, 'Male', NULL, 1, '2025-11-09 14:49:56', 1, 1, NULL, NULL, NULL, NULL, '2025-11-09 14:49:56', 0, NULL, '2025-10-23 11:37:21', '2025-11-10 09:02:35'),
(2, 'principal.musa', 'principal@northland.edu.ng', '$2y$10$et.jlLFy9QsDvRizhFXth.oZPFuTEd7YvDrVbumCNsjv6zGKLnefK', 'principal', 'Ahmed', 'Musa', NULL, NULL, NULL, NULL, NULL, 1, '2025-11-09 14:50:43', 1, 0, NULL, NULL, NULL, NULL, '2025-11-09 14:50:43', 0, NULL, '2025-11-05 00:03:23', '2025-11-09 13:50:43'),
(3, 'teacher.aisha', 'aisha.bello@northland.edu.ng', '$2y$10$et.jlLFy9QsDvRizhFXth.oZPFuTEd7YvDrVbumCNsjv6zGKLnefK', 'teacher', 'Aisha', 'Bello', NULL, NULL, NULL, NULL, NULL, 1, '2025-11-15 11:56:18', 1, 0, NULL, NULL, NULL, NULL, '2025-11-15 11:56:18', 0, NULL, '2025-11-05 00:03:23', '2025-11-15 10:56:18'),
(4, 'teacher.hamza', 'hamza.ali@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Hamza', 'Ali', NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-05 00:03:23', '2025-11-05 00:03:23'),
(5, 'staff.hassan', 'hassan.ibrahim@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Hassan', 'Ibrahim', NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-05 00:03:23', '2025-11-05 00:03:23'),
(6, 'student.yusuf', 'yusuf.sani@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Yusuf', 'Sani', NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-05 00:03:23', '2025-11-05 00:03:23'),
(7, 'student.fatima', 'fatima.k@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Fatima', 'Kareem', NULL, NULL, NULL, NULL, NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-05 00:03:23', '2025-11-05 00:03:23'),
(8, 'safiyya.ahmad', 'safiyya234@gmail.com', '$2y$10$Rpog8V8OFC8H.RTINyMey.WCAZItSV3t8uTo6KNA2ptEWMT6pHnRa', 'student', 'SAFIYYA', 'AHMAD', '08012345978', NULL, '2008-07-16', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-07 10:09:49', '2025-11-07 10:09:49'),
(9, 'hassan.musa', 'hassanu@gmail.com', '$2y$10$JxqbDVvw5In0JM4EIbufGO.uIy3.OfJjZfz/soqyGBGjXULSXe6Ki', 'student', 'Hassan', 'Musa', '09033664643', NULL, '2020-02-22', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-07 10:11:56', '2025-11-07 10:11:56'),
(10, 'ahmad.adamu', 'assoadeeq@gmail.com', '$2y$10$sQjAc6Cs.E0HRhNtFlWlDO3hmnf1Wxw9jB45.dgx/JTjRVnAzh5gG', 'teacher', 'Ahmad', 'Adamu', '09033443344', NULL, '1990-02-22', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-07 10:17:47', '2025-11-07 10:17:47'),
(11, 'ahmad.adamu325', 'dankwarai02@gmail.com', '$2y$10$70K/PZXp6OdxUUk8IleMp.A2Wu.2wfFO0nLxzWsaJfW64nj0U0sHW', 'staff', 'Ahmad', 'Adamu', '08012345978', NULL, NULL, NULL, NULL, 0, '2025-11-07 11:25:36', 1, 0, NULL, NULL, NULL, NULL, '2025-11-07 11:25:36', 0, NULL, '2025-11-07 10:21:12', '2025-11-07 10:26:37'),
(12, 'teacher.musa', 'musa.ahmed@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Musa', 'Ahmed', '08031112222', '123 Education Road, Kano', '1985-03-15', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(13, 'teacher.fatima', 'fatima.yusuf@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Fatima', 'Yusuf', '08041113333', '456 Learning Avenue, Kano', '1990-07-22', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(14, 'student.ahmad', 'ahmad.sani@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Ahmad', 'Sani', '08051114444', '789 Student Lane, Kano', '2012-08-10', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(15, 'student.amina', 'amina.mohammed@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Amina', 'Mohammed', '08061115555', '321 Scholar Street, Kano', '2013-01-25', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(16, 'student.kabiru', 'kabiru.ali@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Kabiru', 'Ali', '08071116666', '654 Education Road, Kano', '2011-11-30', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(17, 'student.zainab', 'zainab.ibrahim@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Zainab', 'Ibrahim', '08081117777', '987 Learning Avenue, Kano', '2012-04-18', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(18, 'student.umar', 'umar.adam@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Umar', 'Adam', '08091118888', '147 Student Lane, Kano', '2013-06-05', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 13:00:00', '2025-11-11 13:00:00'),
(19, 'student.chiamaka', 'chiamaka.okafor@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Chiamaka', 'Okafor', '08101112222', NULL, '2017-03-15', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:18:22'),
(20, 'student.tunde', 'tunde.adebayo@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Tunde', 'Adebayo', '08111113333', NULL, '2017-07-22', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:18:22'),
(21, 'student.blessing', 'blessing.uwakwe@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Blessing', 'Uwakwe', '08121114444', NULL, '2017-11-08', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:18:22'),
(22, 'student.emeka', 'emeka.nwosu@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Emeka', 'Nwosu', '08131115555', NULL, '2017-05-30', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:18:22'),
(23, 'student.aminat', 'aminat.yusuf@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Aminat', 'Yusuf', '08141116666', NULL, '2017-09-14', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:18:22'),
(28, 'student.zara.m', 'zara.mohammed@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Zara', 'Mohammed', '08151117776', NULL, '2022-01-10', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-12 11:52:26'),
(29, 'student.khalid.i', 'khalid.ibrahim@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Khalid', 'Ibrahim', '08161118888', NULL, '2022-04-25', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:22:18'),
(30, 'student.aisha.u', 'aisha.umar@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Aisha', 'Umar', '08171119999', NULL, '2022-08-15', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:22:18'),
(31, 'student.musa.a', 'musa.ali@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Musa', 'Ali', '08181110000', NULL, '2022-11-30', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:22:18'),
(32, 'student.fatima.h', 'fatima.hassan@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Fatima', 'Hassan', '08191111111', NULL, '2022-06-18', 'Female', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-11 14:00:00', '2025-11-11 16:22:18'),
(34, 'abbati .tukur', 'tukur@gmail.com', '$2y$10$1oy31J2cdqahGDNT2qhX1e0dA.6jhSzT3WeK0xWvcr3ijuPmUVx92', 'student', 'abbati ', 'tukur', '0909996646', NULL, '2010-06-09', 'Male', NULL, 1, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-15 13:29:13', '2025-11-15 13:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permissions` json NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `role_name`, `permissions`, `description`, `created_at`) VALUES
(1, 'admin', '[\"users.manage\", \"students.manage\", \"teachers.manage\", \"finance.manage\", \"reports.view\", \"settings.manage\"]', 'Full system administrator', '2025-10-23 11:37:21'),
(2, 'principal', '[\"students.manage\", \"teachers.manage\", \"reports.view\", \"attendance.manage\"]', 'School principal with management access', '2025-10-23 11:37:21'),
(3, 'teacher', '[\"students.view\", \"attendance.record\", \"results.manage\", \"timetable.view\"]', 'Teaching staff with academic access', '2025-10-23 11:37:21'),
(4, 'student', '[\"profile.view\", \"results.view\", \"attendance.view\", \"timetable.view\"]', 'Student access to personal information', '2025-10-23 11:37:21'),
(5, 'staff', '[\"profile.view\", \"inventory.manage\", \"library.manage\"]', 'Non-teaching staff access', '2025-10-23 11:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `last_activity`, `expires_at`, `created_at`) VALUES
(1, 11, '55aa1a574c4a38ad4583d259a7d7993936f966bfd75ef9f6595f56b2774f6ee3', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0', '2025-11-07 10:25:36', '2025-11-08 09:25:36', '2025-11-07 10:25:36'),
(2, 1, '4956b6264051aa57af5191f1e0e635ff660e502ab4c17559ff7c4c19fe99fd41', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 12:48:20', '2025-11-10 11:48:20', '2025-11-09 12:48:20'),
(3, 1, 'd493a40b00b949f69ffc7a54b510821f5d19631b330854d17a1e361061180c83', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 12:50:43', '2025-11-10 11:50:43', '2025-11-09 12:50:43'),
(4, 2, '9ea349b238441d37c16637795f4c5b19d74558a48fb97813b4b5617fdb7ed98b', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-09 13:50:43', '2025-11-10 12:50:43', '2025-11-09 13:50:43'),
(5, 3, '05d5fbcc6dda1139a177891f45a29a0e0856b7ccb23f8159861f1e535dabf948', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:05:30', '2025-11-12 13:05:30', '2025-11-11 14:05:30'),
(6, 3, 'bef199841827c5e1ca7f839c42d33bef92a6a0ebce57c51f8891373e9dee5ab4', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:11:08', '2025-11-12 13:11:08', '2025-11-11 14:11:08'),
(7, 3, 'ce7b9a524a818958fe0b8d7d501969be9ece149280373a74a3f3d352aa3b9714', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:11:55', '2025-11-12 13:11:55', '2025-11-11 14:11:55'),
(8, 3, '176034b4b3f8b85b295f452f594eac7913f1d05132cb22974efb15e037297f11', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 14:54:48', '2025-11-12 13:54:48', '2025-11-11 14:54:48'),
(9, 3, '02e2bc9d08b998518b1f21aa8bb0662993b7a2921a5d8ab208293864e98ebda5', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 15:04:39', '2025-11-12 14:04:39', '2025-11-11 15:04:39'),
(10, 3, '27ee6ab94582acc4f54311bfbaf80fddc07ce6c718decaf5e28879c2c3a0f05f', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 15:06:04', '2025-11-12 14:06:04', '2025-11-11 15:06:04'),
(11, 3, 'f4015618bc0534a1b7caf9378322c2a3fa9d37605271eb8c3734837af2b2f4f2', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 15:24:48', '2025-11-12 14:24:48', '2025-11-11 15:24:48'),
(12, 3, '5c466f4c29f350496d6611215de7a14a3d5f853c588a16528e3de600a7644a97', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-11 15:25:13', '2025-11-12 14:25:13', '2025-11-11 15:25:13'),
(13, 3, 'a4208e133e7e60a4a8cde0fb048fe3aa67e15f4bdcfb0d43e984589c271fd461', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 10:10:19', '2025-11-13 09:10:19', '2025-11-12 10:10:19'),
(14, 3, '8279c77f954b9c23ae9f97340562a98ac6e4f72cfbfbfc7a455bca08c478ebfc', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-12 11:51:15', '2025-11-13 10:51:15', '2025-11-12 11:51:15'),
(15, 3, '0f009a547d5fdae654e1dbd1d2a4f8a2c501c51b6d783cc2ef2ac8ad6239dd9f', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-15 10:56:18', '2025-11-16 09:56:18', '2025-11-15 10:56:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_attendance` (`student_id`,`attendance_date`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `academic_session_id` (`academic_session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `idx_attendance_student_date` (`student_id`,`attendance_date`),
  ADD KEY `idx_attendance_class_date` (`class_id`,`attendance_date`);

--
-- Indexes for table `auth_audit_log`
--
ALTER TABLE `auth_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_auth_audit_user` (`user_id`),
  ADD KEY `idx_auth_audit_event` (`event_type`),
  ADD KEY `idx_auth_audit_created` (`created_at`);

--
-- Indexes for table `book_borrowing`
--
ALTER TABLE `book_borrowing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_book_borrowing_student_status` (`student_id`,`status`),
  ADD KEY `idx_book_borrowing_due_date` (`due_date`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `class_teacher_id` (`class_teacher_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_subject` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_type_id` (`exam_type_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `academic_session_id` (`academic_session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_types`
--
ALTER TABLE `exam_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_code` (`exam_code`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `academic_session_id` (`academic_session_id`),
  ADD KEY `term_id` (`term_id`);

--
-- Indexes for table `generated_reports`
--
ALTER TABLE `generated_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `academic_session_id` (`academic_session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `idx_payments_student_date` (`student_id`,`payment_date`);

--
-- Indexes for table `report_schedules`
--
ALTER TABLE `report_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_exam_subject` (`student_id`,`exam_id`,`subject_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `idx_results_student_exam` (`student_id`,`exam_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_code` (`school_code`),
  ADD KEY `principal_id` (`principal_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`),
  ADD KEY `idx_students_class_id` (`class_id`),
  ADD KEY `idx_students_parent_id` (`parent_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_session_id` (`academic_session_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `academic_session_id` (`academic_session_id`),
  ADD KEY `term_id` (`term_id`),
  ADD KEY `idx_timetable_class_day` (`class_id`,`day_of_week`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_user_type` (`user_type`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_sessions_token` (`session_token`),
  ADD KEY `idx_user_sessions_user_id` (`user_id`),
  ADD KEY `idx_user_sessions_expires` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `auth_audit_log`
--
ALTER TABLE `auth_audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `book_borrowing`
--
ALTER TABLE `book_borrowing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_types`
--
ALTER TABLE `exam_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fee_structure`
--
ALTER TABLE `fee_structure`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `generated_reports`
--
ALTER TABLE `generated_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `report_schedules`
--
ALTER TABLE `report_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD CONSTRAINT `admin_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `auth_audit_log`
--
ALTER TABLE `auth_audit_log`
  ADD CONSTRAINT `auth_audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `book_borrowing`
--
ALTER TABLE `book_borrowing`
  ADD CONSTRAINT `book_borrowing_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_borrowing_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD CONSTRAINT `fee_structure_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_structure_ibfk_2` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_structure_ibfk_3` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schools`
--
ALTER TABLE `schools`
  ADD CONSTRAINT `schools_ibfk_1` FOREIGN KEY (`principal_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD CONSTRAINT `teacher_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `terms_ibfk_1` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_4` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_5` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
