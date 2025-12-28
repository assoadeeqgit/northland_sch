<?php
require_once 'auth-check.php'; // Allows both admin and accountant 

include '../includes/header.php';
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


    <div class="content-body" style="padding: 30px;">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: <?php echo $messageType == 'success' ? '#d4edda' : ($messageType == 'warning' ? '#fff3cd' : '#f8d7da'); ?>; color: <?php echo $messageType == 'success' ? '#155724' : ($messageType == 'warning' ? '#856404' : '#721c24'); ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="page-title-box" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 class="page-title">Fee Management</h1>
                <p style="color: var(--text-light); margin-top: 5px;">Configure fee structures and manage school fees for <strong style="color: var(--brand-navy);">Northland Schools Kano</strong>.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color);"><i class="fas fa-file-export" style="margin-right:8px;"></i> Export</button>
                <button onclick="document.getElementById('addFeeModal').style.display='block'" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Create New Structure</button>
            </div>
        </div>

        <!-- Fee Stats -->
        <div class="stats-grid" style="padding: 0 0 30px 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="stat-card blue" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: var(--text-light); font-size: 0.9rem;">Total Expected (All Active)</span>
                <span class="value" style="display: block; font-size: 1.8rem; font-weight: bold; margin: 10px 0;">₦<?php echo number_format($total_expected, 2); ?></span>
                <span class="trend" style="font-size: 0.85rem; color: #666;">Based on enrolled students</span>
            </div>
            <div class="stat-card orange" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: var(--text-light); font-size: 0.9rem;">Assigned Structures</span>
                <span class="value" style="display: block; font-size: 1.8rem; font-weight: bold; margin: 10px 0;"><?php echo $total_structures; ?></span>
                <span class="trend" style="font-size: 0.85rem;">Across <?php echo $classes_count; ?> Classes</span>
            </div>
            <div class="stat-card green" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: var(--text-light); font-size: 0.9rem;">Total Collected</span>
                <span class="value" style="display: block; font-size: 1.8rem; font-weight: bold; margin: 10px 0;">₦<?php echo number_format($total_collected, 2); ?></span>
                <span class="trend text-success" style="font-size: 0.85rem;"><?php echo $collection_percentage; ?>% of Target</span>
            </div>
        </div>

        <!-- Filter Section -->
        <form method="GET" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 15px; color: var(--brand-navy); font-size: 1.1rem; font-weight: 600;">Filter Fee Structures</h4>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 250px;">
                    <input type="text" name="search" placeholder="Search by fee type or class..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <select name="term" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color); font-size: 0.95rem;">
                        <option value="">All Terms</option>
                        <?php foreach ($all_terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>" <?php echo (($_GET['term'] ?? '') == $term['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($term['term_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Filter</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['term'])): ?>
                    <a href="fees.php" class="btn" style="padding: 12px 24px; border: 1px solid var(--border-color); text-decoration: none;">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Content Area -->
        <div class="table-container" style="margin: 0; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px;">
            <div class="table-header" style="margin-bottom: 20px;">
                <h3>Fee Structures (<?php echo $current_session['session_name'] ?? '2024-2025'; ?>)</h3>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; text-align: left;">
                        <th style="padding: 12px;">Fee Type</th>
                        <th style="padding: 12px;">Class</th>
                        <th style="padding: 12px;">Term</th>
                        <th style="padding: 12px;">Amount</th>
                        <th style="padding: 12px;">Students</th>
                        <th style="padding: 12px;">Due Date</th>
                        <th style="padding: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fee_structures)): ?>
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #666;">No fee structures found. Create your first one!</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($fee_structures as $structure): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($structure['fee_type']); ?></div>
                            </td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($structure['class_name'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($structure['term_name'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px;"><span style="font-weight: 700; color: var(--text-color);">₦<?php echo number_format($structure['amount'], 2); ?></span></td>
                            <td style="padding: 12px;"><?php echo $structure['student_count']; ?></td>
                            <td style="padding: 12px;"><?php echo $structure['due_date'] ? date('M d, Y', strtotime($structure['due_date'])) : '-'; ?></td>
                            <td style="padding: 12px;">
                                <button onclick="editFeeStructure(<?php echo htmlspecialchars(json_encode($structure)); ?>)" class="btn" style="padding: 6px; color: var(--brand-navy); margin-right: 5px;" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteFeeStructure(<?php echo $structure['id']; ?>, '<?php echo htmlspecialchars($structure['fee_type']); ?>')" class="btn" style="padding: 6px; color: #dc3545;" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Add Fee Structure Modal -->
<div id="addFeeModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 25px; border: 1px solid #888; width: 600px; border-radius: 8px; max-height: 90vh; overflow-y: auto;">
        <span onclick="document.getElementById('addFeeModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2 style="margin-top: 0; color: var(--brand-navy); margin-bottom: 20px;">Create New Fee Structure</h2>
        
        <form method="POST">
            <input type="hidden" name="add_fee_structure" value="1">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Fee Type *</label>
                <input type="text" name="fee_type" required placeholder="e.g., Tuition, Development Levy, Exam Fee" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Class *</label>
                    <select name="class_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Class</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Amount (₦) *</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Academic Session *</label>
                    <select name="academic_session_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Session</option>
                        <?php foreach ($all_sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" <?php echo $session['id'] == $current_session_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['session_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Term *</label>
                    <select name="term_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Term</option>
                        <?php foreach ($all_terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>" <?php echo $term['id'] == $current_term_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($term['term_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Due Date (Optional)</label>
                <input type="date" name="due_date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('addFeeModal').style.display='none'" class="btn" style="margin-right: 10px; border: 1px solid #ddd;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Create Fee Structure</button>
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
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Class *</label>
                    <select name="class_id" id="edit_class_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Class</option>
                        <?php foreach ($all_classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Amount (₦) *</label>
                    <input type="number" name="amount" id="edit_amount" step="0.01" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Academic Session *</label>
                    <select name="academic_session_id" id="edit_session_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Session</option>
                        <?php foreach ($all_sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>"><?php echo htmlspecialchars($session['session_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Term *</label>
                    <select name="term_id" id="edit_term_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Select Term</option>
                        <?php foreach ($all_terms as $term): ?>
                            <option value="<?php echo $term['id']; ?>"><?php echo htmlspecialchars($term['term_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Due Date (Optional)</label>
                <input type="date" name="due_date" id="edit_due_date" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('editFeeModal').style.display='none'" class="btn" style="margin-right: 10px; border: 1px solid #ddd;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Update Fee Structure</button>
            </div>
        </form>
    </div>
</div>

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

<?php include '../includes/footer.php'; ?>
