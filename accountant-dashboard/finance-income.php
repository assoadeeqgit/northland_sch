<?php
require_once 'auth-check.php'; // Allows both admin and accountant
include '../includes/header.php';
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get date range from query parameters or default to current year
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Calculate Income (Revenue from payments)
$income_query = $conn->prepare("
    SELECT 
        SUM(amount_paid) as total_revenue,
        COUNT(*) as payment_count
    FROM payments 
    WHERE payment_date BETWEEN ? AND ?
");
$income_query->execute([$start_date, $end_date]);
$income_data = $income_query->fetch(PDO::FETCH_ASSOC);
$total_revenue = $income_data['total_revenue'] ?? 0;
$payment_count = $income_data['payment_count'] ?? 0;

// Calculate Expenses
$expense_query = $conn->prepare("
    SELECT 
        SUM(amount) as total_expenses,
        COUNT(*) as expense_count
    FROM expenses 
    WHERE expense_date BETWEEN ? AND ?
");
$expense_query->execute([$start_date, $end_date]);
$expense_data = $expense_query->fetch(PDO::FETCH_ASSOC);
$total_expenses = $expense_data['total_expenses'] ?? 0;
$expense_count = $expense_data['expense_count'] ?? 0;

// Calculate Net Income
$net_income = $total_revenue - $total_expenses;

// Get Revenue Breakdown by Month
$monthly_revenue = $conn->prepare("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount_paid) as amount
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month
");
$monthly_revenue->execute([$start_date, $end_date]);
$revenue_by_month = $monthly_revenue->fetchAll(PDO::FETCH_ASSOC);

// Get Expense Breakdown by Category
$expense_by_category = $conn->prepare("
    SELECT 
        category,
        SUM(amount) as amount,
        COUNT(*) as count
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
    GROUP BY category
    ORDER BY amount DESC
");
$expense_by_category->execute([$start_date, $end_date]);
$expenses_by_cat = $expense_by_category->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 30px;">
        <h1 class="page-title">Income Statement</h1>
        <p style="color: var(--text-light); margin-top: 5px;">Profit and Loss Statement for <strong style="color: var(--brand-navy);">Northland Schools Kano</strong></p>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label style="display: block; font-weight: 500; margin-bottom: 8px;">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
            </div>
            <div style="flex: 1;">
                <label style="display: block; font-weight: 500; margin-bottom: 8px;">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="?" class="btn" style="border: 1px solid #e5e7eb;">Reset</a>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card green" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Total Revenue</span>
            <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: #10b981; margin-bottom: 5px;">₦<?= number_format($total_revenue, 2) ?></span>
            <span class="trend" style="font-size: 0.85rem; color: #6b7280;"><?= $payment_count ?> transactions</span>
        </div>
        
        <div class="stat-card orange" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Total Expenses</span>
            <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: #f59e0b; margin-bottom: 5px;">₦<?= number_format($total_expenses, 2) ?></span>
            <span class="trend" style="font-size: 0.85rem; color: #6b7280;"><?= $expense_count ?> expenses</span>
        </div>
        
        <div class="stat-card <?= $net_income >= 0 ? 'blue' : '' ?>" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Net Income</span>
            <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: <?= $net_income >= 0 ? '#3b82f6' : '#ef4444' ?>; margin-bottom: 5px;">₦<?= number_format($net_income, 2) ?></span>
            <span class="trend" style="font-size: 0.85rem; color: <?= $net_income >= 0 ? '#10b981' : '#ef4444' ?>;">
                <i class="fas fa-<?= $net_income >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                <?= $net_income >= 0 ? 'Profit' : 'Loss' ?>
            </span>
        </div>
    </div>

    <!-- Detailed Income Statement -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h3 style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e5e7eb;">Income Statement Detail</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 15px 0; font-weight: 600; color: #10b981;">Revenue</td>
                    <td style="padding: 15px 0; text-align: right; font-weight: 600; color: #10b981;">₦<?= number_format($total_revenue, 2) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 15px 0 15px 30px; color: #6b7280;">Fee Payments</td>
                    <td style="padding: 15px 0; text-align: right;">₦<?= number_format($total_revenue, 2) ?></td>
                </tr>
                
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <td style="padding: 20px 0; font-weight: 600; color: #f59e0b;">Less: Expenses</td>
                    <td style="padding: 20px 0; text-align: right; font-weight: 600; color: #f59e0b;">₦<?= number_format($total_expenses, 2) ?></td>
                </tr>
                
                <?php foreach ($expenses_by_cat as $expense): ?>
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 10px 0 10px 30px; color: #6b7280;"><?= htmlspecialchars($expense['category']) ?></td>
                    <td style="padding: 10px 0; text-align: right; color: #6b7280;">₦<?= number_format($expense['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr style="background: <?= $net_income >= 0 ? '#f0fdf4' : '#fef2f2' ?>;">
                    <td style="padding: 20px 0; font-weight: 700; font-size: 1.1rem; color: <?= $net_income >= 0 ? '#10b981' : '#ef4444' ?>;">Net Income (<?= $net_income >= 0 ? 'Profit' : 'Loss' ?>)</td>
                    <td style="padding: 20px 0; text-align: right; font-weight: 700; font-size: 1.1rem; color: <?= $net_income >= 0 ? '#10b981' : '#ef4444' ?>;">₦<?= number_format($net_income, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Monthly Revenue Trend -->
    <?php if (!empty($revenue_by_month)): ?>
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 20px;">Monthly Revenue Trend</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left;">Month</th>
                    <th style="padding: 12px; text-align: right;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue_by_month as $month_data): ?>
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 12px;"><?= date('F Y', strtotime($month_data['month'] . '-01')) ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: 600;">₦<?= number_format($month_data['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>
