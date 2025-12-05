<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include required files
require_once '../config/database.php';
require_once '../config/logger.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// === INPUT STANDARDIZATION FUNCTIONS ===

/**
 * Standardize text input to Title Case
 * Converts: "primary 1" or "PRIMARY 1" => "Primary 1"
 */
function standardizeTitleCase($text)
{
    if (empty($text))
        return $text;

    // Trim whitespace
    $text = trim($text);

    // Convert to lowercase first
    $text = mb_strtolower($text, 'UTF-8');

    // Convert to Title Case (uppercase first letter of each word)
    $text = mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');

    return $text;
}

/**
 * Standardize text input to UPPERCASE
 * Converts: "jss1" or "Jss1" => "JSS1"
 */
function standardizeUpperCase($text)
{
    if (empty($text))
        return $text;

    // Trim whitespace and convert to uppercase
    return mb_strtoupper(trim($text), 'UTF-8');
}

/**
 * Standardize class level input
 * Accepts variations and returns proper format
 */
function standardizeClassLevel($level)
{
    if (empty($level))
        return 'Secondary'; // Default

    $level = mb_strtolower(trim($level), 'UTF-8');

    // Map common variations to standard values
    $levelMap = [
        'early childhood' => 'Early Childhood',
        'earlychildhood' => 'Early Childhood',
        'nursery' => 'Early Childhood',
        'pre-primary' => 'Early Childhood',

        'primary' => 'Primary',
        'elementary' => 'Primary',

        'secondary' => 'Secondary',
        'high school' => 'Secondary',
        'highschool' => 'Secondary',
        'jss' => 'Secondary',
        'sss' => 'Secondary',
    ];

    return $levelMap[$level] ?? 'Secondary';
}

// === HELPER FUNCTIONS ===

