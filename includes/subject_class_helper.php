<?php
/**
 * Subject-Class Relationship Helper Functions
 * Provides utility functions for managing subject-class assignments
 */

/**
 * Get all subjects assigned to a specific class
 * @param PDO $pdo - Database connection
 * @param int $class_id - Class ID
 * @return array - Array of subjects
 */
function getSubjectsByClass($pdo, $class_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.id,
                s.subject_code,
                s.subject_name,
                s.description,
                s.category,
                s.is_active
            FROM subjects s
            JOIN subject_class_assignments sca ON s.id = sca.subject_id
            WHERE sca.class_id = :class_id
            AND s.is_active = 1
            ORDER BY s.subject_name
        ");
        $stmt->execute(['class_id' => $class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subjects for class: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all classes assigned to a specific subject
 * @param PDO $pdo - Database connection
 * @param int $subject_id - Subject ID
 * @return array - Array of classes
 */
function getClassesBySubject($pdo, $subject_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.class_name,
                c.section
            FROM classes c
            JOIN subject_class_assignments sca ON c.id = sca.class_id
            WHERE sca.subject_id = :subject_id
            ORDER BY c.id
        ");
        $stmt->execute(['subject_id' => $subject_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching classes for subject: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if a subject is assigned to a class
 * @param PDO $pdo - Database connection
 * @param int $subject_id - Subject ID
 * @param int $class_id - Class ID
 * @return bool - True if assigned, false otherwise
 */
function isSubjectAssignedToClass($pdo, $subject_id, $class_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM subject_class_assignments
            WHERE subject_id = :subject_id
            AND class_id = :class_id
        ");
        $stmt->execute([
            'subject_id' => $subject_id,
            'class_id' => $class_id
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Error checking subject-class assignment: " . $e->getMessage());
        return false;
    }
}

/**
 * Assign a subject to a class
 * @param PDO $pdo - Database connection
 * @param int $subject_id - Subject ID
 * @param int $class_id - Class ID
 * @return bool - True on success, false on failure
 */
function assignSubjectToClass($pdo, $subject_id, $class_id) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO subject_class_assignments (subject_id, class_id)
            VALUES (:subject_id, :class_id)
            ON DUPLICATE KEY UPDATE subject_id = subject_id
        ");
        $stmt->execute([
            'subject_id' => $subject_id,
            'class_id' => $class_id
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error assigning subject to class: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove a subject assignment from a class
 * @param PDO $pdo - Database connection
 * @param int $subject_id - Subject ID
 * @param int $class_id - Class ID
 * @return bool - True on success, false on failure
 */
function removeSubjectFromClass($pdo, $subject_id, $class_id) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM subject_class_assignments
            WHERE subject_id = :subject_id
            AND class_id = :class_id
        ");
        $stmt->execute([
            'subject_id' => $subject_id,
            'class_id' => $class_id
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error removing subject from class: " . $e->getMessage());
        return false;
    }
}

/**
 * Get subject count per class
 * @param PDO $pdo - Database connection
 * @return array - Array of classes with subject counts
 */
function getSubjectCountPerClass($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                c.id,
                c.class_name,
                COUNT(sca.id) as subject_count
            FROM classes c
            LEFT JOIN subject_class_assignments sca ON c.id = sca.class_id
            GROUP BY c.id, c.class_name
            ORDER BY c.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subject counts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all subjects with their assigned classes
 * @param PDO $pdo - Database connection
 * @return array - Array of subjects with class information
 */
function getAllSubjectsWithClasses($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                s.id,
                s.subject_code,
                s.subject_name,
                s.category,
                GROUP_CONCAT(c.class_name ORDER BY c.id SEPARATOR ', ') as assigned_classes,
                COUNT(DISTINCT c.id) as class_count
            FROM subjects s
            LEFT JOIN subject_class_assignments sca ON s.id = sca.subject_id
            LEFT JOIN classes c ON sca.class_id = c.id
            WHERE s.is_active = 1
            GROUP BY s.id, s.subject_code, s.subject_name, s.category
            ORDER BY s.subject_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subjects with classes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get subjects by section (Pre-Nursery, Nursery, Primary, JSS, SS)
 * @param PDO $pdo - Database connection
 * @param string $section - Section name
 * @return array - Array of subjects
 */
function getSubjectsBySection($pdo, $section) {
    try {
        // Map sections to class IDs
        $section_class_map = [
            'Pre-Nursery' => [1],
            'Nursery' => [2, 3],
            'Primary' => [4, 5, 6, 7, 8],
            'Junior Secondary' => [9, 10, 11],
            'Senior Secondary' => [12, 13, 14]
        ];
        
        if (!isset($section_class_map[$section])) {
            return [];
        }
        
        $class_ids = $section_class_map[$section];
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                s.id,
                s.subject_code,
                s.subject_name,
                s.description,
                s.category
            FROM subjects s
            JOIN subject_class_assignments sca ON s.id = sca.subject_id
            WHERE sca.class_id IN ($placeholders)
            AND s.is_active = 1
            ORDER BY s.subject_name
        ");
        $stmt->execute($class_ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching subjects by section: " . $e->getMessage());
        return [];
    }
}
?>
