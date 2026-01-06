<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? '';
// Check if user is an admin type who can use the dashboard sidebar
$isAdmin = in_array($userType, ['admin', 'administrator', 'super_admin', 'principal']);

// Determine the correct dashboard link based on role
$dashboardLink = '../login-form.php'; // Default to login
if ($isLoggedIn) {
    if ($userType === 'teacher') {
        $dashboardLink = '../sms-teacher/teacher_dashboard.php';
    } elseif ($userType === 'student') {
        $dashboardLink = 'student-dashboard.php';
    } elseif ($userType === 'accountant') {
        $dashboardLink = '../accountant-dashboard/index.php';
    } elseif ($isAdmin) {
        $dashboardLink = 'admin-dashboard.php';
    } else {
        $dashboardLink = 'default-dashboard.html';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body { font-family: 'Montserrat', sans-serif; }
    </style>
    <?php if ($isLoggedIn && $isAdmin): ?>
    <link rel="stylesheet" href="sidebar.css">
    <?php endif; ?>
</head>
<body class="bg-gray-50">

<?php
if ($isLoggedIn && $isAdmin) {
    include 'sidebar.php';
    echo '<main class="main-content min-h-screen flex flex-col">';
    $pageTitle = 'Errors';
    include 'header.php';
    echo '<div class="content-body flex-grow flex items-center justify-center p-6">';
} else {
    echo '<div class="min-h-screen flex items-center justify-center p-6">';
}
?>

    <div class="text-center bg-white p-8 md:p-12 rounded-2xl shadow-xl max-w-lg w-full">
        <div class="mb-8">
             <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                 <i class="fas fa-lock text-4xl text-red-600"></i>
             </div>
        </div>
        <h1 class="text-6xl font-bold text-gray-900 mb-2">403</h1>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Access Denied</h2>
        <p class="text-gray-600 mb-8 leading-relaxed">
            Sorry, you don't have permission to access up this page. Please contact your administrator if you believe this is a mistake.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center gap-4">
             <a href="<?= htmlspecialchars($dashboardLink) ?>" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl">
                <i class="fas fa-home mr-2"></i>
                <?= $isLoggedIn ? 'Dashboard' : 'Login' ?>
            </a>
             <button onclick="window.history.back()" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all shadow-sm hover:shadow-md">
                 <i class="fas fa-arrow-left mr-2"></i> Go Back
            </button>
        </div>
    </div>

<?php 
if ($isLoggedIn && $isAdmin) {
    echo '</div>'; // content-body
    require_once 'footer.php';
    echo '</main>';
} else {
    echo '</div>'; // wrapper
}
?>

</body>
</html>
