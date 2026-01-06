-- Insert dummy test marks for JSS 1 students
-- Student: kira kira (ID: 304), Class: JSS 1 (ID: 10), Session: 2, Term: 5 (Second Term)
-- Student: sadiq bello (ID: 307)

-- Delete existing results for these students first (if any)
DELETE FROM student_results WHERE student_id IN (304, 307) AND class_id = 10 AND session_id = 2 AND term_id = 5;

-- Student 304 (kira kira) - Good student (Grades A-C)
INSERT INTO student_results (student_id, class_id, subject_id, session_id, term_id, ca_score, exam_score, grade, remark) VALUES
(304, 10, 1, 2, 5, 28, 65, 'A', 'Excellent'),      -- English Language: 93
(304, 10, 2, 2, 5, 25, 60, 'B', 'Very Good'),      -- Mathematics: 85
(304, 10, 7, 2, 5, 22, 58, 'B', 'Very Good'),      -- History: 80
(304, 10, 8, 2, 5, 20, 55, 'B', 'Very Good'),      -- Christian RS: 75
(304, 10, 9, 2, 5, 27, 63, 'A', 'Excellent'),      -- Islamic RS: 90
(304, 10, 10, 2, 5, 18, 50, 'C', 'Good'),          -- Social Studies: 68
(304, 10, 11, 2, 5, 26, 62, 'A', 'Excellent'),     -- Computer Science: 88
(304, 10, 12, 2, 5, 24, 58, 'B', 'Very Good'),     -- Business Studies: 82
(304, 10, 13, 2, 5, 23, 60, 'A', 'Excellent'),     -- Home Economics: 83
(304, 10, 14, 2, 5, 28, 67, 'A', 'Excellent'),     -- Basic Science: 95
(304, 10, 15, 2, 5, 25, 59, 'B', 'Very Good'),     -- Basic Technology: 84
(304, 10, 16, 2, 5, 27, 64, 'A', 'Excellent'),     -- Physical Education: 91
(304, 10, 17, 2, 5, 26, 61, 'A', 'Excellent'),     -- Creative Arts: 87
(304, 10, 18, 2, 5, 24, 56, 'B', 'Very Good'),     -- Agricultural Science: 80
(304, 10, 19, 2, 5, 22, 53, 'B', 'Very Good'),     -- Civic Education: 75
(304, 10, 20, 2, 5, 29, 68, 'A', 'Excellent');     -- French: 97

-- Student 307 (sadiq bello) - Average student (Grades C-E)
INSERT INTO student_results (student_id, class_id, subject_id, session_id, term_id, ca_score, exam_score, grade, remark) VALUES
(307, 10, 1, 2, 5, 18, 45, 'C', 'Good'),           -- English Language: 63
(307, 10, 2, 2, 5, 15, 40, 'D', 'Fair'),           -- Mathematics: 55
(307, 10, 7, 2, 5, 16, 42, 'D', 'Fair'),           -- History: 58
(307, 10, 8, 2, 5, 14, 38, 'E', 'Pass'),           -- Christian RS: 52
(307, 10, 9, 2, 5, 20, 48, 'C', 'Good'),           -- Islamic RS: 68
(307, 10, 10, 2, 5, 12, 35, 'E', 'Pass'),          -- Social Studies: 47
(307, 10, 11, 2, 5, 19, 50, 'C', 'Good'),          -- Computer Science: 69
(307, 10, 12, 2, 5, 17, 44, 'C', 'Good'),          -- Business Studies: 61
(307, 10, 13, 2, 5, 15, 41, 'D', 'Fair'),          -- Home Economics: 56
(307, 10, 14, 2, 5, 21, 52, 'C', 'Good'),          -- Basic Science: 73
(307, 10, 15, 2, 5, 16, 43, 'D', 'Fair'),          -- Basic Technology: 59
(307, 10, 16, 2, 5, 22, 53, 'B', 'Very Good'),     -- Physical Education: 75
(307, 10, 17, 2, 5, 18, 46, 'C', 'Good'),          -- Creative Arts: 64
(307, 10, 18, 2, 5, 14, 39, 'E', 'Pass'),          -- Agricultural Science: 53
(307, 10, 19, 2, 5, 13, 36, 'E', 'Pass'),          -- Civic Education: 49
(307, 10, 20, 2, 5, 19, 51, 'B', 'Very Good');     -- French: 70
