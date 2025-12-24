<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== FEE TYPES ===\n";
$result = $conn->query("SELECT * FROM fee_types");
$fee_types = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($fee_types);

echo "\n\n=== FEE STRUCTURES (Sample) ===\n";
$result = $conn->query("SELECT fs.*, c.name as class_name, ft.name as fee_type_name, ay.name as academic_year 
FROM fee_structures fs 
LEFT JOIN classes c ON fs.class_id = c.id 
LEFT JOIN fee_types ft ON fs.fee_type_id = ft.id 
LEFT JOIN academic_years ay ON fs.academic_year_id = ay.id
LIMIT 5");
$structures = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($structures);

echo "\n\n=== COUNT ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM fee_structures");
echo "Total Fee Structures: " . $result->fetch(PDO::FETCH_ASSOC)['total'] . "\n";

echo "\n\n=== CLASSES ===\n";
$result = $conn->query("SELECT * FROM classes LIMIT 5");
$classes = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($classes);

echo "\n\n=== ACADEMIC YEARS ===\n";
$result = $conn->query("SELECT * FROM academic_years");
$years = $result->fetchAll(PDO::FETCH_ASSOC);
print_r($years);
?>
