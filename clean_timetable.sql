-- ============================================
-- NORTHLAND SCHOOLS KANO - TIMETABLE CLEANUP
-- ============================================
-- This script removes all existing timetable data
-- to prepare for a clean re-population
-- ============================================

-- Temporarily disable foreign key checks (if any)
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate (empty) the timetable table
TRUNCATE TABLE `timetable`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify cleanup
SELECT 'Timetable cleaned successfully' AS status, COUNT(*) AS remaining_entries 
FROM timetable;

-- Optional: Reset the break subject entries
-- This ensures the subject names are correct
UPDATE `subjects` 
SET subject_name = 'Break Time' 
WHERE subject_code IN ('BREAK-NUR', 'BREAK-PS');

-- Verify break subjects
SELECT subject_code, subject_name, description 
FROM subjects 
WHERE subject_code IN ('BREAK-NUR', 'BREAK-PS');
