<?php
/**
 * timetable_copy.php
 * Copies a class timetable to another class.
 */
require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';

checkAuth();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$sourceClassId = (int)($input['source_class_id'] ?? 0);
$targetClassId = (int)($input['target_class_id'] ?? 0);

if (!$sourceClassId || !$targetClassId) {
    echo json_encode(['success' => false, 'message' => 'Source and Target class IDs are required']);
    exit;
}

if ($sourceClassId === $targetClassId) {
    echo json_encode(['success' => false, 'message' => 'Source and Target classes must be different']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // 1. Get current session and term
    $stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    $sessionId = $stmt->fetchColumn();
    $stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
    $termId = $stmt->fetchColumn();

    if (!$sessionId || !$termId) {
        throw new Exception("No active session or term found.");
    }

    // 2. Clear target class timetable for the same session/term
    $stmt = $conn->prepare("DELETE FROM timetable WHERE class_id = ? AND academic_session_id = ? AND term_id = ?");
    $stmt->execute([$targetClassId, $sessionId, $termId]);

    // 3. Fetch source timetable
    $stmt = $conn->prepare("SELECT subject_id, teacher_id, day_of_week, start_time, end_time, room FROM timetable WHERE class_id = ? AND academic_session_id = ? AND term_id = ?");
    $stmt->execute([$sourceClassId, $sessionId, $termId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Insert into target class
    if (empty($items)) {
        throw new Exception("Source class has no timetable entries to copy.");
    }

    $insertStmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room, academic_session_id, term_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($items as $item) {
        $insertStmt->execute([
            $targetClassId,
            $item['subject_id'],
            $item['teacher_id'],
            $item['day_of_week'],
            $item['start_time'],
            $item['end_time'],
            $item['room'],
            $sessionId,
            $termId
        ]);
        $count++;
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => "Successfully copied $count entries to the target class."
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
