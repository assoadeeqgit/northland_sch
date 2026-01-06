<?php
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Current Term Check</h2>";
echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Same query as admin dashboard
$stmt = $conn->prepare("SELECT term_name, start_date, end_date FROM terms WHERE is_current = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Current Term:</strong> " . $result['term_name'] . "<br>";
    echo "<strong>Start Date:</strong> " . $result['start_date'] . "<br>";
    echo "<strong>End Date:</strong> " . $result['end_date'] . "<br>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "No current term found!";
    echo "</div>";
}

echo "<p><a href='admin-dashboard.php'>← Back to Admin Dashboard</a></p>";
?>
