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
            // 1. Total Classes
            $q1 = "SELECT COUNT(DISTINCT class_id) FROM class_subjects WHERE teacher_id = :tid";
            $stmt1 = $this->conn->prepare($q1);
            $stmt1->bindParam(':tid', $this->teacher_id);
            $stmt1->execute();
            $stats['total_classes'] = $stmt1->fetchColumn() ?? 0;

            // 2. Total Students (across all classes taught by the teacher)
            $q2 = "
                SELECT COUNT(DISTINCT s.user_id) 
                FROM students s 
                JOIN class_subjects cs ON s.class_id = cs.class_id 
                WHERE cs.teacher_id = :tid
            ";
            $stmt2 = $this->conn->prepare($q2);
            $stmt2->bindParam(':tid', $this->teacher_id);
            $stmt2->execute();
            $stats['total_students'] = $stmt2->fetchColumn() ?? 0;

            // 3. Today's Classes and Next Class Time
            $today = date('l');
            $time_now = date('H:i:s');
            $q3 = "
                SELECT day_of_week, start_time, end_time, c.class_name, s.subject_name
                FROM timetable tt
                JOIN classes c ON tt.class_id = c.id
                JOIN subjects s ON tt.subject_id = s.id
                WHERE tt.teacher_id = :tid AND tt.day_of_week = :today
                ORDER BY tt.start_time
            ";
            $stmt3 = $this->conn->prepare($q3);
            $stmt3->bindParam(':tid', $this->teacher_id);
            $stmt3->bindParam(':today', $today);
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
                    r.marks_obtained,
                    r.grade,
                    c.class_name,
                    s.subject_name,
                    u.first_name, u.last_name
                FROM results r
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
            ORDER BY c.class_name
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tid', $this->teacher_id);
            $stmt->execute();
            $raw_summaries = $stmt->fetchAll();

            foreach ($raw_summaries as $summary) {
                $class_id = $summary['class_id'];
                $student_count = $summary['student_count'];

                // Calculate Average Grade
                $q_grade = "
                    SELECT AVG(r.marks_obtained) as avg_marks
                    FROM results r
                    JOIN students s ON r.student_id = s.id
                    WHERE s.class_id = :cid
                ";
                $stmt_grade = $this->conn->prepare($q_grade);
                $stmt_grade->bindParam(':cid', $class_id);
                $stmt_grade->execute();
                $grade_data = $stmt_grade->fetch();

                $summary['avg_grade'] = $grade_data['avg_marks'] ? round($grade_data['avg_marks']) : 0;

                // Calculate Attendance Rate
                $q_att = "
                    SELECT COUNT(CASE WHEN status = 'Present' THEN 1 END) AS present_count, COUNT(id) AS total_count
                    FROM attendance
                    WHERE class_id = :cid
                ";
                $stmt_att = $this->conn->prepare($q_att);
                $stmt_att->bindParam(':cid', $class_id);
                $stmt_att->execute();
                $att_data = $stmt_att->fetch();

                $total_recorded_entries = $att_data['total_count'] ?? 0;
                $present_count = $att_data['present_count'] ?? 0;

                // Calculate attendance rate only if there are recorded entries
                $attendance_rate = $total_recorded_entries > 0
                    ? round(($present_count / $total_recorded_entries) * 100)
                    : 0;

                $summary['attendance_rate'] = $attendance_rate;

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
        header("Location: login-form.php?error=auth");
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Northland Schools Kano</title>
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
    <!-- Main Content -->
    <main class="main-content">
        <!-- Desktop Header -->
        <header class="desktop-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy mr-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Dashboard</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" id="globalSearch" placeholder="Search students..."
                                class="bg-transparent outline-none w-32 md:w-64">
                        </div>
                    </div>

                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>

                    <div class="hidden md:flex items-center space-x-2">
                        <div
                            class="w-10 h-10 rounded-full bg-nskgold flex items-center justify-center text-white font-bold">
                            <?= $profile['initials'] ?>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-nsknavy">
                                <?= $profile['first_name'] . ' ' . $profile['last_name'] ?>
                            </p>
                            <p class="text-xs text-gray-600"><?= $profile['specialization'] ?> Teacher</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Header -->
        <header class="mobile-header bg-white shadow-md p-4">
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center space-x-4">
                    <button class="mobile-menu-toggle text-nsknavy mr-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">Dashboard</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="notificationButton" class="relative">
                            <i class="fas fa-bell text-nsknavy text-xl"></i>
                            <div class="notification-dot"></div>
                        </button>
                    </div>

                    <div class="flex items-center space-x-2">
                        <div
                            class="w-8 h-8 rounded-full bg-nskgold flex items-center justify-center text-white font-bold text-sm">
                            <?= $profile['initials'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

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
                    <div class="space-y-3 md:space-y-4">
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
                                        <p class="text-xs md:text-sm text-gray-600">Room N/A • 45 minutes</p>
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

                        <a href="results.php"
                            class="p-3 md:p-4 bg-green-50 rounded-lg hover:bg-green-100 transition text-center">
                            <i class="fas fa-book-open text-nskgreen text-xl md:text-2xl mb-2"></i>
                            <p class="font-semibold text-nskgreen text-sm md:text-base">Upload Results</p>
                        </a>

                        <a href="assignments.php"
                            class="p-3 md:p-4 bg-amber-50 rounded-lg hover:bg-amber-100 transition text-center">
                            <i class="fas fa-tasks text-nskgold text-xl md:text-2xl mb-2"></i>
                            <p class="font-semibold text-nskgold text-sm md:text-base">Manage Assignments</p>
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
                                        <p class="text-sm font-bold text-<?= $att_color ?>"><?= $summary['attendance_rate'] ?>%
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
    </main>

    <!-- Floating Action Button for Mobile -->
    <button
        class="floating-btn md:hidden bg-nskblue text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center">
        <i class="fas fa-plus text-xl"></i>
    </button>

    <!-- Include sidebar at the end of body -->
    <?php include 'sidebar.php'; ?>

    <script>
        // DOM Elements
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileOverlay = document.getElementById('mobileOverlay');

        // Sidebar Toggle Functionality
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            // Toggle sidebar text visibility
            const sidebarTexts = document.querySelectorAll('.sidebar-text');
            sidebarTexts.forEach(text => {
                text.classList.toggle('hidden');
            });
        }

        // Mobile Menu Toggle
        function toggleMobileMenu() {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('active');
        }

        // Event Listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);

        // Responsive adjustments
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                // Ensure sidebar is visible on larger screens
                sidebar.classList.remove('mobile-show');
                mobileOverlay.classList.remove('active');
            }
        });

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Dashboard loaded successfully');
        });
    </script>
</body>

</html>