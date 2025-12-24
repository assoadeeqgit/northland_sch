<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// session_start();

require_once '../config/logger.php';

require_once 'auth-check.php';

// For admin dashboard:
checkAuth('admin');

// Initialize variables
$totalSubjects = 0;
$assignmentsData = [];
$all_classes = [];
$all_subjects_list = [];
$all_teachers = [];
$department_categories = [];
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));

// Database connection
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database connection failed: ". $e->getMessage());
}

// === HELPER FUNCTIONS ===

// Function to add a new subject assignment
// Function to add a new subject assignment
function assignSubjectToClass($db) {
    try {
        if (empty($_POST['subject_id']) || empty($_POST['class_id']) || empty($_POST['teacher_id'])) {
            throw new Exception("Subject, Class, and Teacher are all required.");
        }

        // Get subject, class, and teacher names for logging
        $subjectStmt = $db->prepare("SELECT subject_name FROM subjects WHERE id = ?");
        $subjectStmt->execute([$_POST['subject_id']]);
        $subjectName = $subjectStmt->fetchColumn();

        $classStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
        $classStmt->execute([$_POST['class_id']]);
        $className = $classStmt->fetchColumn();

        $teacherStmt = $db->prepare("SELECT u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $teacherStmt->execute([$_POST['teacher_id']]);
        $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
        $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];

        // Check for duplicates
        $checkSql = "SELECT COUNT(*) FROM class_subjects WHERE subject_id = ? AND class_id = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$_POST['subject_id'], $_POST['class_id']]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("This subject is already assigned to this class.");
        }

        $sql = "INSERT INTO class_subjects (subject_id, class_id, teacher_id, is_compulsory) 
                VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['subject_id'],
            $_POST['class_id'],
            $_POST['teacher_id'],
            $_POST['is_compulsory'] ?? 1
        ]);

        $_SESSION['success'] = "Subject assigned to class successfully!";
        
        // --- LOG ACTIVITY FOR SUBJECT ASSIGNMENT ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $subjectType = ($_POST['is_compulsory'] ?? 1) ? 'Compulsory' : 'Elective';
        
        logActivity(
            $db,
            $admin_name,
            "Assign Subject",
            "Assigned {$subjectName} to {$className} (Teacher: {$teacherName}, Type: {$subjectType})",
            "fas fa-book",
            "bg-nskgreen"
        );
        // --- END LOG ---
        
        header("Location: academics-management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error assigning subject: ". $e->getMessage();
    }
}

