<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== STUDENTS TABLE SCHEMA ===\n";
$result = $conn->query("DESCRIBE students");
$schema = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($schema as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
?>
