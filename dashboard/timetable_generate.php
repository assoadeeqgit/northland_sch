<?php
/**
 * timetable_generate.php
 * Automatically populates a class timetable based on its section rules.
 * It fills unassigned slots with dummy subjects.
 */
// require_once 'auth-check.php';
require_once __DIR__ . '/../includes/TimetableHelper.php';
require_once __DIR__ . '/../config/database.php';

// checkAuth();
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

    // 3. Get Relevant Valid Assignments for this Class from class_subjects
    // This ensures we only use officially assigned teachers for each subject
    $stmt = $conn->prepare("
        SELECT cs.subject_id, cs.teacher_id
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id = s.id
        WHERE cs.class_id = ? 
        AND s.is_active = 1 
    ");
    $stmt->execute([$classId]);
    $validAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($validAssignments)) {
        throw new Exception("No subjects/teachers assigned to this class in Academics Management. Please assign subjects first.");
    }

    // 4. For each day and period, check if slot exists, if not, insert a random valid assignment
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $insertedCount = 0;

    foreach ($days as $day) {
        foreach ($periods as $period) {
            if (isset($period['is_break']) && $period['is_break']) continue;

            $startTime = $period['start'] . ':00';
            $endTime = $period['end'] . ':00';

            // Check if slot exists
            $stmt = $conn->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND start_time = ? AND academic_session_id = ? AND term_id = ?");
            $stmt->execute([$classId, $day, $startTime, $sessionId, $termId]);
            
            if (!$stmt->fetch()) {
                // Pick a random valid assignment pair
                $assignment = $validAssignments[array_rand($validAssignments)];
                
                // Double check for teacher conflict at this specific time
                $conflictCheck = $conn->prepare("SELECT id FROM timetable WHERE teacher_id = ? AND day_of_week = ? AND start_time = ? AND academic_session_id = ? AND term_id = ?");
                $conflictCheck->execute([$assignment['teacher_id'], $day, $startTime, $sessionId, $termId]);
                
                if (!$conflictCheck->fetch()) {
                    // No conflict, safe to insert
                    $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, academic_session_id, term_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $classId,
                        $assignment['subject_id'],
                        $assignment['teacher_id'],
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
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully generated timetable. Filled $insertedCount slots with valid subject assignments.",
        'inserted_count' => $insertedCount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

