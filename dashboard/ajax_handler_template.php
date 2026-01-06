<?php
/**
 * Professional AJAX Handler Template
 * Add this to the top of any PHP page that needs AJAX filtering
 */

// AJAX Handler - Must be at the very top before any HTML output
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    require_once '../config/database.php';
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Get parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $search = $_GET['search'] ?? '';
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        // Build base query - CUSTOMIZE THIS SECTION
        $baseTable = "users"; // Change to your main table
        $selectFields = "id, first_name, last_name, email, created_at"; // Change fields
        $whereParts = ["1=1"];
        $params = [];
        
        // Add search functionality - CUSTOMIZE SEARCH FIELDS
        if (!empty($search)) {
            $whereParts[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Add custom filters - ADD YOUR FILTERS HERE
        foreach ($_GET as $key => $value) {
            if (strpos($key, '_filter') !== false && !empty($value)) {
                $filterField = str_replace('_filter', '', $key);
                $whereParts[] = "$filterField = ?";
                $params[] = $value;
            }
        }
        
        $whereClause = implode(" AND ", $whereParts);
        
        // Count total items
        $countSql = "SELECT COUNT(*) FROM $baseTable WHERE $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = $countStmt->fetchColumn();
        
        // Get paginated data
        $sql = "SELECT $selectFields FROM $baseTable 
                WHERE $whereClause 
                ORDER BY created_at DESC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate pagination
        $totalPages = ceil($totalItems / $perPage);
        
        // Return JSON response
        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $perPage,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit; // CRITICAL: Must exit to prevent HTML output
}

// Regular page continues here...
?>
