<?php
require_once '../auth-check.php';
checkAuth('accountant'); // Finance management is for accountants only 

include '../includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// --- HANDLE FORM SUBMISSION ---
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'other';
    $amount = $_POST['amount'] ?? 0;
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $status = 'pending'; // Default status

    if (!empty($description) && $amount > 0) {
        try {
            $stmt = $conn->prepare("INSERT INTO expenses (description, category, amount, expense_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            // Assuming user_id is in session, default to 1 (admin) if not
            $user_id = $_SESSION['user_id'] ?? 1;
            $stmt->execute([$description, $category, $amount, $expense_date, $status, $user_id]);
            $message = "Expense recorded successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error recording expense: " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "Please fill in all required fields.";
        $messageType = "warning";
    }
}

// --- FETCH EXPENSES ---
// Simple filter logic
$whereCli = "1=1";
$params = [];

if (!empty($_GET['search'])) {
    $whereCli .= " AND description LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}
if (!empty($_GET['category'])) {
    $whereCli .= " AND category = ?";
    $params[] = $_GET['category'];
}

$stmt = $conn->prepare("SELECT * FROM expenses WHERE $whereCli ORDER BY expense_date DESC LIMIT 50");
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats for Top Cards (Dynamic)
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

// Total Expenses (Month)
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$current_month_start, $current_month_end]);
$total_expenses_month = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Pending Approvals
$stmt = $conn->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

?>

        <!-- Page Content -->
        <div class="content-body" style="padding: 30px;">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: <?php echo $messageType == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $messageType == 'success' ? '#155724' : '#721c24'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="page-title-box" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 class="page-title">Expense Tracking</h1>
                <p style="color: var(--text-light); margin-top: 5px;">Monitor school expenditures and operational costs.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color);"><i class="fas fa-file-export" style="margin-right:8px;"></i> Export</button>
                <button onclick="document.getElementById('addExpenseModal').style.display='block'" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Record Expense</button>
            </div>
        </div>

        <!-- Expense Stats -->
        <div class="stats-grid" style="padding: 0 0 30px 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="stat-card blue" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: var(--text-light); font-size: 0.9rem;">Total Expenses (Month)</span>
                <span class="value" style="display: block; font-size: 1.8rem; font-weight: bold; margin: 10px 0;">₦<?php echo number_format($total_expenses_month, 2); ?></span>
            </div>
            <div class="stat-card orange" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: var(--text-light); font-size: 0.9rem;">Pending Approvals</span>
                <span class="value" style="display: block; font-size: 1.8rem; font-weight: bold; margin: 10px 0;"><?php echo $pending_count; ?></span>
                <span class="trend text-warning" style="font-size: 0.85rem;">Requires Attention</span>
            </div>
             <div class="stat-card green" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: var(--text-light); font-size: 0.9rem;">Budget Status</span>
                <span class="value" style="display: block; font-size: 1.8rem; font-weight: bold; margin: 10px 0;">85%</span>
                <span class="trend text-success" style="font-size: 0.85rem;">Within Limit</span>
            </div>
        </div>

        <!-- Controls -->
        <form method="GET" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid var(--border-color);">
            <h4 style="margin-bottom: 15px; color: var(--brand-navy);">Filter Expenses</h4>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 250px;">
                    <input type="text" name="search" placeholder="Search description..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <select name="category" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color);">
                        <option value="">All Categories</option>
                        <option value="salary" <?php echo (($_GET['category'] ?? '') == 'salary') ? 'selected' : ''; ?>>Staff Salary</option>
                        <option value="utilities" <?php echo (($_GET['category'] ?? '') == 'utilities') ? 'selected' : ''; ?>>Utilities</option>
                        <option value="maintenance" <?php echo (($_GET['category'] ?? '') == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="supplies" <?php echo (($_GET['category'] ?? '') == 'supplies') ? 'selected' : ''; ?>>Supplies</option>
                         <option value="other" <?php echo (($_GET['category'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <!-- Expenses Table -->
        <div class="table-container" style="margin: 0; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px;">
            <div class="table-header" style="margin-bottom: 20px;">
                <h3>Expenditure Log</h3>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee; text-align: left;">
                        <th style="padding: 12px;">Date</th>
                        <th style="padding: 12px;">Description</th>
                        <th style="padding: 12px;">Category</th>
                        <th style="padding: 12px;">Amount</th>
                        <th style="padding: 12px;">Status</th>
                        <th style="padding: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center; color: #666;">No expenses found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $expense): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px;"><span style="color: var(--text-light); font-weight: 500;"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></span></td>
                            <td style="padding: 12px;">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($expense['description']); ?></div>
                            </td>
                            <td style="padding: 12px; text-transform: capitalize;"><?php echo htmlspecialchars($expense['category']); ?></td>
                            <td style="padding: 12px;"><span style="font-weight: 700; color: var(--text-color);">₦<?php echo number_format($expense['amount'], 2); ?></span></td>
                            <td style="padding: 12px;">
                                <?php 
                                    $statusColor = 'warning';
                                    if ($expense['status'] == 'paid' || $expense['status'] == 'approved') $statusColor = 'success';
                                    if ($expense['status'] == 'rejected') $statusColor = 'danger';
                                ?>
                                <span class="text-<?php echo $statusColor; ?>" style="background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize;"><?php echo htmlspecialchars($expense['status']); ?></span>
                            </td>
                            <td style="padding: 12px;">
                                <button class="btn" style="padding: 6px; color: var(--brand-navy);"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 500px; border-radius: 8px;">
        <span onclick="document.getElementById('addExpenseModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        <h2 style="margin-top: 0; color: var(--brand-navy); margin-bottom: 20px;">Record New Expense</h2>
        
        <form method="POST">
            <input type="hidden" name="add_expense" value="1">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                <input type="text" name="description" required placeholder="e.g., Office Supplies" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="other">Other</option>
                        <option value="salary">Staff Salary</option>
                        <option value="utilities">Utilities</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="supplies">Supplies</option>
                    </select>
                </div>
                <div>
                     <label style="display: block; margin-bottom: 5px; font-weight: 500;">Date</label>
                     <input type="date" name="expense_date" required value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Amount (₦)</label>
                <input type="number" name="amount" step="0.01" required placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('addExpenseModal').style.display='none'" class="btn" style="margin-right: 10px; border: 1px solid #ddd;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Save Expense</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
