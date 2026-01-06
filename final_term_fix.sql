-- Permanently fix 2025/2026 term dates
UPDATE terms SET 
    start_date = '2025-09-09',
    end_date = '2026-01-05'
WHERE session_id = 3 AND term_name = 'First Term';

UPDATE terms SET 
    start_date = '2026-01-06',
    end_date = '2026-04-11'
WHERE session_id = 3 AND term_name = 'Second Term';

UPDATE terms SET 
    start_date = '2026-04-28',
    end_date = '2026-07-25'
WHERE session_id = 3 AND term_name = 'Third Term';

-- Set First Term as current (since today is Jan 4, 2026)
UPDATE terms SET is_current = 0 WHERE session_id = 3;
UPDATE terms SET is_current = 1 WHERE session_id = 3 AND term_name = 'First Term';

-- Verify
SELECT t.id, t.term_name, t.start_date, t.end_date, t.is_current,
       CASE 
           WHEN '2026-01-04' BETWEEN t.start_date AND t.end_date THEN 'ACTIVE'
           ELSE 'INACTIVE'
       END as should_be_active
FROM terms t 
WHERE session_id = 3 
ORDER BY t.start_date;
