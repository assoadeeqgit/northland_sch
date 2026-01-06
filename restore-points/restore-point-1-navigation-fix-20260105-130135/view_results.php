<?php
/**
 * View Results Page
 * Displays students for a selected class/session/term with their calculated results.
 * Allows downloading templates, uploading scores, and exporting broadsheets/PDFs.
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
require_once 'security.php';

// Initialize session security
SessionSecurity::init();

// Generate CSRF token
$csrf_token = CSRF::generateToken();

// Initialize Database
$database = new Database();
$db = $database->getConnection();

// --- Data Fetching Logic ---

// 1. Fetch Active Sessions
$sessions = [];
try {
    $stmt = $db->query("SELECT id, session_name FROM academic_sessions ORDER BY id DESC");
    $sessions = $stmt->fetchAll();
} catch (PDOException $e) { /* Ignore or Log */ }


// Defaults for session (need this first to filter terms)
$selected_session = $_GET['session_id'] ?? ($sessions[0]['id'] ?? 1);

// 2. Fetch Terms (filtered by selected session, with start_date for future check)
$terms = [];
$today = date('Y-m-d');
try {
    $stmt = $db->prepare("SELECT id, term_name, start_date FROM terms WHERE session_id = ? ORDER BY id ASC");
    $stmt->execute([$selected_session]);
    $terms = $stmt->fetchAll();
} catch (PDOException $e) { /* Ignore */ }

