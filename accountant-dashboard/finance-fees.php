<?php
require_once 'auth-check.php'; // Allows both admin and accountant
include '../includes/header.php';
require_once '../config/DatabaseManager.php';

$dbManager = DatabaseManager::getInstance();
$conn = $dbManager->getConnection();

// Get date range from query parameters or default to current year
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

// 1. Total Fees Collected
$stmt = $conn->prepare("
    SELECT SUM(amount_paid) as total 
    FROM payments 
    WHERE payment_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_collected = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Total Transactions
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM payments 
    WHERE payment_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 3. Breakdown by Fee Type (Grouped by fee_type in fee_structure)
// Note: payments -> fee_structure (id) -> fee_type
$stmt = $conn->prepare("
    SELECT fs.fee_type, SUM(p.amount_paid) as amount
    FROM payments p
    JOIN fee_structure fs ON p.fee_structure_id = fs.id
    WHERE p.payment_date BETWEEN ? AND ?
    GROUP BY fs.fee_type
    ORDER BY amount DESC
");
$stmt->execute([$start_date, $end_date]);
$fees_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Breakdown by Class Level (Early Childhood, Primary, Secondary)
// payments -> student (id) -> class (id) -> class_level
$stmt = $conn->prepare("
    SELECT c.class_level, SUM(p.amount_paid) as amount
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    WHERE p.payment_date BETWEEN ? AND ?
    GROUP BY c.class_level
    ORDER BY amount DESC
");
$stmt->execute([$start_date, $end_date]);
$fees_by_level = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box" style="margin-bottom: 30px;">
        <h1 class="page-title">Fee Collection Report</h1>
        <p style="color: var(--text-light); margin-top: 5px;">Breakdown of fees collected from <strong><?php echo date('M d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M d, Y', strtotime($end_date)); ?></strong></p>
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
            <a href="reports.php" class="btn" style="border: 1px solid #e5e7eb; margin-left: 10px;">Back to Reports</a>
        </div>
    </form>

    <!-- Stats Grid -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card blue" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Total Collected</span>
            <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: #3b82f6; margin-bottom: 5px;">₦<?= number_format($total_collected, 2) ?></span>
        </div>
        <div class="stat-card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Transactions</span>
            <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: #374151; margin-bottom: 5px;"><?= $total_transactions ?></span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
        
        <!-- Breakdown by Fee Type -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e5e7eb;">By Fee Type</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <?php if (empty($fees_by_type)): ?>
                        <tr><td colspan="2" style="padding: 15px; text-align: center; color: #6b7280;">No data found</td></tr>
                    <?php else: ?>
                        <?php foreach ($fees_by_type as $fee): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px 0; color: #4b5563;"><?= htmlspecialchars($fee['fee_type']) ?></td>
                            <td style="padding: 12px 0; text-align: right; font-weight: 600;">₦<?= number_format($fee['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Breakdown by Level -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e5e7eb;">By Class Level</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <?php if (empty($fees_by_level)): ?>
                        <tr><td colspan="2" style="padding: 15px; text-align: center; color: #6b7280;">No data found</td></tr>
                    <?php else: ?>
                        <?php foreach ($fees_by_level as $level): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px 0; color: #4b5563;"><?= htmlspecialchars($level['class_level']) ?></td>
                            <td style="padding: 12px 0; text-align: right; font-weight: 600;">₦<?= number_format($level['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>
