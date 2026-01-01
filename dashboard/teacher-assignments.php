<?php
/**
 * Teacher Assignment Management
 * Assign teachers to subjects and classes
 */

require_once 'auth-check.php';
checkAuth('admin');

require_once '../config/database.php';
require_once '../includes/teacher_assignment_helper.php';
require_once '../includes/subject_class_helper.php';

$database = new Database();
$pdo = $db = $database->getConnection();

$userName = $_SESSION['user_name'] ?? 'Admin User';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['assign_teacher'])) {
            $teacher_db_id = $_POST['teacher_id'];
            $subject_ids = $_POST['subjects'] ?? [];
            $class_ids = $_POST['classes'] ?? [];
            $class_teacher_ids = $_POST['class_teacher'] ?? [];  // Class teacher designations
            
            if (batchAssignTeacher($pdo, $teacher_db_id, $subject_ids, $class_ids)) {
                // Update class teacher designations
                foreach ($class_ids as $class_id) {
                    $is_class_teacher = in_array($class_id, $class_teacher_ids) ? 1 : 0;
                    $updateStmt = $pdo->prepare("
                        UPDATE teacher_class_assignments 
                        SET is_class_teacher = ? 
                        WHERE teacher_id = ? AND class_id = ?
                    ");
                    $updateStmt->execute([$is_class_teacher, $teacher_db_id, $class_id]);
                }
                
                // Update is_form_master flag in teachers table
                // A teacher is a form master if they are class teacher for at least one class
                $checkFormMasterStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM teacher_class_assignments 
                    WHERE teacher_id = ? AND is_class_teacher = 1
                ");
                $checkFormMasterStmt->execute([$teacher_db_id]);
                $isFormMaster = $checkFormMasterStmt->fetchColumn() > 0 ? 1 : 0;
                
                $updateFormMasterStmt = $pdo->prepare("
                    UPDATE teachers SET is_form_master = ? WHERE id = ?
                ");
                $updateFormMasterStmt->execute([$isFormMaster, $teacher_db_id]);
                
                $_SESSION['success'] = "Teacher assignments updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update teacher assignments.";
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?teacher_id=" . $teacher_db_id);
            exit();
        }
        
        if (isset($_POST['assign_subject_class'])) {
            $teacher_db_id = $_POST['teacher_id'];
            $subject_id = $_POST['subject_id'];
            $class_id = $_POST['class_id'];
            
            // Get current session and term
            $sessionStmt = $pdo->query("SELECT id FROM academic_sessions WHERE is_current = 1 LIMIT 1");
            $session_id = $sessionStmt->fetchColumn();
            
            $termStmt = $pdo->query("SELECT id FROM terms WHERE is_current = 1 LIMIT 1");
            $term_id = $termStmt->fetchColumn();
            
            if (assignTeacherToSubjectClass($pdo, $teacher_db_id, $subject_id, $class_id, $session_id, $term_id)) {
                $_SESSION['success'] = "Assignment created successfully!";
            } else {
                $_SESSION['error'] = "Failed to create assignment. Make sure the subject is assigned to the class.";
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        if (isset($_POST['remove_assignment'])) {
            $assignment_id = $_POST['assignment_id'];
            if (removeTeacherAssignment($pdo, $assignment_id)) {
                $_SESSION['success'] = "Assignment removed successfully!";
            } else {
                $_SESSION['error'] = "Failed to remove assignment.";
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get all teachers
$teachersStmt = $pdo->query("
    SELECT 
        t.id,
        t.teacher_id,
        u.first_name,
        u.last_name,
        u.email
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE u.user_type = 'teacher' AND u.is_active = 1
    ORDER BY u.first_name, u.last_name
");
$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects
$subjects = $pdo->query("SELECT id, subject_code, subject_name FROM subjects WHERE is_active = 1 ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);

// Get all classes
$classes = $pdo->query("SELECT id, class_name FROM classes ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get selected teacher details
$selected_teacher = null;
$teacher_subjects = [];
$teacher_classes = [];
$teacher_assignments = [];
$teacher_stats = [];

if (isset($_GET['teacher_id'])) {
    foreach ($teachers as $t) {
        if ($t['id'] == $_GET['teacher_id']) {
            $selected_teacher = $t;
            break;
        }
    }
    
    if ($selected_teacher) {
        $teacher_subjects = getTeacherSubjects($pdo, $selected_teacher['id']);
        $teacher_classes = getTeacherClasses($pdo, $selected_teacher['id']);
        $teacher_assignments = getTeacherAssignments($pdo, $selected_teacher['id']);
        $teacher_stats = getTeacherStats($pdo, $selected_teacher['id']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Assignments - Northland Schools Kano</title>
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
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .assignment-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .assignment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="flex">
    <?php require_once 'sidebar.php'; ?>
    
    <main class="main-content">
        <?php
        $pageTitle = 'Teacher Assignments';
        require_once 'header.php';
        ?>
        
        <div class="p-6">
            <!-- Success/Error Messages -->
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
            
            <!-- Teacher Selection -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-nsknavy mb-4">Select Teacher</h2>
                <form method="GET" action="" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[250px]">
                        <label class="block text-gray-700 mb-2">Teacher</label>
                        <select name="teacher_id" class="w-full px-4 py-2 border rounded-lg" onchange="this.form.submit()">
                            <option value="">-- Select a Teacher --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= (isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?> (<?= $teacher['teacher_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selected_teacher): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($selected_teacher): ?>
                <!-- Teacher Info Card -->
                <div class="bg-gradient-to-r from-nskblue to-nsklightblue rounded-xl shadow-md p-6 mb-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold">
                                <?= htmlspecialchars($selected_teacher['first_name'] . ' ' . $selected_teacher['last_name']) ?>
                            </h2>
                            <p class="text-blue-100">Teacher ID: <?= $selected_teacher['teacher_id'] ?></p>
                            <p class="text-blue-100"><?= $selected_teacher['email'] ?></p>
                        </div>
                        <div class="text-right">

                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-xl shadow-md p-5">
                        <div class="flex items-center">
                            <div class="bg-nskblue p-3 rounded-full mr-3">
                                <i class="fas fa-book text-white"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Subjects</p>
                                <p class="text-2xl font-bold text-nsknavy"><?= $teacher_stats['total_subjects'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-5">
                        <div class="flex items-center">
                            <div class="bg-nskgreen p-3 rounded-full mr-3">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Classes</p>
                                <p class="text-2xl font-bold text-nsknavy"><?= $teacher_stats['total_classes'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-5">
                        <div class="flex items-center">
                            <div class="bg-nskgold p-3 rounded-full mr-3">
                                <i class="fas fa-clipboard-list text-white"></i>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Total Assignments</p>
                                <p class="text-2xl font-bold text-nsknavy"><?= $teacher_stats['total_assignments'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-md p-5">
                        <div class="flex items-center">
                            <div class="bg-<?= $teacher_stats['is_class_teacher'] ? 'nskgreen' : 'gray-400' ?> p-3 rounded-full mr-3">
                                <i class="fas fa-chalkboard-teacher text-white"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-600 text-sm">Class Teacher</p>
                                <p class="text-sm font-bold text-nsknavy"><?= htmlspecialchars($teacher_stats['class_teacher_for']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Assignments Table -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-nsknavy">Subject-Class Assignments</h3>
                        <button onclick="document.getElementById('addAssignmentModal').classList.add('active')"
                                class="bg-nskgreen text-white px-4 py-2 rounded-lg hover:bg-green-600">
                            <i class="fas fa-plus mr-2"></i>New Assignment
                        </button>
                    </div>
                    
                    <?php if (empty($teacher_assignments)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-clipboard text-4xl mb-3"></i>
                            <p>No specific assignments yet. Click "New Assignment" to add one.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="py-3 px-4 text-left text-nsknavy">Subject</th>
                                        <th class="py-3 px-4 text-left text-nsknavy">Class</th>
                                        <th class="py-3 px-4 text-left text-nsknavy">Session/Term</th>
                                        <th class="py-3 px-4 text-center text-nsknavy">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($teacher_assignments as $assignment): ?>
                                        <tr>
                                            <td class="py-3 px-4">
                                                <span class="font-medium"><?= htmlspecialchars($assignment['subject_name']) ?></span>
                                                <br><span class="text-sm text-gray-500"><?= $assignment['subject_code'] ?></span>
                                            </td>
                                            <td class="py-3 px-4"><?= htmlspecialchars($assignment['class_name']) ?></td>
                                            <td class="py-3 px-4 text-sm">
                                                <?= $assignment['session_name'] ?? 'N/A' ?> / <?= $assignment['term_name'] ?? 'N/A' ?>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <form method="POST" action="" class="inline" onsubmit="return confirm('Remove this assignment?')">
                                                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                                    <button type="submit" name="remove_assignment" 
                                                            class="text-nskred hover:text-red-700 p-2">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Subjects and Classes Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Subjects -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-nsknavy mb-4">Assigned Subjects</h3>
                        <?php if (empty($teacher_subjects)): ?>
                            <p class="text-gray-500">No subjects assigned</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($teacher_subjects as $subject): ?>
                                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                        <div>
                                            <span class="font-medium text-nsknavy"><?= htmlspecialchars($subject['subject_name']) ?></span>
                                            <br><span class="text-xs text-gray-600"><?= $subject['subject_code'] ?></span>
                                        </div>
                                        <span class="text-xs bg-nskblue text-white px-2 py-1 rounded"><?= $subject['category'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Classes -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-bold text-nsknavy mb-4">Assigned Classes</h3>
                        <?php if (empty($teacher_classes)): ?>
                            <p class="text-gray-500">No classes assigned</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($teacher_classes as $class): ?>
                                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                        <span class="font-medium text-nsknavy"><?= htmlspecialchars($class['class_name']) ?></span>
                                        <?php if ($class['is_class_teacher']): ?>
                                            <span class="text-xs bg-nskgreen text-white px-2 py-1 rounded">
                                                <i class="fas fa-star mr-1"></i>Class Teacher
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                

                
                <!-- Add Specific Assignment Modal -->
                <div id="addAssignmentModal" class="modal">
                    <div class="modal-content">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-nsknavy">Add Subject-Class Assignment</h3>
                            <button onclick="document.getElementById('addAssignmentModal').classList.remove('active')" 
                                    class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <form method="POST" action="" id="assignmentForm">
                            <input type="hidden" name="teacher_id" value="<?= $selected_teacher['id'] ?>">
                            
                            <!-- Class Selection FIRST -->
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Class *</label>
                                <select name="class_id" id="classSelect" class="w-full px-4 py-2 border rounded-lg" required onchange="loadSubjectsForClass()">
                                    <option value="">-- Select Class First --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>">
                                            <?= htmlspecialchars($class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Subject Selection (loads based on class) -->
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Subject *</label>
                                <select name="subject_id" id="subjectSelect" class="w-full px-4 py-2 border rounded-lg" required disabled>
                                    <option value="">-- Select a class first --</option>
                                </select>
                                <div id="subjectLoader" class="hidden mt-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-spinner fa-spin mr-2"></i>
                                        Loading subjects...
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-500 mb-4">
                                <i class="fas fa-info-circle mr-1"></i>
                                Only subjects assigned to the selected class will be shown. This assignment will be for the current session and term.
                            </p>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="document.getElementById('addAssignmentModal').classList.remove('active'); resetAssignmentForm();"
                                        class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                                <button type="submit" name="assign_subject_class" id="submitAssignmentBtn" disabled
                                        class="px-4 py-2 bg-nskgreen text-white rounded-lg hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-plus mr-2"></i>Add Assignment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <script>
                // Function to load subjects for selected class
                function loadSubjectsForClass() {
                    const classId = document.getElementById('classSelect').value;
                    const subjectSelect = document.getElementById('subjectSelect');
                    const subjectLoader = document.getElementById('subjectLoader');
                    const submitBtn = document.getElementById('submitAssignmentBtn');
                    
                    // Reset subject dropdown
                    subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                    subjectSelect.disabled = true;
                    submitBtn.disabled = true;
                    
                    if (!classId) {
                        subjectSelect.innerHTML = '<option value="">-- Select a class first --</option>';
                        return;
                    }
                    
                    // Show loader
                    subjectLoader.classList.remove('hidden');
                    
                    // Fetch subjects for this class via AJAX
                    fetch('get_class_subjects.php?class_id=' + classId)
                        .then(response => response.json())
                        .then(data => {
                            subjectLoader.classList.add('hidden');
                            
                            if (data.success && data.subjects.length > 0) {
                                data.subjects.forEach(subject => {
                                    const option = document.createElement('option');
                                    option.value = subject.id;
                                    option.textContent = subject.subject_name;
                                    subjectSelect.appendChild(option);
                                });
                                subjectSelect.disabled = false;
                            } else {
                                subjectSelect.innerHTML = '<option value="">No subjects found for this class</option>';
                            }
                        })
                        .catch(error => {
                            subjectLoader.classList.add('hidden');
                            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                            console.error('Error:', error);
                        });
                }
                
                // Enable submit button when both fields are filled
                document.getElementById('subjectSelect').addEventListener('change', function() {
                    const submitBtn = document.getElementById('submitAssignmentBtn');
                    const classId = document.getElementById('classSelect').value;
                    const subjectId = this.value;
                    
                    submitBtn.disabled = !(classId && subjectId);
                });
                
                // Reset form when modal is closed
                function resetAssignmentForm() {
                    document.getElementById('assignmentForm').reset();
                    document.getElementById('subjectSelect').innerHTML = '<option value="">-- Select a class first --</option>';
                    document.getElementById('subjectSelect').disabled = true;
                    document.getElementById('submitAssignmentBtn').disabled = true;
                }
                </script>
                
                <script>
                // Toggle class teacher checkbox based on class selection
                function toggleClassTeacherOption(classId) {
                    const classCheckbox = document.getElementById('class_' + classId);
                    const ctCheckbox = document.getElementById('ct_' + classId);
                    
                    if (classCheckbox && ctCheckbox) {
                        if (classCheckbox.checked) {
                            ctCheckbox.disabled = false;
                        } else {
                            ctCheckbox.disabled = true;
                            ctCheckbox.checked = false;
                        }
                    }
                }
                </script>
                
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <i class="fas fa-chalkboard-teacher text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-600 mb-2">No Teacher Selected</h3>
                    <p class="text-gray-500">Please select a teacher from the dropdown above to manage their assignments.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
