<?php
/**
 * Teacher-Subject-Class Assignment Helper Functions
 * Provides utility functions for managing teacher assignments
 */

/**
 * Get all subjects assigned to a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID (teachers.id)
 * @return array - Array of subjects
 */
function  getTeacherSubjects($pdo, $teacher_db_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                s.id,
                s.subject_code,
                s.subject_name,
                s.category
            FROM subjects s
            JOIN teacher_subject_assignments tsa ON s.id = tsa.subject_id
            WHERE tsa.teacher_id = ?
            ORDER BY s.subject_name
        ");
        $stmt->execute([$teacher_db_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching teacher subjects: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all classes assigned to a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @return array - Array of classes
 */
function getTeacherClasses($pdo, $teacher_db_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.class_name,
                c.section,
                tca.is_class_teacher
            FROM classes c
            JOIN teacher_class_assignments tca ON c.id = tca.class_id
            WHERE tca.teacher_id = ?
            ORDER BY c.id
        ");
        $stmt->execute([$teacher_db_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching teacher classes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get complete teaching assignments for a teacher (subject-class combinations)
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @return array - Array of assignments
 */
function getTeacherAssignments($pdo, $teacher_db_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                tsca.id,
                s.subject_name,
                s.subject_code,
                c.class_name,
                tsca.academic_session_id,
                tsca.term_id,
                asess.session_name,
                t.term_name
            FROM teacher_subject_class_assignments tsca
            JOIN subjects s ON tsca.subject_id = s.id
            JOIN classes c ON tsca.class_id = c.id
            LEFT JOIN academic_sessions asess ON tsca.academic_session_id = asess.id
            LEFT JOIN terms t ON tsca.term_id = t.id
            WHERE tsca.teacher_id = ?
            ORDER BY c.id, s.subject_name
        ");
        $stmt->execute([$teacher_db_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching teacher assignments: " . $e->getMessage());
        return [];
    }
}

/**
 * Assign a subject to a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @param int $subject_id - Subject ID
 * @return bool - Success status
 */
function assignSubjectToTeacher($pdo, $teacher_db_id, $subject_id) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO teacher_subject_assignments (teacher_id, subject_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE teacher_id = teacher_id
        ");
        $stmt->execute([$teacher_db_id, $subject_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error assigning subject to teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Assign a class to a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @param int $class_id - Class ID
 * @param bool $is_class_teacher - Whether this teacher is the homeroom teacher
 * @return bool - Success status
 */
function assignClassToTeacher($pdo, $teacher_db_id, $class_id, $is_class_teacher = false) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO teacher_class_assignments (teacher_id, class_id, is_class_teacher)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_class_teacher = ?
        ");
        $stmt->execute([$teacher_db_id, $class_id, $is_class_teacher, $is_class_teacher]);
        return true;
    } catch (PDOException $e) {
        error_log("Error assigning class to teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Assign a subject-class combination to a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @param int $subject_id - Subject ID
 * @param int $class_id - Class ID
 * @param int $session_id - Academic session ID (optional)
 * @param int $term_id - Term ID (optional)
 * @return bool - Success status
 */
function assignTeacherToSubjectClass($pdo, $teacher_db_id, $subject_id, $class_id, $session_id = null, $term_id = null) {
    try {
        // First, ensure the subject is assigned to the class
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM subject_class_assignments
            WHERE subject_id = ? AND class_id = ?
        ");
        $checkStmt->execute([$subject_id, $class_id]);
        
        if ($checkStmt->fetchColumn() == 0) {
            error_log("Cannot assign: Subject $subject_id is not assigned to class $class_id");
            return false;
        }
        
        // Assign the teacher
        $stmt = $pdo->prepare("
            INSERT INTO teacher_subject_class_assignments 
            (teacher_id, subject_id, class_id, academic_session_id, term_id)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            academic_session_id = ?, 
            term_id = ?
        ");
        $stmt->execute([
            $teacher_db_id, 
            $subject_id, 
            $class_id, 
            $session_id, 
            $term_id,
            $session_id,
            $term_id
        ]);
        
        // Also add to individual assignments
        assignSubjectToTeacher($pdo, $teacher_db_id, $subject_id);
        assignClassToTeacher($pdo, $teacher_db_id, $class_id);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error assigning teacher to subject-class: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a subject from a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @param int $subject_id - Subject ID
 * @return bool - Success status
 */
function removeSubjectFromTeacher($pdo, $teacher_db_id, $subject_id) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM teacher_subject_assignments
            WHERE teacher_id = ? AND subject_id = ?
        ");
        $stmt->execute([$teacher_db_id, $subject_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error removing subject from teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a class from a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @param int $class_id - Class ID
 * @return bool - Success status
 */
function removeClassFromTeacher($pdo, $teacher_db_id, $class_id) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM teacher_class_assignments
            WHERE teacher_id = ? AND class_id = ?
        ");
        $stmt->execute([$teacher_db_id, $class_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error removing class from teacher: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a specific subject-class assignment from a teacher
 * @param PDO $pdo - Database connection
 * @param int $assignment_id - Assignment ID
 * @return bool - Success status
 */
function removeTeacherAssignment($pdo, $assignment_id) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM teacher_subject_class_assignments
            WHERE id = ?
        ");
        $stmt->execute([$assignment_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error removing teacher assignment: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all teachers assigned to a subject
 * @param PDO $pdo - Database connection
 * @param int $subject_id - Subject ID
 * @return array - Array of teachers
 */
function getSubjectTeachers($pdo, $subject_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                t.id,
                t.teacher_id,
                u.first_name,
                u.last_name,
                u.email
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            JOIN teacher_subject_assignments tsa ON t.id = tsa.teacher_id
            WHERE tsa.subject_id = ?
            AND u.is_active = 1
            ORDER BY u.first_name, u.last_name
        ");
        $stmt->execute([$subject_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subject teachers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all teachers assigned to a class
 * @param PDO $pdo - Database connection
 * @param int $class_id - Class ID
 * @return array - Array of teachers
 */
function getClassTeachers($pdo, $class_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                t.id,
                t.teacher_id,
                u.first_name,
                u.last_name,
                u.email,
                tca.is_class_teacher
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            JOIN teacher_class_assignments tca ON t.id = tca.teacher_id
            WHERE tca.class_id = ?
            AND u.is_active = 1
            ORDER BY tca.is_class_teacher DESC, u.first_name, u.last_name
        ");
        $stmt->execute([$class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching class teachers: " . $e->getMessage());
        return [];
    }
}

/**
 * Batch assign subjects and classes to a teacher
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @param array $subject_ids - Array of subject IDs
 * @param array $class_ids - Array of class IDs
 * @return bool - Success status
 */
function batchAssignTeacher($pdo, $teacher_db_id, $subject_ids = [], $class_ids = []) {
    try {
        $pdo->beginTransaction();
        
        // Clear existing assignments
        $pdo->prepare("DELETE FROM teacher_subject_assignments WHERE teacher_id = ?")->execute([$teacher_db_id]);
        $pdo->prepare("DELETE FROM teacher_class_assignments WHERE teacher_id = ?")->execute([$teacher_db_id]);
        
        // Assign subjects
        foreach ($subject_ids as $subject_id) {
            assignSubjectToTeacher($pdo, $teacher_db_id, $subject_id);
        }
        
        // Assign classes
        foreach ($class_ids as $class_id) {
            assignClassToTeacher($pdo, $teacher_db_id, $class_id);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in batch assignment: " . $e->getMessage());
        return false;
    }
}

/**
 * Get teacher statistics
 * @param PDO $pdo - Database connection
 * @param int $teacher_db_id - Teacher's database ID
 * @return array - Statistics array
 */
function getTeacherStats($pdo, $teacher_db_id) {
    try {
        $stats = [];
        
        // Count subjects
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) FROM teacher_subject_assignments WHERE teacher_id = ?");
        $stmt->execute([$teacher_db_id]);
        $stats['total_subjects'] = $stmt->fetchColumn();
        
        // Count classes
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT class_id) FROM teacher_class_assignments WHERE teacher_id = ?");
        $stmt->execute([$teacher_db_id]);
        $stats['total_classes'] = $stmt->fetchColumn();
        
        // Count assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teacher_subject_class_assignments WHERE teacher_id = ?");
        $stmt->execute([$teacher_db_id]);
        $stats['total_assignments'] = $stmt->fetchColumn();
        
        // Check if class teacher and get class names
        $stmt = $pdo->prepare("
            SELECT c.class_name 
            FROM teacher_class_assignments tca
            JOIN classes c ON tca.class_id = c.id
            WHERE tca.teacher_id = ? AND tca.is_class_teacher = 1
            ORDER BY c.id
        ");
        $stmt->execute([$teacher_db_id]);
        $class_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stats['is_class_teacher'] = count($class_names) > 0;
        $stats['class_teacher_for'] = count($class_names) > 0 ? implode(', ', $class_names) : 'No';
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error fetching teacher stats: " . $e->getMessage());
        return [
            'total_subjects' => 0,
            'total_classes' => 0,
            'total_assignments' => 0,
            'is_class_teacher' => false,
            'class_teacher_for' => 'No'
        ];
    }
}
?>
