<?php
session_start();

function checkAuth($requiredRole = null) {
    $isAuthenticated = false;
    
    // Check PHP session first
    if (isset($_SESSION['user_id'])) {
        // Validate session against database to ensure account is still active
        require_once dirname(__FILE__) . '/../config/database.php';
        $database = new Database();
        $db = $database->getConnection();

        try {
            $stmt = $db->prepare("SELECT id, user_type, is_active FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['is_active'] == 1) {
                // User is active, proceed with role check
                $userType = $user['user_type'];
                
                // Sync session role if changed
                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== $userType) {
                    $_SESSION['user_type'] = $userType;
                }

                $roleMatch = false;
                
                if (!$requiredRole) {
                    $roleMatch = true;
                } elseif (is_array($requiredRole)) {
                    $roleMatch = in_array($userType, $requiredRole);
                } elseif ($requiredRole === 'admin') {
                    $roleMatch = in_array($userType, ['admin', 'administrator', 'super_admin']);
                } else {
                    $roleMatch = $userType === $requiredRole;
                }
                
                if ($roleMatch) {
                    $isAuthenticated = true;
                }
            } else {
                // Account deactivated or deleted - destroy session
                session_unset();
                session_destroy();
                $isAuthenticated = false;
            }
        } catch (Exception $e) {
            // DB Error - fail safe
             error_log("Auth Check Error: " . $e->getMessage());
             $isAuthenticated = false;
        }
    } 
    // Check token from URL or POST
    if (!$isAuthenticated && (isset($_GET['token']) || isset($_POST['token']))) {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        
        if (!empty($token)) {
            require_once '../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("
                SELECT us.*, u.* FROM user_sessions us
                JOIN users u ON us.user_id = u.id
                WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
            ");
            
            $stmt->execute([$token]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                $userType = $session['user_type'];
                $roleMatch = false;
                
                if (!$requiredRole) {
                    $roleMatch = true;
                } elseif (is_array($requiredRole)) {
                    $roleMatch = in_array($userType, $requiredRole);
                } elseif ($requiredRole === 'admin') {
                    $roleMatch = in_array($userType, ['admin', 'administrator', 'super_admin']);
                } else {
                    $roleMatch = $userType === $requiredRole;
                }

                if ($roleMatch) {
                    // Set session from token
                    $_SESSION['user_id'] = $session['user_id'];
                    $_SESSION['user_type'] = $session['user_type'];
                    $_SESSION['user_name'] = $session['first_name'] . ' ' . $session['last_name'];
                    $_SESSION['email'] = $session['email'];
                    $_SESSION['session_token'] = $token;
                    $isAuthenticated = true;
                }
            }
        }
    }
    
    if (!$isAuthenticated) {
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit();
        } else {
            // Redirect to login with return URL
            $current_url = urlencode($_SERVER['REQUEST_URI']);
            header('Location: ../login-form.php?return_url=' . $current_url);
            exit();
        }
    }
    
    return true;
}

// Helper function to get user info
function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}

// Helper function to check specific permissions
function hasPermission($requiredPermission) {
    if (!isset($_SESSION['user_type'])) return false;
    
    // You can expand this with more granular permission checks
    $userType = $_SESSION['user_type'];
    
    // Admin has all permissions
    if (in_array($userType, ['admin', 'administrator', 'super_admin'])) return true;
    
    // Add other role-based permission logic here
    $permissions = [
        'teacher' => ['manage_classes', 'view_students', 'submit_grades'],
        'student' => ['view_grades', 'view_attendance'],
        'staff' => ['manage_records', 'view_reports']
    ];
    
    return in_array($requiredPermission, $permissions[$userType] ?? []);
}
?>