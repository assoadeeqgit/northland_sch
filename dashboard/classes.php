<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// session_start();

// Include required files
require_once '../config/logger.php';
require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');

require_once '../config/database.php';

// Initialize variables
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));
$classesData = [];
$teachersData = [];
$totalClasses = 0;
$totalStudents = 0;
$totalSubjects = 0;
$totalTeachers = 0;

// Database connection
try {
    $database = new Database();
    $db = $database->getConnection();

    // Handle delete class request (must be before data fetching)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
        try {
            $class_id = $_POST['class_id'] ?? null;

            if (!$class_id) {
                throw new Exception("Class ID is required.");
            }

            // Check if class has students
            $studentCheckStmt = $db->prepare("SELECT COUNT(*) FROM students WHERE class_id = ?");
            $studentCheckStmt->execute([$class_id]);
            $studentCount = $studentCheckStmt->fetchColumn();

            if ($studentCount > 0) {
                $_SESSION['error'] = "Cannot delete class with enrolled students. Please reassign or remove students first.";
            } else {
                // Get class name for logging
                $classNameStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
                $classNameStmt->execute([$class_id]);
                $className = $classNameStmt->fetchColumn();

                // Delete related records first
                $db->beginTransaction();

                // Delete class subjects
                $deleteSubjectsStmt = $db->prepare("DELETE FROM class_subjects WHERE class_id = ?");
                $deleteSubjectsStmt->execute([$class_id]);

                // Delete timetable entries
                $deleteTimetableStmt = $db->prepare("DELETE FROM timetable WHERE class_id = ?");
                $deleteTimetableStmt->execute([$class_id]);

                // Delete the class
                $deleteClassStmt = $db->prepare("DELETE FROM classes WHERE id = ?");
                $deleteClassStmt->execute([$class_id]);

                $db->commit();

                // Log activity
                $admin_name = $_SESSION['user_name'] ?? 'Admin';
                require_once '../config/logger.php';
                logActivity(
                    $db,
                    $admin_name,
                    "Class Deleted",
                    "Deleted class: $className",
                    "fas fa-trash",
                    "bg-nskred"
                );

                $_SESSION['success'] = "Class deleted successfully!";
            }

            header("Location: classes.php");
            exit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error'] = "Error deleting class: " . $e->getMessage();
            header("Location: classes.php");
            exit();
        }
    }

    // Fetch all classes with teacher info and stats
    $classesSql = "SELECT c.*, 
                          u.first_name as teacher_first_name, 
                          u.last_name as teacher_last_name,
                          t.teacher_id as teacher_code,
                          (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
                          (SELECT COUNT(*) FROM class_subjects WHERE class_id = c.id) as subject_count
                   FROM classes c
                   LEFT JOIN teachers t ON c.class_teacher_id = t.id
                   LEFT JOIN users u ON t.user_id = u.id
                   ORDER BY c.class_level, c.class_name";
    $classesStmt = $db->query($classesSql);
    $classesData = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all active teachers
    $teachersSql = "SELECT t.id, t.teacher_id, u.first_name, u.last_name, u.email,
                           tp.subject_specialization
                    FROM teachers t
                    JOIN users u ON t.user_id = u.id
                    LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
                    WHERE u.is_active = 1
                    ORDER BY u.first_name, u.last_name";
    $teachersStmt = $db->query($teachersSql);
    $teachersData = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $totalClasses = count($classesData);
    $totalStudents = $db->query("SELECT COUNT(*) FROM students s JOIN users u ON s.user_id = u.id WHERE u.is_active = 1")->fetchColumn();
    $totalSubjects = $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $totalTeachers = $db->query("SELECT COUNT(*) FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1")->fetchColumn();

    // Get classroom statistics
    $totalClassrooms = $db->query("SELECT COUNT(DISTINCT room) FROM timetable WHERE room IS NOT NULL")->fetchColumn();
    $classroomsInMaintenance = 0; // You can add a maintenance table or field if needed
    $availableClassrooms = $totalClassrooms - $classroomsInMaintenance;
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Management - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
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

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }

        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        .timetable-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .timetable-card:hover {
            transform: translateY(-5px);
        }

        .nav-item {
            position: relative;
        }

        .nav-item::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: #f59e0b;
            transition: width 0.3s ease;
        }

        .nav-item:hover::after {
            width: 100%;
        }

        .sidebar {
            transition: all 0.3s ease;
            width: 250px;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .main-content {
            transition: all 0.3s ease;
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .main-content.expanded {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .sidebar.mobile-show {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
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
        }

        .timetable-cell {
            transition: all 0.2s ease;
        }

        .timetable-cell:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .subject-math {
            background-color: #bfdbfe;
            border-left: 4px solid #3b82f6;
        }

        .subject-science {
            background-color: #bbf7d0;
            border-left: 4px solid #10b981;
        }

        .subject-english {
            background-color: #fde68a;
            border-left: 4px solid #f59e0b;
        }

        .subject-history {
            background-color: #e9d5ff;
            border-left: 4px solid #8b5cf6;
        }

        .subject-art {
            background-color: #fecaca;
            border-left: 4px solid #ef4444;
        }

        .subject-pe {
            background-color: #c7d2fe;
            border-left: 4px solid #6366f1;
        }

        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: scale(0.9);
            opacity: 0;
        }

        .modal.active {
            transform: scale(1);
            opacity: 1;
        }

        .tab-button {
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background-color: #1e40af;
            color: white;
        }

        .tab-content {
            transition: opacity 0.3s ease;
        }

        .tab-content.hidden {
            opacity: 0;
            pointer-events: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .class-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="flex">
    <div id="sidebar-container"></div>
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content">
        <!-- Header -->
        <header class="bg-white shadow-md p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <button id="mobileMenuToggle" class="md:hidden text-nsknavy">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-bold text-nsknavy">Classes Management</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                            <i class="fas fa-search text-gray-500"></i>
                            <input type="text" placeholder="Search classes..."
                                class="bg-transparent outline-none w-32 md:w-64">
                        </div>
                    </div>

                    <div class="relative">
                        <i class="fas fa-bell text-nsknavy text-xl"></i>
                        <div class="notification-dot"></div>
                    </div>

                    <div class="hidden md:flex items-center space-x-2">
                        <div
                            class="w-10 h-10 rounded-full bg-nskblue flex items-center justify-center text-white font-bold">
                            A
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-nsknavy">Admin User</p>
                            <p class="text-xs text-gray-600">Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Classes Management Content -->
        <div class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nsklightblue p-4 rounded-full mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Classes</p>
                        <p class="text-2xl font-bold text-nsknavy">
                            <?= $totalClasses ?>
                        </p>
                        <p class="text-xs text-nskgreen">All levels</p>
                    </div>
                </div>

                <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Students</p>
                        <p class="text-2xl font-bold text-nsknavy">
                            <?= $totalStudents ?>
                        </p>
                        <p class="text-xs text-gray-600">Enrolled</p>
                    </div>
                </div>

                <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-book text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Subjects</p>
                        <p class="text-2xl font-bold text-nsknavy">
                            <?= $totalSubjects ?>
                        </p>
                        <p class="text-xs text-nskgreen">Core & Electives</p>
                    </div>
                </div>

                <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-purple-500 p-4 rounded-full mr-4">
                        <i class="fas fa-user-tie text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Teachers</p>
                        <p class="text-2xl font-bold text-nsknavy">
                            <?= $totalTeachers ?>
                        </p>
                        <p class="text-xs text-nskgreen">Active</p>
                    </div>
                </div>

                <div class="timetable-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-door-open text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Classrooms</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $totalClassrooms ?></p>
                        <p class="text-xs text-<?= $classroomsInMaintenance > 0 ? 'nskred' : 'nskgreen' ?>">
                            <?= $classroomsInMaintenance > 0 ? $classroomsInMaintenance . ' in maintenance' : 'All available' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-nsknavy">Class Management</h2>

                    <div class="flex flex-wrap gap-4">
                        <select id="classFilter" class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">All Classes</option>
                            <?php foreach ($classesData as $class): ?>
                                <option value="<?= htmlspecialchars($class['class_name']) ?>">
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="sectionFilter" class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">All Levels</option>
                            <option value="early childhood">Early Childhood</option>
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                        </select>

                        <button id="filterBtn"
                            class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>

                        <button id="createClassBtn"
                            class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Create Class
                        </button>

                        <button id="bulkActionsBtn"
                            class="bg-nskgold text-white px-4 py-2 rounded-lg font-semibold hover:bg-amber-600 transition flex items-center">
                            <i class="fas fa-tasks mr-2"></i> Bulk Actions
                        </button>
                    </div>
                </div>
            </div>

            <!-- Classes Tabs -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        class="tab-button px-4 py-2 rounded-lg border border-nskblue text-nskblue font-semibold active"
                        data-tab="classes">
                        Class Overview
                    </button>
                    <button class="tab-button px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold"
                        data-tab="timetable">
                        Class Timetables
                    </button>
                    <button class="tab-button px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold"
                        data-tab="assignments">
                        Class Assignments
                    </button>
                    <button class="tab-button px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold"
                        data-tab="performance">
                        Performance Analytics
                    </button>
                </div>

                <!-- Class Overview Tab -->
                <div id="classesTab" class="tab-content">
                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if (empty($classesData)): ?>
                            <div class="col-span-full text-center py-12">
                                <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                                <p class="text-gray-500 text-lg">No classes found. Click "Create Class" to add your first
                                    class.</p>
                            </div>
                        <?php else: ?>
                            <?php
                            $colors = [
                                'blue' => ['from' => 'blue-50', 'to' => 'blue-100', 'border' => 'blue-500', 'button' => 'nskblue'],
                                'green' => ['from' => 'green-50', 'to' => 'green-100', 'border' => 'green-500', 'button' => 'nskgreen'],
                                'yellow' => ['from' => 'yellow-50', 'to' => 'yellow-100', 'border' => 'yellow-500', 'button' => 'nskgold'],
                                'purple' => ['from' => 'purple-50', 'to' => 'purple-100', 'border' => 'purple-500', 'button' => 'purple-500'],
                                'red' => ['from' => 'red-50', 'to' => 'red-100', 'border' => 'red-500', 'button' => 'red-500'],
                                'indigo' => ['from' => 'indigo-50', 'to' => 'indigo-100', 'border' => 'indigo-500', 'button' => 'indigo-500'],
                            ];
                            $colorKeys = array_keys($colors);
                            $colorIndex = 0;

                            foreach ($classesData as $class):
                                $color = $colors[$colorKeys[$colorIndex % count($colorKeys)]];
                                $colorIndex++;
                                $teacherName = $class['teacher_first_name']
                                    ? $class['teacher_first_name'] . ' ' . $class['teacher_last_name']
                                    : 'Not Assigned';
                                ?>
                                <div class="class-card bg-gradient-to-br from-<?= $color['from'] ?> to-<?= $color['to'] ?> rounded-xl p-6 border-l-4 border-<?= $color['border'] ?> hover:shadow-lg transition-all duration-300"
                                    data-level="<?= htmlspecialchars(strtolower($class['class_level'])) ?>">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-xl font-bold text-nsknavy">
                                                <?= htmlspecialchars($class['class_name']) ?>
                                            </h3>
                                            <p class="text-gray-600">Class Teacher: <?= htmlspecialchars($teacherName) ?></p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button type="button" class="text-nskblue hover:text-nsknavy"
                                                onclick="editClass(<?= $class['id'] ?>)" title="Edit Class">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="text-nskgreen hover:text-green-700"
                                                onclick="viewClassDetails(<?= $class['id'] ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="text-nskred hover:text-red-700"
                                                onclick="confirmDeleteClass(<?= $class['id'] ?>, '<?= htmlspecialchars($class['class_name'], ENT_QUOTES) ?>')"
                                                title="Delete Class">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Students:</span>
                                            <span
                                                class="font-semibold"><?= $class['student_count'] ?>/<?= $class['capacity'] ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Subjects:</span>
                                            <span class="font-semibold"><?= $class['subject_count'] ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Level:</span>
                                            <span class="font-semibold"><?= htmlspecialchars($class['class_level']) ?></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Code:</span>
                                            <span
                                                class="font-semibold text-nskblue"><?= htmlspecialchars($class['class_code']) ?></span>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-4 border-t border-<?= $color['border'] ?> border-opacity-20">
                                        <div class="flex justify-between items-center gap-2">
                                            <button onclick="assignTeacher(<?= $class['id'] ?>)"
                                                class="bg-nskgold text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600 transition flex-1">
                                                <i class="fas fa-user-tie mr-2"></i> Assign Teacher
                                            </button>
                                            <button onclick="viewClassDetails(<?= $class['id'] ?>)"
                                                class="bg-<?= $color['button'] ?> text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition flex-1">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Class Timetable Tab -->
                <div id="timetableTab" class="tab-content hidden">
                    <div class="mb-6">
                        <div class="flex flex-wrap gap-4 items-center">
                            <select id="classSelect"
                                class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                <option value="">Select Class</option>
                                <?php foreach ($classesData as $class): ?>
                                    <option value="<?= htmlspecialchars($class['id']) ?>">
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button
                                class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition">
                                Load Timetable
                            </button>
                        </div>
                    </div>

                    <!-- Existing timetable content -->
                    <!-- Timetable Content -->
                    <div id="timetablePlaceholder"
                        class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300">
                        <i class="fas fa-calendar-alt text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 text-lg">Select a class and click "Load Timetable" to view the schedule.
                        </p>
                    </div>

                    <div id="timetableContent" class="overflow-x-auto hidden">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="bg-nsklightblue text-white p-3">Time/Day</th>
                                    <th class="bg-nsklightblue text-white p-3">Monday</th>
                                    <th class="bg-nsklightblue text-white p-3">Tuesday</th>
                                    <th class="bg-nsklightblue text-white p-3">Wednesday</th>
                                    <th class="bg-nsklightblue text-white p-3">Thursday</th>
                                    <th class="bg-nsklightblue text-white p-3">Friday</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Timetable rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Class Assignments Tab -->
                <div id="assignmentsTab" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-md p-12 text-center">
                        <div class="bg-blue-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-clipboard-list text-nskblue text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-nsknavy mb-2">No Active Assignments</h3>
                        <p class="text-gray-500 mb-6">There are no active assignments or quizzes for any class at the
                            moment.</p>
                        <button
                            class="bg-nskblue text-white px-6 py-2 rounded-lg font-semibold hover:bg-nsknavy transition">
                            <i class="fas fa-plus mr-2"></i> Create Assignment
                        </button>
                    </div>
                </div>

                <!-- Performance Analytics Tab -->
                <div id="performanceTab" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-md p-12 text-center">
                        <div class="bg-green-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-chart-line text-nskgreen text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-nsknavy mb-2">No Performance Data</h3>
                        <p class="text-gray-500 mb-6">Performance analytics will appear here once exam results are
                            uploaded.</p>
                        <button
                            class="bg-nskgreen text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-600 transition">
                            <i class="fas fa-upload mr-2"></i> Upload Results
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Class Modal -->
        <div id="createClassModal"
            class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            style="display: none;">
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Create New Class</h3>
                    <button id="closeCreateModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="createClassForm" class="space-y-4">
                    <input type="hidden" name="action" value="add_class">

                    <div>
                        <label class="block text-gray-700 mb-2" for="className">Class Name</label>
                        <input type="text" id="className" name="class_name"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                            placeholder="e.g., Primary 1, JSS 1, SS 1" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="classCode">Class Code</label>
                        <input type="text" id="classCode" name="class_code"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                            placeholder="e.g., P1, JSS1, SS1" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="classLevel">Class Level</label>
                        <select id="classLevel" name="class_level"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="">Select Level</option>
                            <option value="Early Childhood">Early Childhood</option>
                            <option value="Primary">Primary</option>
                            <option value="Secondary">Secondary</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="classTeacher">Class Teacher (Optional)</label>
                        <select id="classTeacher" name="class_teacher_id"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachersData as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>">
                                    <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                                    <?php if ($teacher['subject_specialization']): ?>
                                        (<?= htmlspecialchars($teacher['subject_specialization']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="maxStudents">Maximum Students</label>
                        <input type="number" id="maxStudents" name="capacity"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" min="20" max="50"
                            value="30" required>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" id="cancelCreateBtn"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-nskgreen text-white rounded-lg font-semibold hover:bg-green-600 transition">
                            Create Class
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteClassModal"
            class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4"
            style="display: none;">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 relative">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-nskred text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-nsknavy">Delete Class</h3>
                </div>

                <p class="text-gray-600 mb-6">
                    Are you sure you want to delete <strong id="deleteClassName" class="text-nsknavy"></strong>?
                    This action cannot be undone and will also delete:
                </p>

                <ul class="list-disc list-inside text-gray-600 mb-6 space-y-1">
                    <li>All subject assignments for this class</li>
                    <li>All timetable entries</li>
                    <li>Class-related data</li>
                </ul>

                <p class="text-sm text-nskred mb-6">
                    <i class="fas fa-info-circle mr-1"></i>
                    Note: Classes with enrolled students cannot be deleted.
                </p>

                <form id="deleteClassForm" method="POST" action="classes.php">
                    <input type="hidden" name="delete_class" value="1">
                    <input type="hidden" name="class_id" id="deleteClassId">

                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancelDeleteBtn"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-nskred text-white rounded-lg font-semibold hover:bg-red-600 transition">
                            <i class="fas fa-trash mr-2"></i>Delete Class
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Schedule Modal -->
        <div id="addScheduleModal"
            class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            style="display: none;">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Add New Schedule</h3>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="scheduleForm" class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2" for="subject">Subject</label>
                        <select id="subject" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                            required>
                            <option value="">Select Subject</option>
                            <option value="math">Mathematics</option>
                            <option value="science">Science</option>
                            <option value="english">English</option>
                            <option value="history">History</option>
                            <option value="art">Art</option>
                            <option value="pe">Physical Education</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2" for="day">Day</label>
                            <select id="day" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                                required>
                                <option value="">Select Day</option>
                                <option value="monday">Monday</option>
                                <option value="tuesday">Tuesday</option>
                                <option value="wednesday">Wednesday</option>
                                <option value="thursday">Thursday</option>
                                <option value="friday">Friday</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="period">Period</label>
                            <select id="period"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                <option value="">Select Period</option>
                                <option value="1">Period 1 (8:00-8:45)</option>
                                <option value="2">Period 2 (8:45-9:30)</option>
                                <option value="3">Period 3 (9:30-10:15)</option>
                                <option value="4">Period 4 (11:00-11:45)</option>
                                <option value="5">Period 5 (11:45-12:30)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="teacher">Teacher</label>
                        <select id="teacher" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                            required>
                            <option value="">Select Teacher</option>
                            <option value="johnson">Mr. Johnson (Mathematics)</option>
                            <option value="amina">Dr. Amina (Science)</option>
                            <option value="yusuf">Mr. Yusuf (English)</option>
                            <option value="kabir">Mr. Kabir (History)</option>
                            <option value="zainab">Mrs. Zainab (Art)</option>
                            <option value="ahmed">Coach Ahmed (PE)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="classroom">Classroom</label>
                        <select id="classroom"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="">Select Classroom</option>
                            <option value="201">Room 201</option>
                            <option value="105">Room 105</option>
                            <option value="112">Room 112</option>
                            <option value="lab1">Lab 1</option>
                            <option value="lab2">Lab 2</option>
                            <option value="lab3">Lab 3</option>
                            <option value="art">Art Room</option>
                            <option value="music">Music Room</option>
                            <option value="sports">Sports Field</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" id="cancelBtn"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                            Add Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-nsknavy text-white py-8 mt-12">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <div class="flex items-center space-x-2 mb-4">
                            <div
                                class="logo-container w-10 h-10 rounded-full flex items-center justify-center text-white font-bold">
                                NSK
                            </div>
                            <h3 class="text-xl font-bold">NORTHLAND SCHOOLS KANO</h3>
                        </div>
                        <p class="text-blue-100">Dream Big, Study Hard & Make It Happen</p>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold mb-4">Contact Info</h4>
                        <p class="text-blue-100 mb-2"><i class="fas fa-phone-alt mr-2"></i> +91-9950348952</p>
                        <p class="text-blue-100 mb-2"><i class="fas fa-envelope mr-2"></i> info@northlandschools.com</p>
                        <p class="text-blue-100"><i class="fas fa-map-marker-alt mr-2"></i> Kano, Nigeria</p>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-blue-100 hover:text-white transition">Dashboard</a></li>
                            <li><a href="#" class="text-blue-100 hover:text-white transition">Student Portal</a></li>
                            <li><a href="#" class="text-blue-100 hover:text-white transition">Teacher Resources</a></li>
                            <li><a href="#" class="text-blue-100 hover:text-white transition">Parent Guide</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-blue-800 mt-8 pt-8 text-center text-blue-200">
                    <p>&copy; 2023 Northland Schools Kano. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </main>

</body>
<script>
    // Global notification function used by classes_management.js
    function showNotification(message, type = 'success') {
        console.log(`[${type.toUpperCase()}] ${message}`);

        // Create a toast notification
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-nskgreen' : type === 'error' ? 'bg-nskred' : 'bg-blue-500';

        toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>${message}`;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Tab functionality
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function () {
                const tabName = this.getAttribute('data-tab');

                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                    btn.classList.add('border-gray-300', 'text-gray-700');
                    btn.classList.remove('border-nskblue', 'text-nskblue', 'bg-nskblue', 'text-white');
                });
                this.classList.add('active');
                this.classList.remove('border-gray-300', 'text-gray-700');
                this.classList.add('border-nskblue', 'bg-nskblue', 'text-white');

                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                const targetTab = document.getElementById(tabName + 'Tab');
                if (targetTab) {
                    targetTab.classList.remove('hidden');
                }
            });
        });

        // Class card animations
        document.querySelectorAll('.class-card').forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
            });
        });

        // Schedule modal functionality
        const modal = document.getElementById('addScheduleModal');
        const addScheduleBtn = document.getElementById('addScheduleBtn');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const scheduleForm = document.getElementById('scheduleForm');

        if (addScheduleBtn) {
            addScheduleBtn.addEventListener('click', function () {
                if (modal) modal.classList.add('active');
            });
        }

        function closeModalFunc() {
            if (modal) modal.classList.remove('active');
        }

        if (closeModal) closeModal.addEventListener('click', closeModalFunc);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModalFunc);

        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModalFunc();
            });
        }

        if (scheduleForm) {
            scheduleForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const subject = document.getElementById('subject').value;
                const day = document.getElementById('day').value;
                const period = document.getElementById('period').value;
                alert(`Schedule added for ${subject} on ${day} during period ${period}`);
                closeModalFunc();
                scheduleForm.reset();
            });
        }
    });
</script>

<!-- Include classes management JavaScript - handles all buttons and modals -->
<script src="classes_management.js"></script>

</html>