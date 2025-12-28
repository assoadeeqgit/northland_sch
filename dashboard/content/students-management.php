<?php
/**
 * Students Management - Content Only Version
 * This file serves content without layout when requested via AJAX
 */

// Include SPA helper
require_once '../spa-helper.php';

// Your existing page logic here
require_once '../../auth-check.php';
checkAuth('admin');

require_once '../../config/database.php';

// ... rest of your students management logic ...

$isAjax = isAjaxRequest();
?>

<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Northland Schools</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="sidebar.css">
</head>
<body class="flex">
    <?php include '../sidebar.php'; ?>
    <main class="main-content min-h-screen">
<?php endif; ?>

<!-- CONTENT STARTS HERE - This gets served for both AJAX and normal requests -->
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Students Management</h1>
        <p class="text-gray-600 mt-1">Manage student records and information</p>
    </div>

    <!-- Your page content here -->
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <p>Student management content goes here...</p>
        <!-- Include your actual students-management.php content here -->
    </div>
</div>
<!-- CONTENT ENDS HERE -->

<?php if (!$isAjax): ?>
    </main>
</body>
</html>
<?php endif; ?>
