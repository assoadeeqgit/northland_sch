<?php
/**
 * AJAX Endpoint: Get Subjects for a Class
 * Returns subjects assigned to a specific class in JSON format
 */

header('Content-Type: application/json');

require_once 'auth-check.php';
checkAuth('admin');

require_once '../config/database.php';
require_once '../includes/subject_class_helper.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get class_id from GET parameter
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
    
    if ($class_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid class ID',
            'subjects' => []
        ]);
        exit;
    }
    
    // Get subjects for this class using the helper function
    $subjects = getSubjectsByClass($pdo, $class_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Subjects loaded successfully',
        'subjects' => $subjects,
        'count' => count($subjects)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'subjects' => []
    ]);
}
?>
