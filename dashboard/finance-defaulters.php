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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nskblue: '#1e40af',
                        nsklightblue: '#3b82f6',
                        nsknavy: '#1e3a8a',
                        nskgold: '#f59e0b',
                        nsklight: '#f0f9ff',
                        nskgreen: '#10b981',
                        nskred: '#ef4444'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8fafc;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include 'sidebar.php'; ?>

<main class="main-content min-h-screen">
    <div class="content-body p-6">
        
        <div class="page-title-box mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Fee Defaulters List</h1>
            <p class="text-gray-600 mt-1">Students with outstanding fee balances at <strong class="text-blue-900">Northland Schools Kano</strong></p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Total Defaulters</div>
                <div class="text-2xl font-bold text-orange-600 mb-1"><?= $total_defaulters ?></div>
                <div class="text-sm text-orange-600"><i class="fas fa-exclamation-triangle"></i> Students with debt</div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <div class="text-sm text-gray-600 mb-2">Total Outstanding</div>
                <div class="text-2xl font-bold text-red-600 mb-1">₦<?= number_format($total_outstanding, 2) ?></div>
                <div class="text-sm text-red-600"><i class="fas fa-arrow-down"></i> Unpaid fees</div>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="bg-white p-6 rounded-lg shadow-sm border mb-8">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Filter Defaulters</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Academic Session</label>
                    <select name="session" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Sessions</option>
                        <?php foreach ($sessions as $session): ?>
                        <option value="<?= $session['id'] ?>" <?= $session_filter == $session['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($session['session_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Term</label>
                    <select name="term" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $term): ?>
                        <option value="<?= $term['id'] ?>" <?= $term_filter == $term['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($term['term_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= $class_filter == $class['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">Apply Filter</button>
                    <a href="?" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors">Reset</a>
                </div>
            </div>
        </form>

        <!-- Defaulters Table -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Defaulters List (<?= $total_defaulters ?>)</h3>
                <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-print mr-2"></i> Print Report
                </button>
            </div>
            
            <?php if (empty($defaulters)): ?>
            <div class="text-center py-16 px-6">
                <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Great! No Defaulters Found</h3>
                <p class="text-gray-600">All students have paid their fees for the selected period.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Due</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($defaulters as $index => $student): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-700"><?= $index + 1 ?></td>
                            <td class="px-6 py-4 font-semibold text-gray-900"><?= htmlspecialchars($student['student_id']) ?></td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($student['admission_number']) ?></div>
                            </td>
                            <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($student['class_name']) ?></td>
                            <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900">₦<?= number_format($student['total_due'], 2) ?></td>
                            <td class="px-6 py-4 text-right text-green-600">₦<?= number_format($student['total_paid'], 2) ?></td>
                            <td class="px-6 py-4 text-right font-bold text-red-600">₦<?= number_format($student['balance'], 2) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Owing
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-red-50">
                        <tr class="font-bold">
                            <td colspan="7" class="px-6 py-4 text-right text-gray-900">Total Outstanding:</td>
                            <td class="px-6 py-4 text-right text-red-600 text-lg">₦<?= number_format($total_outstanding, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>

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
