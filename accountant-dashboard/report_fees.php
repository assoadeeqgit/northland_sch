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

// Fetch Payments
$query = "
    SELECT p.id, p.amount_paid, p.payment_date, p.payment_method,
           s.admission_number, u.first_name, u.last_name, c.class_name
    FROM payments p
    LEFT JOIN students s ON p.student_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE DATE(p.payment_date) BETWEEN ? AND ?
    ORDER BY p.payment_date DESC
";
$stmt = $db->prepare($query);
$stmt->execute([$start_date, $end_date]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_collected = array_sum(array_column($payments, 'amount_paid'));

include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="reports.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 5px; display: inline-block;">&larr; Back to Reports</a>
            <h1 class="page-title">Fee Collection Report</h1>
            <p style="color: var(--text-light);">From <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
        </div>
        <button onclick="window.print()" class="btn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color);"><i class="fas fa-print"></i> Print Report</button>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 30px;">
        <div class="stat-card blue">
            <span class="label">Total Collected</span>
            <span class="value">₦<?php echo number_format($total_collected, 2); ?></span>
        </div>
        <div class="stat-card green">
            <span class="label">Transactions</span>
            <span class="value"><?php echo count($payments); ?></span>
        </div>
    </div>

    <!-- Report Table -->
    <div class="table-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Date</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Receipt ID</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Student</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Class</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Method</th>
                    <th style="padding: 12px; text-align: right; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" style="padding: 20px; text-align: center; color: #6b7280;">No payments found for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;"><?php echo date('Y-m-d', strtotime($pay['payment_date'])); ?></td>
                            <td style="padding: 12px; font-family: monospace;">#<?php echo $pay['id']; ?></td>
                            <td style="padding: 12px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($pay['first_name'] . ' ' . $pay['last_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #9ca3af;"><?php echo htmlspecialchars($pay['admission_number']); ?></div>
                            </td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($pay['class_name'] ?? 'N/A'); ?></td>
                            <td style="padding: 12px; text-transform: capitalize;"><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: 600;">₦<?php echo number_format($pay['amount_paid'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
