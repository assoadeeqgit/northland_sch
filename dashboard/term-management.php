<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth-check.php';
checkAuth('admin');

require_once '../config/logger.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Nigerian Academic Calendar for 2024-2025 and 2025-2026
$nigerianTerms = [
    [
        'term_name' => 'First Term',
        'start_date' => '2024-09-09',
        'end_date' => '2024-12-13',
    ],
    [
        'term_name' => 'Second Term',
        'start_date' => '2025-01-06',
        'end_date' => '2025-04-11',
    ],
    [
        'term_name' => 'Third Term',
        'start_date' => '2025-04-28',
        'end_date' => '2025-07-25',
    ]
];

// Handle term activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'set_active') {
        $term_id = intval($_POST['term_id']);

        try {
            // Deactivate all terms
            $db->exec("UPDATE terms SET is_current = 0");

            // Activate the selected term
            $stmt = $db->prepare("UPDATE terms SET is_current = 1 WHERE id = ?");
            $stmt->execute([$term_id]);

            $message = "Term activated successfully!";
            logActivity($db, $_SESSION['user_name'] ?? 'Admin', 'Term Changed', "Term ID: $term_id activated", 'fas fa-calendar-check', 'bg-nsklightblue');
        } catch (Exception $e) {
            $error = "Failed to activate term: " . $e->getMessage();
        }
    }

    if ($action === 'update_term_dates') {
        $term_id = intval($_POST['term_id']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        try {
            $stmt = $db->prepare("UPDATE terms SET start_date = ?, end_date = ? WHERE id = ?");
            $stmt->execute([$start_date, $end_date, $term_id]);

            $message = "Term dates updated successfully!";
            logActivity($db, $_SESSION['user_name'] ?? 'Admin', 'Term Dates Updated', "Term ID: $term_id dates updated", 'fas fa-calendar-alt', 'bg-nskgold');
        } catch (Exception $e) {
            $error = "Failed to update term dates: " . $e->getMessage();
        }
    }

    if ($action === 'sync_nigerian_calendar') {
        try {
            // Get the current academic session
            $sessionStmt = $db->query("SELECT id FROM academic_sessions WHERE is_current = 1 ORDER BY id DESC LIMIT 1");
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);
            $academic_session_id = $session['id'] ?? 1;

            // Update or insert Nigerian terms
            foreach ($nigerianTerms as $index => $term) {
                $stmt = $db->prepare("UPDATE terms SET start_date = ?, end_date = ? 
                                     WHERE academic_session_id = ? AND term_name = ?");
                $stmt->execute([$term['start_date'], $term['end_date'], $academic_session_id, $term['term_name']]);
            }

            $message = "Calendar synced with Nigerian academic calendar successfully!";
            logActivity($db, $_SESSION['user_name'] ?? 'Admin', 'Calendar Synced', "Synced with Nigerian academic calendar", 'fas fa-sync', 'bg-nskgreen');
        } catch (Exception $e) {
            $error = "Failed to sync calendar: " . $e->getMessage();
        }
    }

    if ($action === 'promote_students') {
        // Promotion logic applies to global student list

        try {
            // Define class progression order (matching exact database class names)
            $classProgression = [
                'Garden (Age 2-3)' => 'Pre-Nursery (Age 3-4)',
                'Pre-Nursery (Age 3-4)' => 'Nursery 1 (Age 4-5)',
                'Nursery 1 (Age 4-5)' => 'Nursery 2 (Age 5-6)',
                'Nursery 2 (Age 5-6)' => 'Primary 1',
                'Primary 1' => 'Primary 2',
                'Primary 2' => 'Primary 3',
                'Primary 3' => 'Primary 4',
                'Primary 4' => 'Primary 5',
                'Primary 5' => 'JSS 1',
                'JSS 1' => 'JSS 2',
                'JSS 2' => 'JSS 3',
                'JSS 3' => 'SS 1',
                'SS 1' => 'SS 2',
                'SS 2' => 'SS 3'
            ];

            // Get all active students
            $stmt = $db->prepare("SELECT s.id, s.class_id, c.class_name 
                                  FROM students s 
                                  JOIN classes c ON s.class_id = c.id
                                  WHERE s.class_id IS NOT NULL AND s.status = 'active'");
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $promoted_count = 0;
            $graduated_count = 0;

            foreach ($students as $student) {
                $currentClassName = $student['class_name'];
                
                // Check if student is in SS 3 (final class) - mark as graduated
                if ($currentClassName === 'SS 3') {
                    // Mark student as graduated
                    $graduateStmt = $db->prepare("UPDATE students 
                                                  SET status = 'graduated', 
                                                      graduation_date = CURDATE() 
                                                  WHERE id = ?");
                    $graduateStmt->execute([$student['id']]);
                    $graduated_count++;
                }
                // Check if there's a next class for this student
                elseif (isset($classProgression[$currentClassName])) {
                    $nextClassName = $classProgression[$currentClassName];
                    
                    // Get the next class ID
                    $nextClassStmt = $db->prepare("SELECT id FROM classes WHERE class_name = ? LIMIT 1");
                    $nextClassStmt->execute([$nextClassName]);
                    $nextClass = $nextClassStmt->fetch(PDO::FETCH_ASSOC);

                    if ($nextClass) {
                        // Update student's class
                        $updateStmt = $db->prepare("UPDATE students SET class_id = ? WHERE id = ?");
                        $updateStmt->execute([$nextClass['id'], $student['id']]);
                        $promoted_count++;
                    }
                }
            }

            $message = "$promoted_count students have been promoted to the next class!";
            if ($graduated_count > 0) {
                $message .= " $graduated_count students have graduated!";
            }
            
            logActivity($db, $_SESSION['user_name'] ?? 'Admin', 'Student Promotion', 
                       "Promoted $promoted_count students, Graduated $graduated_count students", 
                       'fas fa-graduation-cap', 'bg-nskgreen');
        } catch (Exception $e) {
            $error = "Failed to promote students: " . $e->getMessage();
        }
    }
}

// Get selected academic year filter (default to current session)
$selectedSessionId = $_GET['session_filter'] ?? null;

// If no filter selected, get the current academic session
if (!$selectedSessionId) {
    $currentSessionStmt = $db->query("SELECT id FROM academic_sessions WHERE is_current = 1 ORDER BY id DESC LIMIT 1");
    $currentSession = $currentSessionStmt->fetch(PDO::FETCH_ASSOC);
    $selectedSessionId = $currentSession['id'] ?? null;
}

// Fetch terms based on filter
if ($selectedSessionId && $selectedSessionId !== 'all') {
    $termsStmt = $db->prepare("SELECT t.*, a.session_name as academic_year_name FROM terms t 
                               LEFT JOIN academic_sessions a ON t.academic_session_id = a.id
                               WHERE t.academic_session_id = ?
                               ORDER BY t.id ASC");
    $termsStmt->execute([$selectedSessionId]);
} else {
    // Show all terms if "All Years" is selected
    $termsStmt = $db->query("SELECT t.*, a.session_name as academic_year_name FROM terms t 
                             LEFT JOIN academic_sessions a ON t.academic_session_id = a.id
                             ORDER BY a.id DESC, t.id DESC");
}
$terms = $termsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch academic sessions for filters and promotion dropdown
$sessionsStmt = $db->query("SELECT id, session_name as name, is_current FROM academic_sessions ORDER BY id DESC");
$academicYears = $sessionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Term Management - Northland Schools Kano</title>
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
    <link rel="stylesheet" href="sidebar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
    </style>
</head>

<body>
    <div class="flex">
        <?php include 'sidebar.php'; ?>

        <div class="main-content w-full">
            <div class="container mx-auto p-6">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-nskblue">Term Management</h1>
                    <p class="text-gray-600">Manage academic terms and handle class promotions</p>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-check-circle"></i> <?= $message ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- Sync Nigerian Calendar Button -->
                <div class="bg-blue-50 border border-nsklightblue rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-nskblue mb-3">Nigerian Academic Calendar Sync</h3>
                    <p class="text-gray-600 mb-4">Sync your school's calendar with the standard Nigerian academic term dates:</p>
                    <ul class="text-sm text-gray-600 mb-4 list-disc list-inside">
                        <li><strong>First Term:</strong> September 9 - December 13 (96 days)</li>
                        <li><strong>Second Term:</strong> January 6 - April 11 (95 days)</li>
                        <li><strong>Third Term:</strong> April 28 - July 25 (88 days)</li>
                    </ul>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="sync_nigerian_calendar">
                        <button type="submit" class="bg-nsklightblue text-white px-6 py-2 rounded hover:bg-nskblue font-semibold">
                            <i class="fas fa-sync"></i> Sync with Nigerian Calendar
                        </button>
                    </form>
                </div>

                <!-- Terms Table -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold text-nskblue">Academic Terms</h2>
                        
                        <!-- Academic Year Filter -->
                        <div class="flex items-center gap-3">
                            <label class="text-gray-700 font-semibold">Filter by Year:</label>
                            <select id="sessionFilter" class="px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-nskblue" onchange="filterBySession()">
                                <option value="all" <?= ($selectedSessionId === 'all') ? 'selected' : '' ?>>All Years</option>
                                <?php foreach ($academicYears as $year): ?>
                                    <option value="<?= $year['id'] ?>" <?= ($selectedSessionId == $year['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($year['name']) ?>
                                        <?= $year['is_current'] ? ' (Current)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-nsklight border-b-2 border-nskblue">
                                    <th class="px-4 py-3 text-left">Term</th>
                                    <th class="px-4 py-3 text-left">Academic Year</th>
                                    <th class="px-4 py-3 text-left">Start Date</th>
                                    <th class="px-4 py-3 text-left">End Date</th>
                                    <th class="px-4 py-3 text-center">Duration</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($terms as $term):
                                    $duration = strtotime($term['end_date']) - strtotime($term['start_date']);
                                    $days = intval($duration / (60 * 60 * 24));
                                    $isActive = $term['is_current'] == 1;
                                ?>
                                    <tr class="border-b hover:bg-nsklight">
                                        <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($term['term_name']) ?></td>
                                        <td class="px-4 py-3"><?= htmlspecialchars($term['academic_year_name'] ?? 'N/A') ?></td>
                                        <td class="px-4 py-3"><?= date('M d, Y', strtotime($term['start_date'])) ?></td>
                                        <td class="px-4 py-3"><?= date('M d, Y', strtotime($term['end_date'])) ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="bg-nskgold text-white px-3 py-1 rounded"><?= $days ?> days</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php if ($isActive): ?>
                                                <span class="bg-nskgreen text-white px-3 py-1 rounded">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-gray-400 text-white px-3 py-1 rounded">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex gap-2 justify-center">
                                                <!-- Edit Button -->
                                                <button onclick="openEditModal(<?= $term['id'] ?>, '<?= htmlspecialchars($term['term_name']) ?>', '<?= $term['start_date'] ?>', '<?= $term['end_date'] ?>')"
                                                    class="bg-nskgold text-white px-3 py-2 rounded hover:bg-yellow-600">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <!-- Activate Button -->
                                                <?php if (!$isActive): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="set_active">
                                                        <input type="hidden" name="term_id" value="<?= $term['id'] ?>">
                                                        <button type="submit" class="bg-nsklightblue text-white px-3 py-2 rounded hover:bg-nskblue">
                                                            <i class="fas fa-check"></i> Activate
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Student Promotion Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-nskblue mb-4">Class Promotion</h2>
                    <p class="text-gray-600 mb-4">After the third term is completed, use this section to promote all students to the next class.</p>

                    <form method="POST" onsubmit="return confirm('Are you sure you want to promote all students? This action cannot be undone.');">
                        <input type="hidden" name="action" value="promote_students">

                        <!-- Session Selector Removed (Logic promotes all active students irrespective of session) -->
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        This action will promote <strong>ALL active students</strong> to their next assigned class sequence.<br>
                                        Graduating class (SS 3) will be marked as 'Graduated'.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="bg-nskred text-white px-6 py-3 rounded font-semibold hover:bg-red-700">
                            <i class="fas fa-arrow-up"></i> Promote Students to Next Class
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Term Modal -->
    <div id="editTermModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-96">
            <h2 class="text-2xl font-bold text-nskblue mb-4">Edit Term Dates</h2>

            <form id="editTermForm" method="POST">
                <input type="hidden" name="action" value="update_term_dates">
                <input type="hidden" name="term_id" id="editTermId">

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Term Name</label>
                    <input type="text" id="editTermName" class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-100" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Start Date</label>
                    <input type="date" name="start_date" id="editStartDate" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-nskblue" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">End Date</label>
                    <input type="date" name="end_date" id="editEndDate" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-nskblue" required>
                </div>

                <div class="mb-4 p-3 bg-blue-50 rounded">
                    <p class="text-sm text-gray-600"><strong>Duration:</strong> <span id="editDuration">0</span> days</p>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-nskblue text-white px-4 py-2 rounded hover:bg-nsknavy font-semibold">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 font-semibold">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filter terms by academic session
        function filterBySession() {
            const sessionId = document.getElementById('sessionFilter').value;
            window.location.href = '?session_filter=' + sessionId;
        }

        function openEditModal(termId, termName, startDate, endDate) {
            document.getElementById('editTermId').value = termId;
            document.getElementById('editTermName').value = termName;
            document.getElementById('editStartDate').value = startDate;
            document.getElementById('editEndDate').value = endDate;
            calculateDuration();
            document.getElementById('editTermModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editTermModal').classList.add('hidden');
        }

        function calculateDuration() {
            const startDate = new Date(document.getElementById('editStartDate').value);
            const endDate = new Date(document.getElementById('editEndDate').value);
            const timeDiff = endDate - startDate;
            const days = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
            document.getElementById('editDuration').textContent = days;
        }

        document.getElementById('editStartDate').addEventListener('change', calculateDuration);
        document.getElementById('editEndDate').addEventListener('change', calculateDuration);

        // Close modal when clicking outside
        document.getElementById('editTermModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>

</html>