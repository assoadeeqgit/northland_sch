<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/TimetableHelper.php';
checkAuth();

$classId = isset($_GET['class_id']) && is_numeric($_GET['class_id']) ? (int)$_GET['class_id'] : null;
$teacherId = isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;

if (!$classId && !$teacherId) {
    echo json_encode(["success" => false, "message" => "class_id or teacher_id is required"]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Get rules
$rules = null;
if ($classId) {
    $stmt = $conn->prepare("SELECT class_name, class_level FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $rules = TimetableHelper::getRules($classInfo['class_level'] ?? 'Primary', $classInfo['class_name'] ?? '');
} else {
    $rules = [
        'type' => 'Teacher View',
        'start_time' => '08:00',
        'end_time' => '14:00',
        'period_duration' => 60,
        'total_periods' => 6,
        'break_start' => '10:00',
        'break_end' => '10:30',
        'break_after_period' => 2
    ];
}

// Get Academic Context
$stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
$sessionId = $stmt->fetchColumn();
$stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
$termId = $stmt->fetchColumn();

// Base SQL
$sql = "SELECT t.*, s.subject_name, s.is_dummy, c.class_name, 
               CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN classes c ON t.class_id = c.id
        LEFT JOIN teachers tch ON t.teacher_id = tch.id
        LEFT JOIN users u ON tch.user_id = u.id
        WHERE 1=1";
$params = [];

if ($classId) {
    $sql .= " AND t.class_id = :class_id";
    $params[':class_id'] = $classId;
} elseif ($teacherId) {
    $sql .= " AND t.teacher_id = :teacher_id";
    $params[':teacher_id'] = $teacherId;
}

if ($sessionId) {
    $sql .= " AND t.academic_session_id = :session_id AND t.term_id = :term_id";
    $params[':session_id'] = $sessionId;
    $params[':term_id'] = $termId;
}

$sql .= " ORDER BY FIELD(t.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $generatedPeriods = TimetableHelper::generatePeriods($rules);
    
    echo json_encode([
        "success" => true, 
        "data" => $rows,
        "rules" => $rules,
        "periods" => $generatedPeriods
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
