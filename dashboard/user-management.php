<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');
// Initialize variables
$stats = ['total_users' => 0, 'teachers' => 0, 'students' => 0, 'parents' => 0];
$usersData = [];
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

function addUser($db)
{
    try {
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['user_type'])) {
            throw new Exception("First name, last name, email, and role are required.");
        }

        $user_type = $_POST['user_type'];

        $db->beginTransaction();

        // 1. Create User
        $username = strtolower($_POST['first_name']) . '.' . strtolower($_POST['last_name']);
        $usernameCheckStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $usernameCheckStmt->execute([$username]);
        if ($usernameCheckStmt->fetchColumn() > 0) {
            $username = $username . rand(1, 999);
        }

        // 1. Create User (This part is updated to include gender/DOB)
        $userSql = "INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, is_active, gender, date_of_birth) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $userStmt = $db->prepare($userSql);
        $password_hash = password_hash('password123', PASSWORD_DEFAULT); // Default password

        $userStmt->execute([
            $username,
            $_POST['email'],
            $password_hash,
            $user_type,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'] ?? null,
            $_POST['is_active'] ?? 1,
            $_POST['gender'] ?? null,      // <-- ADDED
            $_POST['date_of_birth'] ?? null // <-- ADDED
        ]);
        $userId = $db->lastInsertId();

        // 2. Create profile based on user type
        if ($user_type === 'teacher') {
            // --- This block is now synced with teachers-management.php ---

            // 2a. Generate Teacher ID
            $teacherId = 'TCH' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM teachers WHERE teacher_id = ?");
            $checkStmt->execute([$teacherId]);
            if ($checkStmt->fetchColumn() > 0) {
                $teacherId = 'TCH' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
            }

            // 2b. Create Teacher (HR record)
            $qualification = $_POST['qualification'] ?? null;
            if ($qualification && strlen($qualification) > 100) {
                $qualification = substr($qualification, 0, 100) . '...'; // Truncate
            }

            $teacherSql = "INSERT INTO teachers (user_id, teacher_id, qualification, specialization, employment_date, is_class_teacher) 
                             VALUES (?, ?, ?, ?, ?, 0)";
            $teacherStmt = $db->prepare($teacherSql);
            $teacherStmt->execute([
                $userId,
                $teacherId,
                $qualification, // Truncated qualification
                null,
                $_POST['employment_date'] ?? null // <-- FIXED (no more date('Y-m-d'))
            ]);

            // 2c. Create Teacher Profile (Academic record)
            $profileSql = "INSERT INTO teacher_profiles (user_id, qualification, subject_specialization, department, employment_type, teacher_id) 
                             VALUES (?, ?, ?, ?, ?, ?)";
            $profileStmt = $db->prepare($profileSql);
            $profileStmt->execute([
                $userId,
                $_POST['qualification'] ?? null, // Full qualification
                $_POST['subjects'] ?? null,      // <-- FIXED
                $_POST['department'] ?? null,    // <-- FIXED
                $_POST['employment_type'] ?? 'Full-time', // <-- FIXED
                $teacherId
            ]);
        } else if ($user_type === 'staff') {
            $staffId = 'STF' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
            $profileSql = "INSERT INTO staff_profiles (user_id, staff_id, department, position) VALUES (?, ?, ?, ?)";
            $profileStmt = $db->prepare($profileSql);
            $profileStmt->execute([$userId, $staffId, $_POST['department'] ?? '', $_POST['position'] ?? '']);
        } else if ($user_type === 'admin') {
            $adminId = 'ADM' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
            $profileSql = "INSERT INTO admin_profiles (user_id, admin_id, admin_level, department_access) VALUES (?, ?, ?, ?)";
            $profileStmt = $db->prepare($profileSql);
            $profileStmt->execute([$userId, $adminId, $_POST['admin_level'] ?? '', $_POST['department_access'] ?? '']);
        }
        // 'principal' doesn't have a separate table in your schema

        $db->commit();
        $_SESSION['success'] = "User added successfully! Username: $username";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Error adding user: " . $e->getMessage();
    }
}

