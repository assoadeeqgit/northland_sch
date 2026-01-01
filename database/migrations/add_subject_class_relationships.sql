-- Migration: Add Subject-Class Relationships
-- This creates a many-to-many relationship between subjects and classes
-- Since many subjects are taught across multiple classes

-- Create subject_class_assignments table
CREATE TABLE IF NOT EXISTS subject_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subject_class (subject_id, class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- First, clear existing subject data and add subjects according to NORTHLAND SCHOOL structure
DELETE FROM subjects WHERE id > 0;

-- Reset auto increment
ALTER TABLE subjects AUTO_INCREMENT = 1;

-- PRE NURSERY SECTION SUBJECTS
INSERT INTO subjects (subject_code, subject_name, description, category, is_active) VALUES
('ENG-PN', 'English', 'English Language for Pre-Nursery', 'Early Childhood', 1),
('MAT-PN', 'Mathematics', 'Mathematics for Pre-Nursery', 'Early Childhood', 1),
('PHO-PN', 'Phonics', 'Phonics for Pre-Nursery', 'Early Childhood', 1),
('BSC-PN', 'Basic Science', 'Basic Science for Pre-Nursery', 'Early Childhood', 1),
('HH-PN', 'Health Habits', 'Health Habits for Pre-Nursery', 'Early Childhood', 1),
('SH-PN', 'Social Habits', 'Social Habits for Pre-Nursery', 'Early Childhood', 1),
('HW-PN', 'Handwriting', 'Handwriting for Pre-Nursery', 'Early Childhood', 1),
('RHY-PN', 'Rhymes', 'Rhymes for Pre-Nursery', 'Early Childhood', 1),
('COL-PN', 'Coloring', 'Coloring for Pre-Nursery', 'Early Childhood', 1);

-- NURSERY 1 & 2 SUBJECTS
INSERT INTO subjects (subject_code, subject_name, description, category, is_active) VALUES
('QR-N', 'Quantitative Reasoning', 'Quantitative Reasoning for Nursery', 'Early Childhood', 1),
('VR-N', 'Verbal Reasoning', 'Verbal Reasoning for Nursery', 'Early Childhood', 1),
('PLS-N', 'Practical Life Skills', 'Practical Life Skills for Nursery', 'Early Childhood', 1),
('ENG-N', 'English', 'English Language for Nursery', 'Early Childhood', 1),
('MAT-N', 'Mathematics', 'Mathematics for Nursery', 'Early Childhood', 1),
('PHO-N', 'Phonics', 'Phonics for Nursery', 'Early Childhood', 1),
('BSC-N', 'Basic Science', 'Basic Science for Nursery', 'Early Childhood', 1),
('HH-N', 'Health Habits', 'Health Habits for Nursery', 'Early Childhood', 1),
('SH-N', 'Social Habits', 'Social Habits for Nursery', 'Early Childhood', 1),
('HW-N', 'Handwriting', 'Handwriting for Nursery', 'Early Childhood', 1),
('RHY-N', 'Rhymes', 'Rhymes for Nursery', 'Early Childhood', 1);

-- PRIMARY SECTION SUBJECTS
INSERT INTO subjects (subject_code, subject_name, description, category, is_active) VALUES
('ENG-P', 'English', 'English Language for Primary', 'Core', 1),
('MAT-P', 'Mathematics', 'Mathematics for Primary', 'Core', 1),
('BSC-P', 'Basic Science', 'Basic Science for Primary', 'Core', 1),
('BTH-P', 'Basic Technology', 'Basic Technology for Primary', 'Core', 1),
('SS-P', 'Social Studies', 'Social Studies for Primary', 'Core', 1),
('CIV-P', 'Civic Education', 'Civic Education for Primary', 'Core', 1),
('HW-P', 'Handwriting', 'Handwriting for Primary', 'Core', 1),
('CCA-P', 'C.C.A', 'Cultural and Creative Arts for Primary', 'Core', 1),
('IRK-P', 'I.R.K', 'Islamic Religious Knowledge for Primary', 'Core', 1),
('COM-P', 'Computer', 'Computer Studies for Primary', 'Core', 1),
('PHE-P', 'PHE', 'Physical and Health Education for Primary', 'Core', 1),
('ARB-P', 'ARABIC', 'Arabic Language for Primary', 'Core', 1),
('QR-P', 'Quantitative Reasoning', 'Quantitative Reasoning for Primary', 'Core', 1),
('VR-P', 'Verbal Reasoning', 'Verbal Reasoning for Primary', 'Core', 1);

-- JUNIOR SECONDARY SECTION SUBJECTS
INSERT INTO subjects (subject_code, subject_name, description, category, is_active) VALUES
('ENG-JS', 'English', 'English Language for Junior Secondary', 'Core', 1),
('MAT-JS', 'Mathematics', 'Mathematics for Junior Secondary', 'Core', 1),
('BSC-JS', 'Basic Science', 'Basic Science for Junior Secondary', 'Core', 1),
('BTH-JS', 'Basic Technology', 'Basic Technology for Junior Secondary', 'Core', 1),
('CCA-JS', 'C.C.A', 'Cultural and Creative Arts for Junior Secondary', 'Core', 1),
('CIV-JS', 'Civic Education', 'Civic Education for Junior Secondary', 'Core', 1),
('BST-JS', 'Basic Science & Technology', 'Basic Science & Technology for Junior Secondary', 'Core', 1),
('NV-JS', 'National Values', 'National Values for Junior Secondary', 'Core', 1),
('SE-JS', 'Security Education', 'Security Education for Junior Secondary', 'Core', 1),
('PVS-JS', 'Pre-Vocational Studies', 'Pre-Vocational Studies for Junior Secondary', 'Vocational', 1),
('PHE-JS', 'P.H.E', 'Physical and Health Education for Junior Secondary', 'Core', 1),
('BUS-JS', 'Business Studies', 'Business Studies for Junior Secondary', 'Commercial', 1),
('HAU-JS', 'Hausa', 'Hausa Language for Junior Secondary', 'Core', 1),
('ARB-JS', 'Arabic', 'Arabic Language for Junior Secondary', 'Core', 1),
('HIS-JS', 'History', 'History for Junior Secondary', 'Core', 1);

-- SENIOR SECONDARY SECTION SUBJECTS (SCIENCE DEPARTMENT)
INSERT INTO subjects (subject_code, subject_name, description, category, is_active) VALUES
('ENG-SS', 'English', 'English Language for Senior Secondary', 'Core', 1),
('MAT-SS', 'Mathematics', 'Mathematics for Senior Secondary', 'Core', 1),
('CHM-SS', 'Chemistry', 'Chemistry for Senior Secondary', 'Science', 1),
('COM-SS', 'Computer', 'Computer Studies for Senior Secondary', 'Science', 1);

-- SENIOR SECONDARY SECTION SUBJECTS (ART WITH HISTORY)
INSERT INTO subjects (subject_code, subject_name, description, category, is_active) VALUES
('HIS-SS', 'History', 'History for Senior Secondary', 'Arts', 1),
('ECO-SS', 'Economics', 'Economics for Senior Secondary', 'Commercial', 1),
('LIT-SS', 'Eng. Literature', 'English Literature for Senior Secondary', 'Arts', 1),
('CIV-SS', 'Civic Education', 'Civic Education for Senior Secondary', 'Core', 1),
('IRK-SS', 'I.R.K', 'Islamic Religious Knowledge for Senior Secondary', 'Core', 1),
('AH-SS', 'Animal Husbandry', 'Animal Husbandry for Senior Secondary', 'Vocational', 1),
('CC-SS', 'Catering Craft', 'Catering Craft for Senior Secondary', 'Vocational', 1);

-- Now assign subjects to their respective classes
-- Pre-Nursery (Class ID: 1)
INSERT INTO subject_class_assignments (subject_id, class_id) VALUES
(1, 1),  -- English
(2, 1),  -- Mathematics
(3, 1),  -- Phonics
(4, 1),  -- Basic Science
(5, 1),  -- Health Habits
(6, 1),  -- Social Habits
(7, 1),  -- Handwriting
(8, 1),  -- Rhymes
(9, 1);  -- Coloring

-- Nursery 1 & 2 (Class IDs: 2, 3)
INSERT INTO subject_class_assignments (subject_id, class_id) 
SELECT id, 2 FROM subjects WHERE id BETWEEN 10 AND 20
UNION ALL
SELECT id, 3 FROM subjects WHERE id BETWEEN 10 AND 20;

-- Primary Section (Class IDs: 4, 5, 6, 7, 8) - Primary 1 to Primary 5
INSERT INTO subject_class_assignments (subject_id, class_id) 
SELECT id, 4 FROM subjects WHERE id BETWEEN 21 AND 34
UNION ALL
SELECT id, 5 FROM subjects WHERE id BETWEEN 21 AND 34
UNION ALL
SELECT id, 6 FROM subjects WHERE id BETWEEN 21 AND 34
UNION ALL
SELECT id, 7 FROM subjects WHERE id BETWEEN 21 AND 34
UNION ALL
SELECT id, 8 FROM subjects WHERE id BETWEEN 21 AND 34;

-- Junior Secondary Section (Class IDs: 9, 10, 11) - JSS 1 to JSS 3
INSERT INTO subject_class_assignments (subject_id, class_id) 
SELECT id, 9 FROM subjects WHERE id BETWEEN 35 AND 49
UNION ALL
SELECT id, 10 FROM subjects WHERE id BETWEEN 35 AND 49
UNION ALL
SELECT id, 11 FROM subjects WHERE id BETWEEN 35 AND 49;

-- Senior Secondary Section (Class IDs: 12, 13, 14) - SS 1 to SS 3
-- All SS subjects for all SS classes
INSERT INTO subject_class_assignments (subject_id, class_id) 
SELECT id, 12 FROM subjects WHERE id BETWEEN 50 AND 60
UNION ALL
SELECT id, 13 FROM subjects WHERE id BETWEEN 50 AND 60
UNION ALL
SELECT id, 14 FROM subjects WHERE id BETWEEN 50 AND 60;
