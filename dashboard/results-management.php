<?php
// AJAX Handler
if (isset($_GET["ajax"]) && $_GET["ajax"] == "1") {
    require_once "../config/database.php";
    header("Content-Type: application/json");
    try {
        $database = new Database();
        $db = $database->getConnection();
        $page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
        $search = $_GET["search"] ?? "";
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        echo json_encode(["success" => false, "message" => "AJAX not implemented for this page yet"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}
/**
 * Admin Results Management Page
 * Displays students for any class/session/term with their calculated results.
 * Allows downloading templates, uploading scores, and exporting broadsheets/PDFs.
 */

require_once 'auth-check.php';
require_once __DIR__ . "/../includes/term_helper.php"; // Global term synchronization
checkAuth('admin'); // Ensure user is admin

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../config/database.php';

// Generate CSRF token (simplified if no class available)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Initialize Database
$database = new Database();
$db = $database->getConnection();

// --- Data Fetching Logic ---

// 1. Fetch Active Sessions
$sessions = [];
try {
    $stmt = $db->query("SELECT id, session_name FROM academic_sessions ORDER BY id DESC");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Ignore */ }

// Defaults for session
$selected_session = $_GET['session_id'] ?? ($sessions[0]['id'] ?? 1);

// 2. Fetch Terms (filtered by selected session)
$terms = [];
try {
    $stmt = $db->prepare("SELECT id, term_name FROM terms WHERE session_id = ? ORDER BY id ASC");
    $stmt->execute([$selected_session]);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Ignore */ }

// 3. Fetch ALL Classes (Admin View)
$classes = [];
try {
    $stmt = $db->query("SELECT id, class_name FROM classes ORDER BY class_name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Ignore */ }

// Get current term ID for the selected session
$current_term_id = null;
try {
    $stmt = $db->prepare("SELECT id FROM terms WHERE is_current = 1 AND session_id = ? LIMIT 1");
    $stmt->execute([$selected_session]);
    $current_term_id = $stmt->fetchColumn();
} catch (PDOException $e) { /* Ignore */ }

// Defaults
$selected_term = isset($_GET['term_id']) ? filter_var($_GET['term_id'], FILTER_VALIDATE_INT) : ($current_term_id ?? ($terms[0]['id'] ?? 1));
$selected_class = isset($_GET['class_id']) ? filter_var($_GET['class_id'], FILTER_VALIDATE_INT) : ($classes[0]['id'] ?? null);

// 4. Fetch Results (if class selected)
$results = [];
$total_students = 0;
$total_pages = 1;
$items_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_VALIDATE_INT)) : 1;
$search_query = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : '';
$offset = ($current_page - 1) * $items_per_page;

if ($selected_class) {
    try {
        $where_conditions = ["s.class_id = :cid"];
        $search_param = null;
        
        if (!empty($search_query)) {
            $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR s.student_id LIKE :search OR s.admission_number LIKE :search)";
            $search_param = "%{$search_query}%";
        }
        
        $where_clause = implode(" AND ", $where_conditions);

        // Count total
        $count_query = "
            SELECT COUNT(DISTINCT s.id) as total
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE {$where_clause}
        ";
        $stmt_count = $db->prepare($count_query);
        $stmt_count->bindParam(':cid', $selected_class);
        if ($search_param) $stmt_count->bindParam(':search', $search_param);
        $stmt_count->execute();
        $total_students = $stmt_count->fetchColumn();
        $total_pages = ceil($total_students / $items_per_page);

        // Fetch Students with Results Summary
        $query = "
            SELECT 
                s.id AS internal_student_id,
                s.student_id AS admission_number,
                u.first_name, u.last_name,
                COALESCE(SUM(sr.total_score), 0) as total_obtained,
                COALESCE(COUNT(sr.id), 0) as subjects_taken
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN student_results sr ON s.id = sr.student_id 
                AND sr.class_id = :cid 
                AND sr.session_id = :sid
                AND sr.term_id = :tid
            WHERE {$where_clause}
            GROUP BY s.id, s.student_id, u.first_name, u.last_name
            ORDER BY u.last_name, u.first_name
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':cid', $selected_class, PDO::PARAM_INT);
        $stmt->bindParam(':sid', $selected_session, PDO::PARAM_INT);
        $stmt->bindParam(':tid', $selected_term, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        if ($search_param) $stmt->bindParam(':search', $search_param);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add position logic in PHP loop for display (simple sequential)
        $position = $offset + 1;
        foreach ($results as &$result) {
            $result['position'] = $position++;
        }

    } catch (PDOException $e) { /* Log */ }
}

// --- AJAX Handler ---
if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    
    // Generate Rows HTML
    ob_start();
    if (empty($results)) {
        echo '<tr><td colspan="4" class="p-8 text-center text-gray-400">No students found matching your search.</td></tr>';
    } else {
        foreach($results as $row) {
            $idx = $row['position'];
            $adm = htmlspecialchars($row['admission_number']);
            $name = htmlspecialchars($row['last_name'] . ' ' . $row['first_name']);
            $id = $row['internal_student_id'];
            
            echo '<tr class="hover:bg-slate-50 transition-colors border-b border-gray-50">';
            echo "<td class='p-4 text-gray-500'>{$idx}</td>";
            echo "<td class='p-4 font-medium text-gray-700'>{$adm}</td>";
            echo "<td class='p-4 font-semibold text-gray-800'>{$name}</td>";
            echo "<td class='p-4 text-right'>
                    <div class='flex gap-3 justify-end'>
                        <a href='edit-result.php?student_id={$id}&class_id={$selected_class}&session_id={$selected_session}&term_id={$selected_term}' class='text-gray-400 hover:text-nskblue transition' title='Edit Results'><i class='fas fa-edit fa-lg'></i></a>
                        <a href='../sms-teacher/download_result_pdf.php?student_id={$id}&class_id={$selected_class}&session_id={$selected_session}&term_id={$selected_term}&type=single' target='_blank' class='text-gray-400 hover:text-nskgreen transition' title='Print Result'><i class='fas fa-print fa-lg'></i></a>
                    </div>
                  </td>";
            echo '</tr>';
        }
    }
    $rows_html = ob_get_clean();
    
    // Generate Pagination HTML
    ob_start();
    if ($selected_class && $total_pages > 1) {
        $base_url = "?session_id={$selected_session}&term_id={$selected_term}&class_id={$selected_class}";
        if (!empty($search_query)) $base_url .= "&search=" . urlencode($search_query);
        
        echo '<div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">';
        echo '<div class="text-sm text-gray-600">Showing <strong>' . ($offset + 1) . '</strong> to <strong>' . min($offset + $items_per_page, $total_students) . '</strong> of <strong>' . $total_students . '</strong> students</div>';
        echo '<div class="flex gap-2">';
        
        if ($current_page > 1) {
            echo '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition"><i class="fas fa-chevron-left"></i></a>';
        }
        
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        
        if ($start > 1) {
            echo '<a href="' . $base_url . '&page=1" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">1</a>';
            if ($start > 2) echo '<span class="px-3 py-2">...</span>';
        }
        
        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $current_page ? 'bg-nsknavy text-white' : 'bg-white border border-gray-200 hover:bg-gray-50';
            echo '<a href="' . $base_url . '&page=' . $i . '" class="px-3 py-2 rounded-lg text-sm font-semibold transition ' . $active . '">' . $i . '</a>';
        }
        
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo '<span class="px-3 py-2">...</span>';
            echo '<a href="' . $base_url . '&page=' . $total_pages . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition">' . $total_pages . '</a>';
        }
        
        if ($current_page < $total_pages) {
            echo '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition"><i class="fas fa-chevron-right"></i></a>';
        }
        echo '</div></div>';
    }
    $pagination_html = ob_get_clean();
    
    echo json_encode(['rows_html' => $rows_html, 'pagination_html' => $pagination_html]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Management - Northland Schools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',
                        nsknavy: '#1e3a8a',
                        nskgold: '#f59e0b',
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
    </style>
</head>
<body>

    <?php require_once 'sidebar.php'; ?>

    <main class="main-content flex-1 min-w-0 overflow-auto">
        <!-- Header -->
        <header class="bg-white shadow-sm p-4 sticky top-0 z-20">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <button class="md:hidden sidebar-toggle text-nsknavy text-xl">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-bold text-nsknavy">Results Management</h1>
                </div>
                <div class="flex gap-3">
                   <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="bg-nskgreen text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition flex items-center gap-2 shadow-sm">
                        <i class="fas fa-file-excel"></i> Upload Results
                    </button>
                </div>
            </div>
        </header>


        <div class="px-4 py-3">
            <!-- Filters -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 mb-4 transition-all hover:shadow-md">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Session</label>
                        <select name="session_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-nskblue focus:border-nskblue outline-none transition-all" onchange="this.form.submit()">
                            <?php foreach($sessions as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $selected_session == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['session_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Term</label>
                        <select name="term_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-nskblue focus:border-nskblue outline-none transition-all" onchange="this.form.submit()">
                            <?php foreach($terms as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $selected_term == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['term_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Class</label>
                        <select name="class_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-nskblue focus:border-nskblue outline-none transition-all" onchange="this.form.submit()">
                            <?php foreach($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if($selected_class): ?>
            <!-- Toolbar -->
            <div class="flex flex-wrap gap-3 mb-4">
                <!-- Using absolute paths to sms-teacher scripts which admin can access -->
                <a href="../sms-teacher/generate_template.php?class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>" target="_blank" class="bg-white border border-nskblue text-nskblue px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 flex items-center gap-2 transition-all">
                    <i class="fas fa-download"></i> Download Template
                </a>
                <a href="../sms-teacher/download_broadsheet.php?class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>" target="_blank" class="bg-white border border-purple-600 text-purple-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-50 flex items-center gap-2 transition-all">
                    <i class="fas fa-table"></i> Export Broadsheet
                </a>
                 <a href="../sms-teacher/download_result_pdf.php?class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>&type=bulk" target="_blank" class="bg-nsknavy text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 flex items-center gap-2 transition-all shadow-sm">
                    <i class="fas fa-file-pdf"></i> Download All PDFs
                </a>
            </div>

            <!-- Search -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
                <div class="flex gap-3 items-center relative">
                    <input type="hidden" id="filterSessionId" value="<?= $selected_session ?>">
                    <input type="hidden" id="filterTermId" value="<?= $selected_term ?>">
                    <input type="hidden" id="filterClassId" value="<?= $selected_class ?>">
                    
                    <div class="absolute left-3 text-gray-400"><i class="fas fa-search"></i></div>
                    <input type="text" id="globalSearch" value="<?= htmlspecialchars($search_query) ?>" 
                           placeholder="Search student name or ID..." 
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-nskblue focus:border-nskblue outline-none transition-all">
                    <div id="searchLoading" class="absolute right-3 opacity-0 transition-opacity text-nskblue"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-nsknavy text-white text-xs uppercase tracking-wider font-bold">
                                <th class="p-4 w-16">S/N</th>
                                <th class="p-4 w-32">Admission ID</th>
                                <th class="p-4">Student Name</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="resultsTableBody" class="text-sm divide-y divide-gray-50">
                            <?php if(empty($results)): ?>
                                <tr><td colspan="4" class="p-8 text-center text-gray-400">No students found.</td></tr>
                            <?php else: ?>
                                <?php foreach($results as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-4 text-gray-500"><?= $row['position'] ?></td>
                                    <td class="p-4 font-medium text-gray-700"><?= htmlspecialchars($row['admission_number']) ?></td>
                                    <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($row['last_name'] . ' ' . $row['first_name']) ?></td>
                                    <td class="p-4 text-right">
                                        <div class="flex gap-3 justify-end">
                                            <a href="edit-result.php?student_id=<?= $row['internal_student_id'] ?>&class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>" class="text-gray-400 hover:text-nskblue transition-colors" title="Edit Results">
                                                <i class="fas fa-edit fa-lg"></i>
                                            </a>
                                            <a href="../sms-teacher/download_result_pdf.php?student_id=<?= $row['internal_student_id'] ?>&class_id=<?= $selected_class ?>&session_id=<?= $selected_session ?>&term_id=<?= $selected_term ?>&type=single" target="_blank" class="text-gray-400 hover:text-nskgreen transition-colors" title="Print Result">
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
                <div id="paginationContainer" class="border-t border-gray-100 px-4 py-4 flex justify-between items-center">
                    <?php if ($selected_class && $total_pages > 1): ?>
                       <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                           <div class="text-sm text-gray-600">Showing <strong><?= ($offset + 1) ?></strong> to <strong><?= min($offset + $items_per_page, $total_students) ?></strong> of <strong><?= $total_students ?></strong> students</div>
                           <!-- Helper standard pagination -->
                           <div class="flex gap-2">
                               <?php
                               $base_url = "?session_id={$selected_session}&term_id={$selected_term}&class_id={$selected_class}";
                               if (!empty($search_query)) $base_url .= "&search=" . urlencode($search_query);
                               
                               if ($current_page > 1) echo "<a href='{$base_url}&page=".($current_page-1)."' class='px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50'><i class='fas fa-chevron-left'></i></a>";
                               echo "<span class='px-3 py-2 bg-nsknavy text-white rounded-lg text-sm font-semibold'>{$current_page}</span>";
                               if ($current_page < $total_pages) echo "<a href='{$base_url}&page=".($current_page+1)."' class='px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm hover:bg-gray-50'><i class='fas fa-chevron-right'></i></a>";
                               ?>
                           </div>
                       </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    
        <?php require_once 'footer.php'; ?>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800">Upload Results</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('uploadModal').classList.add('hidden')">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="uploadForm" class="space-y-4" action="../sms-teacher/results.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                <input type="hidden" name="session_id" value="<?= $selected_session ?>">
                <input type="hidden" name="term_id" value="<?= $selected_term ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-nskblue transition-colors bg-gray-50 cursor-pointer" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 font-medium">Click to select file</p>
                    <p class="text-xs text-gray-400 mt-1">Excel (.xls, .xlsx) files</p>
                    <p id="fileName" class="text-sm text-nskblue mt-2 font-medium"></p>
                    <input type="file" id="fileInput" name="result_file" accept=".xls,.xlsx" class="hidden" required onchange="document.getElementById('fileName').textContent = this.files[0]?.name">
                </div>
                
                <div class="flex gap-3 pt-2">
                     <button type="button" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-lg font-medium" onclick="document.getElementById('uploadModal').classList.add('hidden')">Cancel</button>
                    <button type="submit" class="flex-1 bg-nsknavy hover:bg-nskblue text-white py-2.5 rounded-lg font-medium">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const searchInput = document.getElementById('globalSearch');
        const resultsTableBody = document.getElementById('resultsTableBody');
        const paginationContainer = document.getElementById('paginationContainer');
        const searchLoading = document.getElementById('searchLoading');
        
        let debounceTimer;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchResults(1);
                }, 500);
            });
        }

        function fetchResults(page) {
            const query = searchInput.value;
            const sessionId = document.getElementById('filterSessionId').value;
            const termId = document.getElementById('filterTermId').value;
            const classId = document.getElementById('filterClassId').value;

            if (searchLoading) searchLoading.classList.remove('opacity-0');
            resultsTableBody.style.opacity = '0.5';

            const url = `results-management.php?ajax_search=1&page=${page}&search=${encodeURIComponent(query)}&class_id=${classId}&session_id=${sessionId}&term_id=${termId}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    resultsTableBody.innerHTML = data.rows_html;
                    paginationContainer.innerHTML = data.pagination_html;
                    resultsTableBody.style.opacity = '1';
                    if (searchLoading) searchLoading.classList.add('opacity-0');
                    
                    // Update URL history without reload
                    const newUrl = `results-management.php?class_id=${classId}&session_id=${sessionId}&term_id=${termId}&search=${encodeURIComponent(query)}&page=${page}`;
                    window.history.pushState({path: newUrl}, '', newUrl);
                })
                .catch(err => {
                    console.error('Search failed', err);
                    resultsTableBody.style.opacity = '1';
                });
        }
        
        // Handle pagination clicks within the container
        document.addEventListener('click', function(e) {
            const link = e.target.closest('#paginationContainer a');
            if (link) {
                e.preventDefault();
                const urlParams = new URLSearchParams(link.href.split('?')[1]);
                const page = urlParams.get('page') || 1;
                fetchResults(page);
            }
        });
    </script>

    <!-- Universal AJAX Filter -->
    <script src="clean_filter.js"></script>

</body>
</html>
