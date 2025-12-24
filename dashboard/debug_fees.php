<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting fees.php debug...\n";

require_once '../auth-check.php';
echo "Auth check loaded\n";

include '../includes/header.php';
echo "Header loaded\n";

require_once '../config/database.php';
echo "Database config loaded\n";

$db = new Database();
$conn = $db->getConnection();
echo "Database connection established\n";

// Test query
try {
    $test = $conn->query("SELECT COUNT(*) as cnt FROM fee_structure");
    $result = $test->fetch(PDO::FETCH_ASSOC);
    echo "Fee structure count: " . $result['cnt'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Script completed successfully\n";
?>
