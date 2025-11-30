<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// session_start();

// --- ADD THIS LINE ---
require_once '../config/logger.php';
// --- END ---

require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');
// Initialize variables
$totalTeachers = $totalSubjects = $seniorStaff = 0;
$teachersData = [];
$departments = [];
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));

// Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// === HELPER FUNCTIONS ===

function addTeacher($db)
{
    try {
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
            throw new Exception("First name, last name, and email are required.");
        }

        $db->beginTransaction();

        // 1. Create User
        $username = strtolower($_POST['first_name']) . '.' . strtolower($_POST['last_name']);
        $usernameCheckStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $usernameCheckStmt->execute([$username]);
        if ($usernameCheckStmt->fetchColumn() > 0) {
            $username = $username . rand(1, 999);
        }

        $userSql = "INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, date_of_birth, gender, is_active) 
                      VALUES (?, ?, ?, 'teacher', ?, ?, ?, ?, ?, 1)";
        $userStmt = $db->prepare($userSql);
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);

        $userStmt->execute([
            $username,
            $_POST['email'],
            $password_hash,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'] ?? null,
            $_POST['date_of_birth'] ?? null,
            $_POST['gender'] ?? 'Male'
        ]);
        $userId = $db->lastInsertId();

        // 2. Generate Teacher ID
        $teacherId = 'TCH' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ?");
        $checkStmt->execute([$teacherId]);
        if ($checkStmt->fetchColumn() > 0) {
            $teacherId = 'TCH' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
        }

        // 3. Create Teacher (HR/Payroll record) - TRUNCATE qualification if too long
        $qualification = $_POST['qualification'] ?? null;
        if ($qualification && strlen($qualification) > 100) {
            $qualification = substr($qualification, 0, 100) . '...';
        }

        $teacherSql = "INSERT INTO teachers (user_id, teacher_id, qualification, specialization, employment_date, is_class_teacher) 
                         VALUES (?, ?, ?, ?, ?, 0)";
        $teacherStmt = $db->prepare($teacherSql);
        $teacherStmt->execute([
            $userId,
            $teacherId,
            $qualification,
            null,
            $_POST['employment_date'] ?? null
        ]);

        // 4. Create Teacher Profile (Academic record) - qualification can be longer here
        $subjects = isset($_POST['subjects']) ? implode(', ', $_POST['subjects']) : '';
        $profileSql = "INSERT INTO teacher_profiles (user_id, qualification, subject_specialization, department, employment_type, teacher_id) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $profileStmt = $db->prepare($profileSql);
        $profileStmt->execute([
            $userId,
            $_POST['qualification'] ?? null, // Full qualification here
            $subjects,
            $_POST['department'] ?? null,
            $_POST['employment_type'] ?? 'Full-time',
            $teacherId
        ]);

        $db->commit();
        $_SESSION['success'] = "Teacher added successfully! Teacher ID: " . $teacherId;

        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $teacher_name = $_POST['first_name'] . ' ' . $_POST['last_name'];
        logActivity(
            $db,
            $admin_name,
            "New Teacher",
            "Added teacher: $teacher_name ($teacherId)",
            "fas fa-chalkboard-teacher",
            "bg-nskgreen"
        );
        // --- END LOG ---

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error adding teacher: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

