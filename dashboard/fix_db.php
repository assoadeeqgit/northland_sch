<?php
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "Attempting to remove UNIQUE constraint on users.email...\n";

try {
    // Attempt standard index name 'email'
    $sql = "ALTER TABLE users DROP INDEX email";
    $db->exec($sql);
    echo "SUCCESS: Dropped index 'email'.\n";
} catch (PDOException $e) {
    echo "attempt 1 failed: " . $e->getMessage() . "\n";
    
    // Try 'users_email_unique' or similar if previous failed
    try {
        $sql = "ALTER TABLE users DROP INDEX users_email_unique";
        $db->exec($sql);
        echo "SUCCESS: Dropped index 'users_email_unique'.\n";
    } catch (PDOException $e2) {
        echo "attempt 2 failed: " . $e2->getMessage() . "\n";
        
        // Final attempt: Inspect table structure
        echo "Inspecting table structure...\n";
        $stmt = $db->query("SHOW CREATE TABLE users");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Create Table SQL: \n" . print_r($row, true) . "\n";
    }
}
?>
