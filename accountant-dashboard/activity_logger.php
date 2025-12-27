<?php
require_once '../config/database.php';

function logAccountantActivity($action, $details = '', $amount = null) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_name = $_SESSION['user_name'] ?? 'Accountant';
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, user_name, action, details, amount, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$user_id, $user_name, $action, $details, $amount]);
        return true;
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}

function getAccountantProgress() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $today = date('Y-m-d');
        
        // Today's activities
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM activity_log 
            WHERE DATE(created_at) = ? AND action LIKE '%payment%'
        ");
        $stmt->execute([$today]);
        $payments_today = $stmt->fetch()['count'] ?? 0;
        
        // Today's collection
        $stmt = $conn->prepare("
            SELECT SUM(amount_paid) as total FROM payments 
            WHERE payment_date = ?
        ");
        $stmt->execute([$today]);
        $collection_today = $stmt->fetch()['total'] ?? 0;
        
        return [
            'payments_processed' => $payments_today,
            'total_collected' => $collection_today,
            'last_activity' => date('H:i:s')
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}
?>