// Function to get data for a single assignment
function getClassSubjectData($db, $assignment_id) {
    try {
        $sql = "SELECT * FROM class_subjects WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$assignment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// Function to update a subject assignment
function updateClassSubjectAssignment($db) {
    try {
        if (empty($_POST['assignment_id']) || empty($_POST['subject_id']) || empty($_POST['class_id']) || empty($_POST['teacher_id'])) {
            throw new Exception("Subject, Class, and Teacher are all required.");
        }

        // Get old assignment data for logging
        $oldAssignmentStmt = $db->prepare("
            SELECT s.subject_name, c.class_name, u.first_name, u.last_name, cs.is_compulsory 
            FROM class_subjects cs
            JOIN subjects s ON cs.subject_id = s.id
            JOIN classes c ON cs.class_id = c.id
            JOIN teachers t ON cs.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE cs.id = ?
        ");
        $oldAssignmentStmt->execute([$_POST['assignment_id']]);
        $oldAssignment = $oldAssignmentStmt->fetch(PDO::FETCH_ASSOC);

        // Get new assignment data for logging
        $newSubjectStmt = $db->prepare("SELECT subject_name FROM subjects WHERE id = ?");
        $newSubjectStmt->execute([$_POST['subject_id']]);
        $newSubjectName = $newSubjectStmt->fetchColumn();

        $newClassStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
        $newClassStmt->execute([$_POST['class_id']]);
        $newClassName = $newClassStmt->fetchColumn();

        $newTeacherStmt = $db->prepare("SELECT u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $newTeacherStmt->execute([$_POST['teacher_id']]);
        $newTeacher = $newTeacherStmt->fetch(PDO::FETCH_ASSOC);
        $newTeacherName = $newTeacher['first_name'] . ' ' . $newTeacher['last_name'];

        $sql = "UPDATE class_subjects SET 
                    subject_id = ?, 
                    class_id = ?, 
                    teacher_id = ?, 
                    is_compulsory = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['subject_id'],
            $_POST['class_id'],
            $_POST['teacher_id'],
            $_POST['is_compulsory'] ?? 1,
            $_POST['assignment_id']
        ]);

        $_SESSION['success'] = "Subject assignment updated successfully!";
        
        // --- LOG ACTIVITY FOR UPDATE ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $newSubjectType = ($_POST['is_compulsory'] ?? 1) ? 'Compulsory' : 'Elective';
        
        // Build change description
        $changes = [];
        if ($oldAssignment['subject_name'] != $newSubjectName) {
            $changes[] = "subject from {$oldAssignment['subject_name']} to {$newSubjectName}";
        }
        if ($oldAssignment['class_name'] != $newClassName) {
            $changes[] = "class from {$oldAssignment['class_name']} to {$newClassName}";
        }
        if ($oldAssignment['first_name'] . ' ' . $oldAssignment['last_name'] != $newTeacherName) {
            $changes[] = "teacher from {$oldAssignment['first_name']} {$oldAssignment['last_name']} to {$newTeacherName}";
        }
        if ($oldAssignment['is_compulsory'] != ($_POST['is_compulsory'] ?? 1)) {
            $oldType = $oldAssignment['is_compulsory'] ? 'Compulsory' : 'Elective';
            $changes[] = "type from {$oldType} to {$newSubjectType}";
        }
        
        $changeDescription = !empty($changes) ? " (Changes: " . implode(", ", $changes) . ")" : " (No significant changes)";
        
        logActivity(
            $db,
            $admin_name,
            "Update Assignment",
            "Updated subject assignment: {$newSubjectName} to {$newClassName}{$changeDescription}",
            "fas fa-edit",
            "bg-nskgold"
        );
        // --- END LOG ---
        
        header("Location: academics-management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating assignment: ". $e->getMessage();
        header("Location: academics-management.php?edit=" . $_POST['assignment_id']); // Stay on edit page on error
        exit();
    }
}

// Function to delete a subject assignment (Hard Delete)
function deleteClassSubjectAssignment($db, $assignment_id) {
    try {
        // Get assignment data for logging before deletion
        $assignmentStmt = $db->prepare("
            SELECT s.subject_name, c.class_name, u.first_name, u.last_name, cs.is_compulsory 
            FROM class_subjects cs
            JOIN subjects s ON cs.subject_id = s.id
            JOIN classes c ON cs.class_id = c.id
            JOIN teachers t ON cs.teacher_id = t.id
            JOIN users u ON t.user_id = u.id
            WHERE cs.id = ?
        ");
        $assignmentStmt->execute([$assignment_id]);
        $assignment = $assignmentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$assignment) {
            throw new Exception("Assignment not found.");
        }

        $sql = "DELETE FROM class_subjects WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$assignment_id]);

        $_SESSION['success'] = "Subject assignment deleted successfully!";
        
        // --- LOG ACTIVITY FOR DELETE ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $subjectType = $assignment['is_compulsory'] ? 'Compulsory' : 'Elective';
        
        logActivity(
            $db,
            $admin_name,
            "Delete Assignment",
            "Deleted subject assignment: {$assignment['subject_name']} from {$assignment['class_name']} (Teacher: {$assignment['first_name']} {$assignment['last_name']}, Type: {$subjectType})",
            "fas fa-trash",
            "bg-nskred"
        );
        // --- END LOG ---
        
        header("Location: academics-management.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting assignment: ". $e->getMessage();
        header("Location: academics-management.php");
        exit();
    }
}

// Function to create a new subject
function createSubject($db) {
    try {
        if (empty($_POST['subject_name'])) {
            throw new Exception("Subject name is required.");
        }

        // Check for duplicates
        $checkSql = "SELECT COUNT(*) FROM subjects WHERE subject_name = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$_POST['subject_name']]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("This subject already exists.");
        }

        // Generate unique subject code
        $subjectName = $_POST['subject_name'];
        $words = explode(' ', $subjectName);
        $code = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $code .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Add random number to ensure uniqueness
        $baseCode = $code;
        $counter = 1;
        while (true) {
            $checkCodeSql = "SELECT COUNT(*) FROM subjects WHERE subject_code = ?";
            $checkCodeStmt = $db->prepare($checkCodeSql);
            $checkCodeStmt->execute([$code]);
            if ($checkCodeStmt->fetchColumn() == 0) {
                break;
            }
            $code = $baseCode . $counter;
            $counter++;
        }

        $sql = "INSERT INTO subjects (subject_code, subject_name, category, description, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $code,
            $_POST['subject_name'],
            $_POST['category'] ?? null,
            $_POST['description'] ?? null
        ]);

        $_SESSION['success'] = "Subject created successfully!";
        
        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Create Subject",
            "Created new subject: {$_POST['subject_name']} (Code: {$code}, Category: " . ($_POST['category'] ?? 'N/A') . ")",
            "fas fa-plus-circle",
            "bg-nskgreen"
        );
        
        header("Location: academics-management.php?tab=subjects");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating subject: ". $e->getMessage();
    }
}

