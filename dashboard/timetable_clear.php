<?php
/**
 * timetable_clear.php
 * Clears the timetable for a specific class.
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
$classId = (int)($input['class_id'] ?? 0);

if (!$classId) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Get current session and term
    $stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    $sessionId = $stmt->fetchColumn();
    $stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
    $termId = $stmt->fetchColumn();

    if (!$sessionId || !$termId) {
        throw new Exception("No active session or term found.");
    }

    $stmt = $conn->prepare("DELETE FROM timetable WHERE class_id = ? AND academic_session_id = ? AND term_id = ?");
    $stmt->execute([$classId, $sessionId, $termId]);

    echo json_encode(['success' => true, 'message' => 'Timetable cleared for current term.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