// 3. Fetch Classes assigned to Teacher
$classes = [];
try {
    $user_id = $_SESSION['user_id'];
    // Get teacher_id
    $stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $teacher_id = $stmt->fetchColumn();

    if ($teacher_id) {
        // Fetch classes where teacher is either Class Teacher or Subject Teacher
        $stmt = $db->prepare("
            SELECT DISTINCT c.id, c.class_name 
            FROM classes c
            LEFT JOIN class_subjects cs ON c.id = cs.class_id
            WHERE cs.teacher_id = ? OR c.class_teacher_id = ?
            ORDER BY c.class_name
        ");
        $stmt->execute([$teacher_id, $teacher_id]);
        $classes = $stmt->fetchAll();
    }
} catch (PDOException $e) { /* Ignore */ }

// Get current term ID for the selected session (where is_current = 1)
$current_term_id = null;
try {
    $stmt = $db->prepare("SELECT id FROM terms WHERE is_current = 1 AND session_id = ? LIMIT 1");
    $stmt->execute([$selected_session]);
    $current_term_id = $stmt->fetchColumn();
} catch (PDOException $e) { /* Fallback to first term */ }

// Defaults (session already set above)
$selected_term = isset($_GET['term_id']) ? filter_var($_GET['term_id'], FILTER_VALIDATE_INT) : ($current_term_id ?? ($terms[0]['id'] ?? 1));
$selected_class = isset($_GET['class_id']) ? filter_var($_GET['class_id'], FILTER_VALIDATE_INT) : ($classes[0]['id'] ?? null);

// Additional validation
if ($selected_term === false) $selected_term = $current_term_id ?? ($terms[0]['id'] ?? 1);
if ($selected_class === false) $selected_class = $classes[0]['id'] ?? null;

// 4. Fetch Results (if class selected)
$results = [];
if ($selected_class) {
    try {
        // Query to get ALL students in class, joining with their results for the specific term/session
        // Also need to support SUBJECT filtering? The user prompt implies "View Results" usually shows a Broadsheet (All subjects? or specific subject?)
        // The Prompt table structure shows "CA", "Exam", "Total" - implying a Single Subject view?
        // Wait, "Approved Excel Structure" has MULTIPLE subjects.
        // But the "View Results" page table shows: "CA, Exam, Total, Grade, Remark". This usually implies ONE subject or an Aggregate?
        // Let's re-read the prompt details for View Results.
        // "Table Columns: ... CA, Exam, Total, Grade, Remark, Position".
        // If it's a broadsheet view, it would have many subject columns.
        // Since it lists "CA, Exam...", it strongly implies a Per-Subject view OR the user wants an Aggregate view?
        // BUT "Filters: ... Class". It does NOT mention Subject filter in "Page: View Results" description explicitly, BUT in "Table Columns" it's singular CA/Exam.
        // HOWEVER, the Excel Template has MULTIPLE subjects.
        // CONTRADICTION CHECK: 
        // - Template: Broad sheet (All subjects).
        // - Validated Prompt "Table Columns": S/N, ID, Name, CA, Exam, Total, Grade. (Singular).
        // - Result Upload: "Upload CA & Exam scores".
        // - If the template has all subjects, how do we show ALL subjects in this simple table?
        // RESOLUTION: The "View Results" page likely needs a SUBJECT filter to view scores for a specific subject, OR it displays a summary. 
        // Given "Upload JSS" button uploads the Broadsheet (all subjects), likely the VIEW page *should* probably support subject filtering to be useful, OR the table structure in prompt is a simplification.
        // Let's add a Subject Filter to be safe, otherwise we can't show specific CA/Exam scores. 
        // If I strictly follow "Filters: Session, Term, Class", then how do I show "CA, Exam"? Maybe it lists multiple rows per student? No, "S/N" implies one row per student.
        // I will add a **Subject Filter** derived from the teacher's subjects.

        // Pagination and Search Setup
        $items_per_page = 15;
        $current_page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_VALIDATE_INT)) : 1;
        $search_query = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : '';
        $offset = ($current_page - 1) * $items_per_page;

        // Build WHERE conditions for search
        $where_conditions = ["s.class_id = :cid"];
        $search_param = null;
        
        if (!empty($search_query)) {
            $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_id LIKE :search OR s.admission_number LIKE :search)";
            $search_param = "%{$search_query}%";
        }
        
        $where_clause = implode(" AND ", $where_conditions);

        // Get total count for pagination
        $count_query = "
            SELECT COUNT(DISTINCT s.id) as total
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE {$where_clause}
        ";
        $stmt_count = $db->prepare($count_query);
        $stmt_count->bindParam(':cid', $selected_class);
        if ($search_param) {
            $stmt_count->bindParam(':search', $search_param);
        }
        $stmt_count->execute();
        $total_students = $stmt_count->fetchColumn();
        $total_pages = ceil($total_students / $items_per_page);

        // Fetch Students with Aggregate Results (paginated)
        $query = "
            SELECT 
                s.id AS internal_student_id,
                s.student_id AS admission_number,
                u.first_name, u.last_name,
                COALESCE(SUM(sr.total_score), 0) as total_obtained,
                COALESCE(COUNT(sr.id), 0) as subjects_taken,
                COALESCE(AVG(sr.total_score), 0) as average_score
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN student_results sr ON s.id = sr.student_id 
                AND sr.class_id = :cid 
                AND sr.session_id = :sid
                AND sr.term_id = :tid
            WHERE {$where_clause}
            GROUP BY s.id, s.student_id, u.first_name, u.last_name
            ORDER BY total_obtained DESC, u.last_name, u.first_name
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $selected_class, PDO::PARAM_INT);
        $stmt->bindParam(':sid', $selected_session, PDO::PARAM_INT);
        $stmt->bindParam(':tid', $selected_term, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        if ($search_param) {
            $stmt->bindParam(':search', $search_param);
        }
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Calculate positions (adjusted for pagination)
        $position = $offset + 1;
        foreach ($results as &$result) {
            $result['position'] = $position++;
        }

    } catch (PDOException $e) { /* Log */ }

    // --- AJAX Handler Block ---
    if (isset($_GET['ajax_search'])) {
        header('Content-Type: application/json');
        
        // Generate Rows HTML
        ob_start();
        if (empty($results)) {
            echo '<tr><td colspan="4" class="p-8 text-center text-gray-400">No students found matching your search.</td></tr>';
        } else {
            foreach($results as $index => $row) {
                $idx = $row['position']; // Use calculated position
                $adm = htmlspecialchars($row['admission_number']);
                $name = htmlspecialchars($row['last_name'] . ' ' . $row['first_name']);
                $internal_id = $row['internal_student_id'];
                
                echo '<tr class="hover:bg-slate-50 transition-colors">';
                echo "<td class='p-4 text-gray-500'>{$idx}</td>";
                echo "<td class='p-4 font-medium text-gray-700'>{$adm}</td>";
                echo "<td class='p-4 font-semibold text-gray-800'>{$name}</td>";
                echo "<td class='p-4 text-right'>
                        <div class='flex gap-3 justify-end'>
                            <a href='edit_result.php?student_id={$internal_id}&class_id={$selected_class}&session_id={$selected_session}&term_id={$selected_term}' class='text-gray-400 hover:text-blue-600 transition' title='Edit Results'><i class='fas fa-edit fa-lg'></i></a>
                            <a href='download_result_pdf.php?student_id={$internal_id}&class_id={$selected_class}&session_id={$selected_session}&term_id={$selected_term}&type=single' target='_blank' class='text-gray-400 hover:text-green-600 transition' title='Print Result'><i class='fas fa-print fa-lg'></i></a>
                        </div>
                      </td>";
                echo '</tr>';
            }
        }
        $rows_html = ob_get_clean();
        
        // Generate Pagination HTML
        ob_start();
        if ($selected_class && $total_pages > 1) {
            echo '<div class="flex flex-col sm:flex-row justify-between items-center gap-4">';
            // Info
            echo '<div class="text-sm text-gray-600">Showing <strong>' . ($offset + 1) . '</strong> to <strong>' . min($offset + $items_per_page, $total_students) . '</strong> of <strong>' . $total_students . '</strong> students</div>';
            
            // Buttons
            echo '<div class="flex gap-2">';
            $base_url = "?session_id={$selected_session}&term_id={$selected_term}&class_id={$selected_class}";
            if (!empty($search_query)) $base_url .= "&search=" . urlencode($search_query);
            
            if ($current_page > 1) {
                echo '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition"><i class="fas fa-chevron-left"></i></a>';
            }
            
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1) {
                 echo '<a href="' . $base_url . '&page=1" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">1</a>';
                 if ($start_page > 2) echo '<span class="px-3 py-2">...</span>';
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<span class="px-3 py-2 bg-nsknavy text-white rounded-lg text-sm font-semibold">' . $i . '</span>';
                } else {
                    echo '<a href="' . $base_url . '&page=' . $i . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">' . $i . '</a>';
                }
            }
             
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) echo '<span class="px-3 py-2">...</span>';
                echo '<a href="' . $base_url . '&page=' . $total_pages . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">' . $total_pages . '</a>';
            }
            
            if ($current_page < $total_pages) {
                echo '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition"><i class="fas fa-chevron-right"></i></a>';
            }
            echo '</div></div>';
        }
        $pagination_html = ob_get_clean();
        
        $count_info = "Found <strong>{$total_students}</strong> student(s)";
        if (!empty($search_query)) $count_info .= " matching \"<strong>" . htmlspecialchars($search_query) . "</strong>\"";

        echo json_encode([
            'rows_html' => $rows_html,
            'pagination_html' => $pagination_html,
            'count_info' => $count_info
        ]);
        exit;
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - Northland Schools</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }
        .nsk-btn { transition: all 0.3s ease; }
        .nsk-btn:hover { transform: translateY(-2px); }
        
        /* Sidebar Variables if style.css fails or for specific override */
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --transition-speed: 0.3s;
        }
    </style>
