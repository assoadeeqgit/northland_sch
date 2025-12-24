<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs (Basic validation)
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $admission_number = $_POST['admission_number'] ?? '';
    $class_id = $_POST['class_id'] ?? null; // Needs to handle text to ID mapping or change form to use IDs
    $parent_phone = $_POST['parent_phone'] ?? '';
    
    // For now, we'll assume the form sends text for class/section and we might need to look it up or just store it if the schema allows text.
    // Checking schema... schema says class_id INT.
    // We need to fetch class_id based on Class Name and Section.
    // For this prototype, let's insert a dummy class if it doesn't exist or map manually.
    
    // Better approach: Let's assume the form sends valid data or we map it simple.
    // Actually, looking at the form, it sends text like "JSS 1".
    // We should look up the class_id.
    
    try {
        // 1. Get or Create Class ID (Simplified logic for demo)
        $class_name = $_POST['class_name'] ?? 'JSS 1';
        $section = $_POST['section'] ?? 'A';
        
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND section = ?");
        $stmt->execute([$class_name, $section]);
        $class_row = $stmt->fetch();
        
        if ($class_row) {
            $class_id = $class_row['id'];
        } else {
            // Create class if not exists (Auto-setup)
            $stmt = $pdo->prepare("INSERT INTO classes (name, section, academic_year_id) VALUES (?, ?, 1)");
            $stmt->execute([$class_name, $section]);
            $class_id = $pdo->lastInsertId();
        }

        // 2. Insert Student
        $stmt = $pdo->prepare("INSERT INTO students (admission_number, first_name, last_name, gender, date_of_birth, class_id, parent_name, parent_phone, parent_email, address, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $admission_number,
            $first_name,
            $last_name,
            $_POST['gender'] ?? 'Male',
            $_POST['dob'] ?? date('Y-m-d'),
            $class_id,
            $_POST['guardian_name'] ?? '',
            $parent_phone,
            $_POST['guardian_email'] ?? '',
            $_POST['address'] ?? '',
            $_POST['enrollment_date'] ?? date('Y-m-d')
        ]);

        // Success
        header("Location: students.php?status=success");
        exit;

    } catch (PDOException $e) {
        die("Error saving student: " . $e->getMessage());
    }
}
?>
