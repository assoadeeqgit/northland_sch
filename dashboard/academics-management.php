<?php
// Enable error reporting for debugging
// Debugging disabled
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// AJAX Handler for filtering
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    require_once '../config/database.php';
    header('Content-Type: application/json');
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $tab = $_GET['tab'] ?? 'assignments';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $classFilter = isset($_GET['class_filter']) ? intval($_GET['class_filter']) : '';
        $departmentFilter = $_GET['department_filter'] ?? '';
        $search = $_GET['search'] ?? '';
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        if ($tab === 'assignments') {
            // Build assignments query
            $whereParts = ["cs.id IS NOT NULL"];
            $params = [];
            
            if (!empty($classFilter)) {
                $whereParts[] = "cs.class_id = ?";
                $params[] = $classFilter;
            }
            
            if (!empty($departmentFilter)) {
                $whereParts[] = "s.category = ?";
                $params[] = $departmentFilter;
            }
            
            if (!empty($search)) {
                $whereParts[] = "(s.subject_name LIKE ? OR c.class_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = implode(" AND ", $whereParts);
            
            // Count total
            $countSql = "SELECT COUNT(*) FROM class_subjects cs 
                        LEFT JOIN subjects s ON cs.subject_id = s.id 
                        LEFT JOIN classes c ON cs.class_id = c.id 
                        LEFT JOIN teachers t ON cs.teacher_id = t.id 
                        LEFT JOIN users u ON t.user_id = u.id 
                        WHERE $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Get data
            $sql = "SELECT cs.id, c.class_name, c.class_code, s.subject_name, s.subject_code, s.category,
                           CONCAT(u.first_name, ' ', u.last_name) as teacher_name, t.teacher_id, cs.created_at
                    FROM class_subjects cs 
                    LEFT JOIN subjects s ON cs.subject_id = s.id 
                    LEFT JOIN classes c ON cs.class_id = c.id 
                    LEFT JOIN teachers t ON cs.teacher_id = t.id 
                    LEFT JOIN users u ON t.user_id = u.id 
                    WHERE $whereClause 
                    ORDER BY c.class_name, s.subject_name 
                    LIMIT $perPage OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } else if ($tab === 'subjects') {
            // Build subjects query
            $whereParts = ["s.is_active = 1"];
            $params = [];
            $from = "subjects s";
            
            if (!empty($classFilter)) {
                $from = "subjects s INNER JOIN class_subjects cs ON s.id = cs.subject_id";
                $whereParts[] = "cs.class_id = ?";
                $params[] = $classFilter;
            }
            
            if (!empty($departmentFilter)) {
                $whereParts[] = "s.category = ?";
                $params[] = $departmentFilter;
            }
            
            if (!empty($search)) {
                $whereParts[] = "(s.subject_name LIKE ? OR s.subject_code LIKE ? OR s.description LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = implode(" AND ", $whereParts);
            
            // Count total
            $countSql = "SELECT COUNT(DISTINCT s.id) FROM $from WHERE $whereClause";
            $countStmt = $conn->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Get data
            $sql = "SELECT DISTINCT s.id, s.subject_code, s.subject_name, s.category, s.description, s.created_at 
                    FROM $from 
                    WHERE $whereClause 
                    ORDER BY s.subject_name 
                    LIMIT $perPage OFFSET $offset";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $totalPages = ceil($totalItems / $perPage);
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $perPage,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// session_start();

require_once '../config/logger.php';

require_once 'auth-check.php';
require_once __DIR__ . "/../includes/term_helper.php"; // Global term synchronization

// For admin dashboard:
checkAuth('admin');

// Initialize variables
$totalSubjects = 0;
$assignmentsData = [];
$all_classes = [];
$all_subjects_list = [];
$all_teachers = [];
$department_categories = [];
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));

// Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed: ". $e->getMessage());
}

// === HELPER FUNCTIONS ===

// Helper function to determine class level from class name
function getClassLevel($className) {
    $className = strtolower($className);
    if (strpos($className, 'nursery') !== false || strpos($className, 'pre-nursery') !== false || strpos($className, 'kg') !== false) {
        return 'early';
    } elseif (strpos($className, 'primary') !== false) {
        return 'primary';
    } elseif (strpos($className, 'jss') !== false || strpos($className, 'junior') !== false) {
        return 'jss';
    } elseif (strpos($className, 'sss') !== false || strpos($className, 'senior') !== false) {
        return 'sss';
    }
    return 'unknown';
}
// Function to add a new subject assignment
// Function to add a new subject assignment
function assignSubjectToClass($db) {
    try {
        if (empty($_POST['subject_id']) || empty($_POST['class_id']) || empty($_POST['teacher_id'])) {
            throw new Exception("Subject, Class, and Teacher are all required.");
        }

        // Get subject, class, and teacher names for logging
        $subjectStmt = $db->prepare("SELECT subject_name FROM subjects WHERE id = ?");
        $subjectStmt->execute([$_POST['subject_id']]);
        $subjectName = $subjectStmt->fetchColumn();

        $classStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
        $classStmt->execute([$_POST['class_id']]);
        $className = $classStmt->fetchColumn();

        $teacherStmt = $db->prepare("SELECT u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $teacherStmt->execute([$_POST['teacher_id']]);
        $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
        $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];

        // Check for duplicates
        $checkSql = "SELECT COUNT(*) FROM class_subjects WHERE subject_id = ? AND class_id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$_POST['subject_id'], $_POST['class_id']]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("This subject is already assigned to this class.");
        }

        $sql = "INSERT INTO class_subjects (subject_id, class_id, teacher_id, is_compulsory) 
                VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['subject_id'],
            $_POST['class_id'],
            $_POST['teacher_id'],
            $_POST['is_compulsory'] ?? 1
        ]);

        $_SESSION['success'] = "Subject assigned to class successfully!";
        
        // --- LOG ACTIVITY FOR SUBJECT ASSIGNMENT ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $subjectType = ($_POST['is_compulsory'] ?? 1) ? 'Compulsory' : 'Elective';
        
        logActivity(
            $db,
            $admin_name,
            "Assign Subject",
            "Assigned {$subjectName} to {$className} (Teacher: {$teacherName}, Type: {$subjectType})",
            "fas fa-book",
            "bg-nskgreen"
        );
        // --- END LOG ---
        
        header("Location: academics-management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error assigning subject: ". $e->getMessage();
    }
}