function getTeacherData($db, $teacher_id)
{
    try {
        $sql = "
            SELECT 
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.date_of_birth,
                u.gender,
                u.address,
                t.teacher_id,
                t.employment_date,
                tp.qualification,
                tp.subject_specialization,
                tp.department,
                tp.employment_type,
                tp.years_experience
            FROM users u
            LEFT JOIN teachers t ON u.id = t.user_id
            LEFT JOIN teacher_profiles tp ON u.id = tp.user_id
            WHERE t.teacher_id = ? AND u.user_type = 'teacher' AND u.is_active = 1
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$teacher_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

function updateTeacher($db)
{
    try {
        if (empty($_POST['teacher_id']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
            throw new Exception("Required fields are missing.");
        }

        $db->beginTransaction();
        $teacher_id = $_POST['teacher_id'];

        // Get user_id
        $userStmt = $db->prepare("SELECT user_id FROM teachers WHERE teacher_id = ?");
        $userStmt->execute([$teacher_id]);
        $user_id = $userStmt->fetchColumn();

        if (!$user_id) {
            throw new Exception("Invalid Teacher ID.");
        }

        // 1. Update Users table
        $userSql = "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        date_of_birth = ?, 
                        gender = ?
                    WHERE id = ?";
        $userStmt = $db->prepare($userSql);
        $userStmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'] ?? null,
            $_POST['date_of_birth'] ?? null,
            $_POST['gender'] ?? 'Male',
            $user_id
        ]);

        // 2. Update Teachers table - TRUNCATE qualification if too long
        $qualification = $_POST['qualification'] ?? null;
        if ($qualification && strlen($qualification) > 100) {
            $qualification = substr($qualification, 0, 100) . '...';
        }

        $teacherSql = "UPDATE teachers SET 
                           qualification = ?, 
                           specialization = ?,  
                           employment_date = ?
                       WHERE teacher_id = ?";
        $teacherStmt = $db->prepare($teacherSql);
        $teacherStmt->execute([
            $qualification,
            null,
            $_POST['employment_date'] ?? null,
            $teacher_id
        ]);

        // 3. Update Teacher Profiles table - qualification can be longer here
        $subjects = isset($_POST['subjects']) ? implode(', ', $_POST['subjects']) : '';
        $profileSql = "UPDATE teacher_profiles SET 
                           qualification = ?, 
                           subject_specialization = ?, 
                           department = ?, 
                           employment_type = ?
                       WHERE teacher_id = ?";
        $profileStmt = $db->prepare($profileSql);
        $profileStmt->execute([
            $_POST['qualification'] ?? null, // Full qualification here
            $subjects,
            $_POST['department'] ?? null,
            $_POST['employment_type'] ?? 'Full-time',
            $teacher_id
        ]);

        $db->commit();
        $_SESSION['success'] = "Teacher updated successfully!";

        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $teacher_name = $_POST['first_name'] . ' ' . $_POST['last_name'];
        logActivity(
            $db,
            $admin_name,
            "Teacher Updated",
            "Updated profile for: $teacher_name ($teacher_id)",
            "fas fa-user-edit",
            "bg-nskgold"
        );
        // --- END LOG ---

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error updating teacher: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

function deleteTeacher($db, $teacher_id)
{
    try {
        // Get user_id and name first
        $stmt = $db->prepare("SELECT t.user_id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $teacherInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacherInfo) {
            throw new Exception("Teacher not found.");
        }
        $user_id = $teacherInfo['user_id'];
        $teacher_name = $teacherInfo['first_name'] . ' ' . $teacherInfo['last_name'];

        // Soft delete: Update the is_active flag in the users table to 0
        $softDeleteSql = "UPDATE users SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($softDeleteSql);
        $stmt->execute([$user_id]);

        $_SESSION['success'] = "Teacher deactivated successfully!";

        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Teacher Deactivated",
            "Deactivated teacher: $teacher_name ($teacher_id)",
            "fas fa-user-minus",
            "bg-nskred"
        );
        // --- END LOG ---

        return true;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deactivating teacher: " . $e->getMessage();
        return false;
    }
}

function exportTeachersCSV($db, $searchQuery, $departmentFilter)
{
    try {
        // Build the base query
        $queryParts = [
            "SELECT u.first_name, u.last_name, u.email, u.phone, u.gender, u.date_of_birth, t.teacher_id, t.employment_date, tp.qualification, tp.subject_specialization, tp.department, tp.employment_type",
            "FROM users u",
            "LEFT JOIN teachers t ON u.id = t.user_id",
            "LEFT JOIN teacher_profiles tp ON u.id = tp.user_id",
            "WHERE u.user_type = 'teacher' AND u.is_active = 1"
        ];
        $params = [];

        // Add search filter
        if (!empty($searchQuery)) {
            $queryParts[] = "AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR t.teacher_id LIKE ? OR tp.department LIKE ? OR tp.subject_specialization LIKE ?)";
            $searchParam = "%{$searchQuery}%";
            array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
        }

        // Add department filter
        if (!empty($departmentFilter)) {
            $queryParts[] = "AND tp.department = ?";
            $params[] = $departmentFilter;
        }

        $queryParts[] = "ORDER BY u.first_name";
        $sql = implode(" ", $queryParts);

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($exportData)) {
            $_SESSION['error'] = "No teachers to export.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }

        // Set headers for CSV download
        $filename = "teachers_export_" . date('Y-m-d_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Add BOM

        // Add CSV headers
        fputcsv($output, [
            'Teacher ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Gender',
            'Date of Birth',
            'Employment Date',
            'Department',
            'Qualification',
            'Subjects',
            'Employment Type'
        ]);

        // Add data rows
        foreach ($exportData as $teacher) {
            fputcsv($output, [
                $teacher['teacher_id'],
                $teacher['first_name'],
                $teacher['last_name'],
                $teacher['email'],
                $teacher['phone'] ?? 'N/A',
                $teacher['gender'] ?? 'N/A',
                $teacher['date_of_birth'] ?? 'N/A',
                $teacher['employment_date'] ?? 'N/A',
                $teacher['department'] ?? 'N/A',
                $teacher['qualification'] ?? 'N/A',
                $teacher['subject_specialization'] ?? 'N/A',
                $teacher['employment_type'] ?? 'N/A'
            ]);
        }

        fclose($output);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Export failed: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}


// === POST/GET HANDLERS ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher'])) {
        addTeacher($db);
    }

    if (isset($_POST['update_teacher'])) {
        updateTeacher($db);
    }

    if (isset($_POST['delete_teacher']) && isset($_POST['teacher_id'])) {
        deleteTeacher($db, $_POST['teacher_id']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['export_csv'])) {
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        $departmentFilter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';
        exportTeachersCSV($db, $searchQuery, $departmentFilter);
    }

    if (isset($_POST['hide_add_form'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$viewTeacher = null;
$editTeacher = null;

if (isset($_GET['view']) && !empty($_GET['view'])) {
    $viewTeacher = getTeacherData($db, $_GET['view']);
}

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editTeacher = getTeacherData($db, $_GET['edit']);
}


// === DATA FETCHING FOR PAGE LOAD ===

// Get filter parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$departmentFilter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';

try {
    // Build the main query
    $queryParts = [
        "SELECT u.id as user_id, u.first_name, u.last_name, u.phone, u.email, t.teacher_id, t.employment_date, tp.qualification, tp.subject_specialization, tp.department",
        "FROM users u",
        "LEFT JOIN teachers t ON u.id = t.user_id",
        "LEFT JOIN teacher_profiles tp ON u.id = tp.user_id",
        "WHERE u.user_type = 'teacher' AND u.is_active = 1"
    ];
    $params = [];

    // Add search filter
    if (!empty($searchQuery)) {
        $queryParts[] = "AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR t.teacher_id LIKE ? OR tp.department LIKE ? OR tp.subject_specialization LIKE ?)";
        $searchParam = "%{$searchQuery}%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Add department filter
    if (!empty($departmentFilter)) {
        $queryParts[] = "AND tp.department = ?";
        $params[] = $departmentFilter;
    }

    $queryParts[] = "ORDER BY u.first_name";
    $sql = implode(" ", $queryParts);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $teachersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats
    $totalTeachers = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'teacher' AND is_active = 1")->fetchColumn();
    $totalSubjects = $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $seniorStaff = $db->query("SELECT COUNT(*) FROM teachers WHERE employment_date <= DATE_SUB(NOW(), INTERVAL 5 YEAR)")->fetchColumn();

    // Get departments for filter
    $departments = $db->query("SELECT DISTINCT department FROM teacher_profiles WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
}

// Get all subjects for Modals
$all_subjects = $db->query("SELECT subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management - Northland Schools Kano</title>
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

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.active {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            margin: 20px;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        .teacher-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .teacher-card:hover {
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

        .teacher-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .teacher-table th {
            background-color: #f8fafc;
        }

        .teacher-table tr:last-child td {
            border-bottom: 0;
        }

        .subject-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>

    <link rel="stylesheet" href="sidebar.css">
</head>

<body class="flex">
    <div id="sidebar-container"></div>
    <?php require_once 'sidebar.php'; ?>


    <main class="main-content">
        <?php
        $pageTitle = 'Teacher Management';
        require_once 'header.php';
        ?>

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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="teacher-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nsklightblue p-4 rounded-full mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Teachers</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $totalTeachers ?></p>
                        <p class="text-xs text-gray-600">Active Staff</p>
                    </div>
                </div>

                <div class="teacher-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-book text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Subjects</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $totalSubjects ?></p>
                        <p class="text-xs text-gray-600">In Curriculum</p>
                    </div>
                </div>

                <div class="teacher-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-award text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Senior Staff</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $seniorStaff ?></p>
                        <p class="text-xs text-nskgreen">5+ years experience</p>
                    </div>
                </div>

                <div class="teacher-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskred p-4 rounded-full mr-4">
                        <i class="fas fa-briefcase text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Vacancies</p>
                        <p class="text-2xl font-bold text-nsknavy">5</p>
                        <p class="text-xs text-nskred">Urgent hiring needed</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <form method="GET" action="" class="space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-nsknavy">All Teachers</h2>

                        <div class="flex flex-wrap gap-4">
                            <div class="relative">
                                <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                                    <i class="fas fa-search text-gray-500"></i>
                                    <input type="text" name="search" placeholder="Search teachers..."
                                        class="bg-transparent outline-none w-32 md:w-64"
                                        value="<?= htmlspecialchars($searchQuery) ?>">
                                </div>
                            </div>

                            <select name="department_filter"
                                class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['department']) ?>"
                                        <?= $departmentFilter == $dept['department'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit"
                                class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>

                            <?php if (!empty($searchQuery) || !empty($departmentFilter)): ?>
                                <a href="teachers-management.php"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition flex items-center">
                                    <i class="fas fa-times mr-2"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="flex flex-wrap gap-4 mt-6">
                    <form method="POST" action="" class="inline">
                        <?php if (!isset($_POST['show_add_form'])): ?>
                            <button type="submit" name="show_add_form" value="true"
                                class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Teacher
                            </button>
                        <?php else: ?>
                            <button type="submit" name="hide_add_form" value="true"
                                class="bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition flex items-center">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </button>
                        <?php endif; ?>
                    </form>

                    <form method="POST" action="" class="inline">
                        <button type="submit" name="export_csv"
                            class="bg-nskgold text-white px-4 py-2 rounded-lg font-semibold hover:bg-amber-600 transition flex items-center">
                            <i class="fas fa-file-export mr-2"></i> Export
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="min-w-full teacher-table">
                        <thead>
                            <tr>
                                <th class="py-3 px-6 text-left text-nsknavy">Teacher</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Contact</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Department & Subjects</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Joined</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="teachersTableBody">
                            <?php if (empty($teachersData)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 px-6 text-center text-gray-500">
                                        <i class="fas fa-chalkboard-teacher text-4xl mb-4"></i>
                                        <p>No teachers found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachersData as $teacher): ?>
                                    <tr>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 rounded-full <?= getAvatarColor($teacher['first_name']) ?> flex items-center justify-center text-white font-bold mr-3">
                                                    <?= strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold">
                                                        <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <?= htmlspecialchars($teacher['qualification'] ?? 'N/A') ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">ID:
                                                        <?= htmlspecialchars($teacher['teacher_id']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <p class="text-sm"><?= htmlspecialchars($teacher['email']) ?></p>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <p class="font-medium text-nsknavy">
                                                <?= htmlspecialchars($teacher['department'] ?? 'N/A') ?>
                                            </p>
                                            <p class="text-sm text-gray-600 truncate" style="max-width: 250px;"
                                                title="<?= htmlspecialchars($teacher['subject_specialization']) ?>">
                                                <?= htmlspecialchars($teacher['subject_specialization'] ?? 'N/A') ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="status-badge bg-green-100 text-nskgreen">Active</span>
                                            <p class="text-xs text-gray-600 mt-1">
                                                Joined:
                                                <?= !empty($teacher['employment_date']) ? date('M j, Y', strtotime($teacher['employment_date'])) : 'N/A' ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex space-x-2">
                                                <button
                                                    class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50"
                                                    onclick="viewTeacher('<?= $teacher['teacher_id'] ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button
                                                    class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50"
                                                    onclick="editTeacher('<?= $teacher['teacher_id'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50"
                                                    onclick="deleteTeacher('<?= $teacher['teacher_id'] ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-nsknavy mb-6">Department Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                </div>
            </div>
        </div>

        <?php if (isset($_POST['show_add_form'])): ?>
            <div id="addTeacherModal" class="modal active p-4">
                <div class="modal-content">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-nsknavy">Add New Teacher</h3>
                        <a href="teachers-management.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>

                    <form method="POST" action="" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="firstName">First Name *</label>
                                <input type="text" name="first_name"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="lastName">Last Name *</label>
                                <input type="text" name="last_name"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="email">Email Address *</label>
                                <input type="email" name="email"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="phone">Phone Number</label>
                                <input type="tel" name="phone"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="gender">Gender</label>
                                <select name="gender"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="date_of_birth">Date of Birth</label>
                                <input type="date" name="date_of_birth"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="qualification">Highest Qualification</label>
                                <input type="text" name="qualification" placeholder="e.g., B.Sc. Education"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="department">Department</label>
                                <select name="department"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['department']) ?>">
                                            <?= htmlspecialchars($dept['department']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2">Subjects (Check all that apply)</label>
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-32 overflow-y-auto p-2 border rounded-lg">
                                <?php foreach ($all_subjects as $subject): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="subjects[]"
                                            id="subject_<?= htmlspecialchars($subject['subject_name']) ?>" class="mr-2"
                                            value="<?= htmlspecialchars($subject['subject_name']) ?>">
                                        <label for="subject_<?= htmlspecialchars($subject['subject_name']) ?>"
                                            class="text-sm"><?= htmlspecialchars($subject['subject_name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="employment_type">Employment Type</label>
                                <select name="employment_type"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="employment_date">Join Date</label>
                                <input type="date" name="employment_date"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="submit" name="hide_add_form"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" name="add_teacher"
                                class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                                Add Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($editTeacher): ?>
            <div id="editTeacherModal" class="modal active p-4">
                <div class="modal-content">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-nsknavy">Edit Teacher</h3>
                        <a href="teachers-management.php" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>

                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($editTeacher['teacher_id']) ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">First Name *</label>
                                <input type="text" name="first_name" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['first_name']) ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Last Name *</label>
                                <input type="text" name="last_name" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Email Address *</label>
                                <input type="email" name="email" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['email']) ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" name="phone" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['phone']) ?>">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Gender</label>
                                <select name="gender" class="w-full px-4 py-2 border rounded-lg">
                                    <option value="Male" <?= $editTeacher['gender'] == 'Male' ? 'selected' : '' ?>>Male
                                    </option>
                                    <option value="Female" <?= $editTeacher['gender'] == 'Female' ? 'selected' : '' ?>>Female
                                    </option>
                                    <option value="Other" <?= $editTeacher['gender'] == 'Other' ? 'selected' : '' ?>>Other
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['date_of_birth']) ?>">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Highest Qualification</label>
                                <input type="text" name="qualification" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['qualification']) ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Department</label>
                                <select name="department" class="w-full px-4 py-2 border rounded-lg">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['department']) ?>"
                                            <?= $editTeacher['department'] == $dept['department'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['department']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2">Subjects</label>
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-32 overflow-y-auto p-2 border rounded-lg">
                                <?php
                                $assigned_subjects = array_map('trim', explode(',', $editTeacher['subject_specialization']));
                                ?>
                                <?php foreach ($all_subjects as $subject): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="subjects[]"
                                            id="edit_subject_<?= htmlspecialchars($subject['subject_name']) ?>" class="mr-2"
                                            value="<?= htmlspecialchars($subject['subject_name']) ?>"
                                            <?= in_array($subject['subject_name'], $assigned_subjects) ? 'checked' : '' ?>>
                                        <label for="edit_subject_<?= htmlspecialchars($subject['subject_name']) ?>"
                                            class="text-sm"><?= htmlspecialchars($subject['subject_name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Employment Type</label>
                                <select name="employment_type" class="w-full px-4 py-2 border rounded-lg">
                                    <option value="Full-time" <?= $editTeacher['employment_type'] == 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                                    <option value="Part-time" <?= $editTeacher['employment_type'] == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                    <option value="Contract" <?= $editTeacher['employment_type'] == 'Contract' ? 'selected' : '' ?>>Contract</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Join Date</label>
                                <input type="date" name="employment_date" class="w-full px-4 py-2 border rounded-lg"
                                    value="<?= htmlspecialchars($editTeacher['employment_date']) ?>">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <a href="teachers-management.php"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </a>
                            <button type="submit" name="update_teacher"
                                class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                                Update Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($viewTeacher): ?>
            <div id="viewTeacherModal" class="modal active">
                <div class="modal-content" style="max-width: 900px;">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h3 class="text-2xl font-bold text-nsknavy">Teacher Details</h3>
                        <a href="teachers-management.php" class="text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </a>
                    </div>

                    <div id="viewTeacherContent" class="space-y-6">
                    </div>

                    <div class="mt-8 flex justify-end space-x-3 border-t pt-4">
                        <a href="teachers-management.php"
                            class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition flex items-center">
                            <i class="fas fa-times mr-2"></i> Close
                        </a>
                        <button onclick="editTeacher('<?= $viewTeacher['teacher_id'] ?>')"
                            class="bg-nskblue text-white px-6 py-2 rounded-lg hover:bg-nsknavy transition flex items-center">
                            <i class="fas fa-edit mr-2"></i> Edit Teacher
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <script src="footer.js"></script>

    </main>

    <?php
    // PHP Helper Function for Avatar Colors
    function getAvatarColor($name)
    {
        $colors = ['bg-nskblue', 'bg-nskgreen', 'bg-nskgold', 'bg-nskred', 'bg-purple-500'];
        $index = empty($name) ? 0 : ord($name[0]) % count($colors);
        return $colors[$index];
    }
    ?>

    <script>
        // === AVATAR COLOR HELPER (JS) ===
        function getAvatarColor(name) {
            if (!name) return 'bg-nskblue';
            const colors = ['bg-nskblue', 'bg-nskgreen', 'bg-nskgold', 'bg-nskred', 'bg-purple-500'];
            const index = name.charCodeAt(0) % colors.length;
            return colors[index];
        }

        // === MODAL AND ACTION HANDLERS ===

        function viewTeacher(teacherId) {
            window.location.href = `teachers-management.php?view=${teacherId}`;
        }

        function editTeacher(teacherId) {
            window.location.href = `teachers-management.php?edit=${teacherId}`;
        }

        function deleteTeacher(teacherId) {
            if (confirm('Are you sure you want to deactivate this teacher?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'teachers-management.php';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'teacher_id';
                idInput.value = teacherId;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_teacher';
                deleteInput.value = '1';

                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // === MODAL-CLOSING HANDLERS ===

        function closeAllModals() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('active');
            });
            // Clean up the URL
            if (window.location.search.includes('view=') || window.location.search.includes('edit=') || window.location.search.includes('show_add_form=')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                // Find the close button/link inside the modal content
                const closeButton = event.target.querySelector('a[href="teachers-management.php"], button[name="hide_add_form"]');
                if (closeButton) {
                    closeButton.click();
                } else {
                    // Fallback for view modal
                    window.location.href = 'teachers-management.php';
                }
            }
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                // Find any active close button/link
                const closeButton = document.querySelector('.modal.active a[href="teachers-management.php"], .modal.active button[name="hide_add_form"]');
                if (closeButton) {
                    closeButton.click();
                } else {
                    window.location.href = 'teachers-management.php';
                }
            }
        });

        // === VIEW MODAL LOADER ===
        document.addEventListener('DOMContentLoaded', function () {
            <?php if ($viewTeacher): ?>
                loadViewTeacherData(<?= json_encode($viewTeacher) ?>);
            <?php endif; ?>
        });

        function loadViewTeacherData(teacher) {
            const infoRow = (label, value) => {
                const val = value || 'N/A';
                return `
                    <div class="flex flex-col sm:flex-row sm:justify-between py-3 border-b border-gray-100 last:border-b-0">
                        <strong class="text-sm font-medium text-gray-500 w-full sm:w-1/3 flex-shrink-0">${label}</strong>
                        <span class="text-sm text-gray-900 text-left sm:text-right w-full sm:w-2/3">${val}</span>
                    </div>
                `;
            };

            const infoBlock = (label, value) => {
                const val = value || 'None specified.';
                return `
                    <div class="py-3">
                        <strong class="text-sm font-medium text-gray-500">${label}</strong>
                        <p class="text-sm text-gray-900 mt-1 whitespace-pre-wrap">${val}</p>
                    </div>
                `;
            };

            const content = `
                <div class="bg-white rounded-lg p-0 sm:p-4">
                    <div class="flex flex-col sm:flex-row items-center mb-6">
                        <div class="w-20 h-20 rounded-full ${getAvatarColor(teacher.first_name)} flex items-center justify-center text-white font-bold text-3xl mr-0 sm:mr-6 mb-4 sm:mb-0 flex-shrink-0">
                            ${teacher.first_name ? teacher.first_name.charAt(0) : ''}${teacher.last_name ? teacher.last_name.charAt(0) : ''}
                        </div>
                        <div class="text-center sm:text-left">
                            <h2 class="text-3xl font-bold text-nsknavy">${teacher.first_name} ${teacher.last_name}</h2>
                            <div class="flex flex-col sm:flex-row sm:space-x-4 text-gray-600 mt-1">
                                <span>Teacher ID: <strong>${teacher.teacher_id}</strong></span>
                                <span>Department: <strong>${teacher.department || 'N/A'}</strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                        <div class="space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-semibold text-nsknavy border-b border-gray-200 pb-2">Personal Information</h3>
                            <div class="flow-root">
                                ${infoRow('Email', teacher.email)}
                                ${infoRow('Phone', teacher.phone)}
                                ${infoRow('Date of Birth', teacher.date_of_birth ? new Date(teacher.date_of_birth).toLocaleDateString() : 'N/A')}
                                ${infoRow('Gender', teacher.gender)}
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h3 class="text-lg font-semibold text-nsknavy border-b border-gray-200 pb-2">Professional Details</h3>
                                <div class="flow-root">
                                    ${infoRow('Qualification', teacher.qualification)}
                                    ${infoRow('Employment Type', teacher.employment_type)}
                                    ${infoRow('Employment Date', teacher.employment_date ? new Date(teacher.employment_date).toLocaleDateString() : 'N/A')}
                                    ${infoRow('Years Experience', teacher.years_experience)}
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <h3 class="text-lg font-semibold text-nskblue border-b border-blue-200 pb-2 flex items-center">
                                    <i class="fas fa-book-open mr-2"></i> Subjects
                                </h3>
                                <div class="flow-root">
                                    ${infoBlock('Specialization', teacher.subject_specialization)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('viewTeacherContent').innerHTML = content;
        }
    </script>
</body>

</html>