</head>
<body class="bg-slate-50 flex">



<main class="main-content min-h-screen transition-all duration-300 p-8">
    
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Student Results</h1>
            <p class="text-gray-500 text-sm">Manage and view assessment scores</p>
        </div>
        <div class="flex gap-3">
             <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="nsk-btn bg-emerald-600 text-white px-4 py-2 rounded-lg shadow hover:bg-emerald-700 flex items-center gap-2">
                <i class="fas fa-file-excel"></i> Upload Results
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Session</label>
                <select name="session_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <?php foreach($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_session == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['session_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Term</label>
                <select name="term_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <?php foreach($terms as $t): 
                        $is_future = !empty($t['start_date']) && $t['start_date'] > $today;
                    ?>
                        <option value="<?= $t['id'] ?>" <?= $selected_term == $t['id'] ? 'selected' : '' ?> <?= $is_future ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($t['term_name']) ?><?= $is_future ? ' (Upcoming)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Class</label>
                <select name="class_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none" onchange="this.form.submit()">
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Action Toolbar -->
    <?php if($selected_class): ?>
    <div class="flex flex-wrap gap-3 mb-6">
        <a href="generate_template.php?class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>" target="_blank" class="nsk-btn bg-white border border-blue-600 text-blue-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 flex items-center gap-2">
            <i class="fas fa-download"></i> Download Template
        </a>
        <a href="download_broadsheet.php?class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>" target="_blank" class="nsk-btn bg-white border border-purple-600 text-purple-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-50 flex items-center gap-2">
            <i class="fas fa-table"></i> Export Broadsheet
        </a>
         <a href="download_result_pdf.php?class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>&type=bulk" target="_blank" class="nsk-btn bg-nsknavy text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-nskblue flex items-center gap-2">
            <i class="fas fa-file-pdf"></i> Download All PDFs
        </a>
    </div>
    <?php endif; ?>

    <?php if($selected_class): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex gap-3 items-center">
            <!-- Hidden inputs to preserve filters for JS -->
            <input type="hidden" id="filterSessionId" value="<?= $selected_session ?>">
            <input type="hidden" id="filterTermId" value="<?= $selected_term ?>">
            <input type="hidden" id="filterClassId" value="<?= $selected_class ?>">
            
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <!-- ID globalSearch used for AJAX binding -->
                <input type="text" id="globalSearch" value="<?= htmlspecialchars($search_query) ?>" 
                       placeholder="Search by name or admission number..." 
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-shadow">
                <!-- Loading Indicator -->
                <div id="searchLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center opacity-0 transition-opacity">
                    <i class="fas fa-spinner fa-spin text-blue-600"></i>
                </div>
            </div>
        </div>
        <p class="text-sm text-gray-600 mt-2" id="resultsCountDisplay">
            Found <strong><?= $total_students ?></strong> student(s)
        </p>
    </div>
    <?php endif; ?>

    <!-- Results Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-nsknavy text-white border-b border-nskblue text-xs uppercase font-semibold">
                        <th class="p-4 text-left">S/N</th>
                        <th class="p-4 text-left">Admission No</th>
                        <th class="p-4 text-left">Student Name</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="resultsTableBody" class="text-sm divide-y divide-gray-50">
                    <?php if(empty($results)): ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-400">No students found in this class.</td></tr>
                    <?php else: ?>
                        <?php foreach($results as $index => $row): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="p-4 text-gray-500"><?= $index + 1 ?></td>
                            <td class="p-4 font-medium text-gray-700"><?= htmlspecialchars($row['admission_number']) ?></td>
                            <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($row['last_name'] . ' ' . $row['first_name']) ?></td>
                            <td class="p-4 text-right">
                                <div class="flex gap-3 justify-end">
                                    <a href="edit_result.php?student_id=<?= $row['internal_student_id'] ?>&class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>" class="text-gray-400 hover:text-blue-600 transition" title="Edit Results">
                                        <i class="fas fa-edit fa-lg"></i>
                                    </a>
                                    <a href="download_result_pdf.php?student_id=<?= $row['internal_student_id'] ?>&class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>&type=single" target="_blank" class="text-gray-400 hover:text-green-600 transition" title="Print Result">
                                        <i class="fas fa-print fa-lg"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <!-- Pagination -->
        <div id="paginationContainer" class="border-t border-gray-100 px-4 py-4">
            <?php if ($selected_class && $total_pages > 1): ?>
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <!-- Results Info -->
                <div class="text-sm text-gray-600">
                    Showing <strong><?= ($offset + 1) ?></strong> to <strong><?= min($offset + $items_per_page, $total_students) ?></strong> of <strong><?= $total_students ?></strong> students
                </div>

                <!-- Page Numbers -->
                <div class="flex gap-2">
                    <?php
                    // Build base URL with filters
                    $base_url = "?session_id={$selected_session}&term_id={$selected_term}&class_id={$selected_class}";
                    if (!empty($search_query)) {
                        $base_url .= "&search=" . urlencode($search_query);
                    }
                    
                    // Previous button
                    if ($current_page > 1): ?>
                        <a href="<?= $base_url ?>&page=<?= $current_page - 1 ?>" 
                           class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    // Page number buttons
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="<?= $base_url ?>&page=1" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="px-3 py-2">...</span>
                        <?php endif; ?>
                    <?php endif; 

                    for ($i = $start_page; $i <= $end_page; $i++): 
                        if ($i == $current_page): ?>
                            <span class="px-3 py-2 bg-nsknavy text-white rounded-lg text-sm font-semibold"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $base_url ?>&page=<?= $i ?>" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition"><?= $i ?></a>
                        <?php endif; 
                    endfor; 
                    
                    if ($end_page < $total_pages): 
                        if ($end_page < $total_pages - 1): ?>
                            <span class="px-3 py-2">...</span>
                        <?php endif; ?>
                        <a href="<?= $base_url ?>&page=<?= $total_pages ?>" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?= $base_url ?>&page=<?= $current_page + 1 ?>" 
                           class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<!-- Upload Modal -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 transform scale-100 transition-all">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Upload Results</h3>
            <button id="closeModalBtn" type="button" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="uploadForm" class="space-y-4">
            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
            <input type="hidden" name="session_id" value="<?= $selected_session ?>">
            <input type="hidden" name="term_id" value="<?= $selected_term ?>">
            <?= CSRF::getTokenField() ?>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-500 transition-colors bg-gray-50/50 cursor-pointer" id="fileDropArea">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                <p class="text-gray-600 font-medium mb-1">Click to browse or drag file</p>
                <p class="text-xs text-gray-400 mb-2">Excel files (.xls, .xlsx) only</p>
                <p class="text-xs text-blue-600 font-medium hidden" id="fileNameDisplay"></p>
                <input type="file" id="fileInput" name="result_file" accept=".xls,.xlsx" class="hidden" required>
            </div>
            
            <div class="flex gap-3">
                <button type="button" id="cancelBtn" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl transition">
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
                <button type="submit" id="submitBtn" class="flex-1 bg-nsknavy hover:bg-nskblue text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-200">
                    <i class="fas fa-check-circle mr-2"></i> Upload & Process
                </button>
            </div>
        </form>
        <div id="uploadStatus" class="mt-4 hidden text-center text-sm font-medium"></div>
    </div>
