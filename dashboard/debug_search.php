<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "Testing Search Logic...\n";

try {
    // 1. Check Date Column format
    $stmt = $db->query("SELECT created_at FROM activity_log LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Sample Date: " . ($row['created_at'] ?? 'NULL') . "\n";

    // 2. Test DATE_FORMAT logic
    $search = 'Dec';
    $term = "%$search%";
    $sql = "SELECT COUNT(*) FROM activity_log WHERE DATE_FORMAT(created_at, '%b %d') LIKE ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$term]);
    echo "Matches for 'Dec': " . $stmt->fetchColumn() . "\n";

    // 3. Test Date Filter Logic
    $date = date('Y-m-d'); // Today
    $sql = "SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$date]);
    echo "Matches for Today ($date): " . $stmt->fetchColumn() . "\n";

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
}
?>
