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

// Include AJAX check wrapper
require_once 'ajax_check.php';
?>
<?php if (!$is_ajax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Northland Schools Kano</title>
    <!-- Compiled Tailwind CSS -->
    <link rel="stylesheet" href="../assets/css/tailwind.min.css?v=1.0.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
<?php endif; ?>
        <?php 
        $pageTitle = 'Students';
        include 'header.php'; 
        ?>
        <div id="page-content">

            <span id="page-meta-data" data-title="Students" hidden></span>
            
            <!-- Scripts/Styles that must load with content for SPA -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
            <link rel="stylesheet" href="css/students.css">

                        <?php if ($action_message): ?>
            <div class="notification show <?= $is_success ? 'success' : 'error' ?>" style="opacity: 1; transform: translateY(0);">
                <?= htmlspecialchars($action_message) ?>
            </div>
            <?php endif; ?>



            <!-- Dashboard Content -->
            <div class="p-4 md:p-6">
                <!-- Data Filters & Add Button -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-4 md:space-y-0">
                    <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                        <!-- Class Filter -->
                        <div class="relative">
                            <select id="classFilter" class="appearance-none bg-white border border-gray-300 text-gray-700 py-2 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white focus:border-nskblue shadow-sm">
                                <option value="all">All Classes</option>
                                <?php foreach ($teacher_classes as $class): ?>
                                    <option value="<?= $class['class_id'] ?>" <?= $filter_class_id == $class['class_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-2 w-full md:w-auto">
                        <button onclick="openAddStudentModal()" class="bg-nskblue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center space-x-2 shadow-md w-full md:w-auto">
                            <i class="fas fa-plus"></i>
                            <span>Add Student</span>
                        </button>
                        
                         <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center space-x-2 shadow-md w-full md:w-auto">
                            <i class="fas fa-file-excel"></i>
                            <span>Export</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow p-4 border-l-4 border-nskblue">
                        <p class="text-gray-500 text-xs uppercase font-bold">Total Students</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= $stats['total_students'] ?></p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-4 border-l-4 border-nskgreen">
                        <p class="text-gray-500 text-xs uppercase font-bold">Avg Attendance</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= $stats['avg_attendance'] ?>%</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-4 border-l-4 border-nskgold">
                        <p class="text-gray-500 text-xs uppercase font-bold">Class Average</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1"><?= $stats['overall_avg_grade'] ?>%</p>
                    </div>
                     <div class="bg-white rounded-xl shadow p-4 border-l-4 border-nskred">
                        <p class="text-gray-500 text-xs uppercase font-bold">Needs Attention</p>
                        <p class="text-2xl font-bold text-nskred mt-1"><?= $stats['needs_attention'] ?></p>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap">
                            <thead class="bg-gray-50 text-gray-600 font-semibold text-xs uppercase tracking-wider border-b">
                                tr>
                                    <th class="px-6 py-4 text-left">Student Information</th>
                                    <th class="px-6 py-4 text-center">Class</th>
                                    <th class="px-6 py-4 text-center">Avg. Grade</th>
                                    <th class="px-6 py-4 text-center">Attendance</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-user-graduate text-4xl mb-3 text-gray-300"></i>
                                                <p>No students found matching current filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): 
                                        $grade_color = ($student['avg_grade'] != 'N/A' && $student['avg_grade'] >= 50) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                        $att_val = is_numeric($student['avg_attendance']) ? $student['avg_attendance'] : 0;
                                        $att_color = $att_val >= 90 ? 'bg-green-500' : ($att_val >= 75 ? 'bg-yellow-500' : 'bg-red-500');
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="student-avatar text-sm mr-3 shadow-sm" style="background-color: <?= $student['avatar_color'] ?>">
                                                    <?= $student['initials'] ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-800"><?= $student['first_name'] . ' ' . $student['last_name'] ?></p>
                                                    <p class="text-xs text-gray-500">ID: <?= $student['student_id'] ?></p>
                                                    <?php if(!empty($student['medical_conditions'])): ?>
                                                        <span class="text-[10px] bg-red-100 text-red-800 px-1.5 py-0.5 rounded ml-1" title="<?= htmlspecialchars($student['medical_conditions']) ?>">Med</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 bg-blue-50 text-nskblue text-xs font-semibold rounded-full border border-blue-100">
                                                <?= $student['class_name'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if($student['avg_grade'] === 'N/A'): ?>
                                                <span class="text-gray-400 text-sm">-</span>
                                            <?php else: ?>
                                                <span class="font-bold <?= $student['avg_grade'] >= 50 ? 'text-gray-700' : 'text-red-500' ?>"><?= $student['avg_grade'] ?>%</span>
                                                <span class="text-xs text-gray-400 ml-1">(<?= getGradeLetter($student['avg_grade']) ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col items-center justify-center w-24 mx-auto">
                                                <div class="flex justify-between w-full text-xs mb-1">
                                                    <span class="font-semibold text-gray-600"><?= $student['avg_attendance'] ?>%</span>
                                                </div>
                                                <div class="grade-bar-bg">
                                                    <div class="grade-bar-fill <?= $att_color ?>" style="width: <?= $att_val ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button onclick="openViewModal(<?= htmlspecialchars(json_encode($student)) ?>)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($student)) ?>)" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded transition" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-6 py-4 border-t flex flex-col sm:flex-row justify-between items-center">
                        <p class="text-sm text-gray-600 mb-2 sm:mb-0">
                            Showing <span class="font-semibold"><?= $start_index ?></span> to <span class="font-semibold"><?= $end_index ?></span> of <span class="font-semibold"><?= $total_students_count ?></span> students
                        </p>
                        <div class="flex space-x-2">
                             <?php
                                $url_params = [];
                                if ($filter_class_id !== 'all') $url_params['class_id'] = $filter_class_id;
                                
                                $prev_params = $url_params;
                                $prev_params['page'] = $current_page - 1;
                                $prev_url = '?' . http_build_query($prev_params);
                                
                                $next_params = $url_params;
                                $next_params['page'] = $current_page + 1;
                                $next_url = '?' . http_build_query($next_params);
                            ?>
                            
                            <a href="<?= $current_page > 1 ? $prev_url : '#' ?>" 
                               class="pagination-btn px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 <?= $current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50' ?> transition">
                                <i class="fas fa-chevron-left mr-1"></i> Prev
                            </a>
                            
                            <!-- Page Numbers logic simplifies to Prev/Next for now to save space -->
                            
                            <a href="<?= $current_page < $total_pages ? $next_url : '#' ?>" 
                               class="pagination-btn px-3 py-1 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 <?= $current_page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50' ?> transition">
                                Next <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Detail Modal -->
            <div id="viewModal" class="modal">
                <div class="modal-content w-full md:w-2/3 lg:w-1/2">
                    <div class="flex justify-between items-start mb-6 border-b pb-4">
                        <div class="flex items-center space-x-4">
                            <div id="viewAvatar" class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold bg-gray-300">
                                --
                            </div>
                            <div>
                                <h3 id="viewName" class="text-2xl font-bold text-gray-800">Student Name</h3>
                                <p id="viewID" class="text-gray-500 font-medium">STU-12345</p>
                            </div>
                        </div>
                        <button onclick="closeModal('viewModal')" class="text-gray-400 hover:text-gray-600 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Academic Info</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-gray-500">Class:</span> <span id="viewClass" class="font-medium text-gray-900">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Admission No:</span> <span id="viewAdmNo" class="font-medium text-gray-900">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Admission Date:</span> <span id="viewAdmDate" class="font-medium text-gray-900">-</span></div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Personal Info</h4>
                             <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-gray-500">Gender:</span> <span id="viewGender" class="font-medium text-gray-900">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Date of Birth:</span> <span id="viewDOB" class="font-medium text-gray-900">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Nationality:</span> <span id="viewNationality" class="font-medium text-gray-900">-</span></div>
                                <div class="flex justify-between"><span class="text-gray-500">Religion:</span> <span id="viewReligion" class="font-medium text-gray-900">-</span></div>
                            </div>
                        </div>
                        
                         <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Contact Info</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex flex-col"><span class="text-gray-500">Parent/Email:</span> <span id="viewEmail" class="font-medium text-gray-900">-</span></div>
                                <div class="flex flex-col mt-2"><span class="text-gray-500">Phone:</span> <span id="viewPhone" class="font-medium text-gray-900">-</span></div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Medical & Emergency</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex flex-col"><span class="text-gray-500">Emergency Contact:</span> <span id="viewEmergName" class="font-medium text-gray-900">-</span></div>
                                <div class="flex flex-col mt-2"><span class="text-gray-500">Emergency Phone:</span> <span id="viewEmergPhone" class="font-medium text-gray-900 text-red-600">-</span></div>
                                <div class="mt-2 pt-2 border-t text-xs text-red-500 font-semibold">
                                    <i class="fas fa-heartbeat mr-1"></i> <span id="viewMedical">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Student Modal -->
            <div id="studentFormModal" class="modal">
                <div class="modal-content w-full md:w-3/4 lg:w-2/3">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h3 id="formModalTitle" class="text-2xl font-bold text-nsknavy">Add New Student</h3>
                        <button onclick="closeModal('studentFormModal')" class="text-gray-400 hover:text-gray-600 transition">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form id="studentForm" method="POST" action="my_students.php">
                        <input type="hidden" name="form_action" id="formAction" value="add_student">
                        <input type="hidden" name="user_id" id="formUserId" value="">
                        
                        <div class="space-y-6">
                            <!-- Section 1 -->
                            <div>
                                <h4 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-3">Academic Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Assigned Class <span class="text-red-500">*</span></label>
                                        <select name="class_id" id="formClass" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                            <option value="">Select Class</option>
                                            <?php foreach ($teacher_classes_for_form as $class): ?>
                                            <option value="<?= $class['class_id'] ?>"><?= $class['class_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date <span class="text-red-500">*</span></label>
                                        <input type="date" name="admission_date" id="formAdmDate" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <input type="text" value="Active" disabled class="w-full bg-gray-100 border border-gray-300 text-gray-500 rounded-lg p-2.5 cursor-not-allowed">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 2 -->
                            <div>
                                <h4 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-3">Personal Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="first_name" id="formFirstName" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="last_name" id="formLastName" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                                        <input type="date" name="date_of_birth" id="formDOB" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                                        <select name="gender" id="formGender" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                                        <input type="text" name="nationality" id="formNationality" value="Nigerian" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">State of Origin</label>
                                        <input type="text" name="state_of_origin" id="formState" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">LGA</label>
                                        <input type="text" name="lga" id="formLGA" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section 3 -->
                            <div>
                                <h4 class="text-sm uppercase tracking-wide text-gray-500 font-bold mb-3">Contact & Other</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email (Student/Parent) <span class="text-red-500">*</span></label>
                                        <input type="email" name="email" id="formEmail" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                        <input type="tel" name="phone" id="formPhone" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                                        <select name="religion" id="formReligion" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                            <option value="">Select</option>
                                            <option value="Islam">Islam</option>
                                            <option value="Christianity">Christianity</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Medical Conditions (if any)</label>
                                        <input type="text" name="medical_conditions" id="formMedical" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                </div>
                                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 bg-red-50 p-4 rounded-lg border border-red-100">
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name</label>
                                        <input type="text" name="emergency_contact_name" id="formEmergName" class="w-full bg-white border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Phone</label>
                                        <input type="tel" name="emergency_contact_phone" id="formEmergPhone" class="w-full bg-white border border-gray-300 rounded-lg p-2.5 focus:ring-nskblue focus:border-nskblue">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-8 border-t pt-4">
                            <button type="button" onclick="closeModal('studentFormModal')" class="px-5 py-2.5 text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-nsknavy text-white rounded-lg hover:bg-blue-900 transition shadow-lg">
                                <i class="fas fa-save mr-2"></i> Save Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Scripts -->
            <script>
                // Filter Change Handler
                document.getElementById('classFilter').addEventListener('change', function() {
                    let classId = this.value;
                    let url = new URL(window.location.href);
                    if (classId === 'all') {
                        url.searchParams.delete('class_id');
                    } else {
                        url.searchParams.set('class_id', classId);
                    }
                    url.searchParams.delete('page'); // Reset to page 1 on filter change
                    
                    // SPA Navigation for Filter
                    if (window.handleSpaNavigation) {
                        window.handleSpaNavigation(url.toString());
                    } else {
                        window.location.href = url.toString();
                    }
                });

                // Modal Functions
                function openViewModal(student) {
                    document.getElementById('viewName').innerText = student.first_name + ' ' + student.last_name;
                    document.getElementById('viewID').innerText = student.student_id;
                    document.getElementById('viewClass').innerText = student.class_name;
                    document.getElementById('viewAdmNo').innerText = student.admission_number;
                    document.getElementById('viewAdmDate').innerText = student.admission_date;
                    
                    document.getElementById('viewGender').innerText = student.gender;
                    document.getElementById('viewDOB').innerText = student.date_of_birth;
                    document.getElementById('viewNationality').innerText = student.nationality;
                    document.getElementById('viewReligion').innerText = student.religion;
                    
                    document.getElementById('viewEmail').innerText = student.email;
                    document.getElementById('viewPhone').innerText = student.phone || 'N/A';
                    
                    document.getElementById('viewEmergName').innerText = student.emergency_contact_name || 'N/A';
                    document.getElementById('viewEmergPhone').innerText = student.emergency_contact_phone || 'N/A';
                    document.getElementById('viewMedical').innerText = student.medical_conditions || 'None';
                    
                    document.getElementById('viewAvatar').style.backgroundColor = student.avatar_color;
                    document.getElementById('viewAvatar').innerText = student.initials;
                    
                    openModal('viewModal');
                }

                function openAddStudentModal() {
                    document.getElementById('studentForm').reset();
                    document.getElementById('formAction').value = 'add_student';
                    document.getElementById('formUserId').value = '';
                    document.getElementById('formModalTitle').innerText = 'Add New Student';
                    document.getElementById('formAdmDate').valueAsDate = new Date(); // Default today
                    openModal('studentFormModal');
                }

                function openEditModal(student) {
                    document.getElementById('studentForm').reset();
                    document.getElementById('formAction').value = 'edit_student';
                    document.getElementById('formUserId').value = student.user_id;
                    document.getElementById('formModalTitle').innerText = 'Edit Student: ' + student.first_name;
                    
                    // Populate Fields
                    document.getElementById('formClass').value = student.class_id;
                    document.getElementById('formAdmDate').value = student.admission_date;
                    document.getElementById('formFirstName').value = student.first_name;
                    document.getElementById('formLastName').value = student.last_name;
                    document.getElementById('formDOB').value = student.date_of_birth;
                    document.getElementById('formGender').value = student.gender;
                    
                    document.getElementById('formNationality').value = student.nationality;
                    document.getElementById('formState').value = student.state_of_origin;
                    document.getElementById('formLGA').value = student.lga;
                    
                    document.getElementById('formEmail').value = student.email;
                    document.getElementById('formPhone').value = student.phone;
                    document.getElementById('formReligion').value = student.religion;
                    document.getElementById('formMedical').value = student.medical_conditions;
                    
                    document.getElementById('formEmergName').value = student.emergency_contact_name;
                    document.getElementById('formEmergPhone').value = student.emergency_contact_phone;
                    
                    openModal('studentFormModal');
                }

                function openModal(modalId) {
                    document.getElementById(modalId).classList.add('active');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal(modalId) {
                    document.getElementById(modalId).classList.remove('active');
                    document.body.style.overflow = 'auto';
                }

                // Close modal on click outside
                window.onclick = function(event) {
                    if (event.target.classList.contains('modal')) {
                        event.target.classList.remove('active');
                        document.body.style.overflow = 'auto';
                    }
                }

                // Auto hide notifications
                const notification = document.querySelector('.notification');
                if (notification) {
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, 5000);
                }
                
                // Excel Export
                function exportToExcel() {
                    const table = document.querySelector('table');
                    if (!table) return;
                    
                    // Clone table to modify for export (remove Actions column)
                    const clone = table.cloneNode(true);
                    
                    // Remove last column (Actions) from headers and rows
                    const rows = clone.querySelectorAll('tr');
                    rows.forEach(row => {
                        if (row.cells.length > 0) {
                            row.deleteCell(-1);
                        }
                    });
                    
                    const wb = XLSX.utils.table_to_book(clone, {sheet: "Students"});
                    XLSX.writeFile(wb, 'My_Students_List.xlsx');
                }
            </script>

        </div>
<?php if (!$is_ajax): ?>
    </main>
</body>
</html>
<?php endif; ?>