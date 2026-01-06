<?php
/**
 * Admin: Edit Student Results Page
 * Allows admins to edit CA and Exam scores for a specific student
 */

require_once 'auth-check.php';
checkAuth('admin'); // Ensure user is admin

require_once '../config/database.php';

// Get parameters
$student_id = $_GET['student_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;
$session_id = $_GET['session_id'] ?? null;
$term_id = $_GET['term_id'] ?? null;

if (!$student_id || !$class_id || !$session_id || !$term_id) {
    die("Missing required parameters.");
}

$database = new Database();
$db = $database->getConnection();

// Fetch student info
$stmt = $db->prepare("
    SELECT s.student_id AS admission_number, u.first_name, u.last_name 
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) die("Student not found.");

// Fetch class and term info
$stmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt->execute([$class_id]);
$class_name = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT session_name FROM academic_sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session_name = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT term_name FROM terms WHERE id = ?");
$stmt->execute([$term_id]);
$term_name = $stmt->fetchColumn();

// Fetch subjects for this class
$stmt = $db->prepare("
    SELECT s.id, s.subject_name 
    FROM subjects s
    JOIN class_subjects cs ON s.id = cs.subject_id
    WHERE cs.class_id = ?
    ORDER BY s.subject_name ASC
");
$stmt->execute([$class_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing results
$stmt = $db->prepare("
    SELECT subject_id, ca_score, exam_score, total_score, grade, remark
    FROM student_results
    WHERE student_id = ? AND class_id = ? AND session_id = ? AND term_id = ?
");
$stmt->execute([$student_id, $class_id, $session_id, $term_id]);
$results = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[$row['subject_id']] = $row;
}

// Calculate grade function
function calculateGrade($total) {
    if ($total >= 75) return 'A';
    if ($total >= 70) return 'B';
    if ($total >= 60) return 'C';
    if ($total >= 50) return 'D';
    if ($total >= 40) return 'E';
    return 'F';
}

function getRemark($grade) {
    $remarks = [
        'A' => 'Excellent',
        'B' => 'Very Good',
        'C' => 'Good',
        'D' => 'Fair',
        'E' => 'Pass',
        'F' => 'Fail'
    ];
    return $remarks[$grade] ?? 'N/A';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    try {
        $db->beginTransaction();
        
        foreach ($subjects as $subject) {
            $subject_id = $subject['id'];
            $ca_score = floatval($_POST['ca'][$subject_id] ?? 0);
            $exam_score = floatval($_POST['exam'][$subject_id] ?? 0);
            
            // Validate scores
            if ($ca_score < 0 || $ca_score > 30) continue;
            if ($exam_score < 0 || $exam_score > 70) continue;
            
            // total_score is auto-calculated by database (usually triggers) or we set it?
            // PHP logic sets it here:
            $total = $ca_score + $exam_score;
            $grade = calculateGrade($total);
            $remark = getRemark($grade);
            
            // Check if result exists
            $stmt = $db->prepare("
                SELECT id FROM student_results 
                WHERE student_id = ? AND class_id = ? AND subject_id = ? AND session_id = ? AND term_id = ?
            ");
            $stmt->execute([$student_id, $class_id, $subject_id, $session_id, $term_id]);
            $existing_id = $stmt->fetchColumn();
            
            if ($existing_id) {
                // Update
                $stmt = $db->prepare("
                    UPDATE student_results 
                    SET ca_score = ?, exam_score = ?, grade = ?, remark = ?
                    WHERE id = ?
                ");
                $stmt->execute([$ca_score, $exam_score, $grade, $remark, $existing_id]);
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO student_results 
                    (student_id, class_id, subject_id, session_id, term_id, ca_score, exam_score, grade, remark)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$student_id, $class_id, $subject_id, $session_id, $term_id, $ca_score, $exam_score, $grade, $remark]);
            }
        }
        
        $db->commit();
        $success_message = "Results updated successfully!";
        
        // Reload results
        $stmt = $db->prepare("
            SELECT subject_id, ca_score, exam_score, total_score, grade, remark
            FROM student_results
            WHERE student_id = ? AND class_id = ? AND session_id = ? AND term_id = ?
        ");
        $stmt->execute([$student_id, $class_id, $session_id, $term_id]);
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[$row['subject_id']] = $row;
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        $error_message = "Error updating results: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Results - <?= htmlspecialchars($student['last_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Styles Reuse */
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body { font-family: 'Montserrat', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex">
    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <main class="main-content flex-1 min-w-0 overflow-auto p-8">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Edit Student Results</h1>
                <p class="text-gray-600 mt-1">
                    <?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?> 
                    (<?= htmlspecialchars($student['admission_number']) ?>)
                </p>
                <p class="text-sm text-gray-500">
                    <?= htmlspecialchars($class_name) ?> • <?= htmlspecialchars($session_name) ?> • <?= htmlspecialchars($term_name) ?>
                </p>
            </div>
            <a href="results-management.php?class_id=<?= $class_id ?>&session_id=<?= $session_id ?>&term_id=<?= $term_id ?>" 
               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition font-medium">
                <i class="fas fa-arrow-left mr-2"></i> Back to Results
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Results Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="POST" id="editForm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="p-4 text-left font-semibold text-gray-700">S/N</th>
                                <th class="p-4 text-left font-semibold text-gray-700">Subject</th>
                                <th class="p-4 text-center font-semibold text-gray-700">CA (0-30)</th>
                                <th class="p-4 text-center font-semibold text-gray-700">Exam (0-70)</th>
                                <th class="p-4 text-center font-semibold text-gray-700">Total</th>
                                <th class="p-4 text-center font-semibold text-gray-700">Grade</th>
                                <th class="p-4 text-left font-semibold text-gray-700">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $index => $subject): 
                                $subject_id = $subject['id'];
                                $ca = $results[$subject_id]['ca_score'] ?? 0;
                                $exam = $results[$subject_id]['exam_score'] ?? 0;
                                $total = $ca + $exam;
                                $grade = calculateGrade($total);
                                $remark = getRemark($grade);
                            ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50" data-subject="<?= $subject_id ?>">
                                <td class="p-4 text-gray-600"><?= $index + 1 ?></td>
                                <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($subject['subject_name']) ?></td>
                                <td class="p-4 text-center">
                                    <input type="number" name="ca[<?= $subject_id ?>]" value="<?= $ca ?>" 
                                           min="0" max="30" step="0.01" 
                                           class="ca-input w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                           data-subject="<?= $subject_id ?>">
                                </td>
                                <td class="p-4 text-center">
                                    <input type="number" name="exam[<?= $subject_id ?>]" value="<?= $exam ?>" 
                                           min="0" max="70" step="0.01" 
                                           class="exam-input w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow"
                                           data-subject="<?= $subject_id ?>">
                                </td>
                                <td class="p-4 text-center">
                                    <span class="total-display font-bold text-blue-600" data-subject="<?= $subject_id ?>"><?= number_format($total, 1) ?></span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="grade-display px-3 py-1 rounded text-xs font-bold <?= $grade === 'F' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' ?>" 
                                          data-subject="<?= $subject_id ?>"><?= $grade ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="remark-display text-gray-600" data-subject="<?= $subject_id ?>"><?= $remark ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 flex justify-end gap-4">
                    <a href="results-management.php?class_id=<?= $class_id ?>&session_id=<?= $session_id ?>&term_id=<?= $term_id ?>" 
                       class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold transition">
                        Cancel
                    </a>
                    <button type="submit" name="save_results" 
                            class="px-6 py-3 bg-nskblue hover:bg-blue-800 text-white rounded-xl font-bold transition shadow-lg shadow-blue-200">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    
        <?php require_once 'footer.php'; ?>
    </main>

    <script>
    // Auto-calculate total, grade, and remark on input change
    document.querySelectorAll('.ca-input, .exam-input').forEach(input => {
        input.addEventListener('input', function() {
            // Strict Constraint: Auto-correct invalid values
            if (this.classList.contains('ca-input') && parseFloat(this.value) > 30) this.value = 30;
            if (this.classList.contains('exam-input') && parseFloat(this.value) > 70) this.value = 70;
            if (parseFloat(this.value) < 0) this.value = 0;

            const subjectId = this.dataset.subject;
            const row = document.querySelector(`tr[data-subject="${subjectId}"]`);
            
            const ca = parseFloat(row.querySelector('.ca-input').value) || 0;
            const exam = parseFloat(row.querySelector('.exam-input').value) || 0;
            
            // Validation check (Should pass now due to auto-correct)
            if (ca < 0 || ca > 30 || exam < 0 || exam > 70) {
                return;
            }
            
            const total = ca + exam;
            
            // Calculate grade
            let grade = 'F';
            if (total >= 75) grade = 'A';
            else if (total >= 70) grade = 'B';
            else if (total >= 60) grade = 'C';
            else if (total >= 50) grade = 'D';
            else if (total >= 40) grade = 'E';
            
            // Determine remark
            const remarks = {
                'A': 'Excellent',
                'B': 'Very Good',
                'C': 'Good',
                'D': 'Fair',
                'E': 'Pass',
                'F': 'Fail'
            };
            const remark = remarks[grade];
            
            // Update display
            row.querySelector('.total-display').textContent = total.toFixed(1);
            
            const gradeSpan = row.querySelector('.grade-display');
            gradeSpan.textContent = grade;
            gradeSpan.className = 'grade-display px-3 py-1 rounded text-xs font-bold ' + 
                                 (grade === 'F' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600');
            
            row.querySelector('.remark-display').textContent = remark;
        });
    });

    // Form validation
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const caInputs = document.querySelectorAll('.ca-input');
        const examInputs = document.querySelectorAll('.exam-input');
        
        let isValid = true;
        
        caInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (value < 0 || value > 30) {
                isValid = false;
                input.classList.add('border-red-500');
            } else {
                input.classList.remove('border-red-500');
            }
        });
        
        examInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (value < 0 || value > 70) {
                isValid = false;
                input.classList.add('border-red-500');
            } else {
                input.classList.remove('border-red-500');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Scores',
                text: 'Please check the scores. CA must be 0-30 and Exam must be 0-70.'
            });
        }
    });
    </script>

</body>
</html>