// Function to get data for a single assignment
function getClassSubjectData($db, $assignment_id) {
    try {
        $sql = "SELECT * FROM class_subjects WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$assignment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// Function to update a subject assignment
function updateClassSubjectAssignment($db) {
    try {
        if (empty($_POST['assignment_id']) || empty($_POST['subject_id']) || empty($_POST['class_id']) || empty($_POST['teacher_id'])) {
            throw new Exception("Subject, Class, and Teacher are all required.");
        }

        // Get old assignment data for logging
        $oldAssignmentStmt = $db->prepare("
            SELECT s.subject_name, c.class_name, u.first_name, u.last_name, cs.is_compulsory 
            FROM class_subjects cs
            JOIN subjects s ON cs.subject_id = s.id
            JOIN classes c ON cs.class_id = c.id
            JOIN teachers t ON cs.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE cs.id = ?
        ");
        $oldAssignmentStmt->execute([$_POST['assignment_id']]);
        $oldAssignment = $oldAssignmentStmt->fetch(PDO::FETCH_ASSOC);

        // Get new assignment data for logging
        $newSubjectStmt = $db->prepare("SELECT subject_name FROM subjects WHERE id = ?");
        $newSubjectStmt->execute([$_POST['subject_id']]);
        $newSubjectName = $newSubjectStmt->fetchColumn();

        $newClassStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
        $newClassStmt->execute([$_POST['class_id']]);
        $newClassName = $newClassStmt->fetchColumn();

        $newTeacherStmt = $db->prepare("SELECT u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $newTeacherStmt->execute([$_POST['teacher_id']]);
        $newTeacher = $newTeacherStmt->fetch(PDO::FETCH_ASSOC);
        $newTeacherName = $newTeacher['first_name'] . ' ' . $newTeacher['last_name'];

        $sql = "UPDATE class_subjects SET 
                    subject_id = ?, 
                    class_id = ?, 
                    teacher_id = ?, 
                    is_compulsory = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['subject_id'],
            $_POST['class_id'],
            $_POST['teacher_id'],
            $_POST['is_compulsory'] ?? 1,
            $_POST['assignment_id']
        ]);

        $_SESSION['success'] = "Subject assignment updated successfully!";
        
        // --- LOG ACTIVITY FOR UPDATE ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $newSubjectType = ($_POST['is_compulsory'] ?? 1) ? 'Compulsory' : 'Elective';
        
        // Build change description
        $changes = [];
        if ($oldAssignment['subject_name'] != $newSubjectName) {
            $changes[] = "subject from {$oldAssignment['subject_name']} to {$newSubjectName}";
        }
        if ($oldAssignment['class_name'] != $newClassName) {
            $changes[] = "class from {$oldAssignment['class_name']} to {$newClassName}";
        }
        if ($oldAssignment['first_name'] . ' ' . $oldAssignment['last_name'] != $newTeacherName) {
            $changes[] = "teacher from {$oldAssignment['first_name']} {$oldAssignment['last_name']} to {$newTeacherName}";
        }
        if ($oldAssignment['is_compulsory'] != ($_POST['is_compulsory'] ?? 1)) {
            $oldType = $oldAssignment['is_compulsory'] ? 'Compulsory' : 'Elective';
            $changes[] = "type from {$oldType} to {$newSubjectType}";
        }
        
        $changeDescription = !empty($changes) ? " (Changes: " . implode(", ", $changes) . ")" : " (No significant changes)";
        
        logActivity(
            $db,
            $admin_name,
            "Update Assignment",
            "Updated subject assignment: {$newSubjectName} to {$newClassName}{$changeDescription}",
            "fas fa-edit",
            "bg-nskgold"
        );
        // --- END LOG ---
        
        header("Location: academics-management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating assignment: ". $e->getMessage();
        header("Location: academics-management.php?edit=" . $_POST['assignment_id']); // Stay on edit page on error
        exit();
    }
}

// Function to delete a subject assignment (Hard Delete)
function deleteClassSubjectAssignment($db, $assignment_id) {
    try {
        // Get assignment data for logging before deletion
        $assignmentStmt = $db->prepare("
            SELECT s.subject_name, c.class_name, u.first_name, u.last_name, cs.is_compulsory 
            FROM class_subjects cs
            JOIN subjects s ON cs.subject_id = s.id
            JOIN classes c ON cs.class_id = c.id
            JOIN teachers t ON cs.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE cs.id = ?
        ");
        $assignmentStmt->execute([$assignment_id]);
        $assignment = $assignmentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            throw new Exception("Assignment not found.");
        }

        $sql = "DELETE FROM class_subjects WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$assignment_id]);

        $_SESSION['success'] = "Subject assignment deleted successfully!";
        
        // --- LOG ACTIVITY FOR DELETE ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $subjectType = $assignment['is_compulsory'] ? 'Compulsory' : 'Elective';
        
        logActivity(
            $db,
            $admin_name,
            "Delete Assignment",
            "Deleted subject assignment: {$assignment['subject_name']} from {$assignment['class_name']} (Teacher: {$assignment['first_name']} {$assignment['last_name']}, Type: {$subjectType})",
            "fas fa-trash",
            "bg-nskred"
        );
        // --- END LOG ---
        
        header("Location: academics-management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting assignment: ". $e->getMessage();
        header("Location: academics-management.php");
        exit();
    }
}

// Function to create a new subject
function createSubject($db) {
    try {
        if (empty($_POST['subject_name'])) {
            throw new Exception("Subject name is required.");
        }

        // Check if manual code was provided
        $manualCode = isset($_POST['subject_code']) ? strtoupper(trim($_POST['subject_code'])) : '';
        
        if (!empty($manualCode)) {
            // Validate manual code format (alphanumeric with dash allowed)
            if (!preg_match('/^[A-Z0-9\-]+$/', $manualCode)) {
                throw new Exception("Subject code can only contain letters, numbers, and dashes.");
            }
            
            // Check if manual code is unique
            $checkCodeSql = "SELECT COUNT(*) FROM subjects WHERE subject_code = ?";
            $checkCodeStmt = $db->prepare($checkCodeSql);
            $checkCodeStmt->execute([$manualCode]);
            if ($checkCodeStmt->fetchColumn() > 0) {
                throw new Exception("Subject code '{$manualCode}' already exists. Please use a different code.");
            }
            $code = $manualCode;
        } else {
            // Auto-generate unique subject code from name
            $subjectName = $_POST['subject_name'];
            $words = explode(' ', $subjectName);
            $code = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $code .= strtoupper(substr($word, 0, 1));
                }
            }
            
            // Add random number to ensure uniqueness
            $baseCode = $code;
            $counter = 1;
            while (true) {
                $checkCodeSql = "SELECT COUNT(*) FROM subjects WHERE subject_code = ?";
                $checkCodeStmt = $db->prepare($checkCodeSql);
                $checkCodeStmt->execute([$code]);
                if ($checkCodeStmt->fetchColumn() == 0) {
                    break;
                }
                $code = $baseCode . $counter;
                $counter++;
            }
        }

        $sql = "INSERT INTO subjects (subject_code, subject_name, category, description, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $code,
            $_POST['subject_name'],
            $_POST['category'] ?? null,
            $_POST['description'] ?? null
        ]);

        $_SESSION['success'] = "Subject created successfully!";
        
        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Create Subject",
            "Created new subject: {$_POST['subject_name']} (Code: {$code}, Category: " . ($_POST['category'] ?? 'N/A') . ")",
            "fas fa-plus-circle",
            "bg-nskgreen"
        );
        
        header("Location: academics-management.php?tab=subjects");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating subject: ". $e->getMessage();
    }
}

