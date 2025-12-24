<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== FEE_STRUCTURE TABLE SCHEMA ===\n";
$result = $conn->query("DESCRIBE fee_structure");
$schema = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($schema);

echo "\n\n=== FEE_STRUCTURE DATA (Sample) ===\n";
$result = $conn->query("SELECT * FROM fee_structure LIMIT 5");
$data = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($data);

echo "\n\n=== COUNT ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM fee_structure");
echo "Total: " . $result->fetch(PDO::FETCH_ASSOC)['total'] . "\n";

echo "\n\n=== CLASSES DATA ===\n";
$result = $conn->query("SELECT * FROM classes LIMIT 5");
$classes = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($classes);
?>
