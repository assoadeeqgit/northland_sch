-- Add is_form_master column to teachers table
-- Form masters are teachers assigned as class teachers
-- Only form masters can access teacher dashboard

ALTER TABLE teachers 
ADD COLUMN is_form_master TINYINT(1) DEFAULT 0 COMMENT '1 = Form Master (Class Teacher), 0 = Regular Teacher';

-- Create index for faster queries
CREATE INDEX idx_is_form_master ON teachers(is_form_master);

-- Update existing teachers who are already class teachers to be form masters
-- Based on teacher_class_assignments where is_class_teacher = 1
UPDATE teachers t
SET is_form_master = 1
WHERE EXISTS (
    SELECT 1 FROM teacher_class_assignments tca 
    WHERE tca.teacher_id = t.id AND tca.is_class_teacher = 1
);

-- Also update based on old class_teacher_id system
UPDATE teachers t
SET is_form_master = 1
WHERE EXISTS (
    SELECT 1 FROM classes c 
    WHERE c.class_teacher_id = t.id
);