// Function to update a subject
function updateSubject($db) {
    try {
        if (empty($_POST['subject_id']) || empty($_POST['subject_name'])) {
            throw new Exception("Subject ID and name are required.");
        }

        // Get old subject data for logging
        $oldSubjectStmt = $db->prepare("SELECT subject_name, category FROM subjects WHERE id = ?");
        $oldSubjectStmt->execute([$_POST['subject_id']]);
        $oldSubject = $oldSubjectStmt->fetch(PDO::FETCH_ASSOC);

        $sql = "UPDATE subjects SET 
                    subject_name = ?, 
                    category = ?, 
                    description = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_POST['subject_name'],
            $_POST['category'] ?? null,
            $_POST['description'] ?? null,
            $_POST['subject_id']
        ]);

        $_SESSION['success'] = "Subject updated successfully!";
        
        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Update Subject",
            "Updated subject: {$_POST['subject_name']}",
            "fas fa-edit",
            "bg-nskgold"
        );
        
        header("Location: academics-management.php?tab=subjects");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating subject: ". $e->getMessage();
        header("Location: academics-management.php?tab=subjects&edit=" . $_POST['subject_id']);
        exit();
    }
}

// Function to delete a subject
function deleteSubject($db, $subject_id) {
    try {
        // Get subject data for logging
        $subjectStmt = $db->prepare("SELECT subject_name, category FROM subjects WHERE id = ?");
        $subjectStmt->execute([$subject_id]);
        $subject = $subjectStmt->fetch(PDO::FETCH_ASSOC);

        if (!$subject) {
            throw new Exception("Subject not found.");
        }

        // Soft delete - mark as inactive
        $sql = "UPDATE subjects SET is_active = 0 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$subject_id]);

        $_SESSION['success'] = "Subject deleted successfully!";
        
        // --- LOG ACTIVITY ---
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        logActivity(
            $db,
            $admin_name,
            "Delete Subject",
            "Deleted subject: {$subject['subject_name']} (Category: {$subject['category']})",
            "fas fa-trash",
            "bg-nskred"
        );
        
        header("Location: academics-management.php?tab=subjects");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting subject: ". $e->getMessage();
        header("Location: academics-management.php?tab=subjects");
        exit();
    }
}

// Function to get data for a single subject
function getSubjectData($db, $subject_id) {
    try {
        $sql = "SELECT * FROM subjects WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$subject_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// === POST/GET HANDLERS ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_subject'])) {
        assignSubjectToClass($db);
    }
    
    if (isset($_POST['update_assignment'])) {
        updateClassSubjectAssignment($db);
    }

    if (isset($_POST['delete_assignment']) && isset($_POST['assignment_id'])) {
        deleteClassSubjectAssignment($db, $_POST['assignment_id']);
    }

    if (isset($_POST['create_subject'])) {
        createSubject($db);
    }

    if (isset($_POST['update_subject'])) {
        updateSubject($db);
    }

    if (isset($_POST['delete_subject']) && isset($_POST['subject_id'])) {
        deleteSubject($db, $_POST['subject_id']);
    }

    if (isset($_POST['hide_modal_form'])) {
        header("Location: academics-management.php");
        exit();
    }
}

$editAssignmentData = null;
if (isset($_GET['edit']) && !empty($_GET['edit']) && (!isset($_GET['tab']) || $_GET['tab'] != 'subjects')) {
    $editAssignmentData = getClassSubjectData($db, $_GET['edit']);
}

$editSubjectData = null;
if (isset($_GET['edit']) && !empty($_GET['edit']) && isset($_GET['tab']) && $_GET['tab'] == 'subjects') {
    $editSubjectData = getSubjectData($db, $_GET['edit']);
}

// === DATA FETCHING FOR PAGE LOAD ===

// Get filter parameters
$classFilter = isset($_GET['class_filter']) ? intval($_GET['class_filter']) : '';
$departmentFilter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';