function addClass($db)
{
    try {
        if (empty($_POST['class_name']) || empty($_POST['class_code'])) {
            throw new Exception("Class name and code are required.");
        }

        // Standardize inputs
        $className = standardizeTitleCase($_POST['class_name']);
        $classCode = standardizeUpperCase($_POST['class_code']);
        $classLevel = standardizeClassLevel($_POST['class_level'] ?? 'Secondary');

        // Check if class code already exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM classes WHERE class_code = ?");
        $checkStmt->execute([$classCode]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Class code already exists.");
        }

        $sql = "INSERT INTO classes (class_name, class_code, class_teacher_id, capacity, class_level) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $className,
            $classCode,
            !empty($_POST['class_teacher_id']) ? $_POST['class_teacher_id'] : null,
            $_POST['capacity'] ?? 30,
            $classLevel
        ]);

        $classId = $db->lastInsertId();

        // Log activity
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "New Class",
            "Created class: {$className} ({$classCode})",
            "fas fa-chalkboard-teacher",
            "bg-nskgreen"
        );

        return ['success' => true, 'message' => 'Class added successfully!', 'class_id' => $classId];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function updateClass($db)
{
    try {
        if (empty($_POST['class_id']) || empty($_POST['class_name'])) {
            throw new Exception("Class ID and name are required.");
        }

        // Standardize inputs
        $className = standardizeTitleCase($_POST['class_name']);
        $classLevel = standardizeClassLevel($_POST['class_level'] ?? 'Secondary');

        $sql = "UPDATE classes SET class_name = ?, class_teacher_id = ?, capacity = ?, class_level = ? 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $className,
            !empty($_POST['class_teacher_id']) ? $_POST['class_teacher_id'] : null,
            $_POST['capacity'] ?? 30,
            $classLevel,
            $_POST['class_id']
        ]);

        // Log activity
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Class Updated",
            "Updated class: {$className}",
            "fas fa-edit",
            "bg-nskgold"
        );

        return ['success' => true, 'message' => 'Class updated successfully!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function assignTeacher($db)
{
    try {
        if (empty($_POST['class_id']) || empty($_POST['teacher_id'])) {
            throw new Exception("Class ID and Teacher ID are required.");
        }

        // Get class name for logging
        $classStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
        $classStmt->execute([$_POST['class_id']]);
        $className = $classStmt->fetchColumn();

        // Get teacher name for logging
        $teacherStmt = $db->prepare("SELECT u.first_name, u.last_name FROM users u 
                                      JOIN teachers t ON u.id = t.user_id 
                                      WHERE t.id = ?");
        $teacherStmt->execute([$_POST['teacher_id']]);
        $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
        $teacherName = $teacher ? $teacher['first_name'] . ' ' . $teacher['last_name'] : 'Unknown';

        $sql = "UPDATE classes SET class_teacher_id = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_POST['teacher_id'], $_POST['class_id']]);

        // Log activity
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Teacher Assigned",
            "Assigned $teacherName to class: $className",
            "fas fa-user-tie",
            "bg-nskblue"
        );

        return ['success' => true, 'message' => 'Teacher assigned successfully!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getClassDetails($db, $classId)
{
    try {
        $sql = "SELECT c.*, 
                       u.first_name as teacher_first_name, 
                       u.last_name as teacher_last_name,
                       u.email as teacher_email,
                       u.phone as teacher_phone,
                       t.teacher_id as teacher_code,
                       (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
                       (SELECT COUNT(*) FROM class_subjects WHERE class_id = c.id) as subject_count
                FROM classes c
                LEFT JOIN teachers t ON c.class_teacher_id = t.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE c.id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            throw new Exception("Class not found.");
        }

        // Get subjects for this class
        $subjectsSql = "SELECT s.subject_name, s.subject_code, 
                               u.first_name as teacher_first_name, 
                               u.last_name as teacher_last_name
                        FROM class_subjects cs
                        JOIN subjects s ON cs.subject_id = s.id
                        LEFT JOIN teachers t ON cs.teacher_id = t.id
                        LEFT JOIN users u ON t.user_id = u.id
                        WHERE cs.class_id = ?";
        $subjectsStmt = $db->prepare($subjectsSql);
        $subjectsStmt->execute([$classId]);
        $class['subjects'] = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get students for this class
        $studentsSql = "SELECT u.first_name, u.last_name, s.student_id, s.admission_number
                        FROM students s
                        JOIN users u ON s.user_id = u.id
                        WHERE s.class_id = ? AND u.is_active = 1
                        LIMIT 10";
        $studentsStmt = $db->prepare($studentsSql);
        $studentsStmt->execute([$classId]);
        $class['students'] = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $class];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getAllClasses($db)
{
    try {
        $sql = "SELECT c.*, 
                       u.first_name as teacher_first_name, 
                       u.last_name as teacher_last_name,
                       t.teacher_id as teacher_code,
                       (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
                       (SELECT COUNT(*) FROM class_subjects WHERE class_id = c.id) as subject_count
                FROM classes c
                LEFT JOIN teachers t ON c.class_teacher_id = t.id
                LEFT JOIN users u ON t.user_id = u.id
                ORDER BY c.class_level, c.class_name";

        $stmt = $db->query($sql);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $classes];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getActiveTeachers($db)
{
    try {
        $sql = "SELECT t.id, t.teacher_id, u.first_name, u.last_name, u.email,
                       tp.subject_specialization
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
                WHERE u.is_active = 1
                ORDER BY u.first_name, u.last_name";

        $stmt = $db->query($sql);
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $teachers];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getTimetable($db, $classId)
{
    try {
        if (empty($classId)) {
            throw new Exception("Class ID is required.");
        }

        $sql = "SELECT t.*, 
                       s.subject_name,
                       u.first_name as teacher_first_name,
                       u.last_name as teacher_last_name
                FROM timetable t
                LEFT JOIN subjects s ON t.subject_id = s.id
                LEFT JOIN teachers tr ON t.teacher_id = tr.id
                LEFT JOIN users u ON tr.user_id = u.id
                WHERE t.class_id = ?
                ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), t.start_time";

        $stmt = $db->prepare($sql);
        $stmt->execute([$classId]);
        $timetable = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'data' => $timetable];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function deleteClass($db)
{
    try {
        if (empty($_POST['class_id'])) {
            throw new Exception("Class ID is required.");
        }

        $class_id = $_POST['class_id'];

        // Get class name for logging
        $classStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
        $classStmt->execute([$class_id]);
        $className = $classStmt->fetchColumn();

        if (!$className) {
            throw new Exception("Class not found.");
        }

        // Check if class has students
        $studentCheck = $db->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
        $studentCheck->execute([$class_id]);
        $studentCount = $studentCheck->fetchColumn();

        if ($studentCount > 0) {
            throw new Exception("Cannot delete class with enrolled students. Please reassign or remove students first.");
        }

        // Start transaction for data integrity
        $db->beginTransaction();

        try {
            // Delete class subjects first
            $deleteSubjectsStmt = $db->prepare("DELETE FROM class_subjects WHERE class_id = ?");
            $deleteSubjectsStmt->execute([$class_id]);

            // Delete timetable entries
            $deleteTimetableStmt = $db->prepare("DELETE FROM timetable WHERE class_id = ?");
            $deleteTimetableStmt->execute([$class_id]);

            // Delete the class
            $deleteClassStmt = $db->prepare("DELETE FROM classes WHERE id = ?");
            $deleteClassStmt->execute([$class_id]);

            // Commit transaction
            $db->commit();

            // Log activity
            $admin_name = $_SESSION['user_name'] ?? 'Admin';
            logActivity(
                $db,
                $admin_name,
                "Class Deleted",
                "Deleted class: $className",
                "fas fa-trash",
                "bg-nskred"
            );

            return ['success' => true, 'message' => 'Class deleted successfully!'];
        } catch (Exception $e) {
            // Rollback on error
            $db->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// === REQUEST HANDLERS ===

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_class':
            echo json_encode(addClass($db));
            break;
        case 'update_class':
            echo json_encode(updateClass($db));
            break;
        case 'assign_teacher':
            echo json_encode(assignTeacher($db));
            break;
        case 'delete_class':
            echo json_encode(deleteClass($db));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_class_details':
            $classId = $_GET['class_id'] ?? 0;
            echo json_encode(getClassDetails($db, $classId));
            break;
        case 'get_all_classes':
            echo json_encode(getAllClasses($db));
            break;
        case 'get_teachers':
            echo json_encode(getActiveTeachers($db));
            break;
        case 'get_timetable':
            $classId = $_GET['class_id'] ?? 0;
            echo json_encode(getTimetable($db, $classId));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>