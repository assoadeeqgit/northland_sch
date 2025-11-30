-- Simplify Subject Categories to Science, Arts, and Commerce
-- This aligns with standard Nigerian secondary school streaming

-- SCIENCE CATEGORY
-- Pure Sciences and Mathematics
UPDATE subjects SET category = 'Science' WHERE subject_code IN ('BIO', 'CHEM', 'PHY', 'MAT');

-- ARTS CATEGORY  
-- Languages, Social Sciences, Religious Studies, Creative Arts
UPDATE subjects SET category = 'Arts' WHERE subject_code IN (
    'ENG', 'FRN',           -- Languages
    'GEO', 'HIS',           -- Social Sciences
    'CRS', 'IRS',           -- Religious Studies
    'MUS', 'D'              -- Creative Arts
);

-- COMMERCE CATEGORY
-- (Currently no commerce subjects, but reserving for future)
-- Examples would be: Economics, Accounting, Commerce, Business Studies

-- Keep Break periods as NULL or separate
UPDATE subjects SET category = NULL WHERE subject_code LIKE 'BREAK%';

-- Verify the changes
SELECT 
    category,
    COUNT(*) as subject_count,
    GROUP_CONCAT(subject_name SEPARATOR ', ') as subjects
FROM subjects 
WHERE is_active = 1 
GROUP BY category
ORDER BY category;
