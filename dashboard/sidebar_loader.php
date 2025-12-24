<?php
// This file does all the work of loading the sidebar with the correct user info.
// Start session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get user details from session for the sidebar
$sidebar_user_name = $_SESSION['user_name'] ?? 'Guest User';
$sidebar_user_role = ucfirst($_SESSION['user_type'] ?? 'Guest'); // 'admin' -> 'Guest'

// Create initials for the sidebar
$sidebar_name_parts = explode(' ', $sidebar_user_name, 2);
$sidebar_first_initial = $sidebar_name_parts[0][0] ?? 'G';
$sidebar_last_initial = isset($sidebar_name_parts[1]) ? ($sidebar_name_parts[1][0] ?? '') : '';
$sidebar_user_initials = strtoupper($sidebar_first_initial . $sidebar_last_initial);

// Use output buffering to read the sidebar template
ob_start();
include 'sidebar.php'; // Include the template file
$sidebar_content = ob_get_clean(); // Get the file's contents

// Replace the placeholders with the real data
$sidebar_content = str_replace('__USER_NAME__', htmlspecialchars($sidebar_user_name), $sidebar_content);
$sidebar_content = str_replace('__USER_ROLE__', htmlspecialchars($sidebar_user_role), $sidebar_content);
$sidebar_content = str_replace('__USER_INITIALS__', htmlspecialchars($sidebar_user_initials), $sidebar_content);

// Print the final, correct HTML
echo $sidebar_content;
?>