// Function to update a subject
function updateSubject($db) {
    try {
        if (empty($_POST['subject_id']) || empty($_POST['subject_name'])) {
            throw new Exception("Subject ID and name are required.");
        }

        // Get old subject data for logging
        $oldSubjectStmt = $db->prepare("SELECT subject_name, subject_code, category FROM subjects WHERE id = ?");
        $oldSubjectStmt->execute([$_POST['subject_id']]);
        $oldSubject = $oldSubjectStmt->fetch(PDO::FETCH_ASSOC);

        // Handle subject code update
        $newCode = isset($_POST['subject_code']) ? strtoupper(trim($_POST['subject_code'])) : $oldSubject['subject_code'];
        
        if (!empty($newCode) && $newCode !== $oldSubject['subject_code']) {
            // Validate code format
            if (!preg_match('/^[A-Z0-9\-]+$/', $newCode)) {
                throw new Exception("Subject code can only contain letters, numbers, and dashes.");
            }
            
            // Check if new code is unique (exclude current subject)
            $checkCodeSql = "SELECT COUNT(*) FROM subjects WHERE subject_code = ? AND id != ?";
            $checkCodeStmt = $db->prepare($checkCodeSql);
            $checkCodeStmt->execute([$newCode, $_POST['subject_id']]);
            if ($checkCodeStmt->fetchColumn() > 0) {
                throw new Exception("Subject code '{$newCode}' already exists. Please use a different code.");
            }
        }

        $sql = "UPDATE subjects SET 
                    subject_name = ?, 
                    subject_code = ?,
                    category = ?, 
                    description = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['subject_name'],
            $newCode,
            $_POST['category'] ?? null,
            $_POST['description'] ?? null,
            $_POST['subject_id']
        ]);

        $_SESSION['success'] = "Subject updated successfully!";
        
        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Update Subject",
            "Updated subject: {$_POST['subject_name']} (Code: {$newCode})",
            "fas fa-edit",
            "bg-nskgold"
        );
        
        header("Location: academics-management.php?tab=subjects");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating subject: ". $e->getMessage();
        header("Location: academics-management.php?tab=subjects&edit=" . $_POST['subject_id']);
        exit();
    }
}

// Function to delete a subject
function deleteSubject($db, $subject_id) {
    try {
        // Get subject data for logging
        $subjectStmt = $db->prepare("SELECT subject_name, category FROM subjects WHERE id = ?");
        $subjectStmt->execute([$subject_id]);
        $subject = $subjectStmt->fetch(PDO::FETCH_ASSOC);

        if (!$subject) {
            throw new Exception("Subject not found.");
        }

        // Soft delete - mark as inactive
        $sql = "UPDATE subjects SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$subject_id]);

        $_SESSION['success'] = "Subject deleted successfully!";
        
        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Delete Subject",
            "Deleted subject: {$subject['subject_name']} (Category: {$subject['category']})",
            "fas fa-trash",
            "bg-nskred"
        );
        
        header("Location: academics-management.php?tab=subjects");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting subject: ". $e->getMessage();
        header("Location: academics-management.php?tab=subjects");
        exit();
    }
}

// Function to get data for a single subject
function getSubjectData($db, $subject_id) {
    try {
        $sql = "SELECT * FROM subjects WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$subject_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// === POST/GET HANDLERS ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_subject'])) {
        assignSubjectToClass($db);
    }
    
    if (isset($_POST['update_assignment'])) {
        updateClassSubjectAssignment($db);
    }

    if (isset($_POST['delete_assignment']) && isset($_POST['assignment_id'])) {
        deleteClassSubjectAssignment($db, $_POST['assignment_id']);
    }

    if (isset($_POST['create_subject'])) {
        createSubject($db);
    }

    if (isset($_POST['update_subject'])) {
        updateSubject($db);
    }

    if (isset($_POST['delete_subject']) && isset($_POST['subject_id'])) {
        deleteSubject($db, $_POST['subject_id']);
    }

    if (isset($_POST['hide_modal_form'])) {
        header("Location: academics-management.php");
        exit();
    }
}

$editAssignmentData = null;
if (isset($_GET['edit']) && !empty($_GET['edit']) && (!isset($_GET['tab']) || $_GET['tab'] != 'subjects')) {
    $editAssignmentData = getClassSubjectData($db, $_GET['edit']);
}

$editSubjectData = null;
if (isset($_GET['edit']) && !empty($_GET['edit']) && isset($_GET['tab']) && $_GET['tab'] == 'subjects') {
    $editSubjectData = getSubjectData($db, $_GET['edit']);
}

// === AJAX HANDLER ===
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $tab = $_GET['tab'] ?? 'assignments';
    $classFilter = isset($_GET['class_filter']) ? intval($_GET['class_filter']) : '';
    $departmentFilter = $_GET['department_filter'] ?? '';
    
    try {
        if ($tab == 'assignments') {
            // Handle Subject Assignments AJAX
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $items_per_page = 10;
            $offset = ($page - 1) * $items_per_page;
            
            // Build query
            $queryParts = [
                "SELECT cs.id as class_subject_id, s.subject_name, s.category, c.class_name, c.id as class_id,
                t.id as teacher_id, u.first_name, u.last_name,
                (SELECT COUNT(st.id) FROM students st WHERE st.class_id = c.id AND st.user_id IN (SELECT user_id FROM users WHERE is_active = 1)) as student_count
                FROM class_subjects cs",
                "LEFT JOIN subjects s ON cs.subject_id = s.id",
                "LEFT JOIN classes c ON cs.class_id = c.id",
                "LEFT JOIN teachers t ON cs.teacher_id = t.id",
                "LEFT JOIN users u ON t.user_id = u.id",
                "WHERE s.is_active = 1"
            ];
            $params = [];
            
            if (!empty($classFilter)) {
                $queryParts[] = "AND cs.class_id = ?";
                $params[] = $classFilter;
            }
            
            if (!empty($departmentFilter)) {
                $queryParts[] = "AND s.category = ?";
                $params[] = $departmentFilter;
            }
            
            // Count query
            $countSql = str_replace("SELECT cs.id as class_subject_id, s.subject_name, s.category, c.class_name, c.id as class_id,
                t.id as teacher_id, u.first_name, u.last_name,
                (SELECT COUNT(st.id) FROM students st WHERE st.class_id = c.id AND st.user_id IN (SELECT user_id FROM users WHERE is_active = 1)) as student_count", "SELECT COUNT(*)", implode(" ", $queryParts));
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            $total_pages = ceil($total / $items_per_page);
            
            // Data query
            $queryParts[] = "ORDER BY c.class_name, s.subject_name LIMIT $items_per_page OFFSET $offset";
            $sql = implode(" ", $queryParts);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_items' => $total,
                    'items_per_page' => $items_per_page
                ]
            ]);
            
        } else if ($tab == 'subjects') {
            // Handle Subjects AJAX
            $page = isset($_GET['page_subjects']) ? max(1, intval($_GET['page_subjects'])) : 1;
            $items_per_page = 15;
            $offset = ($page - 1) * $items_per_page;
            
            $whereParts = ["s.is_active = 1"];
            $params = [];
            $from = "subjects s";
            
            if (!empty($classFilter)) {
                $from = "subjects s INNER JOIN class_subjects cs ON s.id = cs.subject_id";
                $whereParts[] = "cs.class_id = ?";
                $params[] = $classFilter;
            }
            
            if (!empty($departmentFilter)) {
                $whereParts[] = "s.category = ?";
                $params[] = $departmentFilter;
            }
            
            $whereClause = implode(" AND ", $whereParts);
            
            // Count
            $countSql = "SELECT COUNT(DISTINCT s.id) FROM $from WHERE $whereClause";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            $total_pages = ceil($total / $items_per_page);
            
            // Data
            $dataSql = "SELECT DISTINCT s.id, s.subject_code, s.subject_name, s.category, s.description, s.created_at 
                        FROM $from 
                        WHERE $whereClause 
                        ORDER BY s.subject_name 
                        LIMIT $items_per_page OFFSET $offset";
            $dataStmt = $db->prepare($dataSql);
            $dataStmt->execute($params);
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_items' => $total,
                    'items_per_page' => $items_per_page
                ]
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit();
}

// === DATA FETCHING FOR PAGE LOAD ===

// Get filter parameters
$classFilter = isset($_GET['class_filter']) ? intval($_GET['class_filter']) : '';
$departmentFilter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';

