<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $today = date('Y-m-d');
    
    // Get today's activities
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_activities,
            SUM(CASE WHEN action LIKE '%payment%' THEN 1 ELSE 0 END) as payments_processed,
            MAX(created_at) as last_activity
        FROM activity_log 
        WHERE DATE(created_at) = ? AND user_name LIKE '%accountant%'
    ");
    $stmt->execute([$today]);
    $activities = $stmt->fetch();
    
    // Get today's collection
    $stmt = $conn->prepare("SELECT SUM(amount_paid) as total FROM payments WHERE payment_date = ?");
    $stmt->execute([$today]);
    $collection = $stmt->fetch();
    
    // Get recent activities
    $stmt = $conn->prepare("
        SELECT action, details, amount, created_at 
        FROM activity_log 
        WHERE DATE(created_at) = ? AND user_name LIKE '%accountant%'
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$today]);
    $recent_activities = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_activities' => $activities['total_activities'] ?? 0,
            'payments_processed' => $activities['payments_processed'] ?? 0,
            'total_collected' => $collection['total'] ?? 0,
            'last_activity' => $activities['last_activity'],
            'recent_activities' => $recent_activities
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
