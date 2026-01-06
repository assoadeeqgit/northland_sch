<?php
/**
 * Edit Student Results Page
 * Allows teachers to edit CA and Exam scores for a specific student
 */

// Prevent HTML caching but allow resource caching
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header("Location: ../login-form.php");
    exit;
}

require_once 'config/database.php';

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
            
            // total_score is auto-calculated by database
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
require_once 'ajax_check.php';
?>
<?php if (!$is_ajax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Results - <?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/tailwind.min.css?v=1.0.1">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900 min-h-screen">
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content flex-1 flex flex-col transition-all duration-300">
<?php endif; ?>
        <!-- Include Header -->
        <?php
        $pageTitle = 'Edit Results';
        $pageSubtitle = htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) . " (" . htmlspecialchars($class_name) . ")";
        include 'header.php';
        ?>
        <div id="page-content">
            <span id="page-meta-data" data-title="Edit Results" hidden></span>
            <link rel="stylesheet" href="css/edit_result.css">

                        <div class="p-6 transition-all duration-300">
                <!-- Back Navigation -->
                <div class="mb-6">
                    <a href="view_results.php?class_id=<?= $class_id ?>&session_id=<?= $session_id ?>&term_id=<?= $term_id ?>"
                        class="inline-flex items-center text-gray-600 hover:text-nskblue transition-colors group">
                        <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center mr-2 group-hover:border-nskblue group-hover:bg-blue-50 transition-all">
                            <i class="fas fa-arrow-left text-sm"></i>
                        </div>
                        <span class="font-medium">Back to Results Sheet</span>
                    </a>
                </div>

                <!-- Student info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 flex flex-col md:flex-row justify-between md:items-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?></h2>
                        <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600">
                            <span class="flex items-center"><i class="fas fa-id-card mr-2 text-nskblue"></i> <?= htmlspecialchars($student['admission_number']) ?></span>
                            <span class="flex items-center"><i class="fas fa-layer-group mr-2 text-nskgold"></i> <?= htmlspecialchars($class_name) ?></span>
                            <span class="flex items-center"><i class="fas fa-calendar mr-2 text-gray-400"></i> <?= htmlspecialchars($session_name) ?></span>
                        </div>
                    </div>

                    <div class="mt-4 md:mt-0 relative z-10 text-right">
                        <span class="inline-block px-4 py-1 rounded-full bg-blue-50 text-nskblue text-xs font-bold uppercase tracking-wider">
                            <?= htmlspecialchars($term_name) ?>
                        </span>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm mb-6 flex items-start">
                        <i class="fas fa-check-circle mt-1 mr-3 text-lg"></i>
                        <div>
                            <p class="font-bold">Success</p>
                            <p><?= $success_message ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm mb-6 flex items-start">
                        <i class="fas fa-exclamation-triangle mt-1 mr-3 text-lg"></i>
                        <div>
                            <p class="font-bold">Error</p>
                            <p><?= $error_message ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Edit Form -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700 text-lg">Subject Result Breakdown</h3>
                        <div class="text-xs text-gray-500 hidden sm:block">
                            <span class="mr-3"><span class="w-2 h-2 rounded-full bg-green-500 inline-block mr-1"></span>A (75-100)</span>
                            <span class="mr-3"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block mr-1"></span>B (70-74)</span>
                            <span class="mr-3"><span class="w-2 h-2 rounded-full bg-red-500 inline-block mr-1"></span>F (0-39)</span>
                        </div>
                    </div>
                    
                    <form method="POST" id="editForm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                        <th class="p-5 font-semibold border-b border-gray-100 w-16">#</th>
                                        <th class="p-5 font-semibold border-b border-gray-100">Subject</th>
                                        <th class="p-5 font-semibold border-b border-gray-100 text-center w-32">CA <span class="text-gray-400 text-[10px]">(30)</span></th>
                                        <th class="p-5 font-semibold border-b border-gray-100 text-center w-32">Exam <span class="text-gray-400 text-[10px]">(70)</span></th>
                                        <th class="p-5 font-semibold border-b border-gray-100 text-center w-24">Total</th>
                                        <th class="p-5 font-semibold border-b border-gray-100 text-center w-24">Grade</th>
                                        <th class="p-5 font-semibold border-b border-gray-100 text-right">Remark</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($subjects as $index => $subject):
                                        $subject_id = $subject['id'];
                                        $ca = $results[$subject_id]['ca_score'] ?? 0;
                                        $exam = $results[$subject_id]['exam_score'] ?? 0;
                                        $total = $ca + $exam;
                                        $grade = calculateGrade($total);
                                        $remark = getRemark($grade);
                                        
                                        // Badge Colors
                                        $badgeClass = 'bg-gray-100 text-gray-600';
                                        if ($grade === 'A') $badgeClass = 'bg-green-100 text-green-700 ring-1 ring-green-200';
                                        elseif ($grade === 'B') $badgeClass = 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
                                        elseif ($grade === 'C') $badgeClass = 'bg-sky-100 text-sky-700 ring-1 ring-sky-200';
                                        elseif ($grade === 'D') $badgeClass = 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200';
                                        elseif ($grade === 'E') $badgeClass = 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';
                                        elseif ($grade === 'F') $badgeClass = 'bg-red-100 text-red-700 ring-1 ring-red-200';
                                    ?>
                                        <tr class="hover:bg-blue-50/30 transition-colors group" data-subject="<?= $subject_id ?>">
                                            <td class="p-5 text-gray-400 font-medium"><?= $index + 1 ?></td>
                                            <td class="p-5 font-medium text-gray-800">
                                                <?= htmlspecialchars($subject['subject_name']) ?>
                                            </td>
                                            <td class="p-5 text-center">
                                                <input type="number" name="ca[<?= $subject_id ?>]" value="<?= $ca ?>"
                                                    min="0" max="30" step="0.01"
                                                    class="ca-input input-field w-20 text-center p-2 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-nskblue focus:ring-2 focus:ring-blue-100 outline-none font-bold text-gray-700"
                                                    data-subject="<?= $subject_id ?>">
                                            </td>
                                            <td class="p-5 text-center">
                                                <input type="number" name="exam[<?= $subject_id ?>]" value="<?= $exam ?>"
                                                    min="0" max="70" step="0.01"
                                                    class="exam-input input-field w-20 text-center p-2 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-nskblue focus:ring-2 focus:ring-blue-100 outline-none font-bold text-gray-700"
                                                    data-subject="<?= $subject_id ?>">
                                            </td>
                                            <td class="p-5 text-center">
                                                <span class="total-display text-lg font-bold text-gray-800" data-subject="<?= $subject_id ?>"><?= number_format($total, 1) ?></span>
                                            </td>
                                            <td class="p-5 text-center">
                                                <span class="grade-display inline-block w-8 h-8 leading-8 text-center rounded-full text-sm font-bold shadow-sm <?= $badgeClass ?>"
                                                    data-subject="<?= $subject_id ?>"><?= $grade ?></span>
                                            </td>
                                            <td class="p-5 text-right">
                                                <span class="remark-display text-sm font-medium text-gray-500" data-subject="<?= $subject_id ?>"><?= $remark ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Actions -->
                        <div class="px-6 py-6 bg-gray-50 border-t border-gray-100 flex justify-between items-center flex-wrap gap-4">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i> Changes are calculated instantly but saved only on click.
                            </div>
                            <div class="flex gap-3">
                                <a href="view_results.php?class_id=<?= $class_id ?>&session_id=<?= $session_id ?>&term_id=<?= $term_id ?>"
                                    class="px-6 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg font-medium transition shadow-sm">
                                    Cancel
                                </a>
                                <button type="submit" name="save_results"
                                    class="px-8 py-2.5 bg-gradient-to-r from-nskblue to-nsknavy hover:from-blue-700 hover:to-blue-900 text-white rounded-lg font-medium transition shadow-md shadow-blue-200 flex items-center transform hover:-translate-y-0.5">
                                    <i class="fas fa-save mr-2"></i> Save Results
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                {
                    // Real-time calculation
                    // Re-attached using delegation for SPA safety, though these are static inputs
                    document.querySelectorAll('.ca-input, .exam-input').forEach(input => {
                        input.addEventListener('input', function() {
                            // Strict Input Constraint
                            if (this.classList.contains('ca-input') && parseFloat(this.value) > 30) {
                                this.value = 30;
                            }
                            if (this.classList.contains('exam-input') && parseFloat(this.value) > 70) {
                                this.value = 70;
                            }
                            // Prevent negative
                            if (parseFloat(this.value) < 0) this.value = 0;

                            const subjectId = this.dataset.subject;
                            const row = document.querySelector(`tr[data-subject="${subjectId}"]`);

                            const ca = parseFloat(row.querySelector('.ca-input').value) || 0;
                            const exam = parseFloat(row.querySelector('.exam-input').value) || 0;

                            // Visual Feedback (Redundancy check removed as we auto-fix)
                            if (ca < 0 || ca > 30) row.querySelector('.ca-input').classList.add('border-red-500', 'bg-red-50');
                            else row.querySelector('.ca-input').classList.remove('border-red-500', 'bg-red-50');

                            if (exam < 0 || exam > 70) row.querySelector('.exam-input').classList.add('border-red-500', 'bg-red-50');
                            else row.querySelector('.exam-input').classList.remove('border-red-500', 'bg-red-50');

                            if (ca < 0 || ca > 30 || exam < 0 || exam > 70) return;

                            const total = ca + exam;

                            // Grade Logic
                            let grade = 'F';
                            if (total >= 75) grade = 'A';
                            else if (total >= 70) grade = 'B';
                            else if (total >= 60) grade = 'C';
                            else if (total >= 50) grade = 'D';
                            else if (total >= 40) grade = 'E';

                            // Remarks
                            const remarks = {
                                'A': 'Excellent',
                                'B': 'Very Good',
                                'C': 'Good',
                                'D': 'Fair',
                                'E': 'Pass',
                                'F': 'Fail'
                            };
                            const remark = remarks[grade];

                            // Update UI
                            row.querySelector('.total-display').textContent = total.toFixed(1);

                            const gradeSpan = row.querySelector('.grade-display');
                            gradeSpan.textContent = grade;

                            // Remove existing color classes
                            gradeSpan.className = 'grade-display inline-block w-8 h-8 leading-8 text-center rounded-full text-sm font-bold shadow-sm transition-all duration-300 transform scale-110';

                            // Add new colors
                            if (grade === 'A') gradeSpan.classList.add('bg-green-100', 'text-green-700', 'ring-1', 'ring-green-200');
                            else if (grade === 'B') gradeSpan.classList.add('bg-blue-100', 'text-blue-700', 'ring-1', 'ring-blue-200');
                            else if (grade === 'C') gradeSpan.classList.add('bg-sky-100', 'text-sky-700', 'ring-1', 'ring-sky-200');
                            else if (grade === 'D') gradeSpan.classList.add('bg-yellow-100', 'text-yellow-700', 'ring-1', 'ring-yellow-200');
                            else if (grade === 'E') gradeSpan.classList.add('bg-orange-100', 'text-orange-700', 'ring-1', 'ring-orange-200');
                            else gradeSpan.classList.add('bg-red-100', 'text-red-700', 'ring-1', 'ring-red-200');

                            // Reset scale animation after short delay
                            setTimeout(() => gradeSpan.classList.remove('scale-110'), 200);

                            row.querySelector('.remark-display').textContent = remark;
                        });
                    });

                    // Submit Validation
                    const form = document.getElementById('editForm');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            let isValid = true;
                            document.querySelectorAll('.ca-input').forEach(i => {
                                if (parseFloat(i.value) < 0 || parseFloat(i.value) > 30) isValid = false;
                            });
                            document.querySelectorAll('.exam-input').forEach(i => {
                                if (parseFloat(i.value) < 0 || parseFloat(i.value) > 70) isValid = false;
                            });

                            if (!isValid) {
                                e.preventDefault();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Invalid Scores',
                                    text: 'Please ensure CA is 0-30 and Exam is 0-70.',
                                    confirmButtonColor: '#1e40af'
                                });
                            }
                        });
                    }
                }
                
                // Note: Sidebar logic is handled by spa-navigation.js and persistent layout
            </script>

        </div>
<?php if (!$is_ajax): ?>
    </main>
</body>
</html>
<?php endif; ?>
