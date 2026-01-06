<?php
// debug_timetable.php
$_GET['class_id'] = 1; // Assuming class ID 1 exists
ob_start();
include 'timetable_api.php';
$output = ob_get_clean();
file_put_contents('debug_api_output.txt', $output);
echo "Debug finished. Check debug_api_output.txt";
?>
