<?php
/**
 * Global Configuration File
 * Defines constants for base paths and URLs to ensure consistent asset loading
 * across different directory levels
 */

// Detect protocol (http or https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Get the host name
$host = $_SERVER['HTTP_HOST'];

// Define the base path (adjust if your app is in a subdirectory)
$base_path = '/northland_sch';

// Construct the full base URL
define('BASE_URL', $protocol . '://' . $host . $base_path);

// Define the absolute file system path
define('BASE_PATH', dirname(__DIR__));

// Database configuration (centralized)
define('DB_HOST', 'localhost');
define('DB_NAME', 'northland_sch');
define('DB_USER', 'northland_user');
define('DB_PASS', 'secure_password_2025');

?>
