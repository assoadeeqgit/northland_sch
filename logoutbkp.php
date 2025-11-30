<?php
session_start();

// Include database configuration
require_once 'config/database.php';

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear database sessions if token provided
if (isset($_GET['token'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$_GET['token']]);
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log("Logout session cleanup failed: " . $e->getMessage());
    }
}

// Clear local storage and session storage via JavaScript
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check text-green-600 text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Logged Out Successfully</h2>
        <p class="text-gray-600 mb-6">You have been successfully logged out of the system.</p>
        <div class="flex justify-center space-x-4">
            <a href="login-form.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-sign-in-alt mr-2"></i>Login Again
            </a>
            <a href="../" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
        </div>
    </div>
    
    <script>
        // Clear all client-side storage
        localStorage.clear();
        sessionStorage.clear();
        
        // Optional: Redirect to login after a delay
        setTimeout(() => {
            window.location.href = "login-form.php";
        }, 3000);
    </script>
</body>
</html>';
?>