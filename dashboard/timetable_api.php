<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    echo json_encode(["success" => false, "message" => "class_id is required"]);
    exit;
}
$classId = (int)$_GET['class_id'];

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Use current academic session if available
$sessionId = null;
try {
    $stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    $row = $stmt->fetch();
    if ($row) $sessionId = (int)$row['id'];
} catch (Exception $e) {
    // ignore
}

$params = [':class_id' => $classId];
$sql = "SELECT t.id, t.class_id, t.subject_id, t.teacher_id, t.day_of_week, t.start_time, t.end_time, t.room,
               s.subject_name,
               CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN teachers tch ON t.teacher_id = tch.id
        LEFT JOIN users u ON tch.user_id = u.id
        WHERE t.class_id = :class_id";

if ($sessionId) {
    $sql .= " AND t.academic_session_id = :session_id";
    $params[':session_id'] = $sessionId;
}
$sql .= " ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    echo json_encode(["success" => true, "data" => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
