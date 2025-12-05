-- Migration: Change class_level column to ENUM type
-- This ensures data integrity by restricting class_level values
-- to only the allowed options: 'Early Childhood', 'Primary', 'Secondary'

-- Step 1: Update any invalid or NULL values to a default
UPDATE classes 
SET class_level = 'Primary' 
WHERE class_level IS NULL OR class_level = '0' OR class_level = '';

-- Step 2: Modify the column to ENUM type
ALTER TABLE classes 
MODIFY COLUMN class_level ENUM('Early Childhood', 'Primary', 'Secondary') NOT NULL DEFAULT 'Primary';

-- Verification query (optional - run this to check the change)
-- DESCRIBE classes;

-- Success message
SELECT 'class_level column successfully changed to ENUM type!' AS status;
