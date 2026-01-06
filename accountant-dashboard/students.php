<?php 
require_once '../auth-check.php';
checkAuth('accountant'); // Finance management is for accountants only

require_once '../config/database.php';

// Enable error display for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$students = [];
$totalStudents = 0;
$fullyPaid = 0;
$outstanding = 0;
$unpaidCount = 0;
$partialCount = 0;
$classes = [];
$currentTermName = 'No Active Term';
$currentTermId = null;
$total_pages = 0;
$page = 1;
$total_records = 0;
$errorMessage = null;
$successMessage = null;

// Handle status messages
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $successMessage = "Student deleted successfully!";
            break;
        case 'error':
            $errorMessage = $_GET['msg'] ?? "An error occurred.";
            break;
    }
}

// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

try {
    require_once '../config/DatabaseManager.php';
    $dbManager = DatabaseManager::getInstance();
    $db = $dbManager->getConnection();

    // Get active term
    $termStmt = $db->query("SELECT id, term_name FROM terms WHERE is_current = 1 LIMIT 1");
    $currentTerm = $termStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentTerm) {
        $currentTermId = $currentTerm['id'];
        $currentTermName = $currentTerm['term_name'];
    }

    // Fetch classes for filter dropdown
    $classes = $db->query("SELECT id, class_name FROM classes ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // --- SORTING, FILTERING & PAGINATION LOGIC ---
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10; // Students per page
    
    // Filter params
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $class_id = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? $_GET['class_id'] : null;
    $status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;

    // Build Base Tables Query (Reusable for Stats and List)
    $queryTables = "FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN classes c ON s.class_id = c.id
                    LEFT JOIN (
                        SELECT student_id, 
                               SUM(amount_paid) as total_paid,
                               MAX(id) as last_payment_id
                        FROM payments 
                        WHERE term_id = ?
                        GROUP BY student_id
                    ) payment_summary ON payment_summary.student_id = s.id
                    LEFT JOIN (
                        SELECT class_id, 
                               SUM(amount) as total_fee
                        FROM fee_structure 
                        WHERE term_id = ? AND is_active = 1 AND is_optional = 0
                        GROUP BY class_id
                    ) fee_summary ON fee_summary.class_id = s.class_id";
    
    // Status Calculation SQL
    $statusSql = "CASE 
        WHEN COALESCE(fee_summary.total_fee, 0) <= 0 THEN 'no_fee'
        WHEN COALESCE(payment_summary.total_paid, 0) >= COALESCE(fee_summary.total_fee, 0) THEN 'paid'
        WHEN COALESCE(payment_summary.total_paid, 0) > 0 THEN 'partial'
        ELSE 'unpaid'
    END";

    // 1. Build Filter Conditions
    $whereClause = "WHERE u.is_active = 1 AND s.status = 'active'";
    $params = [$currentTermId, $currentTermId];

    if ($class_id) {
        $whereClause .= " AND s.class_id = ?";
        $params[] = $class_id;
    }

    if ($search) {
        $whereClause .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.student_id LIKE ? OR s.admission_number LIKE ?)";
        $term = "%{$search}%";
        $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
    }

    $havingClause = "";
    if ($status_filter) {
        $havingClause = "HAVING status = ?";
        $params[] = $status_filter;
    }

    // 2. Fetch Aggregated Stats (Count by Status) - Efficiently!
    // We reuse params for this query, but we need to handle the HAVING parameter carefully.
    // The stats query DOES NOT use the status filter (usually stats show the overview of the search result, not just the filtered status itself).
    // Actually, usually "Total" implies total in current search. "Paid" implies paid in current search.
    // So we apply WHERE (Search/Class) but NOT HAVING (Status).
    
    $statsSql = "SELECT status, COUNT(*) as count FROM (
                    SELECT $statusSql as status 
                    $queryTables 
                    $whereClause
                 ) as subquery 
                 GROUP BY status";
    
    // Prepare params for Stats (Exclude HAVING param if it was added)
    $statsParams = array_slice($params, 0, count($params) - ($status_filter ? 1 : 0));

    $statsStmt = $db->prepare($statsSql);
    $statsStmt->execute($statsParams);
    $statsResults = $statsStmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['paid' => 10, 'unpaid' => 5]

    // Calculate Totals from DB results
    $fullyPaid = $statsResults['paid'] ?? 0;
    $unpaidCount = $statsResults['unpaid'] ?? 0;
    $partialCount = $statsResults['partial'] ?? 0;
    $noFeeCount = $statsResults['no_fee'] ?? 0;
    
    // "Outstanding" comprises anyone who hasn't paid in full
    $outstanding = $unpaidCount + $partialCount;
    
    // Total is sum of all status types
    $totalStudents = array_sum($statsResults); 
    
    // Note: If Status Filter is active, pagination "Total Records" is different from "Total Students" stats
    if ($status_filter) {
        $total_records = $statsResults[$status_filter] ?? 0;
    } else {
        $total_records = $totalStudents;
    }

    // 3. Main Data Query with LIMIT
    $total_pages = ceil($total_records / $limit);
    $page = max(1, min($page, $total_pages ?: 1));
    $offset = ($page - 1) * $limit;

    $finalSql = "SELECT 
                 s.id, s.student_id, s.admission_number, u.first_name, u.last_name, u.email,
                 c.class_name, c.id as class_id,
                 COALESCE(payment_summary.total_paid, 0) as total_paid,
                 payment_summary.last_payment_id,
                 COALESCE(fee_summary.total_fee, 0) as total_fee,
                 $statusSql as status
                 $queryTables
                 $whereClause
                 $havingClause
                 ORDER BY u.first_name, u.last_name
                 LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($finalSql);
    $stmt->execute($params); // Uses all params (including HAVING if set)
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process balances (lightweight now)
    foreach ($students as &$st) {
        $st['balance'] = max(0, $st['total_fee'] - $st['total_paid']);
    }
    unset($st);

    // AJAX Response
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        ob_start();
        if (empty($students)) {
            echo '<tr><td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-light);"><i class="fas fa-users" style="font-size: 3rem; color: #e0e0e0; margin-bottom: 15px; display: block;"></i><p style="font-size: 1.1rem; font-weight: 600; color: var(--text-color); margin-bottom: 8px;">No students found</p></td></tr>';
        } else {
            foreach ($students as $student): ?>
                <tr class="student-row">
                    <td>
                        <div style="font-weight: 600; color: var(--brand-navy);">
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 4px;">
                            ID: <?= htmlspecialchars($student['student_id']) ?>
                            <?php if ($student['admission_number']): ?> | Adm: <?= htmlspecialchars($student['admission_number']) ?><?php endif; ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($student['class_name'] ?: 'Not Assigned') ?></td>
                    <td>
                        <?php 
                        $actualBalance = $student['total_fee'] - $student['total_paid'];
                        $isOverpaid = $actualBalance < 0;
                        ?>
                        <div style="font-weight: 600; <?= $student['balance'] > 0 ? 'color: #ff6b6b;' : 'color: var(--success-color);' ?>">
                            <?php if ($isOverpaid): ?>
                                ₦0.00 <span style="font-size: 0.75rem; color: #10b981;">(Overpaid)</span>
                            <?php else: ?>
                                ₦<?= number_format($student['balance'], 2) ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 4px;">
                            Paid: ₦<?= number_format($student['total_paid'], 2) ?> / ₦<?= number_format($student['total_fee'], 2) ?>
                        </div>
                    </td>
                    <td>
                        <?php 
                        $statusBadges = [
                            'paid' => ['label' => 'Fully Paid', 'color' => '#10b981', 'bg' => '#d1fae5'],
                            'partial' => ['label' => 'Partial', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                            'unpaid' => ['label' => 'Not Paid', 'color' => '#ef4444', 'bg' => '#fee2e2'],
                            'no_fee' => ['label' => 'No Fee Set', 'color' => '#6b7280', 'bg' => '#f3f4f6']
                        ];
                        $badge = $statusBadges[$student['status']];
                        ?>
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: <?= $badge['bg'] ?>; color: <?= $badge['color'] ?>;">
                            <?= $badge['label'] ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="payment.php?student_id=<?= $student['id'] ?>" class="btn" style="background: var(--brand-green); color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;" title="Record Payment"><i class="fas fa-money-bill-wave" style="margin-right: 5px;"></i> Pay</a>
                            <?php if ($student['total_paid'] > 0 && $student['last_payment_id']): ?>
                                <a href="receipt.php?id=<?= $student['last_payment_id'] ?>" target="_blank" class="btn" style="background: #3b82f6; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;" title="Reprint Last Receipt"><i class="fas fa-print"></i> Receipt</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach;
        }
        $tableHtml = ob_get_clean();
        
        header('Content-Type: application/json');
        echo json_encode([
            'html' => $tableHtml,
            'total' => $total_records,
            'page' => $page,
            'pages' => $total_pages,
            'stats' => [ // Pass updated stats
                'total' => number_format($totalStudents),
                'paid' => number_format($fullyPaid),
                'unpaid' => number_format($outstanding),
                'paid_pct' => $totalStudents > 0 ? round(($fullyPaid / $totalStudents) * 100, 1) : 0,
                'unpaid_pct' => $totalStudents > 0 ? round(($outstanding / $totalStudents) * 100, 1) : 0
            ]
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("Error fetching student data: " . $e->getMessage());
    $errorMessage = "Database Error: " . $e->getMessage();
}

include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <?php if ($successMessage): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 8px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    
    <div class="page-title-box" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 class="page-title">Student Fee Status</h1>
            <p style="color: var(--text-light); margin-top: 5px;">Track payment status by student and class - <?= htmlspecialchars($currentTermName) ?></p>
        </div>
        <?php if (!$currentTermId): ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 20px; flex: 1; max-width: 400px; font-weight: 500;">
                <i class="fas fa-exclamation-triangle mr-2"></i> No active term found. Please set a term as current in Academic Management.
            </div>
        <?php endif; ?>
        <div style="display: flex; gap: 10px;">
            <a href="export_students.php" class="btn" id="exportBtn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color); display: flex; align-items: center; text-decoration: none;">
                <i class="fas fa-file-export" style="margin-right:8px;"></i> Export List
            </a>
            <a href="student_form.php" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Add New Student</a>
        </div>
    </div>

    <!-- Stats Overview for Fees -->
    <div class="stats-grid" style="padding: 0 0 30px 0; grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card blue">
            <span class="label">Total Students</span>
            <span class="value" id="statTotal"><?= number_format($totalStudents) ?></span>
        </div>
        <div class="stat-card green">
            <span class="label">Fully Paid</span>
            <span class="value" id="statPaid"><?= number_format($fullyPaid) ?></span>
            <span class="trend text-success" id="statPaidPct"><?= $totalStudents > 0 ? round(($fullyPaid / $totalStudents) * 100, 1) : 0 ?>% of Students</span>
        </div>
        <div class="stat-card orange">
            <span class="label">Outstanding Fees</span>
            <span class="value" id="statUnpaid"><?= number_format($outstanding) ?></span>
            <span class="trend text-danger" id="statUnpaidPct"><?= $totalStudents > 0 ? round(($outstanding / $totalStudents) * 100, 1) : 0 ?>% of Students</span>
        </div>
    </div>

    <!-- Search/Filter Controls -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid var(--border-color);">
        <h4 style="margin-bottom: 15px; color: var(--brand-navy);">Filter Students</h4>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 250px;">
                <input type="text" id="searchInput" placeholder="Search by name or admission number..." style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <select id="classFilter" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color);">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <select id="statusFilter" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color);">
                    <option value="">All Payment Statuses</option>
                    <option value="paid">Fully Paid</option>
                    <option value="partial">Partially Paid</option>
                    <option value="unpaid">Not Paid</option>
                </select>
            </div>
            <button class="btn btn-primary" id="resetBtn" style="padding-left: 25px; padding-right: 25px;">Reset</button>
        </div>
    </div>

    <!-- Student Table -->
    <div class="table-container" style="margin: 0;">
        <div class="table-header">
            <h3>Student List <span id="resultCount">(<?= count($students) ?>)</span></h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Student Info</th>
                    <th>Class</th>
                    <th>Fee Balance</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px 20px; color: var(--text-light);">
                            <i class="fas fa-users" style="font-size: 3rem; color: #e0e0e0; margin-bottom: 15px; display: block;"></i>
                            <p style="font-size: 1.1rem; font-weight: 600; color: var(--text-color); margin-bottom: 8px;">No students found</p>
                            <p style="font-size: 0.9rem;">Students will appear here when they are enrolled in the system.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr class="student-row">
                            <td>
                                <div style="font-weight: 600; color: var(--brand-navy);">
                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 4px;">
                                    ID: <?= htmlspecialchars($student['student_id']) ?>
                                    <?php if ($student['admission_number']): ?>
                                        | Adm: <?= htmlspecialchars($student['admission_number']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($student['class_name'] ?: 'Not Assigned') ?></td>
                            <td>
                                <?php 
                                $actualBalance = $student['total_fee'] - $student['total_paid'];
                                $isOverpaid = $actualBalance < 0;
                                ?>
                                <div style="font-weight: 600; <?= $student['balance'] > 0 ? 'color: #ff6b6b;' : 'color: var(--success-color);' ?>">
                                    <?php if ($isOverpaid): ?>
                                        ₦0.00 <span style="font-size: 0.75rem; color: #10b981;">(Overpaid)</span>
                                    <?php else: ?>
                                        ₦<?= number_format($student['balance'], 2) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 4px;">
                                    Paid: ₦<?= number_format($student['total_paid'], 2) ?> / ₦<?= number_format($student['total_fee'], 2) ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $statusBadges = [
                                    'paid' => ['label' => 'Fully Paid', 'color' => '#10b981', 'bg' => '#d1fae5'],
                                    'partial' => ['label' => 'Partial', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                                    'unpaid' => ['label' => 'Not Paid', 'color' => '#ef4444', 'bg' => '#fee2e2'],
                                    'no_fee' => ['label' => 'No Fee Set', 'color' => '#6b7280', 'bg' => '#f3f4f6']
                                ];
                                $badge = $statusBadges[$student['status']];
                                ?>
                                <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; background: <?= $badge['bg'] ?>; color: <?= $badge['color'] ?>;">
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="payment.php?student_id=<?= $student['id'] ?>" class="btn" style="background: var(--brand-green); color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;" title="Record Payment">
                                        <i class="fas fa-money-bill-wave" style="margin-right: 5px;"></i> Pay
                                    </a>
                                    <?php if ($student['total_paid'] > 0 && $student['last_payment_id']): ?>
                                        <a href="receipt.php?id=<?= $student['last_payment_id'] ?>" target="_blank" class="btn" style="background: #3b82f6; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;" title="Reprint Last Receipt">
                                            <i class="fas fa-print"></i> Receipt
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Results Info & Pagination -->
        <div style="padding: 20px 25px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; color: var(--text-light); font-size: 0.9rem;">
            <span id="showingInfo">Showing <?= count($students) ?> of <?= $totalStudents ?> students</span>
            <div id="paginationControls" style="display: flex; gap: 5px;">
                <!-- Pagination will be rendered here via JS -->
                 <?php if ($total_pages > 1): ?>
                    <button class="btn" style="padding: 5px 10px; border: 1px solid #ddd; background: #fff;" disabled>Previous</button>
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <button class="btn" style="padding: 5px 10px; border: 1px solid #ddd; <?= $i==$page ? 'background: var(--brand-navy); color: white;' : 'background: #fff;' ?>"><?= $i ?></button>
                    <?php endfor; ?>
                    <button class="btn" style="padding: 5px 10px; border: 1px solid #ddd; background: #fff;">Next</button>
                 <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
// Backend Pagination & Filtering Logic
let currentPage = 1;
let currentFilters = {
    search: '',
    class_id: '',
    status: ''
};

const searchInput = document.getElementById('searchInput');
const classFilter = document.getElementById('classFilter');
const statusFilter = document.getElementById('statusFilter');
const resetBtn = document.getElementById('resetBtn');
const studentTableBody = document.getElementById('studentTableBody');
const resultCount = document.getElementById('resultCount');
const showingInfo = document.getElementById('showingInfo');
const paginationControls = document.getElementById('paginationControls');
const exportBtn = document.getElementById('exportBtn');

// Stat Elements
const statTotal = document.getElementById('statTotal');
const statPaid = document.getElementById('statPaid');
const statPaidPct = document.getElementById('statPaidPct');
const statUnpaid = document.getElementById('statUnpaid');
const statUnpaidPct = document.getElementById('statUnpaidPct');

async function loadStudents(page = 1) {
    currentPage = page;
    
    // Update filters
    currentFilters.search = searchInput.value;
    currentFilters.class_id = classFilter.value;
    currentFilters.status = statusFilter.value;
    
    // Update Export URL
    const exportUrl = `export_students.php?search=${encodeURIComponent(currentFilters.search)}&class_id=${encodeURIComponent(currentFilters.class_id)}&status=${encodeURIComponent(currentFilters.status)}`;
    exportBtn.href = exportUrl;

    // Build URL for AJAX
    const params = new URLSearchParams({
        ajax: '1',
        page: page,
        ...currentFilters
    });
    
    // Show Loading
    studentTableBody.style.opacity = '0.5';
    
    try {
        const response = await fetch(`students.php?${params.toString()}`);
        const data = await response.json();
        
        // Update Table
        studentTableBody.innerHTML = data.html;
        studentTableBody.style.opacity = '1';
        
        // Update Counts
        resultCount.textContent = `(${data.total})`;
        showingInfo.textContent = `Showing ${Math.min(10, data.total)} of ${data.total} students`;
        
        // Update Stats (if available in response)
        if (data.stats) {
            if(statTotal) statTotal.textContent = data.stats.total;
            if(statPaid) statPaid.textContent = data.stats.paid;
            if(statUnpaid) statUnpaid.textContent = data.stats.unpaid;
            if(statPaidPct) statPaidPct.textContent = data.stats.paid_pct + '% of Students';
            if(statUnpaidPct) statUnpaidPct.textContent = data.stats.unpaid_pct + '% of Students';
        }

        // Render Pagination
        renderPagination(data.page, data.pages);
        
    } catch (error) {
        console.error('Error loading students:', error);
        studentTableBody.style.opacity = '1';
        alert('Failed to load data. Please try again.');
    }
}

function renderPagination(currentPage, totalPages) {
    if (totalPages <= 1) {
        paginationControls.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous
    html += `<button class="btn" onclick="loadStudents(${currentPage - 1})" style="padding: 5px 10px; border: 1px solid #ddd; background: #fff; cursor: pointer;" ${currentPage <= 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>Previous</button>`;
    
    // Page Numbers
    for (let i = 1; i <= totalPages; i++) {
        const activeStyle = i === currentPage ? 'background: var(--brand-navy); color: white; border-color: var(--brand-navy);' : 'background: white; border: 1px solid #ddd; color: var(--text-color);';
        html += `<button class="btn" onclick="loadStudents(${i})" style="padding: 5px 10px; margin: 0 2px; cursor: pointer; ${activeStyle}">${i}</button>`;
    }
    
    // Next
    html += `<button class="btn" onclick="loadStudents(${currentPage + 1})" style="padding: 5px 10px; border: 1px solid #ddd; background: #fff; cursor: pointer;" ${currentPage >= totalPages ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>Next</button>`;
    
    paginationControls.innerHTML = html;
}

// Event listeners
let debounceTimer;
searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadStudents(1), 300);
});

classFilter.addEventListener('change', () => loadStudents(1));
statusFilter.addEventListener('change', () => loadStudents(1));

resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    classFilter.value = '';
    statusFilter.value = '';
    loadStudents(1);
});

// Initial Render Pagination (for the server-rendered first page)
renderPagination(<?= $page ?>, <?= $total_pages ?>);

// Delete functionality
function deleteStudent(studentId, studentName) {
    if (confirm(`Are you sure you want to delete ${studentName}?\n\nThis will remove the student and all associated records. This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'save_student.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'student_id';
        idInput.value = studentId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