try {
    // Build the main query
    $queryParts = [
        "SELECT 
            cs.id as class_subject_id,
            s.subject_name, s.category,
            c.class_name,
            u.first_name, u.last_name,
            (SELECT COUNT(st.id) FROM students st WHERE st.class_id = c.id AND st.user_id IN (SELECT user_id FROM users WHERE is_active = 1)) as student_count
        FROM class_subjects cs",
        "LEFT JOIN subjects s ON cs.subject_id = s.id",
        "LEFT JOIN classes c ON cs.class_id = c.id",
        "LEFT JOIN teachers t ON cs.teacher_id = t.id",
        "LEFT JOIN users u ON t.user_id = u.id",
        "WHERE s.is_active = 1"
    ];
    $params = [];

    // Add class filter
    if (!empty($classFilter)) {
        $queryParts[] = "AND cs.class_id = ?";
        $params[] = $classFilter;
    }

    // Add department filter
    if (!empty($departmentFilter)) {
        $queryParts[] = "AND s.category = ?";
        $params[] = $departmentFilter;
    }

    $queryParts[] = "ORDER BY c.class_name, s.subject_name";
    $sql = implode(" ", $queryParts);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $assignmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats
    $totalSubjects = $db->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1")->fetchColumn();
    $totalAllocations = $db->query("SELECT COUNT(*) FROM class_subjects")->fetchColumn();
    $totalTeachers = $db->query("SELECT COUNT(t.id) FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1")->fetchColumn();
    
    // Calculate Average Attendance
    $avgAttendance = 0;
    $attStats = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status IN ('Present', 'Late') THEN 1 ELSE 0 END) as present FROM attendance")->fetch(PDO::FETCH_ASSOC);
    if ($attStats['total'] > 0) {
        $avgAttendance = round(($attStats['present'] / $attStats['total']) * 100);
    }
    
    // Get data for dropdowns
    $all_classes = $db->query("SELECT id, class_name FROM classes ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $all_subjects_list = $db->query("SELECT id, subject_name, subject_code, category FROM subjects WHERE is_active = 1 ORDER BY category, subject_name")->fetchAll(PDO::FETCH_ASSOC);
    $all_teachers = $db->query("SELECT t.id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1 ORDER BY u.first_name, u.last_name")->fetchAll(PDO::FETCH_ASSOC);
    $department_categories = $db->query("SELECT DISTINCT category FROM subjects WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);

    // Subjects tab pagination
    $items_per_page_subjects = 15;
    $current_page_subjects = isset($_GET['page_subjects']) ? max(1, intval($_GET['page_subjects'])) : 1;
    $offset_subjects = ($current_page_subjects - 1) * $items_per_page_subjects;
    
    // Build subjects query with filters
    $subjectsWhereParts = ["s.is_active = 1"];
    $subjectsParams = [];
    $subjectsFrom = "subjects s";
    
    // If class filter is active, join with class_subjects to filter by class
    if (!empty($classFilter)) {
        $subjectsFrom = "subjects s INNER JOIN class_subjects cs ON s.id = cs.subject_id";
        $subjectsWhereParts[] = "cs.class_id = ?";
        $subjectsParams[] = $classFilter;
    }
    
    // If department filter is active, filter by category
    if (!empty($departmentFilter)) {
        $subjectsWhereParts[] = "s.category = ?";
        $subjectsParams[] = $departmentFilter;
    }
    
    $subjectsWhereClause = implode(" AND ", $subjectsWhereParts);
    
    // Get total subjects count with filters
    $countSql = "SELECT COUNT(DISTINCT s.id) FROM $subjectsFrom WHERE $subjectsWhereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($subjectsParams);
    $total_subjects_count = $countStmt->fetchColumn();
    $total_pages_subjects = ceil($total_subjects_count / $items_per_page_subjects);

    // Get subjects for subjects tab with pagination and filters
    $subjectsSql = "SELECT DISTINCT s.id, s.subject_code, s.subject_name, s.category, s.description, s.created_at 
                    FROM $subjectsFrom 
                    WHERE $subjectsWhereClause 
                    ORDER BY s.subject_name 
                    LIMIT $items_per_page_subjects OFFSET $offset_subjects";
    $subjectsStmt = $db->prepare($subjectsSql);
    $subjectsStmt->execute($subjectsParams);
    $allSubjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: ". $e->getMessage();
}

?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academics Management - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              nskblue: "#1e40af",
              nsklightblue: "#3b82f6",
              nsknavy: "#1e3a8a",
              nskgold: "#f59e0b",
              nsklight: "#f0f9ff",
              nskgreen: "#10b981",
              nskred: "#ef4444",
            },
          },
        },
      };
    </script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap");

      body {
        font-family: "Montserrat", sans-serif;
        background: #f8fafc;
      }
      
      /* Modal Styles */
      /* Standardized Modal Styling */
      .modal {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          display: none; /* Hidden by default */
          align-items: center;
          justify-content: center;
          z-index: 1000;
          opacity: 0;
          transition: opacity 0.3s ease;
          backdrop-filter: blur(5px);
      }

      .modal.active {
          display: flex;
          opacity: 1;
      }

      .modal-content { 
          background-color: white; 
          border-radius: 1rem;
          padding: 2rem;
          width: 95%; 
          max-width: 800px; 
          max-height: 90vh; 
          overflow-y: auto; 
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
          transform: scale(0.95);
          transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      }

      .modal.active .modal-content {
          transform: scale(1);
      }

      /* Prevent body scroll when modal is open */
      body.modal-active { overflow: hidden; }

      .logo-container {
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
      }

      .academics-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .academics-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      }

      .nav-item {
        position: relative;
      }

      .nav-item::after {
        content: "";
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -5px;
        left: 0;
        background-color: #f59e0b;
        transition: width 0.3s ease;
      }

      .nav-item:hover::after {
        width: 100%;
      }

      .notification-dot {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 12px;
        height: 12px;
        background-color: #ef4444;
        border-radius: 50%;
        animation: pulse 2s infinite;
      }

      .academics-table {
        border-collapse: separate;
        border-spacing: 0;
      }

      .academics-table th {
        background-color: #f8fafc;
      }

      .academics-table tr:last-child td {
        border-bottom: 0;
      }

      .academics-table tbody tr {
        transition: all 0.3s ease;
      }

      .academics-table tbody tr:hover {
        background-color: #f0f9ff;
        transform: scale(1.01);
      }

      .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }

      .grade-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }

      .tab-button {
        transition: all 0.3s ease;
      }

      .tab-button.active {
        background-color: #1e40af;
        color: white;
        box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
      }
      
      .tab-content {
        display: none;
      }

      .tab-content.active {
        display: block;
        animation: fadeIn 0.5s ease;
      }
      
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes pulse {
        0%,
        100% {
          opacity: 1;
        }
        50% {
          opacity: 0.5;
        }
      }
    </style>
  </head>
 <body>
    <div id="sidebar-container"></div>
    <?php require_once 'sidebar.php'; ?>
    
    <main class="main-content">
      <?php 
      $pageTitle = 'Academic Management';
      $pageSubtitle = 'Manage subject assignments, and academic activities';
      require_once 'header.php'; 
      ?>

      <div class="p-4 md:p-6">
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8 stats-grid"
        >
          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
          >
            <div class="bg-nskgreen p-4 rounded-full mr-4">
              <i class="fas fa-book text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Total Subjects</p>
              <p class="text-2xl font-bold text-nsknavy" id="totalSubjects">
                <?= $totalSubjects ?>
              </p>
              <p class="text-xs text-nskgreen">Across all grades</p>
            </div>
          </div>

          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
            style="animation-delay: 0.1s"
          >
            <div class="bg-nskblue p-4 rounded-full mr-4">
              <i class="fas fa-tasks text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Subject Allocations</p>
              <p class="text-2xl font-bold text-nsknavy" id="activeAssignments">
                <?= $totalAllocations ?>
              </p>
              <p class="text-xs text-nskblue">Active teacher-subject pairings</p>
            </div>
          </div>

          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
            style="animation-delay: 0.2s"
          >
            <div class="bg-nskgold p-4 rounded-full mr-4">
              <i class="fas fa-user-clock text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Average Attendance</p>
              <p class="text-2xl font-bold text-nsknavy" id="avgPerformance">
                <?= $avgAttendance ?>%
              </p>
              <p class="text-xs text-nskgold">Overall presence rate</p>
            </div>
          </div>

          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
            style="animation-delay: 0.3s"
          >
            <div class="bg-nskred p-4 rounded-full mr-4">
              <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Active Teachers</p>
              <p class="text-2xl font-bold text-nsknavy" id="onlineClasses">
                <?= $totalTeachers ?>
              </p>
              <p class="text-xs text-nskred">Teaching staff members</p>
            </div>
          </div>
        </div>

        <div
          class="bg-white rounded-xl shadow-md p-4 md:p-6 mb-6 md:mb-8"
        >
          <form method="GET" action="" class="space-y-4">
            <div
              class="flex flex-col md:flex-row md:items-center justify-between gap-4"
            >
              <h2 class="text-xl font-bold text-nsknavy mb-4 md:mb-0">
                Academic Management Hub
              </h2>

              <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 mb-3 sm:mb-0">
                  <select
                    class="px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm"
                    name="class_filter"
                  >
                    <option value="">All Grades</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= $classFilter == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>

                  <select
                    class="px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm"
                    name="department_filter"
                  >
                    <option value="">All Departments</option>
                    <?php foreach ($department_categories as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['category']) ?>" <?= $departmentFilter == $dept['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['category']) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="flex flex-wrap gap-2 action-buttons-mobile">
                  <button
                    type="submit"
                    class="flex-1 sm:flex-none bg-nskblue text-white px-3 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center justify-center text-sm"
                  >
                    <i class="fas fa-filter mr-2"></i> Filter
                  </button>
                  
                  <?php if (!empty($classFilter) || !empty($departmentFilter)): ?>
                      <a href="academics-management.php" class="flex-1 sm:flex-none bg-gray-500 text-white px-3 py-2 rounded-lg font-semibold hover:bg-gray-600 transition flex items-center justify-center text-sm">
                          <i class="fas fa-times mr-2"></i> Clear
                      </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 md:p-6 mb-6 md:mb-8">
          <div class="flex flex-wrap gap-2 mb-6 overflow-x-auto pb-2">
            <button
              class="tab-button px-3 py-2 rounded-lg border border-nskblue bg-nskblue text-white font-semibold active"
              data-tab="assignments"
            >
              <i class="fas fa-book mr-2"></i>Subject Assignments
            </button>
            <button
              class="tab-button px-3 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold"
              data-tab="subjects"
            >
              <i class="fas fa-book-open mr-2"></i>Subjects
            </button>
          </div>

          <div id="assignmentsTab" class="tab-content active">
            <div
              class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6"
            >
              <h3 class="text-lg font-bold text-nsknavy">
                Subject Assignments Management
              </h3>
              <div class="flex gap-3">
                <form method="POST" action="">
                    <button
                        type="submit"
                        name="show_add_form"
                        value="true"
                        class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center text-sm"
                    >
                        <i class="fas fa-plus mr-2"></i> Assign Subject
                    </button>
                </form>
              </div>
            </div>

            <div class="grid grid-cols-1">
              <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border overflow-hidden">
                  <div class="overflow-x-auto">
                    <table class="academics-table min-w-full">
                      <thead>
                        <tr class="bg-gray-50">
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Subject
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Grade / Class
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Teacher
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Students
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Status
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-center text-nsknavy font-semibold"
                          >
                            Actions
                          </th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200">
                        <?php if (empty($assignmentsData)): ?>
                            <tr>
                                <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                    <i class="fas fa-book-open text-4xl mb-4"></i>
                                    <p>No subject assignments found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignmentsData as $assignment): ?>
                            <tr>
                              <td class="px-4 md:px-6 py-4">
                                <div class="flex items-center">
                                  <div>
                                    <p class="font-semibold text-nsknavy">
                                      <?= htmlspecialchars($assignment['subject_name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                      <?= htmlspecialchars($assignment['category']) ?>
                                    </p>
                                  </div>
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4 table-grade">
                                <div class="grade-badge-container">
                                  <span
                                    class="bg-nskblue text-white px-3 py-1 rounded-full text-sm font-semibold"
                                    ><?= htmlspecialchars($assignment['class_name']) ?></span
                                  >
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4">
                                <div>
                                  <p class="font-medium"><?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></p>
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4">
                                <div class="flex items-center">
                                  <span class="font-semibold text-nsknavy"><?= $assignment['student_count'] ?></span>
                                  <span class="text-sm text-gray-600 ml-1"
                                    >enrolled</span
                                  >
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4">
                                <span
                                  class="status-badge bg-green-100 text-green-700"
                                  >Active</span
                                >
                              </td>
                              <td class="px-4 md:px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                  <button
                                    class="text-nskgold hover:text-amber-600 p-2 rounded-full hover:bg-amber-50 transition"
                                    title="Edit Assignment"
                                    onclick="editAssignment(<?= $assignment['class_subject_id'] ?>)"
                                  >
                                    <i class="fas fa-edit"></i>
                                  </button>
                                  <button
                                    class="text-nskred hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition"
                                    title="Delete Assignment"
                                    onclick="deleteAssignment(<?= $assignment['class_subject_id'] ?>)"
                                  >
                                    <i class="fas fa-trash"></i>
                                  </button>
                                </div>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div id="subjectsTab" class="tab-content hidden">
            <div
              class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6"
            >
              <h3 class="text-lg font-bold text-nsknavy">
                Subjects Management
              </h3>
              <div class="flex gap-3">
                <form method="POST" action="">
                    <button
                        type="submit"
                        name="show_create_subject_form"
                        value="true"
                        class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center text-sm"
                    >
                        <i class="fas fa-plus mr-2"></i> Create Subject
                    </button>
                </form>
              </div>
            </div>

            <div class="grid grid-cols-1">
              <div class="bg-white rounded-lg border overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="academics-table min-w-full">
                    <thead>
                      <tr class="bg-gray-50">
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Subject Name
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Category
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Description
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Created At
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-center text-nsknavy font-semibold"
                        >
                          Actions
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                      <?php if (empty($allSubjects)): ?>
                          <tr>
                              <td colspan="5" class="py-8 px-6 text-center text-gray-500">
                                  <i class="fas fa-book text-4xl mb-4"></i>
                                  <p>No subjects found. Create your first subject!</p>
                              </td>
                          </tr>
                      <?php else: ?>
                          <?php foreach ($allSubjects as $subject): ?>
                          <tr>
                            <td class="px-4 md:px-6 py-4">
                              <div class="flex items-center">
                                <div class="bg-nskblue p-2 rounded-lg mr-3">
                                  <i class="fas fa-book text-white"></i>
                                </div>
                                <div>
                                  <p class="font-semibold text-nsknavy">
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                  </p>
                                  <p class="text-xs text-gray-500">
                                    Code: <?= htmlspecialchars($subject['subject_code']) ?>
                                  </p>
                                </div>
                              </div>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                              <?php if (!empty($subject['category'])): ?>
                                <span class="bg-nskgold text-white px-3 py-1 rounded-full text-sm font-semibold">
                                  <?= htmlspecialchars($subject['category']) ?>
                                </span>
                              <?php else: ?>
                                <span class="text-gray-400 text-sm">No category</span>
                              <?php endif; ?>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                              <p class="text-gray-600 text-sm">
                                <?= !empty($subject['description']) ? htmlspecialchars(substr($subject['description'], 0, 50)) . (strlen($subject['description']) > 50 ? '...' : '') : 'No description' ?>
                              </p>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                              <p class="text-gray-600 text-sm">
                                <?= date('M d, Y', strtotime($subject['created_at'])) ?>
                              </p>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                              <div class="flex justify-center space-x-2">
                                <button
                                  class="text-nskgold hover:text-amber-600 p-2 rounded-full hover:bg-amber-50 transition"
                                  title="Edit Subject"
                                  onclick="editSubject(<?= $subject['id'] ?>)"
                                >
                                  <i class="fas fa-edit"></i>
                                </button>
                                <button
                                  class="text-nskred hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition"
                                  title="Delete Subject"
                                  onclick="deleteSubject(<?= $subject['id'] ?>)"
                                >
                                  <i class="fas fa-trash"></i>
                                </button>
                              </div>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Pagination for Subjects -->
          <?php if ($total_pages_subjects > 1): ?>
          <div class="flex flex-col sm:flex-row justify-between items-center mt-4 px-4">
              <div class="text-sm text-gray-600 mb-2 sm:mb-0">
                  Showing <?= $offset_subjects + 1 ?> to <?= min($offset_subjects + $items_per_page_subjects, $total_subjects_count) ?> of <?= $total_subjects_count ?> subjects
              </div>
              <div class="flex space-x-2">
                  <?php if ($current_page_subjects > 1): ?>
                      <a href="?tab=subjects&page_subjects=<?= $current_page_subjects - 1 ?>&class_filter=<?= $classFilter ?>&department_filter=<?= urlencode($departmentFilter) ?>" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Previous</a>
                  <?php endif; ?>
                  
                  <?php 
                  $start_page = max(1, $current_page_subjects - 2);
                  $end_page = min($total_pages_subjects, $current_page_subjects + 2);
                  
                  if ($start_page > 1) echo '<span class="px-2">...</span>';
                  
                  for ($i = $start_page; $i <= $end_page; $i++): 
                  ?>
                      <a href="?tab=subjects&page_subjects=<?= $i ?>&class_filter=<?= $classFilter ?>&department_filter=<?= urlencode($departmentFilter) ?>" class="px-3 py-1 border rounded text-sm <?= $i == $current_page_subjects ? 'bg-nskblue text-white' : 'hover:bg-gray-100' ?>"><?= $i ?></a>
                  <?php endfor; ?>
                  
                  <?php if ($end_page < $total_pages_subjects) echo '<span class="px-2">...</span>'; ?>
                  
                  <?php if ($current_page_subjects < $total_pages_subjects): ?>
                      <a href="?tab=subjects&page_subjects=<?= $current_page_subjects + 1 ?>&class_filter=<?= $classFilter ?>&department_filter=<?= urlencode($departmentFilter) ?>" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Next</a>
                  <?php endif; ?>
              </div>
          </div>
          <?php endif; ?>

          
        </div>
      </div>

      <?php if (isset($_POST['show_add_form'])): ?>
      <div id="assignSubjectModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Assign Subject to Class</h3>
            <a href="academics-management.php" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <div>
              <label class="block text-gray-700 mb-2">Class (Grade Level) <span class="text-red-500">*</span></label>
              <select name="class_id" id="assignClassSelect" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required onchange="filterSubjectsByClass(this.value)">
                <option value="">Select Class First</option>
                <?php foreach ($all_classes as $class): ?>
                    <option value="<?= $class['id'] ?>" data-level="<?= getClassLevel($class['class_name']) ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Select a class first to see matching subjects</p>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Subject <span class="text-red-500">*</span></label>
              <select name="subject_id" id="assignSubjectSelect" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">-- Select Class First --</option>
                <?php 
                // Group subjects by category for organized display
                $groupedSubjects = [];
                foreach ($all_subjects_list as $subject) {
                    $cat = $subject['category'] ?: 'Uncategorized';
                    $groupedSubjects[$cat][] = $subject;
                }
                ksort($groupedSubjects);
                foreach ($groupedSubjects as $category => $subjects): 
                ?>
                <optgroup label="<?= htmlspecialchars($category) ?>" class="subject-group" data-category="<?= htmlspecialchars($category) ?>">
                    <?php foreach ($subjects as $subject): 
                        // Determine level from code suffix
                        $code = $subject['subject_code'];
                        $level = 'unknown';
                        if (str_ends_with($code, '-PN') || str_ends_with($code, '-N')) $level = 'early';
                        elseif (str_ends_with($code, '-P')) $level = 'primary';
                        elseif (str_ends_with($code, '-JS')) $level = 'jss';
                        elseif (str_ends_with($code, '-SS')) $level = 'sss';
                    ?>
                        <option value="<?= $subject['id'] ?>" data-code="<?= htmlspecialchars($code) ?>" data-level="<?= $level ?>">
                            <?= htmlspecialchars($subject['subject_name']) ?> [<?= htmlspecialchars($code) ?>]
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
              <p class="text-xs text-gray-500 mt-1"><i class="fas fa-tag"></i> Code in brackets indicates the level (PN=Pre-Nursery, N=Nursery, P=Primary, JS=Junior Sec, SS=Senior Sec)</p>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Teacher</label>
              <select name="teacher_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Teacher</option>
                <?php foreach ($all_teachers as $teacher): ?>
                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
                <label class="block text-gray-700 mb-2">Type</label>
                <select name="is_compulsory" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                    <option value="1">Compulsory</option>
                    <option value="0">Elective</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition inline-block">Cancel</a>
                <button type="submit" name="assign_subject" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                Assign Subject
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($editAssignmentData): ?>
      <div id="editAssignmentModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Edit Subject Assignment</h3>
            <a href="academics-management.php" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="assignment_id" value="<?= $editAssignmentData['id'] ?>">
            
            <div>
              <label class="block text-gray-700 mb-2">Subject</label>
              <select name="subject_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Subject</option>
                <?php foreach ($all_subjects_list as $subject): ?>
                    <option value="<?= $subject['id'] ?>" <?= $editAssignmentData['subject_id'] == $subject['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Class (Grade Level)</label>
              <select name="class_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Class</option>
                <?php foreach ($all_classes as $class): ?>
                    <option value="<?= $class['id'] ?>" <?= $editAssignmentData['class_id'] == $class['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Teacher</label>
              <select name="teacher_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Teacher</option>
                <?php foreach ($all_teachers as $teacher): ?>
                    <option value="<?= $teacher['id'] ?>" <?= $editAssignmentData['teacher_id'] == $teacher['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
                <label class="block text-gray-700 mb-2">Type</label>
                <select name="is_compulsory" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                    <option value="1" <?= $editAssignmentData['is_compulsory'] == 1 ? 'selected' : '' ?>>Compulsory</option>
                    <option value="0" <?= $editAssignmentData['is_compulsory'] == 0 ? 'selected' : '' ?>>Elective</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" name="update_assignment" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                Update Assignment
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if (isset($_POST['show_create_subject_form'])): ?>
      <div id="createSubjectModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Create New Subject</h3>
            <a href="academics-management.php" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 mb-2">Subject Name <span class="text-red-500">*</span></label>
                <input type="text" name="subject_name" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" placeholder="Virtual Reality" required>
              </div>
              
              <div>
                <label class="block text-gray-700 mb-2">Subject Code <span class="text-gray-400 text-sm">(Optional)</span></label>
                <input type="text" name="subject_code" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" placeholder="VR-P" maxlength="15" style="text-transform: uppercase;">
                <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Use suffix: -PN (Pre-Nursery), -N (Nursery), -P (Primary), -JS (Junior), -SS (Senior)</p>
              </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <p class="text-sm text-blue-800"><i class="fas fa-lightbulb mr-2"></i><strong>Tip:</strong> To create level-specific subjects, use codes like:</p>
              <ul class="text-xs text-blue-700 mt-1 ml-6 list-disc">
                <li>VR-P for Virtual Reality (Primary)</li>
                <li>VR-JS for Virtual Reality (Junior Secondary)</li>
                <li>VR-SS for Virtual Reality (Senior Secondary)</li>
              </ul>
              <p class="text-xs text-blue-600 mt-2">Leave blank to auto-generate a code.</p>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Category (Subject Stream)</label>
              <select name="category" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                <option value="">Select Category (Optional)</option>
                <option value="Early Childhood">Early Childhood</option>
                <option value="Core">Core</option>
                <option value="Science">Science</option>
                <option value="Arts">Arts</option>
                <option value="Commercial">Commercial</option>
                <option value="Vocational">Vocational</option>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Description</label>
              <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" placeholder="Brief description of the subject"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php?tab=subjects" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition inline-block">Cancel</a>
                <button type="submit" name="create_subject" class="px-4 py-2 bg-nskgreen text-white rounded-lg font-semibold hover:bg-green-600 transition">
                  <i class="fas fa-plus mr-2"></i>Create Subject
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($editSubjectData): ?>
      <div id="editSubjectModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Edit Subject</h3>
            <a href="academics-management.php?tab=subjects" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="subject_id" value="<?= $editSubjectData['id'] ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 mb-2">Subject Name <span class="text-red-500">*</span></label>
                <input type="text" name="subject_name" value="<?= htmlspecialchars($editSubjectData['subject_name']) ?>" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
              </div>
              
              <div>
                <label class="block text-gray-700 mb-2">Subject Code <span class="text-red-500">*</span></label>
                <input type="text" name="subject_code" value="<?= htmlspecialchars($editSubjectData['subject_code'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" style="text-transform: uppercase;" required maxlength="15">
                <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle"></i> Use suffix: -PN, -N, -P, -JS, -SS for level</p>
              </div>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Category (Subject Stream)</label>
              <select name="category" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                <option value="">Select Category (Optional)</option>
                <option value="Early Childhood" <?= ($editSubjectData['category'] ?? '') == 'Early Childhood' ? 'selected' : '' ?>>Early Childhood</option>
                <option value="Core" <?= ($editSubjectData['category'] ?? '') == 'Core' ? 'selected' : '' ?>>Core</option>
                <option value="Science" <?= ($editSubjectData['category'] ?? '') == 'Science' ? 'selected' : '' ?>>Science</option>
                <option value="Arts" <?= ($editSubjectData['category'] ?? '') == 'Arts' ? 'selected' : '' ?>>Arts</option>
                <option value="Commercial" <?= ($editSubjectData['category'] ?? '') == 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                <option value="Vocational" <?= ($editSubjectData['category'] ?? '') == 'Vocational' ? 'selected' : '' ?>>Vocational</option>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Description</label>
              <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"><?= htmlspecialchars($editSubjectData['description'] ?? '') ?></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php?tab=subjects" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" name="update_subject" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                  <i class="fas fa-save mr-2"></i>Update Subject
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

    
        <?php require_once 'footer.php'; ?>
    </main>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Tab switching functionality
      document.querySelectorAll(".tab-button").forEach((button) => {
        button.addEventListener("click", function () {
          const tabName = this.getAttribute("data-tab");

          // Update tab buttons
          document.querySelectorAll(".tab-button").forEach((btn) => {
            btn.classList.remove("active", "bg-nskblue", "text-white", "border-nskblue");
            btn.classList.add("border-gray-300", "text-gray-700");
          });

          this.classList.add("active", "bg-nskblue", "text-white", "border-nskblue");
          this.classList.remove("border-gray-300", "text-gray-700");

          // Update tab content
          document.querySelectorAll(".tab-content").forEach((content) => {
            content.classList.add("hidden");
            content.classList.remove("active");
          });

          const targetContent = document.getElementById(tabName + "Tab");
          if (targetContent) {
            targetContent.classList.remove("hidden");
            targetContent.classList.add("active");
          }
        });
      });
      
      // Check URL for tab parameter and activate the correct tab
      const urlParams = new URLSearchParams(window.location.search);
      const activeTabParam = urlParams.get('tab');
      
      if (activeTabParam) {
        // Find and click the tab button with matching data-tab attribute
        const tabButton = document.querySelector(`.tab-button[data-tab="${activeTabParam}"]`);
        if (tabButton) {
          tabButton.click();
        } else {
          // If tab not found, activate first tab
          const firstTab = document.querySelector(".tab-button");
          if (firstTab) firstTab.click();
        }
      } else {
        // Set the first tab as active by default
        const firstTab = document.querySelector(".tab-button");
        if (firstTab) {
          firstTab.click();
        }
      }

      // Close modals with Escape key
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
          closeAllModals();
        }
      });
      
      // Close modal on outside click
      document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("click", function (e) {
          if (e.target === this) {
            closeAllModals();
          }
        });
      });
      
      // Sidebar toggle
      document.querySelector(".sidebar-toggle")?.addEventListener("click", function () {
        document.querySelector(".sidebar").classList.toggle("mobile-show");
      });
    });

    function closeAllModals() {
        // This will redirect and close any open modals
        window.location.href = 'academics-management.php';
    }
    
    // === ACTION FUNCTIONS ===

    function editAssignment(assignmentId) {
        window.location.href = `academics-management.php?edit=${assignmentId}`;
    }

    function deleteAssignment(assignmentId) {
        Swal.fire({
            title: 'Delete Assignment?',
            text: 'Are you sure you want to delete this subject assignment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'academics-management.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'assignment_id';
            idInput.value = assignmentId;
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_assignment';
            deleteInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

    function editSubject(subjectId) {
        window.location.href = `academics-management.php?tab=subjects&edit=${subjectId}`;
    }

    function deleteSubject(subjectId) {
        Swal.fire({
            title: 'Delete Subject?',
            text: "Are you sure you want to delete this subject? This will deactivate it and it won't be available for new assignments.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'academics-management.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'subject_id';
            idInput.value = subjectId;
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_subject';
            deleteInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

    // Smart subject filtering based on class level
    function filterSubjectsByClass(classId) {
        const classSelect = document.getElementById('assignClassSelect');
        const subjectSelect = document.getElementById('assignSubjectSelect');
        
        if (!classSelect || !subjectSelect) return;
        
        const selectedOption = classSelect.options[classSelect.selectedIndex];
        const classLevel = selectedOption?.dataset?.level || 'unknown';
        
        // Level mapping: which subject levels are compatible with which class levels
        const levelCompatibility = {
            'early': ['early'],
            'primary': ['primary'],
            'jss': ['jss'],
            'sss': ['sss'],
            'unknown': ['early', 'primary', 'jss', 'sss', 'unknown']
        };
        
        const allowedLevels = levelCompatibility[classLevel] || [];
        
        // Reset subject dropdown
        subjectSelect.value = '';
        
        // Show/hide options based on level
        const options = subjectSelect.querySelectorAll('option');
        let visibleCount = 0;
        
        options.forEach(option => {
            if (option.value === '') {
                // Update placeholder text
                if (classId) {
                    option.textContent = '-- Select a Subject --';
                } else {
                    option.textContent = '-- Select Class First --';
                }
                return;
            }
            
            const optionLevel = option.dataset?.level || 'unknown';
            
            if (!classId || allowedLevels.includes(optionLevel) || classLevel === 'unknown') {
                option.style.display = '';
                option.disabled = false;
                visibleCount++;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });
        
        // Also handle optgroups - hide empty ones
        const optgroups = subjectSelect.querySelectorAll('optgroup');
        optgroups.forEach(group => {
            const visibleOptions = group.querySelectorAll('option:not([disabled])');
            group.style.display = visibleOptions.length > 0 ? '' : 'none';
        });
        
        // Show info message
        console.log(`Class level: ${classLevel}, Showing ${visibleCount} subjects`);
    }

    // Initialize filtering on page load if modal is open
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('assignClassSelect');
        if (classSelect && classSelect.value) {
            filterSubjectsByClass(classSelect.value);
        }
        
        // Setup AJAX for filtering
        setupAjaxFilters();
    });
    
    // === AJAX FUNCTIONALITY ===
    
    function setupAjaxFilters() {
        const filterForm = document.querySelector('form[method="GET"]');
        if (!filterForm) return;
        
        // Intercept filter form submission
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const classFilter = formData.get('class_filter') || '';
            const departmentFilter = formData.get('department_filter') || '';
            
            // Get current active tab
            const activeTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'assignments';
            
            if (activeTab === 'assignments') {
                loadAssignments(1, classFilter, departmentFilter);
            } else if (activeTab === 'subjects') {
                loadSubjects(1, classFilter, departmentFilter);
            }
        });
    }
    
    function loadAssignments(page = 1, classFilter = '', departmentFilter = '') {
        const tbody = document.querySelector('#assignmentsTab tbody');
        if (!tbody) return;
        
        // Show loading state
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-4 border-nskblue border-t-transparent mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></td></tr>';
        
        // Build URL
        const url = `academics-management.php?ajax=1&tab=assignments&page=${page}&class_filter=${classFilter}&department_filter=${encodeURIComponent(departmentFilter)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    renderAssignmentsTable(result.data);
                    renderAssignmentsPagination(result.pagination, classFilter, departmentFilter);
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-600">Error loading data</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-600">Error loading data</td></tr>';
            });
    }
    
    function renderAssignmentsTable(data) {
        const tbody = document.querySelector('#assignmentsTab tbody');
        if (!tbody) return;
        
        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                        <i class="fas fa-book-open text-4xl mb-4"></i>
                        <p>No subject assignments found.</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        data.forEach(row => {
            html += `
                <tr>
                    <td class="px-4 md:px-6 py-4">
                        <div class="flex items-center">
                            <div>
                                <p class="font-semibold text-nsknavy">${escapeHtml(row.subject_name)}</p>
                                <p class="text-sm text-gray-600">${escapeHtml(row.category)}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-4 table-grade">
                        <div class="grade-badge-container">
                            <span class="bg-nskblue text-white px-3 py-1 rounded-full text-sm font-semibold">${escapeHtml(row.class_name)}</span>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-4">
                        <div>
                            <p class="font-medium">${escapeHtml(row.first_name + ' ' + row.last_name)}</p>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-4">
                        <div class="flex items-center">
                            <span class="font-semibold text-nsknavy">${row.student_count}</span>
                            <span class="text-sm text-gray-600 ml-1">enrolled</span>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-4">
                        <span class="status-badge bg-green-100 text-green-700">Active</span>
                    </td>
                    <td class="px-4 md:px-6 py-4 text-center">
                        <div class="flex justify-center space-x-2">
                            <button class="text-nskgold hover:text-amber-600 p-2 rounded-full hover:bg-amber-50 transition" title="Edit Assignment" onclick="editAssignment(${row.class_subject_id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-nskred hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition" title="Delete Assignment" onclick="deleteAssignment(${row.class_subject_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }
    
    function renderAssignmentsPagination(pagination, classFilter, departmentFilter) {
        // This would render pagination if needed
        // For now keeping the existing pagination system
    }
    
    function loadSubjects(page = 1, classFilter = '', departmentFilter = '') {
        const tbody = document.querySelector('#subjectsTab tbody');
        if (!tbody) return;
        
        // Show loading state
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-4 border-nskblue border-t-transparent mx-auto"></div><p class="mt-2 text-gray-600">Loading...</p></td></tr>';
        
        // Build URL
        const url = `academics-management.php?ajax=1&tab=subjects&page_subjects=${page}&class_filter=${classFilter}&department_filter=${encodeURIComponent(departmentFilter)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    renderSubjectsTable(result.data);
                    renderSubjectsPagination(result.pagination, classFilter, departmentFilter);
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-600">Error loading data</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-600">Error loading data</td></tr>';
            });
    }
    
    function renderSubjectsTable(data) {
        const tbody = document.querySelector('#subjectsTab tbody');
        if (!tbody) return;
        
        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-8 px-6 text-center text-gray-500">
                        <i class="fas fa-book text-4xl mb-4"></i>
                        <p>No subjects found. Create your first subject!</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        data.forEach(subject => {
            const description = subject.description && subject.description.length > 50 
                ? escapeHtml(subject.description.substring(0, 50)) + '...' 
                : escapeHtml(subject.description || 'No description');
            
            const category = subject.category 
                ? `<span class="bg-nskgold text-white px-3 py-1 rounded-full text-sm font-semibold">${escapeHtml(subject.category)}</span>`
                : `<span class="text-gray-400 text-sm">No category</span>`;
            
            const createdDate = new Date(subject.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            html += `
                <tr>
                    <td class="px-4 md:px-6 py-4">
                        <div class="flex items-center">
                            <div class="bg-nskblue p-2 rounded-lg mr-3">
                                <i class="fas fa-book text-white"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-nsknavy">${escapeHtml(subject.subject_name)}</p>
                                <p class="text-xs text-gray-500">Code: ${escapeHtml(subject.subject_code)}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 md:px-6 py-4">${category}</td>
                    <td class="px-4 md:px-6 py-4">
                        <p class="text-gray-600 text-sm">${description}</p>
                    </td>
                    <td class="px-4 md:px-6 py-4">
                        <p class="text-gray-600 text-sm">${createdDate}</p>
                    </td>
                    <td class="px-4 md:px-6 py-4 text-center">
                        <div class="flex justify-center space-x-2">
                            <button class="text-nskgold hover:text-amber-600 p-2 rounded-full hover:bg-amber-50 transition" title="Edit Subject" onclick="editSubject(${subject.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-nskred hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition" title="Delete Subject" onclick="deleteSubject(${subject.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
    }
    
    function renderSubjectsPagination(pagination, classFilter, departmentFilter) {
        // This would render pagination if needed  
        // For now keeping the existing pagination system
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
  </script>
  
  <!-- Professional AJAX Filter System -->
  <script src="academics_ajax_filter.js"></script>
  
</body>
</html>