</div>

<script>
// Upload Modal Management
const uploadModal = document.getElementById('uploadModal');
const uploadForm = document.getElementById('uploadForm');
const fileInput = document.getElementById('fileInput');
const fileDropArea = document.getElementById('fileDropArea');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelBtn');
const closeModalBtn = document.getElementById('closeModalBtn');
const statusDiv = document.getElementById('uploadStatus');

// File size limit: 5MB
const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ALLOWED_EXTENSIONS = ['xls', 'xlsx'];

// Client-side validation
function validateFile(file) {
    // Check if file exists
    if (!file) {
        return { valid: false, message: 'Please select a file.' };
    }
    
    // Check file size
    if (file.size > MAX_FILE_SIZE) {
        return { valid: false, message: `File too large. Maximum size is ${(MAX_FILE_SIZE / 1024 / 1024).toFixed(0)}MB. Your file is ${(file.size / 1024 / 1024).toFixed(2)}MB.` };
    }
    
    // Check file extension
    const fileName = file.name.toLowerCase();
    const fileExtension = fileName.split('.').pop();
    if (!ALLOWED_EXTENSIONS.includes(fileExtension)) {
        return { valid: false, message: `Invalid file type. Only ${ALLOWED_EXTENSIONS.join(', ')} files are allowed.` };
    }
    
    return { valid: true };
}

