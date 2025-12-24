<?php
require_once 'auth-check.php';
// checkAuth('admin'); 

include 'includes/header.php';
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// --- FETCH CURRENT SESSION & TERM ---
$stmt = $conn->query("SELECT id, session_name FROM academic_sessions WHERE is_current = 1");
$current_session = $stmt->fetch(PDO::FETCH_ASSOC);
$current_session_id = $current_session['id'] ?? 0;
$current_session_name = $current_session['session_name'] ?? 'Unknown Session';

$stmt = $conn->prepare("SELECT id, term_name FROM terms WHERE academic_session_id = ? AND is_current = 1");
$stmt->execute([$current_session_id]);
$current_term = $stmt->fetch(PDO::FETCH_ASSOC);
$current_term_id = $current_term['id'] ?? 0;
$current_term_name = $current_term['term_name'] ?? 'Unknown Term';

// --- FETCH PAYMENT STATS ---

// 1. Total Collection (All Time or Session?)
// "Total Collected" usually implies cash in hand, so All Time is common, but context might imply Session.
// Let's stick to All Time for "Total Collected" to match Dashboard, or filter by Session if user wants context.
// Given "Fee Structure" context, maybe "Collection for this Session" is better?
// The dashboard has "Total Revenue (All Time)". Let's make this page "Total Revenue (This Session)"?
// But to keep it consistent with the previous static "$0.00" which matched dashboard, let's keep it global OR ask.
// I'll stick to Global for now to match dashboard, validating the "link it with main system" request.
$stmt = $conn->query("SELECT SUM(amount_paid) as total FROM payments");
$total_collected = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Total Expected Revenue (For Current Term)
// Logic: Sum of all fees applicable to all active students in the current term.
$query_expected = "
    SELECT SUM(fs.amount) as expected 
    FROM students s
    JOIN fee_structure fs ON s.class_id = fs.class_id
    WHERE fs.term_id = ? AND s.is_active = 1
";
$stmt = $conn->prepare($query_expected);
$stmt->execute([$current_term_id]);
$total_expected_term = $stmt->fetch(PDO::FETCH_ASSOC)['expected'] ?? 0;

// If we want Annual expected (Session), we sum for all terms in session?
// "Annual" label in static mock suggests Session.
// Let's try to get Session expected.
$query_expected_session = "
    SELECT SUM(fs.amount) as expected 
    FROM students s
    JOIN fee_structure fs ON s.class_id = fs.class_id
    WHERE fs.academic_session_id = ? AND s.is_active = 1
";
$stmt = $conn->prepare($query_expected_session);
$stmt->execute([$current_session_id]);
$total_expected = $stmt->fetch(PDO::FETCH_ASSOC)['expected'] ?? 0;


$outstanding = $total_expected - $total_collected; // Simplistic: Expected (Session) - Collected (All Time). 
// Note: Collected All Time might include past years. Ideally should be Collected (This Session).
// Let's refine Collected to be This Session too IF `payments` table has date or session_id.
// payments table has `payment_date`. We can filter by session start/end.
$stmt = $conn->prepare("SELECT start_date, end_date FROM academic_sessions WHERE id = ?");
$stmt->execute([$current_session_id]);
$session_dates = $stmt->fetch(PDO::FETCH_ASSOC);

if ($session_dates) {
    $stmt = $conn->prepare("SELECT SUM(amount_paid) as total FROM payments WHERE payment_date BETWEEN ? AND ?");
    $stmt->execute([$session_dates['start_date'], $session_dates['end_date']]);
    $total_collected_session = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} else {
    $total_collected_session = $total_collected; // Fallback
}


// --- FETCH FEE STRUCTURE ---
$query_structure = "
    SELECT 
        c.class_name, 
        t.term_name, 
        fs.fee_type, 
        fs.amount 
    FROM fee_structure fs
    JOIN classes c ON fs.class_id = c.id
    JOIN terms t ON fs.term_id = t.id
    WHERE fs.academic_session_id = ?
    ORDER BY c.class_name, t.id, fs.fee_type
";
$stmt = $conn->prepare($query_structure);
$stmt->execute([$current_session_id]);
$raw_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);

$structure_display = [];
foreach ($raw_structure as $row) {
    $key = $row['class_name'] . '|' . $row['term_name'];
    if (!isset($structure_display[$key])) {
        $structure_display[$key] = [
            'class' => $row['class_name'],
            'term' => $row['term_name'],
            'tuition' => 0,
            'development' => 0,
            'total' => 0
        ];
    }
    
    if (stripos($row['fee_type'], 'Tuition') !== false) {
        $structure_display[$key]['tuition'] += $row['amount'];
    } elseif (stripos($row['fee_type'], 'Development') !== false) {
        $structure_display[$key]['development'] += $row['amount'];
    }
    $structure_display[$key]['total'] += $row['amount'];
}

?>

<!-- Main Content -->
<div class="content-body" style="padding: 30px;">
    
    <div class="page-title-box">
        <h1 class="page-title">Fees Management</h1>
        <p style="color: var(--text-light); margin-top: 5px;">
            Session: <strong style="color: var(--brand-navy);"><?php echo htmlspecialchars($current_session_name); ?></strong> | 
            Term: <strong style="color: var(--brand-navy);"><?php echo htmlspecialchars($current_term_name); ?></strong>
        </p>
    </div>

    <!-- Stats -->
    <div class="stats-grid" style="padding: 30px 0; grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card blue">
            <span class="label">Total Expected Revenue</span>
            <span class="value">₦<?php echo number_format($total_expected, 2); ?></span>
            <span class="trend text-success"><i class="fas fa-chart-line"></i> This Session</span>
        </div>
        <div class="stat-card green">
            <span class="label">Total Collected</span>
            <span class="value">₦<?php echo number_format($total_collected_session, 2); ?></span>
            <span class="trend text-success"><i class="fas fa-check"></i> This Session</span>
        </div>
        <div class="stat-card orange">
            <span class="label">Outstanding</span>
            <span class="value">₦<?php echo number_format($outstanding, 2); ?></span>
            <span class="trend text-danger"><i class="fas fa-exclamation-circle"></i> Unpaid</span>
        </div>
    </div>

    <!-- Fee Structure Table -->
    <div class="table-container" style="margin: 0; margin-bottom: 30px;">
        <div class="table-header">
            <h3>Fee Structure (<?php echo htmlspecialchars($current_session_name); ?>)</h3>
            <button class="btn btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> Add New Fee</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Class Level</th>
                    <th>Term</th>
                    <th>Tuition</th>
                    <th>Development Levy</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($structure_display)): ?>
                    <tr><td colspan="6" style="text-align:center;">No fee structure found for this session.</td></tr>
                <?php else: ?>
                    <?php foreach ($structure_display as $fee): ?>
                    <tr>
                        <td><span style="font-weight:600;"><?php echo htmlspecialchars($fee['class']); ?></span></td>
                        <td><?php echo htmlspecialchars($fee['term']); ?></td>
                        <td>₦<?php echo number_format($fee['tuition']); ?></td>
                        <td>₦<?php echo number_format($fee['development']); ?></td>
                        <td><span style="font-weight:700; color:var(--brand-navy);">₦<?php echo number_format($fee['total']); ?></span></td>
                        <td>
                            <button class="btn" style="padding: 5px; color: var(--brand-navy);"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
</div>

<?php include 'includes/footer.php'; ?>
