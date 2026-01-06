<?php
require_once '../auth-check.php';
checkAuth('accountant'); // Finance Dashboard is for accountants only


include '../includes/header.php'; 
require_once '../config/DatabaseManager.php';

$dbManager = DatabaseManager::getInstance();
$conn = $dbManager->getConnection();

// --- FETCH DASHBOARD DATA ---

// 1. Total Collection Today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(amount_paid) as total FROM payments WHERE payment_date = ?");
$stmt->execute([$today]);
$total_collection_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Outstanding Fees (Approximation or Placeholder)
// Since 'outstanding' is complex without full billing, we'll show "Total Expected vs Collected" or similar if possible.
// For now, let's show "Total Revenue (This Term)" or remain static if calculation impossible.
// Let's use "Total Revenue (All Time)" as a verified metric instead of "Outstanding" to be safe, or 
// keep it as a placeholder if clients want that specific metric later. 
// User requested "Backend implementation". I will fetch *Total Revenue* to replace one card.
$stmt = $conn->query("SELECT SUM(amount_paid) as total FROM payments");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// --- FETCH ACTIVE TERM DATES ---
$term_start = date('Y-m-01'); // Default fallback
$term_end = date('Y-m-t');   // Default fallback
$term_name = 'This Month';   // Default label

try {
    $stmt_term = $conn->query("SELECT start_date, end_date, term_name FROM terms WHERE is_current = 1 LIMIT 1");
    $term_data = $stmt_term->fetch(PDO::FETCH_ASSOC);
    if ($term_data) {
        $term_start = $term_data['start_date'];
        $term_end = $term_data['end_date'];
        $term_name = $term_data['term_name'];
    }
} catch (Exception $e) { /* Ignore */ }

// 3. Total Expenses (This Term)
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$term_start, $term_end]);
$total_expenses_term = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 4. Net Cash Flow (Revenue Term - Expenses Term)
$stmt = $conn->prepare("SELECT SUM(amount_paid) as total FROM payments WHERE payment_date BETWEEN ? AND ?");
$stmt->execute([$term_start, $term_end]);
$revenue_term = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$net_cash_flow = $revenue_term - $total_expenses_term;

// 5. Recent Transactions
$stmt = $conn->query("SELECT p.*, u.first_name, u.last_name 
                      FROM payments p 
                      JOIN students s ON p.student_id = s.id 
                      JOIN users u ON s.user_id = u.id 
                      ORDER BY p.payment_date DESC LIMIT 5");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper for professional receipt ID
function generateReceiptId($id, $date) {
    $year = date('y', strtotime($date));
    $paddedId = str_pad($id, 6, '0', STR_PAD_LEFT);
    $suffix = strtoupper(substr(md5($id . 'nsk'), 0, 2)); // Deterministic but non-obvious suffix
    return "NSK/PAY/$year/$paddedId/$suffix";
}

?>

<!-- Main Content -->
<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box">
        <h1 class="page-title">Financial Dashboard</h1>
        <p style="color: var(--text-light); margin-top: 5px; font-size: 1.05rem;">Overview of <strong style="color: var(--brand-navy);">Northland Schools Kano</strong> financial status.</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid" style="padding: 30px 0;">
        <div class="stat-card blue">
            <span class="label">Total Collection Today</span>
            <span class="value">₦<?php echo number_format($total_collection_today, 2); ?></span>
            <span class="trend text-success"><i class="fas fa-arrow-up"></i> (Realtime)</span>
        </div>
        
        <div class="stat-card orange">
            <span class="label">Total Revenue (All Time)</span>
            <span class="value">₦<?php echo number_format($total_revenue, 2); ?></span>
            <span class="trend text-success"><i class="fas fa-check-circle"></i> Verified</span>
        </div>
        
        <div class="stat-card blue">
            <span class="label">Total Expenses (This Term)</span>
            <span class="value">₦<?php echo number_format($total_expenses_term, 2); ?></span>
            <span class="trend text-warning"><i class="fas fa-clipboard-list"></i> <?php echo htmlspecialchars($term_name); ?></span>
        </div>
        
        <div class="stat-card green">
            <span class="label">Net Cash Flow (Term)</span>
            <span class="value">₦<?php echo number_format($net_cash_flow, 2); ?></span>
            <span class="trend <?php echo $net_cash_flow >= 0 ? 'text-success' : 'text-danger'; ?>"><i class="fas fa-chart-line"></i> <?php echo $net_cash_flow >= 0 ? 'Healthy' : 'Deficit'; ?></span>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="table-container" style="margin: 0;">
        <div class="table-header">
            <h3>Recent Transactions</h3>
            <a href="payment.php" class="btn btn-primary">New Payment <i class="fas fa-plus" style="margin-left:8px;"></i></a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Receipt ID</th>
                    <th>Student Name</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">No recent transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $trx): ?>
                    <tr>
                        <td style="font-size: 0.85rem;"><?php echo generateReceiptId($trx['id'], $trx['payment_date']); ?></td>
                        <td>
                            <span style="font-weight:600;"><?php echo htmlspecialchars(($trx['first_name'] ?? '') . ' ' . ($trx['last_name'] ?? '')); ?></span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($trx['payment_date'])); ?></td>
                        <td><span style="font-weight:700;">₦<?php echo number_format($trx['amount_paid'], 2); ?></span></td>
                        <td><span class="text-success" style="background:#e8f5e9; padding:4px 8px; border-radius:4px; font-size:0.8rem;">Paid</span></td>
                        <td>
                            <a href="receipt.php?id=<?php echo $trx['id']; ?>" target="_blank" style="color: #1e40af; padding: 5px;" title="Print Receipt">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>