// Show error message
function showError(message) {
    statusDiv.classList.remove('hidden', 'text-green-600');
    statusDiv.classList.add('text-red-600');
    statusDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
}

// Show success message
function showSuccess(message) {
    statusDiv.classList.remove('hidden', 'text-red-600');
    statusDiv.classList.add('text-green-600');
    statusDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
}

// Show loading state
function showLoading(message = 'Processing...') {
    statusDiv.classList.remove('hidden', 'text-green-600', 'text-red-600');
    statusDiv.classList.add('text-blue-600');
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + message;
}

// Reset form function
function resetUploadForm() {
    uploadForm.reset();
    fileInput.value = '';
    fileNameDisplay.classList.add('hidden');
    fileNameDisplay.textContent = '';
    statusDiv.classList.add('hidden');
    statusDiv.classList.remove('text-green-600', 'text-red-600', 'text-blue-600');
    statusDiv.textContent = '';
    submitBtn.disabled = false;
    cancelBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Upload & Process';
}

// Close modal function
function closeModal() {
    uploadModal.classList.add('hidden');
    resetUploadForm();
}

// File drop area click handler
fileDropArea.addEventListener('click', () => {
    fileInput.click();
});

// File selection handler with validation
fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const validation = validateFile(file);
        
        if (!validation.valid) {
            showError(validation.message);
            this.value = ''; // Clear the file
            fileNameDisplay.classList.add('hidden');
            return;
        }
        
        // Show file name
        fileNameDisplay.textContent = `✓ Selected: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        fileNameDisplay.classList.remove('hidden');
        statusDiv.classList.add('hidden');
    }
});

// Drag and drop support
fileDropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileDropArea.classList.add('border-blue-500', 'bg-blue-50');
});

fileDropArea.addEventListener('dragleave', () => {
    fileDropArea.classList.remove('border-blue-500', 'bg-blue-50');
});

fileDropArea.addEventListener('drop', (e) => {
    e.preventDefault();
    fileDropArea.classList.remove('border-blue-500', 'bg-blue-50');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

// Cancel button
cancelBtn.addEventListener('click', closeModal);
closeModalBtn.addEventListener('click', closeModal);

// Form submission with validation and progress
uploadForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const file = fileInput.files[0];
    
    // Client-side validation
    const validation = validateFile(file);
    if (!validation.valid) {
        showError(validation.message);
        return;
    }
    
    showLoading('Uploading and processing file...');
    submitBtn.disabled = true;
    cancelBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('results.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message || 'Results uploaded successfully!');
            
            // Add confetti or celebration animation
            setTimeout(() => {
                closeModal();
                // Force hard reload bypassing cache
                window.location.href = window.location.pathname + '?' + new Date().getTime() + window.location.hash;
            }, 1500);
        } else {
            showError(result.message || 'Upload failed. Please check your file and try again.');
            submitBtn.disabled = false;
            cancelBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Upload & Process';
        }
    } catch (error) {
        console.error('Upload error:', error);
        showError('Network or server error. Please check your connection and try again.');
        submitBtn.disabled = false;
        cancelBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Upload & Process';
    }
});

// Close modal on outside click
uploadModal.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Keyboard shortcut: Escape to close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !uploadModal.classList.contains('hidden')) {
        closeModal();
    }
});

// Live Search Implementation
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('globalSearch');
    const resultsTableBody = document.getElementById('resultsTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const searchLoading = document.getElementById('searchLoading');
    const resultsCountDisplay = document.getElementById('resultsCountDisplay');

    if (searchInput) {
        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // Fetch Results Function
        function fetchResults(page = 1) {
            const searchQuery = searchInput.value;
            const sessionId = document.getElementById('filterSessionId').value;
            const termId = document.getElementById('filterTermId').value;
            const classId = document.getElementById('filterClassId').value;

            // Show loading
            if (searchLoading) searchLoading.classList.remove('opacity-0');
            resultsTableBody.style.opacity = '0.5';

            // Construct URL
            const url = `view_results.php?ajax_search=1&page=${page}&search=${encodeURIComponent(searchQuery)}&session_id=${sessionId}&term_id=${termId}&class_id=${classId}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Update Table
                    if (data.rows_html) {
                        resultsTableBody.innerHTML = data.rows_html;
                    }
                    
                    // Update Pagination
                    if (paginationContainer && data.pagination_html) {
                        paginationContainer.innerHTML = data.pagination_html;
                        // Re-attach pagination listeners provided the HTML contains onclicks or standard links? 
                        // The PHP returns links with href. For AJAX, we should probably intercept clicks or use onclick="changePage()".
                        // But my PHP generates links like href="?page=2".
                        // To make pagination fully AJAX without reload, I need to intercept these clicks.
                        attachPaginationListeners(); 
                    }

                    // Update Count
                    if (resultsCountDisplay && data.count_info) {
                        resultsCountDisplay.innerHTML = data.count_info;
                    }

                    // Update URL history (optional, to keep state on refresh)
                    const stateUrl = `view_results.php?page=${page}&search=${encodeURIComponent(searchQuery)}&session_id=${sessionId}&term_id=${termId}&class_id=${classId}`;
                    history.pushState(null, '', stateUrl);
                })
                .catch(err => {
                    console.error('Search Error:', err);
                })
                .finally(() => {
                    if (searchLoading) searchLoading.classList.add('opacity-0');
                    resultsTableBody.style.opacity = '1';
                });
        }

        // Attach Input Listener
        searchInput.addEventListener('input', debounce(() => fetchResults(1), 300)); // Reset to page 1

        // Intercept Pagination Clicks
        function attachPaginationListeners() {
            if (!paginationContainer) return;
            const links = paginationContainer.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const urlParams = new URLSearchParams(this.getAttribute('href').split('?')[1]);
                    const page = urlParams.get('page') || 1;
                    fetchResults(page);
                });
            });
        }
        
        // Init pagination listeners
        attachPaginationListeners();
    }
});
</script>

<?php
function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}
?>
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="mobile-overlay"></div>
    
    <?php include 'sidebar.php'; ?>
</body>
</html>
