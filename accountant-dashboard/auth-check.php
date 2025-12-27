<?php
/**
 * Finance/Accountant Dashboard Auth Check
 * Allows both admin and accountant to access finance features
 */

require_once __DIR__ . '/../auth-check.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login-form.php');
    exit();
}

// Allow both admin and accountant to access finance dashboard
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'accountant'])) {
    // User is logged in but doesn't have permission
    http_response_code(403);
    die('Access Denied: You do not have permission to access the Finance section.');
}

// User is authenticated and authorized
return true;
