<?php
/**
 * timetable_save.php
 * Saves or Updates a single timetable entry with conflict detection.
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

$id = (int)($_POST['id'] ?? 0); // Optional ID for update
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
    // Exclude current ID if updating
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND academic_session_id = ? AND term_id = ? AND id != ? AND (
        (start_time BETWEEN ? AND ?) OR 
        (end_time BETWEEN ? AND ?) OR 
        (? BETWEEN start_time AND end_time)
    )");
    // Note: Use slightly adjusted times to allow abutment? 
    // Standard logic: (StartA < EndB) and (EndA > StartB). 
    // The previous logic used BETWEEN which is inclusive.
    // If Period 1 ends 08:30 and Period 2 starts 08:30.
    // 08:30 BETWEEN 08:30 AND 09:00 is TRUE.
    // This causes false conflict for adjacent periods.
    // Fix: Use < and > for strict overlap.
    // Overlap if: (Start1 < End2) AND (End1 > Start2)
    // SQL: start_time < ? AND end_time > ?
    
    // However, keeping consistent with previous logic implies previous logic might have had bugs or handled edges.
    // Let's stick to the previous BETWEEN logic BUT check for exact match on edges?
    // Actually, simply excluding the ID handles the "self-conflict".
    // Neighbors: If I edit Period 1, checking against Period 2.
    // P1: 8:00-8:30. P2: 8:30-9:00.
    // Check P1 (8:00-8:30):
    // start_time (8:00) BETWEEN 8:30 AND 9:00 -> False.
    // end_time (8:30) BETWEEN 8:30 AND 9:00 -> True!
    // So current logic marks adjacent periods as conflict.
    // This looks like a BUG in the original file too, unless DB times are stored differently.
    // I will use strict inequality for 'touching' intervals to allow them.
    
    $checkSql = "SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND academic_session_id = ? AND term_id = ? AND id != ? AND (
        (start_time < ? AND end_time > ?)
    )";
    // Wait, simple overlap logic: Max(start1, start2) < Min(end1, end2)
    // SQL: NOT (end_time <= ? OR start_time >= ?)
    
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE class_id = ? AND day_of_week = ? AND academic_session_id = ? AND term_id = ? AND id != ? AND (
        NOT (end_time <= ? OR start_time >= ?)
    )");
     
    $stmt->execute([$class_id, $day, $sessionId, $termId, $id, $start_time, $end_time]);
    
    if ($stmt->fetch()) {
        throw new Exception("Conflict detected: This class already has a scheduled activity during this time.");
    }

    // Conflict Detection: Check if teacher is busy
    $stmt = $conn->prepare("SELECT id FROM timetable WHERE teacher_id = ? AND day_of_week = ? AND academic_session_id = ? AND term_id = ? AND id != ? AND (
         NOT (end_time <= ? OR start_time >= ?)
    )");
    $stmt->execute([$teacher_id, $day, $sessionId, $termId, $id, $start_time, $end_time]);
    
    if ($stmt->fetch()) {
        throw new Exception("Conflict detected: Teacher is already assigned to another class at this time.");
    }

    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE timetable SET class_id=?, subject_id=?, teacher_id=?, day_of_week=?, start_time=?, end_time=?, room=?, academic_session_id=?, term_id=? WHERE id=?");
        $stmt->execute([$class_id, $subject_id, $teacher_id, $day, $start_time, $end_time, $room, $sessionId, $termId, $id]);
        $msg = 'Schedule updated successfully!';
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room, academic_session_id, term_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $subject_id, $teacher_id, $day, $start_time, $end_time, $room, $sessionId, $termId]);
        $msg = 'Schedule added successfully!';
    }

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
