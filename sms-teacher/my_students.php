<?php
/**
 * My Students Page
 * Displays a list of all students enrolled in the teacher's assigned classes,
 * along with their summary statistics.
 * Handles ADD/EDIT/UPDATE operations for student records.
 */

// Start session and check authentication
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php'; 

class MyStudentsData {
    private $conn;
    private $teacher_user_id;
    private $teacher_id;
    private $error_message = null;

    public function __construct(Database $database) {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed");
        }
        
        // Get the logged-in teacher's user ID from session
        $this->teacher_user_id = $_SESSION['user_id'];
        $this->setTeacherId();
        // Register the helper function so it's available in the main scope
        $this->registerHelperFunctions();
    }

    private function registerHelperFunctions() {
        if (!function_exists('getGradeLetter')) {
            function getGradeLetter($percentage) {
                if (!is_numeric($percentage)) return 'N/A';
                if ($percentage >= 90) return 'A';
                if ($percentage >= 80) return 'B';
                if ($percentage >= 70) return 'C';
                if ($percentage >= 60) return 'D';
                return 'F';
            }
        }
    }
    
    private function setTeacherId() {
        $query = "SELECT id FROM teachers WHERE user_id = :teacher_user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
        $stmt->execute();
        $this->teacher_id = $stmt->fetchColumn();
        
        if (!$this->teacher_id) {
            throw new Exception("Teacher profile not found for user ID: " . $this->teacher_user_id);
        }
    }
    
    public function getTeacherProfile(): array {
        $profile = [
            'first_name' => 'N/A', 
            'last_name' => 'N/A', 
            'initials' => 'NN', 
            'specialization' => 'N/A'
        ];
        
        $query = "
            SELECT 
                u.first_name, u.last_name, tp.subject_specialization 
            FROM users u
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE u.id = :teacher_user_id
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
            $stmt->execute();
            $data = $stmt->fetch();

            if ($data) {
                $profile['first_name'] = $data['first_name'];
                $profile['last_name'] = $data['last_name'];
                $profile['initials'] = strtoupper(substr($data['first_name'], 0, 1) . substr($data['last_name'], 0, 1));
                $profile['specialization'] = $data['subject_specialization'] ?: 'Teacher';
            }
        } catch (PDOException $e) {
            error_log("Error fetching profile: " . $e->getMessage());
        }

        return $profile;
    }

    /**
     * Helper to get student-specific academic metrics.
     */
    private function getStudentMetrics(int $student_id): array {
        $metrics = ['avg_grade' => 'N/A', 'avg_attendance' => 'N/A'];
        $today = date('Y-m-d');
        
        // 1. Calculate Student's Average Grade (across all exams in teacher's subjects)
        $query_grade = "
            SELECT IFNULL(AVG(r.marks_obtained / e.total_marks * 100), 0) AS average_grade
            FROM results r
            JOIN exams e ON r.exam_id = e.id
            JOIN class_subjects cs ON e.class_id = cs.class_id AND e.subject_id = cs.subject_id
            WHERE r.student_id = :student_id
              AND cs.teacher_id = :teacher_id
        ";
        $stmt_grade = $this->conn->prepare($query_grade);
        $stmt_grade->bindParam(':student_id', $student_id);
        $stmt_grade->bindParam(':teacher_id', $this->teacher_id);
        $stmt_grade->execute();
        $avg_grade = $stmt_grade->fetchColumn();
        $metrics['avg_grade'] = $avg_grade > 0 ? round($avg_grade) : 'N/A';

        // 2. Calculate Student's Attendance Rate (in their current class)
        $query_att = "
            SELECT 
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                COUNT(id) AS total_records
            FROM attendance 
            WHERE student_id = :student_id AND attendance_date <= :today
        ";
        $stmt_att = $this->conn->prepare($query_att);
        $stmt_att->bindParam(':student_id', $student_id);
        $stmt_att->bindParam(':today', $today);
        $stmt_att->execute();
        $att_data = $stmt_att->fetch();

        if ($att_data['total_records'] > 0) {
            $metrics['avg_attendance'] = round(($att_data['present_count'] / $att_data['total_records']) * 100);
        } else {
            $metrics['avg_attendance'] = 'N/A';
        }

        return $metrics;
    }

    /**
     * Fetches all students in classes taught by the current teacher with pagination.
     */
    public function getStudents(string $filter_class_id = 'all', int $page = 1, int $per_page = 5): array {
        $students = [];
        if (!$this->teacher_id) {
            return $students;
        }

        $where_clause = "AND c.id = :filter_class_id";
        if ($filter_class_id === 'all' || !is_numeric($filter_class_id)) {
            $where_clause = "";
        }
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $per_page;
        
        // Fetch students only in classes taught by this teacher with pagination
        $query = "
            SELECT DISTINCT
                s.id AS student_db_id,
                s.student_id,
                s.admission_number,
                s.admission_date,
                s.religion,
                s.nationality,
                s.state_of_origin,
                s.lga,
                s.medical_conditions,
                s.emergency_contact_name,
                s.emergency_contact_phone,

                u.id AS user_id,
                u.first_name, 
                u.last_name, 
                u.email,
                u.phone,
                u.date_of_birth,
                u.gender,

                c.id AS class_id,
                c.class_name
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN classes c ON s.class_id = c.id
            JOIN class_subjects cs ON c.id = cs.class_id
            WHERE cs.teacher_id = :teacher_id
            {$where_clause}
            ORDER BY c.class_name, u.last_name, u.first_name
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            if (!empty($where_clause)) {
                $stmt->bindParam(':filter_class_id', $filter_class_id);
            }
            $stmt->execute();
            $students = $stmt->fetchAll();

            foreach ($students as &$student) {
                $metrics = $this->getStudentMetrics($student['student_db_id']);
                $student = array_merge($student, $metrics);
                
                $student['initials'] = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
                $student['avatar_color'] = '#' . substr(md5($student['student_id']), 0, 6); // Unique color hash
            }

        } catch (PDOException $e) {
            error_log("Error fetching students: " . $e->getMessage());
        }

        return $students;
    }

    /**
     * Get total count of students for pagination
     */
    public function getTotalStudentsCount(string $filter_class_id = 'all'): int {
        if (!$this->teacher_id) {
            return 0;
        }

        $where_clause = "AND c.id = :filter_class_id";
        if ($filter_class_id === 'all' || !is_numeric($filter_class_id)) {
            $where_clause = "";
        }

        $query = "
            SELECT COUNT(DISTINCT s.id) as total_count
            FROM students s
            JOIN classes c ON s.class_id = c.id
            JOIN class_subjects cs ON c.id = cs.class_id
            WHERE cs.teacher_id = :teacher_id
            {$where_clause}
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            if (!empty($where_clause)) {
                $stmt->bindParam(':filter_class_id', $filter_class_id);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting students: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Fetches a list of unique classes taught by the teacher for the filter dropdown.
     */
    public function getTeacherClassesList(): array {
        $classes = [];
        if (!$this->teacher_id) return $classes;

        $query = "
            SELECT DISTINCT
                c.id AS class_id,
                c.class_name
            FROM classes c
            JOIN class_subjects cs ON c.id = cs.class_id
            WHERE cs.teacher_id = :teacher_id
            ORDER BY c.class_name
        ";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->execute();
            $classes = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching classes list: " . $e->getMessage());
        }
        return $classes;
    }
    
    /**
     * Fetches only classes assigned to this teacher for modal dropdown.
     */
    public function getTeacherClasses(): array {
        return $this->getTeacherClassesList(); // Reuse the same method
    }

    /**
     * Calculates aggregated statistics across all the teacher's students.
     */
    public function getAggregateStats(array $students): array {
        $total_students = count($students);
        $total_avg_grade = 0;
        $total_avg_attendance = 0;
        $needs_attention = 0;
        $valid_grade_count = 0;
        $valid_att_count = 0;

        foreach ($students as $student) {
            if (is_numeric($student['avg_grade'])) {
                $total_avg_grade += $student['avg_grade'];
                $valid_grade_count++;
            }
            if (is_numeric($student['avg_attendance'])) {
                $total_avg_attendance += $student['avg_attendance'];
                $valid_att_count++;
            }
            
            // Define 'Needs Attention' threshold (Medical conditions OR Grade < 60% OR Attendance < 70%)
            $has_medical_issues = !empty(trim($student['medical_conditions'] ?? ''));
            $low_grade = (is_numeric($student['avg_grade']) && $student['avg_grade'] < 60);
            $low_attendance = (is_numeric($student['avg_attendance']) && $student['avg_attendance'] < 70);
            
            if ($has_medical_issues || $low_grade || $low_attendance) {
                $needs_attention++;
            }
        }

        $overall_avg_grade = $valid_grade_count > 0 ? round($total_avg_grade / $valid_grade_count) : 0;
        $overall_avg_attendance = $valid_att_count > 0 ? round($total_avg_attendance / $valid_att_count) : 0;

        return [
            'total_students' => $total_students,
            'avg_attendance' => $overall_avg_attendance,
            'overall_avg_grade' => $overall_avg_grade,
            'needs_attention' => $needs_attention
        ];
    }
    
    /**
     * Generates a unique Student ID and Admission Number by finding the MAX existing ID.
     */
    private function generateStudentIdentifiers(): array {
        $current_student_id = 'STU000';
        $current_adm_num = 'ADM000';

        // 1. Get MAX student_id (e.g., 'STU004')
        $query_sid = "SELECT MAX(student_id) FROM students WHERE student_id LIKE 'STU%'";
        $max_sid = $this->conn->query($query_sid)->fetchColumn();
        if ($max_sid) {
            $current_student_id = $max_sid;
        }

        // 2. Get MAX admission_number (e.g., 'ADM004')
        $query_adm = "SELECT MAX(admission_number) FROM students WHERE admission_number LIKE 'ADM%'";
        $max_adm = $this->conn->query($query_adm)->fetchColumn();
        if ($max_adm) {
            $current_adm_num = $max_adm;
        }

        // Extract numbers, find the largest, and format.
        $last_sid_num = (int) substr($current_student_id, 3);
        $last_adm_num = (int) substr($current_adm_num, 3);

        $new_id_num = max($last_sid_num, $last_adm_num) + 1;
        
        return [
            'student_id' => 'STU' . str_pad($new_id_num, 3, '0', STR_PAD_LEFT),
            'admission_number' => 'ADM' . str_pad($new_id_num, 3, '0', STR_PAD_LEFT)
        ];
    }
    
    /**
     * Inserts a new student record (User, Student).
     */
    public function insertStudent(array $data): bool {
        
        $base_email = $data['email'];
        $email_to_use = $base_email;
        $base_username = strtolower($data['first_name'] . '.' . $data['last_name']);
        $username_to_use = $base_username;
        $unique_suffix = '';
        $attempt = 0;

        // Loop to ensure uniqueness for both email and username
        while ($attempt < 10) {
            try {
                $this->conn->beginTransaction();
                $ids = $this->generateStudentIdentifiers();
                $default_pass = password_hash(strtolower($data['first_name']) . '123', PASSWORD_DEFAULT); 
                
                if ($attempt > 0) {
                    // Append a unique suffix to make the email and username unique
                    $unique_suffix = substr(md5(microtime() . mt_rand()), 0, 4);
                    $email_parts = explode('@', $base_email);
                    $email_to_use = $email_parts[0] . '.' . $unique_suffix . '@' . $email_parts[1];
                    $username_to_use = $base_username . $unique_suffix;
                }

                // 1. Insert into users table
                $user_q = "
                    INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, date_of_birth, gender)
                    VALUES (:username, :email, :password_hash, 'student', :first_name, :last_name, :phone, :dob, :gender)
                ";
                $stmt_user = $this->conn->prepare($user_q);
                $stmt_user->execute([
                    ':username' => $username_to_use, // Unique username
                    ':email' => $email_to_use,
                    ':password_hash' => $default_pass,
                    ':first_name' => $data['first_name'],
                    ':last_name' => $data['last_name'],
                    ':phone' => $data['phone'] ?: null,
                    ':dob' => $data['date_of_birth'],
                    ':gender' => $data['gender']
                ]);
                $user_id = $this->conn->lastInsertId();
                
                // 2. Insert into students table
                $student_q = "
                    INSERT INTO students (user_id, student_id, admission_number, class_id, admission_date, religion, nationality, state_of_origin, lga, medical_conditions, emergency_contact_name, emergency_contact_phone)
                    VALUES (:user_id, :student_id, :admission_number, :class_id, :admission_date, :religion, :nationality, :state_of_origin, :lga, :medical_conditions, :emergency_contact_name, :emergency_contact_phone)
                ";
                $stmt_student = $this->conn->prepare($student_q);
                $stmt_student->execute([
                    ':user_id' => $user_id,
                    ':student_id' => $ids['student_id'],
                    ':admission_number' => $ids['admission_number'],
                    ':class_id' => $data['class_id'],
                    ':admission_date' => $data['admission_date'],
                    ':religion' => $data['religion'],
                    ':nationality' => $data['nationality'],
                    ':state_of_origin' => $data['state_of_origin'],
                    ':lga' => $data['lga'],
                    ':medical_conditions' => $data['medical_conditions'],
                    ':emergency_contact_name' => $data['emergency_contact_name'],
                    ':emergency_contact_phone' => $data['emergency_contact_phone']
                ]);
                
                $this->conn->commit();
                return true; // Success!
            } catch (PDOException $e) {
                $this->conn->rollBack();
                if ($e->getCode() === '23000') {
                     // Duplicate entry error (username or email collision)
                    $attempt++;
                    if ($attempt < 10) {
                        // Continue loop to try with new suffix
                        continue;
                    }
                }
                // If loop fails or non-duplicate error occurs, report it
                $this->error_message = "Error: " . $e->getMessage();
                error_log("Student Insertion Error: " . $e->getMessage());
                return false;
            }
        }
        // If the loop finished without success
        $this->error_message = "Failed to create unique user credentials after multiple attempts.";
        return false;
    }
    
    /**
     * Updates an existing student record (User, Student).
     */
    public function updateStudent(array $data): bool {
        try {
            $this->conn->beginTransaction();
            
            // 1. Update users table
            $user_q = "
                UPDATE users SET 
                    first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, 
                    date_of_birth = :dob, gender = :gender
                WHERE id = :user_id
            ";
            $stmt_user = $this->conn->prepare($user_q);
            $stmt_user->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?: null,
                ':dob' => $data['date_of_birth'],
                ':gender' => $data['gender'],
                ':user_id' => $data['user_id'] // CRUCIAL: user_id from hidden form field
            ]);
            
            // 2. Update students table
            $student_q = "
                UPDATE students SET
                    class_id = :class_id, admission_date = :admission_date, religion = :religion, 
                    nationality = :nationality, state_of_origin = :state_of_origin, lga = :lga, 
                    medical_conditions = :medical_conditions, emergency_contact_name = :emergency_contact_name, 
                    emergency_contact_phone = :emergency_contact_phone
                WHERE user_id = :user_id
            ";
            $stmt_student = $this->conn->prepare($student_q);
            $stmt_student->execute([
                ':class_id' => $data['class_id'],
                ':admission_date' => $data['admission_date'],
                ':religion' => $data['religion'],
                ':nationality' => $data['nationality'],
                ':state_of_origin' => $data['state_of_origin'],
                ':lga' => $data['lga'],
                ':medical_conditions' => $data['medical_conditions'],
                ':emergency_contact_name' => $data['emergency_contact_name'],
                ':emergency_contact_phone' => $data['emergency_contact_phone'],
                ':user_id' => $data['user_id']
            ]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $this->error_message = "Error: " . $e->getMessage();
            error_log("Student Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getErrorMessage(): ?string {
        return $this->error_message;
    }
}

