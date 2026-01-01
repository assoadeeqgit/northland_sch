-- Fix: Remove old class_teacher_id foreign key constraint from classes table
-- Since we now use teacher_class_assignments table for class teacher designation

-- Drop the old foreign key constraint
ALTER TABLE classes DROP FOREIGN KEY classes_ibfk_1;

-- Optional: Drop the class_teacher_id column entirely (or keep it for backward compatibility)
-- Uncomment the line below if you want to remove the column completely
-- ALTER TABLE classes DROP COLUMN class_teacher_id;

-- If you want to keep the column but make it nullable without foreign key:
ALTER TABLE classes MODIFY class_teacher_id INT NULL;
