<?php
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        echo "Connection SUCCESSFUL";
    } else {
        echo "Connection FAILED (null)";
    }
} catch (Exception $e) {
    echo "Connection ERROR: " . $e->getMessage();
}
?>
