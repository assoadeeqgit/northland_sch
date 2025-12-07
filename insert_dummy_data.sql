-- ============================================================================
-- COMPREHENSIVE DUMMY DATA INSERTION SCRIPT
-- Purpose: Insert realistic dummy data for unified dashboard
-- Database: northland_schools_kano
-- Created: 2025-12-07
-- ============================================================================

USE northland_schools_kano;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- ============================================================================
-- CLEAN EXISTING DATA (Optional - uncomment if you want fresh data)
-- ============================================================================

-- TRUNCATE TABLE assignment_submissions;
-- TRUNCATE TABLE attendance;
-- TRUNCATE TABLE payments;
-- TRUNCATE TABLE results;
-- TRUNCATE TABLE assignments;
-- DELETE FROM users WHERE id > 34;
-- DELETE FROM students WHERE id > 20;
-- DELETE FROM teachers WHERE id > 5;

-- ============================================================================
-- 1. ACADEMIC SESSIONS (Additional sessions)
-- ============================================================================

INSERT INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(2, '2024/2025', '2024-09-01', '2025-08-31', 1, '2025-10-23 11:37:21'),
(3, '2025/2026', '2025-09-01', '2026-08-31', 0, '2025-10-23 11:37:21')
ON DUPLICATE KEY UPDATE session_name=VALUES(session_name);

-- ============================================================================
-- 2. ADDITIONAL USERS (Teachers, Students, Staff)
-- ============================================================================

-- Additional Teachers
INSERT INTO `users` (`username`, `email`, `password_hash`, `user_type`, `first_name`, `last_name`, `phone`, `date_of_birth`, `gender`, `is_active`, `registration_step`, `email_verified`) VALUES
('maryam.danjuma', 'maryam.danjuma@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Maryam', 'Danjuma', '08091234567', '1988-06-18', 'Female', 1, 1, 1),
('ibrahim.salisu', 'ibrahim.salisu@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Ibrahim', 'Salisu', '08102345678', '1985-09-22', 'Male', 1, 1, 1),
('hauwa.bala', 'hauwa.bala@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Hauwa', 'Bala', '08113456789', '1992-03-10', 'Female', 1, 1, 1),
('yunusa.garba', 'yunusa.garba@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Yunusa', 'Garba', '08124567890', '1987-11-05', 'Male', 1, 1, 1),
('fatima.abubakar', 'fatima.abubakar@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Fatima', 'Abubakar', '08135678901', '1990-07-28', 'Female', 1, 1, 1)
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Get the IDs of newly inserted teachers
SET @teacher6_id = (SELECT id FROM users WHERE email = 'maryam.danjuma@northland.edu.ng' LIMIT 1);
SET @teacher7_id = (SELECT id FROM users WHERE email = 'ibrahim.salisu@northland.edu.ng' LIMIT 1);
SET @teacher8_id = (SELECT id FROM users WHERE email = 'hauwa.bala@northland.edu.ng' LIMIT 1);
SET @teacher9_id = (SELECT id FROM users WHERE email = 'yunusa.garba@northland.edu.ng' LIMIT 1);
SET @teacher10_id = (SELECT id FROM users WHERE email = 'fatima.abubakar@northland.edu.ng' LIMIT 1);

