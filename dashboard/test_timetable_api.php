<?php
// Simple test to check if timetable API works
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM timetable");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "message" => "API is working",
        "timetable_count" => $row['count'],
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
