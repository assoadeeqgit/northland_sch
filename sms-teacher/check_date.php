<?php
echo "<h2>Date Check for Teacher Attendance</h2>";
echo "<p>Current server time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Current date (Y-m-d): " . date('Y-m-d') . "</p>";
echo "<p>Expected attendance date: " . ($_GET['date'] ?? date('Y-m-d')) . "</p>";

echo "<hr>";
echo "<p><a href='attendance.php'>Go to Attendance Page</a></p>";
echo "<p><a href='attendance.php?date=" . date('Y-m-d') . "'>Go to Attendance Page (with today's date)</a></p>";
?>
