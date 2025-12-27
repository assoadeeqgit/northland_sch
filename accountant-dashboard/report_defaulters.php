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

// Prepare Defaulters Logic
// 1. Calculate Total Expected Fees per Class (Sum of all fee types for the class)
$class_fees_query = "
    SELECT class_id, SUM(amount) as total_expected 
    FROM fee_structure 
    GROUP BY class_id
";

// 2. Calculate Total Paid per Student
$payments_query = "
    SELECT student_id, SUM(amount_paid) as total_paid_so_far
    FROM payments
    GROUP BY student_id
";

// 3. Main Query: Join Students with Fees and Payments
$query = "
    SELECT s.id, s.admission_number, u.first_name, u.last_name, c.class_name,
           COALESCE(cf.total_expected, 0) as expected_fee,
           COALESCE(p.total_paid_so_far, 0) as total_paid
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN ($class_fees_query) cf ON s.class_id = cf.class_id
    LEFT JOIN ($payments_query) p ON s.id = p.student_id
    WHERE (COALESCE(cf.total_expected, 0) - COALESCE(p.total_paid_so_far, 0)) > 0
    ORDER BY (COALESCE(cf.total_expected, 0) - COALESCE(p.total_paid_so_far, 0)) DESC
";

$stmt = $db->query($query);
$defaulters = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_outstanding = 0;
foreach ($defaulters as $d) {
    $total_outstanding += ($d['expected_fee'] - $d['total_paid']);
}

include '../includes/header.php'; 
?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="reports.php" style="text-decoration: none; color: var(--text-light); font-size: 0.9rem; margin-bottom: 5px; display: inline-block;">&larr; Back to Reports</a>
            <h1 class="page-title">Fee Defaulters List</h1>
            <p style="color: var(--text-light);">Students with outstanding fee balances.</p>
        </div>
        <button onclick="window.print()" class="btn" style="background: white; border: 1px solid var(--border-color); color: var(--text-color);"><i class="fas fa-print"></i> Print list</button>
    </div>

    <!-- Summary -->
    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 30px;">
        <div class="stat-card orange">
            <span class="label">Total Outstanding Amount</span>
            <span class="value text-danger">₦<?php echo number_format($total_outstanding, 2); ?></span>
            <span class="trend">Needed for Operations</span>
        </div>
        <div class="stat-card blue">
            <span class="label">Students Owing</span>
            <span class="value"><?php echo count($defaulters); ?></span>
        </div>
    </div>

    <!-- Defaulters Table -->
    <div class="table-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Student</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Class</th>
                    <th style="padding: 12px; text-align: right; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Expected</th>
                    <th style="padding: 12px; text-align: right; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Paid</th>
                    <th style="padding: 12px; text-align: right; font-size: 0.85rem; color: #bc2828; font-weight: 600;">Balance Due</th>
                    <th style="padding: 12px; text-align: center; font-size: 0.85rem; color: #6b7280; font-weight: 600;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($defaulters)): ?>
                    <tr><td colspan="6" style="padding: 20px; text-align: center; color: #2e7d32; font-weight: 600;">🎉 Amazing! Zero defaulters.</td></tr>
                <?php else: ?>
                    <?php foreach ($defaulters as $row): 
                        $balance = $row['expected_fee'] - $row['total_paid'];
                    ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #9ca3af;"><?php echo htmlspecialchars($row['admission_number']); ?></div>
                            </td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($row['class_name']); ?></td>
                            <td style="padding: 12px; text-align: right;">₦<?php echo number_format($row['expected_fee'], 2); ?></td>
                            <td style="padding: 12px; text-align: right; color: #2e7d32;">₦<?php echo number_format($row['total_paid'], 2); ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: 700; color: #bc2828;">₦<?php echo number_format($balance, 2); ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <a href="students.php?search=<?php echo urlencode($row['admission_number']); ?>" class="btn" style="padding: 4px 8px; background: #eef2ff; color: var(--brand-navy); font-size: 0.8rem;">View Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
