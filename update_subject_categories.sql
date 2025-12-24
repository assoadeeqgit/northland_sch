-- Update Subject Categories to use Department/Type instead of Core/Elective
-- The Core/Elective distinction is now handled by the is_compulsory field in class_subjects table

-- Languages
UPDATE subjects SET category = 'Languages' WHERE subject_code IN ('ENG', 'FRN');

-- Sciences
UPDATE subjects SET category = 'Science' WHERE subject_code IN ('BIO', 'CHEM', 'PHY');

-- Mathematics
UPDATE subjects SET category = 'Mathematics' WHERE subject_code = 'MAT';

-- Social Sciences
UPDATE subjects SET category = 'Social Sciences' WHERE subject_code IN ('GEO', 'HIS');

-- Religious Studies
UPDATE subjects SET category = 'Religious Studies' WHERE subject_code IN ('CRS', 'IRS');

-- Arts & Music
UPDATE subjects SET category = 'Arts' WHERE subject_code IN ('MUS', 'D');

-- Extra-Curricular/Break (keep as is or set to null)
UPDATE subjects SET category = 'Break' WHERE subject_code LIKE 'BREAK%';

-- Verify the changes
SELECT subject_code, subject_name, category FROM subjects ORDER BY category, subject_name;