-- Additional Students (15 more students)
INSERT INTO `users` (`username`, `email`, `password_hash`, `user_type`, `first_name`, `last_name`, `phone`, `date_of_birth`, `gender`, `is_active`, `registration_step`, `email_verified`) VALUES
('student.yakubu', 'yakubu.hassan@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Yakubu', 'Hassan', '08141234567', '2012-05-14', 'Male', 1, 1, 1),
('student.aisha.m', 'aisha.mahmud@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Aisha', 'Mahmud', '08152345678', '2013-08-22', 'Female', 1, 1, 1),
('student.bashir', 'bashir.usman@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Bashir', 'Usman', '08163456789', '2011-12-08', 'Male', 1, 1, 1),
('student.halima', 'halima.bello@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Halima', 'Bello', '08174567890', '2012-03-15', 'Female', 1, 1, 1),
('student.abdullahi.k', 'abdullahi.kano@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Abdullahi', 'Kano', '08185678901', '2013-06-20', 'Male', 1, 1, 1),
('student.zainab.s', 'zainab.sani@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Zainab', 'Sani', '08196789012', '2012-09-11', 'Female', 1, 1, 1),
('student.mohammed.a', 'mohammed.adamu@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Mohammed', 'Adamu', '08201234567', '2011-11-28', 'Male', 1, 1, 1),
('student.khadija', 'khadija.yusuf@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Khadija', 'Yusuf', '08212345678', '2013-04-17', 'Female', 1, 1, 1),
('student.sadiq', 'sadiq.muhammad@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Sadiq', 'Muhammad', '08223456789', '2012-07-09', 'Male', 1, 1, 1),
('student.hafsat', 'hafsat.ibrahim@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Hafsat', 'Ibrahim', '08234567890', '2013-01-25', 'Female', 1, 1, 1),
('student.sulaiman', 'sulaiman.ali@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Sulaiman', 'Ali', '08245678901', '2017-05-12', 'Male', 1, 1, 1),
('student.amina.b', 'amina.bala@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Amina', 'Bala', '08256789012', '2017-08-30', 'Female', 1, 1, 1),
('student.ahmad.m', 'ahmad.musa@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Ahmad', 'Musa', '08267890123', '2017-11-19', 'Male', 1, 1, 1),
('student.maryam.i', 'maryam.isa@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Maryam', 'Isa', '08278901234', '2017-02-07', 'Female', 1, 1, 1),
('student.yusuf.a', 'yusuf.ahmad@northland.edu.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Yusuf', 'Ahmad', '08289012345', '2017-06-23', 'Male', 1, 1, 1)
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- ============================================================================
-- 3. TEACHER PROFILES
-- ============================================================================

INSERT INTO `teachers` (`user_id`, `teacher_id`, `qualification`, `specialization`, `employment_date`, `salary_grade`, `bank_name`, `account_number`, `is_class_teacher`) VALUES
(@teacher6_id, 'TCH006', 'B.Ed English', 'English Language', '2020-03-15', 'CONUAS 6', 'UBA', '3056789012', 1),
(@teacher7_id, 'TCH007', 'M.Sc Chemistry', 'Chemistry, Biology', '2019-08-20', 'CONUAS 7', 'Access Bank', '3067890123', 1),
(@teacher8_id, 'TCH008', 'B.Sc Economics', 'Economics, Commerce', '2021-01-10', 'CONUAS 5', 'Zenith Bank', '3078901234', 0),
(@teacher9_id, 'TCH009', 'B.A Hausa', 'Hausa Language', '2018-09-05', 'CONUAS 8', 'First Bank', '3089012345', 1),
(@teacher10_id, 'TCH010', 'B.Sc Geography', 'Geography', '2022-02-28', 'CONUAS 5', 'GTBank', '3090123456', 0)
ON DUPLICATE KEY UPDATE teacher_id=VALUES(teacher_id);

INSERT INTO `teacher_profiles` (`user_id`, `qualification`, `subject_specialization`, `years_experience`, `department`, `employment_type`, `teacher_id`) VALUES
(@teacher6_id, 'B.Ed English', 'English Language', '3-5 years', 'Arts', 'Full-time', 'TCH006'),
(@teacher7_id, 'M.Sc Chemistry', 'Chemistry, Biology', '6-10 years', 'Science', 'Full-time', 'TCH007'),
(@teacher8_id, 'B.Sc Economics', 'Economics, Commerce', '1-3 years', 'Social Sciences', 'Full-time', 'TCH008'),
(@teacher9_id, 'B.A Hausa', 'Hausa Language', '10+ years', 'Languages', 'Full-time', 'TCH009'),
(@teacher10_id, 'B.Sc Geography', 'Geography', '1-3 years', 'Social Sciences', 'Part-time', 'TCH010')
ON DUPLICATE KEY UPDATE teacher_id=VALUES(teacher_id);

