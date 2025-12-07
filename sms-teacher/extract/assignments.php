<?php
/**
 * Assignments Page
 * Handles fetching, creating, updating, and deleting assignments directly from the database.
 * Now with proper session handling and dynamic teacher identification.
 */

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php'; 


class AssignmentsData {
    private $conn;
    private $teacher_user_id;
    private $teacher_id;
    private $error_message = null;

    public function __construct(Database $database) {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            exit(); 
        }
        $this->teacher_user_id = $_SESSION['user_id'];
        $this->setTeacherId();
    }
    
    private function setTeacherId() {
        $query = "SELECT id FROM teachers WHERE user_id = :teacher_user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
        $stmt->execute();
        $this->teacher_id = $stmt->fetchColumn();
        
        if (!$this->teacher_id) {
            error_log("Teacher ID not found for user ID: " . $this->teacher_user_id);
        }
    }
    
    public function getTeacherProfile(): array {
        $profile = [
            'first_name' => 'N/A', 
            'last_name' => 'N/A', 
            'initials' => 'NN', 
            'specialization' => 'N/A',
            'teacher_id' => 'N/A'
        ];
        
        $query = "
            SELECT 
                u.first_name, u.last_name, tp.subject_specialization, t.teacher_id
            FROM users u
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            LEFT JOIN teachers t ON u.id = t.user_id
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
                $profile['initials'] = strtoupper(
                    substr($data['first_name'], 0, 1) . 
                    substr($data['last_name'], 0, 1)
                );
                $profile['specialization'] = $data['subject_specialization'] ?: 'Teacher';
                $profile['teacher_id'] = $data['teacher_id'] ?: 'N/A';
            }
        } catch (PDOException $e) {
            error_log("Error fetching profile: " . $e->getMessage());
        }

        return $profile;
    }

    /**
     * Fetches classes the teacher is responsible for, joined with the subject.
     */
    public function getTeacherClassesWithSubjects(): array {
        $classes = [];
        if (!$this->teacher_id) return $classes;

        $query = "
            SELECT 
                c.id AS class_id,
                c.class_name,
                s.id AS subject_id,
                s.subject_name,
                s.subject_code
            FROM class_subjects cs
            JOIN classes c ON cs.class_id = c.id
            JOIN subjects s ON cs.subject_id = s.id
            WHERE cs.teacher_id = :teacher_id
            ORDER BY c.class_name, s.subject_name
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
     * Calculates the assignment status and simulated metrics.
     */
    private function calculateStatus(string $dueDate, int $classId, PDO $conn): array {
        $today = new DateTime();
        $due = new DateTime($dueDate);

        // Get total students in the class
        $query_count = "SELECT COUNT(id) FROM students WHERE class_id = :class_id";
        $stmt_count = $conn->prepare($query_count);
        $stmt_count->bindParam(':class_id', $classId);
        $stmt_count->execute();
        $total_students = (int)$stmt_count->fetchColumn();

        // Simulated submissions and grading needed
        // For demonstration, submissions are tied to total students.
        $submissions = min($total_students, rand(0, $total_students)); 
        $grade_needed = $submissions > 0 ? rand(0, min(5, $submissions)) : 0; 
        
        $status = 'Upcoming';
        if ($due < $today) {
            $status = ($submissions == $total_students && $grade_needed == 0) ? 'Completed' : 'Overdue';
        } elseif ($due == $today || $due > $today && $due < (clone $today)->modify('+7 days')) {
            $status = 'Active';
        }
        
        $progress = $total_students > 0 ? round(($submissions / $total_students) * 100) : 0;
        
        $data = [
            'status' => $status,
            'submissions' => $submissions,
            'total_students' => $total_students,
            'grade_needed' => $grade_needed,
            'progress' => $progress
        ];

        // Assign colors and display text based on final status
        switch ($status) {
            case 'Completed':
                $data['color_class'] = 'assignment-completed';
                $data['status_text_class'] = 'bg-gray-100 text-gray-600';
                break;
            case 'Overdue':
                $data['color_class'] = 'assignment-overdue';
                $data['status_text_class'] = 'bg-red-100 text-nskred';
                break;
            case 'Active':
                $data['color_class'] = 'assignment-active';
                $data['status_text_class'] = 'bg-green-100 text-nskgreen';
                break;
            case 'Upcoming':
            default:
                $data['color_class'] = 'assignment-upcoming';
                $data['status_text_class'] = 'bg-blue-100 text-nskblue';
                break;
        }
        return $data;
    }

    /**
     * Fetches assignments based on filter criteria with pagination.
     */
    public function getAssignments(string $status_filter, string $class_id_filter, int $page = 1, int $per_page = 9): array {
        $assignments = [];
        if (!$this->teacher_id) return $assignments;
        
        $where = "a.teacher_id = :teacher_id";
        $params = [':teacher_id' => $this->teacher_id];

        // Class Filter
        if (is_numeric($class_id_filter)) {
            $where .= " AND a.class_id = :class_id_filter";
            $params[':class_id_filter'] = $class_id_filter;
        }
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $per_page;
        
        $query = "
            SELECT 
                a.*,
                c.class_name,
                c.class_code,
                s.subject_name,
                s.subject_code
            FROM assignments a
            JOIN classes c ON a.class_id = c.id
            JOIN subjects s ON a.subject_id = s.id
            WHERE {$where}
            ORDER BY a.due_date DESC
            LIMIT :limit OFFSET :offset
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            if (is_numeric($class_id_filter)) {
                $stmt->bindParam(':class_id_filter', $class_id_filter);
            }
            $stmt->execute();
            $raw_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($raw_assignments as $assignment) {
                // Calculate status and simulated metrics
                $status_data = $this->calculateStatus($assignment['due_date'], $assignment['class_id'], $this->conn);
                $assignment = array_merge($assignment, $status_data);
                
                // Status Filter (applied in PHP since status is calculated)
                if ($status_filter === 'all' || strtolower($assignment['status']) === strtolower($status_filter)) {
                    $assignments[] = $assignment;
                }
            }

        } catch (PDOException $e) {
            error_log("Error fetching assignments: " . $e->getMessage());
        }
        return $assignments;
    }
    
    /**
     * Get total count of assignments for pagination
     */
    public function getTotalAssignmentsCount(string $status_filter, string $class_id_filter): int {
        if (!$this->teacher_id) return 0;
        
        $where = "a.teacher_id = :teacher_id";
        $params = [':teacher_id' => $this->teacher_id];

        // Class Filter
        if (is_numeric($class_id_filter)) {
            $where .= " AND a.class_id = :class_id_filter";
            $params[':class_id_filter'] = $class_id_filter;
        }
        
        $query = "
            SELECT COUNT(*) as total_count
            FROM assignments a
            WHERE {$where}
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            if (is_numeric($class_id_filter)) {
                $stmt->bindParam(':class_id_filter', $class_id_filter);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting assignments: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Creates a new assignment.
     */
    public function createAssignment(array $data): bool {
        if (!$this->teacher_id) {
            $this->error_message = "Teacher ID not found.";
            return false;
        }

        // Split class_subject_id to get class_id and subject_id
        list($class_id, $subject_id) = explode('_', $data['class_subject_id']);
        
        $query = "
            INSERT INTO assignments 
            (teacher_id, class_id, subject_id, title, description, due_date, total_points, type, allow_late_submission)
            VALUES (:teacher_id, :class_id, :subject_id, :title, :description, :due_date, :total_points, :type, :allow_late)
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':teacher_id' => $this->teacher_id,
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':title' => $data['title'],
                ':description' => $data['description'] ?: null,
                ':due_date' => $data['due_date'],
                ':total_points' => $data['total_points'],
                ':type' => $data['assignment_type'],
                ':allow_late' => isset($data['allow_late']) ? 1 : 0
            ]);
        } catch (PDOException $e) {
            $this->error_message = "Database Error: " . $e->getMessage();
            error_log("Assignment Creation Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Updates an existing assignment.
     */
    public function updateAssignment(int $assignment_id, array $data): bool {
        if (!$this->teacher_id) {
            $this->error_message = "Teacher ID not found.";
            return false;
        }
        
        list($class_id, $subject_id) = explode('_', $data['class_subject_id']);

        $query = "
            UPDATE assignments SET
                class_id = :class_id,
                subject_id = :subject_id,
                title = :title,
                description = :description,
                due_date = :due_date,
                total_points = :total_points,
                type = :type,
                allow_late_submission = :allow_late
            WHERE id = :id AND teacher_id = :teacher_id
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute([
                ':class_id' => $class_id,
                ':subject_id' => $subject_id,
                ':title' => $data['title'],
                ':description' => $data['description'] ?: null,
                ':due_date' => $data['due_date'],
                ':total_points' => $data['total_points'],
                ':type' => $data['assignment_type'],
                ':allow_late' => isset($data['allow_late']) ? 1 : 0,
                ':id' => $assignment_id,
                ':teacher_id' => $this->teacher_id
            ]);

            if ($success && $stmt->rowCount() == 0) {
                 // Assignment not found or unauthorized
                 $this->error_message = "Assignment not found or unauthorized.";
                 return false;
            }

            return $success;
        } catch (PDOException $e) {
            $this->error_message = "Database Error: " . $e->getMessage();
            error_log("Assignment Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes an assignment.
     */
    public function deleteAssignment(int $assignment_id): bool {
        $query = "DELETE FROM assignments WHERE id = :id AND teacher_id = :teacher_id";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $assignment_id);
            $stmt->bindParam(':teacher_id', $this->teacher_id);
            $success = $stmt->execute();

            if ($success && $stmt->rowCount() == 0) {
                $this->error_message = "Assignment not found or unauthorized.";
                return false;
            }

            return $success;
        } catch (PDOException $e) {
            $this->error_message = "Database Error: " . $e->getMessage();
            error_log("Assignment Deletion Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getErrorMessage(): ?string {
        return $this->error_message;
    }
}

// --- Execution & Form Handling ---
$database = new Database();
$data_handler = new AssignmentsData($database);

// Handle POST submissions (Create/Update/Delete)
$form_action = $_POST['form_action'] ?? '';
$is_success = false;
$action_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_url = 'assignments.php';
    $assignment_id = $_POST['assignment_id'] ?? null;
    
    // Determine the action
    if ($form_action === 'create_assignment') {
        $is_success = $data_handler->createAssignment($_POST);
        $action_message = $is_success ? 'Assignment created successfully!' : ('Error creating assignment: ' . $data_handler->getErrorMessage());
    } elseif ($form_action === 'edit_assignment' && $assignment_id) {
        $is_success = $data_handler->updateAssignment($assignment_id, $_POST);
        $action_message = $is_success ? 'Assignment updated successfully!' : ('Error updating assignment: ' . $data_handler->getErrorMessage());
    } elseif ($form_action === 'delete_assignment' && $assignment_id) {
        $is_success = $data_handler->deleteAssignment($assignment_id);
        $action_message = $is_success ? 'Assignment deleted successfully!' : ('Error deleting assignment: ' . $data_handler->getErrorMessage());
    }
    
    // Redirect to clear POST data and show status message
    $redirect_url .= '?status=' . ($is_success ? 'success' : 'error') . '&msg=' . urlencode($action_message);
    header("Location: $redirect_url");
    exit;
}

// Handle GET requests for status messages and filters
$status_filter = $_GET['status_filter'] ?? 'all';
$class_id_filter = $_GET['class_id_filter'] ?? 'all';
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 9; // Show 9 assignments per page

if (isset($_GET['status']) && isset($_GET['msg'])) {
    $is_success = $_GET['status'] === 'success';
    $action_message = htmlspecialchars(urldecode($_GET['msg']));
}

// Fetch data for rendering
$profile = $data_handler->getTeacherProfile();
$classes_subjects = $data_handler->getTeacherClassesWithSubjects();
$total_assignments_count = $data_handler->getTotalAssignmentsCount($status_filter, $class_id_filter);
$assignments = $data_handler->getAssignments($status_filter, $class_id_filter, $current_page, $per_page);

// Calculate overall stats
$total_assignments = $total_assignments_count;
$active_count = 0;
$upcoming_count = 0;
$need_grading_count = 0;

// Calculate stats from ALL assignments (not just current page)
$all_assignments = $data_handler->getAssignments($status_filter, $class_id_filter, 1, 1000); // Get all for stats
foreach ($all_assignments as $assignment) {
    if ($assignment['status'] === 'Active') $active_count++;
    if ($assignment['status'] === 'Upcoming') $upcoming_count++;
    $need_grading_count += $assignment['grade_needed'];
}

// Calculate pagination
$total_pages = ceil($total_assignments_count / $per_page);
$start_index = (($current_page - 1) * $per_page) + 1;
$end_index = min($current_page * $per_page, $total_assignments_count);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .status-active { background-color: #10b981; }
        .status-upcoming { background-color: #3b82f6; }
        .status-overdue { background-color: #ef4444; }
        .status-completed { background-color: #6b7280; }
        
        .assignment-card {
            border-left: 4px solid;
        }
        
        .assignment-active { border-left-color: #10b981; }
        .assignment-upcoming { border-left-color: #3b82f6; }
        .assignment-overdue { border-left-color: #ef4444; }
        .assignment-completed { border-left-color: #6b7280; }
        
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
                    <h1 class="text-2xl font-bold text-nsknavy">Assignments</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search assignments..." class="bg-transparent outline-none w-32 md:w-64">
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
                            <p class="text-xs text-gray-600">ID: <?= $profile['teacher_id'] ?></p>
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
                    <h1 class="text-xl font-bold text-nsknavy">Assignments</h1>
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
                    <h2 class="text-lg md:text-xl font-bold text-nsknavy">All Assignments</h2>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        <select id="statusFilter" class="px-3 py-2 border rounded-lg text-sm" 
                                onchange="applyFilters()">
                            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="upcoming" <?= $status_filter == 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="overdue" <?= $status_filter == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                            <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <select id="classFilter" class="px-3 py-2 border rounded-lg text-sm"
                                onchange="applyFilters()">
                            <option value="all" <?= $class_id_filter == 'all' ? 'selected' : '' ?>>All Classes</option>
                            <?php 
                                $unique_classes = [];
                                foreach ($classes_subjects as $cs) {
                                    $unique_classes[$cs['class_id']] = $cs['class_name'];
                                }
                                foreach ($unique_classes as $id => $name): 
                            ?>
                                <option value="<?= $id ?>" <?= $class_id_filter == $id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button id="createAssignmentBtn" class="bg-nskgreen text-white px-3 py-2 rounded-lg hover:bg-green-600 transition text-sm">
                            <i class="fas fa-plus mr-2"></i>Create Assignment
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskblue"><?= $total_assignments ?></div>
                        <p class="text-xs text-gray-600">Total Assignments</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskgreen"><?= $active_count ?></div>
                        <p class="text-xs text-gray-600">Active</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskgold"><?= $upcoming_count ?></div>
                        <p class="text-xs text-gray-600">Upcoming</p>
                    </div>
                    <div class="text-center">
                        <div class="text-xl md:text-2xl font-bold text-nskred"><?= $need_grading_count ?></div>
                        <p class="text-xs text-gray-600">Need Grading</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6" id="assignmentList">
                    <?php if (count($assignments) > 0): ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="dashboard-card assignment-card <?= $assignment['color_class'] ?> border border-gray-200 rounded-lg p-4 hover:shadow-lg"
                                data-assignment='<?= htmlspecialchars(json_encode($assignment), ENT_QUOTES, 'UTF-8') ?>'>
                                
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-bold text-nsknavy text-sm md:text-base"><?= htmlspecialchars($assignment['title']) ?></h3>
                                        <p class="text-xs md:text-sm text-gray-600"><?= htmlspecialchars($assignment['class_name']) ?> - <?= htmlspecialchars($assignment['subject_name']) ?> • Due: <?= date('M j, Y', strtotime($assignment['due_date'])) ?></p>
                                    </div>
                                    <span class="<?= $assignment['status_text_class'] ?> px-2 py-1 rounded-full text-xs font-semibold"><?= htmlspecialchars($assignment['status']) ?></span>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Total Points:</span>
                                        <span class="font-semibold"><?= htmlspecialchars($assignment['total_points']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Submissions:</span>
                                        <span class="font-semibold"><?= $assignment['submissions'] ?>/<?= $assignment['total_students'] ?></span>
                                    </div>
                                    <div class="flex justify-between text-xs md:text-sm">
                                        <span>Status:</span>
                                        <span class="font-semibold text-nskred"><?= $assignment['grade_needed'] ?> pending grade</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill status-<?= strtolower($assignment['status']) ?>" style="width: <?= $assignment['progress'] ?>%"></div>
                                    </div>
                                    <div class="text-xs text-gray-600 text-center"><?= $assignment['progress'] ?>% submitted</div>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="view-assignment flex-1 bg-nskblue text-white py-2 px-3 rounded text-xs md:text-sm hover:bg-nsknavy transition" 
                                            data-id="<?= $assignment['id'] ?>">
                                        View Details
                                    </button>
                                    <button class="edit-assignment bg-nskgold text-white py-2 px-3 rounded text-xs md:text-sm hover:bg-amber-600 transition"
                                            data-id="<?= $assignment['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-assignment bg-nskred text-white py-2 px-3 rounded text-xs md:text-sm hover:bg-red-600 transition"
                                            data-id="<?= $assignment['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full p-8 text-center text-gray-600 bg-gray-100 rounded-lg">
                            <i class="fas fa-info-circle mr-2"></i> No assignments found matching the current filters.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-center mt-6 space-y-3 sm:space-y-0">
                    <p class="text-sm text-gray-600">
                        Showing <?= $start_index ?> to <?= $end_index ?> of <?= $total_assignments_count ?> assignments
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

            <div class="bg-white rounded-xl shadow-md p-4 md:p-6 mt-6">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition text-center">
                        <i class="fas fa-file-import text-nskblue text-2xl mb-2"></i>
                        <p class="font-semibold text-nskblue">Import Grades</p>
                    </button>
                    <button class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition text-center">
                        <i class="fas fa-copy text-nskgreen text-2xl mb-2"></i>
                        <p class="font-semibold text-nskgreen">Grade All Pending</p>
                    </button>
                    <button class="p-4 bg-amber-50 rounded-lg hover:bg-amber-100 transition text-center">
                        <i class="fas fa-download text-nskgold text-2xl mb-2"></i>
                        <p class="font-semibold text-nskgold">Export Grades</p>
                    </button>
                    <button class="p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition text-center">
                        <i class="fas fa-chart-bar text-purple-600 text-2xl mb-2"></i>
                        <p class="font-semibold text-purple-600">View Analytics</p>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <button class="floating-btn md:hidden bg-nskblue text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center" id="mobileCreateAssignmentBtn">
        <i class="fas fa-plus text-xl"></i>
    </button>

    <!-- Include sidebar at the end of body -->
    <?php include 'sidebar.php'; ?>

    <div id="assignmentModal" class="modal">
        <div class="modal-content w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy" id="assignmentModalTitle">Create New Assignment</h3>
                <button id="closeAssignmentModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="assignments.php" id="assignmentForm" class="space-y-4">
                <input type="hidden" name="form_action" id="formAction">
                <input type="hidden" name="assignment_id" id="assignmentId">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Title *</label>
                    <input type="text" name="title" id="formTitle" required class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="Enter assignment title">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea rows="3" name="description" id="formDescription" class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" placeholder="Describe the assignment..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class/Subject *</label>
                        <select name="class_subject_id" id="formClassSubjectId" required class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                            <option value="">Select Class & Subject</option>
                            <?php foreach ($classes_subjects as $cs): ?>
                                <option value="<?= $cs['class_id'] ?>_<?= $cs['subject_id'] ?>">
                                    <?= htmlspecialchars($cs['class_name']) ?> - <?= htmlspecialchars($cs['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                        <input type="date" name="due_date" id="formDueDate" required class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Points *</label>
                        <input type="number" name="total_points" id="formTotalPoints" required class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue" value="100" min="1">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Type *</label>
                        <select name="assignment_type" id="formAssignmentType" required class="w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-nskblue">
                            <option value="Quiz">Quiz</option>
                            <option value="Homework">Homework</option>
                            <option value="Project">Project</option>
                            <option value="Exam">Exam</option>
                            <option value="Worksheet">Worksheet</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="allow_late" id="formAllowLate" class="rounded border-gray-300">
                    <label for="formAllowLate" class="text-sm text-gray-700">Allow late submissions</label>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" id="cancelAssignment" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100 transition">Cancel</button>
                    <button type="submit" id="submitAssignment" class="px-4 py-2 bg-nskgreen text-white rounded-lg text-sm hover:bg-green-600 transition">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewAssignmentModal" class="modal">
        <div class="modal-content w-full max-w-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg md:text-xl font-bold text-nsknavy" id="viewModalTitle">Assignment Details</h3>
                <button id="closeViewModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500">Class/Subject</p>
                        <p class="text-sm font-medium" id="viewModalClassSubject"></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500">Due Date</p>
                        <p class="text-sm font-medium" id="viewModalDueDate"></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500">Points / Type</p>
                        <p class="text-sm font-medium" id="viewModalPointsType"></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500">Submissions</p>
                        <p class="text-sm font-medium" id="viewModalSubmissions"></p>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-nsknavy mb-2 mt-4">Description</h4>
                    <p class="text-sm text-gray-700 p-3 border rounded-lg" id="viewModalDescription"></p>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button id="editFromView" class="px-4 py-2 bg-nskgold text-white rounded-lg text-sm hover:bg-amber-600 transition">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </button>
                    <button class="px-4 py-2 bg-nskblue text-white rounded-lg text-sm hover:bg-nsknavy transition">
                        <i class="fas fa-check mr-2"></i>Go to Grading
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- DOM Elements ---
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const createAssignmentBtn = document.getElementById('createAssignmentBtn');
        const mobileCreateAssignmentBtn = document.getElementById('mobileCreateAssignmentBtn');

        // Modals
        const assignmentModal = document.getElementById('assignmentModal');
        const viewAssignmentModal = document.getElementById('viewAssignmentModal');
        const closeAssignmentModal = document.getElementById('closeAssignmentModal');
        const cancelAssignment = document.getElementById('cancelAssignment');
        const closeViewModal = document.getElementById('closeViewModal');
        
        // Form Elements
        const assignmentForm = document.getElementById('assignmentForm');
        const assignmentModalTitle = document.getElementById('assignmentModalTitle');
        const formAction = document.getElementById('formAction');
        const assignmentId = document.getElementById('assignmentId');
        const submitAssignment = document.getElementById('submitAssignment');

        // Form Fields
        const formTitle = document.getElementById('formTitle');
        const formDescription = document.getElementById('formDescription');
        const formClassSubjectId = document.getElementById('formClassSubjectId');
        const formDueDate = document.getElementById('formDueDate');
        const formTotalPoints = document.getElementById('formTotalPoints');
        const formAssignmentType = document.getElementById('formAssignmentType');
        const formAllowLate = document.getElementById('formAllowLate');

        // View Modal Elements
        const viewModalTitle = document.getElementById('viewModalTitle');
        const viewModalClassSubject = document.getElementById('viewModalClassSubject');
        const viewModalDueDate = document.getElementById('viewModalDueDate');
        const viewModalPointsType = document.getElementById('viewModalPointsType');
        const viewModalSubmissions = document.getElementById('viewModalSubmissions');
        const viewModalDescription = document.getElementById('viewModalDescription');
        const editFromView = document.getElementById('editFromView');
        
        // Filter Elements
        const statusFilter = document.getElementById('statusFilter');
        const classFilter = document.getElementById('classFilter');

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
        
        function applyFilters() {
            const status = statusFilter.value;
            const classId = classFilter.value;
            window.location.href = `assignments.php?status_filter=${status}&class_id_filter=${classId}`;
        }
        
        function changePage(page) {
            const status = statusFilter.value;
            const classId = classFilter.value;
            window.location.href = `assignments.php?status_filter=${status}&class_id_filter=${classId}&page=${page}`;
        }

        // --- CRUD MODAL HANDLERS ---
        
        function openCreateEditModal(assignment = null) {
            assignmentForm.reset();
            
            if (assignment) {
                assignmentModalTitle.textContent = 'Edit Assignment';
                formAction.value = 'edit_assignment';
                assignmentId.value = assignment.id;
                submitAssignment.textContent = 'Save Changes';
                submitAssignment.classList.remove('bg-nskgreen', 'hover:bg-green-600');
                submitAssignment.classList.add('bg-nskgold', 'hover:bg-amber-600');
                
                // Populate fields for editing
                formTitle.value = assignment.title;
                formDescription.value = assignment.description || '';
                formDueDate.value = assignment.due_date;
                formTotalPoints.value = assignment.total_points;
                formAssignmentType.value = assignment.type;
                formAllowLate.checked = assignment.allow_late_submission == 1;

                // Select correct Class/Subject combination (Class ID_Subject ID)
                const classSubjectValue = `${assignment.class_id}_${assignment.subject_id}`;
                formClassSubjectId.value = classSubjectValue;
                
            } else {
                assignmentModalTitle.textContent = 'Create New Assignment';
                formAction.value = 'create_assignment';
                assignmentId.value = '';
                submitAssignment.textContent = 'Create Assignment';
                submitAssignment.classList.remove('bg-nskgold', 'hover:bg-amber-600');
                submitAssignment.classList.add('bg-nskgreen', 'hover:bg-green-600');
                formTotalPoints.value = 100;
                formAllowLate.checked = false;
            }
            openModal(assignmentModal);
        }

        function openViewModal(assignment) {
            // Format dates
            const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
            const dueDateFormatted = new Date(assignment.due_date).toLocaleDateString('en-US', dateOptions);

            viewModalTitle.textContent = assignment.title;
            viewModalClassSubject.textContent = `${assignment.class_name} - ${assignment.subject_name}`;
            viewModalDueDate.textContent = dueDateFormatted;
            viewModalPointsType.textContent = `${assignment.total_points} Points (${assignment.type})`;
            viewModalSubmissions.textContent = `${assignment.submissions}/${assignment.total_students} Submitted`;
            viewModalDescription.textContent = assignment.description || 'No description provided.';
            
            // Set the assignment data onto the Edit button within the view modal for seamless transition
            editFromView.onclick = () => {
                closeModalFunc(viewAssignmentModal);
                openCreateEditModal(assignment);
            };
            
            openModal(viewAssignmentModal);
        }
        
        function handleDelete(assignmentId) {
            if (!confirm("Are you sure you want to delete this assignment? This action cannot be undone.")) {
                return;
            }
            
            // Create a temporary form to submit the delete action
            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = 'assignments.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'form_action';
            actionInput.value = 'delete_assignment';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'assignment_id';
            idInput.value = assignmentId;
            
            tempForm.appendChild(actionInput);
            tempForm.appendChild(idInput);
            document.body.appendChild(tempForm);
            
            tempForm.submit();
        }

        // --- Event Listeners ---
        
        // Sidebar/Menu controls
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            document.querySelectorAll('.sidebar-text').forEach(text => text.classList.toggle('hidden'));
        });
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('active');
        });
        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-show');
            mobileOverlay.classList.remove('active');
        });
        
        // Open Create Modal
        createAssignmentBtn.addEventListener('click', () => openCreateEditModal());
        mobileCreateAssignmentBtn.addEventListener('click', () => openCreateEditModal());

        // Close Modals
        closeAssignmentModal.addEventListener('click', () => closeModalFunc(assignmentModal));
        cancelAssignment.addEventListener('click', () => closeModalFunc(assignmentModal));
        closeViewModal.addEventListener('click', () => closeModalFunc(viewAssignmentModal));
        
        // Dynamic Card Buttons (Delegation)
        document.getElementById('assignmentList').addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;

            // Find the closest assignment card to get data-assignment JSON
            const card = e.target.closest('.dashboard-card');
            if (!card) return; // Should always be inside a card

            const assignmentData = JSON.parse(card.getAttribute('data-assignment'));
            const assignmentId = btn.getAttribute('data-id');

            if (btn.classList.contains('view-assignment')) {
                openViewModal(assignmentData);
            } else if (btn.classList.contains('edit-assignment')) {
                openCreateEditModal(assignmentData);
            } else if (btn.classList.contains('delete-assignment')) {
                handleDelete(assignmentId);
            }
        });

        // Close modal when clicking overlay
        window.addEventListener('click', (e) => {
            if (e.target === assignmentModal) closeModalFunc(assignmentModal);
            if (e.target === viewAssignmentModal) closeModalFunc(viewAssignmentModal);
        });

        // Responsive adjustments
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-show');
                mobileOverlay.classList.remove('active');
            }
        });
        
        // Auto-hide PHP status message after load
        document.addEventListener('DOMContentLoaded', () => {
            const notif = document.querySelector('.notification.show');
            if (notif) {
                setTimeout(() => {
                    notif.classList.remove('show');
                }, 3000);
            }
            
            // Set minimum due date to today for creation
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('formDueDate').setAttribute('min', today);
        });
    </script>
</body>
</html>