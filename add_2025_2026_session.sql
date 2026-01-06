-- Add 2025/2026 Academic Session and Terms
-- Run this script to add the new academic session for 2025/2026

-- First, set the current session (2024/2025) to inactive
UPDATE `academic_sessions` SET `is_current` = 0 WHERE `session_name` = '2024/2025';

-- Add the new 2025/2026 academic session
INSERT INTO `academic_sessions` (`session_name`, `start_date`, `end_date`, `is_current`, `created_at`) 
VALUES ('2025/2026', '2025-09-01', '2026-08-31', 1, NOW());

-- Get the ID of the newly created session (assuming it will be ID 3)
SET @session_id = LAST_INSERT_ID();

-- Add terms for 2025/2026 academic session
INSERT INTO `terms` (`term_name`, `session_id`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
('First Term', @session_id, '2025-09-09', '2025-12-13', 0, NOW()),
('Second Term', @session_id, '2026-01-06', '2026-04-11', 1, NOW()),
('Third Term', @session_id, '2026-04-28', '2026-07-25', 0, NOW());

-- Update any existing current terms to inactive
UPDATE `terms` SET `is_current` = 0 WHERE `session_id` != @session_id;

-- Verify the data
SELECT 'Academic Sessions:' as Info;
SELECT * FROM `academic_sessions` ORDER BY `id`;

SELECT 'Terms for 2025/2026:' as Info;
SELECT t.*, s.session_name 
FROM `terms` t 
JOIN `academic_sessions` s ON t.session_id = s.id 
WHERE s.session_name = '2025/2026' 
ORDER BY t.id;