-- ============================================================================
-- 4. STUDENT RECORDS
-- ============================================================================

-- Get student user IDs
SET @student21_id = (SELECT id FROM users WHERE email = 'yakubu.hassan@northland.edu.ng' LIMIT 1);
SET @student22_id = (SELECT id FROM users WHERE email = 'aisha.mahmud@northland.edu.ng' LIMIT 1);
SET @student23_id = (SELECT id FROM users WHERE email = 'bashir.usman@northland.edu.ng' LIMIT 1);
SET @student24_id = (SELECT id FROM users WHERE email = 'halima.bello@northland.edu.ng' LIMIT 1);
SET @student25_id = (SELECT id FROM users WHERE email = 'abdullahi.kano@northland.edu.ng' LIMIT 1);

INSERT INTO `students` (`user_id`, `student_id`, `admission_number`, `class_id`, `admission_date`, `religion`, `nationality`, `state_of_origin`, `lga`, `emergency_contact_name`, `emergency_contact_phone`) VALUES
(@student21_id, 'STU021', 'ADM021', 10, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Kano Municipal', 'Alhaji Hassan', '08141118888'),
(@student22_id, 'STU022', 'ADM022', 10, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Nassarawa', 'Hajiya Mahmud', '08152229999'),
(@student23_id, 'STU023', 'ADM023', 11, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Gwale', 'Malam Usman', '08163330000'),
(@student24_id, 'STU024', 'ADM024', 11, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Fagge', 'Alhaji Bello', '08174441111'),
(@student25_id, 'STU025', 'ADM025', 11, '2024-09-01', 'Islam', 'Nigerian', 'Kano', 'Dala', 'Mr. Kano', '08185552222')
ON DUPLICATE KEY UPDATE student_id=VALUES(student_id);

INSERT INTO `student_profiles` (`user_id`, `date_of_birth`, `gender`, `class_level`, `parent_name`, `parent_phone`, `medical_info`) VALUES
(@student21_id, '2012-05-14', 'Male', 'Secondary', 'Alhaji Hassan', '08141118888', NULL),
(@student22_id, '2013-08-22', 'Female', 'Secondary', 'Hajiya Mahmud', '08152229999', NULL),
(@student23_id, '2011-12-08', 'Male', 'Secondary', 'Malam Usman', '08163330000', NULL),
(@student24_id, '2012-03-15', 'Female', 'Secondary', 'Alhaji Bello', '08174441111', 'Mild asthma'),
(@student25_id, '2013-06-20', 'Male', 'Secondary', 'Mr. Kano', '08185552222', NULL)
ON DUPLICATE KEY UPDATE user_id=VALUES(user_id);

-- ============================================================================
-- 5. EXAMS AND RESULTS
-- ============================================================================

-- Create exams for current session and term
INSERT INTO `exams` (`exam_type_id`, `class_id`, `subject_id`, `exam_date`, `total_marks`, `passing_marks`, `academic_session_id`, `term_id`, `created_by`) VALUES
(1, 10, 1, '2024-11-20', 100.00, 40.00, 2, 3, 1),
(1, 10, 2, '2024-11-21', 100.00, 40.00, 2, 3, 1),
(1, 10, 3, '2024-11-22', 100.00, 40.00, 2, 3, 1),
(1, 10, 4, '2024-11-23', 100.00, 40.00, 2, 3, 1),
(1, 10, 5, '2024-11-24', 100.00, 40.00, 2, 3, 1),
(1, 11, 1, '2024-11-20', 100.00, 40.00, 2, 3, 1),
(1, 11, 2, '2024-11-21', 100.00, 40.00, 2, 3, 1),
(1, 11, 3, '2024-11-22', 100.00, 40.00, 2, 3, 1),
(1, 12, 1, '2024-11-20', 100.00, 40.00, 2, 3, 1),
(1, 12, 2, '2024-11-21', 100.00, 40.00, 2, 3, 1)
ON DUPLICATE KEY UPDATE exam_date=VALUES(exam_date);

-- Insert results for JSS 1 students (class_id=10)
INSERT INTO `results` (`student_id`, `exam_id`, `subject_id`, `class_id`, `academic_session_id`, `term_id`, `marks_obtained`, `total_marks`, `grade`, `grade_point`, `position_in_class`, `position_in_subject`, `remarks`, `created_by`) VALUES
-- Student 2 (Fatima Kareem) - JSS 1
(2, 1, 1, 10, 2, 3, 75.00, 100.00, 'B', 3.50, 2, 2, 'Good performance', 1),
(2, 2, 2, 10, 2, 3, 82.00, 100.00, 'A', 4.00, 1, 1, 'Excellent!', 1),
(2, 3, 3, 10, 2, 3, 68.00, 100.00, 'C', 3.00, 3, 3, 'Satisfactory', 1),
-- Student 5 (Ahmad Sani) - JSS 1
(5, 1, 1, 10, 2, 3, 88.00, 100.00, 'A', 4.50, 1, 1, 'Outstanding work!', 1),
(5, 2, 2, 10, 2, 3, 79.00, 100.00, 'B', 3.75, 2, 2, 'Very good', 1),
(5, 3, 3, 10, 2, 3, 91.00, 100.00, 'A', 4.75, 1, 1, 'Exceptional!', 1),
-- Student 6 (Amina Mohammed) - JSS 1
(6, 1, 1, 10, 2, 3, 72.00, 100.00, 'B', 3.25, 3, 3, 'Good effort', 1),
(6, 2, 2, 10, 2, 3, 65.00, 100.00, 'C', 3.00, 4, 4, 'Can improve', 1),
-- New students
(@student21_id, 1, 1, 10, 2, 3, 78.00, 100.00, 'B', 3.60, 2, 2, 'Good work', 1),
(@student21_id, 2, 2, 10, 2, 3, 85.00, 100.00, 'A', 4.25, 1, 1, 'Excellent', 1),
(@student22_id, 1, 1, 10, 2, 3, 69.00, 100.00, 'C', 3.10, 4, 4, 'Satisfactory', 1),
(@student22_id, 2, 2, 10, 2, 3, 74.00, 100.00, 'B', 3.50, 3, 3, 'Good', 1)
ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained);

-- JSS 2 students results
INSERT INTO `results` (`student_id`, `exam_id`, `subject_id`, `class_id`, `academic_session_id`, `term_id`, `marks_obtained`, `total_marks`, `grade`, `grade_point`, `position_in_class`, `position_in_subject`, `remarks`, `created_by`) VALUES
(@student23_id, 6, 1, 11, 2, 3, 82.00, 100.00, 'A', 4.00, 1, 1, 'Excellent work', 1),
(@student23_id, 7, 2, 11, 2, 3, 76.00, 100.00, 'B', 3.60, 2, 2, 'Very good', 1),
(@student24_id, 6, 1, 11, 2, 3, 71.00, 100.00, 'B', 3.30, 3, 3, 'Good performance', 1),
(@student24_id, 7, 2, 11, 2, 3, 79.00, 100.00, 'B', 3.75, 2, 2, 'Good work', 1),
(@student25_id, 6, 1, 11, 2, 3, 88.00, 100.00, 'A', 4.50, 1, 1, 'Outstanding!', 1),
(@student25_id, 7, 2, 11, 2, 3, 92.00, 100.00, 'A', 4.75, 1, 1, 'Exceptional!', 1)
ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained);

-- ============================================================================
-- 6. ASSIGNMENTS
-- ============================================================================

INSERT INTO `assignments` (`teacher_id`, `class_id`, `subject_id`, `title`, `description`, `due_date`, `total_points`, `type`, `allow_late_submission`, `status`) VALUES
(1, 10, 1, 'Essay Writing - My School', 'Write a 300-word essay about your school', '2024-12-15', 20, 'Homework', 1, 'Published'),
(2, 10, 2, 'Algebra Problems', 'Solve questions 1-20 from chapter 5', '2024-12-18', 40, 'Homework', 0, 'Published'),
(1, 11, 1, 'Comprehension Test', 'Read passage and answer questions', '2024-12-20', 30, 'Quiz', 0, 'Published'),
(2, 11, 2, 'Geometry Project', 'Create a project on triangles and their properties', '2024-12-25', 50, 'Project', 1, 'Published'),
(1, 5, 1, 'Spelling Words', 'Practice writing spelling words 10 times each', '2024-12-12', 10, 'Homework', 1, 'Published')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- ============================================================================
-- 7. ASSIGNMENT SUBMISSIONS
-- ============================================================================

SET @assignment1_id = (SELECT id FROM assignments WHERE title = 'Essay Writing - My School' LIMIT 1);
SET @assignment2_id = (SELECT id FROM assignments WHERE title = 'Algebra Problems' LIMIT 1);

INSERT INTO `assignment_submissions` (`assignment_id`, `student_id`, `submission_text`, `score`, `feedback`, `graded_by`, `status`) VALUES
(@assignment1_id, 2, 'My school is located in Kano. It has beautiful buildings and friendly teachers...', 18.00, 'Well written!', 1, 'Graded'),
(@assignment1_id, 5, 'Northland Schools is one of the best schools in Kano...', 20.00, 'Excellent work!', 1, 'Graded'),
(@assignment2_id, 2, 'Solutions submitted', 35.00, 'Good attempt, work on question 15', 2, 'Graded'),
(@assignment2_id, 5, 'All solutions completed', 40.00, 'Perfect!', 2, 'Graded'),
(@assignment1_id, @student21_id, 'I love my school because it has great facilities...', 17.00, 'Good work', 1, 'Graded')
ON DUPLICATE KEY UPDATE score=VALUES(score);

-- ============================================================================
-- 8. ATTENDANCE RECORDS
-- ============================================================================

-- Generate attendance for the last 5 school days
INSERT INTO `attendance` (`student_id`, `class_id`, `attendance_date`, `status`, `remarks`, `recorded_by`, `academic_session_id`, `term_id`) VALUES
-- December 2nd
(2, 10, '2024-12-02', 'Present', NULL, 1, 2, 3),
(5, 10, '2024-12-02', 'Present', NULL, 1, 2, 3),
(6, 10, '2024-12-02', 'Late', 'Arrived 15 mins late', 1, 2, 3),
(@student21_id, 10, '2024-12-02', 'Present', NULL, 1, 2, 3),
(@student22_id, 10, '2024-12-02', 'Absent', 'Sick', 1, 2, 3),
-- December 3rd
(2, 10, '2024-12-03', 'Present', NULL, 1, 2, 3),
(5, 10, '2024-12-03', 'Present', NULL, 1, 2, 3),
(6, 10, '2024-12-03', 'Present', NULL, 1, 2, 3),
(@student21_id, 10, '2024-12-03', 'Present', NULL, 1, 2, 3),
(@student22_id, 10, '2024-12-03', 'Present', 'Recovered', 1, 2, 3),
-- December 4th
(2, 10, '2024-12-04', 'Present', NULL, 1, 2, 3),
(5, 10, '2024-12-04', 'Present', NULL, 1, 2, 3),
(6, 10, '2024-12-04', 'Present', NULL, 1, 2, 3),
(@student21_id, 10, '2024-12-04', 'Late', 'Traffic', 1, 2, 3),
(@student22_id, 10, '2024-12-04', 'Present', NULL, 1, 2, 3),
-- December 5th
(@student23_id, 11, '2024-12-05', 'Present', NULL, 1, 2, 3),
(@student24_id, 11, '2024-12-05', 'Present', NULL, 1, 2, 3),
(@student25_id, 11, '2024-12-05', 'Present', NULL, 1, 2, 3),
-- December 6th
(@student23_id, 11, '2024-12-06', 'Present', NULL, 1, 2, 3),
(@student24_id, 11, '2024-12-06', 'Absent', 'Family event', 1, 2, 3),
(@student25_id, 11, '2024-12-06', 'Present', NULL, 1, 2, 3)
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- ============================================================================
-- 9. FEE STRUCTURE AND PAYMENTS
-- ============================================================================

-- Fee structure for the new session
INSERT INTO `fee_structure` (`class_id`, `fee_type`, `amount`, `academic_session_id`, `term_id`, `due_date`, `is_active`) VALUES
(10, 'Tuition Fee', 30000.00, 2, 3, '2024-12-31', 1),
(10, 'Development Levy', 6000.00, 2, 3, '2024-12-31', 1),
(10, 'Sports Fee', 2500.00, 2, 3, '2024-12-31', 1),
(10, 'Library Fee', 1500.00, 2, 3, '2024-12-31', 1),
(11, 'Tuition Fee', 32000.00, 2, 3, '2024-12-31', 1),
(11, 'Development Levy', 6000.00, 2, 3, '2024-12-31', 1),
(11, 'Sports Fee', 2500.00, 2, 3, '2024-12-31', 1),
(5, 'Tuition Fee', 20000.00, 2, 3, '2024-12-31', 1),
(5, 'Development Levy', 3500.00, 2, 3, '2024-12-31', 1)
ON DUPLICATE KEY UPDATE amount=VALUES(amount);

-- Payment records
SET @fee1_id = (SELECT id FROM fee_structure WHERE class_id=10 AND fee_type='Tuition Fee' AND academic_session_id=2 LIMIT 1);
SET @fee2_id = (SELECT id FROM fee_structure WHERE class_id=11 AND fee_type='Tuition Fee' AND academic_session_id=2 LIMIT 1);

INSERT INTO `payments` (`student_id`, `fee_structure_id`, `amount_paid`, `payment_date`, `payment_method`, `transaction_id`, `received_by`, `academic_session_id`, `term_id`, `remarks`) VALUES
(2, @fee1_id, 30000.00, '2024-11-15', 'Bank Transfer', 'TXN2024111501', 1, 2, 3, 'Full payment'),
(5, @fee1_id, 30000.00, '2024-11-18', 'Cash', NULL, 1, 2, 3, 'Full payment'),
(6, @fee1_id, 15000.00, '2024-11-20', 'POS', 'POS2024112001', 1, 2, 3, 'First installment'),
(@student21_id, @fee1_id, 30000.00, '2024-11-22', 'Bank Transfer', 'TXN2024112201', 1, 2, 3, 'Full payment'),
(@student22_id, @fee1_id, 20000.00, '2024-11-25', 'Cash', NULL, 1, 2, 3, 'Partial payment'),
(@student23_id, @fee2_id, 32000.00, '2024-11-16', 'Bank Transfer', 'TXN2024111602', 1, 2, 3, 'Full payment'),
(@student24_id, @fee2_id, 32000.00, '2024-11-19', 'Online', 'ONLINE2024111901', 1, 2, 3, 'Full payment'),
(@student25_id, @fee2_id, 16000.00, '2024-11-21', 'Cash', NULL, 1, 2, 3, 'First installment')
ON DUPLICATE KEY UPDATE amount_paid=VALUES(amount_paid);

-- ============================================================================
-- 10. EVENTS
-- ============================================================================

INSERT INTO `events` (`event_title`, `event_description`, `event_date`, `start_time`, `end_time`, `venue`, `target_audience`, `created_by`) VALUES
('End of Term Examination', 'Third term final examinations', '2024-12-15', '08:00:00', '14:00:00', 'Examination Hall', 'Students', 1),
('Parent-Teacher Conference', 'Discussion on students progress', '2024-12-20', '10:00:00', '15:00:00', 'School Hall', 'Parents', 1),
('Christmas Carol Service', 'Annual Christmas celebration', '2024-12-22', '16:00:00', '18:00:00', 'School Chapel', 'All', 1),
('Staff Year-End Party', 'Staff appreciation event', '2024-12-23', '18:00:00', '22:00:00', 'Staff Lounge', 'Teachers', 1)
ON DUPLICATE KEY UPDATE event_title=VALUES(event_title);

-- ============================================================================
-- 11. NOTICES
-- ============================================================================

INSERT INTO `notices` (`title`, `content`, `target_audience`, `priority`, `publish_date`, `expiry_date`, `created_by`, `is_active`) VALUES
('End of Term Reminder', 'The third term ends on December 22nd, 2024. All students should ensure they complete their assignments and examinations.', 'Students', 'High', '2024-12-01', '2024-12-22', 1, 1),
('Parent Meeting Notice', 'All parents are invited to attend the parent-teacher conference on December 20th. This is mandatory.', 'Parents', 'High', '2024-12-01', '2024-12-20', 1, 1),
('Library Hours Extended', 'The library will be open until 6 PM during examination period (Dec 10-20).', 'Students', 'Medium', '2024-12-05', '2024-12-20', 1, 1),
('Holiday Closure', 'School will be closed from December 23rd to January 7th for Christmas and New Year holidays.', 'All', 'Medium', '2024-12-07', '2025-01-07', 1, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- ============================================================================
-- 12. LIBRARY BOOKS (Additional)
-- ============================================================================

INSERT INTO `library_books` (`isbn`, `title`, `author`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`, `shelf_location`) VALUES
('9780141182803', 'Purple Hibiscus', 'Chimamanda Ngozi Adichie', 'Fourth Estate', '2003', 'Literature', 4, 3, 'A1-05'),
('9780140449334', 'An Introduction to Mathematics', 'A.N. Whitehead', 'Cambridge Press', '2018', 'Mathematics', 10, 8, 'C3-15'),
('9780323087865', 'Human Anatomy & Physiology', 'Elaine N. Marieb', 'Pearson', '2019', 'Science', 6, 4, 'C3-18'),
('9780199536986', 'Oxford Advanced Learner''s Dictionary', 'Oxford', 'Oxford University Press', '2020', 'Reference', 15, 13, 'A1-30'),
('9780141439525', 'Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', 'Penguin Books', '2006', 'Literature', 3, 2, 'A1-06')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- ============================================================================
-- 13. INVENTORY ITEMS (Additional)
-- ============================================================================

INSERT INTO `inventory` (`item_name`, `item_code`, `category`, `quantity`, `min_quantity`, `unit_price`, `supplier`, `storage_location`, `description`) VALUES
('Mathematics Set', 'MSET-01', 'Stationery', 80, 20, 500.00, 'School Supplies Ltd', 'Store Room A', 'Complete mathematics set with compass and protractor'),
('Lab Coats (Medium)', 'LABCOAT-M', 'Science Lab', 30, 10, 1500.00, 'Lab Equipment Co.', 'Science Lab Store', 'White lab coats size medium'),
('Footballs', 'BALL-FB', 'Sports', 15, 5, 3500.00, 'Sports Direct', 'Sports Room', 'Standard size 5 footballs'),
('First Aid Kit', 'AID-KIT', 'Medical', 8, 3, 2500.00, 'Medical Supplies', 'Clinic', 'Complete first aid kit'),
('Projector Bulbs', 'PROJ-BULB', 'Electronics', 5, 2, 8000.00, 'TechGadgets Ltd', 'Store Room B', 'Replacement bulbs for classroom projectors')
ON DUPLICATE KEY UPDATE item_code=VALUES(item_code);

-- ============================================================================
-- 14. ACTIVITY LOG (Additional)
-- ============================================================================

INSERT INTO `activity_log` (`user_name`, `action_type`, `description`, `performed_by`, `icon`, `color`, `created_at`) VALUES
('Admin', 'New Students', 'Enrolled 5 new students for 2024/2025 session', 'Admin', 'fas fa-user-plus', 'bg-nsklightblue', '2024-11-25 09:30:00'),
('Admin', 'Fee Structure', 'Updated fee structure for JSS 1-3', 'Admin', 'fas fa-money-bill', 'bg-nskgreen', '2024-11-26 10:15:00'),
('Ahmad Adamu', 'Results Upload', 'Uploaded results for JSS 1 English exams', 'Ahmad Adamu', 'fas fa-file-upload', 'bg-nskblue', '2024-11-28 14:22:00'),
('Hamza Ali', 'Results Upload', 'Uploaded results for JSS 1 Mathematics exams', 'Hamza Ali', 'fas fa-file-upload', 'bg-nskblue', '2024-11-28 14:45:00'),
('Admin', 'System Update', 'Database unified - Teacher and Admin dashboards merged', 'Admin', 'fas fa-database', 'bg-nskgold', '2024-12-07 11:04:28')
ON DUPLICATE KEY UPDATE user_name=VALUES(user_name);

-- ============================================================================
-- 15. REPORT SCHEDULES AND GENERATED REPORTS
-- ============================================================================

INSERT INTO `report_schedules` (`report_type`, `frequency`, `recipient_email`, `created_by`) VALUES
('attendance', 'Daily', 'principal@northland.edu.ng', 'Admin'),
('academic', 'Weekly', 'admin@northland.edu.ng', 'Admin'),
('financial', 'Monthly', 'accounts@northland.edu.ng', 'Admin'),
('student_performance', 'Term', 'principal@northland.edu.ng', 'Admin')
ON DUPLICATE KEY UPDATE report_type=VALUES(report_type);

INSERT INTO `generated_reports` (`report_name`, `report_type`, `period_start`, `period_end`, `file_path`, `generated_by`, `download_count`) VALUES
('Attendance Report - November 2024', 'attendance', '2024-11-01', '2024-11-30', 'reports/attendance_nov2024.pdf', 'Admin', 3),
('Academic Performance - Term 3', 'academic', '2024-09-01', '2024-12-07', 'reports/academic_term3_2024.pdf', 'Admin', 5),
('Financial Report - Q4 2024', 'financial', '2024-10-01', '2024-12-31', 'reports/financial_q4_2024.xlsx', 'Admin', 2),
('Student Results - First Term Exam', 'academic', '2024-11-15', '2024-11-30', 'reports/results_term1_exam.pdf', 'Admin', 8)
ON DUPLICATE KEY UPDATE report_name=VALUES(report_name);

-- ============================================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================================

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- SUMMARY REPORT
-- ============================================================================

SELECT 'Dummy data insertion completed successfully!' AS Status;

SELECT 
    'Users' AS TableName, 
    COUNT(*) AS TotalRecords 
FROM users
UNION ALL
SELECT 'Students', COUNT(*) FROM students
UNION ALL
SELECT 'Teachers', COUNT(*) FROM teachers
UNION ALL
SELECT 'Attendance', COUNT(*) FROM attendance
UNION ALL
SELECT 'Results', COUNT(*) FROM results
UNION ALL
SELECT 'Assignments', COUNT(*) FROM assignments
UNION ALL
SELECT 'Payments', COUNT(*) FROM payments
UNION ALL
SELECT 'Events', COUNT(*) FROM events
UNION ALL
SELECT 'Notices', COUNT(*) FROM notices
UNION ALL
SELECT 'Library Books', COUNT(*) FROM library_books
UNION ALL
SELECT 'Inventory', COUNT(*) FROM inventory;
