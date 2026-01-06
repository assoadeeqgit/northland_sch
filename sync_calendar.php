<?php
require_once 'config/database.php';

function syncTermsWithCalendar() {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $currentDate = date('Y-m-d');
        
        // Get current academic session
        $sessionQuery = "SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1";
        $sessionStmt = $conn->prepare($sessionQuery);
        $sessionStmt->execute();
        $currentSession = $sessionStmt->fetch();
        
        if (!$currentSession) {
            return ['success' => false, 'message' => 'No current academic session found'];
        }
        
        // First, set all terms to inactive
        $deactivateQuery = "UPDATE terms SET is_current = 0 WHERE session_id = ?";
        $deactivateStmt = $conn->prepare($deactivateQuery);
        $deactivateStmt->execute([$currentSession['id']]);
        
        // Find and activate the correct term based on current date
        $termQuery = "SELECT id, term_name FROM terms 
                     WHERE session_id = ? 
                     AND start_date <= ? 
                     AND end_date >= ? 
                     ORDER BY start_date DESC 
                     LIMIT 1";
        
        $termStmt = $conn->prepare($termQuery);
        $termStmt->execute([$currentSession['id'], $currentDate, $currentDate]);
        $activeTerm = $termStmt->fetch();
        
        if ($activeTerm) {
            // Activate the correct term
            $activateQuery = "UPDATE terms SET is_current = 1 WHERE id = ?";
            $activateStmt = $conn->prepare($activateQuery);
            $activateStmt->execute([$activeTerm['id']]);
            
            // Fix attendance records to use current term for today and future dates
            $today = date('Y-m-d');
            $attendanceFixQuery = "
                UPDATE attendance 
                SET academic_session_id = ?, term_id = ? 
                WHERE attendance_date >= ?
            ";
            $attendanceStmt = $conn->prepare($attendanceFixQuery);
            $attendanceStmt->execute([$currentSession['id'], $activeTerm['id'], $today]);
            
            $attendanceFixed = $attendanceStmt->rowCount();
            
            return [
                'success' => true, 
                'message' => "Calendar synced successfully. {$activeTerm['term_name']} is now active. Fixed {$attendanceFixed} attendance records.",
                'active_term' => $activeTerm['term_name'],
                'attendance_fixed' => $attendanceFixed
            ];
        } else {
            return ['success' => false, 'message' => 'No term found for current date'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()];
    }
}

// If called directly (for AJAX or direct access)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_calendar') {
    header('Content-Type: application/json');
    echo json_encode(syncTermsWithCalendar());
    exit;
}
?>
