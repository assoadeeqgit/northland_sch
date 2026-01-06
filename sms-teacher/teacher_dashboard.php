<?php
/**
 * Teacher Dashboard Page
 * Displays personalized quick stats, recent activity, and class summaries.
 */

// Start session at the very beginning - ONLY ONCE
session_start();

// Debug: Check session (remove this in production)
error_log("Teacher Dashboard - Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Teacher Dashboard - Session user_type: " . ($_SESSION['user_type'] ?? 'not set'));

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    error_log("Teacher Dashboard - Redirecting to login. User type: " . ($_SESSION['user_type'] ?? 'not set'));
    header("Location: ../login-form.php");
    exit();
}

require_once 'config/database.php';

class DashboardData
{
    private $conn;
    private $teacher_user_id;
    private $teacher_id;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
        if ($this->conn === null) {
            throw new Exception("Database connection failed");
        }

        // Get the logged-in teacher's user ID from session
        $this->teacher_user_id = $_SESSION['user_id'];
        $this->setTeacherId();
        $this->setCurrentContext();
    }

    private $current_term_id;
    private $current_session_id;

    private function setCurrentContext()
    {
        $sessionQuery = "SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1";
        $this->current_session_id = $this->conn->query($sessionQuery)->fetchColumn();
        
        $termQuery = "SELECT id FROM terms WHERE is_current = 1 LIMIT 1";
        $this->current_term_id = $this->conn->query($termQuery)->fetchColumn();
    }

    private function setTeacherId()
    {
        $query = "SELECT id FROM teachers WHERE user_id = :teacher_user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_user_id', $this->teacher_user_id);
        $stmt->execute();
        $this->teacher_id = $stmt->fetchColumn();

        if (!$this->teacher_id) {
            // If no teacher profile found, check if the user exists in users table
            $checkUserQuery = "SELECT id FROM users WHERE id = :user_id AND user_type = 'teacher'";
            $checkStmt = $this->conn->prepare($checkUserQuery);
            $checkStmt->bindParam(':user_id', $this->teacher_user_id);
            $checkStmt->execute();

            if (!$checkStmt->fetchColumn()) {
                throw new Exception("Teacher profile not found for user ID: " . $this->teacher_user_id);
            } else {
                // User exists but no teacher profile - this might be a data inconsistency
                throw new Exception("Teacher profile incomplete. Please contact administrator.");
            }
        }
    }

    public function getTeacherProfile(): array
    {
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
                $profile['first_name'] = $data['first_name'] ?? 'N/A';
                $profile['last_name'] = $data['last_name'] ?? 'N/A';
                $profile['initials'] = strtoupper(
                    substr($profile['first_name'], 0, 1) .
                    substr($profile['last_name'], 0, 1)
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
     * Fetches core stats for the quick cards.
     */
    public function getQuickStats(): array
    {
        $stats = [
            'total_classes' => 0,
            'total_students' => 0,
            'pending_assignments' => 0,
            'todays_classes' => 0,
            'next_class_time' => 'None Today',
            'schedule' => []
        ];

        if (!$this->teacher_id)
            return $stats;

        try {
            // 1. Total Classes (Subject classes + Assigned Class)
            $q1 = "
                SELECT COUNT(DISTINCT c.id) 
                FROM classes c
                LEFT JOIN class_subjects cs ON c.id = cs.class_id AND cs.teacher_id = :tid
                WHERE cs.teacher_id IS NOT NULL 
                   OR c.class_teacher_id = :tid
            ";
            $stmt1 = $this->conn->prepare($q1);
            $stmt1->bindParam(':tid', $this->teacher_id);
            $stmt1->execute();
            $stats['total_classes'] = $stmt1->fetchColumn() ?? 0;

            // 2. Total Students (across all classes taught by the teacher OR assigned)
            $q2 = "
                SELECT COUNT(DISTINCT s.user_id) 
                FROM students s 
                JOIN classes c ON s.class_id = c.id
                LEFT JOIN class_subjects cs ON c.id = cs.class_id AND cs.teacher_id = :tid
                WHERE (cs.teacher_id IS NOT NULL OR c.class_teacher_id = :tid)
            ";
            $stmt2 = $this->conn->prepare($q2);
            $stmt2->bindParam(':tid', $this->teacher_id);
            $stmt2->execute();
            $stats['total_students'] = $stmt2->fetchColumn() ?? 0;

            // 3. Today's Classes and Next Class Time
            $today = date('l');
            $time_now = date('H:i:s');
            
            // Check if today is a weekend (no school on Saturday and Sunday)
            $is_weekend = in_array($today, ['Saturday', 'Sunday']);
            
            if (!$is_weekend) {
                // Get current session and term
                $sessionQuery = "SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1";
                $currentSessionId = $this->conn->query($sessionQuery)->fetchColumn();
                
                $termQuery = "SELECT id FROM terms WHERE is_current = 1 LIMIT 1";
                $currentTermId = $this->conn->query($termQuery)->fetchColumn();

                $q3 = "
                    SELECT day_of_week, start_time, end_time, room, c.class_name, s.subject_name
                    FROM timetable tt
                    JOIN classes c ON tt.class_id = c.id
                    JOIN subjects s ON tt.subject_id = s.id
                    JOIN class_subjects cs ON tt.class_id = cs.class_id AND tt.subject_id = cs.subject_id AND cs.teacher_id = tt.teacher_id
                    WHERE tt.teacher_id = :tid 
                    AND tt.day_of_week = :today
                    AND tt.academic_session_id = :session_id
                    AND tt.term_id = :term_id
                    ORDER BY tt.start_time
                ";
                $stmt3 = $this->conn->prepare($q3);
                $stmt3->bindParam(':tid', $this->teacher_id);
                $stmt3->bindParam(':today', $today);
                $stmt3->bindParam(':session_id', $this->current_session_id);
                $stmt3->bindParam(':term_id', $this->current_term_id);
                $stmt3->execute();
                $todays_schedule = $stmt3->fetchAll();

                $stats['todays_classes'] = count($todays_schedule);
                $stats['schedule'] = $todays_schedule;

                // Find next class time
                $stats['next_class_time'] = 'None Today';
                foreach ($todays_schedule as $class) {
                    if ($class['start_time'] > $time_now) {
                        $stats['next_class_time'] = (new DateTime($class['start_time']))->format('g:i A');
                        break;
                    }
                }
            } else {
                // Weekend - no classes
                $stats['todays_classes'] = 0;
                $stats['schedule'] = [];
                $stats['next_class_time'] = 'Weekend';
            }

            // 4. Assignments requiring action (Pending Review)
            $q4 = "
                SELECT COUNT(id) 
                FROM assignments 
                WHERE teacher_id = :tid AND due_date >= CURDATE()
            ";
            $stmt4 = $this->conn->prepare($q4);
            $stmt4->bindParam(':tid', $this->teacher_id);
            $stmt4->execute();
            $stats['pending_assignments'] = $stmt4->fetchColumn() ?? 0;


        } catch (PDOException $e) {
            error_log("Error fetching quick stats: " . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Fetches the 3 most recent activities (Attendance and Results) recorded by the teacher.
     */
    public function getRecentActivity(): array
    {
        if (!$this->teacher_user_id)
            return [];

        try {
            // Fetch recent attendance records recorded by this teacher
            $q_attendance = "
                SELECT 
                    a.created_at, 
                    'Attendance' AS type,
                    a.status,
                    c.class_name,
                    u.first_name, u.last_name
                FROM attendance a
                JOIN classes c ON a.class_id = c.id
                JOIN students s ON a.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE a.recorded_by = :uid
                ORDER BY a.created_at DESC
                LIMIT 3
            ";

            // Fetch recent result records where the teacher is responsible for the subject/class
            $q_results = "
                SELECT 
                    r.created_at,
                    'Result' AS type,
                    r.total_score as marks_obtained,
                    r.grade,
                    c.class_name,
                    s.subject_name,
                    u.first_name, u.last_name
                FROM student_results r
                JOIN subjects s ON r.subject_id = s.id
                JOIN students stu ON r.student_id = stu.id
                JOIN users u ON stu.user_id = u.id
                JOIN classes c ON stu.class_id = c.id
                JOIN class_subjects cs ON c.id = cs.class_id AND s.id = cs.subject_id
                WHERE cs.teacher_id = :tid
                ORDER BY r.created_at DESC
                LIMIT 3
            ";

            $combined = [];

            // Execute attendance query
            $stmt_att = $this->conn->prepare($q_attendance);
            $stmt_att->bindParam(':uid', $this->teacher_user_id);
            if ($stmt_att->execute()) {
                $attendance_data = $stmt_att->fetchAll();
                $combined = array_merge($combined, $attendance_data);
            }

            // Execute results query
            $stmt_res = $this->conn->prepare($q_results);
            $stmt_res->bindParam(':tid', $this->teacher_id);
            if ($stmt_res->execute()) {
                $results_data = $stmt_res->fetchAll();
                $combined = array_merge($combined, $results_data);
            }

            // Sort combined data by timestamp (most recent first)
            usort($combined, function ($a, $b) {
                $timeA = strtotime($a['created_at'] ?? '2000-01-01');
                $timeB = strtotime($b['created_at'] ?? '2000-01-01');
                return $timeB - $timeA;
            });

            return array_slice($combined, 0, 3);

        } catch (PDOException $e) {
            error_log("Error fetching recent activity: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetches performance summaries for all classes taught by the teacher.
     */
    public function getClassSummaries(): array
    {
        $summaries = [];
        if (!$this->teacher_id)
            return $summaries;

        // Union query to get subject classes AND assigned classes
        $query = "
            SELECT 
                c.id AS class_id, 
                c.class_name, 
                s.subject_name,
                c.class_code,
                (SELECT COUNT(stu.id) FROM students stu WHERE stu.class_id = c.id) AS student_count
            FROM class_subjects cs
            JOIN classes c ON cs.class_id = c.id
            JOIN subjects s ON cs.subject_id = s.id
            WHERE cs.teacher_id = :tid
            
            UNION
            
            SELECT 
                c.id AS class_id, 
                c.class_name, 
                'Class Teacher' as subject_name,
                c.class_code,
                (SELECT COUNT(stu.id) FROM students stu WHERE stu.class_id = c.id) AS student_count
            FROM classes c
            WHERE c.class_teacher_id = :tid
            
            ORDER BY class_name
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tid', $this->teacher_id);
            $stmt->execute();
            $raw_summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($raw_summaries)) {
                return [];
            }

            // Extract Class IDs for bulk querying
            $class_ids = array_column($raw_summaries, 'class_id');
            // Sanitize IDs (ensure integers) to use safely in IN clause
            $valid_ids = array_map('intval', array_unique($class_ids));
            
            if (empty($valid_ids)) {
                return $raw_summaries; // Should not happen if not empty
            }
            
            $ids_placeholder = implode(',', $valid_ids);

            // 1. Bulk Fetch Average Grades
            // Note: Grouping by class_id from students table is correct as results links to students
            $q_grades = "
                SELECT s.class_id, AVG(r.total_score) as avg_marks
                FROM student_results r
                JOIN students s ON r.student_id = s.id
                WHERE s.class_id IN ($ids_placeholder)
                AND r.term_id = :term_id
                GROUP BY s.class_id
            ";
            $grade_map = [];
            try {
                $stmt_grades = $this->conn->prepare($q_grades);
                $stmt_grades->bindParam(':term_id', $this->current_term_id);
                $stmt_grades->execute();
                while ($row = $stmt_grades->fetch(PDO::FETCH_ASSOC)) {
                    $grade_map[$row['class_id']] = $row['avg_marks'];
                }
            } catch (PDOException $e) { error_log("Grade fetch error: " . $e->getMessage()); }

            // 2. Bulk Fetch Attendance Rates
            $q_att = "
                SELECT class_id, 
                       COUNT(CASE WHEN status = 'Present' THEN 1 END) AS present_count, 
                       COUNT(id) AS total_count
                FROM attendance
                WHERE class_id IN ($ids_placeholder)
                AND term_id = :term_id
                GROUP BY class_id
            ";
            $att_map = [];
            try {
                $stmt_att = $this->conn->prepare($q_att);
                $stmt_att->bindParam(':term_id', $this->current_term_id);
                $stmt_att->execute();
                while ($row = $stmt_att->fetch(PDO::FETCH_ASSOC)) {
                    $att_map[$row['class_id']] = $row;
                }
            } catch (PDOException $e) { error_log("Attendance fetch error: " . $e->getMessage()); }

            // Merge Data
            foreach ($raw_summaries as $summary) {
                $cid = $summary['class_id'];
                
                // Grade
                $avg = $grade_map[$cid] ?? 0;
                $summary['avg_grade'] = $avg ? round($avg) : 0;
                
                // Attendance
                $att_data = $att_map[$cid] ?? ['present_count' => 0, 'total_count' => 0];
                $total = $att_data['total_count'];
                $present = $att_data['present_count'];
                
                $summary['attendance_rate'] = $total > 0 
                    ? round(($present / $total) * 100) 
                    : 'N/A';
                
                $summaries[] = $summary;
            }

        } catch (PDOException $e) {
            error_log("Error fetching class summaries: " . $e->getMessage());
        }

        return $summaries;
    }
}

// --- Execution ---
try {
    $database = new Database();
    $data_handler = new DashboardData($database);

    $profile = $data_handler->getTeacherProfile();
    $stats = $data_handler->getQuickStats();
    $recent_activity = $data_handler->getRecentActivity();
    $class_summaries = $data_handler->getClassSummaries();
} catch (Exception $e) {
    // Handle errors gracefully
    error_log("Dashboard initialization error: " . $e->getMessage());

    // If it's an authentication error, redirect to login
    if (
        strpos($e->getMessage(), 'Teacher profile not found') !== false ||
        strpos($e->getMessage(), 'Teacher profile incomplete') !== false
    ) {
        session_destroy();
        header("Location: ../login-form.php?error=auth");
        exit();
    }

    // For other errors, show a user-friendly message
    $error_message = "An error occurred while loading the dashboard. Please try again later.";
    if (strpos($e->getMessage(), 'Database connection failed') !== false) {
        $error_message = "Database connection failed. Please contact administrator.";
    }
    die($error_message);
}

// Determine schedule status
$time_now = date('H:i:s');

function getScheduleStatus($start_time, $end_time, $time_now)
{
    if ($start_time < $time_now && $end_time > $time_now) {
        return ['status' => 'Ongoing', 'color' => 'nskgold', 'icon' => 'fas fa-arrow-right', 'bg' => 'bg-amber-50', 'border' => 'border-nskgold'];
    } elseif ($start_time > $time_now) {
        return ['status' => 'Upcoming', 'color' => 'nskblue', 'icon' => 'fas fa-clock', 'bg' => 'bg-blue-50', 'border' => 'border-nskblue'];
    } else {
        return ['status' => 'Completed', 'color' => 'nskgreen', 'icon' => 'fas fa-check-circle', 'bg' => 'bg-green-50', 'border' => 'border-nskgreen'];
    }
}

// Include AJAX check helper
require_once 'ajax_check.php';
?>
<?php if (!$is_ajax): ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Northland Schools Kano</title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="css/style.css" as="style">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    
    <!-- Pre-compiled Tailwind CSS -->
    <link rel="stylesheet" href="../assets/css/tailwind.min.css?v=1.0.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-gray-50 min-h-screen">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
<?php endif; ?>
        <!-- Persistent Header -->
        <?php 
        $pageTitle = 'Dashboard';
        include 'header.php'; 
        ?>

        <!-- Dynamic Page Content -->
        <div id="page-content" class="p-0">
            <span id="page-meta-data" data-title="Dashboard" hidden></span>

            <!-- Dashboard Content -->
            <div class="p-4 md:p-6">
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
                    <div class="dashboard-card bg-white rounded-xl shadow-md p-4 md:p-5 flex items-center">
                        <div class="bg-nsklightblue p-3 md:p-4 rounded-full mr-3 md:mr-4">
                            <i class="fas fa-chalkboard text-white text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm md:text-base">My Classes</p>
                            <p class="text-xl md:text-2xl font-bold text-nsknavy"><?= $stats['total_classes'] ?></p>
                            <p class="text-xs text-nskgreen"><i class="fas fa-arrow-up"></i> Active classes</p>
                        </div>
                    </div>

                    <div class="dashboard-card bg-white rounded-xl shadow-md p-4 md:p-5 flex items-center">
                        <div class="bg-nskgreen p-3 md:p-4 rounded-full mr-3 md:mr-4">
                            <i class="fas fa-user-graduate text-white text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm md:text-base">Total Students</p>
                            <p class="text-xl md:text-2xl font-bold text-nsknavy"><?= $stats['total_students'] ?></p>
                            <p class="text-xs text-gray-600">Across all classes</p>
                        </div>
                    </div>

                    <div class="dashboard-card bg-white rounded-xl shadow-md p-4 md:p-5 flex items-center">
                        <div class="bg-nskgold p-3 md:p-4 rounded-full mr-3 md:mr-4">
                            <i class="fas fa-tasks text-white text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm md:text-base">Assignments</p>
                            <p class="text-xl md:text-2xl font-bold text-nsknavy"><?= $stats['pending_assignments'] ?></p>
                            <p class="text-xs text-nskred">Pending or Active</p>
                        </div>
                    </div>

                    <div class="dashboard-card bg-white rounded-xl shadow-md p-4 md:p-5 flex items-center">
                        <div class="bg-nskred p-3 md:p-4 rounded-full mr-3 md:mr-4">
                            <i class="fas fa-clock text-white text-lg md:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm md:text-base">Today's Classes</p>
                            <p class="text-xl md:text-2xl font-bold text-nsknavy"><?= $stats['todays_classes'] ?></p>
                            <p class="text-xs text-nskblue">Next: <?= $stats['next_class_time'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Today's Schedule & Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">
                    <!-- Today's Schedule -->
                <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                    <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Today's Schedule (<?= date('l') ?>)</h3>
                    <div class="space-y-3 md:space-y-4 max-h-[400px] overflow-y-auto pr-2">
                        <?php if (!empty($stats['schedule'])): ?>
                            <?php foreach ($stats['schedule'] as $class):
                                $status_data = getScheduleStatus($class['start_time'], $class['end_time'], $time_now);
                                $start_time_fmt = (new DateTime($class['start_time']))->format('g:i');
                                $period_fmt = (new DateTime($class['start_time']))->format('A');
                                ?>
                                <div
                                    class="flex items-center p-3 <?= $status_data['bg'] ?> rounded-lg border-l-4 <?= $status_data['border'] ?>">
                                    <div class="flex-shrink-0 w-14 md:w-16 text-center">
                                        <p class="text-sm font-bold text-<?= $status_data['color'] ?>"><?= $start_time_fmt ?>
                                        </p>
                                        <p class="text-xs text-gray-600"><?= $period_fmt ?></p>
                                    </div>
                                    <div class="ml-3 md:ml-4 flex-1">
                                        <p class="font-semibold text-sm md:text-base">
                                            <?= htmlspecialchars($class['subject_name']) ?> -
                                            <?= htmlspecialchars($class['class_name']) ?>
                                        </p>
                                        <?php 
                                            $duration = round((strtotime($class['end_time']) - strtotime($class['start_time'])) / 60);
                                            $room = !empty($class['room']) ? htmlspecialchars($class['room']) : 'N/A';
                                        ?>
                                        <p class="text-xs md:text-sm text-gray-600">Room <?= $room ?> • <?= $duration ?> minutes</p>
                                    </div>
                                    <div class="text-<?= $status_data['color'] ?>">
                                        <i class="<?= $status_data['icon'] ?>"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 text-center text-gray-500 bg-gray-50 rounded-lg">
                                <i class="fas fa-calendar-times mr-2"></i> No classes scheduled for today.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                        <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3 md:gap-4">
                            <a href="attendance.php"
                                class="p-3 md:p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition text-center">
                                <i class="fas fa-clipboard-check text-nskblue text-xl md:text-2xl mb-2"></i>
                                <p class="font-semibold text-nskblue text-sm md:text-base">Take Attendance</p>
                            </a>

                            <a href="view_results.php"
                                class="p-3 md:p-4 bg-green-50 rounded-lg hover:bg-green-100 transition text-center">
                                <i class="fas fa-book-open text-nskgreen text-xl md:text-2xl mb-2"></i>
                                <p class="font-semibold text-nskgreen text-sm md:text-base">Upload Results</p>
                            </a>


                            <a href="my_students.php"
                                class="p-3 md:p-4 bg-red-50 rounded-lg hover:bg-red-100 transition text-center">
                                <i class="fas fa-user-graduate text-nskred text-xl md:text-2xl mb-2"></i>
                                <p class="font-semibold text-nskred text-sm md:text-base">View Students</p>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Class Performance -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                    <!-- Recent Activity - DYNAMICALLY POPULATED -->
                    <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                        <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Recent Activity</h3>
                        <div class="space-y-3 md:space-y-4">
                            <?php if (!empty($recent_activity)): ?>
                                <?php foreach ($recent_activity as $activity):
                                    $time_ago = time() - strtotime($activity['created_at']);
                                    $time_display = $time_ago < 3600 ? round($time_ago / 60) . ' mins ago' : round($time_ago / 3600) . ' hours ago';

                                    if ($activity['type'] === 'Attendance') {
                                        $icon = 'fas fa-clipboard-check';
                                        $color = 'nskblue';
                                        $title = "Attendance recorded: {$activity['status']}";
                                        $details = "{$activity['first_name']} {$activity['last_name']} in {$activity['class_name']}";
                                    } else {
                                        $icon = 'fas fa-chart-bar';
                                        $color = 'nskgreen';
                                        $title = "Result entered: {$activity['grade']} ({$activity['marks_obtained']} marks)";
                                        $details = "{$activity['subject_name']} for {$activity['first_name']} {$activity['last_name']}";
                                    }
                                    ?>
                                    <div class="flex items-start space-x-3">
                                        <div
                                            class="w-7 h-7 md:w-8 md:h-8 bg-<?= $color ?> rounded-full flex items-center justify-center">
                                            <i class="<?= $icon ?> text-white text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold"><?= $title ?></p>
                                            <p class="text-xs text-gray-600"><?= $details ?> • <?= $time_display ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-3 text-center text-gray-500 bg-gray-50 rounded-lg">
                                    <i class="fas fa-info-circle mr-2"></i> No recent activity found.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Class Summary Overview - DYNAMICALLY POPULATED -->
                    <div class="bg-white rounded-xl shadow-md p-4 md:p-6">
                        <h3 class="text-lg md:text-xl font-bold text-nsknavy mb-4">Class Summary</h3>
                        <div class="space-y-3 md:space-y-4">
                            <?php if (!empty($class_summaries)): ?>
                                <?php foreach ($class_summaries as $summary):
                                    $grade_color = $summary['avg_grade'] >= 85 ? 'nskgreen' : ($summary['avg_grade'] >= 75 ? 'nskgold' : 'nskred');
                                    $att_color = $summary['attendance_rate'] >= 90 ? 'nskgreen' : ($summary['attendance_rate'] >= 80 ? 'nskgold' : 'nskred');
                                    ?>
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <div>
                                            <p class="font-semibold text-nskblue text-sm md:text-base">
                                                <?= htmlspecialchars($summary['class_name']) ?> -
                                                <?= htmlspecialchars($summary['subject_name']) ?>
                                            </p>
                                            <p class="text-xs md:text-sm text-gray-600"><?= $summary['student_count'] ?> students •
                                                Avg: <span class="text-<?= $grade_color ?>"><?= $summary['avg_grade'] ?>%</span></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-<?= $att_color ?>">
                                                <?php if (is_numeric($summary['attendance_rate'])): ?>
                                                    <?= $summary['attendance_rate'] ?>%
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-xs text-gray-600">Attendance</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-3 text-center text-gray-500 bg-gray-50 rounded-lg">
                                    <i class="fas fa-chalkboard-teacher mr-2"></i> No classes assigned to summarize.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- End #page-content -->
<?php if (!$is_ajax): ?>
    </main>
</body>
</html>
<?php endif; ?>