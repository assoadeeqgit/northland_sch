<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug logging function
function logAuthDebug($message) {
    $logFile = '/tmp/auth_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function checkAuth($requiredRole = null) {
    $isAuthenticated = false;
    
    // Log intent
    $rolesStr = is_array($requiredRole) ? implode(',', $requiredRole) : ($requiredRole ?? 'ANY');
    logAuthDebug("Checking auth for roles: $rolesStr. Current URI: " . $_SERVER['REQUEST_URI']);
    
    // 1. Check PHP session first
    if (isset($_SESSION['user_id'])) {
        logAuthDebug("Session found for user_id: " . $_SESSION['user_id'] . ", Type: " . ($_SESSION['user_type'] ?? 'unset'));
        
        if (!$requiredRole) {
            $isAuthenticated = true;
        } elseif (is_array($requiredRole)) {
            if (isset($_SESSION['user_type']) && in_array(trim($_SESSION['user_type']), $requiredRole)) {
                $isAuthenticated = true;
            } else {
                 logAuthDebug("Role mismatch in session. Required: $rolesStr, Found: " . ($_SESSION['user_type'] ?? 'null'));
            }
        } elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === $requiredRole) {
            $isAuthenticated = true;
        } else {
             logAuthDebug("Role mismatch in session (single). Required: $requiredRole, Found: " . ($_SESSION['user_type'] ?? 'null'));
        }
    } else {
        logAuthDebug("No active PHP session found.");
    }

    // 2. Check token from URL or POST if session check failed
    if (!$isAuthenticated && (isset($_GET['token']) || isset($_POST['token']))) {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        logAuthDebug("Attempting token auth with token: " . substr($token, 0, 10) . "...");
        
        if (!empty($token)) {
            require_once __DIR__ . '/config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("
                SELECT us.*, u.id as uid, u.user_type, u.username, u.email, u.first_name, u.last_name, u.is_active 
                FROM user_sessions us
                JOIN users u ON us.user_id = u.id
                WHERE us.session_token = ? AND us.expires_at > NOW()
            ");
            
            $stmt->execute([$token]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                logAuthDebug("Token found. User ID: " . $session['uid'] . ", Active: " . $session['is_active']);
                
                if ($session['is_active'] == 1) {
                    $roleCheckPassed = false;
                    
                    if (!$requiredRole) {
                        $roleCheckPassed = true;
                    } elseif (is_array($requiredRole)) {
                        if (in_array($session['user_type'], $requiredRole)) {
                            $roleCheckPassed = true;
                        }
                    } elseif ($session['user_type'] === $requiredRole) {
                        $roleCheckPassed = true;
                    }
                    
                    if ($roleCheckPassed) {
                        // Set session from token
                        $_SESSION['user_id'] = $session['uid'];
                        $_SESSION['user_type'] = $session['user_type'];
                        $_SESSION['user_name'] = $session['first_name'] . ' ' . $session['last_name'];
                        $_SESSION['email'] = $session['email'];
                        $_SESSION['session_token'] = $token;
                        $isAuthenticated = true;
                        logAuthDebug("Token auth successful. Session populated.");
                    } else {
                        logAuthDebug("Token found but role mismatch. User type: " . $session['user_type']);
                    }
                } else {
                     logAuthDebug("User is not active.");
                }
            } else {
                logAuthDebug("Token invalid or expired.");
            }
        }
    }
    
    if (!$isAuthenticated) {
        logAuthDebug("Authentication failed. Redirecting to login.");
        
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit();
        } else {
            // Redirect to login with return URL
            $current_url = urlencode($_SERVER['REQUEST_URI']);
            require_once __DIR__ . '/config/config.php';
            
            // Prevent redirect loop if we are already on login page (though checkAuth usually not called there)
            if (strpos($_SERVER['PHP_SELF'], 'login-form.php') === false) {
                 header('Location: ' . BASE_URL . '/login-form.php?return_url=' . $current_url);
                 exit();
            }
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
    if ($userType === 'admin') return true;
    
    // Add other role-based permission logic here
    $permissions = [
        'teacher' => ['manage_classes', 'view_students', 'submit_grades'],
        'student' => ['view_grades', 'view_attendance'],
        'staff' => ['manage_records', 'view_reports'],
        'accountant' => ['manage_payments', 'view_reports', 'manage_expenses', 'manage_inventory']
    ];
    
    return in_array($requiredPermission, $permissions[$userType] ?? []);
}
?>
