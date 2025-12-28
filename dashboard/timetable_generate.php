<?php
/**
 * timetable_generate.php
 * Automatically populates a class timetable based on its section rules.
 * It fills unassigned slots with dummy subjects.
 */
require_once 'auth-check.php';
require_once __DIR__ . '/../includes/TimetableHelper.php';
require_once __DIR__ . '/../config/database.php';

checkAuth();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input && !empty($_POST)) $input = $_POST;

$classId = (int)($input['class_id'] ?? 0);

if (!$classId) {
    error_log("Timetable Auto-Generate Error: Class ID is missing. Input: " . json_encode($input));
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // 1. Get Class Info
    $stmt = $conn->prepare("SELECT class_name, class_level FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$classInfo) {
        throw new Exception("Class not found");
    }

    $rules = TimetableHelper::getRules($classInfo['class_level'], $classInfo['class_name']);
    $periods = TimetableHelper::generatePeriods($rules);
    
    // 2. Get Academic Context
    $stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    $sessionId = $stmt->fetchColumn();
    $stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
    $termId = $stmt->fetchColumn();

    if (!$sessionId || !$termId) {
        throw new Exception("No active session or term found.");
    }

    // 3. Get Relevant Subjects for this Class Level
    $categories = [];
    if ($classInfo['class_level'] === 'Early Childhood') {
        $categories = ["'Early Childhood'"];
    } elseif ($classInfo['class_level'] === 'Primary') {
        $categories = ["'Core'", "'Vocational'", "'Extra-curricular'"];
    } else {
        // Secondary
        $categories = ["'Core'", "'Science'", "'Commercial'", "'Arts'", "'Vocational'"];
    }

    $catString = implode(',', $categories);
    $stmt = $conn->query("SELECT id FROM subjects WHERE category IN ($catString) AND is_active = 1 AND is_dummy = 0");
    $relevantSubjectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($relevantSubjectIds)) {
        // Fallback to dummy subjects if no curriculum subjects found
        $relevantSubjectIds = array_column(TimetableHelper::getDummySubjects($conn), 'id');
    }

    if (empty($relevantSubjectIds)) {
        throw new Exception("No suitable subjects found for this class level.");
    }

    // Get Placeholder Teacher
    $stmt = $conn->query("SELECT id FROM teachers LIMIT 1");
    $placeholderTeacherId = $stmt->fetchColumn();

    if (!$placeholderTeacherId) {
        throw new Exception("No teachers found to assign as placeholders.");
    }

    // 3.5 Clear existing dummy entries for this class/session/term so they can be replaced by real subjects
    $stmt = $conn->prepare("DELETE t FROM timetable t JOIN subjects s ON t.subject_id = s.id WHERE t.class_id = ? AND t.academic_session_id = ? AND t.term_id = ? AND s.is_dummy = 1");
    $stmt->execute([$classId, $sessionId, $termId]);

    // 4. For each day and period, check if slot exists, if not, insert a random relevant subject
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $insertedCount = 0;

    foreach ($days as $day) {
        foreach ($periods as $period) {
            if ($period['is_break']) continue;

            $startTime = $period['start'] . ':00';
            $endTime = $period['end'] . ':00';

            // Check if slot exists
            $stmt = $conn->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND start_time = ? AND academic_session_id = ? AND term_id = ?");
            $stmt->execute([$classId, $day, $startTime, $sessionId, $termId]);
            
            if (!$stmt->fetch()) {
                // Pick a random subject from relevant ones
                $randomSubjectId = $relevantSubjectIds[array_rand($relevantSubjectIds)];
                
                // Insert
                $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, academic_session_id, term_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $classId,
                    $randomSubjectId,
                    $placeholderTeacherId,
                    $day,
                    $startTime,
                    $endTime,
                    $sessionId,
                    $termId
                ]);
                $insertedCount++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully generated timetable with $insertedCount relevant subjects assigned to empty slots.",
        'inserted_count' => $insertedCount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

