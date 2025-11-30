-- ============================================
-- FILL TIMETABLE TO CLOSING TIME (1:30 PM)
-- ============================================
-- Current state: Classes end at 10:00-11:00 AM
-- Target: Extend to 1:30 PM (13:30)
-- 
-- Schedule structure:
-- Nursery (Classes 1-4): 8:00 AM - 10:00 AM (currently complete)
-- Primary/Secondary (Classes 5-15): Need to add 11:00 AM - 1:30 PM
-- ============================================

-- Period structure after break (11:00 AM - 1:30 PM):
-- Period 4: 11:00 - 11:45 (45 min)
-- Period 5: 11:45 - 12:30 (45 min)
-- Period 6: 12:30 - 13:15 (45 min)
-- Closing: 13:15 - 13:30 (15 min - can be used for closing activities/dismissal)

-- ============================================
-- PRIMARY CLASSES (5-9)
-- ============================================

-- Primary 1 (Class 5)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(5, 1, 1, 'Monday', '08:00:00', '08:45:00', 'P1-A', 1, 3),
(5, 2, 2, 'Monday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
(5, 1, 1, 'Monday', '09:30:00', '10:30:00', 'P1-A', 1, 3),
(5, 2, 2, 'Monday', '11:00:00', '11:45:00', 'P1-A', 1, 3),
(5, 12, 1, 'Monday', '11:45:00', '12:30:00', 'Sports Field', 1, 3),
(5, 11, 1, 'Monday', '12:30:00', '13:15:00', 'Hall', 1, 3),

-- Tuesday  
(5, 2, 2, 'Tuesday', '08:00:00', '08:45:00', 'P1-A', 1, 3),
(5, 1, 1, 'Tuesday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
(5, 9, 1, 'Tuesday', '09:30:00', '10:30:00', 'P1-A', 1, 3),
(5, 1, 1, 'Tuesday', '11:00:00', '11:45:00', 'P1-A', 1, 3),
(5, 2, 2, 'Tuesday', '11:45:00', '12:30:00', 'P1-A', 1, 3),
(5, 10, 1, 'Tuesday', '12:30:00', '13:15:00', 'Art Room', 1, 3),

-- Wednesday
(5, 1, 1, 'Wednesday', '08:00:00', '08:45:00', 'P1-A', 1, 3),
(5, 2, 2, 'Wednesday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
(5, 12, 1, 'Wednesday', '09:30:00', '10:30:00', 'Sports Field', 1, 3),
(5, 2, 2, 'Wednesday', '11:00:00', '11:45:00', 'P1-A', 1, 3),
(5, 1, 1, 'Wednesday', '11:45:00', '12:30:00', 'P1-A', 1, 3),
(5, 9, 1, 'Wednesday', '12:30:00', '13:15:00', 'P1-A', 1, 3),

-- Thursday
(5, 2, 2, 'Thursday', '08:00:00', '08:45:00', 'P1-A', 1, 3),
(5, 1, 1, 'Thursday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
(5, 9, 1, 'Thursday', '09:30:00', '10:30:00', 'P1-A', 1, 3),
(5, 1, 1, 'Thursday', '11:00:00', '11:45:00', 'P1-A', 1, 3),
(5, 2, 2, 'Thursday', '11:45:00', '12:30:00', 'P1-A', 1, 3),
(5, 11, 1, 'Thursday', '12:30:00', '13:15:00', 'Hall', 1, 3),

-- Friday
(5, 1, 1, 'Friday', '08:00:00', '08:45:00', 'P1-A', 1, 3),
(5, 2, 2, 'Friday', '08:45:00', '09:30:00', 'P1-A', 1, 3),
(5, 12, 1, 'Friday', '09:30:00', '10:30:00', 'Sports Field', 1, 3),
(5, 9, 1, 'Friday', '11:00:00', '11:45:00', 'P1-A', 1, 3),
(5, 1, 1, 'Friday', '11:45:00', '12:30:00', 'P1-A', 1, 3),
(5, 2, 2, 'Friday', '12:30:00', '13:15:00', 'P1-A', 1, 3);

-- Primary 2 (Class 6)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(6, 2, 2, 'Monday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 1, 1, 'Monday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 9, 1, 'Monday', '09:30:00', '10:30:00', 'P2-A', 1, 3),
(6, 1, 1, 'Monday', '11:00:00', '11:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Monday', '11:45:00', '12:30:00', 'P2-A', 1, 3),
(6, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(6, 1, 1, 'Tuesday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Tuesday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 1, 1, 'Tuesday', '09:30:00', '10:30:00', 'P2-A', 1, 3),
(6, 2, 2, 'Tuesday', '11:00:00', '11:45:00', 'P2-A', 1, 3),
(6, 10, 1, 'Tuesday', '11:45:00', '12:30:00', 'Art Room', 1, 3),
(6, 11, 1, 'Tuesday', '12:30:00', '13:15:00', 'Hall', 1, 3),

-- Wednesday
(6, 2, 2, 'Wednesday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 1, 1, 'Wednesday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 12, 1, 'Wednesday', '09:30:00', '10:30:00', 'Sports Field', 1, 3),
(6, 1, 1, 'Wednesday', '11:00:00', '11:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Wednesday', '11:45:00', '12:30:00', 'P2-A', 1, 3),
(6, 9, 1, 'Wednesday', '12:30:00', '13:15:00', 'P2-A', 1, 3),

-- Thursday
(6, 1, 1, 'Thursday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Thursday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 9, 1, 'Thursday', '09:30:00', '10:30:00', 'P2-A', 1, 3),
(6, 2, 2, 'Thursday', '11:00:00', '11:45:00', 'P2-A', 1, 3),
(6, 1, 1, 'Thursday', '11:45:00', '12:30:00', 'P2-A', 1, 3),
(6, 12, 1, 'Thursday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Friday
(6, 2, 2, 'Friday', '08:00:00', '08:45:00', 'P2-A', 1, 3),
(6, 1, 1, 'Friday', '08:45:00', '09:30:00', 'P2-A', 1, 3),
(6, 10, 1, 'Friday', '09:30:00', '10:30:00', 'Art Room', 1, 3),
(6, 1, 1, 'Friday', '11:00:00', '11:45:00', 'P2-A', 1, 3),
(6, 2, 2, 'Friday', '11:45:00', '12:30:00', 'P2-A', 1, 3),  
(6, 11, 1, 'Friday', '12:30:00', '13:15:00', 'Hall', 1, 3);

-- Primary 3 (Class 7)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(7, 2, 2, 'Monday', '11:00:00', '11:45:00', 'P3-A', 1, 3),
(7, 9, 1, 'Monday', '11:45:00', '12:30:00', 'P3-A', 1, 3),
(7, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(7, 1, 1, 'Tuesday', '11:00:00', '11:45:00', 'P3-A', 1, 3),
(7, 10, 1, 'Tuesday', '11:45:00', '12:30:00', 'Art Room', 1, 3),
(7, 11, 1, 'Tuesday', '12:30:00', '13:15:00', 'Hall', 1, 3),

-- Wednesday
(7, 2, 2, 'Wednesday', '11:00:00', '11:45:00', 'P3-A', 1, 3),
(7, 9, 1, 'Wednesday', '11:45:00', '12:30:00', 'P3-A', 1, 3),
(7, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(7, 1, 1, 'Thursday', '11:00:00', '11:45:00', 'P3-A', 1, 3),
(7, 2, 2, 'Thursday', '11:45:00', '12:30:00', 'P3-A', 1, 3),
(7, 10, 1, 'Thursday', '12:30:00', '13:15:00', 'Art Room', 1, 3),

-- Friday
(7, 2, 2, 'Friday', '11:00:00', '11:45:00', 'P3-A', 1, 3),
(7, 1, 1, 'Friday', '11:45:00', '12:30:00', 'P3-A', 1, 3),
(7, 11, 1, 'Friday', '12:30:00', '13:15:00', 'Hall', 1, 3);

-- Primary 4 (Class 8)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(8, 2, 2, 'Monday', '11:00:00', '11:45:00', 'P4-A', 1, 3),
(8, 9, 1, 'Monday', '11:45:00', '12:30:00', 'P4-A', 1, 3),
(8, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(8, 1, 1, 'Tuesday', '11:00:00', '11:45:00', 'P4-A', 1, 3),
(8, 10, 1, 'Tuesday', '11:45:00', '12:30:00', 'Art Room', 1, 3),
(8, 11, 1, 'Tuesday', '12:30:00', '13:15:00', 'Hall', 1, 3),

-- Wednesday
(8, 2, 2, 'Wednesday', '11:00:00', '11:45:00', 'P4-A', 1, 3),
(8, 9, 1, 'Wednesday', '11:45:00', '12:30:00', 'P4-A', 1, 3),
(8, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(8, 1, 1, 'Thursday', '11:00:00', '11:45:00', 'P4-A', 1, 3),
(8, 2, 2, 'Thursday', '11:45:00', '12:30:00', 'P4-A', 1, 3),
(8, 10, 1, 'Thursday', '12:30:00', '13:15:00', 'Art Room', 1, 3),

-- Friday
(8, 2, 2, 'Friday', '11:00:00', '11:45:00', 'P4-A', 1, 3),
(8, 1, 1, 'Friday', '11:45:00', '12:30:00', 'P4-A', 1, 3),
(8, 11, 1, 'Friday', '12:30:00', '13:15:00', 'Hall', 1, 3);

-- Primary 5 (Class 9)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(9, 2, 2, 'Monday', '11:00:00', '11:45:00', 'P5-A', 1, 3),
(9, 9, 1, 'Monday', '11:45:00', '12:30:00', 'P5-A', 1, 3),
(9, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(9, 1, 1, 'Tuesday', '11:00:00', '11:45:00', 'P5-A', 1, 3),
(9, 10, 1, 'Tuesday', '11:45:00', '12:30:00', 'Art Room', 1, 3),
(9, 11, 1, 'Tuesday', '12:30:00', '13:15:00', 'Hall', 1, 3),

-- Wednesday
(9, 2, 2, 'Wednesday', '11:00:00', '11:45:00', 'P5-A', 1, 3),
(9, 9, 1, 'Wednesday', '11:45:00', '12:30:00', 'P5-A', 1, 3),
(9, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(9, 1, 1, 'Thursday', '11:00:00', '11:45:00', 'P5-A', 1, 3),
(9, 2, 2, 'Thursday', '11:45:00', '12:30:00', 'P5-A', 1, 3),
(9, 10, 1, 'Thursday', '12:30:00', '13:15:00', 'Art Room', 1, 3),

-- Friday
(9, 2, 2, 'Friday', '11:00:00', '11:45:00', 'P5-A', 1, 3),
(9, 1, 1, 'Friday', '11:45:00', '12:30:00', 'P5-A', 1, 3),
(9, 11, 1, 'Friday', '12:30:00', '13:15:00', 'Hall', 1, 3);

-- ============================================
-- SECONDARY CLASSES (10-15)
-- ============================================

-- JSS 1 (Class 10)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(10, 2, 2, 'Monday', '11:00:00', '11:45:00', 'JSS1-Hall', 1, 3),
(10, 1, 1, 'Monday', '11:45:00', '12:30:00', 'JSS1-Hall', 1, 3),
(10, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(10, 3, 2, 'Tuesday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(10, 2, 2, 'Tuesday', '11:45:00', '12:30:00', 'JSS1-Hall', 1, 3),
(10, 9, 1, 'Tuesday', '12:30:00', '13:15:00', 'JSS1-Hall', 1, 3),

-- Wednesday
(10, 1, 1, 'Wednesday', '11:00:00', '11:45:00', 'JSS1-Hall', 1, 3),
(10, 3, 2, 'Wednesday', '11:45:00', '12:30:00', 'Lab A', 1, 3),
(10, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(10, 1, 1, 'Thursday', '11:00:00', '11:45:00', 'JSS1-Hall', 1, 3),
(10, 2, 2, 'Thursday', '11:45:00', '12:30:00', 'JSS1-Hall', 1, 3),
(10, 9, 1, 'Thursday', '12:30:00', '13:15:00', 'JSS1-Hall', 1, 3),

-- Friday
(10, 3, 2, 'Friday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(10, 1, 1, 'Friday', '11:45:00', '12:30:00', 'JSS1-Hall', 1, 3),
(10, 2, 2, 'Friday', '12:30:00', '13:15:00', 'JSS1-Hall', 1, 3);

-- JSS 2 (Class 11)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(11, 1, 1, 'Monday', '11:00:00', '11:45:00', 'JSS2-Hall', 1, 3),
(11, 3, 2, 'Monday', '11:45:00', '12:30:00', 'Lab A', 1, 3),
(11, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(11, 2, 2, 'Tuesday', '11:00:00', '11:45:00', 'JSS2-Hall', 1, 3),
(11, 9, 1, 'Tuesday', '11:45:00', '12:30:00', 'JSS2-Hall', 1, 3),
(11, 1, 1, 'Tuesday', '12:30:00', '13:15:00', 'JSS2-Hall', 1, 3),

-- Wednesday
(11, 1, 1, 'Wednesday', '11:00:00', '11:45:00', 'JSS2-Hall', 1, 3),
(11, 2, 2, 'Wednesday', '11:45:00', '12:30:00', 'JSS2-Hall', 1, 3),
(11, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(11, 3, 2, 'Thursday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(11, 1, 1, 'Thursday', '11:45:00', '12:30:00', 'JSS2-Hall', 1, 3),
(11, 9, 1, 'Thursday', '12:30:00', '13:15:00', 'JSS2-Hall', 1, 3),

-- Friday
(11, 2, 2, 'Friday', '11:00:00', '11:45:00', 'JSS2-Hall', 1, 3),
(11, 1, 1, 'Friday', '11:45:00', '12:30:00', 'JSS2-Hall', 1, 3),
(11, 3, 2, 'Friday', '12:30:00', '13:15:00', 'Lab A', 1, 3);

-- JSS 3 (Class 12)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(12, 1, 1, 'Monday', '11:00:00', '11:45:00', 'JSS3-Hall', 1, 3),
(12, 3, 2, 'Monday', '11:45:00', '12:30:00', 'Lab A', 1, 3),
(12, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(12, 2, 2, 'Tuesday', '11:00:00', '11:45:00', 'JSS3-Hall', 1, 3),
(12, 1, 1, 'Tuesday', '11:45:00', '12:30:00', 'JSS3-Hall', 1, 3),
(12, 9, 1, 'Tuesday', '12:30:00', '13:15:00', 'JSS3-Hall', 1, 3),

-- Wednesday
(12, 1, 1, 'Wednesday', '11:00:00', '11:45:00', 'JSS3-Hall', 1, 3),
(12, 2, 2, 'Wednesday', '11:45:00', '12:30:00', 'JSS3-Hall', 1, 3),
(12, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(12, 3, 2, 'Thursday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(12, 1, 1, 'Thursday', '11:45:00', '12:30:00', 'JSS3-Hall', 1, 3),
(12, 2, 2, 'Thursday', '12:30:00', '13:15:00', 'JSS3-Hall', 1, 3),

-- Friday
(12, 9, 1, 'Friday', '11:00:00', '11:45:00', 'JSS3-Hall', 1, 3),
(12, 1, 1, 'Friday', '11:45:00', '12:30:00', 'JSS3-Hall', 1, 3),
(12, 2, 2, 'Friday', '12:30:00', '13:15:00', 'JSS3-Hall', 1, 3);

-- SS 1 (Class 13)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(13, 3, 2, 'Monday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(13, 1, 1, 'Monday', '11:45:00', '12:30:00', 'SS1-Sci', 1, 3),
(13, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(13, 4, 2, 'Tuesday', '11:00:00', '11:45:00', 'Lab B', 1, 3),
(13, 2, 2, 'Tuesday', '11:45:00', '12:30:00', 'SS1-Sci', 1, 3),
(13, 9, 1, 'Tuesday', '12:30:00', '13:15:00', 'SS1-Sci', 1, 3),

-- Wednesday
(13, 1, 1, 'Wednesday', '11:00:00', '11:45:00', 'SS1-Sci', 1, 3),
(13, 5, 2, 'Wednesday', '11:45:00', '12:30:00', 'SS1-Sci', 1, 3),
(13, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(13, 3, 2, 'Thursday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(13, 2, 2, 'Thursday', '11:45:00', '12:30:00', 'SS1-Sci', 1, 3),
(13, 4, 2, 'Thursday', '12:30:00', '13:15:00', 'Lab B', 1, 3),

-- Friday
(13, 5, 2, 'Friday', '11:00:00', '11:45:00', 'SS1-Sci', 1, 3),
(13, 1, 1, 'Friday', '11:45:00', '12:30:00', 'SS1-Sci', 1, 3),
(13, 9, 1, 'Friday', '12:30:00', '13:15:00', 'SS1-Sci', 1, 3);

-- SS 2 (Class 14)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(14, 1, 1, 'Monday', '11:00:00', '11:45:00', 'SS2-Sci', 1, 3),
(14, 3, 2, 'Monday', '11:45:00', '12:30:00', 'Lab A', 1, 3),
(14, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(14, 2, 2, 'Tuesday', '11:00:00', '11:45:00', 'SS2-Sci', 1, 3),
(14, 4, 2, 'Tuesday', '11:45:00', '12:30:00', 'Lab B', 1, 3),
(14, 9, 1, 'Tuesday', '12:30:00', '13:15:00', 'SS2-Sci', 1, 3),

-- Wednesday
(14, 3, 2, 'Wednesday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(14, 1, 1, 'Wednesday', '11:45:00', '12:30:00', 'SS2-Sci', 1, 3),
(14, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(14, 5, 2, 'Thursday', '11:00:00', '11:45:00', 'SS2-Sci', 1, 3),
(14, 1, 1, 'Thursday', '11:45:00', '12:30:00', 'SS2-Sci', 1, 3),
(14, 3, 2, 'Thursday', '12:30:00', '13:15:00', 'Lab A', 1, 3),

-- Friday
(14, 2, 2, 'Friday', '11:00:00', '11:45:00', 'SS2-Sci', 1, 3),
(14, 5, 2, 'Friday', '11:45:00', '12:30:00', 'SS2-Sci', 1, 3),
(14, 9, 1, 'Friday', '12:30:00', '13:15:00', 'SS2-Sci', 1, 3);

-- SS 3 (Class 15)
INSERT INTO `timetable` (`class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room`, `academic_session_id`, `term_id`)
VALUES
-- Monday
(15, 1, 1, 'Monday', '11:00:00', '11:45:00', 'SS3-Sci', 1, 3),
(15, 3, 2, 'Monday', '11:45:00', '12:30:00', 'Lab A', 1, 3),
(15, 12, 1, 'Monday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Tuesday
(15, 2, 2, 'Tuesday', '11:00:00', '11:45:00', 'SS3-Sci', 1, 3),
(15, 4, 2, 'Tuesday', '11:45:00', '12:30:00', 'Lab B', 1, 3),
(15, 9, 1, 'Tuesday', '12:30:00', '13:15:00', 'SS3-Sci', 1, 3),

-- Wednesday
(15, 3, 2, 'Wednesday', '11:00:00', '11:45:00', 'Lab A', 1, 3),
(15, 1, 1, 'Wednesday', '11:45:00', '12:30:00', 'SS3-Sci', 1, 3),
(15, 12, 1, 'Wednesday', '12:30:00', '13:15:00', 'Sports Field', 1, 3),

-- Thursday
(15, 5, 2, 'Thursday', '11:00:00', '11:45:00', 'SS3-Sci', 1, 3),
(15, 1, 1, 'Thursday', '11:45:00', '12:30:00', 'SS3-Sci', 1, 3),
(15, 3, 2, 'Thursday', '12:30:00', '13:15:00', 'Lab A', 1, 3),

-- Friday
(15, 2, 2, 'Friday', '11:00:00', '11:45:00', 'SS3-Sci', 1, 3),
(15, 5, 2, 'Friday', '11:45:00', '12:30:00', 'SS3-Sci', 1, 3),
(15, 9, 1, 'Friday', '12:30:00', '13:15:00', 'SS3-Sci', 1, 3);

-- ============================================
-- VERIFICATION
-- ============================================
SELECT 'Afternoon periods added successfully' AS status;

-- Check coverage for all classes
SELECT 
    c.class_name,
    MIN(t.start_time) as earliest_period,
    MAX(t.end_time) as latest_period,
    COUNT(DISTINCT CONCAT(t.day_of_week, '-', t.start_time)) as unique_periods
FROM classes c
LEFT JOIN timetable t ON c.id = t.class_id
GROUP BY c.id, c.class_name
ORDER BY c.id;
