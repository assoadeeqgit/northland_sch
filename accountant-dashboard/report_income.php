<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../auth-check.php';
require_once '../config/database.php';

// Allow both admin and accountant access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'accountant'])) {
    header('Location: ../login-form.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Date Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Calculate Income (Payments)
$stmt = $db->prepare("SELECT SUM(amount_paid) as total_income FROM payments WHERE DATE(payment_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_income = $stmt->fetch(PDO::FETCH_ASSOC)['total_income'] ?? 0;

// Calculate Expenses
$stmt = $db->prepare("SELECT SUM(amount) as total_expense FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_expense = $stmt->fetch(PDO::FETCH_ASSOC)['total_expense'] ?? 0;

$net_income = $total_income - $total_expense;
$status_color = $net_income >= 0 ? 'text-success' : 'text-danger';

// Fetch Expense Breakdown
$stmt = $db->prepare("SELECT category, SUM(amount) as cat_total FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ? GROUP BY category");
$stmt->execute([$start_date, $end_date]);
$expenses_by_cat = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="reports.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 5px; display: inline-block;">&larr; Back to Reports</a>
            <h1 class="page-title">Income Statement</h1>
             <p style="color: var(--text-light);">From <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
        </div>
        <button onclick="window.print()" class="btn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color);"><i class="fas fa-print"></i> Print Report</button>
    </div>

    <!-- P/L Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
        <div class="stat-card green">
            <span class="label">Total Revenue</span>
            <span class="value text-success">₦<?php echo number_format($total_income, 2); ?></span>
        </div>
        <div class="stat-card orange">
            <span class="label">Total Expenses</span>
            <span class="value text-danger">₦<?php echo number_format($total_expense, 2); ?></span>
        </div>
        <div class="stat-card blue">
            <span class="label">Net Income</span>
            <span class="value <?php echo $status_color; ?>">₦<?php echo number_format($net_income, 2); ?></span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <!-- Summary Table -->
        <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color);">
             <h3 style="margin-bottom: 20px; font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Financial Summary</h3>
             <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                 <span style="font-weight: 600;">Total Revenue</span>
                 <span style="font-weight: 600;">₦<?php echo number_format($total_income, 2); ?></span>
             </div>
             <div style="display: flex; justify-content: space-between; margin-bottom: 15px; color: #dc2626;">
                 <span>Less: Total Expenses</span>
                 <span>(₦<?php echo number_format($total_expense, 2); ?>)</span>
             </div>
             <hr style="border: 0; border-top: 1px dashed #ccc; margin: 15px 0;">
             <div style="display: flex; justify-content: space-between; font-size: 1.2rem;">
                 <strong style="color: var(--brand-navy);">Net Profit / (Loss)</strong>
                 <strong class="<?php echo $status_color; ?>">₦<?php echo number_format($net_income, 2); ?></strong>
             </div>
        </div>

        <!-- Expense Breakdown -->
        <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 20px; font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px;">Expense Breakdown</h3>
            <?php if (empty($expenses_by_cat)): ?>
                <p style="color: #888;">No expenses recorded for this period.</p>
            <?php else: ?>
                <table style="width: 100%;">
                <?php foreach ($expenses_by_cat as $cat): ?>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;"><?php echo htmlspecialchars($cat['category']); ?></td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; text-align: right; font-weight: 600;">₦<?php echo number_format($cat['cat_total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>
