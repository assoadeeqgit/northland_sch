<?php
// Fix attendance term synchronization
require_once 'config/database.php';

function fixAttendanceTermSync() {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Get current session and term
        $sessionQuery = "SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1";
        $sessionStmt = $conn->prepare($sessionQuery);
        $sessionStmt->execute();
        $currentSession = $sessionStmt->fetchColumn();
        
        $termQuery = "SELECT id FROM terms WHERE is_current = 1 LIMIT 1";
        $termStmt = $conn->prepare($termQuery);
        $termStmt->execute();
        $currentTerm = $termStmt->fetchColumn();
        
        if (!$currentSession || !$currentTerm) {
            return ['success' => false, 'message' => 'No current session or term found'];
        }
        
        // Update all attendance records from today onwards to use current term
        $today = date('Y-m-d');
        $updateQuery = "
            UPDATE attendance 
            SET academic_session_id = ?, term_id = ? 
            WHERE attendance_date >= ?
        ";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$currentSession, $currentTerm, $today]);
        
        $affectedRows = $updateStmt->rowCount();
        
        return [
            'success' => true,
            'message' => "Attendance sync fixed. Updated {$affectedRows} records to current term.",
            'current_session' => $currentSession,
            'current_term' => $currentTerm
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Fix failed: ' . $e->getMessage()];
    }
}

// If called directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix_attendance') {
    header('Content-Type: application/json');
    echo json_encode(fixAttendanceTermSync());
    exit;
}
?>
