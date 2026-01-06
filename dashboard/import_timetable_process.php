<?php
require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/TimetableHelper.php';

checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
    exit;
}

$file = $_FILES['csvFile']['tmp_name'];
$handle = fopen($file, 'r');

if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Could not open file']);
    exit;
}

// Read headers
$headers = fgetcsv($handle);
// Expected: ['Class Name', 'Day', 'Start Time (HH:MM)', 'End Time (HH:MM)', 'Subject', 'Teacher Email', 'Room']

$db = new Database();
$conn = $db->getConnection();

$successCount = 0;
$errors = [];

// Get current session and term
try {
    $stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    $sessionId = $stmt->fetchColumn();
    $stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
    $termId = $stmt->fetchColumn();

    if (!$sessionId || !$termId) {
        echo json_encode(['success' => false, 'message' => 'Current session or term not active.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Cache for lookups
$classes = [];
$teachers = [];
$subjects = [];

$rowIdx = 1;
while (($row = fgetcsv($handle)) !== false) {
    $rowIdx++;
    // Skip empty rows
    if (empty(implode('', $row)))
        continue;

    // Extract columns (safely)
    $className = isset($row[0]) ? trim($row[0]) : '';
    $day = isset($row[1]) ? trim($row[1]) : '';
    $start = isset($row[2]) ? trim($row[2]) : '';
    $end = isset($row[3]) ? trim($row[3]) : '';
    $subjectName = isset($row[4]) ? trim($row[4]) : '';
    $teacherEmail = isset($row[5]) ? trim($row[5]) : '';
    $room = isset($row[6]) ? trim($row[6]) : '';

    if (empty($className) || empty($day) || empty($start) || empty($end) || empty($subjectName)) {
        $errors[] = "Row $rowIdx: Missing required fields";
        continue;
    }

    // 1. Resolve Class
    if (!isset($classes[$className])) {
        $stmt = $conn->prepare("SELECT id, class_level FROM classes WHERE class_name = ?");
        $stmt->execute([$className]);
        $cinfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cinfo)
            $classes[$className] = $cinfo;
        else {
            $errors[] = "Row $rowIdx: Class '$className' not found";
            continue;
        }
    }
    $classId = $classes[$className]['id'];
    $classLevel = $classes[$className]['class_level'];
    
    // Time Validation
    $rules = TimetableHelper::getRules($classLevel, $className);
    if (strtotime($start) < strtotime($rules['start_time']) || strtotime($end) > strtotime($rules['end_time'])) {
        $errors[] = "Row $rowIdx: Time $start-$end is outside school hours for $className ({$rules['start_time']}-{$rules['end_time']})";
        // We continue anyway but mark as warning in a real system, here we enforce rules
        continue;
    }

    // 2. Resolve Subject
    $subjectId = null;
    $isDummy = false;
    
    if (!isset($subjects[$subjectName])) {
        // Exact match first
        $stmt = $conn->prepare("SELECT id, is_dummy FROM subjects WHERE subject_name = ? LIMIT 1");
        $stmt->execute([$subjectName]);
        $sinfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sinfo) {
            $subjects[$subjectName] = $sinfo;
        } else {
            // Case-insensitive Check for Break
            if (stripos($subjectName, 'Break') !== false) {
                 $stmt = $conn->query("SELECT id, is_dummy FROM subjects WHERE subject_name LIKE '%Break%' LIMIT 1");
                 $sinfo = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // If still not found, use a dummy "Free Period" or similar
            if (!$sinfo) {
                $dummyId = TimetableHelper::getRandomDummySubjectId($conn);
                if ($dummyId) {
                    $stmt = $conn->prepare("SELECT id, is_dummy FROM subjects WHERE id = ?");
                    $stmt->execute([$dummyId]);
                    $sinfo = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if ($sinfo) {
                $subjects[$subjectName] = $sinfo;
            } else {
                $errors[] = "Row $rowIdx: Subject '$subjectName' not found and no dummy available";
                continue;
            }
        }
    }
    $subjectId = $subjects[$subjectName]['id'];
    $isDummy = $subjects[$subjectName]['is_dummy'];

    // 3. Resolve Teacher
    if (!isset($teachers[$teacherEmail])) {
        if (empty($teacherEmail)) {
            // If subject is Break or Dummy, assign a placeholder teacher to satisfy FK
            if (stripos($subjectName, 'Break') !== false || $isDummy) {
                $stmt = $conn->query("SELECT id FROM teachers LIMIT 1");
                $tid = $stmt->fetchColumn();

                if ($tid) {
                    $teachers[$teacherEmail] = $tid;
                } else {
                    $errors[] = "Row $rowIdx: No teachers available in system to assign to Break/Dummy";
                    continue;
                }
            } else {
                $errors[] = "Row $rowIdx: Teacher email required for non-break/non-dummy subjects";
                continue;
            }
        } else {
            $stmt = $conn->prepare("SELECT t.id FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.email = ?");
            $stmt->execute([$teacherEmail]);
            $tid = $stmt->fetchColumn();
            if ($tid)
                $teachers[$teacherEmail] = $tid;
            else {
                $errors[] = "Row $rowIdx: Teacher with email '$teacherEmail' not found";
                continue;
            }
        }
    }
    $teacherId = $teachers[$teacherEmail];

    // 4. Insert
    try {
        $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room, academic_session_id, term_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$classId, $subjectId, $teacherId, $day, $start, $end, $room, $sessionId, $termId]);
        $successCount++;
    } catch (Exception $e) {
        $errors[] = "Row $rowIdx: Error - " . $e->getMessage();
    }
}

fclose($handle);

echo json_encode([
    'success' => true,
    'imported_count' => $successCount,
    'errors' => $errors,
    'message' => "Imported $successCount records. " . (count($errors) > 0 ? "Some errors occurred." : "")
]);
?>