function updateUser($db)
{
    try {
        if (empty($_POST['user_id']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['user_type'])) {
            throw new Exception("Required fields are missing.");
        }

        $sql = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    user_type = ?, 
                    is_active = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'] ?? null,
            $_POST['user_type'],
            $_POST['is_active'],
            $_POST['user_id']
        ]);

        $_SESSION['success'] = "User updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating user: " . $e->getMessage();
    }
}

function softDeleteUser($db, $user_id)
{
    try {
        $sql = "UPDATE users SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "User deactivated successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deactivating user: " . $e->getMessage();
    }
}

function resetUserPassword($db, $user_id)
{
    try {
        $new_password_hash = password_hash('password123', PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$new_password_hash, $user_id]);
        $_SESSION['success'] = "User password reset to 'password123' successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error resetting password: " . $e->getMessage();
    }
}

function getUserData($db, $user_id)
{
    $stmt = $db->prepare("SELECT id, first_name, last_name, email, phone, user_type, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getRoleBadge($role)
{
    switch ($role) {
        case 'admin':
            return '<span class="role-badge bg-blue-100 text-nskblue">Administrator</span>';
        case 'teacher':
            return '<span class="role-badge bg-green-100 text-nskgreen">Teacher</span>';
        case 'staff':
            return '<span class="role-badge bg-purple-100 text-purple-700">Staff</span>';
        case 'principal':
            return '<span class="role-badge bg-red-100 text-nskred">Principal</span>';
        default:
            return '<span class="role-badge bg-gray-100 text-gray-700">' . ucfirst($role) . '</span>';
    }
}

function getStatusBadge($is_active)
{
    if ($is_active) {
        return '<span class="status-badge bg-green-100 text-nskgreen">Active</span>';
    } else {
        return '<span class="status-badge bg-gray-100 text-gray-700">Inactive</span>';
    }
}


// === POST/GET HANDLERS ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        addUser($db);
    }
    if (isset($_POST['update_user'])) {
        updateUser($db);
    }
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        softDeleteUser($db, $_POST['user_id']);
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh page
        exit();
    }
    if (isset($_POST['reset_password']) && isset($_POST['user_id'])) {
        resetUserPassword($db, $_POST['user_id']);
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh page
        exit();
    }
    if (isset($_POST['hide_modal_form'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$editUserData = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editUserData = getUserData($db, $_GET['edit']);
}


// === DATA FETCHING FOR PAGE LOAD ===

// Get filter parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';
$statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

try {
    // Stats
    $stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE user_type != 'student'")->fetchColumn();
    $stats['teachers'] = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'teacher' AND is_active = 1")->fetchColumn();
    $stats['students'] = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'student' AND is_active = 1")->fetchColumn();
    $stats['parents'] = 0; // Parent table/role not defined in schema

    // Build main query
    $queryParts = ["SELECT id, first_name, last_name, email, user_type, is_active, last_login FROM users WHERE user_type != 'student'"];
    $params = [];

    // Add search filter
    if (!empty($searchQuery)) {
        $queryParts[] = "AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
        $searchParam = "%{$searchQuery}%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Add role filter
    if (!empty($roleFilter)) {
        $queryParts[] = "AND user_type = ?";
        $params[] = $roleFilter;
    }

    // Add status filter
    if ($statusFilter !== '') {
        $queryParts[] = "AND is_active = ?";
        $params[] = $statusFilter;
    }

    $queryParts[] = "ORDER BY first_name, last_name";
    $sql = implode(" ", $queryParts);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $usersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Northland Schools Kano</title>
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
            max-width: 500px;
            /* Adjusted max-width */
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .logo-container {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }

        .user-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .user-card:hover {
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

        .user-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .user-table th {
            background-color: #f8fafc;
        }

        .user-table tr:last-child td {
            border-bottom: 0;
        }

        .role-badge {
            padding: 4px 12px;
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

        /* Multi-step modal styles */
        .step-indicator {
            transition: all 0.3s ease;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .step-indicator.active {
            background: #1e40af;
            color: white;
            border-color: #1e40af;
        }

        .step-indicator.completed {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }

        .step-content {
            transition: all 0.3s ease;
        }

        .role-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem 1rem;
            text-align: center;
            background: white;
        }

        .role-card:hover {
            transform: translateY(-2px);
            border-color: #1e40af;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.1);
        }

        .role-card.selected {
            border-color: #1e40af;
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
        }

        .input-field {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-field input,
        .input-field select,
        .input-field textarea {
            width: 100%;
            padding: 0.75rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            color: #374151;
            transition: all 0.3s ease;
        }

        .input-field input:focus,
        .input-field select:focus,
        .input-field textarea:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2);
        }

        .input-field label {
            position: absolute;
            left: 0.75rem;
            top: 0.75rem;
            color: #6b7280;
            pointer-events: none;
            transition: all 0.3s ease;
            background: white;
            padding: 0 0.25rem;
            font-size: 0.875rem;
        }

        .input-field input:focus+label,
        .input-field input:not(:placeholder-shown)+label,
        .input-field select:focus+label,
        .input-field select:not([value=""])+label,
        .input-field textarea:focus+label,
        .input-field textarea:not(:placeholder-shown)+label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: #1e40af;
        }

        .input-field input::placeholder,
        .input-field textarea::placeholder {
            color: transparent;
        }

        .input-field input:placeholder-shown+label,
        .input-field textarea:placeholder-shown+label {
            top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: #1e40af;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #1e3a8a;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }
    </style>
</head>

<body class="flex">
    <?php require_once 'sidebar.php'; ?>
    <main class="main-content">
        <?php
        $pageTitle = 'User Management';
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
                <div class="user-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nsklightblue p-4 rounded-full mr-4">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $stats['total_users'] ?></p>
                        <p class="text-xs text-gray-600">Admins, Teachers, Staff</p>
                    </div>
                </div>

                <div class="user-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgreen p-4 rounded-full mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Teachers</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $stats['teachers'] ?></p>
                        <p class="text-xs text-gray-600">Active teaching staff</p>
                    </div>
                </div>

                <div class="user-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nskgold p-4 rounded-full mr-4">
                        <i class="fas fa-user-graduate text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Students</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $stats['students'] ?></p>
                        <p class="text-xs text-nskgreen">Total active enrollment</p>
                    </div>
                </div>

                <div class="user-card bg-white rounded-xl shadow-md p-5 flex items-center">
                    <div class="bg-nsklightblue p-4 rounded-full mr-4">
                        <i class="fas fa-user-shield text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-600">Staff</p>
                        <p class="text-2xl font-bold text-nsknavy"><?= $stats['total_users'] - $stats['teachers'] ?></p>
                        <p class="text-xs text-gray-600">Admin & Support</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                <form method="GET" action="">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-nsknavy">All Users (Non-Students)</h2>

                        <div class="flex flex-wrap gap-4">
                            <div class="relative">
                                <div class="flex items-center space-x-2 bg-nsklight rounded-full py-2 px-4">
                                    <i class="fas fa-search text-gray-500"></i>
                                    <input type="text" name="search" placeholder="Search users..."
                                        class="bg-transparent outline-none w-32 md:w-64"
                                        value="<?= htmlspecialchars($searchQuery) ?>">
                                </div>
                            </div>

                            <select name="role_filter"
                                class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                <option value="">All Roles</option>
                                <option value="admin" <?= $roleFilter == 'admin' ? 'selected' : '' ?>>Administrator
                                </option>
                                <option value="teacher" <?= $roleFilter == 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="staff" <?= $roleFilter == 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="principal" <?= $roleFilter == 'principal' ? 'selected' : '' ?>>Principal
                                </option>
                            </select>

                            <select name="status_filter"
                                class="px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                                <option value="">All Status</option>
                                <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                            </select>

                            <button type="submit"
                                class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>

                            <?php if (!empty($searchQuery) || !empty($roleFilter) || $statusFilter !== ''): ?>
                                <a href="user-management.php"
                                    class="bg-gray-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-600 transition flex items-center">
                                    <i class="fas fa-times mr-2"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="mt-4">
                    <form method="POST" action="" class="inline">
                        <button type="submit" name="show_add_form" value="true"
                            class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add User
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full user-table">
                        <thead>
                            <tr>
                                <th class="py-3 px-6 text-left text-nsknavy">User</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Role</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Status</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Last Login</th>
                                <th class="py-3 px-6 text-left text-nsknavy">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($usersData)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 px-6 text-center text-gray-500">
                                        <i class="fas fa-users-slash text-4xl mb-4"></i>
                                        <p>No users found matching your criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usersData as $user): ?>
                                    <tr>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-10 h-10 rounded-full bg-nskblue flex items-center justify-center text-white font-bold mr-3">
                                                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold">
                                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?= getRoleBadge($user['user_type']) ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?= getStatusBadge($user['is_active']) ?>
                                        </td>
                                        <td class="py-4 px-6">
                                            <p class="text-sm">
                                                <?= $user['last_login'] ? date('M j, Y, g:i A', strtotime($user['last_login'])) : 'Never' ?>
                                            </p>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex space-x-2">
                                                <button onclick="editUser(<?= $user['id'] ?>)"
                                                    class="text-nskblue hover:text-nsknavy p-2 rounded-full hover:bg-blue-50"
                                                    title="Edit User">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteUser(<?= $user['id'] ?>)"
                                                    class="text-nskred hover:text-red-700 p-2 rounded-full hover:bg-red-50"
                                                    title="Deactivate User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button onclick="resetPassword(<?= $user['id'] ?>)"
                                                    class="text-nskgreen hover:text-green-700 p-2 rounded-full hover:bg-green-50"
                                                    title="Reset Password">
                                                    <i class="fas fa-key"></i>
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
        </div>

        <?php if (isset($_POST['show_add_form'])): ?>
            <div id="addUserModal" class="modal active p-4">
                <div class="modal-content" style="max-width: 700px;">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-nsknavy">Add New User</h3>
                        <form method="POST" action="">
                            <button type="submit" name="hide_modal_form" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Step Indicator -->
                    <div class="flex justify-center mb-6">
                        <div class="flex items-center space-x-2">
                            <div class="step-indicator active" id="step1">1</div>
                            <div class="w-8 h-0.5 bg-gray-300"></div>
                            <div class="step-indicator" id="step2">2</div>
                            <div class="w-8 h-0.5 bg-gray-300"></div>
                            <div class="step-indicator" id="step3">3</div>
                        </div>
                    </div>

                    <form method="POST" action="" id="addUserForm" class="space-y-4">
                        <!-- Step 1: Role Selection -->
                        <div id="roleStep" class="step-content">
                            <h3 class="text-lg font-semibold text-nsknavy text-center mb-4">Select User Role</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                                <div class="role-card" data-role="teacher">
                                    <i class="fas fa-chalkboard-teacher text-2xl text-nskblue mb-2"></i>
                                    <p class="text-sm font-medium text-nsknavy">Teacher</p>
                                </div>
                                <div class="role-card" data-role="admin">
                                    <i class="fas fa-user-shield text-2xl text-nskblue mb-2"></i>
                                    <p class="text-sm font-medium text-nsknavy">Administrator</p>
                                </div>
                                <div class="role-card" data-role="staff">
                                    <i class="fas fa-user-tie text-2xl text-nskblue mb-2"></i>
                                    <p class="text-sm font-medium text-nsknavy">Staff</p>
                                </div>
                                <div class="role-card" data-role="principal">
                                    <i class="fas fa-user-graduate text-2xl text-nskblue mb-2"></i>
                                    <p class="text-sm font-medium text-nsknavy">Principal</p>
                                </div>
                            </div>
                            <input type="hidden" name="user_type" id="selectedRole" required>
                        </div>

                        <!-- Step 2: Basic Information -->
                        <div id="basicStep" class="step-content hidden">
                            <h3 class="text-lg font-semibold text-nsknavy text-center mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="input-field">
                                    <input type="text" name="first_name" id="firstName" placeholder=" " required>
                                    <label for="firstName">First Name *</label>
                                </div>
                                <div class="input-field">
                                    <input type="text" name="last_name" id="lastName" placeholder=" " required>
                                    <label for="lastName">Last Name *</label>
                                </div>
                                <div class="input-field">
                                    <input type="email" name="email" id="email" placeholder=" " required>
                                    <label for="email">Email Address *</label>
                                </div>
                                <div class="input-field">
                                    <input type="tel" name="phone" id="phone" placeholder=" ">
                                    <label for="phone">Phone Number</label>
                                </div>
                                <div class="input-field md:col-span-2">
                                    <select name="is_active"
                                        class="w-full px-4 py-3 border rounded-lg form-input focus:border-nskblue" required>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <label for="is_active"
                                        style="position: static; transform: none; background: none; padding: 0; margin-bottom: 0.5rem; display: block;">Status
                                        *</label>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Role-Specific Information -->
                        <div id="specificStep" class="step-content hidden">
                            <h3 class="text-lg font-semibold text-nsknavy text-center mb-4">Additional Information</h3>
                            <div id="roleSpecificFields" class="space-y-4">
                                <!-- Dynamic content will be inserted here -->
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between pt-4 mt-6">
                            <button type="button" id="prevBtn" class="btn btn-secondary hidden" onclick="previousStep()">
                                <i class="fas fa-arrow-left mr-2"></i>Previous
                            </button>
                            <button type="button" id="nextBtn" class="btn btn-primary ml-auto" onclick="nextStep()"
                                disabled>
                                Next<i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            <button type="submit" name="add_user" id="submitBtn" class="btn btn-success ml-auto hidden">
                                <i class="fas fa-user-plus mr-2"></i>Create User
                            </button>
                        </div>
                    </form>

                    <p class="text-sm text-gray-500 mt-4 text-center">
                        Default password will be 'password123'. User can change it after first login.
                    </p>
                </div>
            </div>

            <script>
                let currentStep = 1;
                let selectedRole = '';

                // Role-specific field configurations (excluding student)
                const roleFields = {
                    teacher: [
                        // These fields go to the 'users' table (like in File 1)
                        {
                            type: 'select',
                            id: 'gender',
                            label: 'Gender',
                            options: ['Male', 'Female', 'Other'],
                            required: true
                        },
                        {
                            type: 'date',
                            id: 'date_of_birth',
                            label: 'Date of Birth',
                            required: false
                        },

                        // These fields go to the 'teachers' & 'teacher_profiles' tables
                        {
                            type: 'text',
                            id: 'qualification',
                            label: 'Highest Qualification',
                            required: false
                        },
                        {
                            type: 'select',
                            id: 'department',
                            label: 'Department',
                            options: ['Science', 'Arts', 'Commercial', 'Technical', 'Others'],
                            required: false
                        },
                        {
                            type: 'text',
                            id: 'subjects',
                            label: 'Subject Specialization (comma separated)',
                            required: false
                        },
                        {
                            type: 'select',
                            id: 'employment_type',
                            label: 'Employment Type',
                            options: ['Full-time', 'Part-time', 'Contract'],
                            required: true
                        },
                        {
                            type: 'date',
                            id: 'employment_date',
                            label: 'Employment Date',
                            required: false
                        }
                    ],
                    admin: [{
                        type: 'select',
                        id: 'admin_level',
                        label: 'Admin Level',
                        options: ['Super Admin', 'Admin', 'Sub-Admin'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'department_access',
                        label: 'Department Access',
                        required: true
                    },
                    {
                        type: 'textarea',
                        id: 'special_permissions',
                        label: 'Special Permissions',
                        required: false
                    }
                    ],
                    staff: [{
                        type: 'select',
                        id: 'department',
                        label: 'Department',
                        options: ['Administration', 'Maintenance', 'Security', 'Kitchen', 'Library'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'position',
                        label: 'Job Title/Position',
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'employment_type',
                        label: 'Employment Type',
                        options: ['Full-time', 'Part-time', 'Contract'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'supervisor',
                        label: 'Supervisor Name',
                        required: false
                    }
                    ],
                    principal: [{
                        type: 'text',
                        id: 'qualification',
                        label: 'Highest Qualification',
                        required: true
                    },
                    // We removed 'experience' and added 'employment_date' for consistency
                    {
                        type: 'date',
                        id: 'employment_date',
                        label: 'Employment Date',
                        required: false
                    },
                    {
                        type: 'textarea',
                        id: 'vision_statement',
                        label: 'Vision Statement',
                        required: false
                    }
                    ]
                };

                // Initialize role selection
                document.addEventListener('DOMContentLoaded', function () {
                    const roleCards = document.querySelectorAll('.role-card');
                    roleCards.forEach(card => {
                        card.addEventListener('click', () => {
                            // Remove previous selection
                            roleCards.forEach(c => c.classList.remove('selected'));
                            // Add selection to clicked card
                            card.classList.add('selected');
                            selectedRole = card.dataset.role;
                            document.getElementById('selectedRole').value = selectedRole;

                            // Enable next button
                            document.getElementById('nextBtn').disabled = false;
                        });
                    });

                    // Setup floating labels
                    setupFloatingLabels();

                    // Add form submission handler
                    document.getElementById('addUserForm').addEventListener('submit', function (e) {
                        if (currentStep !== 3) {
                            e.preventDefault();
                            alert('Please complete all steps before submitting');
                            return;
                        }

                        if (!validateBasicInfo() || !validateRoleSpecificInfo()) {
                            e.preventDefault();
                            alert('Please fill in all required fields correctly');
                            return;
                        }
                    });
                });

                function setupFloatingLabels() {
                    document.querySelectorAll('.input-field input, .input-field textarea').forEach(field => {
                        if (field.value) {
                            field.nextElementSibling.classList.add('active');
                        }

                        field.addEventListener('input', () => {
                            if (field.value) {
                                field.nextElementSibling.classList.add('active');
                            } else {
                                field.nextElementSibling.classList.remove('active');
                            }
                        });
                    });
                }

                function nextStep() {
                    if (currentStep === 1 && !selectedRole) {
                        alert('Please select a role');
                        return;
                    }

                    if (currentStep === 2 && !validateBasicInfo()) {
                        return;
                    }

                    if (currentStep === 3 && !validateRoleSpecificInfo()) {
                        return;
                    }

                    if (currentStep < 3) {
                        currentStep++;
                        updateStepDisplay();

                        if (currentStep === 3) {
                            generateRoleSpecificFields();
                        }
                    }
                }

                function previousStep() {
                    if (currentStep > 1) {
                        currentStep--;
                        updateStepDisplay();
                    }
                }

                function updateStepDisplay() {
                    // Hide all steps
                    document.querySelectorAll('.step-content').forEach(step => {
                        step.classList.add('hidden');
                    });

                    // Show current step
                    const steps = ['roleStep', 'basicStep', 'specificStep'];
                    document.getElementById(steps[currentStep - 1]).classList.remove('hidden');

                    // Update step indicators
                    for (let i = 1; i <= 3; i++) {
                        const indicator = document.getElementById(`step${i}`);
                        indicator.classList.remove('active', 'completed');

                        if (i < currentStep) {
                            indicator.classList.add('completed');
                        } else if (i === currentStep) {
                            indicator.classList.add('active');
                        }
                    }

                    // Update navigation buttons
                    const prevBtn = document.getElementById('prevBtn');
                    const nextBtn = document.getElementById('nextBtn');
                    const submitBtn = document.getElementById('submitBtn');

                    prevBtn.classList.toggle('hidden', currentStep === 1);
                    nextBtn.classList.toggle('hidden', currentStep === 3);
                    submitBtn.classList.toggle('hidden', currentStep !== 3);
                }

                function generateRoleSpecificFields() {
                    const container = document.getElementById('roleSpecificFields');
                    container.innerHTML = '';

                    if (!selectedRole || !roleFields[selectedRole]) return;

                    const fields = roleFields[selectedRole];
                    const gridClass = fields.length > 2 ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-4';
                    container.className = gridClass;

                    fields.forEach(field => {
                        const fieldHtml = createFieldHtml(field);
                        container.insertAdjacentHTML('beforeend', fieldHtml);
                    });

                    // Re-setup floating labels for new fields
                    setTimeout(() => setupFloatingLabels(), 100);
                }

                function createFieldHtml(field) {
                    const {
                        type,
                        id,
                        label,
                        options,
                        required
                    } = field;

                    if (type === 'select') {
                        return `
                <div class="input-field">
                    <select name="${id}" id="${id}" ${required ? 'required' : ''}>
                        <option value="" selected disabled>Select ${label}</option>
                        ${options.map(option => `<option value="${option}">${option}</option>`).join('')}
                    </select>
                    <label for="${id}">${label}</label>
                </div>
            `;
                    } else if (type === 'textarea') {
                        return `
                <div class="input-field">
                    <textarea name="${id}" id="${id}" rows="3" placeholder=" " ${required ? 'required' : ''}></textarea>
                    <label for="${id}">${label}</label>
                </div>
            `;
                    } else {
                        return `
                <div class="input-field">
                    <input type="${type}" name="${id}" id="${id}" placeholder=" " ${required ? 'required' : ''}>
                    <label for="${id}">${label}</label>
                </div>
            `;
                    }
                }

                function validateBasicInfo() {
                    const requiredFields = ['firstName', 'lastName', 'email'];
                    let isValid = true;

                    requiredFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (!field.value.trim()) {
                            field.style.borderColor = '#ef4444';
                            isValid = false;
                        } else {
                            field.style.borderColor = '';
                        }
                    });

                    // Email validation
                    const email = document.getElementById('email').value;
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (email && !emailRegex.test(email)) {
                        document.getElementById('email').style.borderColor = '#ef4444';
                        alert('Please enter a valid email address');
                        isValid = false;
                    }

                    if (!isValid) {
                        alert('Please fill in all required fields correctly');
                    }

                    return isValid;
                }

                function validateRoleSpecificInfo() {
                    if (!selectedRole || !roleFields[selectedRole]) return true;

                    let isValid = true;
                    const fields = roleFields[selectedRole];

                    fields.forEach(field => {
                        if (field.required) {
                            const element = document.getElementById(field.id);
                            if (element && !element.value.trim()) {
                                element.style.borderColor = '#ef4444';
                                isValid = false;
                            } else if (element) {
                                element.style.borderColor = '';
                            }
                        }
                    });

                    if (!isValid) {
                        alert('Please fill in all required role-specific fields');
                    }

                    return isValid;
                }
            </script>
        <?php endif; ?>

        <?php if ($editUserData): ?>
            <div id="editUserModal" class="modal active p-4">
                <div class="modal-content">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-nsknavy">Edit User</h3>
                        <a href="user-management.php" class="text-gray-500 hover:text-gray-700"><i
                                class="fas fa-times"></i></a>
                    </div>

                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="user_id" value="<?= $editUserData['id'] ?>">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="first_name">First Name *</label>
                                <input type="text" name="first_name"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                                    value="<?= htmlspecialchars($editUserData['first_name']) ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="last_name">Last Name *</label>
                                <input type="text" name="last_name"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                                    value="<?= htmlspecialchars($editUserData['last_name']) ?>" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="email">Email Address *</label>
                            <input type="email" name="email"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                                value="<?= htmlspecialchars($editUserData['email']) ?>" required>
                        </div>

                        <div>
                            <label class="block text-gray-700 mb-2" for="phone">Phone</label>
                            <input type="tel" name="phone"
                                class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                                value="<?= htmlspecialchars($editUserData['phone']) ?>">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2" for="user_type">User Role *</label>
                                <select name="user_type"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                    <option value="">Select Role</option>
                                    <option value="admin" <?= $editUserData['user_type'] == 'admin' ? 'selected' : '' ?>>
                                        Administrator</option>
                                    <option value="teacher" <?= $editUserData['user_type'] == 'teacher' ? 'selected' : '' ?>>
                                        Teacher</option>
                                    <option value="staff" <?= $editUserData['user_type'] == 'staff' ? 'selected' : '' ?>>Staff
                                    </option>
                                    <option value="principal" <?= $editUserData['user_type'] == 'principal' ? 'selected' : '' ?>>Principal</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-gray-700 mb-2" for="is_active">Status *</label>
                                <select name="is_active"
                                    class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                                    <option value="1" <?= $editUserData['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= $editUserData['is_active'] == 0 ? 'selected' : '' ?>>Inactive
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <a href="user-management.php"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                            <button type="submit" name="update_user"
                                class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                                Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <script src="footer.js"></script>
    </main>

    <script>
        // Sidebar toggle functionality
        document.getElementById('mobileMenuToggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('mobile-show');
        });

        // Modal functionality (Show/Hide is now controlled by PHP)
        function closeAllModals() {
            // This will redirect and close any open modals
            window.location.href = 'user-management.php';
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeAllModals();
                }
            });
        });

        // --- Action Functions ---

        function editUser(userId) {
            window.location.href = 'user-management.php?edit=' + userId;
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to deactivate this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'user-management.php';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_user';
                deleteInput.value = '1';

                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function resetPassword(userId) {
            if (confirm("Are you sure you want to reset this user's password to 'password123'?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'user-management.php';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;

                const resetInput = document.createElement('input');
                resetInput.type = 'hidden';
                resetInput.name = 'reset_password';
                resetInput.value = '1';

                form.appendChild(idInput);
                form.appendChild(resetInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>