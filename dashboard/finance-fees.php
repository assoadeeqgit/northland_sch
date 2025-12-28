<?php
require_once '../auth-check.php';

// Allow both admin and accountant to access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'accountant'])) {
    header('Location: ../login-form.php');
    exit();
}

// Remove form handling for admins - make it read-only
if ($_SESSION['user_type'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: finance-fees.php');
    exit();
}
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// --- HANDLE FORM SUBMISSION ---
$message = '';
$messageType = '';

// Add New Fee Structure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee_structure'])) {
    $class_id = $_POST['class_id'] ?? 0;
    $fee_type = $_POST['fee_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $academic_session_id = $_POST['academic_session_id'] ?? 0;
    $term_id = $_POST['term_id'] ?? 0;
    $due_date = $_POST['due_date'] ?? null;

    if ($class_id > 0 && !empty($fee_type) && $amount > 0 && $academic_session_id > 0 && $term_id > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO fee_structure (class_id, fee_type, amount, academic_session_id, term_id, due_date, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$class_id, $fee_type, $amount, $academic_session_id, $term_id, $due_date]);
            $message = "Fee structure created successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error creating fee structure: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "Please fill in all required fields.";
        $messageType = "warning";
    }
}

// Update Fee Structure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_fee_structure'])) {
    $id = $_POST['fee_id'] ?? 0;
    $class_id = $_POST['class_id'] ?? 0;
    $fee_type = $_POST['fee_type'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $academic_session_id = $_POST['academic_session_id'] ?? 0;
    $term_id = $_POST['term_id'] ?? 0;
    $due_date = $_POST['due_date'] ?? null;

    if ($id > 0 && $class_id > 0 && !empty($fee_type) && $amount > 0) {
        try {
            $stmt = $conn->prepare("UPDATE fee_structure SET class_id = ?, fee_type = ?, amount = ?, academic_session_id = ?, term_id = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$class_id, $fee_type, $amount, $academic_session_id, $term_id, $due_date, $id]);
            $message = "Fee structure updated successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error updating fee structure: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Delete (Soft Delete) Fee Structure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_fee_structure'])) {
    $id = $_POST['fee_id'] ?? 0;
    if ($id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE fee_structure SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Fee structure deleted successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error deleting fee structure: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// --- FETCH DATA ---

// Get current academic session and term (you can make this dynamic based on your logic)
$current_session_query = $conn->query("SELECT * FROM academic_sessions ORDER BY id DESC LIMIT 1");
$current_session = $current_session_query->fetch(PDO::FETCH_ASSOC);
$current_session_id = $current_session['id'] ?? 2; // Default to 2 if not found

$current_term_query = $conn->query("SELECT * FROM terms ORDER BY id DESC LIMIT 1");
$current_term = $current_term_query->fetch(PDO::FETCH_ASSOC);
$current_term_id = $current_term['id'] ?? 4; // Default to 4 if not found

// Fetch Fee Structures with related data and apply filters
$whereConditions = ["fs.is_active = 1"];
$params = [];

// Search filter
if (!empty($_GET['search'])) {
    $whereConditions[] = "(fs.fee_type LIKE ? OR c.class_name LIKE ?)";
    $searchTerm = "%" . $_GET['search'] . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Term filter
if (!empty($_GET['term'])) {
    $whereConditions[] = "fs.term_id = ?";
    $params[] = $_GET['term'];
}

$whereClause = implode(" AND ", $whereConditions);

$fee_structures_query = $conn->prepare("
    SELECT 
        fs.*,
        c.class_name,
        t.term_name,
        acs.session_name,
        (SELECT COUNT(*) FROM students s WHERE s.class_id = fs.class_id) as student_count
    FROM fee_structure fs
    LEFT JOIN classes c ON fs.class_id = c.id
    LEFT JOIN terms t ON fs.term_id = t.id
    LEFT JOIN academic_sessions acs ON fs.academic_session_id = acs.id
    WHERE $whereClause
    ORDER BY fs.created_at DESC
");
$fee_structures_query->execute($params);
$fee_structures = $fee_structures_query->fetchAll(PDO::FETCH_ASSOC);

// Calculate Statistics
// Total Assigned Structures
$total_structures = count($fee_structures);

// Total Expected (for ALL active fee structures)
$total_expected = 0;
foreach ($fee_structures as $structure) {
    $total_expected += $structure['amount'] * $structure['student_count'];
}

// Total Collected (for current session)
$collected_query = $conn->prepare("SELECT SUM(amount_paid) as total FROM payments p INNER JOIN students s ON p.student_id = s.id WHERE YEAR(p.payment_date) = YEAR(CURDATE())");
$collected_query->execute();
$total_collected = $collected_query->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Collection Percentage
$collection_percentage = $total_expected > 0 ? round(($total_collected / $total_expected) * 100, 1) : 0;

// Count classes with assigned structures
$classes_with_fees = array_unique(array_column($fee_structures, 'class_id'));
$classes_count = count($classes_with_fees);

// Fetch all classes for dropdown
$classes_query = $conn->query("SELECT * FROM classes ORDER BY class_name ASC");
$all_classes = $classes_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch all academic sessions
$sessions_query = $conn->query("SELECT * FROM academic_sessions ORDER BY id DESC");
$all_sessions = $sessions_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch all terms
$terms_query = $conn->query("SELECT * FROM terms ORDER BY id ASC");
$all_terms = $terms_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Collection Report - Northland Schools</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'sidebar.php'; ?>

<main class="main-content min-h-screen">
    <div class="content-body p-6">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> mb-6 p-4 rounded-lg <?php echo $messageType == 'success' ? 'bg-green-50 text-green-800 border border-green-200' : ($messageType == 'warning' ? 'bg-yellow-50 text-yellow-800 border border-yellow-200' : 'bg-red-50 text-red-800 border border-red-200'); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="page-title-box flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Fee Management</h1>
                <p class="text-gray-600 mt-1">Configure fee structures and manage school fees for <strong class="text-blue-900">Northland Schools Kano</strong>.</p>
            </div>
            <div class="flex gap-3">
                <button class="btn bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-export mr-2"></i> Export
                </button>
                <button onclick="document.getElementById('addFeeModal').style.display='block'" class="btn bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Create New Structure
                </button>
            </div>
        </div>

        <!-- Fee Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Total Expected (All Active)</div>
                <div class="text-2xl font-bold text-gray-900 mb-1">₦<?php echo number_format($total_expected, 2); ?></div>
                <div class="text-sm text-gray-500">Based on enrolled students</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Assigned Structures</div>
                <div class="text-2xl font-bold text-gray-900 mb-1"><?php echo $total_structures; ?></div>
                <div class="text-sm text-gray-500">Across <?php echo $classes_count; ?> Classes</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Total Collected</div>
                <div class="text-2xl font-bold text-gray-900 mb-1">₦<?php echo number_format($total_collected, 2); ?></div>
                <div class="text-sm text-green-600"><?php echo $collection_percentage; ?>% of Target</div>
            </div>
        </div>

        <!-- Filter Section -->
        <form method="GET" class="bg-white p-6 rounded-lg shadow-sm border mb-8">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Filter Fee Structures</h4>
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input type="text" name="search" placeholder="Search by fee type or class..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="min-w-40">
                    <select name="term" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Terms</option>
                        <?php foreach ($all_terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>" <?php echo (($_GET['term'] ?? '') == $term['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($term['term_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Filter</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['term'])): ?>
                    <a href="finance-fees.php" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Content Area -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Fee Structures (<?php echo $current_session['session_name'] ?? '2024-2025'; ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($fee_structures)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">No fee structures found. Create your first one!</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($fee_structures as $structure): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($structure['fee_type']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($structure['class_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($structure['term_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4"><span class="font-bold text-gray-900">₦<?php echo number_format($structure['amount'], 2); ?></span></td>
                                <td class="px-6 py-4 text-gray-700"><?php echo $structure['student_count']; ?></td>
                                <td class="px-6 py-4 text-gray-700"><?php echo $structure['due_date'] ? date('M d, Y', strtotime($structure['due_date'])) : '-'; ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editFeeStructure(<?php echo htmlspecialchars(json_encode($structure)); ?>)" class="text-blue-600 hover:text-blue-800 p-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteFeeStructure(<?php echo $structure['id']; ?>, '<?php echo htmlspecialchars($structure['fee_type']); ?>')" class="text-red-600 hover:text-red-800 p-1" title="Delete">
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
</main>

<!-- Add Fee Structure Modal -->
<div id="addFeeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900">Create New Fee Structure</h2>
                <button onclick="document.getElementById('addFeeModal').style.display='none'" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="add_fee_structure" value="1">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Fee Type *</label>
                <input type="text" name="fee_type" required placeholder="e.g., Tuition, Development Levy, Exam Fee" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class *</label>
                    <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Class</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₦) *</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Academic Session *</label>
                    <select name="academic_session_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Session</option>
                        <?php foreach ($all_sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" <?php echo $session['id'] == $current_session_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['session_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Term *</label>
                    <select name="term_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Term</option>
                        <?php foreach ($all_terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>" <?php echo $term['id'] == $current_term_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($term['term_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Due Date (Optional)</label>
                <input type="date" name="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('addFeeModal').style.display='none'" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Fee Structure</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Fee Structure Modal -->
<div id="editFeeModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 25px; border: 1px solid #888; width: 600px; border-radius: 8px; max-height: 90vh; overflow-y: auto;">
        <span onclick="document.getElementById('editFeeModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2 style="margin-top: 0; color: var(--brand-navy); margin-bottom: 20px;">Edit Fee Structure</h2>
        
        <form method="POST" id="editFeeForm">
            <input type="hidden" name="update_fee_structure" value="1">
            <input type="hidden" name="fee_id" id="edit_fee_id">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Fee Type *</label>
                <input type="text" name="fee_type" id="edit_fee_type" required placeholder="e.g., Tuition" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </main>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Class *</label>
                    <select name="class_id" id="edit_class_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Class</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </main>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Amount (₦) *</label>
                    <input type="number" name="amount" id="edit_amount" step="0.01" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </main>
            </main>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Academic Session *</label>
                    <select name="academic_session_id" id="edit_session_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Session</option>
                        <?php foreach ($all_sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>"><?php echo htmlspecialchars($session['session_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </main>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Term *</label>
                    <select name="term_id" id="edit_term_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Term</option>
                        <?php foreach ($all_terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>"><?php echo htmlspecialchars($term['term_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </main>
            </main>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Due Date (Optional)</label>
                <input type="date" name="due_date" id="edit_due_date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </main>

            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('editFeeModal').style.display='none'" class="btn" style="margin-right: 10px; border: 1px solid #ddd;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Update Fee Structure</button>
            </main>
        </form>
    </main>
</main>

<!-- Delete Confirmation Form (Hidden) -->
<form method="POST" id="deleteFeeForm" style="display: none;">
    <input type="hidden" name="delete_fee_structure" value="1">
    <input type="hidden" name="fee_id" id="delete_fee_id">
</form>

<style>
/* Custom SweetAlert Button Styles */
.swal2-styled.btn {
    padding: 10px 24px !important;
    font-size: 14px !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
    margin: 0 8px !important;
    transition: all 0.3s ease !important;
}

.swal2-styled.btn-danger {
    background-color: #dc3545 !important;
    border: 1px solid #dc3545 !important;
    color: white !important;
}

.swal2-styled.btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #bd2130 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3) !important;
}

.swal2-styled.btn-secondary {
    background-color: #6c757d !important;
    border: 1px solid #6c757d !important;
    color: white !important;
}

.swal2-styled.btn-secondary:hover {
    background-color: #5a6268 !important;
    border-color: #545b62 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3) !important;
}

.swal2-icon.swal2-warning {
    border-color: #ffc107 !important;
    color: #ffc107 !important;
}

.swal2-popup {
    border-radius: 12px !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
}

.swal2-title {
    color: var(--brand-navy, #24252b) !important;
    font-weight: 600 !important;
}
</style>

<script>
function editFeeStructure(structure) {
    document.getElementById('edit_fee_id').value = structure.id;
    document.getElementById('edit_fee_type').value = structure.fee_type;
    document.getElementById('edit_class_id').value = structure.class_id;
    document.getElementById('edit_amount').value = structure.amount;
    document.getElementById('edit_session_id').value = structure.academic_session_id;
    document.getElementById('edit_term_id').value = structure.term_id;
    document.getElementById('edit_due_date').value = structure.due_date || '';
    document.getElementById('editFeeModal').style.display = 'block';
}

function deleteFeeStructure(id, feeName) {
    Swal.fire({
        title: 'Delete Fee Structure?',
        html: `Are you sure you want to delete <strong>"${feeName}"</strong>?<br><small>This action cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        reverseButtons: true,
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit the form
            document.getElementById('delete_fee_id').value = id;
            document.getElementById('deleteFeeForm').submit();
        }
    });
}
</script>

</main>
</main>


</body>
</html>
