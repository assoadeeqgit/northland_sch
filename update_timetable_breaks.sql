-- ============================================
-- NORTHLAND SCHOOLS KANO - TIMETABLE UPDATE
-- ============================================
-- This script updates break times and fills free periods
-- Break Times:
--   - Nursery (Classes 1-4): 9:30 AM - 10:00 AM (30 min)
--   - Primary/Secondary (Classes 5-15): 10:30 AM - 11:00 AM (30 min)
-- ============================================

-- First, let's add break time entries to the subjects table if not exists
INSERT INTO `subjects` (`subject_code`, `subject_name`, `description`, `category`, `is_active`) 
VALUES 
('BREAK-NUR', 'Break Time', 'Nursery break time', 'Extra-curricular', 1),
('BREAK-PS', 'Break Time', 'Primary/Secondary break time', 'Extra-curricular', 1)
ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name);

-- Get the subject IDs for breaks (we'll use these in the timetable)
SET @break_nursery_id = (SELECT id FROM subjects WHERE subject_code = 'BREAK-NUR');
SET @break_primary_secondary_id = (SELECT id FROM subjects WHERE subject_code = 'BREAK-PS');

-- ============================================
-- STEP 1: ADD BREAK TIMES FOR NURSERY CLASSES
-- ============================================
-- Nursery classes: Garden (1), Pre-Nursery (2), Nursery 1 (3), Nursery 2 (4)
-- Break time: 9:30 AM - 10:00 AM (30 minutes)

-- Delete any existing break entries to avoid duplicates
DELETE FROM timetable WHERE subject_id IN (@break_nursery_id, @break_primary_secondary_id);

-- Add Nursery break times for all weekdays
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Garden (Class 1)
(1, @break_nursery_id, 1, 'Monday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(1, @break_nursery_id, 1, 'Tuesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(1, @break_nursery_id, 1, 'Wednesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(1, @break_nursery_id, 1, 'Thursday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(1, @break_nursery_id, 1, 'Friday', '09:30:00', '10:00:00', 'Playground', 1, 3),

-- Pre-Nursery (Class 2)
(2, @break_nursery_id, 1, 'Monday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(2, @break_nursery_id, 1, 'Tuesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(2, @break_nursery_id, 1, 'Wednesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(2, @break_nursery_id, 1, 'Thursday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(2, @break_nursery_id, 1, 'Friday', '09:30:00', '10:00:00', 'Playground', 1, 3),

-- Nursery 1 (Class 3)
(3, @break_nursery_id, 1, 'Monday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(3, @break_nursery_id, 1, 'Tuesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(3, @break_nursery_id, 1, 'Wednesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(3, @break_nursery_id, 1, 'Thursday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(3, @break_nursery_id, 1, 'Friday', '09:30:00', '10:00:00', 'Playground', 1, 3),

-- Nursery 2 (Class 4)
(4, @break_nursery_id, 1, 'Monday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(4, @break_nursery_id, 1, 'Tuesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(4, @break_nursery_id, 1, 'Wednesday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(4, @break_nursery_id, 1, 'Thursday', '09:30:00', '10:00:00', 'Playground', 1, 3),
(4, @break_nursery_id, 1, 'Friday', '09:30:00', '10:00:00', 'Playground', 1, 3);

-- ============================================
-- STEP 2: ADD BREAK TIMES FOR PRIMARY/SECONDARY CLASSES
-- ============================================
-- Primary classes: P1 (5), P2 (6), P3 (7), P4 (8), P5 (9)
-- Secondary classes: JSS1 (10), JSS2 (11), JSS3 (12), SS1 (13), SS2 (14), SS3 (15)
-- Break time: 10:30 AM - 11:00 AM (30 minutes)

INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Primary 1 (Class 5)
(5, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(5, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(5, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(5, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(5, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- Primary 2 (Class 6)
(6, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(6, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(6, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(6, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(6, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- Primary 3 (Class 7)
(7, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(7, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(7, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(7, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(7, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- Primary 4 (Class 8)
(8, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(8, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(8, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(8, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(8, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- Primary 5 (Class 9)
(9, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(9, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(9, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(9, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(9, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- JSS 1 (Class 10)
(10, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(10, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(10, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(10, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(10, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- JSS 2 (Class 11)
(11, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(11, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(11, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(11, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(11, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- JSS 3 (Class 12)
(12, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(12, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(12, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(12, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(12, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- SS 1 (Class 13)
(13, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(13, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(13, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(13, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(13, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- SS 2 (Class 14)
(14, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(14, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(14, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(14, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(14, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3),

-- SS 3 (Class 15)
(15, @break_primary_secondary_id, 1, 'Monday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(15, @break_primary_secondary_id, 1, 'Tuesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(15, @break_primary_secondary_id, 1, 'Wednesday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(15, @break_primary_secondary_id, 1, 'Thursday', '10:30:00', '11:00:00', 'Playground', 1, 3),
(15, @break_primary_secondary_id, 1, 'Friday', '10:30:00', '11:00:00', 'Playground', 1, 3);

-- ============================================
-- STEP 3: FILL FREE PERIODS WITH COURSES
-- ============================================
-- Based on analysis of existing timetable, filling gaps with appropriate subjects

-- Primary 1 (Class 5) - Fill missing periods
-- Currently has: Mon(8-8:45, 8:45-9:30, 9:30-10:15), Tue(8-8:45, 8:45-9:30), Wed(8-8:45, 9:30-10:15), Thu(8-8:45), Fri(8-8:45)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Fill Wednesday 8:45-9:30 with Mathematics
(5, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
-- Fill Tuesday 10:00-10:45 with English
(5, 1, 1, 'Tuesday', '10:00:00', '10:30:00', 'P1-A', 1, 3),
-- Fill Thursday 8:45-9:30 with Mathematics
(5, 2, 2, 'Thursday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
-- Fill Thursday 10:00-10:30 with Music
(5, 11, 1, 'Thursday', '10:00:00', '10:30:00', 'Hall', 1, 3),
-- Fill Friday 8:45-9:30 with Mathematics
(5, 2, 2, 'Friday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
-- Fill Friday 10:00-10:30 with English
(5, 1, 1, 'Friday', '10:00:00', '10:30:00', 'P1-A', 1, 3);

-- JSS 1 (Class 10) - Fill missing periods
-- Currently has: Mon(8-8:45, 8:45-9:30, 9:30-10:15), Tue(8-8:45, 8:45-9:30), Wed(9:30-10:15), Thu(8:45-9:30), Fri(8-8:45)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Fill Wednesday 8:00-8:45 with English
(10, 1, 1, 'Wednesday', '08:00:00', '08:45:00', 'JSS1-Hall', 1, 3),
-- Fill Wednesday 8:45-9:30 with Mathematics
(10, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'JSS1-Hall', 1, 3),
-- Fill Thursday 8:00-8:45 with Biology
(10, 3, 2, 'Thursday', '08:00:00', '08:45:00', 'Lab A', 1, 3),
-- Fill Thursday 10:00-10:30 with Mathematics
(10, 2, 2, 'Thursday', '10:00:00', '10:30:00', 'JSS1-Hall', 1, 3),
-- Fill Friday 8:45-9:30 with English
(10, 1, 1, 'Friday', '08:45:00', '09:30:00', 'JSS1-Hall', 1, 3),
-- Fill Friday 10:00-10:30 with Biology
(10, 3, 2, 'Friday', '10:00:00', '10:30:00', 'Lab A', 1, 3);

-- SS 1 (Class 13) - Fill missing periods
-- Currently has: Mon(8-8:45, 8:45-9:30, 11-11:45), Tue(8:45-9:30, 11-11:45), Wed(8-8:45, 8:45-9:30), Thu(9:30-10:15), Fri(11-11:45)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Fill Monday 9:30-10:30 (before break at 10:30) with Chemistry
(13, 4, 2, 'Monday', '09:30:00', '10:30:00', 'Lab B', 1, 3),
-- Fill Tuesday 8:00-8:45 with Physics
(13, 5, 2, 'Tuesday', '08:00:00', '08:45:00', 'SS1-Sci', 1, 3),
-- Fill Tuesday 9:30-10:30 with English
(13, 1, 1, 'Tuesday', '09:30:00', '10:30:00', 'SS1-Sci', 1, 3),
-- Fill Wednesday 9:30-10:30 with Biology
(13, 3, 2, 'Wednesday', '09:30:00', '10:30:00', 'Lab A', 1, 3),
-- Fill Thursday 8:00-8:45 with Chemistry
(13, 4, 2, 'Thursday', '08:00:00', '08:45:00', 'Lab B', 1, 3),
-- Fill Thursday 8:45-9:30 with English
(13, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'SS1-Sci', 1, 3),
-- Fill Friday 8:00-8:45 with Mathematics
(13, 2, 2, 'Friday', '08:00:00', '08:45:00', 'SS1-Sci', 1, 3),
-- Fill Friday 8:45-9:30 with Physics
(13, 5, 2, 'Friday', '08:45:00', '09:30:00', 'SS1-Sci', 1, 3),
-- Fill Friday 10:00-10:30 with Chemistry
(13, 4, 2, 'Friday', '10:00:00', '10:30:00', 'Lab B', 1, 3);

-- JSS 2 (Class 11) - Fill all periods (currently only has one entry for Biology on Monday)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(11, 1, 1, 'Monday', '08:00:00', '08:45:00', 'JSS2-Hall', 1, 3),
(11, 2, 2, 'Monday', '08:45:00', '09:30:00', 'JSS2-Hall', 1, 3),
(11, 9, 1, 'Monday', '09:30:00', '10:30:00', 'JSS2-Hall', 1, 3),
-- Tuesday
(11, 2, 2, 'Tuesday', '08:00:00', '08:45:00', 'JSS2-Hall', 1, 3),
(11, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'JSS2-Hall', 1, 3),
(11, 3, 1, 'Tuesday', '09:30:00', '10:30:00', 'Lab A', 1, 3),
-- Wednesday
(11, 1, 1, 'Wednesday', '08:00:00', '08:45:00', 'JSS2-Hall', 1, 3),
(11, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'JSS2-Hall', 1, 3),
(11, 9, 1, 'Wednesday', '09:30:00', '10:30:00', 'JSS2-Hall', 1, 3),
-- Thursday
(11, 3, 1, 'Thursday', '08:00:00', '08:45:00', 'Lab A', 1, 3),
(11, 2, 2, 'Thursday', '08:45:00', '09:30:00', 'JSS2-Hall', 1, 3),
(11, 1, 1, 'Thursday', '09:30:00', '10:30:00', 'JSS2-Hall', 1, 3),
-- Friday
(11, 1, 1, 'Friday', '08:00:00', '08:45:00', 'JSS2-Hall', 1, 3),
(11, 2, 2, 'Friday', '08:45:00', '09:30:00', 'JSS2-Hall', 1, 3),
(11, 3, 1, 'Friday', '09:30:00', '10:30:00', 'Lab A', 1, 3);

-- Fill schedules for classes without any timetable entries
-- Primary 3 (Class 7)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(7, 1, 1, 'Monday', '08:00:00', '08:45:00', 'P3-A', 1, 3),
(7, 2, 2, 'Monday', '08:45:00', '09:30:00', 'P3-A', 1, 3),
(7, 11, 1, 'Monday', '09:30:00', '10:30:00', 'Hall', 1, 3),
-- Tuesday
(7, 2, 2, 'Tuesday', '08:00:00', '08:45:00', 'P3-A', 1, 3),
(7, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'P3-A', 1, 3),
(7, 2, 2, 'Tuesday', '09:30:00', '10:30:00', 'P3-A', 1, 3),
-- Wednesday
(7, 1, 1, 'Wednesday', '08:00:00', '08:45:00', 'P3-A', 1, 3),
(7, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'P3-A', 1, 3),
(7, 1, 1, 'Wednesday', '09:30:00', '10:30:00', 'P3-A', 1, 3),
-- Thursday
(7, 2, 2, 'Thursday', '08:00:00', '08:45:00', 'P3-A', 1, 3),
(7, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'P3-A', 1, 3),
(7, 11, 1, 'Thursday', '09:30:00', '10:30:00', 'Hall', 1, 3),
-- Friday
(7, 1, 1, 'Friday', '08:00:00', '08:45:00', 'P3-A', 1, 3),
(7, 2, 2, 'Friday', '08:45:00', '09:30:00', 'P3-A', 1, 3),
(7, 1, 1, 'Friday', '09:30:00', '10:30:00', 'P3-A', 1, 3);

-- Primary 4 (Class 8) - Already has one entry, fill the rest
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(8, 1, 1, 'Monday', '08:00:00', '08:45:00', 'P4-A', 1, 3),
(8, 2, 2, 'Monday', '08:45:00', '09:30:00', 'P4-A', 1, 3),
(8, 1, 1, 'Monday', '09:30:00', '10:30:00', 'P4-A', 1, 3),
-- Tuesday
(8, 2, 2, 'Tuesday', '08:00:00', '08:45:00', 'P4-A', 1, 3),
(8, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'P4-A', 1, 3),
(8, 2, 2, 'Tuesday', '09:30:00', '10:30:00', 'P4-A', 1, 3),
-- Wednesday
(8, 1, 1, 'Wednesday', '08:00:00', '08:45:00', 'P4-A', 1, 3),
(8, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'P4-A', 1, 3),
(8, 1, 1, 'Wednesday', '09:30:00', '10:30:00', 'P4-A', 1, 3),
-- Thursday
(8, 2, 2, 'Thursday', '08:00:00', '08:45:00', 'P4-A', 1, 3),
(8, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'P4-A', 1, 3),
(8, 2, 2, 'Thursday', '09:30:00', '10:30:00', 'P4-A', 1, 3),
-- Friday
(8, 1, 1, 'Friday', '08:00:00', '08:45:00', 'P4-A', 1, 3),
(8, 2, 2, 'Friday', '08:45:00', '09:30:00', 'P4-A', 1, 3),
(8, 1, 1, 'Friday', '09:30:00', '10:30:00', 'P4-A', 1, 3);

-- Primary 5 (Class 9)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(9, 1, 1, 'Monday', '08:00:00', '08:45:00', 'P5-A', 1, 3),
(9, 2, 2, 'Monday', '08:45:00', '09:30:00', 'P5-A', 1, 3),
(9, 1, 1, 'Monday', '09:30:00', '10:30:00', 'P5-A', 1, 3),
-- Tuesday
(9, 2, 2, 'Tuesday', '08:00:00', '08:45:00', 'P5-A', 1, 3),
(9, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'P5-A', 1, 3),
(9, 2, 2, 'Tuesday', '09:30:00', '10:30:00', 'P5-A', 1, 3),
-- Wednesday
(9, 1, 1, 'Wednesday', '08:00:00', '08:45:00', 'P5-A', 1, 3),
(9, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'P5-A', 1, 3),
(9, 1, 1, 'Wednesday', '09:30:00', '10:30:00', 'P5-A', 1, 3),
-- Thursday
(9, 2, 2, 'Thursday', '08:00:00', '08:45:00', 'P5-A', 1, 3),
(9, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'P5-A', 1, 3),
(9, 2, 2, 'Thursday', '09:30:00', '10:30:00', 'P5-A', 1, 3),
-- Friday
(9, 1, 1, 'Friday', '08:00:00', '08:45:00', 'P5-A', 1, 3),
(9, 2, 2, 'Friday', '08:45:00', '09:30:00', 'P5-A', 1, 3),
(9, 1, 1, 'Friday', '09:30:00', '10:30:00', 'P5-A', 1, 3);

-- JSS 3 (Class 12) - Fill missing periods
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Tuesday
(12, 1, 1, 'Tuesday', '08:00:00', '08:45:00', 'JSS3-Hall', 1, 3),
(12, 2, 2, 'Tuesday', '08:45:00', '09:30:00', 'JSS3-Hall', 1, 3),
(12, 9, 1, 'Tuesday', '09:30:00', '10:30:00', 'JSS3-Hall', 1, 3),
-- Wednesday
(12, 2, 2, 'Wednesday', '08:00:00', '08:45:00', 'JSS3-Hall', 1, 3),
(12, 1, 1, 'Wednesday', '08:45:00', '09:30:00', 'JSS3-Hall', 1, 3),
(12, 9, 1, 'Wednesday', '09:30:00', '10:30:00', 'JSS3-Hall', 1, 3),
-- Thursday
(12, 9, 1, 'Thursday', '08:00:00', '08:45:00', 'JSS3-Hall', 1, 3),
(12, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'JSS3-Hall', 1, 3),
(12, 2, 2, 'Thursday', '09:30:00', '10:30:00', 'JSS3-Hall', 1, 3),
-- Friday
(12, 1, 1, 'Friday', '08:00:00', '08:45:00', 'JSS3-Hall', 1, 3),
(12, 2, 2, 'Friday', '08:45:00', '09:30:00', 'JSS3-Hall', 1, 3),
(12, 9, 1, 'Friday', '09:30:00', '10:30:00', 'JSS3-Hall', 1, 3);

-- SS 2 (Class 14)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(14, 5, 2, 'Monday', '08:00:00', '08:45:00', 'SS2-Sci', 1, 3),
(14, 2, 2, 'Monday', '08:45:00', '09:30:00', 'SS2-Sci', 1, 3),
(14, 4, 2, 'Monday', '09:30:00', '10:30:00', 'Lab B', 1, 3),
-- Tuesday
(14, 1, 1, 'Tuesday', '08:00:00', '08:45:00', 'SS2-Sci', 1, 3),
(14, 3, 2, 'Tuesday', '08:45:00', '09:30:00', 'Lab A', 1, 3),
(14, 5, 2, 'Tuesday', '09:30:00', '10:30:00', 'SS2-Sci', 1, 3),
-- Wednesday
(14, 5, 2, 'Wednesday', '08:00:00', '08:45:00', 'SS2-Sci', 1, 3),
(14, 1, 1, 'Wednesday', '08:45:00', '09:30:00', 'SS2-Sci', 1, 3),
(14, 2, 2, 'Wednesday', '09:30:00', '10:30:00', 'SS2-Sci', 1, 3),
-- Thursday
(14, 4, 2, 'Thursday', '08:00:00', '08:45:00', 'Lab B', 1, 3),
(14, 2, 2, 'Thursday', '08:45:00', '09:30:00', 'SS2-Sci', 1, 3),
(14, 1, 1, 'Thursday', '09:30:00', '10:30:00', 'SS2-Sci', 1, 3),
-- Friday
(14, 3, 2, 'Friday', '08:00:00', '08:45:00', 'Lab A', 1, 3),
(14, 5, 2, 'Friday', '08:45:00', '09:30:00', 'SS2-Sci', 1, 3),
(14, 2, 2, 'Friday', '09:30:00', '10:30:00', 'SS2-Sci', 1, 3);

-- SS 3 (Class 15)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(15, 5, 2, 'Monday', '08:00:00', '08:45:00', 'SS3-Sci', 1, 3),
(15, 2, 2, 'Monday', '08:45:00', '09:30:00', 'SS3-Sci', 1, 3),
(15, 4, 2, 'Monday', '09:30:00', '10:30:00', 'Lab B', 1, 3),
-- Tuesday
(15, 1, 1, 'Tuesday', '08:00:00', '08:45:00', 'SS3-Sci', 1, 3),
(15, 3, 2, 'Tuesday', '08:45:00', '09:30:00', 'Lab A', 1, 3),
(15, 5, 2, 'Tuesday', '09:30:00', '10:30:00', 'SS3-Sci', 1, 3),
-- Wednesday
(15, 5, 2, 'Wednesday', '08:00:00', '08:45:00', 'SS3-Sci', 1, 3),
(15, 1, 1, 'Wednesday', '08:45:00', '09:30:00', 'SS3-Sci', 1, 3),
(15, 2, 2, 'Wednesday', '09:30:00', '10:30:00', 'SS3-Sci', 1, 3),
-- Thursday
(15, 4, 2, 'Thursday', '08:00:00', '08:45:00', 'Lab B', 1, 3),
(15, 2, 2, 'Thursday', '08:45:00', '09:30:00', 'SS3-Sci', 1, 3),
(15, 1, 1, 'Thursday', '09:30:00', '10:30:00', 'SS3-Sci', 1, 3),
-- Friday
(15, 3, 2, 'Friday', '08:00:00', '08:45:00', 'Lab A', 1, 3),
(15, 5, 2, 'Friday', '08:45:00', '09:30:00', 'SS3-Sci', 1, 3),
(15, 2, 2, 'Friday', '09:30:00', '10:30:00', 'SS3-Sci', 1, 3);

-- Nursery classes (1-4) - Fill with basic nursery subjects
-- Garden (Class 1)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(1, 11, 1, 'Monday', '08:00:00', '08:45:00', 'Garden-A', 1, 3),
(1, 11, 1, 'Monday', '08:45:00', '09:30:00', 'Garden-A', 1, 3),
-- Tuesday
(1, 11, 1, 'Tuesday', '08:00:00', '08:45:00', 'Garden-A', 1, 3),
(1, 11, 1, 'Tuesday', '08:45:00', '09:30:00', 'Garden-A', 1, 3),
-- Wednesday
(1, 11, 1, 'Wednesday', '08:00:00', '08:45:00', 'Garden-A', 1, 3),
(1, 11, 1, 'Wednesday', '08:45:00', '09:30:00', 'Garden-A', 1, 3),
-- Thursday
(1, 11, 1, 'Thursday', '08:00:00', '08:45:00', 'Garden-A', 1, 3),
(1, 11, 1, 'Thursday', '08:45:00', '09:30:00', 'Garden-A', 1, 3),
-- Friday
(1, 11, 1, 'Friday', '08:00:00', '08:45:00', 'Garden-A', 1, 3),
(1, 11, 1, 'Friday', '08:45:00', '09:30:00', 'Garden-A', 1, 3);

-- Pre-Nursery (Class 2) - Already has one entry, fill the rest
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(2, 11, 1, 'Monday', '08:00:00', '08:45:00', 'PreNur-A', 1, 3),
(2, 11, 1, 'Monday', '08:45:00', '09:30:00', 'PreNur-A', 1, 3),
-- Tuesday
(2, 11, 1, 'Tuesday', '08:00:00', '08:45:00', 'PreNur-A', 1, 3),
(2, 11, 1, 'Tuesday', '08:45:00', '09:30:00', 'PreNur-A', 1, 3),
-- Wednesday
(2, 11, 1, 'Wednesday', '08:00:00', '08:45:00', 'PreNur-A', 1, 3),
(2, 11, 1, 'Wednesday', '08:45:00', '09:30:00', 'PreNur-A', 1, 3),
-- Thursday
(2, 11, 1, 'Thursday', '08:00:00', '08:45:00', 'PreNur-A', 1, 3),
(2, 11, 1, 'Thursday', '08:45:00', '09:30:00', 'PreNur-A', 1, 3),
-- Friday
(2, 11, 1, 'Friday', '08:00:00', '08:45:00', 'PreNur-A', 1, 3),
(2, 11, 1, 'Friday', '08:45:00', '09:30:00', 'PreNur-A', 1, 3);

-- Nursery 1 (Class 3)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(3, 11, 1, 'Monday', '08:00:00', '08:45:00', 'Nur1-A', 1, 3),
(3, 11, 1, 'Monday', '08:45:00', '09:30:00', 'Nur1-A', 1, 3),
-- Tuesday
(3, 11, 1, 'Tuesday', '08:00:00', '08:45:00', 'Nur1-A', 1, 3),
(3, 11, 1, 'Tuesday', '08:45:00', '09:30:00', 'Nur1-A', 1, 3),
-- Wednesday
(3, 11, 1, 'Wednesday', '08:00:00', '08:45:00', 'Nur1-A', 1, 3),
(3, 11, 1, 'Wednesday', '08:45:00', '09:30:00', 'Nur1-A', 1, 3),
-- Thursday
(3, 11, 1, 'Thursday', '08:00:00', '08:45:00', 'Nur1-A', 1, 3),
(3, 11, 1, 'Thursday', '08:45:00', '09:30:00', 'Nur1-A', 1, 3),
-- Friday
(3, 11, 1, 'Friday', '08:00:00', '08:45:00', 'Nur1-A', 1, 3),
(3, 11, 1, 'Friday', '08:45:00', '09:30:00', 'Nur1-A', 1, 3);

-- Nursery 2 (Class 4) - Already has one entry, fill the rest
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(4, 11, 1, 'Monday', '08:00:00', '08:45:00', 'Nur2-A', 1, 3),
(4, 11, 1, 'Monday', '08:45:00', '09:30:00', 'Nur2-A', 1, 3),
-- Tuesday
(4, 11, 1, 'Tuesday', '08:00:00', '08:45:00', 'Nur2-A', 1, 3),
(4, 11, 1, 'Tuesday', '08:45:00', '09:30:00', 'Nur2-A', 1, 3),
-- Wednesday
(4, 11, 1, 'Wednesday', '08:00:00', '08:45:00', 'Nur2-A', 1, 3),
(4, 11, 1, 'Wednesday', '08:45:00', '09:30:00', 'Nur2-A', 1, 3),
-- Thursday
(4, 11, 1, 'Thursday', '08:00:00', '08:45:00', 'Nur2-A', 1, 3),
(4, 11, 1, 'Thursday', '08:45:00', '09:30:00', 'Nur2-A', 1, 3),
-- Friday
(4, 11, 1, 'Friday', '08:00:00', '08:45:00', 'Nur2-A', 1, 3),
(4, 11, 1, 'Friday', '08:45:00', '09:30:00', 'Nur2-A', 1, 3);

-- Primary 2 (Class 6) - Already has one entry, fill the rest
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(6, 1, 1, 'Monday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Monday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 11, 1, 'Monday', '09:30:00', '10:30:00', 'Hall', 1, 3),
-- Tuesday
(6, 2, 2, 'Tuesday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 2, 2, 'Tuesday', '09:30:00', '10:30:00', 'P2-A', 1, 3),
-- Thursday
(6, 2, 2, 'Thursday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 11, 1, 'Thursday', '09:30:00', '10:30:00', 'Hall', 1, 3),
-- Friday
(6, 1, 1, 'Friday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Friday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 1, 1, 'Friday', '09:30:00', '10:30:00', 'P2-A', 1, 3);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Run these queries to verify the updates

-- Check all break times
SELECT 
    c.class_name,
    t.day_of_week,
    t.start_time,
    t.end_time,
    s.subject_name
FROM timetable t
JOIN classes c ON t.class_id = c.id
JOIN subjects s ON t.subject_id = s.id
WHERE s.subject_code IN ('BREAK-NUR', 'BREAK-PS')
ORDER BY c.id, t.day_of_week, t.start_time;

-- Check timetable coverage for all classes
SELECT 
    c.class_name,
    t.day_of_week,
    COUNT(*) as period_count
FROM classes c
LEFT JOIN timetable t ON c.id = t.class_id
GROUP BY c.id, c.class_name, t.day_of_week
ORDER BY c.id, 
    FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday');

-- Check for time conflicts
SELECT 
    t1.class_id,
    c.class_name,
    t1.day_of_week,
    t1.start_time,
    t1.end_time,
    COUNT(*) as conflict_count
FROM timetable t1
JOIN classes c ON t1.class_id = c.id
GROUP BY t1.class_id, c.class_name, t1.day_of_week, t1.start_time, t1.end_time
HAVING COUNT(*) > 1;
