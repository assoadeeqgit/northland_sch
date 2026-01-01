-- Migration: Teacher Subject and Class Assignments
-- Creates tables for managing which teachers teach which subjects in which classes

-- Create teacher_subject_assignments table
CREATE TABLE IF NOT EXISTS teacher_subject_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL COMMENT 'References teachers.id',
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create teacher_class_assignments table
CREATE TABLE IF NOT EXISTS teacher_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL COMMENT 'References teachers.id',
    class_id INT NOT NULL,
    is_class_teacher TINYINT(1) DEFAULT 0 COMMENT '1 if this teacher is the homeroom/class teacher',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_class (teacher_id, class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create comprehensive teacher_subject_class_assignments table
-- This table combines both subject and class assignments for a teacher
CREATE TABLE IF NOT EXISTS teacher_subject_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL COMMENT 'References teachers.id',
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    academic_session_id INT NULL,
    term_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL,
    UNIQUE KEY unique_teacher_subject_class_term (teacher_id, subject_id, class_id, academic_session_id, term_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_subject (subject_id),
    INDEX idx_class (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for better query performance
CREATE INDEX idx_teacher_class ON teacher_class_assignments(teacher_id, class_id);
CREATE INDEX idx_teacher_subject ON teacher_subject_assignments(teacher_id, subject_id);