// --- Execution & Form Handling ---
try {
    $database = new Database();
    $students_data = new MyStudentsData($database);

    // Handle POST submissions (Add/Edit Student)
    $form_action = $_POST['form_action'] ?? '';
    $is_success = false;
    $action_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
        
        if ($form_action === 'add_student') {
            $is_success = $students_data->insertStudent($data);
            $action_message = $is_success ? 'New student added successfully!' : ('Error adding student: ' . $students_data->getErrorMessage());
        } elseif ($form_action === 'edit_student') {
            $is_success = $students_data->updateStudent($data);
            $action_message = $is_success ? 'Student profile updated successfully!' : ('Error updating profile: ' . $students_data->getErrorMessage());
        }
        
        // Redirect to clear POST data and show updated list
        $redirect_url = 'my_students.php?status=' . ($is_success ? 'success' : 'error') . '&msg=' . urlencode($action_message);
        // Persist the current filter and page during redirect
        if (!empty($_GET['class_id'])) {
            $redirect_url .= '&class_id=' . urlencode($_GET['class_id']);
        }
        if (!empty($_GET['page'])) {
            $redirect_url .= '&page=' . urlencode($_GET['page']);
        }
        header("Location: $redirect_url");
        exit;
    }

    // Handle GET requests for status messages after redirect
    if (isset($_GET['status']) && isset($_GET['msg'])) {
        $is_success = $_GET['status'] === 'success';
        $action_message = htmlspecialchars(urldecode($_GET['msg']));
    }

    // Get URL parameters
    $filter_class_id = $_GET['class_id'] ?? 'all';
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 5; // Show 5 students per page

    // Fetch data for rendering
    $profile = $students_data->getTeacherProfile();
    $teacher_classes = $students_data->getTeacherClassesList();
    $teacher_classes_for_form = $students_data->getTeacherClasses(); // Only teacher's classes for form
    $total_students_count = $students_data->getTotalStudentsCount($filter_class_id);
    $students = $students_data->getStudents($filter_class_id, $current_page, $per_page);
    $stats = $students_data->getAggregateStats($students);

    // Calculate pagination
    $total_pages = ceil($total_students_count / $per_page);
    $start_index = (($current_page - 1) * $per_page) + 1;
    $end_index = min($current_page * $per_page, $total_students_count);

} catch (Exception $e) {
    // Handle errors gracefully
    error_log("My Students page error: " . $e->getMessage());
    
    // If it's an authentication error, redirect to login
    if (strpos($e->getMessage(), 'Teacher profile not found') !== false) {
        session_destroy();
        header("Location: login-form.php?error=auth");
        exit();
    }
    
    // For other errors, show a user-friendly message
    $error_message = "An error occurred while loading the students page. Please try again later.";
    die($error_message);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',
                        nsklightblue: '#3b82f6',
                        nsknavy: '#1e3a8a',
                        nskgold: '#f59e0b',
                        nsklight: '#f0f9ff',
                        nskgreen: '#10b981',
                        nskred: '#ef4444'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
        
        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        
        .dashboard-card {
            transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar {
            transition: all var(--transition-speed) ease;
            width: var(--sidebar-width);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .main-content {
            transition: all var(--transition-speed) ease;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                z-index: 20;
            }
            
            .sidebar.mobile-show {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 15;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
        
        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .floating-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .floating-btn:hover {
            transform: scale(1.1);
        }
        
        .sidebar-link.active {
            background-color: #1e40af !important;
        }
        
        .mobile-header {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .desktop-header {
                display: none;
            }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: scale(1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .grade-bar-bg {
            background-color: #e5e7eb;
            border-radius: 4px;
            height: 8px;
            width: 100%;
        }

        .grade-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 10000;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            opacity: 0;
            transform: translateY(-20px);
        }
        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .notification.success {
            background-color: #10b981;
            color: white;
        }
        .notification.error {
            background-color: #ef4444;
            color: white;
        }
        
        .pagination-btn {
            transition: all 0.3s ease;
        }
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pagination-btn:not(:disabled):hover {
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="flex">
    <?php if ($action_message): ?>
    <div class="notification show <?= $is_success ? 'success' : 'error' ?>" style="opacity: 1; transform: translateY(0);">
        <?= htmlspecialchars($action_message) ?>
    </div>
    <?php endif; ?>

    <div class="mobile-overlay" id="mobileOverlay"></div>

    <main class="main-content">
        <header class="desktop-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Students</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search students..." class="bg-transparent outline-none w-32 md:w-64">
                        </div>
                    </div>
                    
                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>
                    
                    <div class="hidden md:flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                            <?= $profile['initials'] ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-nsknavy">Mr. <?= $profile['first_name'] . ' ' . $profile['last_name'] ?></p>
                            <p class="text-xs text-gray-600"><?= $profile['specialization'] ?> Teacher</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <header class="mobile-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">Students</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                            <?= $profile['initials'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 md:p-6">
            <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 md:mb-6 space-y-3 md:space-y-0">
                    <h2 class="text-lg md:text-xl font-bold text-nsknavy">My Students (<?= $total_students_count ?>)</h2>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        <select id="classFilter" class="px-3 py-2 border rounded-lg text-sm" onchange="updateClassFilter(this.value)">
                            <option value="all" <?= $filter_class_id == 'all' ? 'selected' : '' ?>>All Classes (<?= $total_students_count ?>)</option>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>" <?= $filter_class_id == $class['class_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="exportStudentBtn" class="bg-nskgold text-white px-3 py-2 rounded-lg hover:bg-amber-600 transition text-sm">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                        <button id="addStudentBtn" class="bg-nskgreen text-white px-3 py-2 rounded-lg hover:bg-green-600 transition text-sm">
                            <i class="fas fa-plus mr-2"></i>Add Student
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskblue"><?= $total_students_count ?></div>
                        <p class="text-xs text-gray-600">Total Students</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskgreen"><?= $stats['avg_attendance'] ?>%</div>
                        <p class="text-xs text-gray-600">Avg. Attendance</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskgold"><?= $stats['overall_avg_grade'] ?>%</div>
                        <p class="text-xs text-gray-600">Overall Average</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskred"><?= $stats['needs_attention'] ?></div>
                        <p class="text-xs text-gray-600">Need Attention</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm md:text-base" id="studentTable">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold">Student</th>
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold hidden sm:table-cell">Class</th>
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold">Grade</th>
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold hidden md:table-cell">Attendance</th>
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold">Status</th>
                                <th class="py-3 px-3 md:px-6 text-left text-nsknavy font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): 
                                    $grade_val = is_numeric($student['avg_grade']) ? $student['avg_grade'] : 0;
                                    $att_val = is_numeric($student['avg_attendance']) ? $student['avg_attendance'] : 0;
                                    
                                    // Check for medical conditions
                                    $has_medical_issues = !empty(trim($student['medical_conditions'] ?? ''));
                                    $low_grade = (is_numeric($student['avg_grade']) && $student['avg_grade'] < 60);
                                    $low_attendance = (is_numeric($student['avg_attendance']) && $student['avg_attendance'] < 70);
                                    
                                    // Determine status based on conditions
                                    if ($has_medical_issues) {
                                        $status_text = 'At Risk';
                                        $status_color = 'bg-red-100 text-nskred';
                                        $grade_color = 'text-nskred';
                                        $grade_fill_color = 'bg-nskred';
                                    } elseif ($low_grade) {
                                        $status_text = 'Low Grade';
                                        $status_color = 'bg-red-100 text-nskred';
                                        $grade_color = 'text-nskred';
                                        $grade_fill_color = 'bg-nskred';
                                    } elseif ($low_attendance) {
                                        $status_text = 'Poor Attendance';
                                        $status_color = 'bg-red-100 text-nskred';
                                        $grade_color = 'text-nskred';
                                        $grade_fill_color = 'bg-nskred';
                                    } elseif ($grade_val >= 85) {
                                        $grade_color = 'text-nskgreen';
                                        $grade_fill_color = 'bg-nskgreen';
                                        $status_text = 'Excellent';
                                        $status_color = 'bg-green-100 text-nskgreen';
                                    } elseif ($grade_val >= 70) {
                                        $grade_color = 'text-nskgold';
                                        $grade_fill_color = 'bg-nskgold';
                                        $status_text = 'Good';
                                        $status_color = 'bg-amber-100 text-nskgold';
                                    } else {
                                        // Default status for students with no issues
                                        $grade_color = 'text-nskblue';
                                        $grade_fill_color = 'bg-nskblue';
                                        $status_text = 'Normal';
                                        $status_color = 'bg-blue-100 text-nskblue';
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-4 px-3 md:px-6">
                                            <div class="flex items-center">
                                                <div class="student-avatar" style="background-color: <?= $student['avatar_color'] ?>"><?= $student['initials'] ?></div>
                                                <div class="ml-3">
                                                    <p class="font-semibold text-sm md:text-base"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></p>
                                                    <p class="text-xs text-gray-600">ID: <?= htmlspecialchars($student['student_id']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-3 md:px-6 hidden sm:table-cell">
                                            <span class="bg-blue-100 text-nskblue px-2 py-1 rounded-full text-xs font-semibold"><?= htmlspecialchars($student['class_name']) ?></span>
                                        </td>
                                        <td class="py-4 px-3 md:px-6">
                                            <span class="<?= $grade_color ?> font-bold"><?= is_numeric($student['avg_grade']) ? $student['avg_grade'] . '%' : 'N/A' ?></span>
                                            <p class="text-xs text-gray-600 hidden md:block"><?= $grade_val > 0 ? getGradeLetter($grade_val) : '' ?></p>
                                        </td>
                                        <td class="py-4 px-3 md:px-6 hidden md:table-cell">
                                            <div class="flex items-center">
                                                <div class="grade-bar-bg mr-2">
                                                    <div class="grade-bar-fill <?= $grade_fill_color ?>" style="width: <?= $att_val ?>%"></div>
                                                </div>
                                                <span class="text-xs font-semibold"><?= is_numeric($student['avg_attendance']) ? $student['avg_attendance'] . '%' : 'N/A' ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-3 md:px-6">
                                            <span class="<?= $status_color ?> px-2 py-1 rounded-full text-xs font-semibold"><?= $status_text ?></span>
                                        </td>
                                        <td class="py-4 px-3 md:px-6">
                                            <div class="flex space-x-2">
                                                <button class="view-student text-nskblue hover:text-nsknavy" data-student='<?= htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8') ?>'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="edit-student text-nskgreen hover:text-green-700" data-student='<?= htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8') ?>'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-nskred hover:text-red-700">
                                                    <i class="fas fa-comment"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500">
                                        No students found in your assigned classes<?= $filter_class_id != 'all' ? ' for the selected class.' : '.' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-center mt-4 md:mt-6 space-y-3 sm:space-y-0">
                    <p class="text-sm text-gray-600">
                        Showing <?= $start_index ?> to <?= $end_index ?> of <?= $total_students_count ?> students
                    </p>
                    <div class="flex space-x-2">
                        <button 
                            class="pagination-btn px-3 py-1 bg-gray-200 rounded-lg text-sm hover:bg-gray-300 transition <?= $current_page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                            <?= $current_page <= 1 ? 'disabled' : '' ?>
                            onclick="changePage(<?= $current_page - 1 ?>)"
                        >
                            Previous
                        </button>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button 
                                class="pagination-btn px-3 py-1 rounded-lg text-sm transition <?= $i == $current_page ? 'bg-nskblue text-white' : 'bg-gray-200 hover:bg-gray-300' ?>"
                                onclick="changePage(<?= $i ?>)"
                            >
                                <?= $i ?>
                            </button>
                        <?php endfor; ?>
                        
                        <button 
                            class="pagination-btn px-3 py-1 bg-gray-200 rounded-lg text-sm hover:bg-gray-300 transition <?= $current_page >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>" 
                            <?= $current_page >= $total_pages ? 'disabled' : '' ?>
                            onclick="changePage(<?= $current_page + 1 ?>)"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <button class="floating-btn md:hidden bg-nskblue text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center" id="mobileAddStudentBtn">
        <i class="fas fa-plus text-xl"></i>
    </button>

    <!-- Include sidebar at the end of body -->
    <?php include 'sidebar.php'; ?>

    <!-- Student Details Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content w-full max-w-4xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy" id="modalStudentTitle">Student Details</h3>
                <button id="closeStudentModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-6">
                <div class="flex items-center space-x-4">
                    <div id="modalAvatar" class="student-avatar text-2xl w-16 h-16"></div>
                    <div>
                        <h4 class="font-bold text-lg" id="modalStudentName"></h4>
                        <p class="text-gray-600" id="modalStudentId"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-nskblue mb-2">Personal Information</h4>
                        <p class="text-sm"><strong>Class:</strong> <span id="modalClass"></span></p>
                        <p class="text-sm"><strong>Gender:</strong> <span id="modalGender">N/A</span></p>
                        <p class="text-sm"><strong>DOB:</strong> <span id="modalDOB">N/A</span></p>
                        <p class="text-sm"><strong>Medical Conditions:</strong> <span id="modalMedical">None</span></p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-nskgreen mb-2">Academic Performance</h4>
                        <p class="text-sm"><strong>Average Grade:</strong> <span id="modalAvgGrade"></span></p>
                        <p class="text-sm"><strong>Attendance Rate:</strong> <span id="modalAttRate"></span></p>
                        <p class="text-sm"><strong>Status:</strong> <span id="modalStatus">Normal</span></p>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-nskgold mb-2">Contact Information</h4>
                        <p class="text-sm"><strong>Emergency Contact:</strong> <span id="modalEmergencyName">N/A</span></p>
                        <p class="text-sm"><strong>Emergency Phone:</strong> <span id="modalEmergencyPhone">N/A</span></p>
                        <p class="text-sm"><strong>Email:</strong> <span id="modalEmail">N/A</span></p>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-nsknavy mb-3">Recent Grades (Simulated)</h4>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <span class="text-sm">Last Exam (Math)</span>
                            <span class="text-sm font-semibold text-nskgreen">90%</span>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                            <span class="text-sm">Quiz 2 (Simulated)</span>
                            <span class="text-sm font-semibold text-nskgold">75%</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button id="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Close</button>
                    <button class="px-4 py-2 bg-nskblue text-white rounded-lg text-sm hover:bg-nsknavy transition">Send Message</button>
                    <button class="px-4 py-2 bg-nskgreen text-white rounded-lg text-sm hover:bg-green-600 transition">View Full Profile</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Student Modal -->
    <div id="addEditModal" class="modal">
        <div class="modal-content w-full max-w-4xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy" id="addEditModalTitle">Add New Student</h3>
                <button id="closeAddEditModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="my_students.php" id="addEditForm" class="space-y-4">
                <input type="hidden" name="form_action" id="formAction" value="add_student">
                <input type="hidden" name="user_id" id="formUserId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name *</label>
                        <input type="text" name="first_name" id="formFirstName" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                        <input type="text" name="last_name" id="formLastName" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" name="email" id="formEmail" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" id="formPhone" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                        <input type="date" name="date_of_birth" id="formDOB" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender *</label>
                        <select name="gender" id="formGender" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Class *</label>
                        <select name="class_id" id="formClassId" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">Select Class</option>
                            <?php foreach ($teacher_classes_for_form as $class): ?>
                                <option value="<?= $class['class_id'] ?>">
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Admission Date *</label>
                        <input type="date" name="admission_date" id="formAdmissionDate" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Religion</label>
                        <select name="religion" id="formReligion" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="Islam">Islam</option>
                            <option value="Christianity">Christianity</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nationality</label>
                        <input type="text" name="nationality" id="formNationality" value="Nigerian" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">State of Origin</label>
                        <input type="text" name="state_of_origin" id="formStateOrigin" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">LGA</label>
                        <input type="text" name="lga" id="formLGA" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Medical Conditions</label>
                        <textarea name="medical_conditions" id="formMedical" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Enter any medical conditions or leave blank if none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" id="formEmergencyName" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Emergency Contact Phone</label>
                        <input type="tel" name="emergency_contact_phone" id="formEmergencyPhone" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" id="cancelAddEdit" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" id="submitAddEdit" class="bg-nskgreen text-white px-4 py-2 rounded-md hover:bg-green-600">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // DOM Elements
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const studentModal = document.getElementById('studentModal');
        const addEditModal = document.getElementById('addEditModal');
        const addStudentBtn = document.getElementById('addStudentBtn');
        const mobileAddStudentBtn = document.getElementById('mobileAddStudentBtn');
        const exportStudentBtn = document.getElementById('exportStudentBtn');
        
        // Form & Modal elements
        const addEditModalTitle = document.getElementById('addEditModalTitle');
        const formAction = document.getElementById('formAction');
        const formUserId = document.getElementById('formUserId');
        const submitAddEdit = document.getElementById('submitAddEdit');
        const closeAddEditModal = document.getElementById('closeAddEditModal');
        const cancelAddEdit = document.getElementById('cancelAddEdit');

        // Form Fields (Dynamic access for simplicity)
        const formFirstName = document.getElementById('formFirstName');
        const formLastName = document.getElementById('formLastName');
        const formEmail = document.getElementById('formEmail');
        const formPhone = document.getElementById('formPhone');
        const formDOB = document.getElementById('formDOB');
        const formGender = document.getElementById('formGender');
        const formClassId = document.getElementById('formClassId');
        const formAdmissionDate = document.getElementById('formAdmissionDate');
        const formReligion = document.getElementById('formReligion');
        const formNationality = document.getElementById('formNationality');
        const formStateOrigin = document.getElementById('formStateOrigin');
        const formLGA = document.getElementById('formLGA');
        const formMedical = document.getElementById('formMedical');
        const formEmergencyName = document.getElementById('formEmergencyName');
        const formEmergencyPhone = document.getElementById('formEmergencyPhone');


        // --- UI UTILITIES ---

        function showNotification(message, type = 'success') {
            let notification = document.querySelector('.notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.className = 'notification';
                document.body.appendChild(notification);
            }
            notification.textContent = message;
            notification.className = `notification show ${type}`;
            
            setTimeout(() => {
                notification.classList.remove('show');
                // Remove element if it wasn't pre-rendered by PHP
                if (!"<?= $action_message ?>") notification.remove(); 
            }, 3000);
        }

        function openModal(modal) {
            modal.classList.add('active');
        }

        function closeModalFunc(modal) {
            modal.classList.remove('active');
        }

        // --- PAGINATION & FILTER FUNCTIONS ---

        function changePage(page) {
            const classFilter = document.getElementById('classFilter').value;
            let url = `my_students.php?page=${page}`;
            if (classFilter !== 'all') {
                url += `&class_id=${classFilter}`;
            }
            window.location.href = url;
        }

        function updateClassFilter(classId) {
            let url = 'my_students.php?class_id=' + classId;
            // Keep current page if it exists
            const currentPage = <?= $current_page ?>;
            if (currentPage > 1) {
                url += '&page=' + currentPage;
            }
            window.location.href = url;
        }

        // --- EXPORT FUNCTIONALITY ---

        function exportTableToExcel() {
            const table = document.getElementById('studentTable');
            
            // Clone table and remove the 'Actions' column for export
            const cloneTable = table.cloneNode(true);
            cloneTable.querySelectorAll('th:nth-child(6), td:nth-child(6)').forEach(col => col.remove());

            const ws = XLSX.utils.table_to_sheet(cloneTable);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Students");
            
            // Generate file name with current filter
            const className = document.getElementById('classFilter').options[document.getElementById('classFilter').selectedIndex].text.replace(/\s/g, '_');
            const filename = `NSK_Student_Export_${className}_${new Date().toISOString().slice(0,10)}.xlsx`;
            
            XLSX.writeFile(wb, filename);
            showNotification('Student data exported successfully!', 'success');
        }

        // --- ADD/EDIT MODAL HANDLER ---

        function openAddEditModal(isEdit = false, studentData = null) {
            const form = document.getElementById('addEditForm');
            form.reset(); // Clear previous data
            
            if (isEdit && studentData) {
                addEditModalTitle.textContent = `Edit Student: ${studentData.first_name} ${studentData.last_name}`;
                formAction.value = 'edit_student';
                submitAddEdit.textContent = 'Update Student';
                
                // Populate fields
                formUserId.value = studentData.user_id;
                formFirstName.value = studentData.first_name;
                formLastName.value = studentData.last_name;
                formEmail.value = studentData.email || '';
                formPhone.value = studentData.phone || '';
                formDOB.value = studentData.date_of_birth || '';
                formGender.value = studentData.gender || '';
                formClassId.value = studentData.class_id;
                formAdmissionDate.value = studentData.admission_date || '<?= date('Y-m-d') ?>';
                formReligion.value = studentData.religion || 'Islam';
                formNationality.value = studentData.nationality || 'Nigerian';
                formStateOrigin.value = studentData.state_of_origin || '';
                formLGA.value = studentData.lga || '';
                formMedical.value = studentData.medical_conditions || '';
                formEmergencyName.value = studentData.emergency_contact_name || '';
                formEmergencyPhone.value = studentData.emergency_contact_phone || '';
                
            } else {
                addEditModalTitle.textContent = 'Add New Student';
                formAction.value = 'add_student';
                submitAddEdit.textContent = 'Add Student';
                formUserId.value = ''; // Ensure user_id is clear for new add
                formAdmissionDate.value = '<?= date('Y-m-d') ?>'; // Set default admission date
                formNationality.value = 'Nigerian'; // Set default nationality
            }
            
            openModal(addEditModal);
        }
        
        // --- VIEW DETAILS MODAL HANDLER ---
        function populateViewModal(student) {
             // Populate Modal with Dynamic Data
            document.getElementById('modalStudentTitle').textContent = `Student Details - ${student.first_name} ${student.last_name}`;
            document.getElementById('modalStudentName').textContent = `${student.first_name} ${student.last_name}`;
            document.getElementById('modalStudentId').textContent = `ID: ${student.student_id}`;
            document.getElementById('modalClass').textContent = student.class_name;
            
            document.getElementById('modalAvatar').textContent = student.initials;
            document.getElementById('modalAvatar').style.backgroundColor = student.avatar_color;
            
            document.getElementById('modalAvgGrade').textContent = isFinite(student.avg_grade) ? student.avg_grade + '%' : 'N/A';
            document.getElementById('modalAttRate').textContent = isFinite(student.avg_attendance) ? student.avg_attendance + '%' : 'N/A';
            
            document.getElementById('modalGender').textContent = student.gender || 'N/A';
            document.getElementById('modalDOB').textContent = student.date_of_birth || 'N/A';
            document.getElementById('modalEmergencyName').textContent = student.emergency_contact_name || 'N/A';
            document.getElementById('modalEmergencyPhone').textContent = student.emergency_contact_phone || 'N/A';
            document.getElementById('modalEmail').textContent = student.email || 'N/A';
            document.getElementById('modalMedical').textContent = student.medical_conditions || 'None';
            
            // Determine status for view modal
            const hasMedicalIssues = student.medical_conditions && student.medical_conditions.trim() !== '';
            const lowGrade = isFinite(student.avg_grade) && student.avg_grade < 60;
            const lowAttendance = isFinite(student.avg_attendance) && student.avg_attendance < 70;
            
            let statusText = 'Normal';
            if (hasMedicalIssues) {
                statusText = 'At Risk (Medical)';
            } else if (lowGrade) {
                statusText = 'At Risk (Low Grade)';
            } else if (lowAttendance) {
                statusText = 'At Risk (Poor Attendance)';
            } else if (student.avg_grade >= 85) {
                statusText = 'Excellent';
            } else if (student.avg_grade >= 70) {
                statusText = 'Good';
            }
            document.getElementById('modalStatus').textContent = statusText;

            // Show modal
            openModal(studentModal);
        }

        // --- EVENT LISTENERS INITIALIZATION ---

        // Sidebar/Menu controls
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            document.querySelectorAll('.sidebar-text').forEach(el => el.classList.toggle('hidden'));
        });
        mobileMenuToggle.addEventListener('click', () => toggleMobileMenu());
        mobileOverlay.addEventListener('click', () => toggleMobileMenu());
        
        const toggleMobileMenu = () => {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('active');
        }

        // Open Modals
        addStudentBtn.addEventListener('click', () => openAddEditModal(false));
        mobileAddStudentBtn.addEventListener('click', () => openAddEditModal(false));
        
        // Close Modals
        closeAddEditModal.addEventListener('click', () => closeModalFunc(addEditModal));
        cancelAddEdit.addEventListener('click', () => closeModalFunc(addEditModal));
        document.getElementById('closeStudentModal').addEventListener('click', () => closeModalFunc(studentModal));
        document.getElementById('closeModal').addEventListener('click', () => closeModalFunc(studentModal));

        // Export Button
        exportStudentBtn.addEventListener('click', exportTableToExcel);

        // Dynamic buttons setup
        document.querySelectorAll('.view-student').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const student = JSON.parse(e.currentTarget.getAttribute('data-student'));
                populateViewModal(student);
            });
        });

        document.querySelectorAll('.edit-student').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const student = JSON.parse(e.currentTarget.getAttribute('data-student'));
                openAddEditModal(true, student);
            });
        });

        // Close modal when clicking overlay
        window.addEventListener('click', (e) => {
            if (e.target === studentModal) closeModalFunc(studentModal);
            if (e.target === addEditModal) closeModalFunc(addEditModal);
        });

        // PHP Notification on page load
        document.addEventListener('DOMContentLoaded', () => {
            // This displays the status message after the redirect.
            if ("<?= $action_message ?>") {
                // The showNotification function handles displaying the message and automatically hiding it.
            }
        });
    </script>
</body>
</html>