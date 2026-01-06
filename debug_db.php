<?php
require_once 'config/DatabaseManager.php';
$dbManager = DatabaseManager::getInstance();
$db = $dbManager->getConnection();

$termStmt = $db->query("SELECT id, term_name FROM terms WHERE is_current = 1 LIMIT 1");
$currentTerm = $termStmt->fetch(PDO::FETCH_ASSOC);
echo "Current Term: ";
print_r($currentTerm);

$query = "SELECT COUNT(*) as count FROM students s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1 AND s.status = 'active'";
$stmt = $db->query($query);
echo "\nActive Students: " . $stmt->fetch()['count'];

$currentTermId = $currentTerm['id'] ?? null;
$queryTables = "FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN (
                    SELECT student_id, SUM(amount_paid) as total_paid
                    FROM payments 
                    WHERE term_id = ?
                    GROUP BY student_id
                ) payment_summary ON payment_summary.student_id = s.id
                LEFT JOIN (
                    SELECT class_id, SUM(amount) as total_fee
                    FROM fee_structure 
                    WHERE term_id = ? AND is_active = 1 AND is_optional = 0
                    GROUP BY class_id
                ) fee_summary ON fee_summary.class_id = s.class_id";

$fullQuery = "SELECT COUNT(*) as total $queryTables WHERE u.is_active = 1 AND s.status = 'active'";
$stmt = $db->prepare($fullQuery);
$stmt->execute([$currentTermId, $currentTermId]);
echo "\nQuery Result Total: " . $stmt->fetch()['total'];
?>
