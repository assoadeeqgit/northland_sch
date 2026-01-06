<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $_SESSION['user_type'] ?? '';
$isAdmin = in_array($userType, ['admin', 'administrator', 'super_admin', 'principal']);

$dashboardLink = '../login-form.php';
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
    <title>500 - Server Error</title>
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
             <div class="w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mx-auto">
                 <i class="fas fa-cogs text-4xl text-purple-600 animate-spin-slow" style="animation-duration: 3s;"></i>
             </div>
        </div>
        <h1 class="text-6xl font-bold text-gray-900 mb-2">500</h1>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Internal Server Error</h2>
        <p class="text-gray-600 mb-8 leading-relaxed">
            Something went wrong on our end. We're working to fix it. Please try again later.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center gap-4">
             <a href="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl">
                <i class="fas fa-redo mr-2"></i>
                Retry Page
            </a>
            <?php if ($isLoggedIn): ?>
             <a href="<?= htmlspecialchars($dashboardLink) ?>" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-all shadow-sm hover:shadow-md">
                 <i class="fas fa-home mr-2"></i> Dashboard
            </a>
            <?php endif; ?>
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
