<?php
/**
 * timetable_save.php
 * Saves a single timetable entry with conflict detection.
 */
require_once 'auth-check.php';
require_once __DIR__ . '/../config/database.php';

checkAuth();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$class_id = (int)($_POST['class_id'] ?? 0);
$subject_id = (int)($_POST['subject_id'] ?? 0);
$teacher_id = (int)($_POST['teacher_id'] ?? 0);
$day = $_POST['day'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$room = $_POST['room'] ?? '';

if (!$class_id || !$subject_id || !$teacher_id || !$day || !$start_time || !$end_time) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Get current session and term
    $stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    $sessionId = $stmt->fetchColumn();
    $stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
    $termId = $stmt->fetchColumn();

    if (!$sessionId || !$termId) {
        throw new Exception("No active session or term found.");
    }

    // Conflict Detection: Check if class already has a lesson at this time
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND academic_session_id = ? AND term_id = ? AND (
        (start_time BETWEEN ? AND ?) OR 
        (end_time BETWEEN ? AND ?) OR 
        (? BETWEEN start_time AND end_time)
    )");
    $stmt->execute([$class_id, $day, $sessionId, $termId, $start_time, $end_time, $start_time, $end_time, $start_time]);
    if ($stmt->fetch()) {
        throw new Exception("Conflict detected: This class already has a scheduled activity during this time.");
    }

    // Conflict Detection: Check if teacher is busy
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE teacher_id = ? AND day_of_week = ? AND academic_session_id = ? AND term_id = ? AND (
        (start_time BETWEEN ? AND ?) OR 
        (end_time BETWEEN ? AND ?) OR 
        (? BETWEEN start_time AND end_time)
    )");
    $stmt->execute([$teacher_id, $day, $sessionId, $termId, $start_time, $end_time, $start_time, $end_time, $start_time]);
    if ($stmt->fetch()) {
        throw new Exception("Conflict detected: Teacher is already assigned to another class at this time.");
    }

    // Insert
    $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room, academic_session_id, term_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$class_id, $subject_id, $teacher_id, $day, $start_time, $end_time, $room, $sessionId, $termId]);

    echo json_encode(['success' => true, 'message' => 'Schedule added successfully!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
