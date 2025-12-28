<?php
require_once '../auth-check.php';

// Allow both admin and accountant to access
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'accountant'])) {
    header('Location: ../login-form.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get current academic session and term
$current_session = $conn->query("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$current_term = $conn->query("SELECT * FROM terms WHERE is_current = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Get filter parameters
$session_filter = $_GET['session'] ?? ($current_session['id'] ?? '');
$term_filter = $_GET['term'] ?? ($current_term['id'] ?? '');
$class_filter = $_GET['class'] ?? '';

// Build query to get students with outstanding balances
$where_conditions = ["s.status = 'active'"];
$params = [];

if (!empty($session_filter)) {
    $where_conditions[] = "fs.academic_session_id = ?";
    $params[] = $session_filter;
}

if (!empty($term_filter)) {
    $where_conditions[] = "fs.term_id = ?";
    $params[] = $term_filter;
}

if (!empty($class_filter)) {
    $where_conditions[] = "s.class_id = ?";
    $params[] = $class_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// Get defaulters - students who owe fees
$defaulters_query = $conn->prepare("
    SELECT 
        s.id,
        s.student_id,
        s.admission_number,
        u.first_name,
        u.last_name,
        u.phone,
        c.class_name,
        SUM(fs.amount) as total_due,
        COALESCE(SUM(p.amount_paid), 0) as total_paid,
        (SUM(fs.amount) - COALESCE(SUM(p.amount_paid), 0)) as balance
    FROM students s
    INNER JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN fee_structure fs ON s.class_id = fs.class_id
    LEFT JOIN payments p ON s.id = p.student_id AND fs.id = p.fee_structure_id
    WHERE $where_clause
    GROUP BY s.id, s.student_id, s.admission_number, u.first_name, u.last_name, u.phone, c.class_name
    HAVING balance > 0
    ORDER BY balance DESC
");
$defaulters_query->execute($params);
$defaulters = $defaulters_query->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_defaulters = count($defaulters);
$total_outstanding = array_sum(array_column($defaulters, 'balance'));

// Get all sessions for filter
$sessions = $conn->query("SELECT * FROM academic_sessions ORDER BY session_name DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get all terms for filter
$terms = $conn->query("SELECT * FROM terms ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Get all classes for filter
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name")->fetchAll(PDO::FETCH_ASSOC);

// Page title and user info
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Defaulters - Northland Schools</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="content-body" style="padding: 30px;">
        
        <div class="page-title-box" style="margin-bottom: 30px;">
            <h1 class="page-title">Fee Defaulters List</h1>
            <p style="color: var(--text-light); margin-top: 5px;">Students with outstanding fee balances at <strong style="color: var(--brand-navy);">Northland Schools Kano</strong></p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card orange" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Total Defaulters</span>
                <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: #f59e0b; margin-bottom: 5px;"><?= $total_defaulters ?></span>
                <span class="trend" style="font-size: 0.85rem; color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Students with debt</span>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <span class="label" style="display: block; color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">Total Outstanding</span>
                <span class="value" style="display: block; font-size: 2rem; font-weight: bold; color: #ef4444; margin-bottom: 5px;">₦<?= number_format($total_outstanding, 2) ?></span>
                <span class="trend" style="font-size: 0.85rem; color: #ef4444;"><i class="fas fa-arrow-down"></i> Unpaid fees</span>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h4 style="margin-bottom: 15px; color: var(--brand-navy);">Filter Defaulters</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Academic Session</label>
                    <select name="session" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <option value="">All Sessions</option>
                        <?php foreach ($sessions as $session): ?>
                        <option value="<?= $session['id'] ?>" <?= $session_filter == $session['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($session['session_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Term</label>
                    <select name="term" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $term): ?>
                        <option value="<?= $term['id'] ?>" <?= $term_filter == $term['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($term['term_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 500; margin-bottom: 8px;">Class</label>
                    <select name="class" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= $class_filter == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="?" class="btn" style="border: 1px solid #e5e7eb; text-decoration: none;">Reset</a>
                </div>
            </div>
        </form>

        <!-- Defaulters Table -->
        <div class="table-container" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Defaulters List (<?= $total_defaulters ?>)</h3>
                <button onclick="window.print()" class="btn" style="border: 1px solid #e5e7eb;">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <?php if (empty($defaulters)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 15px;"></i>
                <h3 style="margin-bottom: 10px;">Great! No Defaulters Found</h3>
                <p>All students have paid their fees for the selected period.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                            <th style="padding: 12px; text-align: left;">#</th>
                            <th style="padding: 12px; text-align: left;">Student ID</th>
                            <th style="padding: 12px; text-align: left;">Name</th>
                            <th style="padding: 12px; text-align: left;">Class</th>
                            <th style="padding: 12px; text-align: left;">Phone</th>
                            <th style="padding: 12px; text-align: right;">Total Due</th>
                            <th style="padding: 12px; text-align: right;">Paid</th>
                            <th style="padding: 12px; text-align: right;">Balance</th>
                            <th style="padding: 12px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($defaulters as $index => $student): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px;"><?= $index + 1 ?></td>
                            <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars($student['student_id']) ?></td>
                            <td style="padding: 12px;">
                                <div style="font-weight: 600;"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                <div style="font-size: 0.85rem; color: #6b7280;"><?= htmlspecialchars($student['admission_number']) ?></div>
                            </td>
                            <td style="padding: 12px;"><?= htmlspecialchars($student['class_name']) ?></td>
                            <td style="padding: 12px; color: #6b7280;"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: 600;">₦<?= number_format($student['total_due'], 2) ?></td>
                            <td style="padding: 12px; text-align: right; color: #10b981;">₦<?= number_format($student['total_paid'], 2) ?></td>
                            <td style="padding: 12px; text-align: right; font-weight: 700; color: #ef4444;">₦<?= number_format($student['balance'], 2) ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; background: #fef2f2; color: #ef4444;">
                                    <i class="fas fa-exclamation-circle"></i> Owing
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #fef2f2; font-weight: 700;">
                            <td colspan="7" style="padding: 15px; text-align: right;">Total Outstanding:</td>
                            <td style="padding: 15px; text-align: right; color: #ef4444; font-size: 1.1rem;">₦<?= number_format($total_outstanding, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
@media print {
    .btn, form, .sidebar, .page-title-box p {
        display: none !important;
    }
    
    .content-body {
        padding: 20px !important;
    }
    
    table {
        font-size: 12px;
    }
}
</style>

</body>
</html>