try {
    // Build the main query
    $queryParts = [
        "SELECT 
            cs.id as class_subject_id,
            s.subject_name, s.category,
            c.class_name,
            u.first_name, u.last_name,
            (SELECT COUNT(st.id) FROM students st WHERE st.class_id = c.id AND st.user_id IN (SELECT user_id FROM users WHERE is_active = 1)) as student_count
        FROM class_subjects cs",
        "LEFT JOIN subjects s ON cs.subject_id = s.id",
        "LEFT JOIN classes c ON cs.class_id = c.id",
        "LEFT JOIN teachers t ON cs.teacher_id = t.id",
        "LEFT JOIN users u ON t.user_id = u.id",
        "WHERE s.is_active = 1"
    ];
    $params = [];

    // Add class filter
    if (!empty($classFilter)) {
        $queryParts[] = "AND cs.class_id = ?";
        $params[] = $classFilter;
    }

    // Add department filter
    if (!empty($departmentFilter)) {
        $queryParts[] = "AND s.category = ?";
        $params[] = $departmentFilter;
    }

    $queryParts[] = "ORDER BY c.class_name, s.subject_name";
    $sql = implode(" ", $queryParts);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $assignmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats
    $totalSubjects = $db->query("SELECT COUNT(*) FROM subjects WHERE is_active = 1")->fetchColumn();
    // Other stats are static as per template
    
    // Get data for dropdowns
    $all_classes = $db->query("SELECT id, class_name FROM classes ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $all_subjects_list = $db->query("SELECT id, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);
    $all_teachers = $db->query("SELECT t.id, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.is_active = 1 ORDER BY u.first_name, u.last_name")->fetchAll(PDO::FETCH_ASSOC);
    $department_categories = $db->query("SELECT DISTINCT category FROM subjects WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);

    // Get all subjects for subjects tab
    $allSubjects = $db->query("SELECT id, subject_code, subject_name, category, description, created_at FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: ". $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academics Management - Northland Schools Kano</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              nskblue: "#1e40af",
              nsklightblue: "#3b82f6",
              nsknavy: "#1e3a8a",
              nskgold: "#f59e0b",
              nsklight: "#f0f9ff",
              nskgreen: "#10b981",
              nskred: "#ef4444",
            },
          },
        },
      };
    </script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap");

      body {
        font-family: "Montserrat", sans-serif;
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
          background-color: rgba(0,0,0,0.5); 
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
          animation: fadeIn 0.3s ease;
      }

      .logo-container {
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
      }

      .academics-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .academics-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      }

      .nav-item {
        position: relative;
      }

      .nav-item::after {
        content: "";
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
          z-index: 40;
        }

        .sidebar.mobile-show {
          margin-left: 0;
          box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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
        animation: pulse 2s infinite;
      }

      .academics-table {
        border-collapse: separate;
        border-spacing: 0;
      }

      .academics-table th {
        background-color: #f8fafc;
      }

      .academics-table tr:last-child td {
        border-bottom: 0;
      }

      .academics-table tbody tr {
        transition: all 0.3s ease;
      }

      .academics-table tbody tr:hover {
        background-color: #f0f9ff;
        transform: scale(1.01);
      }

      .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }

      .grade-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }

      .tab-button {
        transition: all 0.3s ease;
      }

      .tab-button.active {
        background-color: #1e40af;
        color: white;
        box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
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
          transform: translateY(10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes pulse {
        0%,
        100% {
          opacity: 1;
        }
        50% {
          opacity: 0.5;
        }
      }
    </style>
  </head>
  <body class="flex">
    <?php require_once 'sidebar.php'; ?>
    
    <main class="main-content">
      <?php 
      $pageTitle = 'Academic Management';
      $pageSubtitle = 'Manage subject assignments, and academic activities';
      require_once 'header.php'; 
      ?>

      <div class="p-4 md:p-6">
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8 stats-grid"
        >
          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
          >
            <div class="bg-nskgreen p-4 rounded-full mr-4">
              <i class="fas fa-book text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Total Subjects</p>
              <p class="text-2xl font-bold text-nsknavy" id="totalSubjects">
                <?= $totalSubjects ?>
              </p>
              <p class="text-xs text-nskgreen">Across all grades</p>
            </div>
          </div>

          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
            style="animation-delay: 0.1s"
          >
            <div class="bg-nskblue p-4 rounded-full mr-4">
              <i class="fas fa-tasks text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Active Assignments</p>
              <p class="text-2xl font-bold text-nsknavy" id="activeAssignments">
                156
              </p>
              <p class="text-xs text-nskblue">12 due this week</p>
            </div>
          </div>

          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
            style="animation-delay: 0.2s"
          >
            <div class="bg-nskgold p-4 rounded-full mr-4">
              <i class="fas fa-chart-line text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Average Performance</p>
              <p class="text-2xl font-bold text-nsknavy" id="avgPerformance">
                82%
              </p>
              <p class="text-xs text-nskgold">+3% from last term</p>
            </div>
          </div>

          <div
            class="academics-card bg-white rounded-xl shadow-md p-5 flex items-center animate-fadeIn"
            style="animation-delay: 0.3s"
          >
            <div class="bg-nskred p-4 rounded-full mr-4">
              <i class="fas fa-graduation-cap text-white text-xl"></i>
            </div>
            <div>
              <p class="text-gray-600">Online Classes</p>
              <p class="text-2xl font-bold text-nsknavy" id="onlineClasses">
                8
              </p>
              <p class="text-xs text-nskred">Live sessions today</p>
            </div>
          </div>
        </div>

        <div
          class="bg-white rounded-xl shadow-md p-4 md:p-6 mb-6 md:mb-8"
        >
          <form method="GET" action="" class="space-y-4">
            <div
              class="flex flex-col md:flex-row md:items-center justify-between gap-4"
            >
              <h2 class="text-xl font-bold text-nsknavy mb-4 md:mb-0">
                Academic Management Hub
              </h2>

              <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 mb-3 sm:mb-0">
                  <select
                    class="px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm"
                    name="class_filter"
                  >
                    <option value="">All Grades</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= $classFilter == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>

                  <select
                    class="px-3 py-2 border rounded-lg form-input focus:border-nskblue text-sm"
                    name="department_filter"
                  >
                    <option value="">All Departments</option>
                    <?php foreach ($department_categories as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['category']) ?>" <?= $departmentFilter == $dept['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['category']) ?>
                        </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="flex flex-wrap gap-2 action-buttons-mobile">
                  <button
                    type="submit"
                    class="flex-1 sm:flex-none bg-nskblue text-white px-3 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center justify-center text-sm"
                  >
                    <i class="fas fa-filter mr-2"></i> Filter
                  </button>
                  
                  <?php if (!empty($classFilter) || !empty($departmentFilter)): ?>
                      <a href="academics-management.php" class="flex-1 sm:flex-none bg-gray-500 text-white px-3 py-2 rounded-lg font-semibold hover:bg-gray-600 transition flex items-center justify-center text-sm">
                          <i class="fas fa-times mr-2"></i> Clear
                      </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 md:p-6 mb-6 md:mb-8">
          <div class="flex flex-wrap gap-2 mb-6 overflow-x-auto pb-2">
            <button
              class="tab-button px-3 py-2 rounded-lg border border-nskblue text-nskblue font-semibold active"
              data-tab="assignments"
            >
              <i class="fas fa-book mr-2"></i>Subject Assignments
            </button>
            <button
              class="tab-button px-3 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold"
              data-tab="subjects"
            >
              <i class="fas fa-book-open mr-2"></i>Subjects
            </button>
            <button
              class="tab-button px-3 py-2 rounded-lg border border-gray-300 text-gray-700 font-semibold"
              data-tab="exams"
            >
              <i class="fas fa-file-alt mr-2"></i>Exams
            </button>
          </div>

          <div id="assignmentsTab" class="tab-content active">
            <div
              class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6"
            >
              <h3 class="text-lg font-bold text-nsknavy">
                Subject Assignments Management
              </h3>
              <div class="flex gap-3">
                <form method="POST" action="">
                    <button
                        type="submit"
                        name="show_add_form"
                        value="true"
                        class="bg-nskblue text-white px-4 py-2 rounded-lg font-semibold hover:bg-nsknavy transition flex items-center text-sm"
                    >
                        <i class="fas fa-plus mr-2"></i> Assign Subject
                    </button>
                </form>
              </div>
            </div>

            <div class="grid grid-cols-1">
              <div class="lg:col-span-3">
                <div class="bg-white rounded-lg border overflow-hidden">
                  <div class="overflow-x-auto">
                    <table class="academics-table min-w-full">
                      <thead>
                        <tr class="bg-gray-50">
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Subject
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Grade / Class
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Teacher
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Students
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                          >
                            Status
                          </th>
                          <th
                            class="px-4 md:px-6 py-4 text-center text-nsknavy font-semibold"
                          >
                            Actions
                          </th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200">
                        <?php if (empty($assignmentsData)): ?>
                            <tr>
                                <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                    <i class="fas fa-book-open text-4xl mb-4"></i>
                                    <p>No subject assignments found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignmentsData as $assignment): ?>
                            <tr>
                              <td class="px-4 md:px-6 py-4">
                                <div class="flex items-center">
                                  <div>
                                    <p class="font-semibold text-nsknavy">
                                      <?= htmlspecialchars($assignment['subject_name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                      <?= htmlspecialchars($assignment['category']) ?>
                                    </p>
                                  </div>
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4 table-grade">
                                <div class="grade-badge-container">
                                  <span
                                    class="bg-nskblue text-white px-3 py-1 rounded-full text-sm font-semibold"
                                    ><?= htmlspecialchars($assignment['class_name']) ?></span
                                  >
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4">
                                <div>
                                  <p class="font-medium"><?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']) ?></p>
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4">
                                <div class="flex items-center">
                                  <span class="font-semibold text-nsknavy"><?= $assignment['student_count'] ?></span>
                                  <span class="text-sm text-gray-600 ml-1"
                                    >enrolled</span
                                  >
                                </div>
                              </td>
                              <td class="px-4 md:px-6 py-4">
                                <span
                                  class="status-badge bg-green-100 text-green-700"
                                  >Active</span
                                >
                              </td>
                              <td class="px-4 md:px-6 py-4 text-center">
                                <div class="flex justify-center space-x-2">
                                  <button
                                    class="text-nskgold hover:text-amber-600 p-2 rounded-full hover:bg-amber-50 transition"
                                    title="Edit Assignment"
                                    onclick="editAssignment(<?= $assignment['class_subject_id'] ?>)"
                                  >
                                    <i class="fas fa-edit"></i>
                                  </button>
                                  <button
                                    class="text-nskred hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition"
                                    title="Delete Assignment"
                                    onclick="deleteAssignment(<?= $assignment['class_subject_id'] ?>)"
                                  >
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
              </div>
            </div>
          </div>
          
          <div id="subjectsTab" class="tab-content hidden">
            <div
              class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6"
            >
              <h3 class="text-lg font-bold text-nsknavy">
                Subjects Management
              </h3>
              <div class="flex gap-3">
                <form method="POST" action="">
                    <button
                        type="submit"
                        name="show_create_subject_form"
                        value="true"
                        class="bg-nskgreen text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition flex items-center text-sm"
                    >
                        <i class="fas fa-plus mr-2"></i> Create Subject
                    </button>
                </form>
              </div>
            </div>

            <div class="grid grid-cols-1">
              <div class="bg-white rounded-lg border overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="academics-table min-w-full">
                    <thead>
                      <tr class="bg-gray-50">
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Subject Name
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Category
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Description
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-left text-nsknavy font-semibold"
                        >
                          Created At
                        </th>
                        <th
                          class="px-4 md:px-6 py-4 text-center text-nsknavy font-semibold"
                        >
                          Actions
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                      <?php if (empty($allSubjects)): ?>
                          <tr>
                              <td colspan="5" class="py-8 px-6 text-center text-gray-500">
                                  <i class="fas fa-book text-4xl mb-4"></i>
                                  <p>No subjects found. Create your first subject!</p>
                              </td>
                          </tr>
                      <?php else: ?>
                          <?php foreach ($allSubjects as $subject): ?>
                          <tr>
                            <td class="px-4 md:px-6 py-4">
                              <div class="flex items-center">
                                <div class="bg-nskblue p-2 rounded-lg mr-3">
                                  <i class="fas fa-book text-white"></i>
                                </div>
                                <div>
                                  <p class="font-semibold text-nsknavy">
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                  </p>
                                  <p class="text-xs text-gray-500">
                                    Code: <?= htmlspecialchars($subject['subject_code']) ?>
                                  </p>
                                </div>
                              </div>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                              <?php if (!empty($subject['category'])): ?>
                                <span class="bg-nskgold text-white px-3 py-1 rounded-full text-sm font-semibold">
                                  <?= htmlspecialchars($subject['category']) ?>
                                </span>
                              <?php else: ?>
                                <span class="text-gray-400 text-sm">No category</span>
                              <?php endif; ?>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                              <p class="text-gray-600 text-sm">
                                <?= !empty($subject['description']) ? htmlspecialchars(substr($subject['description'], 0, 50)) . (strlen($subject['description']) > 50 ? '...' : '') : 'No description' ?>
                              </p>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                              <p class="text-gray-600 text-sm">
                                <?= date('M d, Y', strtotime($subject['created_at'])) ?>
                              </p>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                              <div class="flex justify-center space-x-2">
                                <button
                                  class="text-nskgold hover:text-amber-600 p-2 rounded-full hover:bg-amber-50 transition"
                                  title="Edit Subject"
                                  onclick="editSubject(<?= $subject['id'] ?>)"
                                >
                                  <i class="fas fa-edit"></i>
                                </button>
                                <button
                                  class="text-nskred hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition"
                                  title="Delete Subject"
                                  onclick="deleteSubject(<?= $subject['id'] ?>)"
                                >
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
            </div>
          </div>

          <div id="examsTab" class="tab-content hidden">
            <h3 class="text-lg font-bold text-nsknavy">Examination Schedule</h3>
            <p class="text-gray-600">Exam scheduling feature coming soon...</p>
          </div>
          
        </div>
      </div>

      <?php if (isset($_POST['show_add_form'])): ?>
      <div id="assignSubjectModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Assign Subject to Class</h3>
            <a href="academics-management.php" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <div>
              <label class="block text-gray-700 mb-2">Subject</label>
              <select name="subject_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Subject</option>
                <?php foreach ($all_subjects_list as $subject): ?>
                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Class (Grade Level)</label>
              <select name="class_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Class</option>
                <?php foreach ($all_classes as $class): ?>
                    <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Teacher</label>
              <select name="teacher_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Teacher</option>
                <?php foreach ($all_teachers as $teacher): ?>
                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
                <label class="block text-gray-700 mb-2">Type</label>
                <select name="is_compulsory" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                    <option value="1">Compulsory</option>
                    <option value="0">Elective</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition inline-block">Cancel</a>
                <button type="submit" name="assign_subject" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                Assign Subject
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($editAssignmentData): ?>
      <div id="editAssignmentModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Edit Subject Assignment</h3>
            <a href="academics-management.php" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="assignment_id" value="<?= $editAssignmentData['id'] ?>">
            
            <div>
              <label class="block text-gray-700 mb-2">Subject</label>
              <select name="subject_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Subject</option>
                <?php foreach ($all_subjects_list as $subject): ?>
                    <option value="<?= $subject['id'] ?>" <?= $editAssignmentData['subject_id'] == $subject['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Class (Grade Level)</label>
              <select name="class_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Class</option>
                <?php foreach ($all_classes as $class): ?>
                    <option value="<?= $class['id'] ?>" <?= $editAssignmentData['class_id'] == $class['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Teacher</label>
              <select name="teacher_id" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                <option value="">Select Teacher</option>
                <?php foreach ($all_teachers as $teacher): ?>
                    <option value="<?= $teacher['id'] ?>" <?= $editAssignmentData['teacher_id'] == $teacher['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div>
                <label class="block text-gray-700 mb-2">Type</label>
                <select name="is_compulsory" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                    <option value="1" <?= $editAssignmentData['is_compulsory'] == 1 ? 'selected' : '' ?>>Compulsory</option>
                    <option value="0" <?= $editAssignmentData['is_compulsory'] == 0 ? 'selected' : '' ?>>Elective</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" name="update_assignment" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                Update Assignment
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if (isset($_POST['show_create_subject_form'])): ?>
      <div id="createSubjectModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Create New Subject</h3>
            <a href="academics-management.php" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <div>
              <label class="block text-gray-700 mb-2">Subject Name <span class="text-red-500">*</span></label>
              <input type="text" name="subject_name" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" placeholder="e.g., Mathematics" required>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Category (Subject Stream)</label>
              <select name="category" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                <option value="">Select Category (Optional)</option>
                <option value="Science">Science</option>
                <option value="Arts">Arts</option>
                <option value="Commerce">Commerce</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Science: Math, Biology, Chemistry, Physics | Arts: Languages, Social Sciences, Religious Studies | Commerce: Economics, Accounting, Business</p>
              <p class="text-xs text-gray-500">Note: Compulsory/Elective classification is set when assigning to classes</p>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Description</label>
              <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" placeholder="Brief description of the subject"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php?tab=subjects" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition inline-block">Cancel</a>
                <button type="submit" name="create_subject" class="px-4 py-2 bg-nskgreen text-white rounded-lg font-semibold hover:bg-green-600 transition">
                  <i class="fas fa-plus mr-2"></i>Create Subject
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($editSubjectData): ?>
      <div id="editSubjectModal" class="modal active p-4">
        <div class="modal-content" style="max-width: 500px;">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-nsknavy">Edit Subject</h3>
            <a href="academics-management.php?tab=subjects" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></a>
          </div>

          <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="subject_id" value="<?= $editSubjectData['id'] ?>">
            
            <div>
              <label class="block text-gray-700 mb-2">Subject Name <span class="text-red-500">*</span></label>
              <input type="text" name="subject_name" value="<?= htmlspecialchars($editSubjectData['subject_name']) ?>" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-2">Category (Subject Stream)</label>
              <select name="category" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                <option value="">Select Category (Optional)</option>
                <option value="Science" <?= ($editSubjectData['category'] ?? '') == 'Science' ? 'selected' : '' ?>>Science</option>
                <option value="Arts" <?= ($editSubjectData['category'] ?? '') == 'Arts' ? 'selected' : '' ?>>Arts</option>
                <option value="Commerce" <?= ($editSubjectData['category'] ?? '') == 'Commerce' ? 'selected' : '' ?>>Commerce</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">Science: Math, Biology, Chemistry, Physics | Arts: Languages, Social Sciences, Religious Studies | Commerce: Economics, Accounting, Business</p>
              <p class="text-xs text-gray-500">Note: Compulsory/Elective classification is set when assigning to classes</p>
            </div>

            <div>
              <label class="block text-gray-700 mb-2">Description</label>
              <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"><?= htmlspecialchars($editSubjectData['description'] ?? '') ?></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="academics-management.php?tab=subjects" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" name="update_subject" class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                  <i class="fas fa-save mr-2"></i>Update Subject
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

    </main>
    <script src="footer.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Tab switching functionality
      document.querySelectorAll(".tab-button").forEach((button) => {
        button.addEventListener("click", function () {
          const tabName = this.getAttribute("data-tab");

          // Update tab buttons
          document.querySelectorAll(".tab-button").forEach((btn) => {
            btn.classList.remove("active", "bg-nskblue", "text-white", "border-nskblue");
            btn.classList.add("border-gray-300", "text-gray-700");
          });

          this.classList.add("active", "bg-nskblue", "text-white", "border-nskblue");
          this.classList.remove("border-gray-300", "text-gray-700");

          // Update tab content
          document.querySelectorAll(".tab-content").forEach((content) => {
            content.classList.add("hidden");
            content.classList.remove("active");
          });

          const targetContent = document.getElementById(tabName + "Tab");
          if (targetContent) {
            targetContent.classList.remove("hidden");
            targetContent.classList.add("active");
          }
        });
      });
      
      // Check URL for tab parameter and activate the correct tab
      const urlParams = new URLSearchParams(window.location.search);
      const activeTabParam = urlParams.get('tab');
      
      if (activeTabParam) {
        // Find and click the tab button with matching data-tab attribute
        const tabButton = document.querySelector(`.tab-button[data-tab="${activeTabParam}"]`);
        if (tabButton) {
          tabButton.click();
        } else {
          // If tab not found, activate first tab
          const firstTab = document.querySelector(".tab-button");
          if (firstTab) firstTab.click();
        }
      } else {
        // Set the first tab as active by default
        const firstTab = document.querySelector(".tab-button");
        if (firstTab) {
          firstTab.click();
        }
      }

      // Close modals with Escape key
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
          closeAllModals();
        }
      });
      
      // Close modal on outside click
      document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("click", function (e) {
          if (e.target === this) {
            closeAllModals();
          }
        });
      });
      
      // Sidebar toggle
      document.querySelector(".sidebar-toggle")?.addEventListener("click", function () {
        document.querySelector(".sidebar").classList.toggle("mobile-show");
      });
    });

    function closeAllModals() {
        // This will redirect and close any open modals
        window.location.href = 'academics-management.php';
    }
    
    // === ACTION FUNCTIONS ===

    function editAssignment(assignmentId) {
        window.location.href = `academics-management.php?edit=${assignmentId}`;
    }

    function deleteAssignment(assignmentId) {
        if (confirm('Are you sure you want to delete this subject assignment?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'academics-management.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'assignment_id';
            idInput.value = assignmentId;
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_assignment';
            deleteInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function editSubject(subjectId) {
        window.location.href = `academics-management.php?tab=subjects&edit=${subjectId}`;
    }

    function deleteSubject(subjectId) {
        if (confirm('Are you sure you want to delete this subject? This will deactivate it and it won\'t be available for new assignments.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'academics-management.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'subject_id';
            idInput.value = subjectId;
            
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_subject';
            deleteInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(deleteInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
  </script>
  </body>
</html>