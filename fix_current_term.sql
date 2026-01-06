-- Fix current term status for 2025/2026 session
-- Today is Jan 4, 2026 - we should be in a transition period or extend First Term

-- Option 1: Extend First Term to January 5, 2026 and make it current
UPDATE `terms` SET 
    `end_date` = '2026-01-05',
    `is_current` = 1 
WHERE `session_id` = 3 AND `term_name` = 'First Term';

-- Make Second Term inactive until Jan 6
UPDATE `terms` SET `is_current` = 0 
WHERE `session_id` = 3 AND `term_name` = 'Second Term';

-- Verify the changes
SELECT t.*, s.session_name 
FROM `terms` t 
JOIN `academic_sessions` s ON t.session_id = s.id 
WHERE s.session_name = '2025/2026' 
ORDER BY t.start_date;
