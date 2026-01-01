<?php
/**
 * Subject-Class Relationship - Usage Examples
 * This file demonstrates how to use the subject_class_helper.php functions
 */

require_once '../config/config.php';
require_once '../includes/subject_class_helper.php';

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Example 1: Get all subjects for a specific class (e.g., Primary 1)
echo "<h2>Example 1: Subjects for Primary 1 (Class ID: 4)</h2>";
$subjects = getSubjectsByClass($pdo, 4);
echo "<ul>";
foreach ($subjects as $subject) {
    echo "<li>{$subject['subject_name']} ({$subject['subject_code']}) - Category: {$subject['category']}</li>";
}
echo "</ul>";

// Example 2: Get all classes that teach a specific subject
echo "<h2>Example 2: Classes that teach English</h2>";
$stmt = $pdo->query("SELECT id FROM subjects WHERE subject_name LIKE 'English%' LIMIT 1");
$english_subject = $stmt->fetch(PDO::FETCH_ASSOC);
if ($english_subject) {
    $classes = getClassesBySubject($pdo, $english_subject['id']);
    echo "<ul>";
    foreach ($classes as $class) {
        echo "<li>{$class['class_name']}</li>";
    }
    echo "</ul>";
}

// Example 3: Check if a subject is assigned to a class
echo "<h2>Example 3: Check Subject Assignment</h2>";
$is_assigned = isSubjectAssignedToClass($pdo, 21, 4); // English-P for Primary 1
echo "<p>Is English assigned to Primary 1? " . ($is_assigned ? "Yes" : "No") . "</p>";

// Example 4: Get subject count per class
echo "<h2>Example 4: Subject Count Per Class</h2>";
$counts = getSubjectCountPerClass($pdo);
echo "<table border='1'>";
echo "<tr><th>Class Name</th><th>Subject Count</th></tr>";
foreach ($counts as $count) {
    echo "<tr><td>{$count['class_name']}</td><td>{$count['subject_count']}</td></tr>";
}
echo "</table>";

// Example 5: Get all subjects with their assigned classes
echo "<h2>Example 5: All Subjects with Assigned Classes</h2>";
$subjects_with_classes = getAllSubjectsWithClasses($pdo);
echo "<table border='1'>";
echo "<tr><th>Subject Code</th><th>Subject Name</th><th>Category</th><th>Assigned Classes</th><th>Class Count</th></tr>";
foreach ($subjects_with_classes as $subject) {
    echo "<tr>";
    echo "<td>{$subject['subject_code']}</td>";
    echo "<td>{$subject['subject_name']}</td>";
    echo "<td>{$subject['category']}</td>";
    echo "<td>{$subject['assigned_classes']}</td>";
    echo "<td>{$subject['class_count']}</td>";
    echo "</tr>";
}
echo "</table>";

// Example 6: Get subjects by section
echo "<h2>Example 6: Subjects for Primary Section</h2>";
$primary_subjects = getSubjectsBySection($pdo, 'Primary');
echo "<ul>";
foreach ($primary_subjects as $subject) {
    echo "<li>{$subject['subject_name']} ({$subject['subject_code']})</li>";
}
echo "</ul>";

// Example 7: Usage for Timetable Generation
echo "<h2>Example 7: Timetable Generation - Get Subjects for a Class</h2>";
echo "<p>When generating timetable for a specific class, use:</p>";
echo "<pre>";
echo '$class_id = 4; // Primary 1' . "\n";
echo '$subjects = getSubjectsByClass($pdo, $class_id);' . "\n";
echo 'foreach ($subjects as $subject) {' . "\n";
echo '    // Create timetable slots for this subject' . "\n";
echo '    echo $subject["subject_name"];' . "\n";
echo '}';
echo "</pre>";

// Example 8: Assign a new subject to a class (if needed)
echo "<h2>Example 8: Assign Subject to Class (Administrative Function)</h2>";
echo "<p>To assign a subject to a class programmatically:</p>";
echo "<pre>";
echo '$success = assignSubjectToClass($pdo, $subject_id, $class_id);' . "\n";
echo 'if ($success) {' . "\n";
echo '    echo "Subject assigned successfully!";' . "\n";
echo '}';
echo "</pre>";

// Example 9: Remove a subject from a class
echo "<h2>Example 9: Remove Subject from Class (Administrative Function)</h2>";
echo "<p>To remove a subject assignment:</p>";
echo "<pre>";
echo '$success = removeSubjectFromClass($pdo, $subject_id, $class_id);' . "\n";
echo 'if ($success) {' . "\n";
echo '    echo "Subject removed successfully!";' . "\n";
echo '}';
echo "</pre>";

?>
