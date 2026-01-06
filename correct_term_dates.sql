-- Correct the term dates for 2025/2026 academic session
UPDATE `terms` SET 
    `start_date` = '2025-09-09',
    `end_date` = '2026-01-05',
    `is_current` = 1 
WHERE `session_id` = 3 AND `term_name` = 'First Term';

UPDATE `terms` SET 
    `start_date` = '2026-01-06',
    `end_date` = '2026-04-11',
    `is_current` = 0 
WHERE `session_id` = 3 AND `term_name` = 'Second Term';

UPDATE `terms` SET 
    `start_date` = '2026-04-28',
    `end_date` = '2026-07-25',
    `is_current` = 0 
WHERE `session_id` = 3 AND `term_name` = 'Third Term';

-- Verify
SELECT t.*, s.session_name 
FROM `terms` t 
JOIN `academic_sessions` s ON t.session_id = s.id 
WHERE s.session_name = '2025/2026' 
ORDER BY t.start_date;
