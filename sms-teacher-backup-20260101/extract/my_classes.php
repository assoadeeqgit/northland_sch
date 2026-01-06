<?php
/**
 * My Classes Page
 * Displays a list of all classes taught by the teacher, along with summary statistics, 
 * dynamically fetched from the database.
 */

// Start session and check authentication
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php'; 

class MyClassesData {
    private $conn;
    private $teacher_user_id;
    private $teacher_id;

    public function __construct(Database $database) {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed");
        }
        
        // Get the logged-in teacher's user ID from session
        $this->teacher_user_id = $_SESSION['user_id'];
        $this->setTeacherId();
    }

    /**
     * Sets the internal teacher_id (from the 'teachers' table).
     */
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
    
    /**
     * Fetches the current academic session and term ID required for new timetable entries.
     */
    private function getCurrentAcademicContext(): array {
        $context = ['academic_session_id' => 1, 'term_id' => 3]; // Default fallback
        
        $query = "
            SELECT 
                a.id AS academic_session_id, 
                t.id AS term_id
            FROM academic_sessions a
            JOIN terms t ON a.id = t.academic_session_id
            WHERE a.is_current = 1 AND t.is_current = 1
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $context['academic_session_id'] = (int)$data['academic_session_id'];
                $context['term_id'] = (int)$data['term_id'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching academic context: " . $e->getMessage());
        }
        
        return $context;
    }

    // --- FUNCTION: Assign Class ---
    public function assignNewClass(string $combo_value): array {
        if (!$this->teacher_id) {
            return ['status' => 'error', 'message' => 'Teacher profile not found.'];
        }
        
        if (strpos($combo_value, '-') === false) {
            return ['status' => 'error', 'message' => 'Invalid class selection format.'];
        }
        list($class_id, $subject_id) = explode('-', $combo_value);
        
        $class_id = (int) $class_id;
        $subject_id = (int) $subject_id;

        if ($class_id <= 0 || $subject_id <= 0) {
            return ['status' => 'error', 'message' => 'Invalid Class or Subject ID.'];
        }

        $query = "
            INSERT INTO class_subjects (class_id, subject_id, teacher_id)
            VALUES (:class_id, :subject_id, :teacher_id)
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':subject_id', $subject_id);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->execute();

            return ['status' => 'success', 'message' => 'New class assigned successfully!'];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                 return ['status' => 'error', 'message' => 'Class/Subject combination already exists.'];
            }
            error_log("Database error during class assignment: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error. Check logs.'];
        }
    }
    
    // --- FUNCTION: Delete Class Assignment ---
    public function deleteTeacherClass(int $class_subject_id): bool {
        if (!$this->teacher_id) {
            return false;
        }
        
        // Fetch class and subject IDs associated with class_subject_id to also delete timetable entries
        $query_fetch = "SELECT class_id, subject_id FROM class_subjects WHERE id = :csid AND teacher_id = :tid";
        $stmt_fetch = $this->conn->prepare($query_fetch);
        $stmt_fetch->bindParam(':csid', $class_subject_id);
        $stmt_fetch->bindParam(':tid', $this->teacher_id);
        $stmt_fetch->execute();
        $ids = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($ids) {
            // 1. Delete associated timetable entries for this specific class/subject/teacher
            $query_tt = "
                DELETE FROM timetable 
                WHERE class_id = :class_id 
                AND subject_id = :subject_id
                AND teacher_id = :teacher_id
            ";
            try {
                $stmt_tt = $this->conn->prepare($query_tt);
                $stmt_tt->bindParam(':class_id', $ids['class_id']);
                $stmt_tt->bindParam(':subject_id', $ids['subject_id']);
                $stmt_tt->bindParam(':teacher_id', $this->teacher_id);
                $stmt_tt->execute();
            } catch (PDOException $e) {
                 error_log("Error deleting associated timetable: " . $e->getMessage());
            }
        }

        // 2. Delete the class_subjects assignment
        $query = "
            DELETE FROM class_subjects 
            WHERE id = :class_subject_id AND teacher_id = :teacher_id
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':class_subject_id', $class_subject_id);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting teacher class: " . $e->getMessage());
            return false;
        }
    }

    // --- FUNCTION: Add Timetable Entry ---
    public function addTimetableEntry(int $class_id, int $subject_id, string $day, string $time, string $room): array {
        if (!$this->teacher_id) {
            return ['status' => 'error', 'message' => 'Teacher profile not found.'];
        }
        
        // 1. Get required context
        $context = $this->getCurrentAcademicContext();
        $academic_session_id = $context['academic_session_id'];
        $term_id = $context['term_id'];
        
        // 2. Calculate End Time (Assuming 1 hour duration, adjust as needed)
        $end_time = date('H:i:s', strtotime('+1 hour', strtotime($time)));

        // 3. Prepare and Execute Insert
        $query = "
            INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room, academic_session_id, term_id)
            VALUES (:class_id, :subject_id, :teacher_id, :day_of_week, :start_time, :end_time, :room, :academic_session_id, :term_id)
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':class_id', $class_id);
            $stmt->bindParam(':subject_id', $subject_id);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->bindParam(':day_of_week', $day);
            $stmt->bindParam(':start_time', $time);
            $stmt->bindParam(':end_time', $end_time);
            $stmt->bindParam(':room', $room);
            $stmt->bindParam(':academic_session_id', $academic_session_id);
            $stmt->bindParam(':term_id', $term_id);
            $stmt->execute();
            
            return ['status' => 'success', 'message' => "Schedule added successfully! ({$day} @ {$time} - {$end_time})"];
        } catch (PDOException $e) {
             if ($e->getCode() === '23000') {
                 return ['status' => 'error', 'message' => 'Schedule conflict: A schedule already exists for this day/time/class/subject.'];
            }
            error_log("Database error during scheduling: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Database error during scheduling. Check logs.'];
        }
    }
    
    public function getTeacherProfile(): array {
        $profile = ['first_name' => 'N/A', 'last_name' => 'N/A', 'initials' => 'NN', 'specialization' => 'N/A'];
        $query = "SELECT u.first_name, u.last_name, tp.subject_specialization FROM users u LEFT JOIN teacher_profiles tp ON u.id = tp.user_id WHERE u.id = :teacher_user_id";
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
    
    private function getClassMetrics(int $class_id, int $subject_id): array {
        $metrics = ['avg_grade' => 'N/A', 'avg_attendance' => 'N/A', 'assignments_completed' => rand(3, 8)];
        $today = date('Y-m-d');
        
        // 1. Calculate Average Grade
        $query_grade = "SELECT IFNULL(AVG(r.marks_obtained / e.total_marks * 100), 0) AS average_grade FROM exams e JOIN results r ON e.id = r.exam_id WHERE e.class_id = :class_id AND e.subject_id = :subject_id AND e.created_by = :teacher_user_id";
        $stmt_grade = $this->conn->prepare($query_grade);
        $stmt_grade->bindParam(':class_id', $class_id);
        $stmt_grade->bindParam(':subject_id', $subject_id);
        $stmt_grade->bindParam(':teacher_user_id', $this->teacher_user_id);
        $stmt_grade->execute();
        $avg_grade = $stmt_grade->fetchColumn();
        $metrics['avg_grade'] = $avg_grade > 0 ? round($avg_grade) : 'N/A';
        
        // 2. Calculate Average Attendance
        $query_att = "SELECT SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count, COUNT(id) AS total_records FROM attendance WHERE class_id = :class_id AND attendance_date <= :today";
        $stmt_att = $this->conn->prepare($query_att);
        $stmt_att->bindParam(':class_id', $class_id);
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
    
    public function getTeacherClasses(): array {
        $classes = [];
        if (!$this->teacher_id) { 
            return $classes; 
        }

        $query = "SELECT c.id AS class_id, c.class_name, c.class_code, s.id AS subject_id, s.subject_name, cs.id AS class_subject_id, (SELECT COUNT(stu.id) FROM students stu WHERE stu.class_id = c.id) AS student_count FROM class_subjects cs JOIN classes c ON cs.class_id = c.id JOIN subjects s ON cs.subject_id = s.id WHERE cs.teacher_id = :teacher_id ORDER BY c.class_name, s.subject_name";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->execute();
            $classes = $stmt->fetchAll();

            $now_time = time();

            foreach ($classes as &$class) {
                $metrics = $this->getClassMetrics($class['class_id'], $class['subject_id']);
                $class = array_merge($class, $metrics);

                // Find Next Class Time and Schedule
                $query_tt = "SELECT day_of_week, start_time, room FROM timetable WHERE class_id = :class_id AND teacher_id = :teacher_id AND subject_id = :subject_id ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
                $stmt_tt = $this->conn->prepare($query_tt);
                $stmt_tt->bindParam(':class_id', $class['class_id']);
                $stmt_tt->bindParam(':teacher_id', $this->teacher_id);
                $stmt_tt->bindParam(':subject_id', $class['subject_id']); 
                $stmt_tt->execute();
                $timetable_entries = $stmt_tt->fetchAll();
                
                $class['schedule_days'] = [];
                $class['next_class'] = 'No fixed schedule';
                $class['room'] = $timetable_entries[0]['room'] ?? 'TBD'; 
                
                if (!empty($timetable_entries)) {
                    $next_class_timestamp = PHP_INT_MAX;
                    $found_next_class = false;

                    foreach ($timetable_entries as $entry) {
                        $class['schedule_days'][] = substr($entry['day_of_week'], 0, 3);
                        
                        // Calculate the timestamp for this class's next occurrence
                        $next_occurrence = strtotime('next ' . $entry['day_of_week'] . ' ' . $entry['start_time']);
                        
                        // If the "next day" time is in the past compared to right now, move it to the week after.
                        if ($next_occurrence < $now_time) {
                             $next_occurrence = strtotime('+1 week', $next_occurrence);
                        }
                        
                        // Find the earliest upcoming class
                        if ($next_occurrence < $next_class_timestamp) {
                            $next_class_timestamp = $next_occurrence;
                            $class['next_class'] = date('l, g:i A', $next_occurrence);
                            $found_next_class = true;
                        }
                    }
                    
                    $class['schedule_days'] = implode(', ', array_unique($class['schedule_days']));

                } else {
                     $class['schedule_days'] = 'No fixed schedule';
                }

                $class['status'] = (!empty($timetable_entries) || $class['student_count'] > 0) ? 'Active' : 'Upcoming'; 
                $class['status_color'] = ($class['status'] === 'Active') ? 'nskgreen' : 'nskblue';
                $class['status_bg'] = ($class['status'] === 'Active') ? 'bg-green-100' : 'bg-blue-100';
            }
        } catch (PDOException $e) { 
            error_log("Error fetching teacher classes: " . $e->getMessage()); 
        }
        return $classes;
    }
    
    public function getAggregateStats(array $classes): array {
        $total_classes = count($classes);
        $total_students = 0;
        $total_avg_grade = 0;
        $total_avg_attendance = 0;

        foreach ($classes as $class) {
            $total_students += (int)$class['student_count'];
            
            if (is_numeric($class['avg_grade'])) {
                $total_avg_grade += $class['avg_grade'];
            }
            if (is_numeric($class['avg_attendance'])) {
                $total_avg_attendance += $class['avg_attendance'];
            }
        }

        $valid_classes_grade = count(array_filter($classes, fn($c) => is_numeric($c['avg_grade'])));
        $valid_classes_att = count(array_filter($classes, fn($c) => is_numeric($c['avg_attendance'])));
        
        $overall_avg_grade = $valid_classes_grade > 0 ? round($total_avg_grade / $valid_classes_grade) : 0;
        $overall_avg_attendance = $valid_classes_att > 0 ? round($total_avg_attendance / $valid_classes_att) : 0;

        return [
            'total_classes' => $total_classes,
            'total_students' => $total_students,
            'avg_attendance' => $overall_avg_attendance,
            'overall_avg_grade' => $overall_avg_grade
        ];
    }
    
    public function getAllAvailableClasses(): array {
        $query = "SELECT c.id AS class_id, c.class_name, c.class_code, s.id AS subject_id, s.subject_name FROM classes c JOIN subjects s LEFT JOIN class_subjects cs ON cs.class_id = c.id AND cs.subject_id = s.id AND cs.teacher_id = :teacher_id WHERE cs.id IS NULL ORDER BY c.class_name, s.subject_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $this->teacher_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

// --- Execution ---
try {
    $database = new Database();
    $classes_data = new MyClassesData($database);

    // Handle API calls (Form Submission / Deletion / Scheduling)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        $response = ['status' => 'error', 'message' => 'Invalid action or data.'];
        
        if ($_POST['action'] === 'add_class') {
            $combo_value = $_POST['class_subject_combo'] ?? null;
            $response = $classes_data->assignNewClass($combo_value);
        
        } elseif ($_POST['action'] === 'delete_class' && isset($_POST['class_subject_id']) && is_numeric($_POST['class_subject_id'])) {
            $class_subject_id_to_delete = (int)$_POST['class_subject_id'];
            
            if ($classes_data->deleteTeacherClass($class_subject_id_to_delete)) {
                $response = ['status' => 'success', 'message' => 'Class assignment and related schedules removed successfully!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Could not remove class assignment.'];
            }
        } elseif ($_POST['action'] === 'add_schedule') {
            $class_id = (int)($_POST['schedule_class_id'] ?? 0);
            $subject_id = (int)($_POST['schedule_subject_id'] ?? 0);
            $day = $_POST['day_of_week'] ?? '';
            $time = $_POST['start_time'] ?? '';
            $room = $_POST['room'] ?? '';
            
            if ($class_id > 0 && $subject_id > 0 && !empty($day) && !empty($time)) {
                 $response = $classes_data->addTimetableEntry($class_id, $subject_id, $day, $time, $room);
            } else {
                 $response = ['status' => 'error', 'message' => 'Missing required schedule fields.'];
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Fetch data for rendering
    $profile = $classes_data->getTeacherProfile();
    $teacher_classes = $classes_data->getTeacherClasses();
    $stats = $classes_data->getAggregateStats($teacher_classes);
    $available_classes = $classes_data->getAllAvailableClasses();

} catch (Exception $e) {
    // Handle errors gracefully
    error_log("My Classes page error: " . $e->getMessage());
    
    // If it's an authentication error, redirect to login
    if (strpos($e->getMessage(), 'Teacher profile not found') !== false) {
        session_destroy();
        header("Location: login-form.php?error=auth");
        exit();
    }
    
    // For other errors, show a user-friendly message
    $error_message = "An error occurred while loading the classes page. Please try again later.";
    die($error_message);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
</head>
<body class="flex">
    <main class="main-content">
        <header class="desktop-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button class="mobile-menu-toggle md:hidden text-nsknavy mr-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">My Classes</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search classes..." class="bg-transparent outline-none w-32 md:w-64">
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
                            <p class="text-sm font-semibold text-nsknavy"><?= $profile['first_name'] . ' ' . $profile['last_name'] ?></p>
                            <p class="text-xs text-gray-600"><?= $profile['specialization'] ?> Teacher</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <header class="mobile-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center space-x-4">
                    <button class="mobile-menu-toggle text-nsknavy mr-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">My Classes</h1>
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
            <div class="bg-white rounded-xl shadow-md p-4 md:p-6 mb-4 md:mb-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 md:mb-6 space-y-3 md:space-y-0">
                    <h2 class="text-lg md:text-xl font-bold text-nsknavy">My Classes (<?= $stats['total_classes'] ?>)</h2>
                    <div class="flex space-x-3">
                        <button id="openFilterModal" class="bg-nskblue text-white px-3 py-2 rounded-lg hover:bg-nsknavy transition text-sm">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <button id="openAddClassModal" class="bg-nskgreen text-white px-3 py-2 rounded-lg hover:bg-green-600 transition text-sm">
                            <i class="fas fa-plus mr-2"></i>Assign Class
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6" id="classGrid">
                    
                    <?php if (count($teacher_classes) > 0): ?>
                        <?php foreach ($teacher_classes as $class): ?>
                            <?php 
                                $grade_color = $class['avg_grade'] >= 85 ? 'nskgreen' : ($class['avg_grade'] >= 75 ? 'nskgold' : 'nskred');
                                $attendance_color = $class['avg_attendance'] >= 90 ? 'nskblue' : 'nskgold';
                                $grade_text = is_numeric($class['avg_grade']) ? $class['avg_grade'] . '%' : 'N/A';
                                $attendance_text = is_numeric($class['avg_attendance']) ? $class['avg_attendance'] . '%' : 'N/A';
                                $attendance_link_class = ($class['status'] === 'Upcoming') ? 'bg-gray-400 hover:bg-gray-400 cursor-not-allowed' : 'bg-nskgreen hover:bg-green-600';
                                
                                // Data attributes for scheduling and deletion
                                $class_subject_id = $class['class_subject_id'];
                                $class_name_subject = htmlspecialchars($class['class_name'] . ' - ' . $class['subject_name']);
                            ?>
                            <div class="dashboard-card border border-gray-200 rounded-lg p-4 hover:shadow-lg" 
                                data-class-subject-id="<?= $class_subject_id ?>"
                                data-class-id="<?= $class['class_id'] ?>"
                                data-subject-id="<?= $class['subject_id'] ?>"
                                data-class-name="<?= htmlspecialchars($class['class_name']) ?>"
                                data-status="<?= $class['status'] ?>"
                            >
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-nsknavy text-sm md:text-base"><?= $class_name_subject ?></h3>
                                        <p class="text-xs md:text-sm text-gray-600"><?= htmlspecialchars($class['room'] ?? 'No Room') ?> • <?= htmlspecialchars($class['schedule_days'] ?? 'No Schedule') ?></p>
                                    </div>
                                    <span class="<?= $class['status_bg'] ?> text-<?= $class['status_color'] ?> px-2 py-1 rounded-full text-xs font-semibold"><?= htmlspecialchars($class['status']) ?></span>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Students:</span>
                                        <span class="font-semibold"><?= htmlspecialchars($class['student_count']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Average Grade:</span>
                                        <span class="font-semibold text-<?= $grade_color ?>"><?= $grade_text ?></span>
                                    </div>
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Attendance:</span>
                                        <span class="font-semibold text-<?= $attendance_color ?>"><?= $attendance_text ?></span>
                                    </div>
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Next Class:</span>
                                        <span class="font-semibold text-nskgold"><?= htmlspecialchars($class['next_class']) ?></span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <button class="view-class-btn bg-nskblue text-white py-2 px-1 rounded text-xs md:text-sm hover:bg-nsknavy transition" 
                                            data-class-id="<?= $class['class_id'] ?>" 
                                            data-class-subject="<?= $class_name_subject ?>"
                                            data-class-details='<?= json_encode($class) ?>' >
                                        Details
                                    </button>
                                    <button class="schedule-class-btn bg-nskgold text-white py-2 px-1 rounded text-xs md:text-sm hover:bg-amber-600 transition" 
                                            data-class-id="<?= $class['class_id'] ?>" 
                                            data-subject-id="<?= $class['subject_id'] ?>"
                                            data-class-subject="<?= $class_name_subject ?>">
                                        <i class="fas fa-calendar-alt"></i> Schedule
                                    </button>
                                    <button class="delete-class-btn bg-nskred text-white py-2 px-1 rounded text-xs md:text-sm hover:bg-red-700 transition" 
                                            data-class-subject-id="<?= $class_subject_id ?>" 
                                            data-class-subject="<?= $class_name_subject ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full p-6 text-center text-gray-600 bg-gray-100 rounded-lg">
                            <i class="fas fa-exclamation-circle mr-2"></i> You are not currently assigned to any classes.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Class Statistics Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl md:text-3xl font-bold text-nskblue mb-2"><?= $stats['total_classes'] ?></div>
                        <p class="text-sm text-gray-600">Total Classes</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl md:text-3xl font-bold text-nskgreen mb-2"><?= $stats['total_students'] ?></div>
                        <p class="text-sm text-gray-600">Active Students</p>
                    </div>
                    <div class="text-center p-4 bg-amber-50 rounded-lg">
                        <div class="text-2xl md:text-3xl font-bold text-nskgold mb-2"><?= $stats['avg_attendance'] ?>%</div>
                        <p class="text-sm text-gray-600">Average Attendance</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-2xl md:text-3xl font-bold text-purple-600 mb-2"><?= $stats['overall_avg_grade'] ?>%</div>
                        <p class="text-sm text-gray-600">Overall Average Grade</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Include sidebar at the end of body -->
    <?php include 'sidebar.php'; ?>

    <!-- Student Details Modal -->
    <div id="classModal" class="modal">
        <div class="modal-content w-full max-w-4xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy" id="modalClassTitle">Class Details</h3>
                <button id="closeClassModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-nskblue mb-2">Class Information</h4>
                        <p class="text-sm"><strong>Subject:</strong> <span id="modalSubject"></span></p>
                        <p class="text-sm"><strong>Room:</strong> <span id="modalRoom"></span></p>
                        <p class="text-sm"><strong>Schedule:</strong> <span id="modalSchedule"></span></p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-nskgreen mb-2">Performance</h4>
                        <p class="text-sm"><strong>Average Grade:</strong> <span id="modalAvgGrade"></span></p>
                        <p class="text-sm"><strong>Attendance Rate:</strong> <span id="modalAttRate"></span></p>
                        <p class="text-sm"><strong>Assignments:</strong> <span id="modalAssignments"></span></p>
                    </div>
                    <div class="bg-amber-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-nskgold mb-2">Details</h4>
                        <p class="text-sm"><strong>Total Students:</strong> <span id="modalTotalStudents"></span></p>
                        <p class="text-sm"><strong>Class Code:</strong> <span id="modalClassCode"></span></p>
                        <p class="text-sm"><strong>Status:</strong> <span id="modalStatus"></span></p>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold text-nsknavy mb-3">Recent Activities (Data Mock)</h4>
                    <p class="text-sm text-gray-700 p-2 bg-gray-50 rounded">Activity details need a dedicated API/endpoint but are ready to be implemented.</p>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button id="closeModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Close</button>
                    <button class="px-4 py-2 bg-nskblue text-white rounded-lg text-sm hover:bg-nsknavy transition">View Full Details</button>
                </div>
            </div>
        </div>
    </div>

    <div id="filterModal" class="modal">
        <div class="modal-content w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-nsknavy">Filter Classes</h3>
                <button id="closeFilterModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="classFilterForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filterStatus" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                        <option value="all">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Upcoming">Upcoming</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Students</label>
                    <input type="number" id="filterStudents" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="e.g., 20" min="0">
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="resetFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Clear Filters</button>
                    <button type="submit" class="px-4 py-2 bg-nskblue text-white rounded-lg text-sm hover:bg-nsknavy transition">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addClassModal" class="modal">
        <div class="modal-content w-full max-w-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-nsknavy">Assign New Class/Subject</h3>
                <button id="closeAddClassModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="addClassForm" class="space-y-4">
                <input type="hidden" name="action" value="add_class">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class & Subject Combination</label>
                    <select name="class_subject_combo" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" required>
                        <option value="">Select Class and Subject</option>
                        <?php foreach ($available_classes as $combo): ?>
                            <option value="<?= $combo['class_id'] ?>-<?= $combo['subject_id'] ?>">
                                <?= htmlspecialchars($combo['class_name'] . ' - ' . $combo['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($available_classes)): ?>
                        <p class="text-xs text-nskred mt-1">No unassigned classes available.</p>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancelAddClass" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-nskgreen text-white rounded-lg text-sm hover:bg-green-600 transition" <?= empty($available_classes) ? 'disabled' : '' ?>>
                        Assign Class
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="scheduleClassModal" class="modal">
        <div class="modal-content w-full max-w-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-nsknavy">Schedule Class: <span id="scheduleClassTitle"></span></h3>
                <button id="closeScheduleClassModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="scheduleClassForm" class="space-y-4">
                <input type="hidden" name="action" value="add_schedule">
                <input type="hidden" name="schedule_class_id" id="scheduleClassId">
                <input type="hidden" name="schedule_subject_id" id="scheduleSubjectId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Day of Week</label>
                        <select name="day_of_week" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                        <input type="time" name="start_time" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" required>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Room/Location</label>
                    <input type="text" name="room" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="e.g., Block A, Room 101" required>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancelSchedule" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-nskgold text-white rounded-lg text-sm hover:bg-amber-600 transition">
                        Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-nskred">Confirm Deletion</h3>
                <button id="closeDeleteConfirmModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-gray-700 mb-4">Are you sure you want to remove the class assignment for **<span id="deleteClassName" class="font-semibold text-nsknavy"></span>**?</p>
            <p class="text-sm text-nskred">This action removes the class from your roster and any associated schedules.</p>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" id="cancelDelete" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Cancel</button>
                <button type="button" id="confirmDelete" data-class-subject-id="" class="px-4 py-2 bg-nskred text-white rounded-lg text-sm hover:bg-red-700 transition">
                    Yes, Remove Class
                </button>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const classGrid = document.getElementById('classGrid');
        
        // Modals
        const classModal = document.getElementById('classModal');
        const filterModal = document.getElementById('filterModal');
        const addClassModal = document.getElementById('addClassModal');
        const scheduleClassModal = document.getElementById('scheduleClassModal');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        
        // Delete Modal Elements
        const deleteClassNameSpan = document.getElementById('deleteClassName');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        // Schedule Modal Elements
        const scheduleClassTitle = document.getElementById('scheduleClassTitle');
        const scheduleClassId = document.getElementById('scheduleClassId');
        const scheduleSubjectId = document.getElementById('scheduleSubjectId');
        const scheduleClassForm = document.getElementById('scheduleClassForm');


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
            }, 3000);
        }

        function openModal(modal) {
            modal.classList.add('active');
        }

        function closeModalFunc(modal) {
            modal.classList.remove('active');
        }
        
        // --- API & ACTION HANDLERS ---
        
        function handleFormSubmit(e, modalElement) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            fetch('my_classes.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                closeModalFunc(modalElement);
                if (data.status === 'success') {
                    showNotification(data.message + ' Reloading data...', 'success');
                    setTimeout(() => window.location.reload(), 1500); 
                } else {
                    showNotification(data.message || 'Error processing request.', 'error');
                }
            })
            .catch(error => {
                closeModalFunc(modalElement);
                showNotification('Network error.', 'error');
                console.error('Error:', error);
            });
        }
        
        // --- DELETE FUNCTIONALITY ---
        
        function openDeleteConfirmModal(classSubjectId, className) {
            deleteClassNameSpan.textContent = className;
            confirmDeleteBtn.setAttribute('data-class-subject-id', classSubjectId);
            openModal(deleteConfirmModal);
        }

        function handleDeleteClassBtn(e) {
            const button = e.currentTarget;
            const classSubjectId = button.getAttribute('data-class-subject-id');
            const className = button.getAttribute('data-class-subject');

            openDeleteConfirmModal(classSubjectId, className);
        }
        
        function handleConfirmDelete(e) {
            const classSubjectId = e.currentTarget.getAttribute('data-class-subject-id');
            
            closeModalFunc(deleteConfirmModal);

            const formData = new FormData();
            formData.append('action', 'delete_class');
            formData.append('class_subject_id', classSubjectId);
            
            fetch('my_classes.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message + ' Reloading data...', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Error deleting class.', 'error');
                }
            })
            .catch(error => {
                showNotification('Network error during class deletion.', 'error');
                console.error('Error:', error);
            });
        }
        
        // --- SCHEDULE FUNCTIONALITY ---
        
        function handleScheduleClassBtn(e) {
            const button = e.currentTarget;
            const classId = button.getAttribute('data-class-id');
            const subjectId = button.getAttribute('data-subject-id');
            const className = button.getAttribute('data-class-subject');
            
            scheduleClassTitle.textContent = className;
            scheduleClassId.value = classId;
            scheduleSubjectId.value = subjectId;
            
            // Clear previous form data for a fresh start
            scheduleClassForm.reset(); 
            openModal(scheduleClassModal);
        }


        // --- EVENT ATTACHMENT ---
        
        function attachDynamicListeners() {
            document.querySelectorAll('.delete-class-btn').forEach(btn => {
                btn.removeEventListener('click', handleDeleteClassBtn);
                btn.addEventListener('click', handleDeleteClassBtn);
            });
            document.querySelectorAll('.schedule-class-btn').forEach(btn => {
                btn.removeEventListener('click', handleScheduleClassBtn);
                btn.addEventListener('click', handleScheduleClassBtn);
            });
        }


        // --- INITIALIZATION AND GLOBAL LISTENERS ---
        
        document.addEventListener('DOMContentLoaded', () => {
             // Sidebar UI toggle listeners
             const sidebarToggle = document.getElementById('sidebarToggle');
             const mobileMenuToggle = document.getElementById('mobileMenuToggle');
             const mobileOverlay = document.getElementById('mobileOverlay');
             
             const toggleMobileMenu = () => {
                sidebar.classList.toggle('mobile-show');
                mobileOverlay.classList.toggle('active');
            }
             if(sidebarToggle) sidebarToggle.addEventListener('click', () => {
                 sidebar.classList.toggle('collapsed');
                 mainContent.classList.toggle('expanded');
                 document.querySelectorAll('.sidebar-text').forEach(el => el.classList.toggle('hidden'));
             });
             if(mobileMenuToggle) mobileMenuToggle.addEventListener('click', toggleMobileMenu);
             if(mobileOverlay) mobileOverlay.addEventListener('click', toggleMobileMenu);

            attachDynamicListeners();
            console.log('My Classes page loaded with fixes for deletion and scheduling.');
        });

        // Global Modal Open Buttons
        document.getElementById('openFilterModal').addEventListener('click', () => openModal(filterModal));
        document.getElementById('openAddClassModal').addEventListener('click', () => openModal(addClassModal));

        // Global Modal Close Buttons (Attach directly to IDs)
        document.getElementById('closeClassModal').addEventListener('click', () => closeModalFunc(classModal));
        document.getElementById('closeModal').addEventListener('click', () => closeModalFunc(classModal));
        document.getElementById('closeFilterModal').addEventListener('click', () => closeModalFunc(filterModal));
        document.getElementById('closeAddClassModal').addEventListener('click', () => closeModalFunc(addClassModal));
        document.getElementById('cancelAddClass').addEventListener('click', () => closeModalFunc(addClassModal));
        document.getElementById('closeDeleteConfirmModal').addEventListener('click', () => closeModalFunc(deleteConfirmModal));
        document.getElementById('cancelDelete').addEventListener('click', () => closeModalFunc(deleteConfirmModal));
        document.getElementById('closeScheduleClassModal').addEventListener('click', () => closeModalFunc(scheduleClassModal));
        document.getElementById('cancelSchedule').addEventListener('click', () => closeModalFunc(scheduleClassModal));

        // Delete Confirmation
        confirmDeleteBtn.addEventListener('click', handleConfirmDelete);
        
        // Form Submissions
        document.getElementById('classFilterForm').addEventListener('submit', (e) => handleFormSubmit(e, filterModal));
        document.getElementById('addClassForm').addEventListener('submit', (e) => handleFormSubmit(e, addClassModal));
        scheduleClassForm.addEventListener('submit', (e) => handleFormSubmit(e, scheduleClassModal));
        
        // Custom Filter Reset (needs manual handling)
        document.getElementById('resetFilter').addEventListener('click', () => {
             document.getElementById('filterStatus').value = 'all';
             document.getElementById('filterStudents').value = '';
             classGrid.querySelectorAll('.dashboard-card').forEach(card => card.style.display = 'block');
             closeModalFunc(filterModal);
             showNotification('Filters cleared.', 'success');
        });

        // View Details handler
        document.querySelectorAll('.view-class-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const button = e.currentTarget;
                const classSubject = button.getAttribute('data-class-subject');
                const classData = JSON.parse(button.getAttribute('data-class-details'));
                
                document.getElementById('modalClassTitle').textContent = classSubject;
                document.getElementById('modalSubject').textContent = classData.subject_name;
                document.getElementById('modalRoom').textContent = classData.room || classData.class_code;
                document.getElementById('modalSchedule').textContent = classData.schedule_days || 'No fixed schedule';
                document.getElementById('modalAvgGrade').textContent = classData.avg_grade + (isFinite(classData.avg_grade) && classData.avg_grade !== 'N/A' ? '%' : '');
                document.getElementById('modalAttRate').textContent = classData.avg_attendance + (isFinite(classData.avg_attendance) && classData.avg_attendance !== 'N/A' ? '%' : '');
                document.getElementById('modalTotalStudents').textContent = classData.student_count + ' students';
                document.getElementById('modalClassCode').textContent = classData.class_code;
                document.getElementById('modalStatus').textContent = classData.status;
                document.getElementById('modalAssignments').textContent = (classData.assignments_completed || 'N/A') + ' completed (Simulated)';
                
                openModal(classModal);
            });
        });

        // Close modal when clicking overlay
        window.addEventListener('click', (e) => {
            if (e.target === classModal) closeModalFunc(classModal);
            if (e.target === filterModal) closeModalFunc(filterModal);
            if (e.target === addClassModal) closeModalFunc(addClassModal);
            if (e.target === scheduleClassModal) closeModalFunc(scheduleClassModal);
            if (e.target === deleteConfirmModal) closeModalFunc(deleteConfirmModal);
        });
    </script>
</body>
</html>