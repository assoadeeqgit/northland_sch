<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== EXISTING TABLES ===\n";
$result = $conn->query("SHOW TABLES");
$tables = $result->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "- $table\n";
}
?>
