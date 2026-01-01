-- Add academic session tracking for students
-- This allows tracking which session student joined and expected graduation session

ALTER TABLE students 
ADD COLUMN admission_session_id INT NULL COMMENT 'Session when student was admitted',
ADD COLUMN expected_graduation_session_id INT NULL COMMENT 'Expected graduation session',
ADD FOREIGN KEY (admission_session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL,
ADD FOREIGN KEY (expected_graduation_session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL;

-- Create index for faster filtering
CREATE INDEX idx_expected_graduation ON students(expected_graduation_session_id, status);
CREATE INDEX idx_admission_session ON students(admission_session_id);

-- Update existing students: Set admission_session based on admission_date
-- This matches admission date to the active session at that time
UPDATE students s
LEFT JOIN academic_sessions asess ON YEAR(s.admission_date) = SUBSTRING(asess.session_name, 1, 4)
SET s.admission_session_id = asess.id
WHERE s.admission_session_id IS NULL AND asess.id IS NOT NULL;
