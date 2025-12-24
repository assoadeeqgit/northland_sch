<?php
require_once 'auth-check.php';

// This is a landing page that redirects users to their appropriate dashboard
if (isset($_SESSION['user_type'])) {
    $userType = $_SESSION['user_type'];
    
    switch ($userType) {
        case 'admin':
        case 'principal':
            header('Location: dashboard/admin-dashboard.php');
            break;
        case 'teacher':
            header('Location: sms-teacher/teacher_dashboard.php');
            break;
        case 'student':
            header('Location: dashboard/student-dashboard.php');
            break;
        case 'accountant':
            header('Location: accountant-dashboard/index.php');
            break;
        case 'staff':
            header('Location: dashboard/staff-dashboard.php');
            break;
        default:
            header('Location: dashboard/default-dashboard.html');
            break;
    }
    exit();
} else {
    // If somehow session is not set but auth-check passed (unlikely if checkAuth not called)
    // or if we want to ensure they go to login
    header('Location: login-form.php');
    exit();
}
?>
