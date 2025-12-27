-- Database Migration for Graduation System
-- Date: 2025-12-27
-- Description: Add status and graduation_date columns to students table for graduation system

-- Add status column to track student enrollment status
ALTER TABLE students 
ADD COLUMN status ENUM('active', 'graduated', 'transferred', 'withdrawn') 
NOT NULL DEFAULT 'active' 
AFTER class_id;

-- Add graduation_date column to record when students graduate
ALTER TABLE students 
ADD COLUMN graduation_date DATE NULL 
AFTER status;

-- Update existing students to have 'active' status (already set by DEFAULT)
-- No additional UPDATE needed as DEFAULT handles this

-- Create index for better query performance on status
CREATE INDEX idx_students_status ON students(status);

-- Verify the changes
-- DESCRIBE students;
