<?php
// Simple timetable API without auth
header('Content-Type: application/json; charset=utf-8');

try {
    define('API_MODE', true);
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/TimetableHelper.php';
    require_once __DIR__ . '/../includes/term_helper.php'; // Term synchronization
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Initialization error: " . $e->getMessage()]);
    exit;
}

$classId = isset($_GET['class_id']) && is_numeric($_GET['class_id']) ? (int)$_GET['class_id'] : null;

if (!$classId) {
    echo json_encode(["success" => false, "message" => "class_id is required"]);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Get class info and rules
$stmt = $conn->prepare("SELECT class_name, class_level FROM classes WHERE id = ?");
$stmt->execute([$classId]);
$classInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$classInfo) {
    echo json_encode(["success" => false, "message" => "Class not found"]);
    exit;
}

$rules = TimetableHelper::getRules($classInfo['class_level'], $classInfo['class_name']);

// Get current session and term
$stmt = $conn->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
$sessionId = $stmt->fetchColumn();
$stmt = $conn->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
$termId = $stmt->fetchColumn();

// Query timetable
$sql = "SELECT t.*, s.subject_name, COALESCE(s.is_dummy, 0) as is_dummy, c.class_name, 
               COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'No Teacher') AS teacher_name
        FROM timetable t
        LEFT JOIN subjects s ON t.subject_id = s.id
        LEFT JOIN classes c ON t.class_id = c.id
        LEFT JOIN teachers tch ON t.teacher_id = tch.id
        LEFT JOIN users u ON tch.user_id = u.id
        WHERE t.class_id = ?";

$params = [$classId];

if ($sessionId && $termId) {
    $sql .= " AND t.academic_session_id = ? AND t.term_id = ?";
    $params[] = $sessionId;
    $params[] = $termId;
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
        "periods" => $generatedPeriods,
        "debug" => [
            "class_info" => $classInfo,
            "session_id" => $sessionId,
            "term_id" => $termId,
            "entry_count" => count($rows)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
